<?php
namespace PRC\Platform\Copilot\Abilities;

/**
 * Site Info Ability
 * Gets basic information about the WordPress site.
 * Including name, description, active plugins, and error log.
 */
class Site_Info {
	/**
	 * Constructor.
	 * Loads the ability into the WP abilities API.
	 *
	 * @param Loader $loader The loader.
	 */
	public function __construct( $loader ) {
		$loader->add_action( 'abilities_api_init', $this, 'register' );
	}

	/**
	 * Register the site info ability with WP abilities api.
	 *
	 * @hook abilities_api_init
	 */
	public function register() {
		$registered = wp_register_ability(
			'prc-copilot/wp-site-info',
			array(
				'label'               => __( 'Get Site Information', 'prc-copilot' ),
				'description'         => __( 'Retrieves basic information about the WordPress site including name, description, and url.', 'prc-copilot' ),
				'input_schema'        => array(),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'name'           => array(
							'type'        => 'string',
							'description' => 'Site name',
						),
						'description'    => array(
							'type'        => 'string',
							'description' => 'Site tagline',
						),
						'url'            => array(
							'type'        => 'string',
							'format'      => 'uri',
							'description' => 'Site URL',
						),
						'active_plugins' => array(
							'type'        => 'array',
							'description' => 'List of active plugins',
							'items'       => array(
								'type' => 'string',
							),
						),
					),
				),
				'execute_callback'    => array( $this, 'get_site_info' ),
				'permission_callback' => function ( $input ) {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * Get the site info.
	 *
	 * @param array $input The input.
	 * @return array The site info.
	 */
	public function get_site_info( $input ) {
		$info = array(
			'url'            => get_site_url(),
			'name'           => get_bloginfo( 'name' ),
			'description'    => get_bloginfo( 'description' ),
			'active_plugins' => get_option( 'active_plugins' ),
		);
		return array_filter( $info );
	}
}
