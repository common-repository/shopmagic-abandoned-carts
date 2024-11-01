<?php

declare( strict_types=1 );

namespace WPDesk\ShopMagicCart\Database;

use WPDesk\ShopMagicCart\Cart\BaseCart;
use WPDesk\ShopMagicCart\DatabaseTable;
use WPDesk\ShopMagic\Components\Database\Abstraction\ObjectManager;

/**
 * @extends ObjectManager<BaseCart>
 */
class CartManager extends ObjectManager {

	protected function get_columns(): array {
		return [
			'id',
			'status',
			'user_id',
			'guest_id',
			'last_modified',
			'created',
			'items',
			'coupons',
			'fees',
			'shipping_tax_total',
			'shipping_total',
			'total',
			'token',
			'currency',
		];
	}

	protected function get_name(): string {
		return DatabaseTable::cart();
	}
}
