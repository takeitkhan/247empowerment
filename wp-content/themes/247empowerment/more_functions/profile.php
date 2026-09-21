<?php
// ============================================
// AJAX HANDLER: Delete post via AJAX
// ============================================
add_action('wp_ajax_delete_user_post', 'ajax_delete_user_post');
add_action('wp_ajax_nopriv_delete_user_post', 'ajax_delete_user_post'); // Allow non-admin too

function ajax_delete_user_post() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'You must be logged in']);
    }
    
    $post_id = intval($_POST['post_id'] ?? 0);
    $nonce = sanitize_text_field($_POST['nonce'] ?? '');
    
    error_log('═══════════════════════════════════════════════════');
    error_log('🗑️  AJAX DELETE POST HANDLER');
    error_log('Post ID: ' . $post_id);
    error_log('User ID: ' . get_current_user_id());
    error_log('Nonce: ' . $nonce);
    
    if (!$post_id) {
        error_log('❌ No post ID');
        wp_send_json_error(['message' => 'No post ID provided']);
    }
    
    if (!wp_verify_nonce($nonce, 'delete_post_nonce_' . $post_id)) {
        error_log('❌ Nonce invalid');
        wp_send_json_error(['message' => 'Nonce verification failed']);
    }
    
    $post = get_post($post_id);
    if (!$post) {
        error_log('❌ Post not found');
        wp_send_json_error(['message' => 'Post not found']);
    }
    
    $current_user_id = get_current_user_id();
    $post_author_id = (int) $post->post_author;
    
    if ($current_user_id !== $post_author_id) {
        error_log('❌ User not author. Current: ' . $current_user_id . ', Author: ' . $post_author_id);
        wp_send_json_error(['message' => 'You cannot delete this post']);
    }
    
    if (!current_user_can('delete_posts')) {
        error_log('❌ No capability');
        $user = wp_get_current_user();
        error_log('User caps: ' . json_encode($user->caps));
        wp_send_json_error(['message' => 'Permission denied']);
    }
    
    error_log('✓ All checks passed, deleting post ' . $post_id);
    
    $deleted = wp_delete_post($post_id, true);
    
    if (!$deleted) {
        error_log('❌ wp_delete_post failed');
        wp_send_json_error(['message' => 'Failed to delete post']);
    }
    
    error_log('✅ Post deleted successfully');
    error_log('═══════════════════════════════════════════════════');
    
    wp_send_json_success(['message' => 'Post deleted successfully']);
    die();  // ✅ Ensure proper exit
}

// ✅ AUTO-SAVE AJAX HANDLER - Save individual field to database
add_action('wp_ajax_save_profile_field', 'save_profile_field_ajax');
function save_profile_field_ajax() {
    error_log('🔍 AJAX called: save_profile_field');
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        error_log('❌ User not logged in');
        wp_send_json_error(['message' => 'Unauthorized']);
    }
    
    // Verify nonce
    $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
    error_log('📋 Nonce: ' . $nonce);
    
    if (!wp_verify_nonce($nonce, 'frontend_profile_update')) {
        error_log('❌ Nonce verification failed');
        wp_send_json_error(['message' => 'Security check failed']);
    }
    
    $user_id = get_current_user_id();
    $field_name = isset($_POST['field_name']) ? sanitize_text_field($_POST['field_name']) : '';
    $field_value = isset($_POST['field_value']) ? $_POST['field_value'] : '';
    
    error_log('💾 Saving - User: ' . $user_id . ', Field: ' . $field_name . ', Value: ' . substr($field_value, 0, 50));
    
    // Sanitize based on field type
    switch($field_name) {
        case 'email':
            $field_value = sanitize_email($field_value);
            wp_update_user([
                'ID' => $user_id,
                'user_email' => $field_value
            ]);
            error_log('✅ Email updated');
            break;
        case 'first_name':
        case 'last_name':
        case 'phone':
        case 'place_display_name':
        case 'dob':
        case 'about_me_short':
            $field_value = sanitize_text_field($field_value);
            
            // Validate phone number if it's phone field
            if ($field_name === 'phone') {
                if (empty($field_value)) {
                    error_log('❌ Phone number cannot be empty');
                    wp_send_json_error(['message' => 'Phone number is required']);
                }
                // Validate phone format (at least 7 digits)
                $phone_digits = preg_replace('/\D/', '', $field_value);
                if (strlen($phone_digits) < 7) {
                    error_log('❌ Invalid phone format');
                    wp_send_json_error(['message' => 'Please enter a valid phone number (at least 7 digits)']);
                }
            }
            
            if ($field_name === 'first_name' || $field_name === 'last_name') {
                wp_update_user([
                    'ID' => $user_id,
                    $field_name => $field_value
                ]);
                error_log('✅ User field updated: ' . $field_name);
            } else {
                update_user_meta($user_id, $field_name, $field_value);
                error_log('✅ Meta field updated: ' . $field_name);
            }
            break;
        case 'about_me':
            $field_value = sanitize_textarea_field($field_value);
            update_user_meta($user_id, 'about_me', $field_value);
            error_log('✅ About me updated');
            break;
        case 'show_email':
        case 'show_phone':
        case 'show_dob':
        case 'show_full_address':
            $field_value = $field_value === '1' ? '1' : '0';
            update_user_meta($user_id, $field_name, $field_value);
            error_log('✅ Checkbox updated: ' . $field_name);
            break;
        default:
            error_log('❌ Unknown field: ' . $field_name);
            wp_send_json_error(['message' => 'Unknown field']);
            break;
    }
    
    error_log('✅ All done - sending success');
    wp_send_json_success(['message' => 'Field saved successfully']);
}

add_action('wp_enqueue_scripts', function () {
    if (is_page_template('modify-profile.php')) {
        wp_enqueue_media();  // Enqueue WordPress media
        wp_enqueue_script('jquery');  // Ensure jQuery is loaded
        wp_enqueue_script(
            'modify-profile-js', // Handle
            get_template_directory_uri() . '/assets/js/modify-profile.js', // Path to JS file
            ['jquery'], // Dependencies: Make sure jQuery is loaded before your script
            null, // Version
            true // Load in footer
        );
        
        // Localize AJAX URL
        wp_localize_script('modify-profile-js', 'ajaxurl', array('url' => admin_url('admin-ajax.php')));
    }
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('dashicons');
});

add_action('wp_ajax_upload_cover_photo', 'handle_cover_photo_upload');
function handle_cover_photo_upload()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }

    if (!function_exists('wp_handle_upload')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    if (empty($_FILES['cover_photo'])) {
        wp_send_json_error(['message' => 'No file uploaded']);
    }

    $uploadedfile = $_FILES['cover_photo'];
    $upload_overrides = ['test_form' => false];

    $movefile = wp_handle_upload($uploadedfile, $upload_overrides);

    if ($movefile && !isset($movefile['error'])) {
        update_user_meta(get_current_user_id(), 'cover_photo', esc_url_raw($movefile['url']));
        wp_send_json_success(['url' => esc_url($movefile['url'])]);
    } else {
        $error = isset($movefile['error']) ? $movefile['error'] : 'Unknown error';
        wp_send_json_error(['message' => $error]);
    }
}

add_action('wp_ajax_upload_profile_photo', 'handle_profile_photo_upload');
function handle_profile_photo_upload()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }

    if (!function_exists('wp_handle_upload')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    if (empty($_FILES['profile_photo'])) {
        wp_send_json_error(['message' => 'No file uploaded']);
    }

    $uploadedfile = $_FILES['profile_photo'];
    $upload_overrides = ['test_form' => false];

    $movefile = wp_handle_upload($uploadedfile, $upload_overrides);

    if ($movefile && !isset($movefile['error'])) {
        update_user_meta(get_current_user_id(), 'profile_photo', esc_url_raw($movefile['url']));
        wp_send_json_success(['url' => esc_url($movefile['url'])]);
    } else {
        $error = isset($movefile['error']) ? $movefile['error'] : 'Unknown error';
        wp_send_json_error(['message' => $error]);
    }
}

