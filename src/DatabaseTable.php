<?php
declare( strict_types=1 );

namespace WPDesk\ShopMagicCart;

class DatabaseTable {

	public static function cart(): string {
		global $wpdb;

		return "{$wpdb->prefix}shopmagic_cart";
	}

}
