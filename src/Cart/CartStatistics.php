<?php

declare( strict_types=1 );

namespace WPDesk\ShopMagicCart\Cart;

// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
use WPDesk\ShopMagicCart\DatabaseTable;

final class CartStatistics {

	/** @var \wpdb */
	private $wpdb;

	public function __construct( \wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	public function get_recoverable_carts_count(): int {
		$sql = 'SELECT COUNT(*) FROM ' . DatabaseTable::cart() . ' WHERE status = %s';

		return (int) $this->wpdb->get_var( $this->wpdb->prepare( $sql, CartFactory::STATUS_ABANDONED ) );
	}

	public function get_recoverable_revenue(): float {
		$sql = 'SELECT SUM(total) FROM ' . DatabaseTable::cart() . ' WHERE status = %s';

		return (float) $this->wpdb->get_var( $this->wpdb->prepare( $sql, CartFactory::STATUS_ABANDONED ) );
	}

	public function get_recovered_carts_count(): int {
		$sql = 'SELECT COUNT(*) FROM ' . DatabaseTable::cart() . ' WHERE status = %s';

		return (int) $this->wpdb->get_var( $this->wpdb->prepare( $sql, CartFactory::STATUS_RECOVERED ) );
	}

	public function get_recovered_revenue(): float {
		$sql = 'SELECT SUM(total) FROM ' . DatabaseTable::cart() . ' WHERE status = %s';

		return (float) $this->wpdb->get_var( $this->wpdb->prepare( $sql, CartFactory::STATUS_RECOVERED ) );
	}
}
// phpcs:enable
