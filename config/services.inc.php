<?php

use function ShopMagicVendor\DI\get;
use function ShopMagicVendor\DI\string;

return [
	'hookable.init'                                        => [
		\ShopMagicVendor\DI\create( \WPDesk\ShopMagicCart\Interceptor\CartInterceptor::class )
			->constructor(
				get( \WPDesk\ShopMagicCart\Database\CartManager::class ),
				get( \WPDesk\ShopMagicCart\Cart\CartFactory::class ),
				get( \WPDesk\ShopMagic\Customer\CustomerRepository::class ),
				get( \WPDesk\ShopMagic\Customer\CustomerProvider::class ),
				get( \ShopMagicVendor\Psr\Log\LoggerInterface::class )
			),
		\ShopMagicVendor\DI\create( \WPDesk\ShopMagicCart\Frontend\CartRestore::class )
			->constructor(
				get( \WPDesk\ShopMagicCart\Database\CartRepository::class ),
				get( \ShopMagicVendor\Psr\Log\LoggerInterface::class )
			),
		\ShopMagicVendor\DI\create( \WPDesk\ShopMagicCart\HookEmitter\CartAbandoner::class )
			->constructor(
				get( \WPDesk\ShopMagicCart\Database\CartRepository::class ),
				get( \WPDesk\ShopMagicCart\Database\CartManager::class ),
				get( \ShopMagicVendor\Psr\Log\LoggerInterface::class )
			),
		\ShopMagicVendor\DI\create( \WPDesk\ShopMagicCart\HookEmitter\CartExpiration::class )
			->constructor(
				get( \WPDesk\ShopMagicCart\Database\CartManager::class ),
				get( \ShopMagicVendor\Psr\Log\LoggerInterface::class )
			),
		\ShopMagicVendor\DI\create( \WPDesk\ShopMagicCart\Frontend\ExitIntent::class )
			->constructor(
				get( \WPDesk\ShopMagic\Frontend\Interceptor\CustomerSessionTracker::class ),
				string( '{shopmagic.abandonedCarts.pluginUrl}/assets/' ),
				get( 'shopmagic.abandonedCarts.version' )
			),
		\ShopMagicVendor\DI\create( \WPDesk\ShopMagicCart\Admin\Statistics::class )
			->constructor( get( \WPDesk\ShopMagicCart\Database\CartRepository::class ) ),
		\ShopMagicVendor\DI\create( \WPDesk\ShopMagicCart\Interceptor\CartMergeOnUserRegistration::class )
			->constructor(
				get( \WPDesk\ShopMagicCart\Database\CartRepository::class ),
				get( \WPDesk\ShopMagicCart\Database\CartManager::class ),
				get( \ShopMagicVendor\Psr\Log\LoggerInterface::class )
			),
	],
	\WPDesk\ShopMagicCart\TestData\CartTestProvider::class => \ShopMagicVendor\DI\create()
		->constructor( get( \WPDesk\ShopMagicCart\Database\CartRepository::class ) ),

	\WPDesk\ShopMagicCart\Database\CartRepository::class => \ShopMagicVendor\DI\create()
		->constructor( get( \WPDesk\ShopMagicCart\Database\CartHydrator::class ) ),
	\WPDesk\ShopMagicCart\Database\CartManager::class => \ShopMagicVendor\DI\create()
		->constructor(
			get( \WPDesk\ShopMagicCart\Database\CartRepository::class ),
			get( \WPDesk\ShopMagicCart\Database\CartHydrator::class )
		),

	\WPDesk\ShopMagicCart\Event\AbandonedCartEvent::class => \ShopMagicVendor\DI\create()
		->constructor(
			get( \WPDesk\ShopMagicCart\Database\CartRepository::class ),
			get( \WPDesk\ShopMagic\Workflow\Outcome\OutcomeRepository::class )
		),
	\WPDesk\ShopMagicCart\Controller\CartsController::class => \ShopMagicVendor\DI\create()
		->constructor(
			get( \WPDesk\ShopMagicCart\Database\CartManager::class ),
			get( \WPDesk\ShopMagicCart\Normalizer\CartNormalizer::class )
		),
];
