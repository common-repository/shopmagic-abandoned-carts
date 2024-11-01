<?php

declare( strict_types=1 );

namespace WPDesk\ShopMagicCart;

use ShopMagicCartVendor\WPDesk\Notice\Notice;
use ShopMagicCartVendor\WPDesk\PluginBuilder\Plugin\AbstractPlugin;
use ShopMagicCartVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use ShopMagicCartVendor\WPDesk\PluginBuilder\Plugin\HookableCollection;
use ShopMagicCartVendor\WPDesk\PluginBuilder\Plugin\HookableParent;
use ShopMagicCartVendor\WPDesk_Plugin_Info;
use ShopMagicVendor\DI\ContainerBuilder;
use ShopMagicVendor\WPDesk\Migrations\WpdbMigrator;
use WPDesk\ShopMagicCart\Admin\AnalyticsController;
use WPDesk\ShopMagicCart\Controller\CartsController;
use WPDesk\ShopMagicCart\TestData\CartTestProvider;
use WPDesk\ShopMagicCart\migrations\Version_10;
use WPDesk\ShopMagic\Components\HookProvider\Conditional;
use WPDesk\ShopMagic\Components\HookProvider\HookProvider;
use WPDesk\ShopMagic\Components\Routing\Argument;
use WPDesk\ShopMagic\Components\Routing\ArgumentCollection;
use WPDesk\ShopMagic\Components\Routing\IntArgument;
use WPDesk\ShopMagic\Components\Routing\RestRoutesRegistry;
use WPDesk\ShopMagic\Integration\ExternalPluginsAccess;

/**
 * Main plugin class. The most important flow decisions are made here.
 */
final class Plugin extends AbstractPlugin implements HookableCollection {
	use HookableParent;

	public function __construct( WPDesk_Plugin_Info $plugin_info ) {
		parent::__construct( $plugin_info ); // @phpstan-ignore-line

		$this->plugin_url       = $this->plugin_info->get_plugin_url();
		$this->plugin_namespace = $this->plugin_info->get_text_domain();
		$this->docs_url         = 'https://docs.shopmagic.app/?utm_source=user-site&utm_medium=quick-link&utm_campaign=docs';
		$this->support_url      = 'https://shopmagic.app/support/?utm_source=user-site&utm_medium=quick-link&utm_campaign=support';
	}

	public function hooks(): void {
		parent::hooks();

		add_action(
			'shopmagic/core/initialized/v2',
			function ( ExternalPluginsAccess $core ) {
				$shopmagic_version = $core->get_version();
				if ( version_compare( $shopmagic_version, '5', '>=' ) ) {
					new Notice(
						sprintf(
						// translators: %s ShopMagic version.
							__(
								'This version of ShopMagic Abandoned Carts plugin is not compatible with ShopMagic %s. Please upgrade ShopMagic Abandoned Carts to the newest version.',
								'shopmagic-abandoned-carts'
							),
							$shopmagic_version
						)
					);

					return;
				}

				$container = $core->get_container();
				$builder   = new ContainerBuilder();
				$builder->wrapContainer( $container );
				$builder->useAutowiring( false );
				$builder->addDefinitions(
					[
						'shopmagic.abandonedCarts.pluginUrl' => $this->plugin_info->get_plugin_url(),
						'shopmagic.abandonedCarts.version' => $this->plugin_info->get_version(),
					],
					$this->plugin_info->get_plugin_dir() . '/config/services.inc.php'
				);
				$carts_container = $builder->build();

				$container->addContainer( $carts_container );

				foreach ( $container->get( 'hookable.init' ) as $item ) {
					if ( $item instanceof Conditional && ! $item::is_needed() ) {
						continue;
					}
					if ( $item instanceof Hookable || $item instanceof HookProvider ) {
						$item->hooks();
					}
				}

				add_action(
					'shopmagic/core/rest/init',
					static function () use ( $container, $core ) {
						$routes   = $core->get_routes_configurator();
						$registry = RestRoutesRegistry::with_defaults( $routes, $container );
						$registry->setLogger( $core->get_logger() );

						$routes->add( '/carts' )
								->args(
									new ArgumentCollection(
										( new IntArgument( 'page' ) )
											->minimum( 1 )
											->default( 1 ),
										( new IntArgument( 'pageSize' ) )
											->default( 20 )
											->minimum( 1 )
											->maximum( 100 ),
										( new Argument( 'filters' ) )
											->type( 'object' )
									)
								)
								->controller( [ CartsController::class, 'index' ] );

						$routes->add( '/carts/(?P<id>[\d]+)' )
								->args(
									new ArgumentCollection(
										new IntArgument( 'id' )
									)
								)
								->methods( 'DELETE' )
								->controller( [ CartsController::class, 'delete' ] );

						$routes->add( '/analytics/carts/aggregate' )
								->controller( [ AnalyticsController::class, 'carts' ] );

						$routes->add( '/analytics/carts/top-stats' )
								->controller( [ AnalyticsController::class, 'top_statistics' ] );

						$routes->add( '/carts/count' )
								->controller( [ CartsController::class, 'count' ] );

						$registry->hooks();
					}
				);

				WpdbMigrator::from_classes( [ Version_10::class ], 'shopmagic_cart' )->migrate();

				$core->add_extension( new CartExtension( $container ) );

				$core->add_test_provider( $container->get( CartTestProvider::class ) );

				$core->append_setting_tab( new Admin\Settings() );
			},
			10
		);
	}
}
