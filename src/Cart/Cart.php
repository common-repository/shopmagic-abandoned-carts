<?php
declare( strict_types=1 );

namespace WPDesk\ShopMagicCart\Cart;

interface Cart {
	const ACTIVE    = 'active';
	const RECOVERED = 'recovered';
	const FRESH     = 'new';
	const ABANDONED = 'abandoned';
	const ORDERED   = 'ordered';
	const SUBMITTED = 'submitted';
}
