<?php

declare( strict_types=1 );

namespace WPDesk\ShopMagicCart\Admin;

use WPDesk\ShopMagic\Helper\DateIterator;
use WPDesk\ShopMagicCart\Cart\CartStatistics;
use WPDesk\ShopMagicCart\DatabaseTable;

class AnalyticsController {

	public function carts(): \WP_REST_Response {
		global $wpdb;
		$date_iterator = new DateIterator();
		$table         = DatabaseTable::cart();
		$carts         = $wpdb->get_results( "
			SELECT
				count(*) as count,
				DATE(last_modified) as date
			FROM $table
			GROUP BY last_modified
		",
			ARRAY_A );

		$stats = array_fill_keys( $date_iterator->get_date_labels(), 0 );

		foreach ( $carts as $cart ) {
			if ( isset( $stats[ $cart['date'] ] ) ) {
				$stats[ $cart['date']] = (int) $cart['count'];
			}
		}

		return new \WP_REST_Response( [
			'labels' => array_keys( $stats ),
			'plot'   => array_values( $stats ),
		] );
	}

	public function top_statistics( CartStatistics $statistics ): \WP_REST_Response {
		$order_count     = $statistics->get_recoverable_carts_count();
		$recovered_count = $statistics->get_recovered_carts_count();
		$recovery_rate   = ( $order_count + $recovered_count === 0 )
			? 0
			: round( $recovered_count / ( $order_count + $recovered_count ) * 100,
				2 );

		$stats_values = [
			[
				'name'  => esc_html__( 'Recoverable orders', 'shopmagic-abandoned-carts' ),
				'value' => $order_count,
			],
			[
				'name'  => esc_html__( 'Recoverable revenue', 'shopmagic-abandoned-carts' ),
				'value' => wc_price( $statistics->get_recoverable_revenue() ),
			],
			[
				'name'  => esc_html__( 'Recovered orders', 'shopmagic-abandoned-carts' ),
				'value' => $recovered_count,
			],
			[
				'name'  => esc_html__( 'Recovered revenue', 'shopmagic-abandoned-carts' ),
				'value' => wc_price( $statistics->get_recovered_revenue() ),
			],
			[
				'name'  => esc_html__( 'Recovery rate', 'shopmagic-abandoned-carts' ),
				'value' => $recovery_rate . '%',
			],
		];

		return new \WP_REST_Response( [ 'top_stats' => $stats_values ] );
	}

}
