<?php
/**
 * Plugin Name: ShopMagic Abandoned Carts
 * Plugin URI: https://shopmagic.app/products/shopmagic-abandoned-carts/?utm_source=add_plugin_details&utm_medium=link&utm_campaign=plugin_homepage
 * Description: Allows saving customer details on a partial WooCommerce purchase and send abandoned cart emails.
 * Version: 2.2.22
 * Author: WP Desk
 * Author URI: https://shopmagic.app/?utm_source=user-site&utm_medium=quick-link&utm_campaign=author
 * Text Domain: shopmagic-abandoned-carts
 * Domain Path: /lang/
 * Requires at least: 5.0
 * Tested up to: 6.6
 * WC requires at least: 8.9
 * WC tested up to: 9.3
 * Requires PHP: 7.3
 * Requires Plugins: woocommerce,shopmagic-for-woocommerce
 *
 * Copyright 2023 WP Desk Ltd.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/* THESE TWO VARIABLES CAN BE CHANGED AUTOMATICALLY */
$plugin_version = '2.2.22';

$plugin_name        = 'ShopMagic Abandoned Carts';
$plugin_class_name  = '\WPDesk\ShopMagicCart\Plugin';
$product_id         = '';
$plugin_text_domain = 'shopmagic-abandoned-carts';
$plugin_file        = __FILE__;
$plugin_dir         = __DIR__;

$requirements = [
	'php'     => '7.3',
	'wp'      => '6.2',
	'plugins' => [
		[
			'name'      => 'woocommerce/woocommerce.php',
			'nice_name' => 'WooCommerce',
		],
		[
			'name'      => 'shopmagic-for-woocommerce/shopMagic.php',
			'nice_name' => 'ShopMagic',
			'version'   => '4.2.19',
		],
	],
];

require __DIR__ . '/vendor_prefixed/wp-plugin-flow-common/src/plugin-init-php52-free.php';
