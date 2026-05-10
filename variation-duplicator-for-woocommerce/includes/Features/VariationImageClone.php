<?php
	/**
	 * Variation image clone
	 *
	 * @package    StorePress/VariationDuplicator
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	namespace StorePress\VariationDuplicator\Features;

	use StorePress\AdminUtils\Traits\HelperMethodsTrait;
	use StorePress\AdminUtils\Traits\SingletonTrait;
	use StorePress\VariationDuplicator\Traits\UtilityHelperTrait;
	use WC_Product;
	use WP_Post;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	/**
	 * VariationImageClone Class.
	 *
	 * @name VariationImageClone
	 *
	 * @package    StorePress/Variation_Duplicator_For_WooCommerce
	 * @since      1.0.0
	 */
class VariationImageClone {
	use SingletonTrait;
	use UtilityHelperTrait;
	use HelperMethodsTrait;

	/**
	 * Loads includes.
	 *
	 * @since 1.0.0
	 *
	 * @see   hooks()
	 */
	public function __construct() {
		$this->hooks();

		/**
		 * Variation image clone loaded.
		 *
		 * @since 1.0.0
		 */
		do_action( 'variation_duplicator_for_woocommerce_variation_image_clone_loaded', $this );
	}

	/**
	 * Hooks.
	 *
	 * @since 1.0.0
	 */
	public function hooks(): void {
		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'form' ), 10, 3 );
		add_action( 'woocommerce_save_product_variation', array( $this, 'prepare' ), 999 );
		add_action( 'woo_variation_duplicator_load_variations', array( $this, 'notice' ) );
	}

	/**
	 * Form.
	 *
	 * @param int                  $loop           Loop.
	 * @param array<string, mixed> $variation_data Variation data.
	 * @param WP_Post              $variation      Variation.
	 *
	 * @return void
	 */
	public function form( int $loop, array $variation_data, WP_Post $variation ): void {
		/**
		 * Filter to disable variation duplicator image clone.
		 *
		 * @param bool                 $disable        Whether to disable image clone.
		 * @param int                  $loop           Loop counter.
		 * @param array<string, mixed> $variation_data Variation data.
		 * @param WP_Post              $variation      Variation object.
		 *
		 * @since 1.0.0
		 */
		if ( apply_filters( 'disable_variation_duplicator_for_woocommerce_image_clone', false, $loop, $variation_data, $variation ) ) {
			return;
		}

		$variation_id = absint( $variation->ID );
		$parent_id    = wp_get_post_parent_id( $variation_id );
		$product      = wc_get_product( $parent_id );
		$child_ids    = $product->get_children();
		$child_ids    = array_diff( $child_ids, array( $variation_id ) );
		$image_id     = $variation_data['_thumbnail_id'] ?? 0;

		$selected_variation          = wc_get_product_object( 'variation', $variation_id );
		$selected_variation_image_id = absint( $selected_variation->get_image_id() );

		include $this->templates_path() . '/html-variation-duplicator-form.php';
	}

	/**
	 * Notice.
	 *
	 * @return void
	 */
	public function notice(): void {
		$results = get_transient( 'woo_variation_duplicator_image_cloned' );

		if ( $results ) {
			printf( '<div class="inline notice variation-duplicator-for-woocommerce-notice"><p>%s</p></div>', esc_html__( 'Variation image cloned.', 'variation-duplicator-for-woocommerce' ) );
			delete_transient( 'woo_variation_duplicator_image_cloned' );
		}
	}

	/**
	 * Prepare.
	 *
	 * @param int $variation_id Variation ID.
	 *
	 * @return void
	 */
	public function prepare( int $variation_id ): void {

		if ( ! isset( $_POST['variable_image_duplicate_type'] ) ) {
			return;
		}

		if ( ! isset( $_POST['variable_image_duplicator_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['variable_image_duplicator_nonce'] ) ), 'variable_image_duplicator_action' ) ) {
			return;
		}

		$variation             = wc_get_product_object( 'variation', $variation_id );
		$current_variation     = $variation;
		$variation_data        = $variation->get_data();
		$parent_product_id     = $variation->get_parent_id();
		$current_variation_img = absint( $variation_data['image_id'] );

		$clone_type = isset( $_POST['variable_image_duplicate_type'][ $variation_id ] ) ? sanitize_text_field( wp_unslash( $_POST['variable_image_duplicate_type'][ $variation_id ] ) ) : '';

		// To Post Data.

		$variable_image_to_post_data = array_map( 'sanitize_text_field', wp_unslash( $_POST['variable_image_duplicate_to'][ $variation_id ] ?? array() ) );
		$variation_img_to            = array_map( 'absint', $variable_image_to_post_data );

		// From Post Data.
		$variable_image_from_post_data = sanitize_text_field( wp_unslash( $_POST['variable_image_duplicate_from'][ $variation_id ] ?? 0 ) );
		$variation_img_from            = absint( $variable_image_from_post_data );

		/**
		 * Action to prepare variation duplicator.
		 *
		 * @param WC_Product $current_variation  Current variation object.
		 * @param string     $clone_type         Clone type.
		 * @param array      $variation_img_to   Variation images to.
		 * @param int        $variation_img_from Variation image from.
		 *
		 * @since 1.0.0
		 */
		do_action( 'woo_variation_duplicator_prepare', $current_variation, $clone_type, $variation_img_to, $variation_img_from );

		// Set (this) variation image to given variations.
		$is_cloned = false;
		if ( 'to' === $clone_type && ! empty( $variation_img_to ) ) {
			foreach ( $variation_img_to as $id ) {

				$selected_variation      = wc_get_product_object( 'variation', $id );
				$selected_variation_data = $selected_variation->get_data();
				$selected_variation_img  = absint( $selected_variation_data['image_id'] );

				if ( empty( $current_variation_img ) ) {
					continue;
				}

				$is_cloned = true;

				$this->save( $selected_variation, $current_variation_img, $current_variation );

				/**
				 * Action after variation image is saved to.
				 *
				 * @param WC_Product $selected_variation    Selected variation object.
				 * @param WC_Product $current_variation     Current variation object.
				 * @param int        $current_variation_img Current variation image ID.
				 * @param int        $parent_product_id     Parent product ID.
				 *
				 * @since 1.0.0
				 */
				do_action( 'woo_variation_duplicator_image_saved_to', $selected_variation, $current_variation, $current_variation_img, $parent_product_id );
				clean_post_cache( $selected_variation->get_id() );
			}

			if ( $is_cloned ) {
				set_transient( 'woo_variation_duplicator_image_cloned', 'yes' );
			}
		}

		// Set (this) variation image from given variation or product featured image.
		if ( 'from' === $clone_type && ! empty( $variation_img_from ) ) {
			// Variation product.
			if ( 'product_variation' === get_post_type( $variation_img_from ) ) {
				$selected_variation      = wc_get_product_object( 'variation', $variation_img_from );
				$selected_variation_data = $selected_variation->get_data();
				$selected_variation_img  = absint( $selected_variation_data['image_id'] );
				$selected_variation_img  = ( ( $selected_variation_img > 0 ) ? $selected_variation_img : absint( $selected_variation->get_image_id() ) );
			} elseif ( 'attachment' === get_post_type( $variation_img_from ) && wp_attachment_is_image( $variation_img_from ) ) {
				// Image from product gallery.
				$selected_variation     = wc_get_product_object( 'variable', isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : null );
				$selected_variation_img = absint( $variation_img_from );
			} else {
				// Image from product featured image.
				$selected_variation     = wc_get_product_object( 'variable', $variation_img_from );
				$selected_variation_img = absint( $selected_variation->get_image_id() );
			}

			if ( empty( $selected_variation_img ) ) {
				return;
			}

			$this->save( $current_variation, $selected_variation_img, $selected_variation );

			/**
			 * Action after variation image is saved from.
			 *
			 * @param WC_Product $current_variation      Current variation object.
			 * @param WC_Product $selected_variation     Selected variation object.
			 * @param int        $selected_variation_img Selected variation image ID.
			 * @param int        $parent_product_id      Parent product ID.
			 *
			 * @since 1.0.0
			 */
			do_action( 'woo_variation_duplicator_image_saved_from', $current_variation, $selected_variation, $selected_variation_img, $parent_product_id );
			clean_post_cache( $current_variation->get_id() );
			set_transient( 'woo_variation_duplicator_image_cloned', 'yes' );
		}

		clean_post_cache( $parent_product_id );
	}

	/**
	 * Save variation image.
	 *
	 * @param WC_Product $variation          Variation object.
	 * @param int        $image_id           Image ID.
	 * @param WC_Product $selected_variation Selected variation object.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function save( WC_Product $variation, int $image_id, WC_Product $selected_variation ): void {

		if ( ! is_ajax() ) {
			return;
		}

		/**
		 * Filter variation image ID.
		 *
		 * @param int        $image_id           Image ID.
		 * @param WC_Product $variation          Variation object.
		 * @param WC_Product $selected_variation Selected variation object.
		 *
		 * @since 1.0.0
		 */
		$image_id = apply_filters( 'woo_variation_duplicator_image_id', $image_id, $variation, $selected_variation );

		$variation->set_props(
			array(
				'image_id' => absint( $image_id ),
			) 
		);

		$variation->save();
	}
}
