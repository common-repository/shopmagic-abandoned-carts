<?php

namespace WPDesk\ShopMagicCart\Filter;

use WPDesk\ShopMagic\Workflow\Filter\ComparisonType\ComparisonType;
use WPDesk\ShopMagic\Workflow\Filter\ComparisonType\DateType;

final class CartDateCreated extends CartBasedFilter {
	public function get_name(): string {
		return __( 'Cart - Date Created', 'shopmagic-abandoned-carts' );
	}

	public function get_description(): string {
		return esc_html__('Run automation if date of registering customer cart matches the rule.', 'shopmagic-abandoned-carts');
	}

	public function passed(): bool {
		return $this->get_type()->passed(
			$this->fields_data->get( DateType::VALUE_KEY ),
			$this->fields_data->get( DateType::CONDITION_KEY ),
			$this->get_cart()->get_created()
		);
	}

	protected function get_type(): ComparisonType {
		return new DateType();
	}
}
