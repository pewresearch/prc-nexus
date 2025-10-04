<?php
/**
 * Slack Response Formatter
 *
 * @package PRC\Platform\Nexus\Integrations\Slack
 */

namespace PRC\Platform\Nexus\Integrations\Slack;

/**
 * Class Slack_Response_Formatter
 *
 * Formats responses for Slack using Block Kit.
 */
class Slack_Response_Formatter {

	/**
	 * Format individual story summary for channel.
	 *
	 * @param array $story Single story data.
	 * @param int   $index Story index (0-based).
	 * @param array $args Original arguments.
	 * @param array $context Context information.
	 * @return array Slack message payload.
	 */
	public static function format_individual_story_summary( $story, $index, $args, $context ) {
		$number      = self::get_number_emoji( $index + 1 );
		$title       = self::clean_text( $story['title'] ?? 'Untitled' );
		$source      = $story['source'] ?? '';
		$suggestions = $story['suggestions'] ?? array();
		$angle_count = count( $suggestions );

		// Count total related reports across all suggestions.
		$report_count = 0;
		foreach ( $suggestions as $suggestion ) {
			$report_count += count( $suggestion['links'] ?? array() );
		}

		$blocks = array();

		if ( ! empty( $source ) ) {
			// Story title with emoji number and link.
			$blocks[] = array(
				'type' => 'section',
				'text' => array(
					'type' => 'mrkdwn',
					'text' => sprintf( '%s <%s|*%s*>', $number, $source, $title ),
				),
			);
		} else {
			// Story title with emoji number.
			$blocks[] = array(
				'type' => 'section',
				'text' => array(
					'type' => 'mrkdwn',
					'text' => sprintf( '%s *%s*', $number, $title ),
				),
			);
		}

		// Footer with thread prompt.
		$blocks[] = array(
			'type'     => 'context',
			'elements' => array(
				array(
					'type' => 'mrkdwn',
					'text' => '👇 _Click thread below for full trending news analysis_',
				),
			),
		);

		return array(
			'blocks' => $blocks,
			'text'   => sprintf( '%s %s', $number, $title ), // Fallback text.
		);
	}

	/**
	 * Format individual story for thread.
	 *
	 * @param array $story Single story data.
	 * @param int   $index Story index (0-based).
	 * @return array Message data with blocks and text.
	 */
	public static function format_story_thread_message( $story, $index ) {
		$number      = self::get_number_emoji( $index + 1 );
		$title       = self::clean_text( $story['title'] ?? 'Untitled' );
		$summary     = self::clean_text( $story['summary'] ?? '' );
		$source      = $story['source'] ?? '';
		$suggestions = $story['suggestions'] ?? array();

		$blocks = array();

		// Story header with divider.
		$blocks[] = array(
			'type' => 'section',
			'text' => array(
				'type' => 'mrkdwn',
				'text' => sprintf( '%s *%s*', $number, $title ),
			),
		);

		// Summary.
		if ( ! empty( $summary ) ) {
			$blocks[] = array(
				'type' => 'section',
				'text' => array(
					'type' => 'mrkdwn',
					'text' => sprintf( '*📝 Summary* %s', $summary ),
				),
			);
		}

		// Source.
		if ( ! empty( $source ) ) {
			$blocks[] = array(
				'type'     => 'context',
				'elements' => array(
					array(
						'type' => 'mrkdwn',
						'text' => sprintf( '🔗 *Source:* <%s|%s>', $source, self::extract_domain( $source ) ),
					),
				),
			);
		}

		// Story angles/suggestions.
		if ( ! empty( $suggestions ) ) {
			$blocks[] = array(
				'type' => 'section',
				'text' => array(
					'type' => 'mrkdwn',
					'text' => '*💡 Story Angles for PRC:*',
				),
			);

			foreach ( $suggestions as $suggestion_index => $suggestion ) {
				$headline = self::clean_text( $suggestion['headline'] ?? '' );
				$angle    = self::clean_text( $suggestion['angle'] ?? '' );
				$links    = $suggestion['links'] ?? array();

				if ( ! empty( $headline ) ) {
					$arrow = count( $suggestions ) > 1 ? sprintf( '→ *Angle %d:*', $suggestion_index + 1 ) : '→';

					$blocks[] = array(
						'type' => 'section',
						'text' => array(
							'type' => 'mrkdwn',
							'text' => sprintf( "%s\n_%s_", $arrow, $headline ),
						),
					);

					if ( ! empty( $angle ) ) {
						$blocks[] = array(
							'type' => 'section',
							'text' => array(
								'type' => 'mrkdwn',
								'text' => sprintf( '*How to use:* %s', $angle ),
							),
						);
					}

					// Related research links.
					if ( ! empty( $links ) ) {
						$link_texts = array();
						foreach ( $links as $link ) {
							if ( ! empty( $link['url'] ) && ! empty( $link['title'] ) ) {
								$link_texts[] = sprintf( '• <%s|%s>', $link['url'], $link['title'] );
							}
						}

						if ( ! empty( $link_texts ) ) {
							$blocks[] = array(
								'type'     => 'context',
								'elements' => array(
									array(
										'type' => 'mrkdwn',
										'text' => "*📊 Related PRC Research:*\n" . implode( "\n", $link_texts ),
									),
								),
							);
						}
					}

					// Divider between suggestions. Only if the index is less than last.
					if ( $suggestion_index < count( $suggestions ) - 1 ) {
						$blocks[] = array(
							'type' => 'divider',
						);
					}
				}
			}
		}

		return array(
			'blocks' => $blocks,
			'text'   => sprintf( '%s %s', $number, $title ), // Fallback text.
		);
	}

