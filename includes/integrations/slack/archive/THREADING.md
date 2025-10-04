# Slack Threading Feature

## Overview

The Slack integration now uses a **two-part response pattern**:

1. **Main Channel Message** - Slim summary with headlines (max 10)
2. **Threaded Replies** - Full detailed analysis, one message per story

## Benefits

✅ **Cleaner Channels** - Main message is compact and scannable  
✅ **Better Organization** - Full details in thread  
✅ **Easy Discussion** - Team can reply to specific stories  
✅ **Reduced Noise** - Channel history stays readable  
✅ **Optional Deep Dive** - Click thread to see full analysis

## How It Works

### User Workflow

```
1. User: /trending-news category:technology
2. Immediate: "⏳ Analyzing..."
3. ~20 seconds later...
4. Main message appears with headlines and counts
5. Thread contains full analysis for each story
```

### Technical Flow

```
1. Parse slash command → Force JSON format
2. Run trending news analysis → Get structured data
3. Format slim summary from JSON
4. Post to channel via response_url → Get message timestamp
5. Format each story as separate message
6. Post to thread via Slack API using timestamp
```

## Main Message Format

```
📰 Trending News Analysis - Technology

✨ 3 stories analyzed:

1️⃣ Tech Giants Announce AI Regulations
   💡 2 PRC story angles | 📊 3 related reports | 🔗 Source

2️⃣ Remote Work Trends Shift Post-Pandemic
   💡 1 PRC story angle | 📊 2 related reports | 🔗 Source

3️⃣ Social Media Usage Reaches New Heights
   💡 3 PRC story angles | 📊 4 related reports | 🔗 Source

Requested by @username | Completed at 2:34 PM
👇 Full analysis in thread below

[🔄 Run Again] [📊 View in WordPress]
```

### Key Features

- **Number Emojis** (1️⃣-🔟) - Easy to reference in discussion
- **Truncated Titles** - Max 100 characters
- **Inline Counts** - Shows value at a glance
- **Source Links** - Direct access to original article
- **Max 10 Stories** - Keeps main message readable
- **Action Buttons** - Run again or view in WordPress

## Thread Message Format

Each story gets its own threaded reply:

```
━━━━━━━━━━━━━━━━━━━━━━━━━━
1️⃣ Tech Giants Announce AI Regulations

📝 Summary
Major technology companies agreed to new AI safety standards,
marking a significant shift in industry self-regulation...

🔗 Source: news.com

💡 Story Angles for PRC:

→ Angle 1:
"As tech companies embrace AI regulation, here's what
Americans think about government oversight"

How to use: Connect to PRC data on public attitudes toward
tech regulation and corporate responsibility

📊 Related PRC Research:
• Americans' Views on Technology Regulation
  https://pewresearch.org/...
• Public Opinion on AI Safety Standards
  https://pewresearch.org/...

→ Angle 2:
"Tech industry self-regulation: What the public thinks works"

How to use: Leverage PRC data on trust in tech companies vs
government to regulate emerging technologies

📊 Related PRC Research:
• Trust in Tech Companies vs Government
  https://pewresearch.org/...
```

### Thread Features

- **Divider** - Visual separation between stories
- **Full Summary** - Complete story context
- **Source Domain** - Extracted from URL (e.g., "news.com")
- **Multiple Angles** - Each suggestion clearly numbered
- **Usage Guidance** - "How to use" for each angle
- **Clickable Links** - All PRC research URLs

## JSON-First Processing

### Why JSON?

Instead of letting the AI generate markdown, we:

1. **Force JSON output** from the analysis
2. **Parse structured data**
3. **Format for Slack ourselves**

This gives us:

- Full control over formatting
- Consistent presentation
- Ability to create multiple formats from same data
- Easier to modify without re-running analysis

### Data Flow

```php
// 1. Force JSON format
$args['output_format'] = 'json';

// 2. Run analysis
$result = $ability->run($args);

// 3. Parse JSON
$json_data = json_decode($result['response'], true);

// 4. Create multiple formats from same data
$slim_message = format_slim_channel_message($json_data);
$thread_messages = format_story_thread_messages($json_data);
```

## Implementation Details

### New Classes

**`Slack_API_Client`**

- Direct Slack API calls
- Supports `thread_ts` parameter
- Posts multiple messages in thread
- Rate limit friendly (0.1s delay between posts)

### Updated Methods

**`Slack_Response_Formatter::format_slim_channel_message()`**

- Creates main channel message
- Shows up to 10 stories
- Counts angles and reports
- Includes source links

**`Slack_Response_Formatter::format_story_thread_message()`**

