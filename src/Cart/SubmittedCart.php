<?php
declare( strict_types=1 );

namespace WPDesk\ShopMagicCart\Cart;

use WPDesk\ShopMagic\Customer\Customer;

/**
 * Readonly. Can be abandoned or ordered.
 */
class SubmittedCart extends BaseCart {

	public function __construct(
		int $id,
		string $status,
		Customer $customer,
		\DateTimeInterface $last_modified,
		\DateTimeInterface $created,
		array $items,
		array $coupons,
		array $fees,
		float $shipping_tax_total,
		float $shipping_total,
		float $total,
		string $token,
		string $currency
	) {
		if (
			$status !== Cart::ACTIVE &&
			$status !== Cart::FRESH &&
			$status !== Cart::ABANDONED &&
			$status !== Cart::SUBMITTED
		) {
			throw new \InvalidArgumentException( sprintf( 'Allowed statuses are: "active", "abandoned", "submitted"; "%s" given.', $status ) );
		}

		parent::__construct(
			$id,
			$status,
			$customer,
			$last_modified,
			$created,
			$items,
			$coupons,
			$fees,
			$shipping_tax_total,
			$shipping_total,
			$total,
			$token,
			$currency
		);
	}

	public function bind_with_order( \WC_Abstract_Order $order ) {
		$order->add_meta_data( 'shopmagic_cart_id', $this->get_id() );
		if ( $this->status === Cart::ACTIVE ) {
			$this->status  = Cart::SUBMITTED;
			$this->changed = true;
		}
	}

}
