<?php
declare( strict_types=1 );

namespace WPDesk\ShopMagicCart\Cart;

use WPDesk\ShopMagic\Customer\Customer;
use WPDesk\ShopMagic\Helper\WordPressFormatHelper;

/**
 * Currently active cart. Can manipulate its data. May become submitted or abandoned.
 */
class ActiveCart extends BaseCart {

	public function __construct(
		?int $id,
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

	/**
	 * Updates the stored cart with the current time and cart items
	 *
	 * @return void
	 */
	public function sync( \WC_Cart $wc_cart, Customer $customer, string $token ) {
		$this->last_modified = new \DateTimeImmutable();

		if ( $this->needs_update( $token ) ) {
			$this->changed = true;
		}

		$this->items = $this->map_array_items( WC()->cart->get_cart_for_session() );

		$coupon_data = [];
		foreach ( $wc_cart->get_applied_coupons() as $coupon_code ) {
			$coupon_data[ $coupon_code ] = [
				'discount_incl_tax' => $wc_cart->get_coupon_discount_amount( $coupon_code, false ),
				'discount_excl_tax' => $wc_cart->get_coupon_discount_amount( $coupon_code ),
				'discount_tax'      => $wc_cart->get_coupon_discount_tax_amount( $coupon_code ),
			];
		}

		$this->coupons            = $coupon_data;
		$this->token              = $token;
		$this->fees               = $wc_cart->get_fees();
		$this->currency           = get_woocommerce_currency();
		$this->customer           = $customer;
		$this->shipping_tax_total = $wc_cart->shipping_tax_total;
		$this->shipping_total     = $wc_cart->shipping_total;

		$this->calculate_totals();

		if ( $this->status === Cart::FRESH ) {
			$this->status = Cart::ACTIVE;
		}
	}

	private function needs_update( string $token ): bool {
		$created = $this->created->format( WordPressFormatHelper::MYSQL_DATETIME_FORMAT );
		$updated = $this->last_modified->format( WordPressFormatHelper::MYSQL_DATETIME_FORMAT );

		return ( $created !== $updated ) ||
			   ( $this->token !== $token );
	}

	/** @return void */
	private function calculate_totals() {
		$this->calculated_subtotal  = 0;
		$this->calculated_tax_total = 0;
		$this->total                = 0;

		$tax_display = get_option( 'woocommerce_tax_display_cart' );

		foreach ( $this->items as $item ) {
			$this->calculated_tax_total += $item->get_line_subtotal_tax();
			$this->total                += $item->get_line_subtotal() + $item->get_line_subtotal_tax();
			$this->calculated_subtotal  += $tax_display === 'excl' ? $item->get_line_subtotal() : $item->get_line_subtotal() + $item->get_line_subtotal_tax();
		}

		foreach ( $this->coupons as $coupon ) {
			$this->total                -= $coupon['discount_incl_tax'];
			$this->calculated_tax_total -= $coupon['discount_tax'];
		}

		foreach ( $this->fees as $fee ) {
			$this->total                += ( $fee->amount + $fee->tax );
			$this->calculated_tax_total += $fee->tax;
		}

		$this->calculated_tax_total += $this->shipping_tax_total;
		$this->total                += $this->shipping_total;
		$this->total                += $this->shipping_tax_total;
	}


}