function enqueue_post_create_script()
{
    wp_enqueue_style(
        'sweetalert2',
        'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
        [],
        '11.10.5'
    );

    wp_enqueue_script(
        'sweetalert2',
        'https://cdn.jsdelivr.net/npm/sweetalert2@11',
        [],
        '11.10.5',
        true
    );

    // ❌ REMOVED: post-create-js was causing duplicate form submissions
    // The new modal-handler-js handles the redesigned form exclusively
    /*
    wp_enqueue_script(
        'post-create-js',
        get_template_directory_uri() . '/assets/js/post-create.js',
        array('jquery', 'sweetalert2'),
        null,
        true
    );
    */

    // Enqueue modal handler for redesigned v2 form
    wp_enqueue_script(
        'modal-handler-js',
        get_template_directory_uri() . '/template-custom/auth/profile-parts/modal-handler.js',
        array('jquery'),
        null,
        true
    );

    // ❌ REMOVED: post-create-js localize (no longer used)
    /*
    wp_localize_script('post-create-js', 'ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('post_comment_nonce'),
        'reaction_nonce' => wp_create_nonce('post_reaction_nonce')
    ));
    */

    // Localize for modal handler
    wp_localize_script('modal-handler-js', 'ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));

    // Enqueue lightweight reactions handler (separate from post-create to avoid duplicate form submissions)
    wp_enqueue_script(
        'reactions-js',
        get_template_directory_uri() . '/assets/js/reactions.js',
        array('jquery'),
        null,
        true
    );

    wp_localize_script('reactions-js', 'ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('post_comment_nonce'),
        'reaction_nonce' => wp_create_nonce('post_reaction_nonce')
    ));

    // Enqueue comment handlers separately (extracted from post-create.js)
    wp_enqueue_script(
        'comments-js',
        get_template_directory_uri() . '/assets/js/comments.js',
        array('jquery', 'sweetalert2'),
        null,
        true
    );

    // Ensure ajax_object exists for comments too (this will overwrite previous ajax_object if printed later, which is fine)
    wp_localize_script('comments-js', 'ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('post_comment_nonce'),
        'reaction_nonce' => wp_create_nonce('post_reaction_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'enqueue_post_create_script');

function enqueue_profile_map_script()
{
    // ✅ Map functionality disabled - no longer needed
    return;
    
    /*
    if (is_page_template('template-custom/auth/modify-profile.php')) {
        wp_enqueue_script(
            'profile-map',
            get_template_directory_uri() . '/assets/js/profile-map.js',
            [],
            null,
            true
        );
        wp_enqueue_script(
            'google-maps',
            'https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&libraries=places&callback=initProfileMap',
            [],
            null,
            true
        );
    }
    */
}
add_action('wp_enqueue_scripts', 'enqueue_profile_map_script');

add_action('wp_ajax_create_post', 'handle_create_post');
function handle_create_post()
{
    // Debug logging - DETAILED REQUEST DATA
    error_log('╔════════════════════════════════════════════════════════╗');
    error_log('║          CREATE POST AJAX HANDLER CALLED               ║');
    error_log('╚════════════════════════════════════════════════════════╝');
    
    error_log('=== REQUEST DETAILS ===');
    error_log('Request method: ' . $_SERVER['REQUEST_METHOD']);
    error_log('Content-Type: ' . ($_SERVER['CONTENT_TYPE'] ?? 'Not set'));
    error_log('User ID: ' . get_current_user_id());
    
    error_log('=== POST DATA ===');
    error_log('POST data received: ' . (empty($_POST) ? 'EMPTY' : implode(', ', array_keys($_POST))));
    
    if (isset($_POST['action'])) {
        error_log('Action: ' . $_POST['action']);
    }
    if (isset($_POST['post_content'])) {
        error_log('Post content length: ' . strlen($_POST['post_content']) . ' chars');
        error_log('Post content (first 100 chars): ' . substr($_POST['post_content'], 0, 100));
    }
    if (isset($_POST['post_privacy'])) {
        error_log('Privacy: ' . $_POST['post_privacy']);
    }
    if (isset($_POST['post_status_type'])) {
        error_log('Status type: ' . $_POST['post_status_type']);
    }
    if (isset($_POST['create_post_nonce'])) {
        error_log('Nonce present: Yes (length: ' . strlen($_POST['create_post_nonce']) . ')');
    } else {
        error_log('Nonce present: NO');
    }
    
    error_log('=== FILES ===');
    error_log('Files received: ' . (empty($_FILES) ? 'NONE' : implode(', ', array_keys($_FILES))));
    
    // Check nonce
    if (!isset($_POST['create_post_nonce'])) {
        error_log('❌ ERROR: Nonce not in POST');
        wp_send_json_error(array('message' => 'Nonce missing'), 403);
    }

    if (!wp_verify_nonce($_POST['create_post_nonce'], 'create_post_action')) {
        error_log('❌ ERROR: Nonce verification failed');
        wp_send_json_error(array('message' => 'Invalid nonce'), 403);
    }

    // Check user logged in
    if (!is_user_logged_in()) {
        error_log('❌ ERROR: User not logged in');
        wp_send_json_error(array('message' => 'You must be logged in'), 403);
    }

    // Get post content
    if (!isset($_POST['post_content'])) {
        error_log('❌ ERROR: post_content not in POST');
        wp_send_json_error(array('message' => 'Content is required'), 400);
    }

    $post_content = sanitize_textarea_field($_POST['post_content']);

    if (empty(trim($post_content))) {
        error_log('❌ ERROR: post_content is empty after sanitization');
        wp_send_json_error(array('message' => 'Post content cannot be empty'), 400);
    }

    error_log('Post content length: ' . strlen($post_content) . ' chars');

    // Sanitize privacy option
    $privacy_options = array('only_me', 'referral_partners', 'public');
    $raw_privacy = $_POST['post_privacy'] ?? '';
    error_log('Raw post_privacy value: "' . $raw_privacy . '"');
    $post_privacy = in_array($raw_privacy, $privacy_options) ? $raw_privacy : 'only_me';
    error_log('✓ Privacy level set to: ' . $post_privacy);

    // Check if this is a scheduled post
    $post_status = 'publish';
    $post_date = current_time('mysql');
    
    if (isset($_POST['post_status_type']) && $_POST['post_status_type'] === 'scheduled') {
        if (isset($_POST['schedule_timestamp'])) {
            $schedule_timestamp = intval($_POST['schedule_timestamp']);
            if ($schedule_timestamp > 0) {
                $post_status = 'future';
                // Convert Unix timestamp to WordPress blog time (respects blog timezone)
                $post_date = wp_date('Y-m-d H:i:s', $schedule_timestamp);
                error_log('✓ Scheduled post for: ' . $post_date . ' (timestamp: ' . $schedule_timestamp . ')');
            }
        }
    } else {
        error_log('✓ Instant post (status: publish)');
    }

    // Get current user ID
    $current_user_id = get_current_user_id();
    error_log('✓ Creating post for user ID: ' . $current_user_id);

    // ✅ NEW: Get wall owner ID (which profile the post is being posted on)
    $wall_owner_id = isset($_POST['wall_owner_id']) ? intval($_POST['wall_owner_id']) : $current_user_id;
    $is_posting_on_friend_wall = ($wall_owner_id !== $current_user_id);
    
    if ($is_posting_on_friend_wall) {
        error_log('✓ Post will be posted on wall of user ID: ' . $wall_owner_id);
        
        // ✅ NEW: Validate permission - user must be a referral partner (friend) of wall owner
        $wall_owner_profile = new UserProfileData($wall_owner_id);
        $referral_partners = $wall_owner_profile->getReferredUsers();
        
        $is_partner_of_wall_owner = false;
        foreach ($referral_partners as $partner) {
            if ($partner->ID === $current_user_id) {
                $is_partner_of_wall_owner = true;
                break;
            }
        }
        
        if (!$is_partner_of_wall_owner) {
            error_log('❌ ERROR: User ' . $current_user_id . ' is not a friend of wall owner ' . $wall_owner_id);
            wp_send_json_error(array('message' => 'You do not have permission to post on this profile'), 403);
        }
        
        error_log('✓ Permission verified: User is a friend of the wall owner');
    } else {
        error_log('✓ Post will be posted on own wall');
    }

    // Create post
    $post_args = array(
        'post_type'    => 'post',
        'post_status'  => $post_status,
        'post_content' => $post_content,
        'post_title'   => wp_trim_words($post_content, 10, '...'),
        'post_date'    => $post_date,
        'post_author'  => $current_user_id
    );

    error_log('✓ Post args: ' . json_encode($post_args));

    $post_id = wp_insert_post($post_args);

    if (is_wp_error($post_id)) {
        $error_msg = $post_id->get_error_message();
        error_log('❌ ERROR in wp_insert_post: ' . $error_msg);
        wp_send_json_error(array('message' => 'Post creation failed: ' . $error_msg));
    }

    error_log('✓ POST CREATED - ID: ' . $post_id);

    // Save privacy as post meta
    update_post_meta($post_id, '_post_privacy', $post_privacy);
    
    // ✅ NEW: Save wall owner ID as post meta (for posts on friend walls)
    if ($is_posting_on_friend_wall) {
        update_post_meta($post_id, '_post_wall_owner_id', $wall_owner_id);
        error_log('✓ Wall owner ID saved: ' . $wall_owner_id);
    }

    // Image handling
    if (!empty($_FILES['post_image']['name'])) {
        error_log('✓ Image file uploaded: ' . $_FILES['post_image']['name']);
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $attachment_id = media_handle_upload('post_image', $post_id);
        if (!is_wp_error($attachment_id)) {
            set_post_thumbnail($post_id, $attachment_id);
            error_log('✓ Image attached - ID: ' . $attachment_id);
        } else {
            error_log('⚠ Image upload error: ' . $attachment_id->get_error_message());
        }
    }

    error_log('╔════════════════════════════════════════════════════════╗');
    error_log('║          POST CREATION COMPLETE (ID: ' . $post_id . ')' . str_repeat(' ', 18 - strlen($post_id)) . '║');
    error_log('╚════════════════════════════════════════════════════════╝');

    // ============================================
    // SOCIAL MEDIA SHARING (via MM Social Poster plugin)
    // ============================================

    $social_shares = array();
    $share_to_linkedin = isset($_POST['share_to_linkedin']) && $_POST['share_to_linkedin'] === '1';

    error_log('[Profile.php] Share attempt — share_to_linkedin=' . ($share_to_linkedin ? 'yes' : 'no') . ', post_status=' . $post_status . ', privacy=' . $post_privacy);

    // LinkedIn sharing is only offered for immediate, public posts.
    if ($share_to_linkedin && $post_status === 'publish' && $post_privacy === 'public') {
        if (function_exists('mm_social_poster_publish')) {
            error_log('[Profile.php] Calling mm_social_poster_publish() for post ' . $post_id . ', user ' . $current_user_id);
            $social_shares = mm_social_poster_publish($post_id, $current_user_id, array('linkedin'));
            error_log('[Profile.php] LinkedIn share result: ' . wp_json_encode($social_shares));
        } else {
            error_log('[Profile.php] ⚠ MM Social Poster plugin not active; LinkedIn share skipped');
            $social_shares['linkedin'] = array(
                'status' => 'failed',
                'code'   => 'plugin_inactive',
                'error'  => 'Social posting is not available right now.',
            );
        }
    } elseif ($share_to_linkedin) {
        error_log('[Profile.php] Share checkbox was checked but conditions not met: post_status=' . $post_status . ', privacy=' . $post_privacy);
    }

    wp_send_json_success(array(
        'post_id' => $post_id,
        'privacy' => $post_privacy,
        'status' => $post_status,
        'message' => 'Post created successfully',
        'social_shares' => $social_shares
    ));
}

/**
 * Delete Scheduled Post AJAX Handler
 */
add_action('wp_ajax_delete_scheduled_post', 'handle_delete_scheduled_post');

function handle_delete_scheduled_post() {
    // Check user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'User not logged in'));
    }

    $current_user_id = get_current_user_id();
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

    if (!$post_id) {
        wp_send_json_error(array('message' => 'Invalid post ID'));
    }

    // Verify user is the post author
    $post = get_post($post_id);
    if (!$post || $post->post_author != $current_user_id) {
        wp_send_json_error(array('message' => 'You do not have permission to delete this post'));
    }

    // Verify post is scheduled (future status)
    if ($post->post_status !== 'future') {
        wp_send_json_error(array('message' => 'Only scheduled posts can be deleted this way'));
    }

    // Delete the post permanently (skip trash)
    $deleted = wp_delete_post($post_id, true);

    if ($deleted) {
        error_log('✓ Scheduled post deleted - ID: ' . $post_id . ' by user: ' . $current_user_id);
        wp_send_json_success(array(
            'message' => 'Scheduled post deleted successfully',
            'post_id' => $post_id
        ));
    } else {
        wp_send_json_error(array('message' => 'Failed to delete scheduled post'));
    }
}

// AJAX handler to get scheduled post data for editing
add_action('wp_ajax_get_scheduled_post_data', 'handle_get_scheduled_post_data');
function handle_get_scheduled_post_data()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Not authorized'], 403);
    }

    // Verify nonce for security
    if (!isset($_POST['edit_post_nonce']) || !wp_verify_nonce($_POST['edit_post_nonce'], 'edit_scheduled_post_action')) {
        wp_send_json_error(['message' => 'Invalid nonce'], 403);
    }

    $post_id = intval($_POST['post_id'] ?? 0);
    $current_user_id = get_current_user_id();
    
    if (!$post_id) {
        wp_send_json_error(['message' => 'Invalid post ID']);
    }

    // Get post
    $post = get_post($post_id);
    
    if (!$post) {
        wp_send_json_error(['message' => 'Post not found']);
    }
    
    // Check if user is the author
    if ($current_user_id !== (int)$post->post_author) {
        wp_send_json_error(['message' => 'You can only edit your own posts'], 403);
    }
    
    // Check if post is scheduled (future status)
    if ($post->post_status !== 'future') {
        wp_send_json_error(['message' => 'Can only edit scheduled posts']);
    }
    
    // Get privacy meta
    $privacy = get_post_meta($post_id, '_post_privacy', true) ?: 'only_me';
    
    // Return post data
    wp_send_json_success([
        'post_id' => $post_id,
        'post_title' => $post->post_title,
        'post_content' => $post->post_content,
        'post_privacy' => $privacy,
        'post_date' => $post->post_date,
        'post_date_gmt' => $post->post_date_gmt
    ]);
}

