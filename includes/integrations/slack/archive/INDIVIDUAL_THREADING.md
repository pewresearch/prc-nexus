# Individual Story Threading Model

## Overview

The Slack integration now uses an **individual threading model** where each trending story is posted as a separate channel message with its own dedicated thread for details.

## Architecture Change

### Old Model (Consolidated Thread)

```
Channel:
  📰 Trending News Analysis - Technology
  ✨ 3 stories analyzed:
  1️⃣ AI Regulations (2 angles)
  2️⃣ Remote Work (1 angle)
  3️⃣ Social Media (3 angles)
  [3 replies] ← All story details in one thread
```

### New Model (Individual Threads)

```
Channel:
  1️⃣ AI Regulations lessens after deal
  💡 2 PRC story angles | 📊 3 related reports | 🔗 Source
  [1 reply] ← Full analysis for story 1

  2️⃣ Remote Work Trends shift post-pandemic
  💡 1 PRC story angle | 📊 2 related reports | 🔗 Source
  [1 reply] ← Full analysis for story 2

  3️⃣ Social Media Usage reaches new heights
  💡 3 PRC story angles | 📊 4 related reports | 🔗 Source
  [1 reply] ← Full analysis for story 3
```

## Benefits

### 1. Focused Discussions

Each story has its own conversation thread, making it easier to:

- Discuss specific stories without confusion
- Track conversations about individual topics
- Keep related comments together

### 2. Better Referencing

Team members can easily reference stories:

- "Check out the 1️⃣ message" vs "Look at the first reply in the thread"
- Link directly to a specific story
- React to individual stories with emojis

### 3. Improved Reactions

Slack reactions work better:

- 👍 on a specific story shows agreement with that story
- Team can vote/react to individual stories
- More granular feedback

### 4. Modular Sharing

Stories can be:

- Bookmarked individually
- Shared to other channels
- Referenced in other discussions
- Forwarded to specific people

### 5. Natural Threading

One topic = one thread is more intuitive:

- Follows Slack's threading best practices
- Easier for new team members to understand
- Less cognitive load when scanning

## Implementation Details

### Flow

1. **User runs command**: `/trending-news category:tech articles:3`
2. **Action Scheduler job starts**
3. **Analysis completes** with JSON data for 3 stories
4. **For each story**:
    - Format slim summary message
    - Post to channel via Slack API
    - Get message timestamp
    - Format full analysis
    - Post as thread reply to that message
    - Wait 0.5 seconds (rate limiting)
5. **Complete**

### Code Structure

**New Formatter Method:**

```php
Slack_Response_Formatter::format_individual_story_summary(
    $story,    // Single story data
    $index,    // Story index (0-based)
    $args,     // Original command args
    $context   // User/channel context
)
```

Returns a compact Slack message with:

- Number emoji + title
- Angle/report counts + source link
- Prompt to check thread

**Action Scheduler Logic:**

```php
foreach ( $json_data as $index => $story ) {
    // 1. Post summary to channel
    $story_summary = format_individual_story_summary();
    $response = post_message( $channel_id, $summary );

    // 2. Get timestamp for threading
    $message_ts = $response['ts'];

    // 3. Post full details as thread
    $story_details = format_story_thread_message();
    post_message( $channel_id, $details, $message_ts );

    // 4. Rate limit delay
    usleep( 500000 ); // 0.5 seconds
}
```

### Message Format

**Channel Message (Slim Summary):**

```
1️⃣ AI Regulations lessens after Pfizer deal
💡 2 PRC story angles | 📊 3 related reports | 🔗 Source
👇 Click thread below for full PRC analysis
```

**Thread Reply (Full Details):**

```
━━━━━━━━━━━━━━━━━━━━━━━━━━
1️⃣ AI Regulations lessens after Pfizer deal

📝 Summary
Pfizer's deal with Trump was a relief to the pharma industry...

🔗 Source: cnbc.com

💡 Story Angles for PRC:

→ Angle 1:
"As Trump administration averts pharma tariffs, here's how
Americans view his trade policies"

How to use: This story would use data on public opinion...

📊 Related PRC Research:
• Americans' Views on Trade Policy
• Public Opinion on Healthcare Costs
• Trust in Pharmaceutical Industry
```

## Rate Limiting

