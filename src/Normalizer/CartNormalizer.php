<?php

declare( strict_types=1 );

namespace WPDesk\ShopMagicCart\Normalizer;

use WPDesk\ShopMagic\Api\Normalizer\Normalizer;
use WPDesk\ShopMagicCart\Cart\BaseCart;
use WPDesk\ShopMagicCart\Cart\CartProductItem;

/**
 * @implements Normalizer<BaseCart>
 */
class CartNormalizer implements Normalizer {

	/** @param BaseCart|object $object */
	public function normalize( object $object ): array {
		return [
			'id'       => $object->get_id(),
			'object'   => 'cart',
			'customer' => [
				'id'    => $object->get_customer()->get_id(),
				'email' => $object->get_customer()->get_email(),
			],
			'updated'  => $object->get_last_modified()->format( \DateTimeInterface::ATOM ),
			'products' => array_values( array_map( static function ( CartProductItem $item ) {
				return [
					'id'       => $item->get_product_id(),
					'name'     => $item->get_name(),
					'quantity' => $item->get_quantity(),
					'image'    => $item->get_image_src(),
				];
			}, $object->get_items() ) ),
			'value'    => [
				'price'    => $object->get_total(),
				'currency' => $object->get_currency(),
			],
			'status'   => $object->get_status(),
			'_links'   => [
				'self' => [ 'href' => get_rest_url( null, '/shopmagic/v1/carts/' . $object->get_id() ) ],
			],
		];
	}

	public function supports_normalization( object $object ): bool {
		return $object instanceof BaseCart;
	}

}
