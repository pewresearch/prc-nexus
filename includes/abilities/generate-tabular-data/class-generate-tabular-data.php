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
	 * Constructor.
	 *
	 * @param object $loader The loader object.
	 */
	public function __construct( $loader ) {
		$loader->add_action( 'abilities_api_init', $this, 'register_ability' );
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
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'data_description' => array(
							'type'        => 'string',
							'description' => 'A description of the data to get',
						),
						'year_range_start' => array(
							'type'        => 'number',
							'description' => 'Start year of the data',
						),
						'year_range_end'   => array(
							'type'        => 'number',
							'description' => 'The end year of the data',
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
				'permission_callback' => function ( $input ) {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * Get system instructions for tabular data generation.
	 *
	 * @return string System instructions.
	 */
	private function get_system_instructions() {
		return <<<INSTRUCTIONS
You are a data analyst assistant for Pew Research Center. Your task is to generate tabular data in markdown format based on information from provided source URLs.

## Core Responsibilities
- Extract relevant data from the provided source URLs
- Format data as clean markdown tables
- Include source attribution with URLs as table caption
- Only use information that can be verified from the sources

## Output Format Requirements
- Use standard markdown table syntax with pipes (|) and hyphens (-)
- Include clear column headers
- Ensure data is properly aligned
- Add a caption below the table listing source URLs
- Keep tables concise and readable

## Critical Rules
🔴 NEVER fabricate or estimate data
🔴 NEVER use data from sources not provided in the URLs list
🔴 If you cannot find the requested data in the sources, respond with: "No data can be generated for this request. The information was not found in the provided sources."
🟢 ONLY use data that appears in the provided source URLs
🟢 ALWAYS cite which specific URLs you used for each data point

## Example Output Format
| Column 1 | Column 2 | Column 3 |
|----------|----------|----------|
| Data     | Data     | Data     |

*Source: [URL1], [URL2]*
INSTRUCTIONS;
	}

	/**
	 * Generate tabular data from prompt.
	 *
	 * @param array $input The input.
	 * @return array The site info.
	 */
	public function generate_tabular_data( $input ) {
		error_log( '------------------------------------------' ); // phpcs:ignore
		error_log( 'generate_tabular_data input: ' . print_r( $input, true ) ); // phpcs:ignore
		$data_description = $input['data_description'] ?? '';
		$year_range_start = $input['year_range_start'] ?? null;
		$year_range_end   = $input['year_range_end'] ?? null;

		$search_term = \PRC\Platform\Nexus\Utils\refine_search_term( $data_description );

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

				$table = AiClient::prompt( $prompt )
					->usingSystemInstruction( $this->get_system_instructions() )
					->usingTemperature( 0.3 )
					->generateText();

				return array(
					'source_urls' => $urls_to_check,
					'table'       => $table,
				);
			} else {
				return array( 'error' => 'No data can be generated for request. No relevant posts found on Pew Research Center website.' );
			}
		}
		return array( 'error' => 'No data can be generated for request. Unable to determine search term.' );
	}
}
