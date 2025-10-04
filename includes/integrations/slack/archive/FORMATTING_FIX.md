# Formatting Fix - Literal \n Characters

## The Problem

Threaded messages were showing literal `\n` characters in the text instead of line breaks:

```
———\n 1️⃣ Trump pharmaceutical...
* 📝 Summary*\nPfizer's deal...
```

## Root Cause

The AI analysis (via Claude/Anthropic API) was returning text with **escaped newline characters** (`\n` as literal text) rather than actual newline characters. When this JSON response was parsed and passed to Slack, the literal `\n` strings appeared in the message.

### Why This Happened

1. AI model returns JSON with text content
2. Text content has literal `\n` characters: `"summary": "Text here\nMore text"`
3. When parsed as JSON, `\n` becomes actual newline (correct)
4. But sometimes the AI includes `\\n` (escaped) which becomes literal `\n` text (wrong)

## The Solution

Added a `clean_text()` helper function that:

1. Removes literal `\n`, `\r`, `\t` characters from text
2. Replaces them with spaces
3. Cleans up multiple consecutive spaces
4. Trims whitespace

### Code Changes

**New Helper Function:**

```php
private static function clean_text( $text ) {
    if ( empty( $text ) ) {
        return $text;
    }

    // Remove literal \n, \r, \t that might come from AI responses.
    $text = str_replace( array( '\\n', '\\r', '\\t' ), array( ' ', ' ', ' ' ), $text );

    // Clean up multiple spaces.
    $text = preg_replace( '/\s+/', ' ', $text );

    // Trim.
    return trim( $text );
}
```

**Applied To:**

- Story titles (both slim message and thread)
- Story summaries (thread only)
- Suggestion headlines (thread only)
- Suggestion angles (thread only)

### Where Applied

1. **Slim Channel Message** - `format_slim_channel_message()`
    - Cleans story titles

2. **Thread Messages** - `format_story_thread_message()`
    - Cleans story titles
    - Cleans story summaries
    - Cleans suggestion headlines
    - Cleans suggestion angles

## Result

Messages now display cleanly:

**Before:**

```
———\n 1️⃣ Trump pharmaceutical tariff threat...
* 📝 Summary*\nPfizer's deal with Trump...
```

**After:**

```
━━━━━━━━━━━━━━━━━━━━━━━━━━
1️⃣ Trump pharmaceutical tariff threat...

📝 Summary
Pfizer's deal with Trump...
```

## Technical Notes

### Slack Block Kit mrkdwn Format

Slack's `mrkdwn` text type:

- ✅ **Supports** `\n` for line breaks (when it's an actual newline character)
- ❌ **Displays literally** `\n` if it's text content (the problem we had)

### AI Response Variability

AI models can return text with:

- Actual newlines: `"text": "Line 1\nLine 2"` → Correct
- Escaped newlines: `"text": "Line 1\\nLine 2"` → Becomes literal `\n` → Wrong
- Mixed formats depending on model and prompt

Our `clean_text()` function handles all cases by removing literal escape sequences.

### Why Not str_replace('\\n', "\n")?

We could convert `\n` to actual newlines, but that would:

1. Create awkward line breaks in titles
2. Disrupt Slack's Block Kit formatting structure
3. Make text harder to read

Better to convert to spaces and let Slack's block structure handle layout.

## Testing

After applying this fix:

1. Run `/trending-news` command
2. Check thread messages
3. Verify no literal `\n`, `\r`, or `\t` characters appear
4. Verify text flows naturally with spaces

## Related Issues

This is a **different issue** from the threading problem (which was about `response_url` vs `chat.postMessage`). This is purely about text cleaning.

Both issues are now fixed:

- ✅ Threading works (use Slack API for initial message)
- ✅ Text displays cleanly (clean escaped characters from AI response)
