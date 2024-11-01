<?php
declare( strict_types=1 );

namespace WPDesk\ShopMagicCart\Cart;

use WPDesk\ShopMagic\Customer\Customer;

/**
 * Readonly. Ordered after abandonment. If ordered normally, should be deleted.
 */
class OrderedCart extends BaseCart {

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
			$status !== Cart::ABANDONED &&
			$status !== Cart::SUBMITTED &&
			$status !== Cart::RECOVERED &&
			$status !== Cart::ORDERED
		) {
			throw new \InvalidArgumentException( sprintf( 'Allowed statuses are: "ordered", "submitted", "abandoned", "recovered"; "%s" given.', $status ) );
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

		$this->maybe_mark_as_recovered();
	}

	private function maybe_mark_as_recovered() {
		if ( $this->status === Cart::ABANDONED ) {
			$this->status  = Cart::RECOVERED;
			$this->changed = true;
		}
	}

	public function is_recovered(): bool {
		return $this->status === Cart::RECOVERED;
	}

}
