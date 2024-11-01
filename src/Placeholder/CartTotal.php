<?php

declare( strict_types=1 );

namespace WPDesk\ShopMagicCart\Placeholder;

final class CartTotal extends CartBasedPlaceholder {

	public function get_slug(): string {
		return 'total';
	}

	public function get_description(): string {
		return __( 'Displays total value of items in cart.', 'shopmagic-abandoned-carts' );
	}

	public function value( array $parameters ): string {
		return wc_price( $this->get_cart()->get_total() );
	}
}
