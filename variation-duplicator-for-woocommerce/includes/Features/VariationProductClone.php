<?php
	/**
	 * Variation Product Clone
	 *
	 * @package    StorePress/VariationDuplicator
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	namespace StorePress\VariationDuplicator\Features;

	use Automattic\WooCommerce\Enums\ProductType;
	use Automattic\WooCommerce\Internal\CostOfGoodsSold\CostOfGoodsSoldController;
	use Automattic\WooCommerce\Internal\DependencyManagement\ContainerException;
	use StorePress\AdminUtils\Traits\SingletonTrait;
	use WC_Product;
	use WP_Post;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	/**
	 * Variation Product Clone Class.
	 *
	 * @name VariationProductClone
	 *
	 * @package    StorePress/Variation_Duplicator_For_WooCommerce
	 * @since      1.0.0
	 */
class VariationProductClone {
	use SingletonTrait;

	/**
	 * Loads hooks.
	 *
	 * @since 1.0.0
	 *
	 * @see   hooks()
	 */
	public function __construct() {
		$this->hooks();

		/**
		 * Variation clone loaded.
		 *
		 * @since 1.0.0
		 */
		do_action( 'variation_duplicator_for_woocommerce_variation_product_clone_loaded', $this );
	}

	/**
	 * Hooks.
	 *
	 * @since 1.0.0
	 */
	public function hooks(): void {
		add_action( 'woocommerce_variation_header', array( $this, 'add_checkbox' ) );
		add_action( 'woocommerce_variable_product_bulk_edit_actions', array( $this, 'add_dropdown' ) );
		add_action( 'woo_variation_duplicator_load_variations', array( $this, 'notice' ) );
		add_action( 'woocommerce_bulk_edit_variations', array( $this, 'clone' ), 10, 4 );
		add_action( 'woocommerce_variable_product_before_variations', array( $this, 'instruction' ) );

		remove_action( 'wp_ajax_woocommerce_load_variations', array( 'WC_AJAX', 'load_variations' ) );
		add_action( 'wp_ajax_woocommerce_load_variations', array( $this, 'load_variations' ) );
	}

	/**
	 * Notice.
	 *
	 * @return void
	 */
	public function notice(): void {
		$results      = get_transient( 'woo_variation_duplicator_cloned_ids' );
		$limit_exceed = get_transient( 'woo_variation_duplicator_exceed_clone_limit' );

		if ( $results ) {
			/* translators: %s: Variation IDs */
			$message = sprintf( esc_html__( 'Variation: #%s cloned.', 'variation-duplicator-for-woocommerce' ), implode( ', #', $results ) );
			printf( '<div class="inline notice variation-duplicator-for-woocommerce-notice" data-duplicated-ids="%s"><p>%s</p></div>', esc_attr( wp_json_encode( $results ) ), wp_kses_post( $message ) );
			delete_transient( 'woo_variation_duplicator_cloned_ids' );
		}

		if ( $limit_exceed ) {
			printf( '<div class="inline notice variation-duplicator-for-woocommerce-notice error"><p>%s</p></div>', esc_html__( 'Variation clone limit exceed.', 'variation-duplicator-for-woocommerce' ) );
			delete_transient( 'woo_variation_duplicator_exceed_clone_limit' );
		}
	}

	/**
	 * Instruction.
	 *
	 * @return void
	 */
	public function instruction(): void {
		printf( '<button id="variation-duplicator-for-woocommerce-action-button" disabled type="button" class="button">%s</button>', esc_html__( 'Bulk duplicate', 'variation-duplicator-for-woocommerce' ) );
	}


	/**
	 * Get the Cost of Goods Sold value for a product (0 if it's null), return null if the Cost of Goods Sold feature is disabled.
	 *
	 * @param WC_Product $product_object Product object.
	 *
	 * @return float|null Cost of the product, or null.
	 * @throws ContainerException Error when resolving the class to an object instance, or class not found.
	 * @see \WC_AJAX::base_cost_or_null()
	 */
	public static function base_cost_or_null( WC_Product $product_object ): ?float {
		return wc_get_container()->get( CostOfGoodsSoldController::class )->feature_is_enabled() ? ( $product_object->get_cogs_value() ?? 0 ) : null;
	}

	// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

	/**
	 * Render a variation editor.
	 *
	 * NOTE! Do NOT remove the apparently unused function arguments.
	 * These are actually used inside the included html-variation-admin template.
	 *
	 * @param WC_Product $product_object   Parent product of the variation being edited.
	 * @param WC_Product $variation_object Variation being edited.
	 * @param int        $loop             Index of the variation being rendered.
	 * @param float|null $base_cost        Default cost for variations, null if the Cost of Goods Sold feature is disabled.
	 *
	 * @return void
	 * @see \WC_AJAX::render_variation_html()
	 */
	public static function render_variation_html( WC_Product $product_object, WC_Product $variation_object, int $loop, ?float $base_cost = null ): void {
		$variation_id   = $variation_object->get_id();
		$variation      = get_post( $variation_id );
		$variation_data = array_merge( get_post_custom( $variation_id ), wc_get_product_variation_attributes( $variation_id ) ); // kept for BW compatibility.
		include dirname( WC_PLUGIN_FILE ) . '/includes/admin/meta-boxes/views/html-variation-admin.php';
	}
	// phpcs:enable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

	/**
	 * Load variations.
	 *
	 * @return void
	 * @throws ContainerException Error when resolving the class to an object instance, or class not found.
	 * @see \WC_AJAX::load_variations()
	 */
	public function load_variations(): void {
		ob_start();

		check_ajax_referer( 'load-variations', 'security' );

		if ( ! current_user_can( 'edit_products' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
			wp_die();
		}

		if ( ! isset( $_POST['product_id'] ) ) {
			wp_die();
		}

		// Set $post global so its available, like within the admin screens.
		global $post;

		$loop           = 0;
		$product_id     = absint( $_POST['product_id'] );
		$post           = get_post( $product_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$product_object = wc_get_product( $product_id );
		$per_page       = ! empty( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 10;
		$page           = ! empty( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
		$variations     = wc_get_products(
			array(
				'status'  => array( 'private', 'publish' ),
				'type'    => ProductType::VARIATION, // Product type: variation.
				'parent'  => $product_id,
				'limit'   => $per_page,
				'page'    => $page,
				'orderby' => array(
					'menu_order' => 'ASC',
					'ID'         => 'DESC',
				),
				'return'  => 'objects',
			)
		);

		if ( $variations ) {
			/**
			 * Action after variations are loaded.
			 *
			 * @param WC_Product $product_object Product object.
			 * @param array      $variations     Variations array.
			 *
			 * @since 1.0.0
			 */
			do_action( 'woo_variation_duplicator_load_variations', $product_object, $variations );
			wc_render_invalid_variation_notice( $product_object );

			$base_cost = self::base_cost_or_null( $product_object );

			foreach ( $variations as $variation_object ) {
				self::render_variation_html( $product_object, $variation_object, $loop, $base_cost );
				++$loop;
			}
		}

		wp_die();
	}

	/**
	 * Add checkbox.
	 *
	 * @param WP_Post $variation Variation.
	 *
	 * @return void
	 */
	public function add_checkbox( WP_Post $variation ): void {
		/**
		 * Filter to disable variation clone checkbox.
		 *
		 * @param bool    $disable   Whether to disable.
		 * @param WP_Post $variation Variation object.
		 *
		 * @since 1.0.0
		 */
		if ( apply_filters( 'disable_variation_duplicator_for_woocommerce_variation_clone', false, $variation ) ) {
			return;
		}

		printf( '<label class="clone-checkbox"><input class="no-track-change variation_is_cloneable" type="checkbox" name="variation_is_cloneable[]" value="%d"><span class="clone-text" data-clone-text="%s" data-clone-save-text="%s"></span> %s </label> ', absint( $variation->ID ), esc_attr__( 'Duplicate', 'variation-duplicator-for-woocommerce' ), esc_attr__( 'Save before duplicate', 'variation-duplicator-for-woocommerce' ), wp_kses_post( wc_help_tip( esc_attr__( 'If you want to duplicate this variation you have to save this variation first.', 'variation-duplicator-for-woocommerce' ) ) ) );
	}

	/**
	 * Add dropdown.
	 *
	 * @return void
	 */
	public function add_dropdown(): void {
		/**
		 * Filter to disable variation clone dropdown.
		 *
		 * @param bool $disable Whether to disable.
		 *
		 * @since 1.0.0
		 */
		if ( apply_filters( 'disable_variation_duplicator_for_woocommerce_variation_clone', false ) ) {
			return;
		}

		printf( '<option class="woo_variation_duplicate_option" value="woo_variation_duplicate">%s</option>', esc_html__( 'Duplicate variations', 'variation-duplicator-for-woocommerce' ) );
	}

	/**
	 * Clone variations.
	 *
	 * @param string               $bulk_action Bulk action.
	 * @param array<string, mixed> $data        Data.
	 * @param int                  $product_id  Product ID.
	 * @param WP_Post[]            $_variations Variations.
	 *
	 * @return void
	 */
	public function clone( string $bulk_action, array $data, int $product_id, array $_variations ): void {
		if ( 'woo_variation_duplicate' === $bulk_action && ! empty( $data['items'] ) ) {
			$get_limit = absint( $data['times'] );
			$exceed    = wc_string_to_bool( $data['exceed'] );

			/**
			 * Filter variation clone limit.
			 *
			 * @param int $limit Default limit.
			 *
			 * @since 1.0.0
			 */
			$clone_limit = absint( apply_filters( 'woo_variation_duplicator_clone_limit', 9 ) );
			$times       = ( $get_limit > $clone_limit ) ? 1 : $get_limit;

			if ( $exceed ) {
				set_transient( 'woo_variation_duplicator_exceed_clone_limit', 1 );
			}

			$variation_ids = array_map( 'absint', $data['items'] );
			$cloned_ids    = array();

			foreach ( $variation_ids as $variation_id ) {
				for ( $i = 1; $i <= $times; $i++ ) {
					// Main Variation.
					$variation_object = wc_get_product_object( 'variation', $variation_id );

					$cloned_variation_object = wc_get_product_object( 'variation', $variation_id );
					$cloned_variation_object->set_props( array( 'id' => 0 ) );
					$cloned_variation_object->set_parent_id( $product_id );
					$cloned_variation_id = $cloned_variation_object->save();

					$cloned_ids[] = $cloned_variation_id;

					/**
					 * Action during variation save.
					 *
					 * @param int        $cloned_variation_id     Cloned variation ID.
					 * @param int        $variation_id            Original variation ID.
					 * @param WC_Product $cloned_variation_object Cloned variation object.
					 * @param WC_Product $variation_object        Original variation object.
					 * @param int[]      $variation_ids           All variation IDs.
					 * @param int        $i                       Current iteration.
					 *
					 * @since 1.0.0
					 */
					do_action( 'woo_variation_duplicator_variation_save', $cloned_variation_id, $variation_id, $cloned_variation_object, $variation_object, $variation_ids, $i );
				}
			}

			/**
			 * Action after all variations are saved.
			 *
			 * @param array     $variation_ids Variation IDs.
			 * @param array     $cloned_ids    Cloned IDs.
			 * @param int       $product_id    Product ID.
			 * @param WP_Post[] $_variations   Variations.
			 *
			 * @since 1.0.0
			 */
			do_action( 'woo_variation_duplicator_variation_saved', $variation_ids, $cloned_ids, $product_id, $_variations );
			clean_post_cache( $product_id );

			set_transient( 'woo_variation_duplicator_cloned_ids', $cloned_ids );
		}
	}
}
