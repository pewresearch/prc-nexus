<?php
/**
 * WP Abilities API Manager
 *
 * @package PRC\Platform\Copilot
 */

namespace PRC\Platform\Copilot\Abilities;

/**
 * WP Abilities API Manager
 *
 * Loads new abilities and manages interface for Abilities/Tools.
 */
class Abilities_Manager {
	/**
	 * Constructor.
	 *
	 * @param Loader $loader The loader.
	 */
	public function __construct( $loader ) {
		require_once plugin_dir_path( __DIR__ ) . '/abilities/site-info/class-site-info.php';

		$site_info = new Site_Info( $loader );
	}
}
