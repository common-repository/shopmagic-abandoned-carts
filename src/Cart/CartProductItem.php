<?php

declare( strict_types=1 );

namespace WPDesk\ShopMagicCart\Cart;

use WC_Product;
use WPDesk\ShopMagic\Exception\ReferenceNoLongerAvailableException;

final class CartProductItem implements \JsonSerializable {

	/** @var array */
	private $data;

	public function __construct( array $item ) {
		$this->data = $item;
		try {
			$this->data['data'] = $this->get_product();
		} catch ( ReferenceNoLongerAvailableException $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// product no longer exists.
		}
	}

	public function get_product(): \WC_Product {
		$product_id = $this->get_variation_id() ?: $this->get_product_id();
		$product    = wc_get_product( $product_id );

		if ( $product instanceof WC_Product ) {
			return $product;
		}

		throw new ReferenceNoLongerAvailableException( sprintf( 'Product with ID %d is non existent.', $product_id ) );
	}

	public function get_variation_id(): int {
		return isset( $this->data['variation_id'] ) ? (int) $this->data['variation_id'] : 0;
	}

	public function get_product_id(): int {
		return isset( $this->data['product_id'] ) ? (int) $this->data['product_id'] : 0;
	}

	public function get_line_subtotal(): float {
		return isset( $this->data['line_subtotal'] ) ? (float) $this->data['line_subtotal'] : 0;
	}

	public function get_line_subtotal_tax(): float {
		return isset( $this->data['line_subtotal_tax'] ) ? (float) $this->data['line_subtotal_tax'] : 0;
	}

	/** @return array */
	public function jsonSerialize(): array {
		return $this->data;
	}

	public function get_permalink(): string {
		try {
			return $this->get_product()->get_permalink();
		} catch ( ReferenceNoLongerAvailableException $e ) {
			return '';
		}
	}

	public function get_image_src(): string {
		try {
			return wp_get_attachment_image_url( $this->get_product()->get_image_id(), [ 40, 40 ] ) ?: '';
		} catch ( ReferenceNoLongerAvailableException $e ) {
			return wc_placeholder_img_src();
		}
	}

	public function get_name(): string {
		try {
			$product = $this->get_product();

			return apply_filters( 'woocommerce_cart_item_name', $product->get_name(), $this->data, $this->get_key() ) ?? esc_html__( 'Removed product', 'shopmagic-abandoned-carts' );
		} catch ( ReferenceNoLongerAvailableException $e ) {
			return esc_html__( 'Removed product', 'shopmagic-abandoned-carts' );
		}
	}

	private function get_key(): string {
		return (string) $this->data['key'] ?? '';
	}

	/** @retrun void */
	public function append_to_wc_cart( \WC_Cart $cart ) {
		$existing_items = $cart->get_cart_for_session();
		if ( ! isset( $existing_items[ $this->get_key() ] ) ) {
			$cart->add_to_cart( $this->get_product_id(), $this->get_quantity(), $this->get_variation_id(), $this->get_variation_data(), $this->data );
		}
	}

	/**
	 * Get item quantity.
	 *
	 * @return int|float
	 */
	public function get_quantity() {
		$quantity = wc_stock_amount( $this->data['quantity'] ?? 0 );

		return apply_filters( 'shopmagic/carts/cart_item/get_quantity', $quantity, $this );
	}

	private function get_variation_data(): array {
		return isset( $this->data['variation'] ) && is_array( $this->data['variation'] ) ? $this->data['variation'] : [];
	}
}
