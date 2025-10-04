<?php
/**
 * MCP Server class.
 *
 * @package PRC\Platform\Nexus
 */

namespace PRC\Platform\Nexus;

use WP\MCP\Core\McpAdapter;

/**
 * MCP Server class.
 *
 * Registers the MCP server with the MCP adapter.
 */
class MCP_Server {
	/**
	 * The unique identifier of this MCP server.
	 *
	 * @since    0.1.0
	 * @access   protected
	 * @var      string    $server_id    The string used to uniquely identify this MCP server.
	 */
	protected $server_id = 'prc-nexus';

	/**
	 * The REST namespace for the MCP server.
	 *
	 * @since    0.1.0
	 * @access   protected
	 * @var      string    $server_namespace    The namespace for the MCP server.
	 */
	protected $server_namespace = 'prc-nexus';

	/**
	 * The REST endpoint for the MCP server.
	 *
	 * @since    0.1.0
	 * @access   protected
	 * @var      string    $rest_endpoint    The restful endpoint for the MCP server.
	 */
	protected $server_endpoint = 'mcp';

	/**
	 * The name of the MCP server.
	 *
	 * @since    0.1.0
	 * @access   protected
	 * @var      string    $server_name    The name of the MCP server.
	 */
	protected $server_name = 'PRC Nexus';

	/**
	 * The description of the MCP server.
	 *
	 * @since    0.1.0
	 * @access   protected
	 * @var      string    $server_description    The description of the MCP server.
	 */
	protected $server_description = 'PRC Nexus MCP Server';

	/**
	 * The version of the MCP server.
	 *
	 * @since    0.1.0
	 * @access   protected
	 * @var      string    $server_version    The current version of the MCP server.
	 */
	protected $server_version = '0.1.0';

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
	 * Constructor.
	 *
	 * @param Loader $loader The loader.
	 * @param array  $abilities The list of abilities.
	 * @param array  $resources The list of resources.
	 * @param array  $prompts The list of prompts.
	 */
	public function __construct( $loader, $abilities, $resources, $prompts ) {
		$this->abilities = $abilities;
		$this->resources = $resources;
		$this->prompts   = $prompts;
		// Init the WordPress MCP Adapter.
		$adapter = McpAdapter::instance();
		$loader->add_action( 'mcp_adapter_init', $this, 'register_server', 10, 1 );
	}

	/**
	 * Register the MCP server.
	 *
	 * @hook mcp_adapter_init
	 *
	 * @param \WP\MCP\Adapter $adapter The MCP adapter.
	 */
	public function register_server( $adapter ) {
		$adapter->create_server(
			$this->server_id,
			$this->server_namespace,
			$this->server_endpoint,
			$this->server_name,
			$this->server_description,
			$this->server_version,
			array(
				\WP\MCP\Transport\Http\RestTransport::class,
			), // Transport methods.
			\WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler::class, // Error handler.
			\WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler::class, // Observability handler.
			$this->abilities, // Abilities.
			$this->resources, // Resources.
			$this->prompts // Prompts.
		);
	}
}
