<?php
/**
 * Plugin Activator
 *
 * @package PRC\Platform\Nexus
 */

namespace PRC\Platform\Nexus;

/**
 * Plugin Activator
 */
class Plugin_Activator {

	/**
	 * Activate the plugin
	 */
	public static function activate() {
		flush_rewrite_rules();

		wp_mail(
			DEFAULT_TECHNICAL_CONTACT,
			'PRC Nexus Activated',
			'The PRC Nexus plugin has been activated on ' . get_site_url()
		);
	}
}
