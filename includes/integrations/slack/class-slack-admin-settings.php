<?php
/**
 * Slack Admin Settings Page
 *
 * @package PRC\Platform\Nexus\Integrations\Slack
 */

namespace PRC\Platform\Nexus\Integrations\Slack;

/**
 * Class Slack_Admin_Settings
 *
 * Handles admin settings page for Slack integration.
 */
class Slack_Admin_Settings {

	/**
	 * Constructor.
	 *
	 * @param object $loader The loader object.
	 */
	public function __construct( $loader = null ) {
		if ( null !== $loader ) {
			$loader->add_action( 'admin_menu', $this, 'add_admin_menu' );
			$loader->add_action( 'admin_init', $this, 'register_settings_fields' );
		}
	}

	/**
	 * Add admin menu page.
	 *
	 * @hook admin_menu
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'options-general.php',
			'PRC Nexus - Slack Integration',
			'PRC Nexus Slack',
			'manage_options',
			'prc-nexus-slack',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings fields.
	 *
	 * @hook admin_init
	 */
	public function register_settings_fields() {
		add_settings_section(
			'prc_nexus_slack_main',
			'Slack Integration Settings',
			array( $this, 'render_section_description' ),
			'prc-nexus-slack'
		);

		add_settings_field(
			'enabled',
			'Enable Integration',
			array( $this, 'render_enabled_field' ),
			'prc-nexus-slack',
			'prc_nexus_slack_main'
		);

		add_settings_field(
			'signing_secret',
			'Signing Secret',
			array( $this, 'render_signing_secret_field' ),
			'prc-nexus-slack',
			'prc_nexus_slack_main'
		);

		add_settings_field(
			'bot_token',
			'Bot User OAuth Token',
			array( $this, 'render_bot_token_field' ),
			'prc-nexus-slack',
			'prc_nexus_slack_main'
		);

		add_settings_field(
			'workspace_id',
			'Workspace ID',
			array( $this, 'render_workspace_id_field' ),
			'prc-nexus-slack',
			'prc_nexus_slack_main'
		);

		add_settings_field(
			'rate_limit',
			'Rate Limit (per user/hour)',
			array( $this, 'render_rate_limit_field' ),
			'prc-nexus-slack',
			'prc_nexus_slack_main'
		);
	}

	/**
	 * Render section description.
	 */
	public function render_section_description() {
		echo '<p>Configure the Slack integration for PRC Nexus Trending News Analysis.</p>';
		echo '<p><a href="' . esc_url( plugins_url( 'README.md', __FILE__ ) ) . '" target="_blank">View Setup Instructions</a></p>';
	}

	/**
	 * Render enabled field.
	 */
	public function render_enabled_field() {
		$settings = Slack_Integration::get_settings();
		$checked  = $settings['enabled'] ? 'checked' : '';
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( Slack_Integration::SETTINGS_KEY ); ?>[enabled]" value="1" <?php echo esc_attr( $checked ); ?> />
			Enable Slack integration
		</label>
		<p class="description">Turn on to allow Slack commands to work.</p>
		<?php
	}

	/**
	 * Render signing secret field.
	 */
	public function render_signing_secret_field() {
		// Check if using constant.
		if ( defined( 'PRC_PLATFORM_SLACK_SIGNING_SECRET' ) ) {
			?>
			<p class="description">
				✅ <strong>Configured via constant:</strong> <code>PRC_PLATFORM_SLACK_SIGNING_SECRET</code><br>
				Value: <code>***<?php echo esc_html( substr( constant( 'PRC_PLATFORM_SLACK_SIGNING_SECRET' ), -4 ) ); ?></code> (last 4 characters shown)
			</p>
			<?php
			return;
		}

		$settings = Slack_Integration::get_settings();
		$value    = $settings['signing_secret'];
		?>
		<input type="password" name="<?php echo esc_attr( Slack_Integration::SETTINGS_KEY ); ?>[signing_secret]"
			value="<?php echo esc_attr( $value ); ?>" class="regular-text" autocomplete="off" />
		<p class="description">
			Found in Slack App settings → Basic Information → App Credentials → Signing Secret<br>
			<strong>Recommended:</strong> Use <code>PRC_PLATFORM_SLACK_SIGNING_SECRET</code> constant instead.
		</p>
		<?php
	}