	/**
	 * Get number emoji for story index.
	 *
	 * @param int $number Number (1-10).
	 * @return string Emoji.
	 */
	private static function get_number_emoji( $number ) {
		$emojis = array(
			1  => '1️⃣',
			2  => '2️⃣',
			3  => '3️⃣',
			4  => '4️⃣',
			5  => '5️⃣',
			6  => '6️⃣',
			7  => '7️⃣',
			8  => '8️⃣',
			9  => '9️⃣',
			10 => '🔟',
		);

		return $emojis[ $number ] ?? sprintf( '%d.', $number );
	}

	/**
	 * Extract domain from URL.
	 *
	 * @param string $url URL.
	 * @return string Domain.
	 */
	private static function extract_domain( $url ) {
		$parsed = wp_parse_url( $url );
		$host   = $parsed['host'] ?? $url;

		// Remove www. prefix.
		return preg_replace( '/^www\./', '', $host );
	}

	/**
	 * Clean text formatting issues from AI responses.
	 *
	 * @param string $text Text to clean.
	 * @return string Cleaned text.
	 */
	private static function clean_text( $text ) {
		if ( empty( $text ) ) {
			return $text;
		}

		// Remove literal \n, \r, \t that might come from AI responses.
		$text = str_replace( array( '\n', '\r', '\t' ), array( ' ', ' ', ' ' ), $text );

		// Clean up multiple spaces.
		$text = preg_replace( '/\s+/', ' ', $text );

		// Trim.
		return trim( $text );
	}

	/**
	 * Legacy format trending news analysis response.
	 * Kept for backward compatibility if JSON parsing fails.
	 *
	 * @param array $result Analysis result.
	 * @param array $args Original arguments.
	 * @param array $context Context information.
	 * @return array Slack message payload.
	 */
	public static function format_trending_news_response( $result, $args, $context ) {
		$response_text = $result['response'] ?? 'No results available.';
		$user_name     = $context['user_name'] ?? 'Unknown user';

		// Try to parse JSON if output_format was json.
		$structured_data = null;
		if ( 'json' === ( $args['output_format'] ?? 'markdown' ) ) {
			$structured_data = json_decode( $response_text, true );
		}

		$blocks = array();

		// Header block.
		$blocks[] = array(
			'type' => 'header',
			'text' => array(
				'type'  => 'plain_text',
				'text'  => '📰 Trending News Analysis Complete',
				'emoji' => true,
			),
		);

		// Metadata block.
		$blocks[] = array(
			'type'   => 'section',
			'fields' => array(
				array(
					'type' => 'mrkdwn',
					'text' => sprintf( '*Requested by:*\n%s', $user_name ),
				),
				array(
					'type' => 'mrkdwn',
					'text' => sprintf( '*Category:*\n%s', $args['category'] ?? 'nation' ),
				),
				array(
					'type' => 'mrkdwn',
					'text' => sprintf( '*Total:*\n%d', $args['total'] ?? 5 ),
				),
				array(
					'type' => 'mrkdwn',
					'text' => sprintf( '*Date:*\n%s', gmdate( 'Y-m-d H:i:s' ) ),
				),
			),
		);

		$blocks[] = array(
			'type' => 'divider',
		);

		// Format the main content based on whether we have structured data.
		if ( $structured_data && is_array( $structured_data ) ) {
			$blocks = array_merge( $blocks, self::format_structured_news_items( $structured_data ) );
		} else {
			// Fallback to markdown formatting.
			// Split long content into chunks (Slack has a 3000 char limit per block).
			$chunks = self::split_markdown_content( $response_text );

			foreach ( $chunks as $chunk ) {
				$blocks[] = array(
					'type' => 'section',
					'text' => array(
						'type' => 'mrkdwn',
						'text' => $chunk,
					),
				);
			}
		}

		return array(
			'response_type' => 'in_channel',
			'blocks'        => $blocks,
		);
	}

