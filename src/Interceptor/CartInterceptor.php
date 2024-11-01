<?php

declare( strict_types=1 );

namespace WPDesk\ShopMagicCart\Interceptor;

use ShopMagicVendor\Psr\Log\LoggerInterface;
use ShopMagicCartVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use WC_Order;
use WPDesk\ShopMagic\Customer\CustomerProvider;
use WPDesk\ShopMagic\Customer\CustomerRepository;
use WPDesk\ShopMagic\Exception\CannotProvideItemException;
use WPDesk\ShopMagic\Exception\ShopMagicException;
use WPDesk\ShopMagic\Helper\Conditional;
use WPDesk\ShopMagic\Helper\RestRequestUtil;
use WPDesk\ShopMagic\Helper\WooCommerceCookies;
use WPDesk\ShopMagicCart\Cart\AbandonedCart;
use WPDesk\ShopMagicCart\Cart\ActiveCart;
use WPDesk\ShopMagicCart\Cart\Cart;
use WPDesk\ShopMagicCart\Cart\CartFactory;
use WPDesk\ShopMagicCart\Cart\OrderedCart;
use WPDesk\ShopMagicCart\Cart\SubmittedCart;
use WPDesk\ShopMagicCart\Database\CartManager;
use WPDesk\ShopMagicCart\Database\CartRepository;

final class CartInterceptor implements Hookable, Conditional {
	const SESSION_TOKEN_KEY      = 'shopmagic_cart_token';
	const COOKIE_IS_CHANGED_NAME = 'shopmagic_cookie';

	/** @var CustomerProvider */
	private $customer_provider;

	/** @var LoggerInterface */
	private $logger;

	/** @var bool */
	private $is_changed = false;

	/** @var CartRepository */
	private $repository;

	/** @var CartManager */
	private $manager;

	/** @var CartFactory */
	private $factory;

	/** @var CustomerRepository */
	private $customer_repository;

	public function __construct(
		CartManager $manager,
		CartFactory $factory,
		CustomerRepository $customer_repository,
		CustomerProvider $customer_provider,
		LoggerInterface $logger
	) {
		$this->repository          = $manager->get_repository();
		$this->manager             = $manager;
		$this->factory             = $factory;
		$this->customer_repository = $customer_repository;
		$this->customer_provider   = $customer_provider;
		$this->logger              = $logger;
	}

	/** @return void */
	public function hooks() {
		add_action( 'woocommerce_add_to_cart', [ $this, 'set_changed' ] );
		add_action( 'woocommerce_applied_coupon', [ $this, 'set_changed' ] );
		add_action( 'woocommerce_removed_coupon', [ $this, 'set_changed' ] );
		add_action( 'woocommerce_cart_item_removed', [ $this, 'set_changed' ] );
		add_action( 'woocommerce_cart_item_restored', [ $this, 'set_changed' ] );
		add_action( 'woocommerce_before_cart_item_quantity_zero', [ $this, 'set_changed' ] );
		add_action( 'woocommerce_after_cart_item_quantity_update', [ $this, 'set_changed' ] );
		add_action( 'shopmagic/core/customer_interceptor/changed', [ $this, 'set_changed' ] );

		add_action( 'woocommerce_after_calculate_totals', [ $this, 'trigger_update_on_cart_and_checkout_pages' ] );

		add_action( 'wp_login', [ $this, 'set_changed_into_cookie' ], 20 );
		add_action( 'wp', [ $this, 'set_changed_from_cookie' ], 99 );

		add_action( 'shutdown', [ $this, 'save_cart' ] );

		add_action( 'woocommerce_checkout_create_order', [ $this, 'sync_cart_with_order' ] );
		add_action(
			'woocommerce_order_status_changed',
			function ( $_, $__, $to, $order ) {
				if ( in_array( $to, wc_get_is_paid_statuses(), true ) ) {
					$this->mark_as_ordered( $order );
				}
			},
			10,
			4
		);
	}

	private function mark_as_ordered( \WC_Abstract_Order $order ): void {
		$cart_id = $order->get_meta( 'shopmagic_cart_id' );
		try {
			$cart = $this->repository->find( $cart_id );
		} catch ( CannotProvideItemException $e ) {
			$this->logger->warning(
				sprintf(
					'Cart %d associated with order %d no longer exists.',
					$cart_id,
					$order->get_id()
				)
			);

			return;
		}

		if ( ! $cart instanceof SubmittedCart && ! $cart instanceof AbandonedCart ) {
			return;
		}

		$ordered_cart = OrderedCart::convert( $cart );
		if ( $ordered_cart->is_recovered() ) {
			$this->manager->save( $ordered_cart );
		} else {
			$this->manager->delete( $ordered_cart );
		}
	}

	/**
	 * @return void
	 * @internal
	 */
	public function set_changed_into_cookie() {
		if ( ! headers_sent() ) {
			WooCommerceCookies::set( self::COOKIE_IS_CHANGED_NAME, '1' );
		}
	}

	/**
	 * Important not to run this in the admin area, may not update cart properly
	 *
	 * @return void
	 * @internal
	 */
	public function set_changed_from_cookie() {
		if ( WooCommerceCookies::get( self::COOKIE_IS_CHANGED_NAME ) === '1' ) {
			$this->is_changed = true;
			WooCommerceCookies::clear( self::COOKIE_IS_CHANGED_NAME );
		}
	}

	/**
	 * @return void
	 * @internal
	 */
	public function trigger_update_on_cart_and_checkout_pages() {
		if (
			defined( 'WOOCOMMERCE_CART' )
			|| is_checkout()
			|| did_action( 'woocommerce_before_checkout_form' ) // support for one-page checkout plugins.
		) {
			$this->is_changed = true;
		}
	}