	/**
	 * Render bot token field.
	 */
	public function render_bot_token_field() {
		// Check if using constant.
		if ( defined( 'PRC_PLATFORM_SLACK_TOKEN' ) ) {
			?>
			<p class="description">
				✅ <strong>Configured via constant:</strong> <code>PRC_PLATFORM_SLACK_TOKEN</code><br>
				Value: <code>xoxb-***<?php echo esc_html( substr( constant( 'PRC_PLATFORM_SLACK_TOKEN' ), -4 ) ); ?></code> (last 4 characters shown)
			</p>
			<?php
			return;
		}

		$settings = Slack_Integration::get_settings();
		$value    = $settings['bot_token'];
		?>
		<input type="password" name="<?php echo esc_attr( Slack_Integration::SETTINGS_KEY ); ?>[bot_token]"
			value="<?php echo esc_attr( $value ); ?>" class="regular-text" autocomplete="off" />
		<p class="description">
			Found in Slack App settings → OAuth & Permissions → Bot User OAuth Token (starts with xoxb-)<br>
			<strong>Recommended:</strong> Use <code>PRC_PLATFORM_SLACK_TOKEN</code> constant instead.
		</p>
		<?php
	}

	/**
	 * Render workspace ID field.
	 */
	public function render_workspace_id_field() {
		// Check if using constant.
		if ( defined( 'PRC_PLATFORM_SLACK_WORKSPACE_ID' ) ) {
			?>
			<p class="description">
				✅ <strong>Configured via constant:</strong> <code>PRC_PLATFORM_SLACK_WORKSPACE_ID</code><br>
				Value: <code><?php echo esc_html( constant( 'PRC_PLATFORM_SLACK_WORKSPACE_ID' ) ); ?></code>
			</p>
			<?php
			return;
		}

		$settings = Slack_Integration::get_settings();
		$value    = $settings['workspace_id'];
		?>
		<input type="text" name="<?php echo esc_attr( Slack_Integration::SETTINGS_KEY ); ?>[workspace_id]"
			value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
		<p class="description">
			Your Slack workspace ID (e.g., T1234567890)<br>
			<strong>Recommended:</strong> Use <code>PRC_PLATFORM_SLACK_WORKSPACE_ID</code> constant instead.
		</p>
		<?php
	}

	/**
	 * Render rate limit field.
	 */
	public function render_rate_limit_field() {
		$settings = Slack_Integration::get_settings();
		$value    = $settings['rate_limit'];
		?>
		<input type="number" name="<?php echo esc_attr( Slack_Integration::SETTINGS_KEY ); ?>[rate_limit]"
			value="<?php echo esc_attr( $value ); ?>" class="small-text" min="1" max="100" />
		<p class="description">Maximum number of requests per user per hour (1-100)</p>
		<?php
	}

	/**
	 * Render settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'prc-nexus' ) );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php
			// Show status indicator.
			$is_enabled = Slack_Integration::is_enabled();
			if ( $is_enabled ) {
				echo '<div class="notice notice-success"><p><strong>✓ Slack integration is active and configured.</strong></p></div>';
			} else {
				echo '<div class="notice notice-warning"><p><strong>⚠ Slack integration is not fully configured.</strong></p></div>';
			}
			?>

			<form method="post" action="options.php">
				<?php
				wp_nonce_field( 'prc_nexus_slack_settings', 'prc_nexus_slack_nonce' );
				settings_fields( 'prc_nexus_settings' );
				do_settings_sections( 'prc-nexus-slack' );
				submit_button();
				?>
			</form>

			<hr />

			<h2>Endpoints</h2>
			<p>Use these URLs in your Slack app configuration:</p>
			<table class="widefat">
				<thead>
					<tr>
						<th>Purpose</th>
						<th>URL</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>Slash Command</td>
						<td><code><?php echo esc_html( esc_url_raw( rest_url( 'prc-api/v3/nexus/slack/trending-news' ) ) ); ?></code></td>
					</tr>
					<tr>
						<td>Interactive Components</td>
						<td><code><?php echo esc_html( esc_url_raw( rest_url( 'prc-api/v3/nexus/slack/interactive' ) ) ); ?></code></td>
					</tr>
				</tbody>
			</table>

			<hr />

			<h2>Usage Example</h2>
			<p>Once configured, users can use the following command in Slack:</p>
			<pre><code>/trending-news category:technology total:5</code></pre>

			<h3>Available Parameters:</h3>
			<ul>
				<li><code>category</code> - nation, world, business, technology, sports, science, health, entertainment</li>
				<li><code>total</code> - Number of articles (1-100)</li>
				<li><code>from</code> - Start date (YYYY-MM-DD)</li>
				<li><code>to</code> - End date (YYYY-MM-DD)</li>
				<li><code>query</code> - Search query</li>
				<li><code>format</code> - json or markdown</li>
			</ul>

			<hr />

			<h2>Documentation</h2>
			<p>
				<a href="<?php echo esc_url( plugins_url( 'README.md', __FILE__ ) ); ?>" target="_blank" class="button">
					View Full Documentation
				</a>
			</p>
		</div>
		<?php
	}
}
