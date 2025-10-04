<?php
/**
 * Analyze Trending News tool.
 *
 * @package PRC\Platform\Nexus\Abilities
 */

namespace PRC\Platform\Nexus\Abilities;

use WordPress\AiClient\AiClient;
use GNews\GNews;

/**
 * Class Trending_News_Analysis
 */
class Trending_News_Analysis {

	/**
	 * Ability name.
	 *
	 * @var string
	 */
	public static $ability_name = 'prc-nexus/trending-news-analysis';

	/**
	 * Constructor.
	 *
	 * @param object $loader The loader object.
	 */
	public function __construct( $loader = null ) {
		require_once plugin_dir_path( __FILE__ ) . 'class-cli-command.php';
		if ( null !== $loader ) {
			$loader->add_action( 'abilities_api_init', $this, 'register_ability' );
			return;
		}
	}

	/**
	 * Run the trending news analysis ability.
	 *
	 * @param array $args The input arguments.
	 * @return array The analysis results.
	 */
	public function run( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'category'      => 'nation',
				'total'         => 5,
				'from'          => '',
				'to'            => '',
				'query'         => '',
				'output_format' => 'markdown',
			)
		);
		return $this->perform_trending_news_analysis( $args );
	}

	/**
	 * Register the generate-tabular-data ability with WP abilities api.
	 *
	 * @hook abilities_api_init
	 */
	public function register_ability() {
		$registered = wp_register_ability(
			self::$ability_name,
			array(
				'label'               => __( 'Analyze Trending News', 'prc-nexus' ),
				'description'         => __( 'Analyzes trending news, identifies trending topics, and suggests writing prompts.', 'prc-nexus' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'category'      => array(
							'type'        => 'string',
							'description' => 'News category (e.g., nation, world, business, technology, sports, science, health, entertainment)',
							'default'     => 'nation',
						),
						'total'         => array(
							'type'        => 'integer',
							'description' => 'Total number of articles to fetch (1-100)',
							'default'     => 5,
							'minimum'     => 1,
							'maximum'     => 100,
						),
						'from'          => array(
							'type'        => 'string',
							'description' => 'Start date in YYYY-MM-DD format (defaults to yesterday)',
						),
						'to'            => array(
							'type'        => 'string',
							'description' => 'End date in YYYY-MM-DD format (defaults to today)',
						),
						'query'         => array(
							'type'        => 'string',
							'description' => 'Search query to filter articles by keywords',
						),
						'output_format' => array(
							'type'        => 'string',
							'description' => 'Output format: json (structured data) or markdown (formatted text)',
							'enum'        => array( 'json', 'markdown' ),
							'default'     => 'markdown',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'error'    => array(
							'type'        => 'string',
							'description' => 'An error message, if any.',
						),
						'response' => array(
							'type'        => 'string',
							'description' => 'The response from the analysis.',
						),
					),
				),
				'execute_callback'    => array( $this, 'perform_trending_news_analysis' ),
				'permission_callback' => function ( $input ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * Get the expected analysis format.
	 *
	 * @return string The expected analysis format in JSON.
	 */
	public static function get_analysis_format() {
		$sample_shape = array(
			'title'       => 'The title of the trending news item',
			'summary'     => '1 sentence summary of the news item',
			'source'      => 'link to the original source of this trending news item',
			'suggestions' => array(
				array(
					'headline' => 'Suggest a story headline example of how Pew Research Center would connect its research to the news to add explanatory information or explain the pulse of how people feel on a current news topic. Headline should connect the current news peg to the research, like: "As China tariff is delayed, heres how Americans feel about the role of government in setting tariffs"',
					'angle'    => 'Explanation of how Pew Research Center reporters would use the data to connect to the news article',
					'links'    => array(
						array(
							'title' => 'Title of the related Pew Research Center report',
							'url'   => 'URL to the related_posts for this trending news',
						),
					),
				),
			),
		);
		return wp_json_encode( $sample_shape );
	}

	/**
	 * Get the system instructions for the AI model.
	 *
	 * @TODO: This needs to be cleaned up.
	 *
	 * @return string The system instructions.
	 */
	public static function get_instructions() {
		$analysis_format = self::get_analysis_format();
		return wp_sprintf(
			'You are a news analysis assistant for Pew Research Center staff.

			Your task: Assess which trending news stories have strong connections to the provided Pew Research Center content, and suggest story angles that connect our research to current news.

			CRITICAL OUTPUT REQUIREMENTS:
			- Return ONLY valid JSON matching this exact structure: %s
			- Do not include explanatory text, markdown formatting, or commentary
			- Do not wrap JSON in code blocks or backticks
			- Return raw JSON only

			For each trending news story:
			1. Evaluate if related Pew Research Center content is a close enough match to support a story
			2. If yes, suggest exactly two story angles that connect the news to Pew research
			3. Headlines should be specific and connect the news peg to research (e.g., "As China tariff is delayed, here\'s how Americans feel about government\'s role in setting tariffs")
			4. For links, include both the title and url from the related_posts provided in the format: {"title": "Report Title", "url": "https://..."}',
			$analysis_format
		);
	}

	/**
	 * Get trending news articles using GNews API.
	 *
	 * @param string $category Category of news to fetch.
	 * @param int    $total Number of articles to fetch.
	 * @param string $from Start date in YYYY-MM-DD format.
	 * @param string $to End date in YYYY-MM-DD format.
	 * @param string $query Search query to filter articles.
	 *
	 * @return array List of trending news articles.
	 */
	private function get_trending_news( $category = 'nation', $total = 5, $from = '', $to = '', $query = '' ) {
		// Set default dates if not provided.
		$from_date = ! empty( $from ) ? $from : gmdate( 'Y-m-d', strtotime( '-1 day' ) );
		$to_date   = ! empty( $to ) ? $to : gmdate( 'Y-m-d' );
		$client    = new GNews( PRC_PLATFORM_GOOGLE_NEWS_API_KEY );

		$response = $client->getTopHeadlines(
			array(
				'category' => $category,
				'lang'     => 'en',
				'country'  => 'us',
				'max'      => $total,
				'nullable' => '',
				'from'     => $from_date . 'T00:00:00Z',
				'to'       => $to_date . 'T23:59:59Z',
				'q'        => $query,
				'page'     => 1,
				'expand'   => 'content',
			)
		);

		return array_map(
			function ( $article ) {
				return array(
					'title'       => $article->getTitle(),
					'description' => $article->getDescription(),
					'url'         => $article->getUrl(),
					'source'      => $article->getSourceName(),
					'publishedAt' => $article->getPublishedAt(),
				);
			},
			$response->getArticles()
		);
	}

	/**
	 * Get the category dictionary for topic extraction.
	 *
	 * @return array List of categories with name and term_id.
	 */
	private function get_category_dictionary() {
		$cache_key = 'prc_nexus_category_dictionary';
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			error_log( 'Using cached category dictionary' ); // phpcs:ignore
			return $cached;
		}

		$categories    = get_categories(
			array(
				'hide_empty' => false,
			)
		);
		$category_list = array();
		foreach ( $categories as $category ) {
			$category_list[] = array(
				'name'    => $category->name,
				'term_id' => $category->term_id,
			);
		}

		// Cache for 24 hours (categories don't change often).
		set_transient( $cache_key, $category_list, DAY_IN_SECONDS );
		error_log( 'Category dictionary cached for 24 hours' ); // phpcs:ignore

		return $category_list;
	}

	/**
	 * Extract categories from trending news using AI.
	 *
	 * @param array $trending_news List of trending news articles.
	 * @param array $category_dictionary List of available categories.
	 * @return array Stories with extracted categories.
	 */
	private function extract_categories_from_news( $trending_news, $category_dictionary ) {
		$topic_dictionary = 'TOPIC DICTIONARY: ' . wp_json_encode( $category_dictionary ) . '.\n';
		$news_data        = 'TRENDING NEWS: ' . wp_json_encode( $trending_news );

		$trending_topics_analysis = AiClient::prompt(
			wp_sprintf(
				'Analyze these stories and extract relevant categories or topics from our topic dictionary. The data should be returned as an array with these properites {title, description, date, url, categories: [{name, term_id}]}. %s %s',
				$topic_dictionary,
				$news_data,
			)
		)
		->asJsonResponse()
		->generateText();

		return json_decode( $trending_topics_analysis, true );
	}

	/**
	 * Get related posts for given category IDs.
	 *
	 * @param array $category_ids List of category term IDs.
	 * @param int   $limit Number of posts to retrieve.
	 * @return array List of related posts.
	 */
	private function get_related_posts( $category_ids, $limit = 5 ) {
		if ( empty( $category_ids ) ) {
			return array();
		}

		// Create cache key based on sorted category IDs and limit.
		sort( $category_ids );
		$cache_key = 'prc_nexus_related_posts_' . md5( wp_json_encode( $category_ids ) . '_' . $limit );
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			error_log( wp_sprintf( 'Using cached related posts for categories: %s', implode( ',', $category_ids ) ) ); // phpcs:ignore
			return $cached;
		}

		$tax_query = array();
		foreach ( $category_ids as $category_id ) {
			$tax_query[] = array(
				'taxonomy' => 'category',
				'field'    => 'term_id',
				'terms'    => $category_id,
			);
		}

		$tax_query['relation'] = 'OR';

		$query_args = array(
			'post_type'      => array( 'post', 'short-read', 'fact-sheet' ),
			'posts_per_page' => $limit,
			'post_parent'    => 0,
			'post_status'    => 'publish',
			'orderby'        => 'date',
			'order'          => 'DESC',
			'tax_query'      => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'date_query'     => array(
				array(
					'after'     => gmdate( 'Y' ) . '-01-01',
					'inclusive' => true,
				),
			),
		);

		$query = new \WP_Query( $query_args );
		$posts = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$posts[] = array(
					'title'   => get_the_title(),
					'date'    => get_the_date( 'Y-m-d' ),
					'url'     => get_permalink(),
					'excerpt' => get_the_excerpt(),
				);
			}
			wp_reset_postdata();
		}

		// Cache for 1 hour (posts are relatively stable but may get published).
		set_transient( $cache_key, $posts, HOUR_IN_SECONDS );
		error_log( wp_sprintf( 'Related posts cached for 1 hour (categories: %s)', implode( ',', $category_ids ) ) ); // phpcs:ignore

		return $posts;
	}

	/**
	 * Enrich stories with related posts.
	 *
	 * @param array $stories List of stories with categories.
	 * @return array Stories enriched with related posts.
	 */
	private function enrich_stories_with_related_posts( $stories ) {
		foreach ( $stories as $index => $story ) {
			$categories    = $story['categories'] ?? array();
			$category_ids  = array_column( $categories, 'term_id' );
			$related_posts = $this->get_related_posts( $category_ids );

			$stories[ $index ]['related_posts'] = $related_posts;
		}

		return $stories;
	}

	/**
	 * Analyze a single story using AI.
	 *
	 * @param array $story Story data with related posts.
	 * @return array|null Analyzed story or null on failure.
	 */
	private function analyze_story( $story ) {
		// Skip stories without sufficient related content.
		if ( empty( $story['related_posts'] ) ) {
			error_log( wp_sprintf( 'Skipping story "%s" - no related posts found', $story['title'] ) ); // phpcs:ignore
			return null;
		}

		// Create a structured prompt for this individual story.
		$story_prompt = wp_sprintf(
			'You have been provided with a trending news story and related Pew Research Center content. Transform this data into the required analysis format.

			INPUT DATA: %s

			Evaluate if the related Pew Research Center posts are strong enough matches to support story suggestions. If they are, provide exactly two story angles that connect the news to Pew research.',
			wp_json_encode( $story )
		);

		try {
			$story_response = AiClient::prompt( $story_prompt )
				->usingSystemInstruction( self::get_instructions() )
				->usingTemperature( 0.3 )
				->asJsonResponse()
				->generateText();

			return $this->validate_story_response( $story_response, $story['title'] );
		} catch ( \Exception $e ) {
			error_log( wp_sprintf( 'Error processing story "%s": %s', $story['title'], $e->getMessage() ) ); // phpcs:ignore
			return null;
		}
	}

	/**
	 * Validate and decode AI response for a story.
	 *
	 * @param string $response Raw AI response.
	 * @param string $story_title Story title for logging.
	 * @return array|null Decoded response or null on failure.
	 */
	private function validate_story_response( $response, $story_title ) {
		$response = trim( $response );

		// Attempt to decode and validate the response.
		$decoded_response = json_decode( $response, true );

		if ( json_last_error() === JSON_ERROR_NONE && ! empty( $decoded_response ) ) {
			error_log( wp_sprintf( 'Successfully processed story: %s', $story_title ) ); // phpcs:ignore
			return $decoded_response;
		}

		error_log( wp_sprintf( 'Failed to decode JSON for story: %s', $story_title ) ); // phpcs:ignore
		// error_log( 'Response was: ' . $response ); // phpcs:ignore
		return null;
	}

	/**
	 * Analyze multiple stories.
	 *
	 * @param array $stories List of enriched stories.
	 * @return array List of analyzed stories.
	 */
	private function analyze_stories( $stories ) {
		$final_analysis = array();

		foreach ( $stories as $index => $story ) {
			error_log( wp_sprintf( 'Processing story %d of %d', $index + 1, count( $stories ) ) ); // phpcs:ignore

			$analyzed_story = $this->analyze_story( $story );

			if ( null !== $analyzed_story ) {
				$final_analysis[] = $analyzed_story;
			}
		}

		return $final_analysis;
	}

	/**
	 * Format analysis results as markdown.
	 *
	 * @param array $analysis_results Array of analyzed stories.
	 * @return string Markdown-formatted output.
	 */
	private function format_analysis_as_markdown( $analysis_results ) {
		if ( empty( $analysis_results ) ) {
			return 'No trending news analysis available.';
		}

		$markdown = '';

		foreach ( $analysis_results as $story ) {
			// Large subhead: News headline.
			$markdown .= '## **' . $story['title'] . '**' . "\n\n";

			// Italic summary with source link.
			$source_text = ! empty( $story['source'] ) ? ' [Source](' . $story['source'] . ')' : '';
			$markdown   .= '*' . $story['summary'] . $source_text . '*' . "\n\n";

			// Process suggestions.
			if ( ! empty( $story['suggestions'] ) && is_array( $story['suggestions'] ) ) {
				foreach ( $story['suggestions'] as $index => $suggestion ) {
					$suggestion_num = $index + 1;

					// Bullet: PRC HEADLINE.
					$markdown .= '• **PRC HEADLINE ' . $suggestion_num . ':** ' . $suggestion['headline'] . "\n";

					// Nested sub-bullet: PRC ANGLE.
					$markdown .= '  - **PRC ANGLE ' . $suggestion_num . ':** ' . $suggestion['angle'] . "\n";

					// Nested sub-bullet: REPORT LINK(S).
					if ( ! empty( $suggestion['links'] ) && is_array( $suggestion['links'] ) ) {
						foreach ( $suggestion['links'] as $link_index => $link ) {
							$link_num = $link_index + 1;

							// Handle both old format (string) and new format (array with title/url).
							if ( is_array( $link ) && isset( $link['title'], $link['url'] ) ) {
								$markdown .= '  - **REPORT LINK ' . $link_num . ':** [' . $link['title'] . '](' . $link['url'] . ')' . "\n";
							} elseif ( is_string( $link ) ) {
								// Fallback for old format: extract title from URL or use generic text.
								$link_title = $this->extract_title_from_url( $link );
								$markdown  .= '  - **REPORT LINK ' . $link_num . ':** [' . $link_title . '](' . $link . ')' . "\n";
							}
						}
					}

					// Add spacing between suggestions.
					$markdown .= "\n";
				}
			}

			// Add spacing between stories.
			$markdown .= "---\n\n";
		}

		return $markdown;
	}

	/**
	 * Extract a readable title from a URL.
	 *
	 * @param string $url The URL to extract title from.
	 * @return string The extracted or default title.
	 */
	private function extract_title_from_url( $url ) {
		// Try to get the last segment of the path as a title.
		$parsed = wp_parse_url( $url );
		if ( isset( $parsed['path'] ) ) {
			$path_parts = array_filter( explode( '/', trim( $parsed['path'], '/' ) ) );
			if ( ! empty( $path_parts ) ) {
				$last_segment = end( $path_parts );
				// Convert slug to title (e.g., 'my-report-title' -> 'My Report Title').
				$title = ucwords( str_replace( array( '-', '_' ), ' ', $last_segment ) );
				return $title;
			}
		}
		return 'View Report';
	}

	/**
	 * Perform trending news analysis.
	 *
	 * @param array $input The input parameters.
	 * @return array The analysis results.
	 */
	public function perform_trending_news_analysis( $input ) {
		error_log( '------------------------------------------' ); // phpcs:ignore
		error_log( 'perform_trending_news_analysis' ); // phpcs:ignore

		// Extract input parameters with defaults.
		$category      = $input['category'] ?? 'nation';
		$total         = $input['total'] ?? 5;
		$from          = $input['from'] ?? '';
		$to            = $input['to'] ?? '';
		$query         = $input['query'] ?? '';
		$output_format = $input['output_format'] ?? 'markdown';

		// Get category dictionary.
		$category_dictionary = $this->get_category_dictionary();

		// Get trending news.
		$trending_news = $this->get_trending_news( $category, $total, $from, $to, $query );

		if ( empty( $trending_news ) ) {
			return array(
				'error' => 'No trending news available',
			);
		}

		// Extract categories from news.
		$stories_with_categories = $this->extract_categories_from_news( $trending_news, $category_dictionary );

		// Enrich stories with related posts.
		$enriched_stories = $this->enrich_stories_with_related_posts( $stories_with_categories );

		// Analyze stories.
		$final_analysis = $this->analyze_stories( $enriched_stories );

		// Format response based on output_format parameter.
		if ( 'markdown' === $output_format ) {
			return array(
				'response' => $this->format_analysis_as_markdown( $final_analysis ),
			);
		}

		// Default to JSON format.
		return array(
			'response' => wp_json_encode( $final_analysis ),
		);
	}
}
