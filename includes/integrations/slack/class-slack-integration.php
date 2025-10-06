<?php
/**
 * Slack Integration for Trending News Analysis
 *
 * @package PRC\Platform\Nexus\Integrations
 */

namespace PRC\Platform\Nexus\Integrations\Slack;

use PRC\Platform\Nexus\Abilities\Trending_News_Analysis;

/**
 * Class Slack_Integration
 *
 * Main integration class for Slack bot functionality.
 */
class Slack_Integration {

	/**
	 * Option key for Slack settings.
	 *
	 * @var string
	 */
	const SETTINGS_KEY = 'prc_nexus_slack_settings';

	/**
	 * The loader object.
	 *
	 * @var object
	 */
	protected $loader;

	/**
	 * Constructor.
	 *
	 * @param object $loader The loader object.
	 */
	public function __construct( $loader = null ) {
		$this->loader = $loader;
		if ( null !== $loader ) {
			$loader->add_action( 'init', $this, 'init' );
			$loader->add_action( 'admin_init', $this, 'register_settings' );

			// Load and initialize admin settings early.
			if ( is_admin() ) {
				require_once __DIR__ . '/class-slack-admin-settings.php';
				new Slack_Admin_Settings( $loader );
			}
		}
	}

	/**
	 * Initialize the Slack integration.
	 *
	 * @hook init
	 */
	public function init() {
		// Load dependencies.
		require_once __DIR__ . '/class-slack-rest-api.php';
		require_once __DIR__ . '/class-slack-signature-verifier.php';
		require_once __DIR__ . '/class-slack-response-formatter.php';
		require_once __DIR__ . '/class-slack-action-scheduler.php';
		require_once __DIR__ . '/class-slack-api-client.php';

		// Initialize REST API.
		new Slack_Rest_API();
	}

	/**
	 * Register settings for Slack integration.
	 *
	 * @hook admin_init
	 */
	public function register_settings() {
		register_setting(
			'prc_nexus_settings',
			self::SETTINGS_KEY,
			array(
				'type'              => 'object',
				'description'       => 'Slack integration settings',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'show_in_rest'      => false,
				'default'           => array(
					'enabled'        => false,
					'signing_secret' => '',
					'bot_token'      => '',
					'workspace_id'   => '',
					'rate_limit'     => 10, // Requests per user per hour.
				),
			)
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array $input The input settings.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings( $input ) {
		// Verify nonce for security.
		if ( isset( $_POST['prc_nexus_slack_nonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['prc_nexus_slack_nonce'] ) ), 'prc_nexus_slack_settings' ) ) {
				add_settings_error(
					self::SETTINGS_KEY,
					'invalid_nonce',
					'Security check failed. Please try again.',
					'error'
				);
				return get_option( self::SETTINGS_KEY, array() );
			}
		}

		$sanitized = array();

		$sanitized['enabled']        = ! empty( $input['enabled'] );
		$sanitized['signing_secret'] = sanitize_text_field( $input['signing_secret'] ?? '' );
		$sanitized['bot_token']      = sanitize_text_field( $input['bot_token'] ?? '' );
		$sanitized['workspace_id']   = sanitize_text_field( $input['workspace_id'] ?? '' );
		$sanitized['rate_limit']     = absint( $input['rate_limit'] ?? 10 );

		return $sanitized;
	}

	/**
	 * Get Slack settings.
	 *
	 * @return array Settings array.
	 */
	public static function get_settings() {
		$defaults = array(
			'enabled'        => false,
			'signing_secret' => '',
			'bot_token'      => '',
			'workspace_id'   => '',
			'rate_limit'     => 10,
		);

		$settings = get_option( self::SETTINGS_KEY, $defaults );
		$settings = wp_parse_args( $settings, $defaults );

		// Prefer constants over database settings for sensitive values.
		if ( defined( 'PRC_PLATFORM_SLACK_SIGNING_SECRET' ) ) {
			$settings['signing_secret'] = constant( 'PRC_PLATFORM_SLACK_SIGNING_SECRET' );
		}

		if ( defined( 'PRC_PLATFORM_SLACK_TOKEN' ) ) {
			$settings['bot_token'] = constant( 'PRC_PLATFORM_SLACK_TOKEN' );
		}

		if ( defined( 'PRC_PLATFORM_SLACK_WORKSPACE_ID' ) ) {
			$settings['workspace_id'] = constant( 'PRC_PLATFORM_SLACK_WORKSPACE_ID' );
		}

		return $settings;
	}

	/**
	 * Check if Slack integration is enabled.
	 *
	 * @return bool True if enabled and configured.
	 */
	public static function is_enabled() {
		$settings = self::get_settings();

		return $settings['enabled'] &&
			! empty( $settings['signing_secret'] ) &&
			! empty( $settings['bot_token'] );
	}

	/**
	 * Get bot token.
	 *
	 * @return string Bot token.
	 */
	public static function get_bot_token() {
		// Prefer constant over database setting for security.
		if ( defined( 'PRC_PLATFORM_SLACK_TOKEN' ) ) {
			return constant( 'PRC_PLATFORM_SLACK_TOKEN' );
		}
		$settings = self::get_settings();
		return $settings['bot_token'];
	}

	/**
	 * Get signing secret.
	 *
	 * @return string Signing secret.
	 */
	public static function get_signing_secret() {
		// Prefer constant over database setting for security.
		if ( defined( 'PRC_PLATFORM_SLACK_SIGNING_SECRET' ) ) {
			return constant( 'PRC_PLATFORM_SLACK_SIGNING_SECRET' );
		}
		$settings = self::get_settings();
		return $settings['signing_secret'];
	}

	/**
	 * Get rate limit.
	 *
	 * @return int Rate limit per user per hour.
	 */
	public static function get_rate_limit() {
		$settings = self::get_settings();
		return absint( $settings['rate_limit'] );
	}

	/**
	 * Get workspace ID.
	 *
	 * @return string Workspace ID.
	 */
	public static function get_workspace_id() {
		// Prefer constant over database setting for security.
		if ( defined( 'PRC_PLATFORM_SLACK_WORKSPACE_ID' ) ) {
			return constant( 'PRC_PLATFORM_SLACK_WORKSPACE_ID' );
		}
		$settings = self::get_settings();
		return $settings['workspace_id'];
	}
}
