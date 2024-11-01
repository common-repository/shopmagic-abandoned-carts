<?php

declare( strict_types=1 );

namespace WPDesk\ShopMagicCart\Admin;

use WPDesk\ShopMagicCart\Database\CartRepository;

class Statistics implements \WPDesk\ShopMagic\Helper\Hookable {

	/** @var CartRepository */
	private $repository;

	public function __construct( CartRepository $repository ) {
		$this->repository = $repository;
	}

	public function hooks(): void {
		add_filter( 'shopmagic/core/statistics/top_stats', function ( array $stats ): array {
			return $this->add_cart_stats( $stats );
		} );
	}

	private function add_cart_stats( array $stats ): array {
		$stats[1]['value'] = $this->repository->get_count(['status' => 'active']);

		return $stats;
	}
}
