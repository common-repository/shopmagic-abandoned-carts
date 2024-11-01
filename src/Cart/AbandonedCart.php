<?php
declare( strict_types=1 );

namespace WPDesk\ShopMagicCart\Cart;

use WPDesk\ShopMagic\Customer\Customer;

/**
 * Can become ordered.
 */
class AbandonedCart extends BaseCart {

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
		if ( $status !== Cart::ACTIVE &&
			 $status !== Cart::ABANDONED
		) {
			throw new \InvalidArgumentException( sprintf( 'Allowed statuses are: "active", "abandoned"; "%s" given.', $status ) );
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

		if ( $status === Cart::ACTIVE ) {
			$this->changed = true;
			$this->status  = Cart::ABANDONED;
		}
	}

	/** @return void */
	public function append_to_wc_cart( \WC_Cart $wc_cart ) {
		$notices_backup = wc_get_notices();
		try {
			foreach ( $this->items as $item ) {
				$item->append_to_wc_cart( $wc_cart );
			}

			foreach ( $this->coupons as $coupon_code => $coupon_data ) {
				if ( ! $wc_cart->has_discount( $coupon_code ) ) {
					$wc_cart->add_discount( $coupon_code );
				}
			}
		} finally {
			WC()->session->set( 'wc_notices', $notices_backup );
		}
	}
}
