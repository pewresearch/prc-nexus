# Slack Integration Configuration Example

## Environment Variables (Recommended for Production)

For better security, you can define these as environment variables or constants in `wp-config.php`:

```php
// In wp-config.php
define( 'PRC_NEXUS_SLACK_SIGNING_SECRET', 'your-signing-secret-here' );
define( 'PRC_NEXUS_SLACK_BOT_TOKEN', 'xoxb-your-bot-token-here' );
```

## Slack App Manifest (Optional)

You can use this manifest to quickly create your Slack app:

```json
{
	"display_information": {
		"name": "PRC Nexus",
		"description": "AI tools and orchestration for PRC Platform.",
		"background_color": "#002A3E"
	},
	"features": {
		"bot_user": {
			"display_name": "PRC Nexus",
			"always_online": true
		},
		"slash_commands": [
			{
				"command": "/trending-news",
				"url": "https://www.pewresearch.org/wp-json/prc-api/v3/nexus/slack/trending-news",
				"description": "Analyze trending news",
				"usage_hint": "category:nation articles:5 format:markdown",
				"should_escape": false
			}
		],
		"interactivity": {
			"is_enabled": true,
			"request_url": "https://www.pewresearch.org/wp-json/prc-api/v3/nexus/slack/interactive"
		}
	},
	"oauth_config": {
		"scopes": {
			"bot": ["commands", "chat:write", "chat:write.public"]
		}
	},
	"settings": {
		"org_deploy_enabled": false,
		"socket_mode_enabled": false,
		"token_rotation_enabled": false
	}
}
```

## WordPress Settings

Navigate to: **Settings → PRC Nexus Slack**

Required fields:

- ✅ Enable Integration: `checked`
- ✅ Signing Secret: From Slack App → Basic Information
- ✅ Bot User OAuth Token: From Slack App → OAuth & Permissions

Optional fields:

- Workspace ID: Your Slack workspace ID (e.g., T1234567890)
- Rate Limit: 10 (or your preferred limit per user per hour)

## Endpoint URLs

**Slash Command URL:**

```
https://your-site.com/wp-json/prc-api/v3/nexus/slack/trending-news
```

**Interactive Components URL:**

```
https://your-site.com/wp-json/prc-api/v3/nexus/slack/interactive
```

## Testing the Integration

### 1. Test REST API Directly

```bash
# Replace with your actual values
curl -X POST https://your-site.com/wp-json/prc-api/v3/nexus/slack/trending-news \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "X-Slack-Request-Timestamp: $(date +%s)" \
  -H "X-Slack-Signature: v0=YOUR_SIGNATURE" \
  -d "text=category:technology articles:3" \
  -d "user_id=U12345678" \
  -d "user_name=testuser" \
  -d "channel_id=C12345678" \
  -d "response_url=https://hooks.slack.com/commands/..."
```

### 2. Test from Slack

```
/trending-news category:nation articles:3
```

### 3. Check Action Scheduler

Go to: **WP Admin → Tools → Scheduled Actions**

Filter by group: `prc-nexus-slack`

You should see queued or completed jobs.

## Troubleshooting Checklist

- [ ] Slack app is installed to workspace
- [ ] Signing secret is correct in WordPress settings
- [ ] Bot token is correct and starts with `xoxb-`
- [ ] Integration is enabled in WordPress
- [ ] Endpoint URLs are correct in Slack app
- [ ] WordPress permalinks are set to "Post name" or custom
- [ ] HTTPS is properly configured
- [ ] Server time is synchronized (NTP)
- [ ] Action Scheduler is working (check Tools → Scheduled Actions)

## Rate Limit Configuration

Default: 10 requests per user per hour

To change:

1. Go to Settings → PRC Nexus Slack
2. Update "Rate Limit (per user/hour)"
3. Save changes

Recommended limits:

- Development: 100 requests/hour
- Production (small team): 10-20 requests/hour
- Production (large team): 5-10 requests/hour

## Security Notes

### Required Slack App Permissions

Minimal required OAuth scopes:

- `commands` - For slash commands
- `chat:write` - To post messages
- `chat:write.public` - To post in public channels

### Request Verification

All requests are verified using:

1. Slack signature verification
2. Timestamp validation (5-minute window)
3. Timing-safe comparison

### WordPress Security

- Only administrators can configure settings
- All user input is sanitized
- Rate limiting prevents abuse
- Usage is logged for audit

## Monitoring

### Error Logs

Check WordPress error log:

```bash
tail -f wp-content/debug.log | grep -i slack
```

### Action Scheduler Logs

Go to: **WP Admin → Tools → Scheduled Actions → Logs**

### Usage Analytics (Future)

Hook for custom analytics:

```php
add_action( 'prc_nexus_slack_command_used', function( $user_id, $user_name, $timestamp ) {
    // Log usage to your analytics system
}, 10, 3 );
```

## Support

For issues:

1. Check WordPress error logs
2. Check Slack app event logs
3. Review Action Scheduler status
4. Test REST endpoints manually
5. Verify Slack app configuration

Common issues:

- **"Invalid signature"** → Check signing secret and server time
- **"Rate limit exceeded"** → Wait 1 hour or increase limit
- **No response** → Check Action Scheduler and error logs
- **Slow response** → Normal, analysis takes 10-30 seconds

## Advanced Configuration

### Custom Timeout

Default timeout for Slack webhooks is 15 seconds. To adjust:

```php
add_filter( 'http_request_timeout', function( $timeout, $url ) {
    if ( strpos( $url, 'slack.com' ) !== false ) {
        return 30; // 30 seconds
    }
    return $timeout;
}, 10, 2 );
```

### Custom Rate Limits per User

```php
add_filter( 'prc_nexus_slack_rate_limit', function( $limit, $user_id ) {
    // VIP users get higher limit
    if ( in_array( $user_id, ['U12345', 'U67890'] ) ) {
        return 100;
    }
    return $limit;
}, 10, 2 );
```

### Custom Error Messages

```php
add_filter( 'prc_nexus_slack_error_message', function( $message, $error_code ) {
    if ( $error_code === 'rate_limit' ) {
        return 'Whoa there! You\'re using this too much. Take a break! ☕';
    }
    return $message;
}, 10, 2 );
```
