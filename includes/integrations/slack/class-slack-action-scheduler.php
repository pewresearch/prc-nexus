<?php
/**
 * Slack Action Scheduler
 *
 * @package PRC\Platform\Nexus\Integrations\Slack
 */

namespace PRC\Platform\Nexus\Integrations\Slack;

use PRC\Platform\Nexus\Abilities\Trending_News_Analysis;
use ActionScheduler;

/**
 * Class Slack_Action_Scheduler
 *
 * Handles background processing via Action Scheduler.
 */
class Slack_Action_Scheduler {

	/**
	 * Action hook name for trending news analysis.
	 */
	const ACTION_HOOK = 'prc_nexus_slack_trending_news_analysis';

	/**
	 * Initialize action scheduler hooks.
	 */
	public static function init() {
		add_action( self::ACTION_HOOK, array( __CLASS__, 'process_trending_news_analysis' ), 10, 3 );
	}

	/**
	 * Schedule a trending news analysis job.
	 *
	 * @param array  $args Analysis arguments.
	 * @param string $response_url Slack response URL.
	 * @param array  $context Additional context (user info, etc).
	 * @return int Action ID.
	 */
	public static function schedule_trending_news_analysis( $args, $response_url, $context = array() ) {
		$job_id = as_enqueue_async_action(
			self::ACTION_HOOK,
			array(
				'args'         => $args,
				'response_url' => $response_url,
				'context'      => $context,
			),
			'prc-nexus-slack'
		);

		return $job_id;
	}

	/**
	 * Process trending news analysis job.
	 *
	 * @param array  $args Analysis arguments.
	 * @param string $response_url Slack response URL.
	 * @param array  $context Additional context.
	 */
	public static function process_trending_news_analysis( $args, $response_url, $context ) {
		try {
			// Force JSON format for structured data.
			$args['output_format'] = 'json';

			// Run the analysis.
			$ability = new Trending_News_Analysis();
			$result  = $ability->run( $args );

			// Check for errors.
			if ( isset( $result['error'] ) && ! empty( $result['error'] ) ) {
				self::send_error_to_slack( $response_url, $result['error'], $args, $context );
				return;
			}

			// Parse JSON response with depth limit.
			$json_data = json_decode( $result['response'], true, 512 );

			// Validate JSON parsing.
			if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $json_data ) || empty( $json_data ) ) {
				self::send_error_to_slack(
					$response_url,
					'Failed to parse analysis results',
					$args,
					$context
				);
				return;
			}

			// Get channel ID from context.
			$channel_id = $context['channel_id'] ?? null;

			if ( ! $channel_id ) {
				self::send_error_to_slack(
					$response_url,
					'Missing channel ID for posting messages',
					$args,
					$context
				);
				return;
			}

			// Load Slack API client.
			require_once __DIR__ . '/class-slack-api-client.php';

			// Post each story as an individual message with its own thread.
			$success_count = 0;
			foreach ( $json_data as $index => $story ) {
				// Format slim summary for this individual story.
				$story_summary = Slack_Response_Formatter::format_individual_story_summary(
					$story,
					$index,
					$args,
					$context
				);

				// Post story summary to channel.
				$story_response = Slack_API_Client::post_message(
					$channel_id,
					$story_summary['blocks'],
					$story_summary['text'] ?? 'Trending Story'
				);

				// If we got a timestamp, post the full analysis as a thread reply.
				if ( $story_response && isset( $story_response['ts'] ) ) {
					$message_ts = $story_response['ts'];

					// Format full story details.
					$story_details = Slack_Response_Formatter::format_story_thread_message(
						$story,
						$index
					);

					// Post as thread reply.
					$thread_response = Slack_API_Client::post_message(
						$channel_id,
						$story_details['blocks'],
						$story_details['text'] ?? '',
						$message_ts
					);

					if ( $thread_response ) {
						++$success_count;
					}
				}

				// Delay between stories to avoid rate limits.
				usleep( 1000000 ); // 1 second.
			}

			// If no stories were posted successfully, fall back to response_url.
			// This is useful, for now, if the bot is not in the channel or if its a private message.
			if ( 0 === $success_count ) {
				$fallback_message = Slack_Response_Formatter::format_trending_news_response(
					$result,
					$args,
					$context
				);
				self::send_to_slack( $response_url, $fallback_message );
			} else {
				// Send completion notification to the user.
				$user_name = $context['user_name'] ?? null;
				if ( $user_name ) {
					$completion_message = array(
						'text'   => "👋 <@{$user_name}>, 🌀 Nexus here, your trending news analysis is ready for review 👆",
						'blocks' => array(
							array(
								'type' => 'section',
								'text' => array(
									'type' => 'mrkdwn',
									'text' => "👋 <@{$user_name}>, 🌀 Nexus here, your trending news analysis is ready for review 👆",
								),
							),
						),
					);

					Slack_API_Client::post_message(
						$channel_id,
						$completion_message['blocks'],
						$completion_message['text']
					);
				}
			}
		} catch ( \Exception $e ) {
			self::send_error_to_slack(
				$response_url,
				'An unexpected error occurred: ' . $e->getMessage(),
				$args,
				$context
			);
		}
	}

	/**
	 * Send response to Slack via webhook.
	 *
	 * @param string $response_url Slack response URL.
	 * @param array  $payload Response payload.
	 * @return array|bool Response data or false on failure.
	 */
	private static function send_to_slack( $response_url, $payload ) {
		$response = wp_remote_post(
			$response_url,
			array(
				'body'    => wp_json_encode( $payload ),
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status_code ) {
			return false;
		}

		// Try to decode response body for message timestamp.
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		return is_array( $data ) ? $data : true;
	}

	/**
	 * Send error message to Slack.
	 *
	 * @param string $response_url Slack response URL.
	 * @param string $error_message Error message.
	 * @param array  $args Original arguments.
	 * @param array  $context Context information.
	 */
	private static function send_error_to_slack( $response_url, $error_message, $args, $context ) {
		$payload = Slack_Response_Formatter::format_error_response(
			$error_message,
			$args,
			$context
		);

		self::send_to_slack( $response_url, $payload );
	}
}

// Initialize action scheduler hooks.
Slack_Action_Scheduler::init();
