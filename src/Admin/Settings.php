<?php

declare( strict_types=1 );

namespace WPDesk\ShopMagicCart\Admin;

use ShopMagicVendor\WPDesk\Forms\Field\CheckboxField;
use ShopMagicVendor\WPDesk\Forms\Field\InputNumberField;
use ShopMagicVendor\WPDesk\Forms\Field\InputTextField;
use ShopMagicVendor\WPDesk\Forms\Field\TextAreaField;
use WPDesk\ShopMagic\Admin\Settings\FieldSettingsTab;

final class Settings extends FieldSettingsTab {
	const TIMEOUT                = 'abandoned_cart_timeout';
	const TIMEOUT_DEFAULT        = '5';
	const EXPIRATION             = 'abandoned_cart_expiration';
	const ENABLE_EXIT_POPUP      = 'exit_popup';
	const EXIT_POPUP_TITLE       = 'exit_popup_title';
	const EXIT_POPUP_CONTENT     = 'exit_popup_content';
	const ENABLE_EXIT_POPUP_TEST = 'popup_test';

	public static function get_tab_slug(): string {
		return 'carts';
	}

	public function get_tab_name(): string {
		return esc_html__( 'Abandoned Carts', 'shopmagic-abandoned-carts' );
	}

	/** @return \ShopMagicVendor\WPDesk\Forms\Field[] */
	public function get_fields(): array {
		return [
			( new InputNumberField() )
				->set_default_value( self::TIMEOUT_DEFAULT )
				->set_required()
				->set_attribute( 'min', self::TIMEOUT_DEFAULT )
				->set_label( esc_html__( 'Abandoned cart timeout (in minutes)', 'shopmagic-abandoned-carts' ) )
				->set_name( self::TIMEOUT ),
			( new CheckboxField() )
				->set_name( self::EXPIRATION )
				->set_label( esc_html__( 'Enable Carts clear', 'shopmagic-abandoned-carts' ) )
				->set_description( esc_html__( 'Automatically clear Carts tab after 30 days. This option will affect cart statistics, at they will be available for the last 30 days only.',
					'shopmagic-abandoned-carts' ) ),
			( new CheckboxField() )
				->set_name( self::ENABLE_EXIT_POPUP )
				->set_label( esc_html__( 'Enable Exit Intent Popup', 'shopmagic-abandoned-carts' ) )
				->set_description( esc_html__( 'Show your customers a popup message right before they leave your store to save more recoverable abandoned carts.',
					'shopmagic-abandoned-carts' ) ),
			( new InputTextField() )
				->set_name( self::EXIT_POPUP_TITLE )
				->set_placeholder( self::title_default() )
				->set_label( esc_html__( 'Main title', 'shopmagic-abandoned-carts' ) ),
			( new TextAreaField() )
				->set_name( self::EXIT_POPUP_CONTENT )
				->set_placeholder( self::content_default() )
				->set_label( esc_html__( 'Content', 'shopmagic-abandoned-carts' ) ),
			( new CheckboxField() )
				->set_name( self::ENABLE_EXIT_POPUP_TEST )
				->set_label( esc_html__( 'Enable popup test mode', 'shopmagic-abandoned-carts' ) )
				->set_description( esc_html__( 'Test mode allows you to see popup on cart page when you are logged in.',
					'shopmagic-abandoned-carts' ) ),
		];
	}

	public static function title_default(): string {
		return esc_html__( 'Oh-oh, your cart was left alone... 😞', 'shopmagic-abandoned-carts' );
	}

	public static function content_default(): string {
		return esc_html__( 'Just leave your email address and we will make sure it gets to safety and is not forgotten!',
			'shopmagic-abandoned-carts' );
	}
}