// AJAX handler to update scheduled post
add_action('wp_ajax_update_scheduled_post', 'handle_update_scheduled_post');
function handle_update_scheduled_post()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Not authorized'], 403);
    }

    // Verify nonce for security
    if (!isset($_POST['edit_post_nonce']) || !wp_verify_nonce($_POST['edit_post_nonce'], 'edit_scheduled_post_action')) {
        wp_send_json_error(['message' => 'Invalid nonce'], 403);
    }

    $post_id = intval($_POST['post_id'] ?? 0);
    $current_user_id = get_current_user_id();
    
    if (!$post_id) {
        wp_send_json_error(['message' => 'Invalid post ID']);
    }

    // Get post
    $post = get_post($post_id);
    
    if (!$post) {
        wp_send_json_error(['message' => 'Post not found']);
    }
    
    // Check if user is the author
    if ($current_user_id !== (int)$post->post_author) {
        wp_send_json_error(['message' => 'You can only edit your own posts'], 403);
    }
    
    // Check if post is scheduled (future status)
    if ($post->post_status !== 'future') {
        wp_send_json_error(['message' => 'Can only edit scheduled posts']);
    }

    // Validate inputs
    $title = sanitize_text_field($_POST['post_title'] ?? '');
    $content = sanitize_textarea_field($_POST['post_content'] ?? '');
    $privacy = in_array($_POST['post_privacy'] ?? '', ['only_me', 'referral_partners', 'public']) 
        ? sanitize_text_field($_POST['post_privacy']) 
        : 'only_me';
    $schedule_timestamp = intval($_POST['schedule_timestamp'] ?? 0);

    if (!$content) {
        wp_send_json_error(['message' => 'Post content is required']);
    }

    if (!$schedule_timestamp) {
        wp_send_json_error(['message' => 'Invalid schedule timestamp']);
    }

    // Convert timestamp to WordPress date format
    $new_post_date = wp_date('Y-m-d H:i:s', $schedule_timestamp);

    // Prepare post data
    $post_data = [
        'ID' => $post_id,
        'post_title' => $title,
        'post_content' => $content,
        'post_date' => $new_post_date,
        'post_status' => 'future'
    ];

    // Update post
    $result = wp_update_post($post_data);
    
    if (is_wp_error($result)) {
        error_log('Failed to update post ' . $post_id . ': ' . $result->get_error_message());
        wp_send_json_error(['message' => 'Failed to update post']);
    }

    // Update privacy meta
    update_post_meta($post_id, '_post_privacy', $privacy);

    wp_send_json_success(['message' => 'Post updated successfully']);
}

