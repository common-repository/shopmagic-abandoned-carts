<?php

namespace WPDesk\ShopMagicCart\HookEmitter;

use ShopMagicVendor\WPDesk\Forms\Field\CheckboxField;
use WPDesk\ShopMagic\Helper\Conditional;
use WPDesk\ShopMagic\Helper\WordPressFormatHelper;
use WPDesk\ShopMagic\HookEmitter\RecurringCleaner;
use WPDesk\ShopMagicCart\Admin\Settings;
use WPDesk\ShopMagicCart\Cart\Cart;

/**
 * Cleans cart table on recurrent manner.
 */
final class CartExpiration extends RecurringCleaner implements Conditional {
	public static function is_needed(): bool {
		return \filter_var( Settings::get_option( Settings::EXPIRATION ), \FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * @return iterable<Cart>
	 * @throws \Exception
	 */
	protected function get_items_to_clean(): iterable {
		$cut_time = ( new \DateTimeImmutable( 'now', wp_timezone() ) )
			->modify( RecurringCleaner::DEFAULT_EXPIRATION_TIME );

		return $this->persister->get_repository()->find_by(
			[
				[
					'field'     => 'last_modified',
					'condition' => '<=',
					'value'     => WordPressFormatHelper::datetime_as_mysql( $cut_time ),
				],
			]
		);
	}
}
