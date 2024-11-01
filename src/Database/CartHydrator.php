<?php

declare( strict_types=1 );

namespace WPDesk\ShopMagicCart\Database;

use WPDesk\ShopMagic\Components\Database\Abstraction\DAO\ObjectDehydrator;
use WPDesk\ShopMagic\Components\Database\Abstraction\DAO\ObjectHydrator;
use WPDesk\ShopMagic\Customer\CustomerFactory;
use WPDesk\ShopMagic\Customer\CustomerRepository;
use WPDesk\ShopMagic\Customer\NullCustomer;
use WPDesk\ShopMagic\Exception\CustomerNotFound;
use WPDesk\ShopMagic\Helper\WordPressFormatHelper;
use WPDesk\ShopMagicCart\Cart\AbandonedCart;
use WPDesk\ShopMagicCart\Cart\ActiveCart;
use WPDesk\ShopMagicCart\Cart\BaseCart;
use WPDesk\ShopMagicCart\Cart\Cart;
use WPDesk\ShopMagicCart\Cart\NullCart;
use WPDesk\ShopMagicCart\Cart\OrderedCart;
use WPDesk\ShopMagicCart\Cart\SubmittedCart;

class CartHydrator implements ObjectHydrator, ObjectDehydrator {

	/** @var CustomerRepository */
	private $repository;

	public function __construct( CustomerRepository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * @param BaseCart|object $object
	 *
	 * @return object[]
	 */
	public function normalize( object $object ): array {
		$user_id = null;
		$guest_id = null;
		if ( $object->get_customer()->is_guest() ) {
			$guest_id = CustomerFactory::convert_customer_guest_id_to_number( $object->get_customer()->get_id() );
		} else {
			$user_id = $object->get_customer()->get_id();
		}
		return [
			'id'                 => $object->get_id(),
			'status'             => $object->get_status(),
			'user_id'            => $user_id,
			'guest_id'           => $guest_id,
			'last_modified'      => $object->get_last_modified()->format( WordPressFormatHelper::MYSQL_DATETIME_FORMAT ),
			'created'            => $object->get_created()->format( WordPressFormatHelper::MYSQL_DATETIME_FORMAT ),
			'items'              => json_encode( $object->get_items() ),
			'coupons'            => json_encode( $object->get_coupons() ),
			'fees'               => json_encode( $object->get_fees() ),
			'shipping_tax_total' => $object->get_shipping_tax_total(),
			'shipping_total'     => $object->get_shipping_total(),
			'total'              => $object->get_total(),
			'token'              => $object->get_token(),
			'currency'           => $object->get_currency(),
		];
	}

	public function supports_normalization( object $object ): bool {
		return $object instanceof BaseCart;
	}

	public function denormalize( array $payload ): object {
		try {
			if ( ! empty( $payload['user_id'] ) ) {
				$customer = $this->repository->fetch_user( $payload['user_id'] );
			} else {
				$customer = $this->repository->find( CustomerFactory::id_to_guest_id( $payload['guest_id'] ) );
			}
		} catch ( CustomerNotFound $e ) {
			$customer = new NullCustomer();
		}

		$status = $payload['status'];

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
		}

		$cart = new $cart_class(
			(int) $payload['id'],
			$payload['status'],
			$customer,
			new \DateTimeImmutable( $payload['last_modified'] ),
			new \DateTimeImmutable( $payload['created'] ),
			json_decode( $payload['items'], true ) ?: [],
			json_decode( $payload['coupons'], true ) ?: [],
			json_decode( $payload['fees'], true ) ?: [],
			(float) $payload['shipping_tax_total'],
			(float) $payload['shipping_total'],
			(float) $payload['total'],
			$payload['token'],
			$payload['currency']
		);

		return apply_filters( 'shopmagic/carts/create_cart', $cart );
	}

	public function supports_denormalization( array $data ): bool {
		return true;
	}
}