// AJAX handler to update post privacy
add_action('wp_ajax_update_post_privacy', 'handle_update_post_privacy');
function handle_update_post_privacy()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Not authorized'], 403);
    }

    $post_id = intval($_POST['post_id'] ?? 0);
    $current_user_id = get_current_user_id();
    
    if (!$post_id) {
        wp_send_json_error(['message' => 'Invalid post ID']);
    }

    // Check if user is the author
    $post_author_id = get_post_field('post_author', $post_id);
    if ($current_user_id !== (int)$post_author_id) {
        wp_send_json_error(['message' => 'You can only edit your own posts'], 403);
    }

    // Sanitize privacy option
    $privacy_options = ['only_me', 'referral_partners', 'public'];
    $new_privacy = in_array($_POST['privacy'] ?? '', $privacy_options) ? $_POST['privacy'] : 'only_me';

    // Update post privacy meta
    update_post_meta($post_id, '_post_privacy', $new_privacy);

    wp_send_json_success([
        'message' => 'Privacy updated successfully',
        'privacy' => $new_privacy
    ]);
}

// ✅ NEW: AJAX handler to delete a post
add_action('wp_ajax_delete_post', 'handle_delete_post_ajax');
function handle_delete_post_ajax()
{
    // Check user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'User not logged in'), 403);
    }

    $current_user_id = get_current_user_id();
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

    if (!$post_id) {
        wp_send_json_error(array('message' => 'Invalid post ID'));
    }

    // Get the post
    $post = get_post($post_id);
    
    if (!$post) {
        wp_send_json_error(array('message' => 'Post not found'));
    }

    // Verify user is either the post author OR the wall owner
    $wall_owner_id = get_post_meta($post_id, '_post_wall_owner_id', true);
    $is_author = (int)$post->post_author === $current_user_id;
    $is_wall_owner = !empty($wall_owner_id) && (int)$wall_owner_id === $current_user_id;
    
    if (!$is_author && !$is_wall_owner) {
        error_log('❌ Delete permission denied: User ' . $current_user_id . ' (post author: ' . $post->post_author . ', wall owner: ' . $wall_owner_id . ') tried to delete post ' . $post_id);
        wp_send_json_error(array('message' => 'You do not have permission to delete this post'), 403);
    }

    error_log('✓ User ' . $current_user_id . ' authorized to delete post ' . $post_id . ' (is_author: ' . ($is_author ? 'yes' : 'no') . ', is_wall_owner: ' . ($is_wall_owner ? 'yes' : 'no') . ')');

    // Delete the post permanently (skip trash)
    $deleted = wp_delete_post($post_id, true);

    if ($deleted) {
        error_log('✓ Post deleted - ID: ' . $post_id . ' by user: ' . $current_user_id);
        wp_send_json_success(array(
            'message' => 'Post deleted successfully',
            'post_id' => $post_id
        ));
    } else {
        error_log('❌ Failed to delete post ' . $post_id);
        wp_send_json_error(array('message' => 'Failed to delete post'));
    }
}

// Add inside your theme's functions.php or a loaded plugin
add_action('admin_post_delete_custom_post', 'handle_delete_custom_post');
function handle_delete_custom_post()
{
    if (!is_user_logged_in()) {
        wp_die('Unauthorized');
    }

    $post_id = intval($_GET['post_id'] ?? 0);
    $nonce = $_GET['_wpnonce'] ?? '';
    $redirect_to = isset($_GET['redirect_to']) ? urldecode($_GET['redirect_to']) : '';

    error_log('═══════════════════════════════════════════════════');
    error_log('🗑️  DELETE POST HANDLER STARTED');
    error_log('Post ID: ' . $post_id);
    error_log('Redirect To: ' . $redirect_to);
    error_log('Current User ID: ' . get_current_user_id());
    
    // Set transient to verify handler was called
    set_transient('delete_handler_called_' . get_current_user_id(), time(), 60);
    set_transient('last_delete_post_id', $post_id, 60);
    
    // Validate post ID
    if (!$post_id) {
        error_log('❌ STOPPED: No post_id provided');
        wp_die('No post ID provided');
    }
    
    // Get post
    $post = get_post($post_id);
    if (!$post) {
        error_log('❌ STOPPED: Post not found - ID: ' . $post_id);
        wp_die('Post not found');
    }
    
    error_log('Post exists. Author: ' . $post->post_author . ', Type: ' . $post->post_type . ', Status: ' . $post->post_status);

    // Verify nonce
    $nonce_check = wp_verify_nonce($nonce, 'delete_post_' . $post_id);
    error_log('Nonce check result: ' . ($nonce_check ? 'VALID' : 'INVALID'));
    
    if (!$nonce_check) {
        error_log('❌ STOPPED: Nonce verification failed');
        wp_die('Invalid request - nonce failed');
    }

    // Check if user is the author
    $current_user_id = get_current_user_id();
    $post_author_id = (int) $post->post_author;
    
    if ($current_user_id !== $post_author_id) {
        error_log('❌ STOPPED: User not author. Current user: ' . $current_user_id . ', Post author: ' . $post_author_id);
        wp_die('You cannot delete this post.');
    }
    
    error_log('✓ User is post author');

    // Check capability
    if (!current_user_can('delete_posts')) {
        $user = wp_get_current_user();
        error_log('❌ STOPPED: No delete_posts capability. User caps: ' . json_encode($user->caps));
        wp_die('You do not have permission to delete this post.');
    }
    
    error_log('✓ User has delete_posts capability');

    // DELETE POST
    error_log('🔄 Calling wp_delete_post(' . $post_id . ', true)...');
    
    $deleted = wp_delete_post($post_id, true);
    
    error_log('wp_delete_post returned: ' . gettype($deleted));
    if ($deleted) {
        error_log('Delete returned POST object: ' . json_encode(['ID' => $deleted->ID, 'post_status' => $deleted->post_status]));
    } else {
        error_log('Delete returned: ' . var_export($deleted, true));
    }

    // Verify post is deleted
    $post_after = get_post($post_id);
    if ($post_after) {
        error_log('❌ FAILED: Post still exists! Status: ' . $post_after->post_status);
        error_log('═══════════════════════════════════════════════════');
        wp_die('Post could not be deleted. Status: ' . $post_after->post_status);
    }
    
    error_log('✅ SUCCESS: Post deleted and verified gone');
    error_log('═══════════════════════════════════════════════════');
    
    // Redirect
    if ($redirect_to && wp_http_validate_url($redirect_to)) {
        error_log('Redirecting to: ' . $redirect_to);
        wp_safe_redirect($redirect_to);
    } else {
        error_log('No valid redirect_to, redirecting to home');
        wp_safe_redirect(home_url());
    }
    exit;
}

