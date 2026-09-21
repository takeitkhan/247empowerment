# Complete Post Creation Troubleshooting Guide

## What Has Been Fixed

✅ **PHP Handler** (`more_functions/profile.php`):
- Added comprehensive error logging to track every step
- Checks nonce properly with error messages
- Validates user is logged in
- Validates post content exists
- Handles both instant and scheduled posts
- Sets correct post_author
- Saves privacy meta data
- Handles image uploads

✅ **JavaScript Handler** (`modal-handler.js`):
- Form submit event listener properly attached
- Form data correctly collected via FormData API
- Validates content and schedule requirements
- Sends all required fields to PHP:
  - action: "create_post"
  - post_content: textarea value
  - post_privacy: selected radio button
  - post_status_type: instant or scheduled
  - schedule_timestamp: calculated timestamp (if scheduled)
  - create_post_nonce: nonce token
  - post_image: file (if uploaded)
- Comprehensive console logging for debugging

✅ **HTML Template** (`create-post-redesigned-v2.php`):
- Form structure is correct (opens at line 59, closes at line 332)
- All hidden fields inside form
- Textarea with correct name="post_content"
- Privacy selector with name="post_privacy"
- Nonce field with correct action
- Submit button with type="submit"

---

## How to Test - Step by Step

### PART 1: Verify Browser Setup
```javascript
// Open browser console (F12 or Cmd+Option+J)
// Paste this code:

// 1. Check jQuery
jQuery
// Should return: jQuery(...) function object

// 2. Check AJAX object
ajax_object
// Should return: Object { ajax_url: "http://..." }

// 3. Check form exists
$('#create-post-form-redesigned')
// Should return jQuery object with length: 1

// 4. Check modal handler loaded
// Should see in console: ✓ Modal Handler Script Loaded
// If NOT visible, modal-handler.js isn't loading!
```

### PART 2: Test Form Submission

```javascript
// 1. Open Modal
// Click "What's on your mind?" input

// 2. Type test content
// "This is a test post from 247empowerment"

// 3. Watch Browser Console
// Click "Share Now" button

// EXPECTED CONSOLE OUTPUT:
// === FORM SUBMIT EVENT TRIGGERED ===
// Form content length: 43
// Is scheduled: false
// Validation passed, proceeding with submission...
// === SUBMITTING FORM DATA ===
// action: create_post
// post_content: This is a test post from 247empowerment
// post_privacy: only_me
// create_post_nonce: [long nonce string]
// post_status_type: instant
// AJAX URL: http://site.local/wp-admin/admin-ajax.php
```

### PART 3: Check Server Response

If you get console logs from Part 2, check next log for response:

```javascript
// SUCCESS RESPONSE:
AJAX Success response: {
  success: true,
  data: {
    post_id: 123,
    privacy: "only_me",
    status: "publish",
    message: "Post created successfully"
  }
}
Form submission callback - success

// ERROR RESPONSE examples:
// "Invalid nonce" - nonce verification failed
// "You must be logged in" - user not authenticated
// "Content is required" - post_content empty
// "Post creation failed: [error]" - WordPress wp_insert_post error
```

### PART 4: Check WordPress Debug Log

Edit `/wp-config.php` and add near the bottom (before the "That's all" comment):

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Then check `/wp-content/debug.log` after submitting:

```
=== CREATE POST AJAX CALLED ===
POST data keys: action, post_content, post_privacy, create_post_nonce, post_status_type, schedule_timestamp
Post content length: 43 chars
Creating post for user ID: 1
POST CREATED - ID: 123
=== POST CREATION COMPLETE ===
```

---

## Troubleshooting Decision Tree

### ❌ Console shows: "✓ Modal Handler Script Loaded" is NOT visible

**PROBLEM**: JavaScript not loading
**SOLUTIONS**:
1. Hard refresh page (Cmd+Shift+R or Ctrl+Shift+F5)
2. Check Network tab in DevTools:
   - Look for modal-handler.js
   - Should show status 200
   - If 404: File path is wrong in functions.php
   - If missing: Check if script enqueue is active
3. Check browser console for any errors
4. Verify jQuery is loaded (type `jQuery` in console)

---

### ❌ Console stops at: "=== FORM SUBMIT EVENT TRIGGERED ===" then shows validation error

**PROBLEM**: Form validation failing
**SOLUTIONS**:
1. Check "Form content length: X"
   - If 0 or empty: Textarea #post-content not getting value
   - Solution: Verify textarea exists and has name="post_content"
2. Check "Is scheduled: X"
   - If true but no date/time filled: Fill schedule date and time
3. Reload page and try again

---

### ❌ Console shows form logs, but NO AJAX response

**PROBLEM**: AJAX request not reaching server
**SOLUTIONS**:
1. Check Network tab → XHR section
   - Look for admin-ajax.php request
   - Should show POST request
   - Should show Response tab with JSON
2. If NO request visible:
   - AJAX URL is wrong
   - Solution: Check that ajax_object is localized correctly
   - Run: `console.log(ajax_object.ajax_url || ajaxurl)`
   - Should show valid WordPress AJAX URL
