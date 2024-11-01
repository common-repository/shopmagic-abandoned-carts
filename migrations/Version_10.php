<?php

declare( strict_types=1 );

namespace WPDesk\ShopMagicCart\migrations;

use WPDesk\ShopMagicCart\DatabaseTable;

final class Version_10 extends \ShopMagicVendor\WPDesk\Migrations\AbstractMigration {
	public function up(): bool {
		$table_name      = DatabaseTable::cart();
		$charset_collate = $this->wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			    id                 bigint auto_increment primary key,
			    status             varchar(100) default 'new' not null,
			    user_id            bigint       		      null,
			    guest_id           bigint                     null,
			    last_modified      datetime                   not null,
			    created            datetime                   not null,
			    items              longtext                   not null,
			    coupons            longtext                   not null,
			    fees               longtext                   not null,
			    shipping_tax_total double          default 0  not null,
			    shipping_total     double          default 0  not null,
			    total              double          default 0  not null,
			    token              varchar(32)     default '' not null,
			    currency           varchar(8)      default '' not null,

			    KEY(token),
			    KEY(last_modified),
			    KEY(total),
			    KEY(status, last_modified)
		) {$charset_collate};";

		return (bool) $this->wpdb->query( $sql ); // phpcs:ignore WordPress.DB
	}
}
