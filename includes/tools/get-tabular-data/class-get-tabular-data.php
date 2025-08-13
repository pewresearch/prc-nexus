<?php
/**
 * Get Tabular Data tool.
 *
 * @package PRC\Platform\Copilot\Tools
 */

namespace PRC\Platform\Copilot\Tools;

/**
 * Get Tabular Data tool.
 */
class Get_Tabular_Data {

	/**
	 * The name of the feature.
	 *
	 * @var string
	 */
	protected static $feature_name = 'get-tabular-data';

	/**
	 * Constructor.
	 *
	 * @param PRC\Platform\Copilot\Loader $loader The loader.
	 */
	public function __construct( $loader ) {
		$loader->add_action( 'wp_feature_api_init', $this, 'register_feature' );
		$loader->add_action( 'ai_services_model_params', $this, 'system_instruction', 10, 2 );
	}

	/**
	 * System instructions for the get-tabular-data feature.
	 *
	 * @hook ai_services_model_params
	 *
	 * @param array $params The parameters for the model.
	 * @param array $service The service.
	 * @return array The parametersfor the model.
	 */
	public function system_instruction( $params, $service ) {
		if ( self::$feature_name === $params['feature'] ) {
			$params['systemInstruction'] .= 'You are generating data for a markdown table. To accomplish this task you will be given a description of the data to compile, you should only source your data from Pew Research Center. If you cannot find the data you need, return with a "No data can be generated for request" message and include the description of the data you were given and steps you took to find the data.';
			$params['systemInstruction'] .= 'Look for "reports", "short reads", and "fact sheets" to find the data you need. Work in chronological order, starting with the most recent data. If the user is requesting data about a specific year, make sure to include that year in the data you return. If the user is requesting data over a range of years, make sure to include all the years in the data you return.';
			$params['systemInstruction'] .= 'Double check your work, be precise. If you are unsure about the data you are returning, ask the user for clarification.';
			$params['systemInstruction'] .= 'Only return the markdown table, no other text.';
		}

		return $params;
	}
	/**
	 * Register the feature.
	 */
	public function register_feature() {
		$registered = wp_register_feature(
			array(
				'id'                  => 'prc-copilot/get-tabular-data',
				'name'                => 'Get Tabular Data',
				'description'         => 'Get tabular data from the Pew Research Center. This tool will return a markdown table of the data you request along with a caption and source note.',
				'callback'            => array( $this, 'get_tabular_data' ),
				'type'                => \WP_Feature::TYPE_RESOURCE,
				'categories'          => array( 'prc-copilot', 'rest' ),
				'permission_callback' => '__return_true',
				'expose_via'          => array( 'mcp' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'data_description' => array(
							'type'        => 'string',
							'description' => 'A description of the data to get',
							'required'    => true,
						),
						'year_range_start' => array(
							'type'        => 'number',
							'description' => 'The start year of the data to get',
							'required'    => false,
						),
						'year_range_end'   => array(
							'type'        => 'number',
							'description' => 'The end year of the data to get',
							'required'    => false,
						),
					),
				),
			)
		);
	}

	/**
	 * Get tabular data.
	 *
	 * @param array $input_params The input parameters.
	 * @return string The tabular data.
	 */
	public function get_tabular_data( $input_params ) {
		error_log( 'get_tabular_data: ' . print_r( $input_params, true ) );
		// Dummy demo data.
		return '| Year | Population | Percentage |
|------|------------|------------|
| 2020 | 331,002,651 | 100% |
| 2010 | 308,745,538 | 93.3% |
| 2000 | 281,421,906 | 85.0% |';
		// TODO: Implement a get_tabular_data function that will process the prompt, shape it, and look to charts or perhaps a new "table" object type will be needed.
	}
}
