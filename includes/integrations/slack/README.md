# Slack Integration for PRC Nexus

## Overview

This Slack integration allows users to interact with the Trending News Analysis ability directly from Slack using slash commands.

## Features

- ✅ **Slash Command Interface** - Simple `/trending-news` command
- ✅ **Async Processing** - Uses Action Scheduler for background processing
- ✅ **Rich Formatting** - Slack Block Kit for beautiful responses
- ✅ **Rate Limiting** - Configurable per-user rate limits
- ✅ **Error Handling** - Friendly error messages with troubleshooting tips
- ✅ **Interactive Buttons** - Rerun analysis or view in WordPress
- ✅ **Secure** - Request signature verification

## Setup

### 1. Create Slack App

1. Go to https://api.slack.com/apps
2. Click "Create New App" → "From scratch"
3. Name: "PRC Nexus" (or your choice)
4. Select your workspace

### 2. Configure Slash Command

1. In your Slack app settings, go to "Slash Commands"
2. Click "Create New Command"
3. Configure:
    - **Command**: `/trending-news`
    - **Request URL**: `https://your-site.com/wp-json/prc-api/v3/nexus/slack/trending-news`
    - **Short Description**: "Analyze trending news"
    - **Usage Hint**: `category:nation articles:5 format:markdown`

### 3. Enable Interactivity

1. Go to "Interactivity & Shortcuts"
2. Turn on Interactivity
3. **Request URL**: `https://your-site.com/wp-json/prc-api/v3/nexus/slack/interactive`

### 4. Get Credentials

1. Go to "Basic Information"
2. Copy **Signing Secret**
3. Go to "OAuth & Permissions"
4. Install app to workspace
5. Copy **Bot User OAuth Token**

### 5. Configure WordPress

#### Recommended: Use Environment Constants (Secure)

Add these constants to your `vip-config/vip-env-vars.local.php` or VIP environment configuration:

```php
define( 'PRC_PLATFORM_SLACK_TOKEN', 'xoxb-your-bot-token-here' );
define( 'PRC_PLATFORM_SLACK_SIGNING_SECRET', 'your-signing-secret-here' );
define( 'PRC_PLATFORM_SLACK_WORKSPACE_ID', 'T1234567890' );
```

**Why use constants?**

- ✅ Secrets never stored in database
- ✅ More secure than database storage
- ✅ Follows WordPress VIP best practices
- ✅ Environment-specific configuration

#### Alternative: Database Configuration (Less Secure)

1. Go to WordPress admin
2. Navigate to Settings → PRC Nexus Slack
3. Enter:
    - **Signing Secret**: From step 4
    - **Bot Token**: From step 4
    - **Workspace ID**: Your Slack workspace ID (e.g., T1234567890)
    - **Enable Integration**: Check the box
    - **Rate Limit**: Set per-user hourly limit (default: 10)

**Note:** If constants are defined, the admin UI will show status-only fields.

## Usage

### Basic Command

```
/trending-news
```

Uses default parameters:

- Category: nation
- Articles: 5
- Format: markdown

### With Parameters

```
/trending-news category:technology articles:10
```

```
/trending-news category:world from:2025-09-01 to:2025-09-30
```

```
/trending-news query:"artificial intelligence" articles:3
```

### Available Parameters

| Parameter  | Description                   | Default    | Example                  |
| ---------- | ----------------------------- | ---------- | ------------------------ |
| `category` | News category                 | `nation`   | `category:technology`    |
| `articles` | Number of articles (1-100)    | `5`        | `articles:10`            |
| `from`     | Start date (YYYY-MM-DD)       | Yesterday  | `from:2025-09-01`        |
| `to`       | End date (YYYY-MM-DD)         | Today      | `to:2025-09-30`          |
| `query`    | Search query                  | _(empty)_  | `query:"climate change"` |
| `format`   | Output format (json/markdown) | `markdown` | `format:json`            |

### Categories

- `nation` - National news
- `world` - World news
- `business` - Business news
- `technology` - Technology news
- `sports` - Sports news
- `science` - Science news
- `health` - Health news
- `entertainment` - Entertainment news

