<?php

declare( strict_types=1 );

namespace WPDesk\ShopMagicCart;

use ShopMagicVendor\Psr\Container\ContainerInterface;
use WPDesk\ShopMagic\Workflow\Placeholder\TemplateRendererForPlaceholders;
use WPDesk\ShopMagicCart\Event\AbandonedCartEvent;

class CartExtension extends \WPDesk\ShopMagic\Extensions\AbstractExtension {

	/** @var ContainerInterface */
	private $container;

	public function __construct( ContainerInterface $container ) {
		$this->container = $container;
	}

	public function get_events(): array {
		return [
			'cart_abandoned_event' => $this->container->get( AbandonedCartEvent::class ),
		];
	}

	public function get_filters(): array {
		return [
			'cart_date_created'    => new Filter\CartDateCreated(),
			'cart_item_categories' => new Filter\CartItemCategories(),
			'cart_item_count'      => new Filter\CartItemCount(),
			'cart_items'           => new Filter\CartItems(),
			'cart_total'           => new Filter\CartTotal(),
		];
	}

	public function get_placeholders(): array {
		return [
			new Placeholder\CartItemCount(),
			new Placeholder\CartItems( TemplateRendererForPlaceholders::with_template_dir( 'products_ordered' ) ),
			new Placeholder\CartTotal(),
			new Placeholder\CartLink(),
		];
	}


}
