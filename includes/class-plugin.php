<?php
/**
 * Plugin class.
 *
 * @package    PRC\Platform\Copilot
 */

namespace PRC\Platform\Copilot;

use WP_Error;

/**
 * Plugin class.
 *
 * @package    PRC\Platform\Copilot
 */
class Plugin {
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the platform as initialized by hooks.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->version     = '1.0.0';
		$this->plugin_name = 'prc-copilot';

		$this->load_dependencies();
		$this->init_dependencies();
	}


	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		// Load WP Feature API.
		// require_once plugin_dir_path( __DIR__ ) . '/vendor/automattic/wp-feature-api/wp-feature-api.php';
		// Load WordPress MCP.

		// Load plugin loading class.
		require_once plugin_dir_path( __DIR__ ) . '/includes/class-loader.php';

		// Load assets.
		require_once plugin_dir_path( __DIR__ ) . '/includes/class-assets.php';

		// Load post meta class.
		require_once plugin_dir_path( __DIR__ ) . '/includes/class-post-meta.php';

		// Load tools.
		require_once plugin_dir_path( __DIR__ ) . '/includes/tools/get-tabular-data/class-get-tabular-data.php';

		// Initialize the loader.
		$this->loader = new Loader();
	}

	/**
	 * Initialize the dependencies.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function init_dependencies() {
		$this->loader->add_filter( 'ai_services_model_params', $this, 'default_system_instructions', 10, 2 );
		$this->loader->add_filter( 'jetpack_set_available_extensions', $this, 'disable_jetpack_ai_assistant', 10, 1 );

		new Assets( $this->get_loader() );
		new Post_Meta( $this->get_loader() );
		new Tools\Get_Tabular_Data( $this->get_loader() );
	}

	/**
	 * Disable Jetpack's AI Assistant.
	 *
	 * @hook jetpack_set_available_extensions
	 *
	 * @param array $extensions Jetpack extensions array.
	 * @return array updated extensions array.
	 */
	public function disable_jetpack_ai_assistant( $extensions ) {
		$modified_extensions = array_filter(
			$extensions,
			function ( $extension ) {
				$disallowed = array(
					'ai-assistant',
					'ai-assistant-support',
				);
				return ! in_array(
					$extension,
					$disallowed
				);
			}
		);
		return $modified_extensions;
	}

	/**
	 * Default system instructions for the Copilot Playground.
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

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    PRC\Platform\Copilot\Loader
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
