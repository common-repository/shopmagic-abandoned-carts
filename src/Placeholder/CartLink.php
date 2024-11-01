<?php

declare( strict_types=1 );

namespace WPDesk\ShopMagicCart\Placeholder;

use WPDesk\ShopMagic\Workflow\Placeholder\Helper\PlaceholderUTMBuilder;
use WPDesk\ShopMagicCart\Frontend\CartRestore;

final class CartLink extends CartBasedPlaceholder {

	/** @var PlaceholderUTMBuilder */
	private $utm_builder;

	public function __construct() {
		$this->utm_builder = new PlaceholderUTMBuilder();
	}

	public function get_slug(): string {
		return 'link';
	}

	public function get_description(): string {
		return $this->utm_builder->get_description();
	}

	public function get_supported_parameters( $values = null ): array {
		return $this->utm_builder->get_utm_fields();
	}

	public function value( array $parameters ): string {
		return $this->utm_builder->append_utm_parameters_to_uri( $parameters,
			CartRestore::get_restore_url( $this->get_cart()->get_token() ) );
	}
}
