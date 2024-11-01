<?php
declare( strict_types=1 );

namespace WPDesk\ShopMagicCart\Cart;

use WPDesk\ShopMagic\Customer\Customer;
use WPDesk\ShopMagic\Customer\CustomerFactory;
use WPDesk\ShopMagic\Helper\WordPressFormatHelper;

abstract class BaseCart implements Cart {

	/** @var int|null */
	protected $id;

	/** @var string */
	protected $status;

	/** @var Customer */
	protected $customer;

	/** @var \DateTimeInterface */
	protected $last_modified;

	/** @var \DateTimeInterface */
	protected $created;

	/** @var CartProductItem[] */
	protected $items;

	/** @var array */
	protected $coupons;

	/** @var array */
	protected $fees;

	/** @var float */
	protected $shipping_tax_total;

	/** @var float */
	protected $shipping_total;

	/** @var float */
	protected $total;

	/** @var string */
	protected $token;

	/** @var string */
	protected $currency;

	/** @var bool */
	protected $changed = false;

	/** @var float */
	protected $calculated_tax_total = 0;

	/** @var float */
	protected $calculated_subtotal = 0;

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
		$this->id                 = $id;
		$this->status             = $status;
		$this->customer           = $customer;
		$this->last_modified      = $last_modified;
		$this->created            = $created;
		$this->items              = $this->map_array_items( $items );
		$this->coupons            = $coupons;
		$this->fees               = $fees;
		$this->shipping_tax_total = $shipping_tax_total;
		$this->shipping_total     = $shipping_total;
		$this->total              = $total;
		$this->token              = $token;
		$this->currency           = $currency;
	}

	/**
	 * @param array $items
	 *
	 * @return CartProductItem[]
	 */
	protected function map_array_items( array $items ): array {
		return array_map(
			function ( $item_data ) {
				if ( $item_data instanceof CartProductItem ) {
					return $item_data;
				}
				return new CartProductItem( $item_data );
			},
			$items
		);
	}

	/** @return static */
	public static function convert( self $cart ): self {
		return new static(
			$cart->id,
			$cart->status,
			$cart->customer,
			$cart->last_modified,
			$cart->created,
			$cart->items,
			$cart->coupons,
			$cart->fees,
			$cart->shipping_tax_total,
			$cart->shipping_total,
			$cart->total,
			$cart->token,
			$cart->currency
		);
	}

	public function has_changed(): bool {
		return empty( $this->id ) || $this->changed;
	}

	public function get_customer(): Customer {
		return $this->customer;
	}

	public function set_customer( Customer $customer ): void {
		$this->customer = $customer;
	}

	public function get_last_modified(): \DateTimeInterface {
		return $this->last_modified;
	}

	public function get_created(): \DateTimeInterface {
		return $this->created;
	}

	/** @return CartProductItem[] */
	public function get_items(): array {
		return $this->items;
	}

	public function get_products_quantity_count(): float {
		return array_reduce(
			$this->items,
			static function ( $carry, CartProductItem $item ) {
				return $carry + $item->get_quantity();
			},
			0
		);
	}

	public function get_shipping_tax_total(): float {
		return (float) $this->shipping_tax_total;
	}

	public function get_shipping_total(): float {
		return (float) $this->shipping_total;
	}

	public function get_total(): float {
		return (float) $this->total;
	}

	public function get_token(): string {
		return $this->token;
	}

	public function get_currency(): string {
		return $this->currency;
	}

	public function get_coupons(): array {
		return $this->coupons;
	}

	public function get_fees(): array {
		return $this->fees;
	}

	public function get_calculated_tax_total(): int {
		return $this->calculated_tax_total;
	}

	public function get_calculated_subtotal(): int {
		return $this->calculated_subtotal;
	}

	public function normalize(): array {
		$vars = get_object_vars( $this );

		$vars['last_modified'] = WordPressFormatHelper::datetime_as_mysql( $this->last_modified );
		$vars['created']       = WordPressFormatHelper::datetime_as_mysql( $this->created );
		$vars['items']         = json_encode( $this->items );
		$vars['coupons']       = json_encode( $this->coupons );
		$vars['fees']          = json_encode( $this->fees );

		if ( $this->customer->is_guest() ) {
			$vars['guest_id'] = CustomerFactory::convert_customer_guest_id_to_number( (string) $this->customer->get_id() );
			$vars['user_id']  = null;
		} else {
			$vars['user_id']  = $this->customer->get_id();
			$vars['guest_id'] = null;
		}

		return $vars;
	}

	public function get_id(): ?int {
		return $this->id;
	}

	public function set_last_inserted_id( int $id ): void {
		$this->id = $id;
	}

	public function get_status(): string {
		return $this->status;
	}

	public function set_status( string $status ): void {
		$this->status = $status;
	}
}