function add_delete_posts_capability_to_role()
{
    // Add to subscriber role (default user role) so they can delete their own posts
    $subscriber_role = get_role('subscriber');
    if ($subscriber_role && !$subscriber_role->has_cap('delete_posts')) {
        $subscriber_role->add_cap('delete_posts');
    }
    
    // Also add to author role for completeness
    $author_role = get_role('author');
    if ($author_role && !$author_role->has_cap('delete_posts')) {
        $author_role->add_cap('delete_posts');
    }
}
add_action('init', 'add_delete_posts_capability_to_role');


function add_custom_referral_rewrite_rule()
{
    add_rewrite_rule('^u/([^/]+)/referrals/?$', 'index.php?referral_user=$matches[1]', 'top');
}
// add_action('init', 'add_custom_referral_rewrite_rule');

// ==========================================
// 1. QUERY VARS (ডুপ্লিকেট দূর করে একবার রাখা হলো)
// ==========================================
function add_custom_query_vars($vars)
{
    $vars[] = 'username';
    $vars[] = 'referral_user';
    $vars[] = 'user_profile';
    $vars[] = 'meetings_page';
    return $vars;
}
add_filter('query_vars', 'add_custom_query_vars');

// ==========================================
// 2. REWRITE RULES (সবগুলো রুল এক জায়গায়)
// ==========================================
function custom_user_and_feature_rewrite_rules()
{
    // কাস্টম পেজ বা স্লাগ বাদ দিয়ে ইউজারনেম ক্যাপচার করার জন্য
    $pages = get_pages(['post_status' => 'publish']);
    $reserved_slugs = array_map(function ($page) {
        return $page->post_name;
    }, $pages);

    // কিছু ডিফল্ট ওয়ার্ডপ্রেস স্লাগও রিজার্ভ রাখা ভালো
    $reserved_slugs[] = 'wp-admin';
    $reserved_slugs[] = 'wp-login.php';
    $reserved_slugs[] = 'wp-json';

    $reserved_pattern = implode('|', array_map('preg_quote', $reserved_slugs));

    // ১. ইউজার প্রোফাইল রুল (যেমন: /u/connectcollaborategrowsupport)
    add_rewrite_rule(
        '^u/((?!' . $reserved_pattern . ')[^/]+)/?$',
        'index.php?pagename=custom-user&username=$matches[1]',
        'top'
    );

    // ২. রেফারেল রুল (যেমন: /u/username/referrals)
    add_rewrite_rule('^u/([^/]+)/referrals/?$', 'index.php?referral_user=$matches[1]', 'top');

    // ৩. মিটিং রুল (যেমন: /u/username/meetings)
    add_rewrite_rule('^u/([^/]+)/meetings/?$', 'index.php?user_profile=$matches[1]&meetings_page=1', 'top');
}
add_action('init', 'custom_user_and_feature_rewrite_rules');

// Add custom rewrite rule
function custom_rewrite_rule()
{
    // Get all published page slugs
    $pages = get_pages(['post_status' => 'publish']);
    $reserved_slugs = array_map(function ($page) {
        return $page->post_name;
    }, $pages);

    // Escape each slug for regex
    $reserved_pattern = implode('|', array_map('preg_quote', $reserved_slugs));

    // Add namespaced rewrite rule excluding those slugs
    add_rewrite_rule(
        '^u/((?!' . $reserved_pattern . ')[^/]+)/?$',
        'index.php?pagename=custom-user&username=$matches[1]',
        'top'
    );
}
// add_action('init', 'custom_rewrite_rule');

function custom_meetings_rewrite_rule()
{
    add_rewrite_rule(
        '^u/([^/]+)/meetings/?$', // e.g. u/joseph/meetings
        'index.php?user_profile=$matches[1]&meetings_page=1',
        'top'
    );
}
// add_action('init', 'custom_meetings_rewrite_rule');


// মিটিং পেজ টেম্পলেট লোড করার ফিল্টার
function load_custom_meetings_template($template)
{
    if (get_query_var('meetings_page')) {
        $meeting_template = get_template_directory() . '/template-custom/auth/user-meetings.php';
        if (file_exists($meeting_template)) {
            return $meeting_template;
        }
    }
    return $template;
}
add_filter('template_include', 'load_custom_meetings_template');


// function load_custom_meetings_template($template)
// {
//     if (get_query_var('meetings_page')) {
//         return get_template_directory() . '/template-custom/auth/user-meetings.php';
//     }
//     return $template;
// }
// add_filter('template_include', 'load_custom_meetings_template');


function custom_meetings_query_vars($vars)
{
    $vars[] = 'user_profile';
    $vars[] = 'meetings_page';
    return $vars;
}
add_filter('query_vars', 'custom_meetings_query_vars');


// ==========================================
// 3. TEMPLATE REDIRECT & LOADING HANDLERS
// ==========================================

// কাস্টম ইউজার প্রোফাইল পেজ অথবা ইউজারনেম সঠিকভাবে হ্যান্ডেল করা
add_action('template_redirect', function () {
    $username = get_query_var('username');
    $referral_user = get_query_var('referral_user');

    // যদি ইউজারনেম কুয়েরি ভ্যারিয়েবলে থাকে
    if ($username) {
        // ডাটাবেস থেকে চেক করুন এই নামের কোনো ইউজার সত্যই আছে কিনা
        $user = mm_resolve_public_user($username);
        
        if (!$user) {
            // যদি ইউজার না থাকে, তবে ওয়ার্ডপ্রেসের নিজস্ব 404 ট্রিগার করুন
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            nocache_headers();
            return;
        }
        
        // ইউজার পাওয়া গেলে query var normalize করে দিন, template loading
        // functions.php এর template_include হ্যান্ডলার করবে
        set_query_var('user_profile', $user->user_nicename ?: $user->user_login);
    }

    // রেফারেল পেজ হ্যান্ডলার
    if ($referral_user && file_exists(get_template_directory() . '/template-custom/auth/referrals.php')) {
        include get_template_directory() . '/template-custom/auth/referrals.php';
        exit;
    }
});

// add_action('template_redirect', function () {
//     $referral_user = get_query_var('referral_user');

//     if ($referral_user && file_exists(get_template_directory() . '/template-custom/auth/referrals.php')) {
//         include get_template_directory() . '/template-custom/auth/referrals.php';
//         exit;
//     }
// });


// Load referral partners script
function load_more_referrals_callback()
{
    $user_id = isset($_GET['user']) ? intval($_GET['user']) : 0;
    $offset  = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    $limit   = 8;

    $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

    if (!$user_id) {
        wp_send_json_error('Invalid user ID');
    }

    $user = get_user_by('id', $user_id);
    if (!$user) {
        wp_send_json_error('User not found');
    }

    /* -------------------------------------------------
     * Fetch referrals (raw)
     * ------------------------------------------------- */
    $raw_referrals = UserProfileData::getReferredUsersBy($user);

    /* -------------------------------------------------
     * Normalize → profile arrays
     * ------------------------------------------------- */
    $profiles = [];

    foreach ($raw_referrals as $ref) {

        $ref_id = is_array($ref)
            ? ($ref['id'] ?? 0)
            : ($ref->ID ?? 0);

        if (!$ref_id) {
            continue;
        }
        // Build profile array...
        $profiles[] = array(
            'id' => $ref_id,
            'first_name' => '',
            'last_name' => '',
            'username' => ''
        );
    }

    if ($search) {
        $profiles = array_filter($profiles, function ($p) use ($search) {
            return str_contains(
                strtolower(
                    $p['first_name'] . ' ' .
                        $p['last_name'] . ' ' .
                        $p['username']
                ),
                strtolower($search)
            );
        });

        $profiles = array_values($profiles);
    }

    $sort = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'recent';

    usort($profiles, function ($a, $b) use ($sort) {
        switch ($sort) {
            case 'name_asc':
                return strcasecmp($a['first_name'] . $a['last_name'], $b['first_name'] . $b['last_name']);
            case 'name_desc':
                return strcasecmp($b['first_name'] . $b['last_name'], $a['first_name'] . $a['last_name']);
            case 'recent':
            default:
                return $b['id'] <=> $a['id'];
        }
    });


    /* -------------------------------------------------
     * Slice (pagination)
     * ------------------------------------------------- */
    $slice = array_slice($profiles, $offset, $limit);

    if (empty($slice)) {
        wp_send_json_success('');
    }

    /* -------------------------------------------------
     * Render HTML
     * ------------------------------------------------- */
    ob_start();

    foreach ($slice as $profile) {

        $photo = $profile['profile_photo']
            ?: 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($profile['email']))) . '?s=150&d=mm';
