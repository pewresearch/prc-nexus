# Link Preview Disable

## Change Made

Disabled automatic link previews (unfurling) in all Slack messages to reduce visual clutter.

## Implementation

Added two parameters to the `chat.postMessage` API payload in `class-slack-api-client.php`:

```php
$payload = array(
    'channel'      => $channel_id,
    'blocks'       => $blocks,
    'text'         => $text,
    'unfurl_links' => false, // Disable link previews
    'unfurl_media' => false, // Disable media previews
);
```

## What This Does

### Before (with unfurling enabled)

```
1️⃣ Story Title
   💡 2 angles | 📊 3 reports | 🔗 Source

[Large preview card with image, title, description from cnbc.com]
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  CNBC.com
  Trump pharmaceutical tariff threat...
  Full article preview with image
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

2️⃣ Next Story Title
[Another large preview card...]
```

### After (with unfurling disabled)

```
1️⃣ Story Title
   💡 2 angles | 📊 3 reports | 🔗 Source

2️⃣ Next Story Title
   💡 1 angle | 📊 2 reports | 🔗 Source

3️⃣ Third Story Title
   💡 3 angles | 📊 4 reports | 🔗 Source
```

Much cleaner and more compact!

## Technical Details

### Slack Unfurling Parameters

- **`unfurl_links`**: Controls automatic link preview expansion
    - `true` (default): Shows rich previews for URLs
    - `false`: Shows only the URL text/link

- **`unfurl_media`**: Controls automatic media expansion
    - `true` (default): Shows inline images/videos
    - `false`: Shows only links to media

### Why Disable?

1. **Reduces clutter**: Each story already has metadata (angles, reports, source)
2. **Faster scanning**: Users can see more stories at once
3. **Consistent formatting**: Preview cards vary by site and can be unpredictable
4. **Thread context**: Main message is slim summary; details are in thread
5. **Mobile friendly**: Less scrolling required on mobile devices

### Applied To

This setting applies to:

- ✅ Main channel messages (slim summaries)
- ✅ Thread reply messages (detailed stories)
- ✅ Error messages
- ✅ All messages sent via `Slack_API_Client::post_message()`

### User Control

If users want to see a preview:

1. Click the source link in the message
2. Slack will show a hover preview
3. Click again to open in browser

The information is still accessible, just not automatically expanded.

## Testing

After this change:

1. Run `/trending-news` command
2. Check main channel message - should see only text/links, no preview cards
3. Check thread messages - should see only text/links, no preview cards
4. Links should still be clickable
5. Hover over links should still show preview popup

## Alternative Approaches Considered

### Per-Link Control

Could use `<url|text>` format with manual unfurling, but:

- More complex to implement
- Inconsistent behavior
- Not worth the complexity for this use case

### User Preference

Could make it a setting, but:

- Adds configuration complexity
- Most users prefer less clutter
- Can always click links if they want preview

### Keep Unfurling

Keeping it enabled would:

- ❌ Make messages 3-5x longer
- ❌ Inconsistent preview quality across sites
- ❌ Slow down scanning
- ❌ Poor mobile experience

## Impact

This change makes the Slack integration:

- ✅ **Cleaner** - No large preview cards
- ✅ **Faster to scan** - See more at once
- ✅ **More predictable** - Consistent formatting
- ✅ **Mobile-friendly** - Less scrolling
- ✅ **Thread-focused** - Encourages clicking into threads for details

## Related Settings

This is different from:

- **Thread condensing** - Putting details in thread replies (already implemented)
- **Text cleaning** - Removing literal `\n` characters (already implemented)
- **Threading** - Using thread_ts for replies (already implemented)

All of these work together to create a clean, organized Slack experience.
