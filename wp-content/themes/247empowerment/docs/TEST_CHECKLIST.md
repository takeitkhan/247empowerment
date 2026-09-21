# Complete Form Submission Test Checklist

## Step 1: Verify Script is Loading ✓
In browser console, you should see immediately:
```
✓ Modal Handler Script Loaded
✓ All modal components initialized
```

If NOT visible:
- [ ] Check if jQuery is loaded (type `jQuery` in console)
- [ ] Check Network tab - modal-handler.js should be loaded
- [ ] Check for JavaScript syntax errors in console

---

## Step 2: Open Modal and Write Content
1. Click "What's on your mind?" input
2. Modal opens
3. Type test content: "This is a test post"
4. Check console - should show character counter updates

---

## Step 3: Click "Share Now" Button
Watch console for logs in this order:

```
=== FORM SUBMIT EVENT TRIGGERED ===
Form content length: 19
Is scheduled: false
Validation passed, proceeding with submission...
=== SUBMITTING FORM DATA ===
action: create_post
post_content: This is a test post
post_privacy: only_me
create_post_nonce: [long nonce string]
post_status_type: instant
AJAX URL: http://yoursite.local/wp-admin/admin-ajax.php
```

If you see above ^ then JavaScript is working correctly.

---

## Step 4: Check Server Response
In console, should see one of:

### ✅ SUCCESS:
```
AJAX Success response: {success: true, data: {...}}
Form submission callback - success
```
Modal closes, page reloads, new post visible in feed.

### ❌ FAILURE:
```
AJAX Success response: {success: false, data: {message: "error text"}}
```
Alert shows error message. Check error text:
- "Invalid nonce" → nonce generation/verification issue
- "You must be logged in" → user not authenticated
- "Post content cannot be empty" → content not being sent
- "Post creation failed: [error]" → WordPress error

### ❌ NETWORK ERROR:
```
AJAX Error - Status: 404/500
AJAX Response: [HTML error page]
```
Common causes:
- 404: AJAX URL is wrong, admin-ajax.php not found
- 500: Server error, check WordPress debug.log

---

## Step 5: Check WordPress Debug Log
If post not created, check: `/wp-content/debug.log`

Should contain:
```
=== CREATE POST AJAX CALLED ===
POST data keys: action, post_content, post_privacy, ...
Post content length: 19 chars
Privacy level: only_me
Creating post for user ID: 1
Post args: {"post_type":"post","post_status":"publish",...}
POST CREATED - ID: 123
=== POST CREATION COMPLETE ===
```

If ERROR entries appear, note the exact error.

---

## Step 6: Verify Post in WordPress Admin
1. Go to WordPress admin → Posts
2. Look for "This is a test post" in the list
3. Click to edit:
   - Check post content is correct
   - Check post_privacy post meta field is "only_me"
   - Check post author is correct user
   - Check post status is "publish" (or "scheduled" if tested schedule)

---

## Troubleshooting Quick Links

| Symptom | Likely Cause | Solution |
|---------|-------------|----------|
| No console logs | Script not loading | Check Network tab, reload page |
| Console logs stop at "FORM SUBMIT" | Form validation failing | Check if content field exists and has text |
| "Invalid nonce" error | Nonce mismatch | Verify nonce action name is 'create_post_action' in both PHP and JS |
| AJAX Success but no post | Handler issue | Check WordPress debug.log for error messages |
| 404 error on AJAX | Wrong AJAX URL | Verify ajax_object is localized in functions.php |
| Post created but data wrong | Sanitization issue | Check what fields are being saved |

---

## Nuclear Option: Direct AJAX Test

If everything above fails, test directly in console:

```javascript
// First, get a nonce from the form
const nonce = $('input[name="create_post_nonce"]').val();
console.log('Nonce found:', nonce);

// Then send direct AJAX request
$.post(ajaxurl || '/wp-admin/admin-ajax.php', {
    action: 'create_post',
    post_content: 'Direct test from console',
    post_privacy: 'only_me',
    post_status_type: 'instant',
    create_post_nonce: nonce
}, function(response) {
    console.log('Direct AJAX Response:', response);
});
```

This bypasses the form entirely and tests if the backend handler works.

---

## After Successful Test

Once posting works:
1. Test with image upload
2. Test with scheduled post (enable toggle, set future date/time)
3. Test all privacy levels (only_me, referral_partners, public)
4. Test with longer text and emojis
5. Test on mobile device for responsiveness