?>
        <div class="d-flex flex-column flex-lg-row justify-content-between py-3">
            <div class="d-flex align-items-center gap-3">
                <div class="img60">
                    <img src="<?= esc_url($photo); ?>"
                        class="w-100 h-100 object-fit-cover"
                        alt="<?= esc_attr($profile['first_name']); ?>">
                </div>

                <div class="post-user">
                    <a href="<?= esc_url($profile['profile_url']); ?>" class="fw-bold">
                        <?= esc_html($profile['first_name'] . ' ' . $profile['last_name']); ?>
                    </a>
                    <?php if ($profile['about_me_short']) { ?>
                        <span class="px-2">
                            <i class="far fa-bookmark"></i>
                            <?= esc_html($profile['about_me_short']); ?>
                        </span>
                    <?php } ?>

                    <?php if (!empty($profile['user_category_names'])) : ?>
                        <div class="fs14">
                            <i class="fas fa-briefcase"></i> <?= esc_html(implode(', ', $profile['user_category_names'])); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="dropdown">
                <button class="btn" data-bs-toggle="dropdown">
                    <i class="bi bi-three-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <button class="dropdown-item remove-partner-btn"
                            data-user-id="<?= esc_attr($profile['id']); ?>">
                            Remove Partner
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    <?php
    }

    wp_send_json_success(ob_get_clean());
}

add_action('wp_ajax_load_more_referrals', 'load_more_referrals_callback');
add_action('wp_ajax_nopriv_load_more_referrals', 'load_more_referrals_callback');


// Display referred_by field in user profile
function show_referred_by_field($user)
{
    $referred_by = get_user_meta($user->ID, 'referrer', true);
    ?>
    <h3>Referral Info</h3>
    <table class="form-table">
        <tr>
            <th><label for="referrer">Referred By (Username or User ID)</label></th>
            <td>
                <input type="text" name="referrer" id="referrer" value="<?php echo esc_attr($referred_by); ?>" class="regular-text" />
                <p class="description">Enter the username or user ID of the referrer.</p>
            </td>
        </tr>
    </table>
<?php
}
add_action('show_user_profile', 'show_referred_by_field');
add_action('edit_user_profile', 'show_referred_by_field');

// Save referred_by field
function save_referred_by_field($user_id)
{
    if (!current_user_can('edit_user', $user_id)) return;

    if (isset($_POST['referrer'])) {
        update_user_meta($user_id, 'referrer', sanitize_text_field($_POST['referrer']));
    }
}
add_action('personal_options_update', 'save_referred_by_field');
add_action('edit_user_profile_update', 'save_referred_by_field');

// Handle delete profile photo
add_action('wp_ajax_delete_profile_photo', function () {
    if (!is_user_logged_in()) wp_send_json_error('Not authorized');

    $user_id = get_current_user_id();
    delete_user_meta($user_id, 'profile_photo');

    wp_send_json_success('Profile photo deleted');
});


add_filter('get_avatar', 'mm_custom_user_avatar', 10, 5);


// Default Gravatar to Profile Photo in Dashboard > Users
function mm_custom_user_avatar($avatar, $id_or_email, $size, $default, $alt)
{
    $user = false;

    // Resolve user
    if (is_numeric($id_or_email)) {
        $user = get_user_by('id', (int) $id_or_email);
    } elseif (is_object($id_or_email)) {
        if (!empty($id_or_email->user_id)) {
            $user = get_user_by('id', (int) $id_or_email->user_id);
        }
    } elseif (is_string($id_or_email)) {
        $user = get_user_by('email', $id_or_email);
    }

    if (!$user) {
        return $avatar;
    }

    // Get custom profile photo
    $custom_avatar = get_user_meta($user->ID, 'profile_photo', true);

    if (!$custom_avatar) {
        return $avatar; // fallback to Gravatar
    }

    // Return custom avatar HTML
    return sprintf(
        '<img src="%s" alt="%s" width="%d" height="%d" class="custom-avatar avatar avatar-%d photo" />',
        esc_url($custom_avatar),
        esc_attr($alt),
        (int) $size,
        (int) $size,
        (int) $size
    );
}


if (!empty($_POST['user_categories_priority'])) {

    $selected_categories = array_map(
        'intval',
        $_POST['user_categories'] ?? []
    );

    $clean = [];

    foreach ($_POST['user_categories_priority'] as $term_id => $priority) {

        $term_id  = (int) $term_id;
        $priority = (int) $priority;

        // Only allow priority if category is selected
        if (
            in_array($term_id, $selected_categories, true) &&
            $priority >= 1 &&
            $priority <= 5
        ) {
            $clean[$term_id] = $priority;
        }
    }
    
    $user_id = isset($_GET['user']) ? intval($_GET['user']) : 0;

    // Ensure unique priorities (1st, 2nd, 3rd...)
    if (count($clean) === count(array_unique($clean))) {

        update_user_meta($user_id, 'user_categories_priority', $clean);

    } else {
        // Optional: error flag for UI
        update_user_meta($user_id, 'mm_spg_interest_error', 'duplicate_priority');
    }
}

// AJAX handler for reactions
add_action('wp_ajax_add_reaction', 'handle_add_reaction');
add_action('wp_ajax_nopriv_add_reaction', 'handle_add_reaction');
function handle_add_reaction()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Not authorized'], 403);
    }

    check_ajax_referer('post_reaction_nonce', 'nonce', false);

    $post_id = intval($_POST['post_id'] ?? 0);
    $reaction = sanitize_text_field($_POST['reaction'] ?? '');
    $user_id = get_current_user_id();

    if (!$post_id || empty($reaction)) {
        wp_send_json_error(['message' => 'Invalid post ID or reaction']);
    }

    // Allowed reactions
    $allowed_reactions = ['like', 'love', 'happy', 'wow', 'sad', 'angry'];
    if (!in_array($reaction, $allowed_reactions)) {
        wp_send_json_error(['message' => 'Invalid reaction type']);
    }

    // Get current reactions
    $reactions = get_post_meta($post_id, '_post_reactions', true);
    $reactions = is_array($reactions) ? $reactions : [];

    // Initialize all reaction types
    foreach ($allowed_reactions as $r) {
        if (!isset($reactions[$r])) {
            $reactions[$r] = [];
        }
    }

    // Check if user already has a reaction on this post
    $user_current_reaction = null;
    foreach ($allowed_reactions as $r) {
        $user_key = array_search($user_id, $reactions[$r]);
        if ($user_key !== false) {
            $user_current_reaction = $r;
            // Remove user from this reaction
            unset($reactions[$r][$user_key]);
            $reactions[$r] = array_values($reactions[$r]);
            break;
        }
    }

    // If user is clicking the same reaction, toggle it off
    if ($user_current_reaction === $reaction) {
        // Remove the reaction (already removed above)
    } else {
        // Add new reaction
        $reactions[$reaction][] = $user_id;
    }

    // Update post meta
    update_post_meta($post_id, '_post_reactions', $reactions);

    // Get reaction counts
    $reaction_counts = [];
    foreach ($allowed_reactions as $r) {
        $reaction_counts[$r] = isset($reactions[$r]) ? count($reactions[$r]) : 0;
    }

    wp_send_json_success([
        'message' => 'Reaction updated',
        'reactions' => $reaction_counts,
        'user_reaction' => get_user_reactions($post_id, $user_id)
    ]);
}

