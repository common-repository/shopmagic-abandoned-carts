<?php
declare(strict_types=1);

namespace WPDesk\ShopMagicCart\Frontend;

use ShopMagicCartVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use ShopMagicVendor\WPDesk\Forms\Field\CheckboxField;
use ShopMagicVendor\WPDesk\View\Renderer\SimplePhpRenderer;
use ShopMagicVendor\WPDesk\View\Resolver\ChainResolver;
use ShopMagicVendor\WPDesk\View\Resolver\DirResolver;
use ShopMagicVendor\WPDesk\View\Resolver\WPThemeResolver;
use WPDesk\ShopMagic\Frontend\Interceptor\CustomerSessionTracker;
use WPDesk\ShopMagic\Helper\Conditional;
use WPDesk\ShopMagic\Helper\WooCommerceCookies;
use WPDesk\ShopMagicCart\Admin\Settings;

/**
 * Displays and handles frontend modal when exiting cart page.
 */
final class ExitIntent implements Hookable, Conditional {
	const PROCESS_EXIT_INTENT = 'process_exit_intent';
	const POPUP_COOKIE        = 'shopmagic_exit_popup';

	/** @var string */
	private $assets_url;

	/** @var string */
	private $version;

	/** @var CustomerSessionTracker */
	private $tracker;

	public function __construct( CustomerSessionTracker $tracker, string $assets_url, string $version ) {
		$this->tracker    = $tracker;
		$this->assets_url = $assets_url;
		$this->version    = $version;
	}

	public static function is_needed(): bool {
		return filter_var(
			Settings::get_option( Settings::ENABLE_EXIT_POPUP ),
			\FILTER_VALIDATE_BOOLEAN
		);
	}

	/** @return void */
	public function hooks() {
		add_action(
			'wp',
			function () {
				if ( ! $this->should_show() ) {
					return;
				}

				if ( $this->is_test_mode_enabled() ) {
					WooCommerceCookies::set( self::POPUP_COOKIE, 'test' );
				}

				add_action( 'wp_print_footer_scripts', [ $this, 'show_exit_intent_popup' ] );
				add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_script' ] );
			}
		);
		add_action( 'wp_ajax_nopriv_' . self::PROCESS_EXIT_INTENT, [ $this, self::PROCESS_EXIT_INTENT ] );
	}

	private function should_show(): bool {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return false;
		}

		if ( ! self::is_needed() ) {
			return false;
		}

		if ( $this->is_test_mode_enabled() ) {
			return true;
		}

		if ( is_user_logged_in() ) {
			return false;
		}

		if ( WooCommerceCookies::is_set( self::POPUP_COOKIE ) ) {
			return false;
		}

		if ( is_cart() || ( is_checkout() && ! is_wc_endpoint_url( 'order-received' ) ) ) {
			return true;
		}

		return false;
	}

	private function is_test_mode_enabled(): bool {
		return is_user_logged_in() &&
				current_user_can( 'edit_others_posts' ) &&
				filter_var( Settings::get_option( Settings::ENABLE_EXIT_POPUP_TEST ), \FILTER_VALIDATE_BOOLEAN );
	}

	/** @return void */
	public function process_exit_intent() {
		if (
			( ! isset( $_POST['nonce'], $_POST['email'], $_POST['action'] ) ) ||
			( sanitize_text_field( wp_unslash( $_POST['action'] ) ) !== self::PROCESS_EXIT_INTENT ) ||
			( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), self::PROCESS_EXIT_INTENT ) )
		) {
			wp_send_json_error();
		}

		$this->tracker->set_user_email( sanitize_email( wp_unslash( $_POST['email'] ) ) );

		wp_send_json_success();
	}

	/** @return void */
	public function show_exit_intent_popup() {
		$renderer = new SimplePhpRenderer(
			new ChainResolver(
				new WPThemeResolver( 'shopmagic' ),
				new DirResolver( __DIR__ . DIRECTORY_SEPARATOR . 'templates' )
			)
		);

		$renderer->output_render(
			'exit-intent',
			[
				'title'             => Settings::get_option( Settings::EXIT_POPUP_TITLE ) ?: Settings::title_default(),
				'content'           => Settings::get_option( Settings::EXIT_POPUP_CONTENT ) ?: Settings::content_default(),
				'action'            => self::PROCESS_EXIT_INTENT,
				'nonce'             => wp_create_nonce( self::PROCESS_EXIT_INTENT ),
			]
		);
	}

	/** @return void */
	public function enqueue_script() {
		wp_enqueue_style( 'sm-exit-intent', $this->assets_url . '/js/exit-intent.min.css', [], $this->version );

		wp_register_script( 'sm-exit-intent', $this->assets_url . '/js/exit-intent.min.js', [], $this->version, true );

		$ajax_url = add_query_arg( [ 'action' => self::PROCESS_EXIT_INTENT ], admin_url( 'admin-ajax.php' ) );
		wp_add_inline_script(
			'sm-exit-intent',
			"const SMAbandonedCarts = { ajax_url: '$ajax_url' }",
			'before'
		);

		wp_enqueue_script( 'sm-exit-intent' );
	}
}
