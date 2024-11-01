<?php

namespace WPDesk\ShopMagicCart\Event;

use ShopMagicVendor\WPDesk\Forms\Field\InputNumberField;
use WPDesk\ShopMagic\Customer\Customer;
use WPDesk\ShopMagic\Extensions\Elements\Groups;
use WPDesk\ShopMagic\Workflow\Automation\Automation;
use WPDesk\ShopMagic\Workflow\Event\CustomerAwareInterface;
use WPDesk\ShopMagic\Workflow\Event\CustomerAwareTrait;
use WPDesk\ShopMagic\Workflow\Event\DeferredStateCheck\SupportsDeferredCheck;
use WPDesk\ShopMagic\Workflow\Event\Event;
use WPDesk\ShopMagic\Workflow\Outcome\OutcomeRepository;
use WPDesk\ShopMagicCart\Admin\Settings;
use WPDesk\ShopMagicCart\Cart\AbandonedCart;
use WPDesk\ShopMagicCart\Cart\BaseCart;
use WPDesk\ShopMagicCart\Database\CartRepository;

final class AbandonedCartEvent extends Event implements SupportsDeferredCheck, CustomerAwareInterface {
	use CustomerAwareTrait;

	const FIELD_NAME_PAUSE_PERIOD = 'pause_period';

	/** @var CartRepository */
	private $repository;

	/** @var OutcomeRepository */
	private $outcome_repository;

	public function __construct(
		CartRepository $repository,
		OutcomeRepository $outcome_repository
	) {
		$this->repository         = $repository;
		$this->outcome_repository = $outcome_repository;
	}

	public function get_fields(): array {
		return [
			( new InputNumberField() )
				->set_name( self::FIELD_NAME_PAUSE_PERIOD )
				->set_attribute( 'min', 1 )
				->set_label( __( 'Pause period for customer (days)', 'shopmagic-abandoned-carts' ) )
				->set_description( __( 'Can be used to ensure that this event will trigger only once in a specified time period.',
					'shopmagic-abandoned-carts' ) ),
		];
	}

	public function get_name(): string {
		return __( 'Abandoned Cart', 'shopmagic-abandoned-carts' );
	}

	public function get_description(): string {
		$abandoned_timeout = Settings::get_option( 'abandoned_cart_timeout' );

		return sprintf(
		// translators: %d amount of minutes to consider abandoned.
			_n(
				'Run automation %d minute after the cart is considered abandoned.',
				'Run automation %d minutes after the cart is considered abandoned.',
				$abandoned_timeout,
				'shopmagic-abandoned-carts'
			),
			$abandoned_timeout
		);
	}

	public function get_group_slug(): string {
		return Groups::CART;
	}

	public function jsonSerialize(): array {
		return [
			'cart_id'     => $this->resources->get( BaseCart::class )->get_id(),
			'customer_id' => $this->resources->get( Customer::class )->get_id(),
		];
	}

	public function set_from_json( array $serialized_json ): void {
		$this->resources->set( BaseCart::class, $this->repository->find( (string) $serialized_json['cart_id'] ) );
		$this->resources->set( Customer::class, $this->customer_repository->find( $serialized_json['customer_id'] ) );
	}

	public function get_provided_data_domains(): array {
		return array_merge(
			parent::get_provided_data_domains(),
			[ BaseCart::class, Customer::class ]
		);
	}

	public function process_event( BaseCart $cart ): void {
		$this->resources->set( BaseCart::class, $cart );
		$this->resources->set( Customer::class, $cart->get_customer() );

		if ( $this->fields_data->has( self::FIELD_NAME_PAUSE_PERIOD ) ) {
			$pause_days = $this->fields_data->get( self::FIELD_NAME_PAUSE_PERIOD );
			if ( $this->automation_triggered_within( (int) $pause_days ) ) {
				return;
			}
		}

		$this->trigger_automation();
	}

	public function initialize(): void {
		add_action( 'shopmagic/carts/abandoned_cart', [ $this, 'process_event' ] );
	}

	public function is_event_still_valid(): bool {
		return $this->resources->get( BaseCart::class ) instanceof AbandonedCart;
	}

	private function automation_triggered_within( int $pause_days ): bool {
		return $this->amount_of_recent_automations_since( $pause_days ) > 0;
	}

	private function amount_of_recent_automations_since( int $days ): int {
		$automation_id = $this->resources->get( Automation::class )->get_id();
		$customer_id   = $this->resources->get( Customer::class )->get_id();

		return $this->outcome_repository
			->count_automations_for_customer_with_time(
				$automation_id,
				$customer_id,
				$days
			);
	}
}