	/**
	 * Format structured news items as Slack blocks.
	 *
	 * @param array $data Structured news data.
	 * @return array Slack blocks.
	 */
	private static function format_structured_news_items( $data ) {
		$blocks = array();

		foreach ( $data as $item ) {
			// News item header.
			if ( ! empty( $item['title'] ) ) {
				$blocks[] = array(
					'type' => 'section',
					'text' => array(
						'type' => 'mrkdwn',
						'text' => sprintf( '*%s*', $item['title'] ),
					),
				);
			}

			// Summary.
			if ( ! empty( $item['summary'] ) ) {
				$blocks[] = array(
					'type' => 'section',
					'text' => array(
						'type' => 'mrkdwn',
						'text' => $item['summary'],
					),
				);
			}

			// Source link.
			if ( ! empty( $item['source'] ) ) {
				$blocks[] = array(
					'type'     => 'context',
					'elements' => array(
						array(
							'type' => 'mrkdwn',
							'text' => sprintf( '🔗 <\\%s|Source>', $item['source'] ),
						),
					),
				);
			}

			// Suggestions.
			if ( ! empty( $item['suggestions'] ) && is_array( $item['suggestions'] ) ) {
				foreach ( $item['suggestions'] as $suggestion ) {
					if ( ! empty( $suggestion['headline'] ) ) {
						$blocks[] = array(
							'type' => 'section',
							'text' => array(
								'type' => 'mrkdwn',
								'text' => sprintf( '💡 *Story Angle:*\n_%s_', $suggestion['headline'] ),
							),
						);

						if ( ! empty( $suggestion['angle'] ) ) {
							$blocks[] = array(
								'type' => 'section',
								'text' => array(
									'type' => 'mrkdwn',
									'text' => $suggestion['angle'],
								),
							);
						}

						// Related links.
						if ( ! empty( $suggestion['links'] ) && is_array( $suggestion['links'] ) ) {
							$link_texts = array();
							foreach ( $suggestion['links'] as $link ) {
								if ( ! empty( $link['url'] ) && ! empty( $link['title'] ) ) {
									$link_texts[] = sprintf( '• <%s|%s>', $link['url'], $link['title'] );
								}
							}

							if ( ! empty( $link_texts ) ) {
								$blocks[] = array(
									'type'     => 'context',
									'elements' => array(
										array(
											'type' => 'mrkdwn',
											'text' => "*Related PRC Reports:*\n" . implode( "\n", $link_texts ),
										),
									),
								);
							}
						}
					}
				}
			}

			$blocks[] = array(
				'type' => 'divider',
			);
		}

		return $blocks;
	}

	/**
	 * Format error response.
	 *
	 * @param string $error_message Error message.
	 * @param array  $args Original arguments.
	 * @param array  $context Context information.
	 * @return array Slack message payload.
	 */
	public static function format_error_response( $error_message, $args, $context ) {
		$user_name = $context['user_name'] ?? 'Unknown user';

		return array(
			'response_type' => 'ephemeral',
			'blocks'        => array(
				array(
					'type' => 'header',
					'text' => array(
						'type'  => 'plain_text',
						'text'  => '❌ Analysis Failed',
						'emoji' => true,
					),
				),
				array(
					'type' => 'section',
					'text' => array(
						'type' => 'mrkdwn',
						'text' => sprintf(
							"*Error:*\n```%s```\n\n*Requested by:* %s\n*Parameters:* %s",
							$error_message,
							$user_name,
							wp_json_encode( $args )
						),
					),
				),
				array(
					'type'     => 'context',
					'elements' => array(
						array(
							'type' => 'mrkdwn',
							'text' => '💡 *Troubleshooting tips:*\n• Check your category name\n• Verify date format (YYYY-MM-DD)\n• Ensure article count is between 1-100\n• Try again in a few moments',
						),
					),
				),
				array(
					'type'     => 'actions',
					'elements' => array(
						array(
							'type' => 'button',
							'text' => array(
								'type'  => 'plain_text',
								'text'  => '📖 View Documentation',
								'emoji' => true,
							),
							'url'  => 'https://github.com/pewresearch/prc-platform/blob/main/plugins/prc-nexus/README.md',
						),
					),
				),
			),
		);
	}

	/**
	 * Split markdown content into chunks for Slack blocks.
	 *
	 * @param string $content Markdown content.
	 * @param int    $max_length Maximum length per chunk (default 3000).
	 * @return array Array of content chunks.
	 */
	private static function split_markdown_content( $content, $max_length = 3000 ) {
		$chunks = array();

		// Split by double newlines to preserve sections.
		$sections      = preg_split( '/\n\n/', $content );
		$current_chunk = '';

		foreach ( $sections as $section ) {
			$section_length = strlen( $section );

			// If adding this section would exceed max length, start a new chunk.
			if ( strlen( $current_chunk ) + $section_length + 2 > $max_length ) {
				if ( ! empty( $current_chunk ) ) {
					$chunks[]      = trim( $current_chunk );
					$current_chunk = '';
				}

				// If single section is too long, split it further.
				if ( $section_length > $max_length ) {
					$chunks[]      = substr( $section, 0, $max_length - 20 ) . '... [continued]';
					$current_chunk = '[continued] ' . substr( $section, $max_length - 20 );
				} else {
					$current_chunk = $section;
				}
			} else {
				$current_chunk .= ( empty( $current_chunk ) ? '' : "\n\n" ) . $section;
			}
		}

		if ( ! empty( $current_chunk ) ) {
			$chunks[] = trim( $current_chunk );
		}

		return $chunks;
	}
}
