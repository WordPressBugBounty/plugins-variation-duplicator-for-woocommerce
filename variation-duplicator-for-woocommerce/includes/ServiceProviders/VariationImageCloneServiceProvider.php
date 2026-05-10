<?php
	/**
	 * Variation Image Clone Service Provider Class File.
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
	use StorePress\VariationDuplicator\Features\VariationImageClone;
	use StorePress\VariationDuplicator\Traits\UtilityHelperTrait;

	/**
	 * Service provider for the Blocks feature.
	 *
	 * Registers and boots the {@see Blocks} service into the DI container.
	 *
	 * @name    VariationImageCloneServiceProvider
	 * @package StorePress/Variation_Duplicator_For_WooCommerce
	 * @since   2.0.0
	 *
	 * @phpstan-use SingletonTrait<VariationImageCloneServiceProvider>
	 *
	 * @example VariationImageCloneServiceProvider::instance()->register();
	 * @example VariationImageCloneServiceProvider::instance()->boot();
	 */
class VariationImageCloneServiceProvider extends AbstractServiceProvider {

	use SingletonTrait;
	use UtilityHelperTrait;

	// =====================================================================
	// Service Lifecycle Methods
	// =====================================================================

	/**
	 * Registers the Variation Image Clone service factory into the DI container.
	 *
	 * @since  1.0.0
	 * @return void
	 * @see    boot()
	 */
	public function register(): void {
		$this->get_container()->register(
			VariationImageClone::class,
			function () {
				return VariationImageClone::instance();
			}
		);
	}

	/**
	 * Resolves and boots the Variation Image Clone service from the DI container.
	 *
	 * @since  1.0.0
	 * @return void
	 * @see    register()
	 */
	public function boot(): void {
		$this->get_container()->get( VariationImageClone::class );
	}
}
