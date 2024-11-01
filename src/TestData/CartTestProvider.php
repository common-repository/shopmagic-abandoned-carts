<?php

declare( strict_types=1 );

namespace WPDesk\ShopMagicCart\TestData;

use DateTimeImmutable;
use WPDesk\ShopMagicCart\Cart\AbandonedCart;
use WPDesk\ShopMagicCart\Cart\BaseCart;
use WPDesk\ShopMagicCart\Database\CartRepository;
use WPDesk\ShopMagic\Components\Database\Abstraction\EntityNotFound;
use WPDesk\ShopMagic\Customer\NullCustomer;
use WPDesk\ShopMagic\DataSharing\DataProvider;
use WPDesk\ShopMagic\Workflow\Event\DataLayer;

/**
 * Can inject test data for TestDataProvider
 */
final class CartTestProvider implements DataProvider {

	/** @var CartRepository */
	private $repository;

	public function __construct( CartRepository $repository ) {
		$this->repository = $repository;
	}

	public function get_provided_data_domains(): array {
		return [ BaseCart::class ];
	}

	public function get_provided_data(): DataLayer {
		return new DataLayer( [ BaseCart::class => $this->get_cart() ] );
	}

	private function get_cart(): BaseCart {
		try {
			return $this->repository->find_one_by( [], [ 'id' => 'DESC' ] );
		} catch ( EntityNotFound $e ) {
			return $this->get_cart_stub();
		}
	}

	private function get_cart_stub(): BaseCart {
		return new AbandonedCart(
			1,
			'abandoned',
			new NullCustomer(),
			new DateTimeImmutable(),
			new DateTimeImmutable(),
			[
				[
					'quantity'      => 1,
					'line_subtotal' => 123.3,
					'key'           => 'ade',
					'product_id'    => wc_get_products(
						[
							'limit'  => 1,
							'return' => 'ids',
						]
					)[0],
				],
			],
			[],
			[],
			0,
			0,
			123.3,
			'test-token',
			'USD'
		);
	}
}
