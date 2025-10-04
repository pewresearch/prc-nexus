<?php
/**
 * Slack REST API
 *
 * @package PRC\Platform\Nexus\Integrations\Slack
 */

namespace PRC\Platform\Nexus\Integrations\Slack;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Class Slack_Rest_API
 *
 * Handles REST API endpoints for Slack integration.
 */
class Slack_Rest_API {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'prc_api_endpoints', array( $this, 'register_endpoints' ) );
	}

	/**
	 * Register REST API endpoints.
	 *
	 * @hook prc_api_endpoints
	 * @param array $endpoints Current endpoints.
	 * @return array Modified endpoints.
	 */
	public function register_endpoints( $endpoints ) {
		$endpoints[] = array(
			'route'               => 'nexus/slack/trending-news',
			'methods'             => 'POST',
			'callback'            => array( $this, 'handle_trending_news_command' ),
			'permission_callback' => array( $this, 'verify_slack_request' ),
			'args'                => array(),
		);

		$endpoints[] = array(
			'route'               => 'nexus/slack/interactive',
			'methods'             => 'POST',
			'callback'            => array( $this, 'handle_interactive_action' ),
			'permission_callback' => array( $this, 'verify_slack_request' ),
			'args'                => array(),
		);

		return $endpoints;
	}

	/**
	 * Verify Slack request signature.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return bool|WP_Error True if valid, WP_Error otherwise.
	 */
	public function verify_slack_request( $request ) {
		if ( ! Slack_Integration::is_enabled() ) {
			return new WP_Error(
				'slack_disabled',
				'Slack integration is not enabled',
				array( 'status' => 403 )
			);
		}

		$headers = $request->get_headers();
		$body    = $request->get_body();

		if ( ! Slack_Signature_Verifier::verify_request( $headers, $body ) ) {
			return new WP_Error(
				'invalid_signature',
				'Invalid Slack signature',
				array( 'status' => 401 )
			);
		}

		// Validate workspace ID.
		$params               = $request->get_params();
		$team_id              = '';
		$configured_workspace = Slack_Integration::get_workspace_id();

		// Extract team_id from request.
		if ( ! empty( $params['team_id'] ) ) {
			$team_id = sanitize_text_field( $params['team_id'] );
		} elseif ( ! empty( $params['payload'] ) ) {
			$payload = json_decode( $params['payload'], true, 10 );
			if ( is_array( $payload ) && ! empty( $payload['team']['id'] ) ) {
				$team_id = sanitize_text_field( $payload['team']['id'] );
			}
		}

		// Validate workspace ID if configured.
		if ( ! empty( $configured_workspace ) && $team_id !== $configured_workspace ) {
			return new WP_Error(
				'invalid_workspace',
				'Request from unauthorized workspace',
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Handle trending news slash command.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function handle_trending_news_command( $request ) {
		$params = $request->get_params();

		// Extract and validate Slack command parameters.
		$slack_user_id   = sanitize_text_field( $params['user_id'] ?? '' );
		$slack_user_name = sanitize_text_field( $params['user_name'] ?? '' );
		$channel_id      = sanitize_text_field( $params['channel_id'] ?? '' );
		$response_url    = esc_url_raw( $params['response_url'] ?? '' );
		$command_text    = sanitize_text_field( $params['text'] ?? '' );

		// Validate Slack ID formats.
		if ( ! empty( $slack_user_id ) && ! preg_match( '/^[UW][A-Z0-9]{8,}$/', $slack_user_id ) ) {
			return new WP_REST_Response(
				array(
					'response_type' => 'ephemeral',
					'text'          => '⚠️ Invalid user ID format.',
				),
				200
			);
		}

		if ( ! empty( $channel_id ) && ! preg_match( '/^[CDG][A-Z0-9]{8,}$/', $channel_id ) ) {
			return new WP_REST_Response(
				array(
					'response_type' => 'ephemeral',
					'text'          => '⚠️ Invalid channel ID format.',
				),
				200
			);
		}

		// Validate response_url domain.
		if ( ! empty( $response_url ) ) {
			$parsed_url = wp_parse_url( $response_url );
			if ( empty( $parsed_url['host'] ) || 'hooks.slack.com' !== $parsed_url['host'] ) {
				return new WP_REST_Response(
					array(
						'response_type' => 'ephemeral',
						'text'          => '⚠️ Invalid response URL.',
					),
					200
				);
			}
		}

		// Check rate limiting.
		$rate_limit_check = $this->check_rate_limit( $slack_user_id );
		if ( is_wp_error( $rate_limit_check ) ) {
			return new WP_REST_Response(
				array(
					'response_type' => 'ephemeral',
					'text'          => '⚠️ ' . $rate_limit_check->get_error_message(),
				),
				200
			);
		}

		// Parse command parameters.
		$args = $this->parse_command_text( $command_text );

		// Log usage.
		$this->log_usage( $slack_user_id, $slack_user_name );

		// Schedule background job.
		$job_id = Slack_Action_Scheduler::schedule_trending_news_analysis(
			$args,
			$response_url,
			array(
				'user_id'    => $slack_user_id,
				'user_name'  => $slack_user_name,
				'channel_id' => $channel_id,
			)
		);

		// Return immediate acknowledgment.
		return new WP_REST_Response(
			array(
				'response_type' => 'in_channel',
				'text'          => '🌀 Nexus is analyzing trending news and will notify you when complete.',
				'blocks'        => array(
					array(
						'type' => 'section',
						'text' => array(
							'type' => 'mrkdwn',
							'text' => sprintf(
								'🌀 *Nexus is analyzing trending news*  You will be notified here when the analysis is complete.',
								$job_id,
								$this->format_args_for_display( $args )
							),
						),
					),
					array(
						'type'     => 'context',
						'elements' => array(
							array(
								'type' => 'mrkdwn',
								'text' => sprintf(
									'(Job ID: `%s`) Parameters: %s',
									$job_id,
									$this->format_args_for_display( $args )
								),
							),
						),
					),
				),
			),
			200
		);
	}

	/**
	 * Handle interactive actions (button clicks).
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function handle_interactive_action( $request ) {
		$params  = $request->get_params();
		$payload = json_decode( $params['payload'] ?? '{}', true, 10 );

		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $payload ) ) {
			return new WP_REST_Response(
				array(
					'text' => '❌ Invalid request format',
				),
				200
			);
		}

		$action    = $payload['actions'][0] ?? array();
		$action_id = $action['action_id'] ?? '';

		// Handle different action types.
		switch ( $action_id ) {
			case 'rerun_analysis':
				// Extract original parameters and rerun.
				$value = json_decode( $action['value'] ?? '{}', true );
				return $this->handle_trending_news_command( new WP_REST_Request( 'POST', '', $value ) );

			default:
				return new WP_REST_Response(
					array(
						'text' => '❌ Unknown action',
					),
					200
				);
		}
	}

	/**
	 * Parse command text into arguments.
	 *
	 * @param string $text Command text.
	 * @return array Parsed arguments.
	 */
	private function parse_command_text( $text ) {
		$args = array(
			'category'      => 'nation',
			'total'         => 5,
			'from'          => '',
			'to'            => '',
			'query'         => '',
			'output_format' => 'markdown',
		);

		if ( empty( $text ) ) {
			return $args;
		}

		// Validate input length to prevent ReDoS.
		if ( strlen( $text ) > 1000 ) {
			$text = substr( $text, 0, 1000 );
		}

		// Parse key:value pairs.
		preg_match_all( '/(\w+):([^\s]+(?:\s+(?!\w+:)[^\s]+)*)/', $text, $matches, PREG_SET_ORDER );

		foreach ( $matches as $match ) {
			$key   = $match[1];
			$value = trim( $match[2] );

			switch ( $key ) {
				case 'category':
					$args['category'] = sanitize_text_field( $value );
					break;
				case 'articles':
				case 'number':
				case 'total':
					$args['total'] = min( 100, max( 1, absint( $value ) ) );
					break;
				case 'from':
					$args['from'] = sanitize_text_field( $value );
					break;
				case 'to':
					$args['to'] = sanitize_text_field( $value );
					break;
				case 'search':
				case 'query':
					$args['query'] = sanitize_text_field( $value );
					break;
				case 'format':
					if ( in_array( $value, array( 'json', 'markdown' ), true ) ) {
						$args['output_format'] = $value;
					}
					break;
			}
		}

		return $args;
	}

	/**
	 * Format arguments for display.
	 *
	 * @param array $args Arguments.
	 * @return string Formatted string.
	 */
	private function format_args_for_display( $args ) {
		$parts = array();

		foreach ( $args as $key => $value ) {
			if ( empty( $value ) ) {
				continue;
			}
			$parts[] = sprintf( '`%s: %s`', $key, $value );
		}

		return implode( ', ', $parts );
	}

	/**
	 * Check rate limiting for user.
	 *
	 * @param string $slack_user_id Slack user ID.
	 * @return true|WP_Error True if allowed, WP_Error if rate limited.
	 */
	private function check_rate_limit( $slack_user_id ) {
		$rate_limit = Slack_Integration::get_rate_limit();
		$cache_key  = 'slack_rate_limit_' . md5( $slack_user_id );
		$usage      = wp_cache_get( $cache_key );

		if ( false === $usage ) {
			$usage = 0;
		}

		if ( $usage >= $rate_limit ) {
			return new WP_Error(
				'rate_limit_exceeded',
				sprintf( 'Rate limit exceeded. Maximum %d requests per hour.', $rate_limit )
			);
		}

		return true;
	}

	/**
	 * Log usage for rate limiting.
	 *
	 * @param string $slack_user_id Slack user ID.
	 * @param string $slack_user_name Slack user name.
	 */
	private function log_usage( $slack_user_id, $slack_user_name ) {
		$cache_key = 'slack_rate_limit_' . md5( $slack_user_id );
		$usage     = wp_cache_get( $cache_key );

		if ( false === $usage ) {
			$usage = 0;
		}

		++$usage;
		wp_cache_set( $cache_key, $usage, '', HOUR_IN_SECONDS );

		// Also log to database for analytics (optional).
		do_action( 'prc_nexus_slack_command_used', $slack_user_id, $slack_user_name, time() );
	}
}
