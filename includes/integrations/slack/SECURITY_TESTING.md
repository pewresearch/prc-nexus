# Security Fixes Testing Guide

## Quick Verification Tests

### 1. Constants Configuration Test

**Check:** Verify constants are properly configured

```bash
# In WordPress admin, check the settings page shows:
# ✅ Configured via constant: PRC_PLATFORM_SLACK_TOKEN
# ✅ Configured via constant: PRC_PLATFORM_SLACK_SIGNING_SECRET
# ✅ Configured via constant: PRC_PLATFORM_SLACK_WORKSPACE_ID
```

**Expected:** Admin UI shows status-only fields when constants are defined.

---

### 2. Workspace Validation Test

**Test:** Send request from unauthorized workspace

```php
// In test request, modify team_id to different workspace
$params['team_id'] = 'T99999999'; // Wrong workspace
```

**Expected:** Returns 403 error "Request from unauthorized workspace"

---

### 3. Input Validation Test

**Test:** Send malformed Slack IDs

```bash
# Test invalid user ID
/trending-news category:technology

# With intercepted request, change user_id to: "invalid123"
```

**Expected:** Returns "⚠️ Invalid user ID format."

---

### 4. Long Input Test

**Test:** Send overly long command text

```bash
# Create a command with > 1000 characters
/trending-news category:$(python -c 'print("a"*1001)')
```

**Expected:** Input is truncated to 1000 characters, command still processes

---

### 5. Nonce Protection Test

**Test:** Try to update settings without nonce

```bash
# In browser console on settings page:
fetch('/wp-admin/options.php', {
  method: 'POST',
  body: new FormData(document.querySelector('form'))
});
```

**Expected:** Settings are not saved, error message shown

---

### 6. JSON Depth Test

**Test:** Send deeply nested JSON payload

```php
// Create 513 levels of nesting (exceeds our limit of 512)
$deeply_nested = str_repeat('[', 513) . '1' . str_repeat(']', 513);
```

**Expected:** JSON parsing fails gracefully, returns error

---

## Integration Tests

### End-to-End Slack Command Test

```bash
# In Slack workspace
/trending-news category:technology articles:3

# Expected flow:
# 1. Request received
# 2. Signature verified ✓
# 3. Workspace validated ✓
# 4. Input validated ✓
# 5. Command text parsed (max 1000 chars) ✓
# 6. Job scheduled ✓
# 7. Analysis runs ✓
# 8. Results posted to Slack ✓
# 9. 1-second delay between messages ✓
```

---

## Security Verification Checklist

- [ ] Secrets are NOT in `wp_options` table
- [ ] Constants are used for bot token, signing secret, workspace ID
- [ ] Requests from other workspaces are rejected
- [ ] Malformed Slack IDs are rejected
- [ ] Response URLs from non-Slack domains are rejected
- [ ] Command text over 1000 chars is truncated
- [ ] JSON with depth > 512 fails gracefully
- [ ] Settings form requires valid nonce
- [ ] No sensitive data in error logs
- [ ] Admin UI shows constant status correctly
- [ ] 1-second delay between API calls is observed

---

## Monitoring

### What to Watch

1. **Error Logs:** Should not contain sensitive information
2. **Rate Limiting:** 1-second delays should prevent rate limit errors
3. **Failed Requests:** Track 403 errors for unauthorized workspace attempts
4. **Performance:** No significant performance degradation

### Metrics to Track

- Successful command executions
- Failed signature verifications
- Failed workspace validations
- Failed input validations
- JSON parsing errors
- Average response time
