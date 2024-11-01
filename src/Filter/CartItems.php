<?php

namespace WPDesk\ShopMagicCart\Filter;

use WPDesk\ShopMagic\Workflow\Filter\ComparisonType\ComparisonType;
use WPDesk\ShopMagic\Workflow\Filter\ComparisonType\ProductSelectType;


final class CartItems extends CartBasedFilter {
	public function get_name(): string {
		return __( 'Cart - Items', 'shopmagic-abandoned-carts' );
	}

	public function get_description(): string {
		return esc_html__('Run automation if products in cart matches the rule.', 'shopmagic-abandoned-carts');
	}

	public function passed(): bool {
		$items        = $this->get_cart()->get_items();
		$products_ids = [];

		foreach ( $items as $item ) {
			$products_ids[] = $item->get_product_id();
			$products_ids[] = $item->get_variation_id();
		}

		return $this->get_type()->passed(
			$this->fields_data->get( ProductSelectType::VALUE_KEY ),
			$this->fields_data->get( ProductSelectType::CONDITION_KEY ),
			$products_ids
		);
	}

	protected function get_type(): ComparisonType {
		return new ProductSelectType();
	}
}
