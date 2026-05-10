<?php
	/**
	 * Duplicate Variations for WooCommerce
	 *
	 * @package StorePress/VariationDuplicator
	 *
	 * Plugin Name: Duplicate Variations for WooCommerce
	 * Plugin URI: https://wordpress.org/plugins/variation-duplicator-for-woocommerce/
	 * Description: Duplicate WooCommerce variable product variations with its all available properties including Variation Price, Variation Image, and SKU in just a single click.
	 * Author: Emran Ahmed
	 * Version: 3.0.0
	 * Requires PHP: 7.4
	 * Requires at least: 5.6
	 * Tested up to: 6.9
	 * WC requires at least: 5.6
	 * WC tested up to: 10.7
	 * Text Domain: variation-duplicator-for-woocommerce
	 * License: GPL v3 or later
	 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
	 * Domain Path: /languages
	 * Author URI: https://getwooplugins.com/
	 * Requires Plugins: woocommerce
	 */

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	use StorePress\VariationDuplicator\Plugin;

	define( 'VARIATION_DUPLICATOR_FOR_WOOCOMMERCE_PLUGIN_FILE', __FILE__ );

	/**
	 * Plugin Instance.
	 *
	 * @return Plugin
	 */
function variation_duplicator_for_woocommerce(): Plugin {

	// Include the main class.
	if ( ! class_exists( Plugin::class, false ) ) {
		require_once plugin_dir_path( __FILE__ ) . 'includes/Plugin.php';
	}

	// Include the main class.
	return Plugin::instance();
}

	/**
	 * Plugin Init.
	 *
	 * @return void
	 * @since 1.0.0
	 */
function variation_duplicator_for_woocommerce_init() {
	if ( ! class_exists( 'WooCommerce', false ) ) {
		return;
	}

	// Init Plugin.
	variation_duplicator_for_woocommerce();
}

	add_action( 'plugins_loaded', 'variation_duplicator_for_woocommerce_init' );