## Response Format

The bot will:

1. **Immediately respond** with an acknowledgment message
2. **Process in background** (takes 90-120+ seconds)
3. **Post results** when complete with:
    - 📰 Analysis header
    - 📊 Metadata (user, category, date)
    - 📝 Trending news items and story suggestions
    - 🔗 Related PRC reports
    - 🔄 Interactive buttons

## Interactive Features

### Run Again Button

Click to rerun the analysis with the same parameters.

### View in WordPress Button

Opens the WordPress admin panel for more detailed analysis.

## Rate Limiting

- Default: 10 requests per user per hour
- Configurable in WordPress settings
- Rate limit enforced per Slack user ID
- Friendly error message when exceeded

## Error Handling

The integration provides helpful error messages for:

- ❌ Rate limit exceeded
- ❌ Invalid parameters
- ❌ API failures
- ❌ Authentication errors

Each error includes troubleshooting tips.

## Security

### Request Verification

All requests are verified using Slack's signature verification:

1. Checks `X-Slack-Signature` header
2. Validates `X-Slack-Request-Timestamp` (5-minute window)
3. Uses timing-safe comparison
4. Prevents replay attacks

### WordPress Permissions

- Integration must be enabled in WordPress
- Signing secret and bot token required
- All API calls logged for audit

## Technical Details

### Architecture

```
User → Slack → WordPress REST API → Action Scheduler → Analysis → Slack Webhook
```

### Files

- `class-slack-integration.php` - Main integration class
- `class-slack-rest-api.php` - REST endpoint handler
- `class-slack-signature-verifier.php` - Security verification
- `class-slack-action-scheduler.php` - Background processing
- `class-slack-response-formatter.php` - Slack Block Kit formatting

### Action Scheduler

Jobs are queued in the `prc-nexus-slack` group:

```php
as_enqueue_async_action(
    'prc_nexus_slack_trending_news_analysis',
    $args,
    'prc-nexus-slack'
);
```

### Hooks

**Actions:**

- `prc_nexus_slack_trending_news_analysis` - Process analysis job
- `prc_nexus_slack_command_used` - Log usage (for analytics)

**Filters:**

- `prc_api_endpoints` - Register REST endpoints

## Troubleshooting

### "Invalid Slack signature"

- Verify signing secret is correct in WordPress settings
- Check your server time is synchronized (NTP)
- Ensure HTTPS is properly configured

### "Slack integration is not enabled"

- Go to WordPress admin → Settings → PRC Nexus
- Enable the integration
- Save settings

### "Rate limit exceeded"

- Wait one hour before trying again
- Contact admin to increase rate limit

### Analysis takes too long

- Normal processing time: 10-30 seconds
- Check Action Scheduler status: WP Admin → Tools → Scheduled Actions
- Look for jobs in `prc-nexus-slack` group

### No response in Slack

- Check WordPress error logs
- Verify response_url is valid
- Test REST API endpoint manually

## Development

### Testing the REST API

```bash
curl -X POST https://your-site.com/wp-json/prc-api/v3/nexus/slack/trending-news \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "X-Slack-Request-Timestamp: $(date +%s)" \
  -H "X-Slack-Signature: v0=..." \
  -d "text=category:technology&user_id=U123&user_name=test&response_url=..."
```

### Viewing Scheduled Jobs

Go to: **WP Admin → Tools → Scheduled Actions**

Filter by: `prc-nexus-slack` group

### Debugging

Enable debug logging in `wp-config.php`:

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
```

Logs location: `wp-content/debug.log`

## Future Enhancements

Potential improvements:

- [ ] OAuth installation flow
- [ ] Per-workspace settings
- [ ] Usage analytics dashboard
- [ ] More interactive commands
- [ ] Save favorite queries
- [ ] Schedule recurring analyses
- [ ] Export to PDF/CSV

## Support

For issues or questions:

1. Check WordPress error logs
2. Review Slack app event logs
3. Test REST API endpoints
4. Contact PRC Platform team

## License

GPL-2.0-or-later
