<?php

namespace WPDesk\ShopMagicCart\HookEmitter;

use ShopMagicVendor\Psr\Log\LoggerAwareInterface;
use ShopMagicVendor\Psr\Log\LoggerAwareTrait;
use ShopMagicVendor\Psr\Log\LoggerInterface;
use ShopMagicCartVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use WPDesk\ShopMagic\Helper\WordPressFormatHelper;
use WPDesk\ShopMagicCart\Admin\Settings;
use WPDesk\ShopMagicCart\Cart\AbandonedCart;
use WPDesk\ShopMagicCart\Cart\ActiveCart;
use WPDesk\ShopMagicCart\Cart\Cart;
use WPDesk\ShopMagicCart\Cart\CartFactory;
use WPDesk\ShopMagicCart\Database\CartManager;
use WPDesk\ShopMagicCart\Database\CartRepository;

/**
 * Abandon carts that are overdue.
 */
final class CartAbandoner implements Hookable, LoggerAwareInterface {
	use LoggerAwareTrait;

	/** @var CartRepository */
	private $repository;

	/** @var CartManager */
	private $manager;

	/** @var int */
	private $abandon_when;

	public function __construct( CartRepository $repository, CartManager $manager, LoggerInterface $logger ) {
		$this->repository   = $repository;
		$this->manager   = $manager;
		$this->abandon_when = max( (int) Settings::get_option( Settings::TIMEOUT, Settings::TIMEOUT_DEFAULT ), Settings::TIMEOUT_DEFAULT );
		$this->logger       = $logger;
	}

	public function hooks(): void {
		add_action( 'shopmagic/core/cron/one_minute', [ $this, 'abandon_carts' ] );
	}

	public function abandon_carts(): void {
		/** @var Cart $cart */
		foreach ( $this->find_carts_to_abandon() as $cart ) {
			if ( ! $cart instanceof ActiveCart ) {
				return;
			}
			$abandoned_cart = AbandonedCart::convert( $cart );
			if ( $this->manager->save( $abandoned_cart ) ) {
				$this->logger->debug( sprintf( 'Abandoning cart %d', $cart->get_id() ) );
				do_action( 'shopmagic/carts/abandoned_cart', $abandoned_cart );
			}
		}
	}

	/** @return iterable<Cart> */
	private function find_carts_to_abandon(): iterable {
		$cut_time = ( new \DateTimeImmutable( 'now', wp_timezone() ) )
			->modify( "-{$this->abandon_when} minutes" );

		return $this->repository->find_by(
			[
				[
					'field'     => 'last_modified',
					'condition' => '<=',
					'value'     => WordPressFormatHelper::datetime_as_mysql( $cut_time ),
				],
				[
					'field'     => 'status',
					'condition' => '=',
					'value'     => CartFactory::STATUS_ACTIVE,
				],
			]
		);
	}
}
