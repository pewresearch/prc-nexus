<?php
/**
 * Slack Signature Verifier
 *
 * @package PRC\Platform\Nexus\Integrations\Slack
 */

namespace PRC\Platform\Nexus\Integrations\Slack;

/**
 * Class Slack_Signature_Verifier
 *
 * Verifies Slack request signatures for security.
 */
class Slack_Signature_Verifier {

	/**
	 * Verify Slack request signature.
	 *
	 * @param string $signing_secret The Slack signing secret.
	 * @param string $request_body The raw request body.
	 * @param string $timestamp The X-Slack-Request-Timestamp header.
	 * @param string $signature The X-Slack-Signature header.
	 * @return bool True if signature is valid.
	 */
	public static function verify( $signing_secret, $request_body, $timestamp, $signature ) {
		// Check timestamp is within 5 minutes to prevent replay attacks.
		$current_time = time();
		if ( abs( $current_time - intval( $timestamp ) ) > 300 ) {
			return false;
		}

		// Compute expected signature.
		$base_string        = 'v0:' . $timestamp . ':' . $request_body;
		$expected_signature = 'v0=' . hash_hmac( 'sha256', $base_string, $signing_secret );

		// Compare signatures using timing-safe comparison.
		if ( ! hash_equals( $expected_signature, $signature ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Verify request from headers and body.
	 *
	 * @param array  $headers Request headers.
	 * @param string $body Request body.
	 * @return bool True if valid.
	 */
	public static function verify_request( $headers, $body ) {
		$signing_secret = Slack_Integration::get_signing_secret();

		if ( empty( $signing_secret ) ) {
			return false;
		}

		$timestamp = $headers['x_slack_request_timestamp'] ?? '';
		if ( is_array( $timestamp ) ) {
			$timestamp = array_pop( $timestamp );
		}

		$signature = $headers['x_slack_signature'] ?? '';
		if ( is_array( $signature ) ) {
			$signature = array_pop( $signature );
		}

		if ( empty( $timestamp ) || empty( $signature ) ) {
			return false;
		}

		return self::verify( $signing_secret, $body, $timestamp, $signature );
	}
}
