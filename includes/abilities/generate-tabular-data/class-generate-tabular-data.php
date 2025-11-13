<?php
/**
 * Generate Tabular Data tool.
 *
 * @package PRC\Platform\Nexus\Abilities
 */

namespace PRC\Platform\Nexus\Abilities;

use WordPress\AiClient\AiClient;

/**
 * Class Generate_Tabular_Data
 */
class Generate_Tabular_Data {

	/**
	 * Ability name.
	 *
	 * @var string
	 */
	public static $ability_name = 'prc-nexus/generate-tabular-data';

	/**
	 * Blocks that are allowed to use this ability.
	 *
	 * @var array
	 */
	public static $allowed_blocks = array( 'prc-block/table' );

	/**
	 * Constructor.
	 *
	 * @param object $loader The loader object.
	 */
	public function __construct( $loader ) {
		$loader->add_action( 'wp_abilities_api_init', $this, 'register_ability' );
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
				'label'               => __( 'Generate Tabular Data', 'prc-nexus' ),
				'description'         => __( 'Generates tabular data in markdown format based on a prompt.', 'prc-nexus' ),
				'category'            => 'data-retrieval',
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'data_description' => array(
							'type'        => 'string',
							'description' => 'Description of data to retrieve and format as a table.',
						),
						'from'             => array(
							'type'        => 'number',
							'description' => 'Start year of the data range (e.g., 2010).',
						),
						'to'               => array(
							'type'        => 'number',
							'description' => 'End year of the data range (e.g., 2020).',
						),
					),
					'required'             => array( 'data_description' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'error' => array(
							'type'        => 'string',
							'description' => 'An error message, if any.',
						),
						'table' => array(
							'type'        => 'string',
							'description' => 'The generated tabular data in markdown format.',
						),
					),
				),
				'execute_callback'    => array( $this, 'generate_tabular_data' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'meta'                => array(
					'annotations'    => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => false,
					),
					'show_in_rest'   => true,
					'allowed_blocks' => self::$allowed_blocks,
					'mcp'            => array(
						'public' => true,
						'type'   => 'tool',
					),
				),
			)
		);
	}

	/**
	 * Generate tabular data from prompt.
	 *
	 * @param array $input The input.
	 *
	 * @return array Tabular data in markdown format.
	 */
	public function generate_tabular_data( $input ) {
		error_log( '------------------------------------------' ); // phpcs:ignore
		error_log( 'generate_tabular_data input: ' . print_r( $input, true ) ); // phpcs:ignore
		$data_description = $input['data_description'] ?? '';
		$year_range_start = $input['from'] ?? null;
		$year_range_end   = $input['to'] ?? null;

		$search_term = \PRC\Platform\Nexus\Utils\refine_search_term( $data_description );

		error_log( '--------------------------------------------' );//phpcs:ignore
		error_log( 'AI_SEARCH_TERM: ' . print_r( $search_term, true ) );//phpcs:ignore
		error_log( '--------------------------------------------' );// phpcs:ignore

		if ( $search_term ) {
			error_log("AI_SEARCH_TERM: $search_term");//phpcs:ignore

			$search_topics = \PRC\Platform\Nexus\Utils\refine_search_term_to_list_of_topics( $search_term );
			error_log('-------------------------------------------');//phpcs:ignore
			error_log("AI_SEARCH_TOPICS:" . print_r($search_topics, true));//phpcs:ignore
			error_log('-------------------------------------------');//phpcs:ignore

			$query_args = array(
				's'              => $search_term,
				'post_type'      => array(
					'post',
					'short-read',
					'fact-sheet',
				),
				'posts_per_page' => 25,
				'fields'         => 'id',
				'tax_query'      => array(
					array(
						'taxonomy' => 'category',
						'field'    => 'term_id',
						'terms'    => wp_list_pluck( $search_topics, 'id' ),
					),
				),
			);
			if ( null !== $year_range_start && null !== $year_range_end ) {
				$query_args['date_query'] = array(
					array(
						'after'     => array(
							'year'  => $year_range_start,
							'month' => 1,
							'day'   => 1,
						),
						'before'    => array(
							'year'  => $year_range_end,
							'month' => 12,
							'day'   => 31,
						),
						'inclusive' => true,
					),
				);
			}

			error_log( '-------------------------------------------' );//phpcs:ignore
			error_log( 'AI_QUERY_ARGS: ' . print_r( $query_args, true ) );//phpcs:ignore
			error_log( '-------------------------------------------' );// phpcs:ignore

			$search_query = new \WP_Query( $query_args );
			if ( $search_query->have_posts() ) {
				// Create a list of urls to check against.
				$urls_to_check = array();
				foreach ( $search_query->posts as $post_id ) {
					$urls_to_check[] = get_permalink( $post_id );
				}

				// Replace bloginfo('url') with pewresearch.org, so that we're always checking live site urls.
				$urls_to_check = str_replace( get_bloginfo( 'url' ), 'https://www.pewresearch.org', $urls_to_check );

				error_log( '-------------------------------------------' );//phpcs:ignore
				error_log( 'AI_URL_CHECK: ' . print_r( $urls_to_check, true ) );//phpcs:ignore
				error_log( '-------------------------------------------' );//phpcs:ignore

				// Shape the prompt with user request and source URLs.
				$prompt = wp_sprintf(
					"User request: \"%s\"\n\nSource URLs to check:\n%s",
					$data_description,
					implode( "\n", array_map( fn( $url ) => "- $url", $urls_to_check ) )
				);

				error_log( '--------------------------------------------' );//phpcs:ignore
				error_log( 'AI_PROMPT: ' . print_r( $prompt, true ) );//phpcs:ignore
				error_log( '--------------------------------------------' );// phpcs:ignore

				// $table = AiClient::prompt( $prompt )
				// ->usingSystemInstruction( $this->get_system_instructions() )
				// ->usingTemperature( 0.3 )
				// ->generateText();
				$table = $prompt;

				return array(
					'error' => '',
					'table' => $table,
				);
			} else {
				error_log( '--------------------------------------------' );//phpcs:ignore
				error_log( 'NO DATA FOUND' );//phpcs:ignore
				error_log( '--------------------------------------------' );// phpcs:ignore
				return array(
					'error' => 'No data can be generated for request. No relevant posts found on Pew Research Center website.',
					'table' => '',
				);
			}
		}
		error_log( '--------------------------------------------' );//phpcs:ignore
		error_log( 'NO SEARCH TERM FOUND' );//phpcs:ignore
		error_log( '--------------------------------------------' );// phpcs:ignore
		return array(
			'error' => 'No data can be generated for request. Unable to determine search term.',
			'table' => '',
		);
	}
}
