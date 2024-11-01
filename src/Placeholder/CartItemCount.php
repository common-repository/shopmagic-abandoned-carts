<?php

namespace WPDesk\ShopMagicCart\Placeholder;

final class CartItemCount extends CartBasedPlaceholder {

	public function get_slug(): string {
		return 'item_count';
	}

	public function get_description(): string {
		return __( 'Displays the count of all items currently stored in cart', 'shopmagic-abandoned-carts' );
	}

	public function value( array $parameters ): string {
		return (string) $this->get_cart()->get_products_quantity_count();
	}
}
