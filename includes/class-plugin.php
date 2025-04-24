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
		// Load plugin loading class.
		require_once plugin_dir_path( __DIR__ ) . '/includes/class-loader.php';

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
		if ( 'prc-copilot__playground' === $params['feature'] ) {
			$params['systemInstruction']  = 'You are operating inside the Gutenberg block editor, when you are asked to generate content provide it back in markdown so that it may easily be pasted and converted automatically into block markup.';
			$params['systemInstruction'] .= ' If you are asked a question just return normal strings. When you are asked specifically about "our" as in "our data" use "Pew Research Center".';
			$params['systemInstruction'] .= ' If this prompt is explicitly asking to create a table, escape the markdown output so that I can easily copy and paste it into the block editor.';
		}
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
