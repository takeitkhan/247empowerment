# Debugging Form Submission Issue

## Steps to Debug:

1. **Open Browser Developer Console**
   - Mac: Cmd + Option + J
   - Windows: F12 → Console tab

2. **Clear any old logs**
   - Type: `console.clear()`

3. **Open the Post Modal**
   - Click the "What's on your mind?" input

4. **Write some test content**
   - Type something in the textarea

5. **Check Console Output**
   - Before clicking submit, check console is open
   - You should see:
     ```
     === FORM SUBMIT EVENT TRIGGERED ===
     Form content length: XX
     Is scheduled: false
     Validation passed, proceeding with submission...
     === SUBMITTING FORM DATA ===
     action: create_post
     post_content: [your text]
     post_privacy: only_me
     create_post_nonce: [long string]
     post_status_type: instant
     AJAX URL: http://yoursite.local/wp-admin/admin-ajax.php
     ```

6. **Click Submit Button**
   - Watch for success or error response in console

7. **Check Server Logs**
   - WordPress debug.log location (typically: /path/to/wp-content/debug.log)
   - Should see entries like:
     ```
     === CREATE POST AJAX CALLED ===
     POST data keys: action, post_content, post_privacy, ...
     Post content length: XX chars
     Privacy level: only_me
     Creating post for user ID: X
     POST CREATED - ID: XXX
     === POST CREATION COMPLETE ===
     ```

## Common Issues:

### Issue: "Form content length: 0"
- **Problem**: Textarea not getting value
- **Solution**: Check if #post-content textarea exists in HTML

### Issue: AJAX URL is undefined
- **Problem**: ajax_object not localized
- **Solution**: Check functions.php - must have wp_localize_script for ajax_object

### Issue: Nonce error in console
- **Problem**: Nonce not matching
- **Solution**: Check nonce is created with same 'create_post_action' key in both PHP and form

### Issue: "AJAX Error - Status: 400/403"
- **Problem**: Server rejecting request
- **Solution**: Check error message in console, verify POST data is being sent

### Issue: credentials-library.js error
- **Problem**: Third-party plugin conflict
- **Solution**: This can be ignored if it doesn't prevent form submission
- **To fix**: Disable problematic plugins one by one

## Expected Success Response:

```
AJAX Success response: Object
├─ success: true
├─ data: Object
│  ├─ post_id: 123
│  ├─ privacy: "only_me"
│  ├─ status: "publish"
│  └─ message: "Post created successfully"
└─ (response code 200)
```

Modal should close, page should reload, new post should appear.

## If Still Not Working:

1. **Check WordPress Error Logging**
   - Edit wp-config.php:
     ```php
     define('WP_DEBUG', true);
     define('WP_DEBUG_LOG', true);
     define('WP_DEBUG_DISPLAY', false);
     ```
   - Check wp-content/debug.log

2. **Test AJAX Endpoint Directly**
   - In console, test:
     ```javascript
     $.post(ajaxurl, {
         action: 'create_post',
         post_content: 'Test post',
         post_privacy: 'only_me',
         create_post_nonce: 'your-nonce-here'
     }, function(response) {
         console.log('Response:', response);
     });
     ```

3. **Verify Post Created**
   - Go to WordPress admin → Posts
   - Look for recent posts with your test content
   - Check post privacy meta field
