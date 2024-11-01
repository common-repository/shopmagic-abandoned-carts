<?php

namespace WPDesk\ShopMagicCart\Frontend;

use ShopMagicVendor\Psr\Log\LoggerAwareInterface;
use ShopMagicVendor\Psr\Log\LoggerAwareTrait;
use ShopMagicVendor\Psr\Log\LoggerInterface;
use ShopMagicCartVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use WPDesk\ShopMagic\Exception\CannotProvideItemException;
use WPDesk\ShopMagic\Workflow\Placeholder\Helper\PlaceholderUTMBuilder;
use WPDesk\ShopMagicCart\Cart\AbandonedCart;
use WPDesk\ShopMagicCart\Database\CartRepository;

/**
 * Restore user's cart.
 */
final class CartRestore implements Hookable, LoggerAwareInterface {
	use LoggerAwareTrait;

	const ACTION_NAME = 'cart-restore';

	/** @var CartRepository */
	private $repository;

	public function __construct( CartRepository $repository, LoggerInterface $logger ) {
		$this->repository = $repository;
		$this->logger     = $logger;
	}

	public static function get_restore_url( string $cart_token ): string {
		return add_query_arg(
			[
				'action' => self::ACTION_NAME,
				'token'  => $cart_token,
				'hash'   => self::calculate_hash( $cart_token ),
			],
			wc_get_cart_url()
		);
	}

	private static function calculate_hash( string $token ): string {
		return md5( $token . AUTH_SALT );
	}

	public function cart_restore_callback(): void {
		//phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['action'] ) && $_GET['action'] === self::ACTION_NAME ) {
			$token           = isset( $_GET['token'] ) ? sanitize_key( $_GET['token'] ) : '';
			$calculated_hash = self::calculate_hash( $token );
			$get_hash        = isset( $_GET['hash'] ) ? sanitize_key( $_GET['hash'] ) : '';
			if ( $get_hash !== $calculated_hash ) {
				return;
			}
			$this->restore_cart( $token );

			$utm_builder        = new PlaceholderUTMBuilder();
			$cart_link_with_utm = $utm_builder->append_utm_parameters_to_uri( $_GET, wc_get_cart_url() );
			wp_safe_redirect( $cart_link_with_utm );

			exit;
		}
		//phpcs:enable
	}

	private function restore_cart( string $token ): void {
		try {
			$cart = $this->repository->find_one_by( [ 'token' => $token ] );
		} catch ( CannotProvideItemException $e ) {
			return;
		}
		if ( $cart instanceof AbandonedCart && WC()->cart instanceof \WC_Cart && count( $cart->get_items() ) !== 0 ) {
			$cart->append_to_wc_cart( WC()->cart );
		}
	}

	public function hooks(): void {
		add_action( 'wp', [ $this, 'cart_restore_callback' ] );
	}
}