### Why 0.5 Second Delays?

Slack API rate limits:

- Tier 3 methods (chat.postMessage): ~1/second per channel
- With 0.5s delays, we post 2 messages/second
- Each story = 2 messages (summary + thread)
- 3 stories = 6 messages = ~3 seconds total

Safe and well within limits.

### Scalability

For larger analyses:

- 5 stories = 10 messages = ~5 seconds
- 10 stories = 20 messages = ~10 seconds

Still acceptable for background job processing.

## Error Handling

### Fallback Strategy

If individual posting fails:

1. Catch any errors during story posting
2. Track `$success_count` for successfully posted stories
3. If `$success_count === 0`, fall back to old method
4. Post full consolidated response via `response_url`

This ensures users always get results, even if API fails.

### Partial Success

If some stories post but not all:

- Users still see the successful stories
- Partial data is better than no data
- Error logged for debugging

## User Experience

### Discovery

Users will naturally:

1. See multiple story summaries appear in channel
2. Recognize them by number emojis (1️⃣, 2️⃣, 3️⃣)
3. Click thread on interesting stories
4. Skip stories they're not interested in

### Engagement

Team can:

- React to specific stories (👍 ❤️ 👀)
- Reply in thread to discuss
- Share individual stories
- Bookmark favorites

### Mobile Experience

On mobile:

- Compact summaries are easier to scan
- One tap opens the thread you want
- Don't need to scroll through unrelated stories
- Better use of screen space

## Comparison with Old Model

| Aspect            | Old (Consolidated)     | New (Individual)         |
| ----------------- | ---------------------- | ------------------------ |
| **Channel Space** | 1 message              | 3-5 messages             |
| **Discussions**   | Mixed in one thread    | Focused per story        |
| **Reactions**     | To whole analysis      | To specific stories      |
| **Sharing**       | Share all or nothing   | Share individual stories |
| **Scanning**      | Must open thread       | See headlines in channel |
| **Mobile UX**     | Scroll through thread  | Tap story of interest    |
| **References**    | "Check thread reply 2" | "Look at 2️⃣"             |

## Testing

To verify the new model:

1. **Run command:**

    ```
    /trending-news category:technology articles:3
    ```

2. **Check channel:**
    - Should see 3 separate messages appear
    - Each with number emoji + title
    - Each with counts and source
    - Each with "Click thread below" prompt

3. **Check threads:**
    - Each message should have [1 reply]
    - Click to see full analysis for that story
    - Verify no messages are mixed between stories

4. **Check timing:**
    - Messages should appear ~0.5 seconds apart
    - Total time: ~3 seconds for 3 stories
    - No rate limit errors

5. **Test reactions:**
    - React to one story message
    - Verify reaction appears on that story only
    - Check other stories unaffected

## Future Enhancements

### Potential Additions

1. **Header Message**
    - Optional: "📰 Analyzing 3 Technology stories..."
    - Posted before stories
    - Provides batch context

2. **Footer Summary**
    - Optional: "✅ 3 stories analyzed in Technology"
    - Posted after all stories
    - Marks completion

3. **Thread Relationships**
    - Could thread all stories to a header
    - Creates hierarchy: Header → Stories → Details
    - More complex but provides grouping

4. **Batch Actions**
    - "Run again" button could be on each story
    - Or on a header/footer message
    - Per-story regeneration

### Not Recommended

- ❌ Threading stories to each other - too confusing
- ❌ Posting all at once (no delays) - rate limit issues
- ❌ Consolidating after posting - defeats the purpose

## Migration Notes

### Backward Compatibility

Old methods are deprecated but still available:

- `format_slim_channel_message()` - marked deprecated
- `send_threaded_analysis()` - removed (not needed)
- Fallback still uses old consolidated format

### For Existing Users

When they run `/trending-news` after this update:

- Behavior changes immediately
- Will see individual messages instead of one
- May need brief explanation in channel
- Consider posting announcement about new format

## Summary

The individual threading model provides:

- ✅ Better focused discussions
- ✅ Easier story referencing
- ✅ Improved engagement (reactions, sharing)
- ✅ More natural Slack threading
- ✅ Better mobile experience
- ✅ Maintained functionality and fallbacks

This change makes the Slack integration more aligned with how teams naturally communicate in Slack.
