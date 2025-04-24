<?php
/**
 * Plugin Deactivator
 *
 * @package PRC\Platform\Copilot
 */
namespace PRC\Platform\Copilot;

/**
 * Plugin Deactivator
 */
class Plugin_Deactivator {

	/**
	 * Deactivate the plugin
	 */
	public static function deactivate() {
		flush_rewrite_rules();

		wp_mail(
			DEFAULT_TECHNICAL_CONTACT,
			'PRC Xxx Deactivated',
			'The PRC Xxx plugin has been deactivated on ' . get_site_url()
		);
	}
}