- Creates individual story message
- Full details with formatting
- Dividers for visual separation
- Multiple angles per story

**`Slack_Action_Scheduler::process_trending_news_analysis()`**

- Forces JSON format
- Posts slim message first
- Captures message timestamp
- Posts thread messages

**`Slack_Action_Scheduler::send_threaded_analysis()`**

- Formats all story messages
- Posts to thread via API client
- Handles errors gracefully

## Configuration

No additional configuration needed! The feature works automatically if:

✅ Slack integration is enabled  
✅ Bot token is configured  
✅ Bot has `chat:write` permission

## API Requirements

### Slack Scopes

Required bot token scopes:

- `commands` - For slash commands
- `chat:write` - To post messages
- `chat:write.public` - To post in public channels

### Endpoints

**Command Endpoint:**

```
POST /wp-json/prc-api/v3/nexus/slack/trending-news
```

**Slack API Endpoint:**

```
POST https://slack.com/api/chat.postMessage
Authorization: Bearer xoxb-your-bot-token
```

## Rate Limiting

### WordPress Side

- User rate limit: Configurable (default 10/hour)
- Applied per Slack user ID

### Slack API Side

- Tier 2: 20 requests per minute
- Thread messages: 0.1s delay between posts
- 10 stories = ~1 second total for threading

## Error Handling

### If Threading Fails

The system **gracefully degrades**:

1. Main message still posts (always works)
2. Threading failure is logged
3. Users see summary even if thread fails
4. Can still access full analysis in WordPress

### Common Issues

**Issue: Thread messages don't appear**

Possible causes:

- Bot token missing/invalid
- Missing `chat:write` permission
- Invalid channel ID
- Slack API rate limit

Check: WordPress error logs

**Issue: Main message works, thread doesn't**

Solution:

- Verify bot token in settings
- Check bot permissions in Slack
- Test API call manually

## Monitoring

### Check Thread Status

```php
// WordPress error logs
tail -f wp-content/debug.log | grep -i "slack"
```

### Action Scheduler

Go to: **WP Admin → Tools → Scheduled Actions**

Filter: `prc-nexus-slack` group

Check for failures or delays.

## Best Practices

### For Users

- **Reference by number** - "@user what do you think about 2️⃣?"
- **Reply in thread** - Keep discussion organized
- **React to stories** - 👍 stories you want to pursue
- **Quote angles** - Copy/paste from thread when writing

### For Admins

- **Monitor logs** - Watch for API failures
- **Check rate limits** - Ensure not hitting Slack limits
- **Test threading** - Verify after configuration changes
- **Review permissions** - Keep bot scopes minimal

## Future Enhancements

Potential improvements:

- [ ] Reactions to auto-save stories
- [ ] Direct assignment to team members
- [ ] Export to Google Docs
- [ ] Schedule recurring analyses
- [ ] Custom formatting per user
- [ ] Thread archiving to WordPress
- [ ] Analytics on most-used stories

## Troubleshooting

### No thread messages appear

**Check:**

1. Main message posted successfully?
2. Bot token configured?
3. Channel ID correct?
4. Check error logs

**Quick test:**

```bash
# Test Slack API directly
curl -X POST https://slack.com/api/chat.postMessage \
  -H "Authorization: Bearer xoxb-YOUR-TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"channel":"C12345","text":"Test"}'
```

### Thread goes to wrong channel

**Check:**

- `channel_id` in context passed correctly
- Not using private channel without invitation

### Rate limit errors

**Solution:**

- Increase delay between thread posts
- Reduce number of stories analyzed
- Batch thread messages

## Examples

### Small Analysis (3 stories)

Main message: ~200 characters  
Thread: 3 messages  
Total time: ~0.3 seconds for threading

### Large Analysis (10 stories)

Main message: ~600 characters  
Thread: 10 messages  
Total time: ~1 second for threading

### Maximum (100 articles analyzed)

Main message: Shows first 10 only  
Thread: All stories included  
Total time: ~10 seconds for threading

## Migration Notes

### From Old Format

The old format (single message with everything) is still available as fallback.

If JSON parsing fails, the system automatically uses the legacy formatter.

### Backward Compatibility

- Old installations work without changes
- Settings page unchanged
- Same slash command
- Same parameters

Only difference: Response is now split into main + thread.

## Summary

The threading feature provides a **much better UX** for Slack users:

✅ Cleaner channels  
✅ Better organization  
✅ Easier discussions  
✅ More professional appearance  
✅ Backward compatible  
✅ Graceful degradation

All while maintaining the same simple `/trending-news` command! 🎉