// Helper function to get user's reactions
function get_user_reactions($post_id, $user_id) {
    $reactions = get_post_meta($post_id, '_post_reactions', true);
    $reactions = is_array($reactions) ? $reactions : [];
    
    $user_reactions = [];
    foreach ($reactions as $reaction_type => $users) {
        if (is_array($users) && in_array($user_id, $users)) {
            $user_reactions[] = $reaction_type;
        }
    }
    return $user_reactions;
}

// Helper function to clean duplicate reactions (ensure one user has only one reaction)
function clean_user_reactions($post_id, $user_id) {
    $reactions = get_post_meta($post_id, '_post_reactions', true);
    $reactions = is_array($reactions) ? $reactions : [];
    
    $allowed_reactions = ['like', 'love', 'happy', 'wow', 'sad', 'angry'];
    $user_reaction_found = null;
    
    // Remove user from all reactions
    foreach ($allowed_reactions as $r) {
        if (isset($reactions[$r]) && is_array($reactions[$r])) {
            $user_key = array_search($user_id, $reactions[$r]);
            if ($user_key !== false) {
                if ($user_reaction_found === null) {
                    $user_reaction_found = $r; // Keep first found
                } else {
                    // Remove duplicate
                    unset($reactions[$r][$user_key]);
                    $reactions[$r] = array_values($reactions[$r]);
                }
            }
        }
    }
    
    if ($user_reaction_found !== null) {
        update_post_meta($post_id, '_post_reactions', $reactions);
    }
}

// Helper function to get reaction counts
function get_post_reaction_counts($post_id) {
    $allowed_reactions = ['like', 'love', 'happy', 'wow', 'sad', 'angry'];
    $reactions = get_post_meta($post_id, '_post_reactions', true);
    $reactions = is_array($reactions) ? $reactions : [];
    
    $counts = [];
    foreach ($allowed_reactions as $r) {
        $counts[$r] = isset($reactions[$r]) ? count($reactions[$r]) : 0;
    }
    return $counts;
}

// AJAX handler for comments
add_action('wp_ajax_add_post_comment', 'handle_add_post_comment');
function handle_add_post_comment()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Not authorized'], 403);
    }

    check_ajax_referer('post_comment_nonce', 'nonce');

    $post_id = intval($_POST['post_id'] ?? 0);
    $comment_text = sanitize_textarea_field($_POST['comment'] ?? '');
    $parent_id = intval($_POST['parent_id'] ?? 0);
    $user_id = get_current_user_id();

    if (!$post_id || empty($comment_text)) {
        wp_send_json_error(['message' => 'Post ID and comment text required']);
    }

    // Check if post exists and user can view it
    if (!UserProfileData::canViewPost($post_id, $user_id)) {
        wp_send_json_error(['message' => 'You do not have permission to comment on this post'], 403);
    }

    // Create comment
    $comment_data = [
        'comment_post_ID'      => $post_id,
        'comment_author_email' => wp_get_current_user()->user_email,
        'comment_author'       => wp_get_current_user()->display_name,
        'comment_content'      => $comment_text,
        'user_id'              => $user_id,
        'comment_approved'     => 1,
        'comment_parent'       => $parent_id
    ];

    $comment_id = wp_insert_comment($comment_data);

    if (is_wp_error($comment_id)) {
        wp_send_json_error(['message' => 'Failed to add comment']);
    }

    wp_send_json_success([
        'message' => 'Comment added successfully',
        'comment_id' => $comment_id
    ]);
}

// AJAX handler for deleting comments
add_action('wp_ajax_delete_post_comment', 'handle_delete_post_comment');
function handle_delete_post_comment()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Not authorized'], 403);
    }

    check_ajax_referer('post_comment_nonce', 'nonce');

    $comment_id = intval($_POST['comment_id'] ?? 0);
    if (!$comment_id) {
        wp_send_json_error(['message' => 'Invalid comment ID']);
    }

    $comment = get_comment($comment_id);
    if (!$comment) {
        wp_send_json_error(['message' => 'Comment not found']);
    }

    $current_user_id = get_current_user_id();
    $comment_author_id = (int) $comment->user_id;
    $post_author_id = (int) get_post_field('post_author', $comment->comment_post_ID);

    if ($current_user_id !== $comment_author_id && $current_user_id !== $post_author_id && !current_user_can('moderate_comments')) {
        wp_send_json_error(['message' => 'You do not have permission to delete this comment'], 403);
    }

    $deleted = wp_delete_comment($comment_id, true);
    if (!$deleted) {
        wp_send_json_error(['message' => 'Failed to delete comment']);
    }

    wp_send_json_success([
        'message' => 'Comment deleted successfully',
        'comment_id' => $comment_id
    ]);
}

// AJAX handler for updating comments
add_action('wp_ajax_update_post_comment', 'handle_update_post_comment');
function handle_update_post_comment()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Not authorized'], 403);
    }

    check_ajax_referer('post_comment_nonce', 'nonce');

    $comment_id = intval($_POST['comment_id'] ?? 0);
    $comment_text = sanitize_textarea_field($_POST['comment'] ?? '');

    if (!$comment_id || empty($comment_text)) {
        wp_send_json_error(['message' => 'Comment ID and text required']);
    }

    $comment = get_comment($comment_id);
    if (!$comment) {
        wp_send_json_error(['message' => 'Comment not found']);
    }

    $current_user_id = get_current_user_id();
    $comment_author_id = (int) $comment->user_id;
    $post_author_id = (int) get_post_field('post_author', $comment->comment_post_ID);

    if ($current_user_id !== $comment_author_id && $current_user_id !== $post_author_id && !current_user_can('moderate_comments')) {
        wp_send_json_error(['message' => 'You do not have permission to edit this comment'], 403);
    }

    $updated = wp_update_comment([
        'comment_ID' => $comment_id,
        'comment_content' => $comment_text,
    ]);

    if (!$updated) {
        wp_send_json_error(['message' => 'Failed to update comment']);
    }

    wp_send_json_success([
        'message' => 'Comment updated successfully',
        'comment_id' => $comment_id,
        'comment' => $comment_text
    ]);
}

// Get post comments
add_action('wp_ajax_load_post_comments', 'handle_load_post_comments');
add_action('wp_ajax_nopriv_load_post_comments', 'handle_load_post_comments');
function handle_load_post_comments()
{
    $post_id = intval($_POST['post_id'] ?? 0);
    
    if (!$post_id) {
        wp_send_json_error(['message' => 'Invalid post ID']);
    }

    $comments = get_comments([
        'post_id' => $post_id,
        'status'  => 'approve',
        'orderby' => 'comment_date',
        'order'   => 'ASC'
    ]);

    $comment_html = build_comment_tree($comments, 0);

    wp_send_json_success([
        'comments' => $comment_html,
        'count' => count($comments)
    ]);
}

