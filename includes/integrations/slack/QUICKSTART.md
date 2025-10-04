# Quick Start Guide: Slack Bot for Trending News Analysis

## 🚀 5-Minute Setup

### Step 1: Create Slack App (2 minutes)

1. Go to <https://api.slack.com/apps>
2. Click **"Create New App"** → **"From scratch"**
3. App Name: **"PRC Nexus"**
4. Select your workspace
5. Click **"Create App"**

### Step 2: Add Slash Command (1 minute)

1. In left sidebar, click **"Slash Commands"**
2. Click **"Create New Command"**
3. Fill in:
    - **Command**: `/trending-news`
    - **Request URL**: `https://your-site.com/wp-json/prc-api/v3/nexus/slack/trending-news`
    - **Short Description**: Analyze trending news
    - **Usage Hint**: `category:nation articles:5`
4. Click **"Save"**

### Step 3: Enable Interactivity (30 seconds)

1. In left sidebar, click **"Interactivity & Shortcuts"**
2. Toggle **"Interactivity"** to **ON**
3. **Request URL**: `https://your-site.com/wp-json/prc-api/v3/nexus/slack/interactive`
4. Click **"Save Changes"**

### Step 4: Install to Workspace (30 seconds)

1. In left sidebar, click **"Install App"**
2. Click **"Install to Workspace"**
3. Review permissions
4. Click **"Allow"**

### Step 5: Get Credentials (30 seconds)

1. In left sidebar, click **"Basic Information"**
2. Scroll to **"App Credentials"**
3. Copy **"Signing Secret"** (click "Show" first)
4. In left sidebar, click **"OAuth & Permissions"**
5. Copy **"Bot User OAuth Token"** (starts with `xoxb-`)

### Step 6: Configure WordPress (30 seconds)

1. Log into WordPress admin
2. Go to **Settings → PRC Nexus Slack**
3. Enter:
    - **Signing Secret**: (paste from step 5)
    - **Bot User OAuth Token**: (paste from step 5)
    - Check **"Enable Integration"**
4. Click **"Save Changes"**

### Step 7: Test! (10 seconds)

1. Open Slack
2. Go to any channel
3. Type: `/trending-news`
4. Press Enter
5. Wait ~20 seconds for results

## ✅ That's it!

You should see a response like:

```
📰 Trending News Analysis Complete

Requested by: @you
Category: nation
Articles: 5
Date: 2025-09-30

[Analysis results here...]

🔄 Run Again    📊 View in WordPress
```

## 💡 Try These Commands

**Different category:**

```text
/trending-news category:technology
```

**More articles:**

```text
/trending-news articles:10
```

**Specific query:**

```text
/trending-news query:"artificial intelligence" articles:3
```

**Date range:**

```text
/trending-news from:2025-09-01 to:2025-09-30
```

## 🔧 Troubleshooting

### "Invalid Slack signature"

- Double-check the Signing Secret in WordPress
- Make sure you copied the entire secret

### "Slack integration is not enabled"

- Go to Settings → PRC Nexus Slack
- Make sure "Enable Integration" is checked
- Click "Save Changes"

### No response after 30 seconds

- Check **WP Admin → Tools → Scheduled Actions**
- Look for jobs in `prc-nexus-slack` group
- Check WordPress error logs

### Still stuck?

See full documentation:

- [README.md](README.md) - Complete guide
- [CONFIGURATION.md](CONFIGURATION.md) - Advanced setup
- [IMPLEMENTATION_SUMMARY.md](IMPLEMENTATION_SUMMARY.md) - Technical details

## 📚 Available Parameters

| Parameter  | Options                                                                     | Example                  |
| ---------- | --------------------------------------------------------------------------- | ------------------------ |
| `category` | nation, world, business, technology, sports, science, health, entertainment | `category:world`         |
| `articles` | 1-100                                                                       | `articles:10`            |
| `from`     | YYYY-MM-DD                                                                  | `from:2025-09-01`        |
| `to`       | YYYY-MM-DD                                                                  | `to:2025-09-30`          |
| `query`    | Any search term                                                             | `query:"climate change"` |
| `format`   | json, markdown                                                              | `format:markdown`        |

## 🎯 Pro Tips

1. **Default is smart**: Just `/trending-news` works great
2. **Combine parameters**: `category:tech articles:3 format:json`
3. **Use quotes for multi-word queries**: `query:"artificial intelligence"`
4. **Results are public**: Everyone in channel will see (for now)
5. **Rate limited**: 10 requests per hour per user (configurable)

## 🎉 Enjoy!

You now have a powerful AI-powered trending news analyzer right in Slack!