3. If request shows error status (404, 500):
   - Check Response tab for error details
   - Check WordPress debug.log

---

### ❌ AJAX returns error: "Invalid nonce"

**PROBLEM**: Nonce verification failing
**SOLUTIONS**:
1. Verify nonce is in form:
   ```javascript
   $('input[name="create_post_nonce"]').val()
   // Should return: long string like "abc123xyz..."
   ```
2. Verify nonce action name matches:
   - PHP: `wp_create_nonce('create_post_action')`
   - Form: `value="<?php echo wp_create_nonce('create_post_action'); ?>"`
   - JS verify: Check action name is IDENTICAL
3. If names match but still fails:
   - Nonce might be expired (older than 12 hours)
   - Reload page to get fresh nonce
   - Try again

---

### ❌ AJAX returns error: "Post creation failed: ..."

**PROBLEM**: WordPress wp_insert_post failing
**SOLUTIONS**:
1. Check debug.log for detailed error message
2. Common errors:
   - **"Invalid post type"** - post type 'post' not registered (unlikely)
   - **"Invalid status"** - 'publish' not valid status (unlikely)
   - **Capability issue** - User can't create posts
     - Solution: Go to WordPress admin, ensure user is Editor or Author role
3. Verify post_author is set:
   - Run: `get_current_user_id()` in console
   - Should return a number > 0
   - If 0: User is not logged in

---

### ❌ AJAX succeeds (response shows post_id) but post not visible

**PROBLEM**: Post created but can't find it
**SOLUTIONS**:
1. Go to WordPress admin → Posts
2. Search for post by content
3. If found:
   - Check post status: Should be "Publish" or "Scheduled"
   - Check post author: Should be logged-in user
   - Check post privacy meta: Should be set correctly
4. If not found:
   - Check spam folder
   - Run WP Query in console:
     ```php
     // In WordPress dashboard console or via function:
     $posts = get_posts(['orderby' => 'date', 'order' => 'DESC', 'numberposts' => 5]);
     // Look for recent posts with your content
     ```

---

### ❌ credentials-library.js error in console

**PROBLEM**: Third-party plugin conflict
**SOLUTIONS**:
1. This error is NOT from our code
2. Check if posts are creating despite error:
   - Test with "Direct AJAX Test" below
   - If posts create: Error doesn't matter
3. If posts not creating:
   - Error might be blocking JavaScript execution
   - Disable plugins one by one to find culprit:
     - Deactivate all plugins except Kirki
     - Test posting
     - Re-enable plugins gradually
     - Post again after each re-enable
4. If found problem plugin:
   - Consider alternative plugin
   - Or contact plugin author about compatibility

---

## Nuclear Option: Direct AJAX Test

If everything above fails, bypass the form entirely:

```javascript
// Copy-paste in browser console while logged in

// Step 1: Get nonce from form
const nonce = $('input[name="create_post_nonce"]').val();
if (!nonce) {
  alert('ERROR: Could not find nonce in form. Modal may not be open.');
} else {
  console.log('Found nonce:', nonce.substring(0, 20) + '...');
  
  // Step 2: Send direct AJAX request
  $.post(
    ajaxurl || '/wp-admin/admin-ajax.php',
    {
      action: 'create_post',
      post_content: 'Direct AJAX test from console ' + new Date().toLocaleString(),
      post_privacy: 'only_me',
      post_status_type: 'instant',
      create_post_nonce: nonce
    },
    function(response) {
      console.log('AJAX Response:', response);
      if (response.success) {
        alert('✓ SUCCESS! Post created with ID: ' + response.data.post_id);
      } else {
        alert('✗ FAILED: ' + (response.data?.message || 'Unknown error'));
      }
    }
  );
}
```

**If this works**: Form submission logic is issue, check JavaScript
**If this fails**: Backend handler or nonce issue, check PHP logs

---

## Contact Info for Issues

When reporting an issue, include:
1. Console error messages (screenshot or copy-paste)
2. Network tab response (if AJAX visible)
3. WordPress debug.log entries
4. Browser and OS
5. WordPress version
6. What was being tested (instant post, schedule, with image, etc)

---

## Quick Verification Checklist

Before concluding posting is "broken":

- [ ] Browser console shows "✓ Modal Handler Script Loaded"
- [ ] Can open modal (click on input)
- [ ] Can type in textarea
- [ ] Form submits (button shows spinner)
- [ ] At least ONE of these appears in console:
  - [ ] "=== FORM SUBMIT EVENT TRIGGERED ===" (JavaScript is running)
  - [ ] Network tab shows admin-ajax.php POST request (AJAX reached server)
  - [ ] wordpress debug.log shows "=== CREATE POST AJAX CALLED ===" (PHP received request)
- [ ] If no posts visible after success:
  - [ ] Check WordPress admin → Posts for new posts
  - [ ] Filter by author (current user)
  - [ ] Check all post statuses (Publish, Draft, Scheduled)

If ANY of above are missing, that's where the problem is. Report which step fails.
