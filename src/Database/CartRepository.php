<?php

declare( strict_types=1 );

namespace WPDesk\ShopMagicCart\Database;

use WPDesk\ShopMagic\Components\Database\Abstraction\ObjectRepository;
use WPDesk\ShopMagic\Customer\Customer;
use WPDesk\ShopMagic\Customer\CustomerFactory;
use WPDesk\ShopMagicCart\Cart\BaseCart;
use WPDesk\ShopMagicCart\Cart\Cart;
use WPDesk\ShopMagicCart\DatabaseTable;

/**
 * @extends  ObjectRepository<BaseCart>
 */
class CartRepository extends ObjectRepository {

	public function find_one_by_customer( Customer $customer ): BaseCart {
		if ( $customer->is_guest() ) {
			$where = [
				'guest_id' => CustomerFactory::convert_customer_guest_id_to_number( $customer->get_id() ),
			];
		} else {
			$where = [
				'user_id' => $customer->get_id(),
			];
		}

		/** @var BaseCart */
		return $this->find_one_by( array_merge(
			$where, [
				[
					'field'     => 'status',
					'condition' => '<>',
					'value'     => Cart::ORDERED,
				],
				[
					'field'     => 'status',
					'condition' => '<>',
					'value'     => Cart::RECOVERED,
				],
			]
		) );
	}

	protected function get_name(): string {
		return DatabaseTable::cart();
	}
}
