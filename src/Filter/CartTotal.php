<?php

namespace WPDesk\ShopMagicCart\Filter;

use WPDesk\ShopMagic\Workflow\Filter\ComparisonType\ComparisonType;
use WPDesk\ShopMagic\Workflow\Filter\ComparisonType\FloatType;


final class CartTotal extends CartBasedFilter {
	public function get_name(): string {
		return __( 'Cart - Total', 'shopmagic-abandoned-carts' );
	}

	public function get_description(): string {
		return esc_html__('Run automation if cart total value matches the rule.', 'shopmagic-abandoned-carts');
	}

	public function passed(): bool {
		return $this->get_type()->passed(
			$this->fields_data->get( FloatType::VALUE_KEY ),
			$this->fields_data->get( FloatType::CONDITION_KEY ),
			$this->get_cart()->get_total()
		);
	}

	protected function get_type(): ComparisonType {
		return new FloatType();
	}
}
