<?php
	/**
	 * Plugin Deactivation Feedback Integration File.
	 *
	 * Handles the deactivation feedback dialog for the plugin.
	 *
	 * @package    StorePress/VariationDuplicator
	 * @since      1.0.0
	 * @version    1.0.0
	 */

	namespace StorePress\VariationDuplicator\Integrations;

	defined( 'ABSPATH' ) || die( 'Keep Silent' );

	use StorePress\AdminUtils\Abstracts\AbstractDeactivationFeedback;
	use StorePress\AdminUtils\Traits\SingletonTrait;
	use StorePress\VariationDuplicator\Traits\UtilityHelperTrait;

	/**
	 * Deactivation feedback dialog integration.
	 *
	 * Extends the base AbstractDeactivationFeedback with plugin-specific titles,
	 * API endpoint, settings data, and selectable deactivation reasons.
	 *
	 * @name    DeactivationFeedback
	 * @package StorePress/Variation_Duplicator_For_WooCommerce
	 * @since   1.0.0
	 *
	 * @phpstan-use SingletonTrait<DeactivationFeedback>
	 *
	 * @example $feedback = DeactivationFeedback::get_instance();
	 * @example $title    = DeactivationFeedback::get_instance()->title();
	 */
class DeactivationFeedback extends AbstractDeactivationFeedback {

	use SingletonTrait;
	use UtilityHelperTrait;

	// =====================================================================
	// Dialog Identity Methods
	// =====================================================================

	/**
	 * Returns the deactivation feedback dialog title.
	 *
	 * @return  string
	 * @since   1.0.0
	 * @see     sub_title()
	 * @example DeactivationFeedback::get_instance()->title(); // 'QUICK FEEDBACK from Variatio Dduplicator For WooCommerce'
	 */
	public function title(): string {
		return esc_html__( 'QUICK FEEDBACK', 'variation-duplicator-for-woocommerce' );
	}

	/**
	 * Returns the deactivation feedback dialog subtitle.
	 *
	 * @return string
	 * @since  1.0.0
	 * @see    title()
	 */
	public function sub_title(): string {
		return esc_html__( 'May we have a little info about why you are deactivating?', 'variation-duplicator-for-woocommerce' );
	}

	// =====================================================================
	// API Methods
	// =====================================================================

	/**
	 * Returns the API endpoint URL to submit deactivation feedback.
	 *
	 * @return  string Full URL of the feedback API endpoint.
	 * @since   1.0.0
	 * @example DeactivationFeedback::instance()->api_url(); // 'https://example.com/wp-json/feedback/v1/deactivate'
	 */
	public function api_url(): string {
		return 'https://stats.storepress.com/wp-json/storepress/v1/deactivations/';
	}

	// =====================================================================
	// Data Provider Methods
	// =====================================================================

	/**
	 * Returns the current plugin settings to include with the feedback payload.
	 *
	 * @return array<string, mixed>
	 * @since  1.0.0
	 * @see    get_buttons()
	 * @see    get_reasons()
	 */
	public function options(): array {
		return array();
	}

	/**
	 * Returns action button definitions for the deactivation dialog.
	 *
	 * @return  array<int, array<string, mixed>> List of button configuration arrays.
	 * @since   1.0.0
	 * @see     get_reasons()
	 * @example DeactivationFeedback::get_instance()->get_buttons(); // Returns submit and skip-deactivate button configs.
	 */
	public function get_buttons(): array {

		return array(
			array(
				'type'       => 'button',
				'label'      => esc_html__( 'Send feedback & Deactivate', 'variation-duplicator-for-woocommerce' ),
				'attributes' => array(
					'disabled'        => true,
					'type'            => 'submit',
					'data-action'     => 'submit',
					'data-label'      => esc_html__( 'Send feedback & Deactivate', 'variation-duplicator-for-woocommerce' ),
					'data-processing' => esc_html__( 'Deactivate...', 'variation-duplicator-for-woocommerce' ),
					'class'           => array( 'button', 'button-primary' ),
				),
				'spinner'    => true,
			),
			array(
				'type'       => 'link',
				'label'      => esc_html__( 'Skip & Deactivate', 'variation-duplicator-for-woocommerce' ),
				'attributes' => array(
					'href'  => '#',
					'class' => array( 'skip-deactivate' ),
				),
			),
		);
	}

