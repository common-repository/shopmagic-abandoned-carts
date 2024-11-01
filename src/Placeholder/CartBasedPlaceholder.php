<?php

namespace WPDesk\ShopMagicCart\Placeholder;

use WPDesk\ShopMagic\Workflow\Placeholder\Placeholder;
use WPDesk\ShopMagicCart\Cart\BaseCart;

abstract class CartBasedPlaceholder extends Placeholder {
	final public function get_required_data_domains(): array {
		return [ BaseCart::class ];
	}

	final protected function get_cart(): BaseCart {
		return $this->resources->get( BaseCart::class );
	}

	final protected function is_cart_provided(): bool {
		return $this->resources->has( BaseCart::class );
	}
}
