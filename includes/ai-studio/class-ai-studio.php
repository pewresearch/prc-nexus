<?php
// AI Studio
define( 'AI_STUDIO_URL', plugin_dir_url( __FILE__ ) );
define( 'AI_STUDIO_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Enqueue AI Studio assets.
 *
 * @param mixed $hook
 * @return void
 */
function ai_studio_enqueue_scripts( $hook ) {
	if ( 'toplevel_page_ai-studio' !== $hook ) {
		return;
	}

	$asset_file = include AI_STUDIO_PATH . 'build/index.asset.php';

	wp_enqueue_script(
		'ai-studio-script',
		AI_STUDIO_URL . 'build/index.js',
		$asset_file['dependencies'],
		$asset_file['version'],
		true
	);


	// Enqueue the built styles (includes DataViews styles).
	wp_enqueue_style(
		'ai-studio-style',
		AI_STUDIO_URL . 'build/style-index.css',
		array( 'wp-components' ),
		$asset_file['version']
	);
}
add_action( 'admin_enqueue_scripts', 'ai_studio_enqueue_scripts' );

/**
 * Add admin menu to access AI Studio.
 *
 * @return void
 */
function ai_studio_add_menu() {
	add_menu_page(
		'AI Studio',
		'AI Studio',
		'manage_options',
		'ai-studio',
		'ai_studio_render_page',
		'dashicons-forms',
		30
	);
}
add_action( 'admin_menu', 'ai_studio_add_menu' );

/**
 * Render the AI Studio UI.
 *
 * @return void
 */
function ai_studio_render_page() {
	?>
	<div class="wrap">
		<h1>🌀 AI Studio</h1>
		<div id="ai-studio-root"></div>
	</div>
	<?php
}
