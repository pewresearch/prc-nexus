<?php
/**
 * Plugin Activator
 *
 * @package PRC\Platform\Copilot
 */

namespace PRC\Platform\Copilot;

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
			'PRC Copilot Activated',
			'The PRC Copilot plugin has been activated on ' . get_site_url()
		);
	}
}
