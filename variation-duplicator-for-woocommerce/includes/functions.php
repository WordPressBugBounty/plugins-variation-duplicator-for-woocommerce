<?php
	/**
	 * Plugin Utility Functions File.
	 *
	 * Standalone helpers for container access, color conversion, SVG generation,
	 * CSS class building, and layout/slider calculations.
	 *
	 * @package    StorePress/VariationDuplicator
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	namespace StorePress\VariationDuplicator;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	use StorePress\VariationDuplicator\Containers\Container;

	// =====================================================================
	// Container & Service Access Functions
	// =====================================================================

	/**
	 * Returns the plugin DI container singleton.
	 *
	 * @return Container
	 * @since  1.0.0
	 * @see    get_blocks()
	 * @see    get_block_support()
	 */
function get_container(): Container {
	return Container::instance();
}

	/**
	 * Returns the absolute path to the main plugin file.
	 *
	 * @return string
	 * @since  1.0.0
	 */
function get_plugin_file(): string {
	return constant( 'VARIATION_DUPLICATOR_FOR_WOOCOMMERCE_PLUGIN_FILE' );
}


	/**
	 * Format attribute summary.
	 *
	 * @param string $summary Summary.
	 *
	 * @return string Summary.
	 */
function format_attribute_summary( string $summary ): string {
	// Like: Color: Blue, Logo: No, Size:  One.
	$summary_chunk = explode( ',', $summary );

	// Like: Size:  One.
	$summary_arr = array_map(
		static function ( $chunk ) {
				$parts = explode( ':', $chunk );

				return trim( end( $parts ) );
		},
		$summary_chunk 
	);

	$summary_text = implode( ', ', $summary_arr );

	return ( strlen( $summary_text ) > 55 ) ? substr( $summary_text, 0, 55 ) . '...' : $summary_text;
}
