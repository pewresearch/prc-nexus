<?php
/**
 * Plugin class.
 *
 * @package    PRC\Platform\Copilot
 */

namespace PRC\Platform\Copilot;

/**
 * Register and enqueue assets.
 *
 * @package    PRC\Platform\Copilot
 */
class Assets {

	/**
	 * Constructor.
	 *
	 * @param Loader $loader The loader instance.
	 */
	public function __construct( $loader = null ) {
		$loader->add_action( 'enqueue_block_editor_assets', $this, 'enqueue_plugin', 1 );
		$loader->add_action( 'enqueue_block_editor_assets', $this, 'register_exports', 1 );
	}

	/**
	 * Enqueue the plugin script.
	 *
	 * @hook enqueue_block_editor_assets
	 */
	public function enqueue_plugin() {
		$plugin_asset_file = include plugin_dir_path( __DIR__ ) . 'build/plugin/plugin.asset.php';
		wp_enqueue_script(
			'prc-copilot-admin-plugin',
			plugins_url( 'build/plugin/plugin.js', __DIR__ ),
			$plugin_asset_file['dependencies'],
			$plugin_asset_file['version'],
			true
		);
	}

	/**
	 * Register @prc/copilot exports.
	 *
	 * @hook wp_enqueue_scripts
	 */
	public function register_exports() {
		$export_asset_file = include plugin_dir_path( __DIR__ ) . 'build/exports/exports.asset.php';
		wp_register_script(
			'prc-copilot',
			plugins_url( 'build/exports/exports.js', __DIR__ ),
			$export_asset_file['dependencies'],
			$export_asset_file['version'],
			true
		);
	}
}
