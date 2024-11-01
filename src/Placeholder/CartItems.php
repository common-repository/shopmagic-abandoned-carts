<?php

namespace WPDesk\ShopMagicCart\Placeholder;

use WPDesk\ShopMagic\Workflow\Placeholder\Helper\PlaceholderUTMBuilder;
use WPDesk\ShopMagic\Workflow\Placeholder\TemplateRendererForPlaceholders;

final class CartItems extends CartBasedPlaceholder {

	/** @var TemplateRendererForPlaceholders */
	private $renderer;

	/** @var PlaceholderUTMBuilder */
	private $utm_builder;

	public function __construct( TemplateRendererForPlaceholders $renderer ) {
		$this->renderer    = $renderer;
		$this->utm_builder = new PlaceholderUTMBuilder();
	}

	public function get_slug(): string {
		return 'items';
	}

	public function get_description(): string {
		return __( 'Displays the products from cart.', 'shopmagic-abandoned-carts' ) .
		       '<br>' .
		       $this->utm_builder->get_description();
	}

	public function get_supported_parameters( $values = null ): array {
		return array_merge( $this->utm_builder->get_utm_fields(), $this->renderer->get_template_selector_field() );
	}

	public function value( array $parameters ): string {
		$items         = $this->is_cart_provided() ? $this->get_cart()->get_items() : [];
		$products      = [];
		$product_names = [];

		foreach ( $items as $item ) {
			try {
				$products[]      = $item->get_product();
				$product_names[] = $item->get_product()->get_name();
			} catch ( \TypeError $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				// Product no longer exists.
			}
		}

		if ( ! empty( $products ) ) {
			return $this->renderer->render(
				$parameters['template'],
				[
					'order_items'   => $items,
					'products'      => $products,
					'product_names' => $product_names,
					'parameters'    => $parameters,
					'utm_builder'   => $this->utm_builder,
				]
			);
		}

		return '';
	}
}
