<?php

namespace PRC\Platform\Nexus\Abilities;

class Generate_Caption {
	public function __construct( $loader ) {
		$loader->add_action( 'abilities_api_init', $this, 'register_ability' );
	}

	public static function instructions() {
		return 'You are generating a caption for an image. The caption should be concise and descriptive, providing context for the image. If the image is complex or contains multiple elements, focus on the most important aspects. Ensure that the caption is relevant to the content of the image and enhances the viewer\'s understanding. Avoid using overly technical language or jargon.';
	}

	public function register_ability() {
		wp_register_ability(
			'prc-nexus/generate-caption',
			array(
				'label'         => __( 'Generate Caption', 'prc-nexus' ),
				'description'   => __( 'Generates a caption for an image based on a prompt.', 'prc-nexus' ),
				'input_schema'  => array(),
				'output_schema' => array(
					'type'       => 'object',
					'properties' => array(
						'image_description' => array(
							'type'        => 'string',
							'description' => 'A description of the image to caption',
							'required'    => true,
						),
					),
				),
				'callback'      => array( $this, 'handle_request' ),
			)
		);
	}

	/**
	 * Default system instructions for the Nexus Playground.
	 *
	 * @hook ai_services_model_params
	 *
	 * @param array $params The parameters for the model.
	 * @param array $service The service.
	 * @return array The parameters for the model.
	 */
	public function default_system_instructions( $params, $service ) {
		if ( 'get-table-caption' === $params['feature'] ) {
			$params['systemInstruction']  = 'You are generating captions from a markdown table. If what is passed to you does not seem to be a table, return with a "No caption can be generated for non-tabular data" message.';
			$params['systemInstruction'] .= ' Return a few options for the caption, and the best one should be selected by the user. Return the caption options as a json array of strings, with your best choice being the first element in the array.';
		}
		if ( 'get-table-title' === $params['feature'] ) {
			$params['systemInstruction']  = 'You are generating titles from a markdown table. If what is passed to you does not seem to be a table, return with a "No title can be generated for non-tabular data" message. Highlight the most important part of the table in the title. If the table seems to contain data about populations or percentages, include that in the title.';
			$params['systemInstruction'] .= ' Return a few options for the title, and the best one should be selected by the user. Return the title options as a json array of strings, with your best choice being the first element in the array.';
		}

		$params['systemInstruction'] .= ' Always write in the Pew Research Center style and voice.';

		return $params;
	}


	public function handle_request( $input ) {
		$image_description = $input['image_description'] ?? '';

		return AiClient::prompt( $image_description )->generateText();
	}
}
