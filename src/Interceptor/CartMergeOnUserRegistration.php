<?php

declare(strict_types=1);

namespace WPDesk\ShopMagicCart\Interceptor;

use ShopMagicCartVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use ShopMagicVendor\Psr\Log\LoggerInterface;
use WPDesk\ShopMagicCart\Database\CartManager;
use WPDesk\ShopMagicCart\Database\CartRepository;
use WPDesk\ShopMagic\Components\Database\Abstraction\EntityNotFound;
use WPDesk\ShopMagic\Customer\Guest\Guest;
use WPDesk\ShopMagic\Customer\UserAsCustomer;

/**
 * When previous guest register, check for an existing guest cart. If guest cart is newer, reassign, otherwise just delete.
 */
final class CartMergeOnUserRegistration implements Hookable {

	/** @var CartRepository */
	private $repository;

	/** @var CartManager */
	private $manager;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(
		CartRepository $repository,
		CartManager $manager,
		LoggerInterface $logger
	) {
		$this->repository = $repository;
		$this->manager    = $manager;
		$this->logger     = $logger;
	}

	public function hooks(): void {
		add_action( 'shopmagic/core/customer/merge_guest', $this, 10, 2 );
	}

	/**
	 * @param \WP_User $user
	 * @param Guest $guest
	 */
	public function __invoke( $user, $guest ): void {
		try {
			$guest_cart = $this->repository->find_one_by_customer( $guest );
		} catch ( EntityNotFound $e ) {
			// ignore, no cart for previous guest.
			return;
		}

		$this->logger->debug('Trying to transfer an active cart "{cart}" to newly registered user...', ['cart' => $guest_cart->get_id(), 'guest' => $guest->get_id()]);
		$user_customer     = new UserAsCustomer( $user );
		$delete_guest_cart = false;

		$this->logger->debug('Seraching for an active cart already assigned to registered user...', ['user' => $user_customer->get_id()]);
		try {
			$user_cart = $this->repository->find_one_by_customer( $user_customer );
			$this->logger->debug('Found another active cart assigned to registered user.', ['cart' => $user_cart->get_id(), 'user' => $user_customer->get_id()]);

			if ( $user_cart->get_last_modified() > $guest_cart->get_last_modified() ) {
				$this->logger->debug('User cart is newer. Marking guest cart for deletion.', ['cart' => $user_cart->get_id(), 'user' => $user_customer->get_id()]);
				$delete_guest_cart = true;
			} else {
				$this->manager->delete( $user_cart );
				$this->logger->debug('Deleted user cart.', ['cart' => $user_cart->get_id(), 'user' => $user_customer->get_id()]);
			}
		} catch ( EntityNotFound $e ) {
			// no user cart means we only need to update guest cart.
			$this->logger->debug('No user cart found. Only updating guest cart.', ['user' => $user_customer->get_id()]);
		}

		if ( $delete_guest_cart === true ) {
			$this->manager->delete( $guest_cart );
			$this->logger->debug('Deleted guest cart.', ['cart' => $guest_cart->get_id(), 'guest' => $guest->get_id()]);
		} else {
			$guest_cart->set_customer( $user_customer );

			$this->manager->save( $guest_cart );
			$this->logger->debug('Upgraded guest cart to user cart with registered customer.', ['cart' => $guest_cart->get_id(), 'guest' => $guest->get_id(), 'user' => $user_customer->get_id()]);
		}

		$this->logger->debug('Finished active cart transfer.');
	}
}
