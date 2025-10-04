<?php
/**
 * Plugin class.
 *
 * @package    PRC\Platform\Nexus
 */

namespace PRC\Platform\Nexus;

use PRC\Platform\Nexus\Abilities\Abilities_Manager;

/**
 * Plugin class.
 *
 * @package    PRC\Platform\Nexus
 */
class Plugin {
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    0.1.0
	 * @access   protected
	 * @var      Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    0.1.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    0.1.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * The list of abilities loaded by the plugin.
	 *
	 * @since    0.1.0
	 * @access   protected
	 * @var      array    $abilities    The list of abilities.
	 */
	protected $abilities = array();

	/**
	 * The list of resources loaded by the plugin.
	 *
	 * @since    0.1.0
	 * @access   protected
	 * @var      array    $resources    The list of resources.
	 */
	protected $resources = array();

	/**
	 * The list of prompts loaded by the plugin.
	 *
	 * @since    0.1.0
	 * @access   protected
	 * @var      array    $prompts    The list of prompts.
	 */
	protected $prompts = array();

	/**
	 * Define the core functionality of the platform as initialized by hooks.
	 *
	 * @since    0.1.0
	 */
	public function __construct() {
		$this->version     = '0.1.0';
		$this->plugin_name = 'prc-nexus';

		$this->load_dependencies();
		$this->init_dependencies();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    0.1.0
	 * @access   private
	 */
	private function load_dependencies() {
		// Load the Jetpack/Automattic composer autoloader.
		// This loads the required dependencies of: WP Abilities API, PHP Client API, and MCP Adapter.
		require_once plugin_dir_path( __DIR__ ) . '/vendor/autoload_packages.php';

		// Load plugin loading class.
		require_once plugin_dir_path( __DIR__ ) . '/includes/class-loader.php';

		// Load assets.
		require_once plugin_dir_path( __DIR__ ) . '/includes/class-assets.php';

		// Load post meta class.
		require_once plugin_dir_path( __DIR__ ) . '/includes/metadata-log/class-metadata-log.php';

		// Load abilities manager.
		require_once plugin_dir_path( __DIR__ ) . '/includes/abilities/class-abilities-manager.php';

		// Load the MCP server class.
		require_once plugin_dir_path( __DIR__ ) . '/includes/mcp-server/class-mcp-server.php';

		// Load the Slack integration.
		require_once plugin_dir_path( __DIR__ ) . '/includes/integrations/slack/class-slack-integration.php';

		// Initialize the loader.
		$this->loader = new Loader();
	}

	/**
	 * Initialize the dependencies.
	 *
	 * @since    0.1.0
	 * @access   private
	 */
	private function init_dependencies() {
		// @TODO: For now, we disable the Jetpack AI Assistant for everyone except admins.
		// In the future, we will be working with WordPress VIP to create a lightweight and open source AI Assistant plugin.
		$this->loader->add_filter( 'jetpack_set_available_extensions', $this, 'disable_jetpack_ai_assistant', 10, 1 );

		// Load and initialize all available abilities.
		$abilities = new Abilities_Manager( $this->get_loader() );
		// Get the key names of the abilities, this is in the prc-nexus/<ability-name> format.
		$this->abilities = array_keys( $abilities->available_abilities );

		// Initialize client-side components and assets.
		new Assets( $this->get_loader() );

		// Initialize post meta.
		new Metadata_Log( $this->get_loader() );

		// Initialize the MCP server.
		new MCP_Server( $this->get_loader(), $this->abilities, $this->resources, $this->prompts );

		// Initialize Slack integration.
		new Integrations\Slack\Slack_Integration( $this->get_loader() );
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
		// Do not modify for admins.
		if ( current_user_can( 'manage_options' ) ) {
			return $extensions;
		}
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
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    0.1.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     0.1.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     0.1.0
	 * @return    PRC\Platform\Nexus\Loader
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     0.1.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