// Build nested comment HTML with modern Facebook/Instagram style
function build_comment_tree($comments, $parent_id = 0, $depth = 0) {
    $html = '';
    
    foreach ($comments as $comment) {
        // Convert both to int for proper comparison
        if ((int)$comment->comment_parent !== (int)$parent_id) {
            continue;
        }
        
        // Get user profile data
        $user_profile = new UserProfileData($comment->user_id);
        $profile = $user_profile->getProfile();
        
        // Get name and photo
        $author_first_name = $profile['first_name'] ?? $comment->comment_author;
        $author_last_name = $profile['last_name'] ?? '';
        $author_name = trim($author_first_name . ' ' . $author_last_name);
        
        // Get profile photo with fallback
        $default_photo = get_template_directory_uri() . '/assets/img/loggedin_images/banner.jpg';
        $author_photo = (!empty($profile['profile_photo'])) ? $profile['profile_photo'] : $default_photo;
        
        // Get current user photo for reply input
        $current_user_photo = $default_photo;
        if (is_user_logged_in()) {
            $current_user_profile = new UserProfileData(get_current_user_id());
            $current_user_data = $current_user_profile->getProfile();
            $current_user_photo = (!empty($current_user_data['profile_photo'])) ? $current_user_data['profile_photo'] : $default_photo;
        }
        
        // Format time - improved format
        $comment_time = strtotime($comment->comment_date);
        $current_time = current_time('timestamp');
        $time_diff = $current_time - $comment_time;
        
        if ($time_diff < 60) {
            $time_ago = 'Just now';
        } elseif ($time_diff < 3600) {
            $minutes = floor($time_diff / 60);
            $time_ago = $minutes . 'min ago';
        } elseif ($time_diff < 86400) {
            $hours = floor($time_diff / 3600);
            $time_ago = $hours . 'h ago';
        } elseif ($time_diff < 604800) {
            $days = floor($time_diff / 86400);
            $time_ago = $days . 'd ago';
        } else {
            $weeks = floor($time_diff / 604800);
            $time_ago = $weeks . 'w ago';
        }
        
        // Calculate margin left for nested comments
        $margin_left = $depth > 0 ? ($depth * 45) : 0;
        
        // Main comment container with modern style
        $html .= '<div class="comment-item" data-comment-id="' . $comment->comment_ID . '" data-comment-content="' . esc_attr($comment->comment_content) . '" style="margin-left: ' . $margin_left . 'px; margin-bottom: 12px; padding: 0 8px;">';
        
        // Header: Profile photo + name + time + options
        $html .= '<div class="d-flex align-items-flex-start justify-content-between mb-1">';
        $html .= '<div class="d-flex align-items-center gap-2" style="flex: 1;">';
        
        // Profile photo (larger - 40px)
        $html .= '<div class="position-relative" style="width: 40px; height: 40px; flex-shrink: 0;">';
        $html .= '<img src="' . esc_url($author_photo) . '" alt="' . esc_attr($author_name) . '" class="rounded-circle w-100 h-100" style="object-fit: cover; border: 2px solid #f0f0f0;">';
        $html .= '</div>';
        
        // Name and time
        $html .= '<div style="flex: 1;">';
        $html .= '<strong class="text-dark" style="font-size: 14px; display: block; margin-bottom: 2px;">' . esc_html($author_name) . '</strong>';
        $html .= '<small class="text-muted" style="font-size: 12px;">' . $time_ago . '</small>';
        $html .= '</div>';
        $html .= '</div>';
        
        // Options menu (3-dot button)
        $html .= '<button class="p-0 text-muted btn btn-sm comment-options-btn" data-comment-id="' . $comment->comment_ID . '" style="font-size: 18px; border: none; background: none;">';
        $html .= '<i class="fas fa-ellipsis-h"></i>';
        $html .= '</button>';
        $html .= '</div>';
        
        // Comment content bubble
        $html .= '<div style="margin-left: 48px; margin-bottom: 6px;">';
        $html .= '<div class="comment-bubble" style="background-color: #f0f2f5; padding: 12px 14px; border-radius: 18px; word-wrap: break-word;">';
        $html .= '<p class="mb-0 text-dark" style="font-size: 14px; line-height: 1.5;">' . nl2br(esc_html($comment->comment_content)) . '</p>';
        $html .= '</div>';
        $html .= '</div>';
        
        // Action buttons: Like, Reply
        $html .= '<div style="margin-left: 48px; margin-bottom: 8px;">';
        $html .= '<div class="d-flex gap-3">';
        $html .= '<button class="p-0 text-muted btn btn-sm comment-like-btn" data-comment-id="' . $comment->comment_ID . '" style="font-size: 12px; border: none; background: none; cursor: pointer;">';
        $html .= '<i class="far fa-thumbs-up" style="margin-right: 4px;"></i>Like';
        $html .= '</button>';
        
        if ($depth < 2) { // Only show reply button for first 2 levels
            $html .= '<button class="p-0 text-muted btn btn-sm reply-btn" data-comment-id="' . $comment->comment_ID . '" data-author-name="' . esc_attr($author_name) . '" style="font-size: 12px; border: none; background: none; cursor: pointer;">';
            $html .= '<i class="fas fa-reply" style="margin-right: 4px;"></i>Reply';
            $html .= '</button>';
        }
        $html .= '</div>';
        $html .= '</div>';
        
        // Reply input (hidden by default)
        if ($depth < 2) {
            $html .= '<div class="reply-input-container" id="reply-container-' . $comment->comment_ID . '" style="display: none; margin-left: 48px; margin-bottom: 10px;">';
            
            $html .= '<div class="d-flex align-items-start gap-2">';
            $html .= '<div class="position-relative" style="width: 36px; height: 36px; flex-shrink: 0;">';
            $html .= '<img src="' . esc_url($current_user_photo) . '" alt="You" class="rounded-circle w-100 h-100" style="object-fit: cover;">';
            $html .= '</div>';
            
            $reply_nonce = wp_create_nonce('post_comment_nonce');
            $html .= '<div class="position-relative flex-grow-1">';
            $html .= '<input type="text" class="form-control comment-reply-input" data-parent-id="' . $comment->comment_ID . '" data-nonce="' . esc_attr($reply_nonce) . '" placeholder="Write a reply..." style="font-size: 14px; border-radius: 18px; padding: 10px 14px; border: 1px solid #e0e0e0; background-color: #f0f2f5;">';
            $html .= '<img class="position-absolute emoji-icon" src="' . get_template_directory_uri() . '/assets/img/emoji.svg" alt="Emoji" style="right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; width: 18px; height: 18px;">';
            $html .= '</div>';
            $html .= '</div>';
            
            $html .= '</div>';
        }
        
        // Recursively build child comments
        if ($depth < 2) {
            $html .= build_comment_tree($comments, $comment->comment_ID, $depth + 1);
        }
        
        $html .= '</div>';
    }
    
    return $html;
}

// ============================================
// SOCIAL MEDIA - CHECK CONNECTIONS AJAX
// ============================================
// Logged-in users: Check social connections
add_action('wp_ajax_check_social_connections', 'handle_check_social_connections');
// Non-logged-in users: Return empty (no connections)
add_action('wp_ajax_nopriv_check_social_connections', 'handle_check_social_connections_nopriv');

function handle_check_social_connections() {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }

    $user_id = get_current_user_id();

    // Check Facebook connection
    $facebook_connected = false;
    if (function_exists('is_facebook_connected')) {
        $facebook_connected = is_facebook_connected($user_id);
    }

    // Check LinkedIn connection (MM Social Poster plugin)
    $linkedin_connected = function_exists('mm_social_poster_is_connected')
        && mm_social_poster_is_connected($user_id, 'linkedin');

    wp_send_json_success([
        'facebook_connected' => $facebook_connected,
        'linkedin_connected' => $linkedin_connected
    ]);
}

// Handler for non-logged-in users (returns no connections)
function handle_check_social_connections_nopriv() {
    wp_send_json_success([
        'facebook_connected' => false,
        'linkedin_connected' => false
    ]);
}



// Debug shortcode to display user profile data (সব ইউজারের জন্য উন্মুক্ত)
add_shortcode('debug_user_profile_data', function() {
    // সব লগইন ইউজার দেখতে পারবে
    if (!is_user_logged_in()) {
        return '<p style="color: red;">Please log in</p>';
    }
    
    $user_id = get_current_user_id();
    $profile_data = UserProfileData::getInstance()->getProfile();
    
    // HTML output
    $html = '<div style="background: #f5f5f5; padding: 20px; border: 1px solid #ddd; border-radius: 5px; margin: 20px 0; max-width: 100%; overflow-x: auto;">';
    $html .= '<h3 style="color: #333; margin-top: 0;">🔍 User Profile Debug Data</h3>';
    $html .= '<pre style="background: white; padding: 15px; border-radius: 3px; border: 1px solid #ccc; overflow-x: auto; font-size: 12px;">';
    $html .= htmlspecialchars(wp_json_encode($profile_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    $html .= '</pre>';
    $html .= '</div>';
    
    return $html;
});

