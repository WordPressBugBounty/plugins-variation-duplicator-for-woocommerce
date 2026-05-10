<?php
/**
 * Compatibility Feature.
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
 * Compatibility Class.
 *
 * Wires third-party plugin integrations (e.g. WC Additional Variation Images)
 * into the Variation Duplicator hook system.
 *
 * @name    Compatibility
 * @package StorePress/VariationDuplicator
 * @since   1.0.0
 *
 * @phpstan-use SingletonTrait<Compatibility>
 *
 * @example
 * // Retrieve the singleton instance.
 * $compatibility = Compatibility::instance();
 */
class Compatibility {
	use SingletonTrait;
	use UtilityHelperTrait;

	// =====================================================================
	// Service Lifecycle Methods
	// =====================================================================

	/**
	 * Constructor. Loads includes, registers hooks, and fires the loaded action.
	 *
	 * @since 1.0.0
	 */
	protected function __construct() {
		$this->includes();
		$this->hooks();
		$this->init();

		/**
		 * Fires after the Compatibility feature has been fully loaded.
		 *
		 * @since 1.0.0
		 *
		 * @param Compatibility $instance Current Compatibility instance.
		 */
		do_action( 'variation_duplicator_for_woocommerce_compatibility_loaded', $this );
	}

	// =====================================================================
	// Service Provider Registration Methods
	// =====================================================================

	/**
	 * Loads any required files before hooks are registered.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	protected function includes(): void {
	}

	/**
	 * Registers compatibility hooks for supported third-party plugins.
	 *
	 * @since  1.0.0
	 * @return void
	 * @see    self::wc_additional_variation_images_support()
	 */
	protected function hooks(): void {
		$this->wc_additional_variation_images_support();
	}

	/**
	 * Performs any additional initialization after hooks are registered.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	protected function init(): void {
	}

	// =====================================================================
	// Hook Callback Methods
	// =====================================================================

	/**
	 * Registers hooks for WC Additional Variation Images compatibility.
	 *
	 * Only registers hooks when the `WC_Additional_Variation_Images` class is
	 * present, ensuring zero overhead when the plugin is not active.
	 *
	 * @since  2.0.1
	 * @return void
	 * @see    self::duplicator_variation_save()
	 * @see    self::duplicator_image_saved_to()
	 * @see    self::duplicator_image_saved_from()
	 *
	 * @example
	 * // Called automatically inside hooks(). To invoke manually:
	 * $compatibility->wc_additional_variation_images_support();
	 */
	public function wc_additional_variation_images_support(): void {
		if ( class_exists( 'WC_Additional_Variation_Images' ) ) {
			// Copy additional images when a variation is duplicated.
			add_action(
				'woo_variation_duplicator_variation_save',
				array(
					$this,
					'duplicator_variation_save',
				),
				10,
				2
			);

			// Sync additional images to the selected (destination) variation.
			add_action(
				'woo_variation_duplicator_image_saved_to',
				array(
					$this,
					'duplicator_image_saved_to',
				),
				10,
				2
			);

			// Sync additional images from the selected (source) variation.
			add_action(
				'woo_variation_duplicator_image_saved_from',
				array(
					$this,
					'duplicator_image_saved_from',
				),
				10,
				2
			);
		}
	}

	/**
	 * Copies additional variation images from the source to the new variation.
	 *
	 * Callback for `woo_variation_duplicator_variation_save`.
	 *
	 * @since  2.0.1
	 * @return void
	 * @see    self::wc_additional_variation_images_support()
	 *
	 * @param int $new_variation_id New (cloned) variation post ID.
	 * @param int $variation_id     Source variation post ID.
	 */
	public function duplicator_variation_save( int $new_variation_id, int $variation_id ): void {
		$images = get_post_meta( $variation_id, '_wc_additional_variation_images', true );

		if ( $images ) {
			update_post_meta( $new_variation_id, '_wc_additional_variation_images', $images );
		}
	}

	/**
	 * Copies additional images from the current variation to the selected (destination) variation.
	 *
	 * Callback for `woo_variation_duplicator_image_saved_to`.
	 *
	 * @since  2.0.1
	 * @return void
	 * @see    self::wc_additional_variation_images_support()
	 *
	 * @param object $selected_variation Destination variation object.
	 * @param object $current_variation  Source variation object.
	 */
	public function duplicator_image_saved_to( object $selected_variation, object $current_variation ): void {
		$images = get_post_meta( $current_variation->get_id(), '_wc_additional_variation_images', true );

		if ( $images ) {
			update_post_meta( $selected_variation->get_id(), '_wc_additional_variation_images', $images );
		}
	}

	/**
	 * Copies additional images from the selected (source) variation to the current variation.
	 *
	 * Callback for `woo_variation_duplicator_image_saved_from`.
	 *
	 * @since  2.0.1
	 * @return void
	 * @see    self::wc_additional_variation_images_support()
	 *
	 * @param object $current_variation  Destination variation object.
	 * @param object $selected_variation Source variation object.
	 */
	public function duplicator_image_saved_from( object $current_variation, object $selected_variation ): void {
		$images = get_post_meta( $selected_variation->get_id(), '_wc_additional_variation_images', true );

		if ( $images ) {
			update_post_meta( $current_variation->get_id(), '_wc_additional_variation_images', $images );
		}
	}
}
