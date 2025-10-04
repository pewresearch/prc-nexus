<?php
/**
 * Slack API Client
 *
 * @package PRC\Platform\Nexus\Integrations\Slack
 */

namespace PRC\Platform\Nexus\Integrations\Slack;

/**
 * Class Slack_API_Client
 *
 * Handles direct Slack API calls for threading and advanced features.
 */
class Slack_API_Client {

	/**
	 * Post a message to a Slack channel.
	 *
	 * @param string $channel_id Channel ID.
	 * @param array  $blocks Message blocks.
	 * @param string $text Fallback text.
	 * @param string $thread_ts Optional thread timestamp for replies.
	 * @return array|false Response data or false on failure.
	 */
	public static function post_message( $channel_id, $blocks, $text = '', $thread_ts = null ) {
		$bot_token = Slack_Integration::get_bot_token();

		if ( empty( $bot_token ) ) {
			return false;
		}

		$payload = array(
			'channel'      => $channel_id,
			'blocks'       => $blocks,
			'text'         => $text, // Fallback for notifications.
			'unfurl_links' => false, // Disable link previews.
			'unfurl_media' => false, // Disable media previews.
		);

		// Add thread_ts if this is a reply.
		if ( ! empty( $thread_ts ) ) {
			$payload['thread_ts'] = $thread_ts;
		}

		$response = wp_remote_post(
			'https://slack.com/api/chat.postMessage',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $bot_token,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $payload ),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! isset( $body['ok'] ) || ! $body['ok'] ) {
			return false;
		}

		return $body;
	}

	/**
	 * Post multiple threaded messages.
	 *
	 * @param string $channel_id Channel ID.
	 * @param string $thread_ts Thread timestamp.
	 * @param array  $messages Array of message data (each with blocks and text).
	 * @return bool True if all messages posted successfully.
	 */
	public static function post_thread_messages( $channel_id, $thread_ts, $messages ) {
		$success = true;

		foreach ( $messages as $message ) {
			$result = self::post_message(
				$channel_id,
				$message['blocks'],
				$message['text'] ?? '',
				$thread_ts
			);

			if ( false === $result ) {
				$success = false;
			}

			// Small delay to avoid rate limits.
			usleep( 100000 ); // 0.1 seconds.
		}

		return $success;
	}
}
