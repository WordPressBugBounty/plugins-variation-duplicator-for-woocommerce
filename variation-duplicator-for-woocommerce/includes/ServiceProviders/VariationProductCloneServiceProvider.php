<?php
	/**
	 * Variation Clone Service Provider Class File.
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
	use StorePress\VariationDuplicator\Features\VariationProductClone;
	use StorePress\VariationDuplicator\Traits\UtilityHelperTrait;

	/**
	 * Service provider for the Blocks feature.
	 *
	 * Registers and boots the {@see Blocks} service into the DI container.
	 *
	 * @name    VariationProductCloneServiceProvider
	 * @package StorePress/VariationDuplicator
	 * @since   2.0.0
	 *
	 * @phpstan-use SingletonTrait<VariationProductCloneServiceProvider>
	 *
	 * @example VariationCloneServiceProvider::instance()->register();
	 * @example VariationCloneServiceProvider::instance()->boot();
	 */
class VariationProductCloneServiceProvider extends AbstractServiceProvider {

	use SingletonTrait;
	use UtilityHelperTrait;

	// =====================================================================
	// Service Lifecycle Methods
	// =====================================================================

	/**
	 * Registers the Variation Clone service factory into the DI container.
	 *
	 * @since  1.0.0
	 * @return void
	 * @see    boot()
	 */
	public function register(): void {
		$this->get_container()->register(
			VariationProductClone::class,
			function () {
				return VariationProductClone::instance();
			}
		);
	}

	/**
	 * Resolves and boots the Variation Clone service from the DI container.
	 *
	 * @since  1.0.0
	 * @return void
	 * @see    register()
	 */
	public function boot(): void {
		$this->get_container()->get( VariationProductClone::class );
	}
}
