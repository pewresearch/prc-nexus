# Debugging the Admin Settings Page

If the admin settings page is not showing up, try these steps:

## 1. Check if the class is being loaded

Add this temporarily to `wp-config.php`:

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

Then check `wp-content/debug.log` for any errors.

## 2. Verify the menu hook is firing

Add this temporarily to the bottom of `class-slack-admin-settings.php` before the closing `}`:

```php
add_action( 'admin_menu', function() {
    error_log( 'Admin menu hook fired' );
}, 1 );

add_action( 'admin_menu', function() {
    error_log( 'Slack admin menu should be added now' );
}, 999 );
```

Check the logs to see if both hooks fire.

## 3. Check if the submenu is registered

Add this to `functions.php` temporarily:

```php
add_action( 'admin_menu', function() {
    global $submenu;
    error_log( 'Settings submenu: ' . print_r( $submenu['options-general.php'] ?? 'NOT FOUND', true ) );
}, 999 );
```

## 4. Verify the class is instantiated

Add this to the constructor of `Slack_Admin_Settings`:

```php
public function __construct( $loader = null ) {
    error_log( 'Slack_Admin_Settings constructor called with loader: ' . ( $loader ? 'YES' : 'NO' ) );
    if ( null !== $loader ) {
        error_log( 'Adding admin_menu action' );
        $loader->add_action( 'admin_menu', $this, 'add_admin_menu' );
        $loader->add_action( 'admin_init', $this, 'register_settings_fields' );
    }
}
```

## 5. Check if add_admin_menu is called

Add this to the `add_admin_menu` method:

```php
public function add_admin_menu() {
    error_log( 'Slack add_admin_menu method called!' );
    $result = add_submenu_page(
        'options-general.php',
        'PRC Nexus - Slack Integration',
        'PRC Nexus Slack',
        'manage_options',
        'prc-nexus-slack',
        array( $this, 'render_settings_page' )
    );
    error_log( 'add_submenu_page returned: ' . print_r( $result, true ) );
}
```

## 6. Common Issues

### Issue: Loader not passing hooks correctly

**Solution**: Check if the loader's `run()` method is being called. In `includes/class-loader.php`, verify hooks are being added to WordPress.

### Issue: is_admin() returns false

**Solution**: Make sure you're checking this in the admin context, not during AJAX or REST API calls.

### Issue: Capabilities issue

**Solution**: Make sure you're logged in as an administrator with `manage_options` capability.

### Issue: Cached menus

**Solution**: Try:

1. Logout and login again
2. Clear browser cache
3. Clear any object cache (Redis/Memcached)
4. Visit different admin pages

## 7. Quick Test

Visit this URL directly (replace with your domain):

```
https://your-site.com/wp-admin/options-general.php?page=prc-nexus-slack
```

If you get a "You do not have sufficient permissions" or similar error, the page IS registered but there's a permissions issue.

If you get "Page not found" or redirect, the page is NOT registered.

## 8. Manual Registration Test

Add this temporarily to `functions.php`:

```php
add_action( 'admin_menu', function() {
    add_submenu_page(
        'options-general.php',
        'TEST Slack',
        'TEST Slack',
        'manage_options',
        'test-slack',
        function() {
            echo '<h1>Test Slack Page</h1>';
        }
    );
} );
```

If this test page shows up but the real one doesn't, there's an issue with the class/loader integration.

## 9. Check Loader Implementation

Verify the loader is actually registering hooks. Check `includes/class-loader.php` and ensure the `run()` method calls:

```php
foreach ( $this->actions as $hook ) {
    add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
}
```

## 10. Nuclear Option: Direct Hook

If nothing else works, bypass the loader temporarily by adding this to the Slack_Integration constructor:

```php
if ( is_admin() ) {
    add_action( 'admin_menu', function() {
        add_submenu_page(
            'options-general.php',
            'PRC Nexus - Slack Integration',
            'PRC Nexus Slack',
            'manage_options',
            'prc-nexus-slack',
            function() {
                require_once __DIR__ . '/class-slack-admin-settings.php';
                $settings = new Slack_Admin_Settings( null );
                $settings->render_settings_page();
            }
        );
    } );
}
```

This will tell you if the issue is with the loader or something else.
