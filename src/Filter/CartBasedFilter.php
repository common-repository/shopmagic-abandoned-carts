<?php

namespace WPDesk\ShopMagicCart\Filter;

use WPDesk\ShopMagic\Extensions\Elements\Groups;
use WPDesk\ShopMagic\Workflow\Filter\FilterUsingComparisonTypes;
use WPDesk\ShopMagicCart\Cart\BaseCart;

/**
 * Common code for filters using Abandoned Carts.
 */
abstract class CartBasedFilter extends FilterUsingComparisonTypes {

	final public function get_group_slug(): string {
		return Groups::CART;
	}

	final public function get_required_data_domains(): array {
		return [ BaseCart::class ];
	}

	final protected function get_cart(): BaseCart {
		return $this->resources->get( BaseCart::class );
	}
}