	/**
	 * @return void
	 * @internal
	 */
	public function sync_cart_with_order( \WC_Abstract_Order $order ) {
		if ( ! $order instanceof WC_Order || ! WC()->session instanceof \WC_Session ) {
			return;
		}

		try {
			$cart = $this->repository->find_one_by_customer( $this->customer_repository->find_by_email( $order->get_billing_email() ) );
		} catch ( ShopMagicException $e ) {
			$this->logger->warning( 'Customer cart for order email not found. Trying to get cart from session data.' );
			$cart = $this->get_cart();
		}

		if ( $cart->get_id() === null ) {
			return;
		}

		try {
			$submitted_cart = SubmittedCart::convert( $cart );
		} catch ( \InvalidArgumentException $e ) {
			$this->logger->warning(
				'An error occurred during converting cart {id} to submitted cart. Reason: {message}',
				[
					'id'      => $cart->get_id(),
					'message' => $e->getMessage(),
				]
			);
			return;
		}
		$submitted_cart->bind_with_order( $order );

		$saved = $this->manager->save( $submitted_cart );
		if ( $saved ) {
			$this->logger->info(
				sprintf(
					'Cart %d successfully saved after order. Cart status is %s',
					$cart->get_id(),
					$cart->get_status()
				)
			);
		} else {
			$this->logger->warning( sprintf( 'An error occurred during saving cart %d', $cart->get_id() ) );
		}

		$this->separate_cart_from_session();
	}

	private function get_cart(): Cart {
		try {
			return $this->repository->find_one_by( [ 'token' => $this->get_tracking_key() ] );
		} catch ( CannotProvideItemException $e ) {
			try {
				return $this->repository->find_one_by_customer( $this->customer_provider->get_customer() );
			} catch ( ShopMagicException $e ) {
				$this->logger->debug( 'Cart was not associated with any tracking key nor customer. Creating new cart.' );

				return $this->factory->create_null();
			}
		}
	}

	private function get_tracking_key(): string {
		if ( WC()->session !== null ) {
			$token = WC()->session->get( self::SESSION_TOKEN_KEY );
			if ( is_string( $token ) && strlen( $token ) > 0 ) {
				return $token;
			}
			$this->logger->debug( 'Cart token not found. Generating new session token.' );
		}

		return md5( uniqid( 'sm_', true ) );
	}

	/** @return void */
	private function separate_cart_from_session() {
		WC()->session->set( 'shopmagic_checkout_processed_time', time() );
		WooCommerceCookies::clear( self::COOKIE_IS_CHANGED_NAME );
		WC()->session->set( self::SESSION_TOKEN_KEY, '' );
	}

	/**
	 * @return void
	 * @internal
	 */
	public function save_cart() {
		try {
			if ( ! $this->should_save_cart() ) {
				return;
			}
			$cart = $this->get_cart();
			if ( ! $cart instanceof ActiveCart ) {
				return;
			}
			$token = $cart->get_token() ?: $this->get_tracking_key();
			$this->store_tracking_key( $token );
			$cart->sync( WC()->cart, $this->customer_provider->get_customer(), $token );

			if ( count( $cart->get_items() ) !== 0 ) {
				$saved = $this->manager->save( $cart );
				if ( $saved ) {
					$this->logger->info(
						sprintf(
							'Saved cart with ID %d for %s %s',
							$cart->get_id(),
							$cart->get_customer()->is_guest() ? 'guest' : 'user',
							$cart->get_customer()->get_email()
						)
					);
				} else {
					$this->logger->warning( sprintf( 'An error occurred during saving cart %d', $cart->get_id() ) );
				}
			} elseif ( $cart->get_id() !== null && $cart->get_status() === 'active' ) {
				$deleted = $this->manager->delete( $cart );
				if ( $deleted ) {
					$this->logger->info(
						sprintf(
							'Deleted cart with ID %d for user %s',
							$cart->get_id(),
							$cart->get_customer()->get_email()
						)
					);
				} else {
					$this->logger->warning( sprintf( 'Cart %d could not be deleted.', $cart->get_id() ) );
				}
			}
		} catch ( \Throwable $e ) {
			$this->logger->error( 'Cannot CartInterceptor::save_cart', [ 'exception' => $e ] );
		}
	}

	private function should_save_cart(): bool {
		if ( ! $this->is_changed ) {
			return false;
		}
		if ( is_admin() && ! wp_doing_ajax() ) {
			return false;
		}
		if ( did_action( 'wp_logout' ) ) {
			return false;
		}
		if ( ! $this->customer_provider->is_customer_provided() ) {
			return false;
		}
		if ( ! WC()->cart ) {
			return false;
		}

		// session only loaded on front end.
		if ( WC()->session ) {
			$last_checkout = WC()->session->get( 'shopmagic_checkout_processed_time' );
			$this->logger->debug( 'Last checkout intercepted at ' . date( 'Y-m-d H:i:s', (int) $last_checkout ) );

			// ensure checkout has not been processed in the last 5 minutes
			// this is a fallback for a rare case when the cart session is not cleared after checkout.
			if ( $last_checkout && $last_checkout > ( time() - 5 * MINUTE_IN_SECONDS ) ) {
				$this->logger->debug( 'Skipping cart save.' );

				return false;
			}
		}

		return true;
	}

	/** @return void */
	private function store_tracking_key( string $token ) {
		if ( ! WC()->session ) {
			return;
		}
		WC()->session->set( self::SESSION_TOKEN_KEY, $token );
	}

	/**
	 * @return void
	 * @internal
	 */
	public function set_changed() {
		$this->is_changed = true;
	}

	public static function is_needed(): bool {
		if ( RestRequestUtil::is_rest_request() ) {
			return false;
		}

		return true;
	}
}
