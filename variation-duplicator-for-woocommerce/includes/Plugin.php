<?php
	/**
	 * Main Plugin Initialization Class File.
	 *
	 * Entry point for the Variation Duplicator For WooCommerce plugin; handles autoloading,
	 * service provider bootstrapping, and plugin lifecycle management.
	 *
	 * @package    StorePress/VariationDuplicator
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	namespace StorePress\VariationDuplicator;

	use Automattic\WooCommerce\Utilities\FeaturesUtil;
	use StorePress\VariationDuplicator\ServiceProviders\BackendServiceProvider;
	use StorePress\VariationDuplicator\ServiceProviders\VariationProductCloneServiceProvider;
	use StorePress\VariationDuplicator\ServiceProviders\VariationImageCloneServiceProvider;
	use StorePress\VariationDuplicator\ServiceProviders\DeactivationServiceProvider;
	use StorePress\VariationDuplicator\ServiceProviders\ServiceProviders;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );


	/**
	 * Main plugin bootstrap class.
	 *
	 * Manages autoloading, WordPress hook registration, and service provider
	 * bootstrapping for the Variatio Duplicator For WooCommerce plugin.
	 *
	 * @name    Plugin
	 * @package StorePress/Variation_Duplicator_For_WooCommerce
	 * @since   1.0.0
	 *
	 * @example Plugin::instance();
	 * @example Plugin::instance()->get_plugin_file();
	 */
class Plugin {

	/**
	 * Absolute path to the main plugin file.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected string $plugin_file;

	// =====================================================================
	// Service Lifecycle Methods
	// =====================================================================

	/**
	 * Returns the singleton Plugin instance, creating it on first call.
	 *
	 * @return static
	 * @since  1.0.0
	 */
	public static function instance(): self {
		static $instance = null;

		return $instance ??= new self();
	}

	/**
	 * Loads includes, registers hooks, and boots all service providers.
	 *
	 * @since 1.0.0
	 * @see   includes()
	 * @see   hooks()
	 * @see   init()
	 */
	public function __construct() {
		$this->includes();
		$this->hooks();
		$this->init();

		/**
		 * Fires after the Variatio Dduplicator For WooCommerce plugin has fully loaded.
		 *
		 * @param Plugin $instance The plugin instance.
		 *
		 * @since 1.0.0
		 */
		do_action( 'variation_duplicator_for_woocommerce_loaded', $this );
	}

	/**
	 * Loads the Composer autoloader and plugin utility functions.
	 *
	 * @return void
	 * @since  1.0.0
	 * @see    init()
	 */
	public function includes(): void {

		require_once __DIR__ . '/functions.php';

		$vendor_path = untrailingslashit( plugin_dir_path( $this->get_plugin_file() ) ) . '/vendor';

		if ( file_exists( $vendor_path . '/autoload_packages.php' ) ) {
			require_once $vendor_path . '/autoload_packages.php';
		}
	}

	/**
	 * Registers plugin-level WordPress actions and filters.
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public function hooks(): void {
		// Declare HPOS compatibility before WooCommerce initializes.
		add_action( 'before_woocommerce_init', array( $this, 'custom_order_tables_compatibility' ) );
	}

	/**
	 * Boots all service providers.
	 *
	 * @return void
	 * @since  1.0.0
	 * @see    service_providers()
	 */
	public function init(): void {
		$this->service_providers();
	}

	// =====================================================================
	// Plugin Identity Methods
	// =====================================================================

	/**
	 * Returns the absolute path to the main plugin file.
	 *
	 * @return  string
	 * @since   1.0.0
	 * @example Plugin::instance()->get_plugin_file(); // '/path/to/variation-duplicator-for-woocommerce.php'
	 */
	public function get_plugin_file(): string {
		return get_plugin_file();
	}

	// =====================================================================
	// Service Provider Registration Methods
	// =====================================================================

	/**
	 * Returns all service provider class names to register and boot.
	 *
	 * @return  array<int, class-string>
	 * @since   1.0.0
	 * @see     service_providers()
	 * @example Plugin::instance()->get_service_providers();
	 */
	public function get_service_providers(): array {
		return array(
			BackendServiceProvider::class,
			VariationProductCloneServiceProvider::class,
			VariationImageCloneServiceProvider::class,
			DeactivationServiceProvider::class,
		);
	}

	/**
	 * Instantiates and returns the ServiceProviders runner.
	 *
	 * @return  ServiceProviders
	 * @since   1.0.0
	 * @see     get_service_providers()
	 * @example Plugin::instance()->service_providers();
	 */
	public function service_providers(): ServiceProviders {
		return ServiceProviders::instance( $this->get_service_providers() );
	}

	// =====================================================================
	// Hook Callbacks
	// =====================================================================

	/**
	 * Declare compatibility with custom order tables for WooCommerce.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function custom_order_tables_compatibility(): void {
		if ( class_exists( FeaturesUtil::class ) ) {
			FeaturesUtil::declare_compatibility( 'custom_order_tables', $this->get_plugin_file() );
		}
	}
}