	/**
	 * Returns the selectable deactivation reasons for the feedback dialog.
	 *
	 * @return  array<string, array<string, mixed>> Keyed by reason slug; each entry contains title, optional message, and optional input config.
	 * @since   1.0.0
	 * @see     get_buttons()
	 * @example DeactivationFeedback::instance()->get_reasons(); // Returns all reason slugs with their display config.
	 */
	public function get_reasons(): array {
		$current_user = wp_get_current_user();
		$name         = $this->get_plugin_name();

		$support_ticket = sprintf( '<a target="_blank" href="https://getwooplugins.com/tickets/">%s</a>', esc_html__( 'support ticket', 'variation-duplicator-for-woocommerce' ) );
		$documentation  = sprintf( '<a target="_blank" href="https://getwooplugins.com/documentation/variation-duplicator-for-woocommerce/">%s</a>', esc_html__( 'documentation', 'variation-duplicator-for-woocommerce' ) );

		return array(
			'temporary_deactivation'        => array(
				'title' => esc_html__( 'It\'s a temporary deactivation.', 'variation-duplicator-for-woocommerce' ),
			),

			'dont_know_about'               => array(
				'title'   => esc_html__( 'I couldn\'t understand how to make it work.', 'variation-duplicator-for-woocommerce' ),
				'message' => esc_html__( 'On any variable product\'s "Variations" tab, tick the new "Duplicate" checkbox next to the variations you want to copy, click "Bulk Duplicate," enter how many copies you need, and hit "OK" — the plugin instantly creates identical copies for you to tweak.', 'variation-duplicator-for-woocommerce' ),
			),

			'found_a_better_plugin'         => array(
				'title' => esc_html__( 'I found a better plugin.', 'variation-duplicator-for-woocommerce' ),
				'input' => array(
					'placeholder' => esc_html__( 'Would you mind sharing which plugin you switched to?', 'variation-duplicator-for-woocommerce' ),
				),
			),

			'broke_site_layout'             => array(
				/* translators: %s: 'broke my layout' wrapped in <strong>. */ 'title' => sprintf( esc_html__( 'The plugin %s or some functionality.', 'variation-duplicator-for-woocommerce' ), '<strong>' . esc_html__( 'broke my layout', 'variation-duplicator-for-woocommerce' ) . '</strong>' ),
				/* translators: %s: Support ticket link HTML. */ 'message'            => sprintf( esc_html__( 'Sorry to hear the plugin is causing layout issues on your site! Could you please open a %s with details and a screenshot of the issue? We\'d be happy to look into it and get it fixed for you as soon as possible.', 'variation-duplicator-for-woocommerce' ), $support_ticket ),
			),

			'plugin_setup_help'             => array(
				/* translators: %s: 'setup this plugin.' wrapped in <strong>. */ 'title' => sprintf( esc_html__( 'I need someone to %s', 'variation-duplicator-for-woocommerce' ), '<strong>' . esc_html__( 'setup this plugin.', 'variation-duplicator-for-woocommerce' ) . '</strong>' ),
				'input'   => array(
					'placeholder' => esc_html__( 'Your email address.', 'variation-duplicator-for-woocommerce' ),
					'value'       => sanitize_email( $current_user->user_email ),
				),
				'message' => esc_html__( 'Happy to help you get set up! Please share your email address with us and our team will reach out shortly to walk you through the setup — completely free of charge.', 'variation-duplicator-for-woocommerce' ),
			),

			'plugin_config_too_complicated' => array(
				/* translators: %s: 'too complicated to configure.' wrapped in <strong>. */ 'title' => sprintf( esc_html__( 'The plugin is %s', 'variation-duplicator-for-woocommerce' ), '<strong>' . esc_html__( 'too complicated to configure.', 'variation-duplicator-for-woocommerce' ) . '</strong>' ),
				/* translators: %s: Documentation link HTML. */ 'message'                           => sprintf( esc_html__( 'Sorry to hear the configuration feels overwhelming! We\'ve put together a step-by-step %1$s guide that covers everything in plain language — please give it a read first, and if anything is still unclear, just open a %2$s and we\'ll be glad to walk you through it personally.', 'variation-duplicator-for-woocommerce' ), $documentation, $support_ticket ),
			),

			'need_specific_feature'         => array(
				'title' => esc_html__( 'I need specific feature that you don\'t support.', 'variation-duplicator-for-woocommerce' ),

				'input' => array(
					'placeholder' => esc_html__( 'Could you share the specific feature you\'re looking for? ', 'variation-duplicator-for-woocommerce' ),
				),
			),

			'no_longer_needed'              => array(
				'title' => esc_html__( 'This plugin isn\'t something I need at the moment.', 'variation-duplicator-for-woocommerce' ),
			),

			'other'                         => array(
				'title' => esc_html__( 'Other', 'variation-duplicator-for-woocommerce' ),
				'input' => array(
					'placeholder' => esc_html__( 'Could you please share the reason?', 'variation-duplicator-for-woocommerce' ),
				),
			),
		);
	}
}
