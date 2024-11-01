<?php

declare( strict_types=1 );

namespace WPDesk\ShopMagicCart\Cart;

use ShopMagicVendor\Psr\Log\LoggerInterface;
use ShopMagicVendor\Psr\Log\NullLogger;
use WPDesk\ShopMagic\Customer\CustomerFactory;
use WPDesk\ShopMagic\Customer\CustomerRepository;
use WPDesk\ShopMagic\Customer\NullCustomer;
use WPDesk\ShopMagic\Exception\CustomerNotFound;

final class CartFactory {
	const STATUS_NEW       = 'new';
	const STATUS_ACTIVE    = 'active';
	const STATUS_ABANDONED = 'abandoned';
	const STATUS_RECOVERED = 'recovered';
	const STATUS_ORDERED   = 'ordered';

	/** @var LoggerInterface */
	private $logger;

	/** @var CustomerRepository */
	private $customer_repository;

	public function __construct( CustomerRepository $customer_repository, LoggerInterface $logger = null ) {
		$this->customer_repository = $customer_repository;
		$this->logger = $logger ?? new NullLogger();
	}

	public function create_item( array $data ): object {
		$customer_id = $data['user_id'] ?? CustomerFactory::id_to_guest_id( $data['guest_id'] );
		try {
			$customer = $this->customer_repository->find( $customer_id );
		} catch ( CustomerNotFound $e ) {
			$customer = new NullCustomer();
		}

		$status = $data['status'];

		if ( $status === Cart::ACTIVE || $status === Cart::FRESH ) {
			$cart_class = ActiveCart::class;
		} elseif ( $status === Cart::SUBMITTED ) {
			$cart_class = SubmittedCart::class;
		} elseif ( $status === Cart::ABANDONED ) {
			$cart_class = AbandonedCart::class;
		} elseif ( $status === Cart::RECOVERED || $status === Cart::ORDERED ) {
			$cart_class = OrderedCart::class;
		} else {
			$cart_class = NullCart::class;
			$this->logger->error(sprintf('Could not create cart for status: %s', $status));
		}

		$cart = new $cart_class(
			(int) $data['id'],
			$data['status'],
			$customer,
			new \DateTimeImmutable( $data['last_modified'] ),
			new \DateTimeImmutable( $data['created'] ),
			json_decode( $data['items'], true ),
			json_decode( $data['coupons'], true ),
			json_decode( $data['fees'], true ),
			(float) $data['shipping_tax_total'],
			(float) $data['shipping_total'],
			(float) $data['total'],
			$data['token'],
			$data['currency']
		);

		return apply_filters( 'shopmagic/carts/create_cart', $cart );
	}

	public function create_null() {
		return apply_filters(
			'shopmagic/carts/create_cart',
			new ActiveCart(
				null,
				Cart::FRESH,
				new NullCustomer(),
				new \DateTimeImmutable(),
				new \DateTimeImmutable(),
				[],
				[],
				[],
				0,
				0,
				0,
				'',
				get_woocommerce_currency()
			)
		);
	}
}
