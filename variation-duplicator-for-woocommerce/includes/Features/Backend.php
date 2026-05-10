<?php
/**
 * Backend Feature.
 *
 * @package    StorePress/VariationDuplicator
 * @since      1.0.0
 * @version    2.0.9
 */

namespace StorePress\VariationDuplicator\Features;

use StorePress\AdminUtils\Traits\SingletonTrait;
use StorePress\VariationDuplicator\Traits\UtilityHelperTrait;

defined( 'ABSPATH' ) || die( 'Keep Silent' );

/**
 * Backend Class.
 *
 * Handles admin-side asset enqueueing for the Variation Duplicator plugin.
 *
 * @name    Backend
 * @package StorePress/VariationDuplicator
 * @since   1.0.0
 *
 * @phpstan-use SingletonTrait<Backend>
 *
 * @example
 * // Retrieve the singleton instance.
 * $backend = Backend::instance();
 */
class Backend {
	use SingletonTrait;
	use UtilityHelperTrait;

	// =====================================================================
	// Service Lifecycle Methods
	// =====================================================================

	/**
	 * Constructor. Registers hooks and fires the backend loaded action.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->hooks();
		$this->init();

		/**
		 * Fires after the Backend feature has been fully loaded.
		 *
		 * @since 1.0.0
		 *
		 * @param Backend $instance Current Backend instance.
		 */
		do_action( 'variation_duplicator_for_woocommerce_backend_loaded', $this );
	}

	// =====================================================================
	// Service Provider Registration Methods
	// =====================================================================

	/**
	 * Registers WordPress action and filter hooks.
	 *
	 * @since  1.0.0
	 * @return void
	 * @see    self::admin_enqueue_scripts()
	 */
	public function hooks(): void {
		// Enqueue admin scripts and styles on the product edit screen.
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}

	/**
	 * Performs any additional initialization after hooks are registered.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function init(): void {
	}

	// =====================================================================
	// Hook Callback Methods
	// =====================================================================

	/**
	 * Enqueues scripts and styles on the product edit screen.
	 *
	 * Loads the compiled JS/CSS bundle and passes localised data (clone
	 * limit and UI strings) to the script via `wp_localize_script()`.
	 * Only runs on the `product` admin screen.
	 *
	 * @since  1.0.0
	 * @return void
	 * @see    self::hooks()
	 *
	 * @example
	 * // Triggered automatically via add_action( 'admin_enqueue_scripts', ... ).
	 * // To call manually in tests:
	 * $backend->admin_enqueue_scripts();
	 */
	public function admin_enqueue_scripts(): void {
		$screen    = get_current_screen();
		$screen_id = $screen->id ?? '';

		if ( 'product' !== $screen_id ) {
			return;
		}

		// Editor Scripts.
		$admin_script_src_url    = $this->build_url() . '/variation-duplicator-for-woocommerce.js';
		$admin_css_src_url       = $this->build_url() . '/variation-duplicator-for-woocommerce.css';
		$admin_script_asset_file = $this->build_path() . '/variation-duplicator-for-woocommerce.asset.php';
		$admin_script_asset      = include $admin_script_asset_file;

		wp_enqueue_style( 'variation-duplicator-for-woocommerce', $admin_css_src_url, array(), $admin_script_asset['version'] );

		wp_enqueue_script( 'variation-duplicator-for-woocommerce', $admin_script_src_url, array( 'jquery', 'wp-util', 'select2' ), $admin_script_asset['version'], array( 'strategy' => 'defer' ) );

		/**
		 * Filters the maximum number of clones allowed per variation.
		 *
		 * @since 1.0.0
		 *
		 * @param int $clone_limit Maximum clone count. Default 9.
		 */
		$clone_limit = absint( apply_filters( 'woo_variation_duplicator_clone_limit', 9 ) );
		$translation = array(
			'saveNoticeText' => esc_html__( 'Action Required: Save or update the product before duplicating variations.', 'variation-duplicator-for-woocommerce' ),
			'noCheckedText'  => esc_html__( 'Select a variation to duplicate.', 'variation-duplicator-for-woocommerce' ),
			/* translators: %d: maximum number of clones allowed per variation */
			'limitText'      => sprintf( esc_html__( "Set how many times each variation should clone. \nDefault value is 1. Limit is %d.", 'variation-duplicator-for-woocommerce' ), $clone_limit ),
			'limit'          => $clone_limit,
		);

		wp_localize_script( 'variation-duplicator-for-woocommerce', 'WooVariationDuplicator', $translation );
	}
}
