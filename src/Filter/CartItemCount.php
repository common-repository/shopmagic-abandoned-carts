<?php

namespace WPDesk\ShopMagicCart\Filter;

use WPDesk\ShopMagic\Workflow\Filter\ComparisonType\ComparisonType;
use WPDesk\ShopMagic\Workflow\Filter\ComparisonType\IntegerType;


final class CartItemCount extends CartBasedFilter {
	public function get_name(): string {
		return __( 'Cart - Item Count', 'shopmagic-abandoned-carts' );
	}

	public function get_description(): string {
		return esc_html__('Run automation if amount of products in cart matches the rule.', 'shopmagic-abandoned-carts');
	}

	public function passed(): bool {
		return $this->get_type()->passed(
			$this->fields_data->get( IntegerType::VALUE_KEY ),
			$this->fields_data->get( IntegerType::CONDITION_KEY ),
			$this->get_cart()->get_products_quantity_count()
		);
	}

	protected function get_type(): ComparisonType {
		return new IntegerType();
	}
}
