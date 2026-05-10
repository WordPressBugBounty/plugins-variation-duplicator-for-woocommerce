<?php
	/**
	 * Backend Service Provider Class File.
	 *
	 * Manages service registration and bootstrapping for the plugin's dependency
	 * injection container. Handles the lifecycle of plugin services including
	 * registration and initialization.
	 *
	 * @package    StorePress/VariationDuplicator
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	declare( strict_types=1 );

	namespace StorePress\VariationDuplicator\ServiceProviders;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	use StorePress\AdminUtils\Abstracts\AbstractServiceProvider;
	use StorePress\AdminUtils\Traits\SingletonTrait;
	use StorePress\VariationDuplicator\Features\Backend;
	use StorePress\VariationDuplicator\Traits\UtilityHelperTrait;

	/**
	 * Service provider for the Blocks feature.
	 *
	 * Registers and boots the {@see Blocks} service into the DI container.
	 *
	 * @name    BackendServiceProvider
	 * @package StorePress/Variation_Duplicator_For_WooCommerce
	 * @since   2.0.0
	 *
	 * @phpstan-use SingletonTrait<BackendServiceProvider>
	 *
	 * @example BackendServiceProvider::instance()->register();
	 * @example BackendServiceProvider::instance()->boot();
	 */
class BackendServiceProvider extends AbstractServiceProvider {

	use SingletonTrait;
	use UtilityHelperTrait;

	// =====================================================================
	// Service Lifecycle Methods
	// =====================================================================

	/**
	 * Registers the Backend service factory into the DI container.
	 *
	 * @since  1.0.0
	 * @return void
	 * @see    boot()
	 */
	public function register(): void {
		$this->get_container()->register(
			Backend::class,
			function () {
				return Backend::instance();
			}
		);
	}

	/**
	 * Resolves and boots the Backend service from the DI container.
	 *
	 * @since  1.0.0
	 * @return void
	 * @see    register()
	 */
	public function boot(): void {
		$this->get_container()->get( Backend::class );
	}
}
