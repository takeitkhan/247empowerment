<?php
// ============================================
// Login Redirect — guide first, profile when done
// ============================================
add_filter( 'login_redirect', function ( $redirect_to, $requested_redirect_to, $user ) {
    if ( ! ( $user instanceof WP_User ) || empty( $user->ID ) ) {
        return $redirect_to;
    }
    if ( user_can( $user, 'manage_options' ) || user_can( $user, 'edit_posts' ) ) {
        return $redirect_to;
    }
    $uid = $user->ID;
    $done_cats  = get_user_meta( $uid, 'user_categories_priority', true );
    $done_about = get_user_meta( $uid, 'guide_about', true )
               ?: get_user_meta( $uid, 'about_me', true )
               ?: get_user_meta( $uid, 'digital_card_about', true );
    $done_title = get_user_meta( $uid, 'guide_title', true )
               ?: get_user_meta( $uid, 'designation', true );
    $guide_complete = is_array( $done_cats ) && count( $done_cats ) > 0
                   && ! empty( $done_about ) && ! empty( $done_title );
    return $guide_complete ? home_url( '/modify-profile/' ) : home_url( '/guide/' );
}, 10, 3 );

// ============================================
// Guide Page Access Guard (template_redirect)
// — Runs before ANY template renders
// — Not logged in            → login page
// — Logged in + all done     → /modify-profile/ (guide is hidden)
// — Logged in + steps missing → allow through
// ============================================
add_action( 'template_redirect', function () {
    error_log('[GAMIF] ===== TEMPLATE_REDIRECT HOOK FIRED =====');
    
    $user_id = get_current_user_id();
    $is_logged_in = is_user_logged_in();
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    
    error_log('[GAMIF_EARLY] REQUEST_URI: ' . $request_uri);
    error_log('[GAMIF_EARLY] REQUEST_METHOD: ' . ($_SERVER['REQUEST_METHOD'] ?? 'N/A'));
    error_log('[GAMIF_EARLY] POST action: ' . ($_POST['action'] ?? 'N/A'));
    error_log('[GAMIF_EARLY] Contains admin-post.php: ' . (strpos( $request_uri, 'admin-post.php' ) !== false ? 'YES' : 'NO'));
    
    error_log('[GAMIF] user_id=' . $user_id . ', logged_in=' . ($is_logged_in ? 'yes' : 'no') . ', uri=' . $request_uri);
    
    // ── GUIDE PAGE ──────────────────────────────────────────────
    if ( is_page( 'guide' ) ) {
        error_log('[GAMIF] On guide page');
        // Must be logged in — send to custom signin
        if ( ! is_user_logged_in() ) {
            error_log('[GAMIF] Not logged in, redirecting to signin');
            wp_redirect( home_url( '/signin/' ) );
            exit;
        }

        // Never redirect on AJAX save POSTs — the template handles them and returns JSON.
        // Redirecting here would cause the fetch() to get HTML back and fail silently.
        if ( isset( $_POST['guide_action'] ) ) {
            error_log('[GAMIF] Guide action POST, returning early');
            return;
        }

        $uid        = get_current_user_id();
        $done_cats  = get_user_meta( $uid, 'user_categories_priority', true );
        $done_about = get_user_meta( $uid, 'guide_about', true )
                   ?: get_user_meta( $uid, 'about_me', true )
                   ?: get_user_meta( $uid, 'digital_card_about', true );
        $done_title = get_user_meta( $uid, 'guide_title', true )
                   ?: get_user_meta( $uid, 'designation', true );

        $guide_complete = is_array( $done_cats ) && count( $done_cats ) > 0
                       && ! empty( $done_about ) && ! empty( $done_title );

        // All steps done — guide is no longer accessible
        if ( $guide_complete ) {
            error_log('[GAMIF] Guide complete, redirecting to modify-profile');
            wp_redirect( home_url( '/modify-profile/' ) );
            exit;
        }
        error_log('[GAMIF] Guide page, allowing access');
        return; // Allow guide page to load
    }

    // ── ALL OTHER PAGES: interests gate ─────────────────────────
    // Skip if not logged in, admin, or AJAX
    if ( ! is_user_logged_in() ) {
        error_log('[GAMIF] Not logged in, returning');
        return;
    }
    if ( current_user_can( 'manage_options' ) || current_user_can( 'edit_posts' ) ) {
        error_log('[GAMIF] Is admin/editor, returning');
        return;
    }
    if ( wp_doing_ajax() ) {
        error_log('[GAMIF] Is AJAX, returning');
        return;
    }
    
    // Skip admin-post.php (form handlers, OAuth callbacks, etc)
    if ( strpos( $request_uri, 'admin-post.php' ) !== false ) {
        error_log( '[GAMIF] Skipping admin-post.php request: ' . $request_uri );
        return;
    }

    // Skip auth / utility pages
    $raw_slug = trim( parse_url( $request_uri, PHP_URL_PATH ), '/' );
    $bypass   = [ 'signin', 'signup', 'lost-password', 'wp-login.php', 'wp-admin', 'wp-json', 'wp-cron.php', 'zoom', 'connect-zoom', 'modify-profile', 'connect-social-media-accounts', 'wp-content', 'wp-includes', 'guide-complete' ];
    foreach ( $bypass as $slug ) {
        if ( strpos( $raw_slug, $slug ) !== false ) {
            error_log( '[GAMIF] Skipping bypass page: ' . $raw_slug . ' (matched: ' . $slug . ')' );
            return;
        }
    }

    error_log( '[GAMIF] Checking interests gate for: ' . $raw_slug . ' (user: ' . $user_id . ')' );

    // If the user hasn't chosen interests yet, every page redirects to guide
    $uid       = get_current_user_id();
    $interests = get_user_meta( $uid, 'user_categories_priority', true );
    if ( ! is_array( $interests ) || count( $interests ) === 0 ) {
        error_log( '[GAMIF] ❌ Redirecting to guide (no interests) for user: ' . $user_id . ', page: ' . $raw_slug );
        wp_redirect( home_url( '/guide/' ) );
        exit;
    }
    
    error_log( '[GAMIF] ✅ User has interests, allowing access to: ' . $raw_slug );
} );

// ============================================
// Guide Form - Enqueue guideFormVars
// ============================================
add_action('wp_enqueue_scripts', function() {
    wp_add_inline_script(
        'jquery-core',
        'var guideFormVars = ' . json_encode([
            'nonce'   => wp_create_nonce('guide_form_nonce'),
            'ajaxurl' => admin_url('admin-ajax.php'),
        ]) . ';'
    );
});

// ============================================
// Guide: Save Onboarding Data (AJAX)
// ============================================
add_action('wp_ajax_save_guide_form_data', function () {

    if (!wp_verify_nonce($_POST['nonce'], 'guide_form_nonce')) {
        wp_send_json_error('Invalid nonce'); return;
    }

    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in'); return;
    }

    $uid = get_current_user_id();

    if (isset($_POST['user_categories'])) {
        $selected   = $_POST['user_categories'] ?? [];
        $priorities = $_POST['user_categories_priority'] ?? [];
        $interests  = [];
        foreach ($selected as $cat_id) {
            $cat_id = absint($cat_id);
            $interests[$cat_id] = absint($priorities[$cat_id] ?? 1);
        }
        update_user_meta($uid, 'guide_interests', $interests);
        update_user_meta($uid, 'user_categories_priority', $interests);
    }

    if (isset($_POST['title'])) {
        update_user_meta($uid, 'about_me_short', sanitize_text_field($_POST['title']));
        update_user_meta($uid, 'bp_xprofile_title', sanitize_text_field($_POST['title']));
    }

    if (isset($_POST['about'])) {
        update_user_meta($uid, 'about_me', sanitize_textarea_field($_POST['about']));
        update_user_meta($uid, 'bp_xprofile_about', sanitize_text_field($_POST['about']));
    }

    if (isset($_POST['address'])) {
        update_user_meta($uid, 'place_display_name', sanitize_text_field($_POST['address']));
        update_user_meta($uid, 'bp_xprofile_address', sanitize_text_field($_POST['address']));
    }

    if (isset($_POST['keywords'])) {
        update_user_meta($uid, 'bp_xprofile_keywords', sanitize_text_field($_POST['keywords']));
    }

    if (isset($_POST['hashtags'])) {
        update_user_meta($uid, 'bp_xprofile_hashtags', sanitize_text_field($_POST['hashtags']));
    }

    if (isset($_POST['social_links'])) {
        $links = json_decode(stripslashes($_POST['social_links']), true);
        if (is_array($links)) {
            update_user_meta($uid, 'guide_social_links', $links);
            update_user_meta($uid, 'social_links', $links);
        }
    }

    wp_send_json_success('Saved correctly!');
});

// ============================================
// Guide: Add BuddyPress notification for points
// ============================================
add_action('wp_ajax_guide_add_notification', function () {

    if ( ! is_user_logged_in() ) {
        wp_send_json_error('Not logged in'); return;
    }

    $nonce = sanitize_text_field( $_POST['nonce'] ?? '' );
    if ( ! wp_verify_nonce( $nonce, 'guide_form_nonce' ) ) {
        wp_send_json_error('Invalid nonce'); return;
    }

    $uid    = get_current_user_id();
    $reason = sanitize_text_field( $_POST['reason'] ?? 'guide_step' );
    $added  = absint( $_POST['added'] ?? 0 );
    $total  = absint( $_POST['total'] ?? 0 );

    $labels = [
        'interest_completed'     => 'Interests set',
        'business_card_completed'=> 'Business card saved',
        'social_links_completed' => 'Social links saved',
        'onboarding_completed'   => 'Onboarding complete',
    ];
    $label = $labels[$reason] ?? ucwords(str_replace('_',' ',$reason));
    $msg   = sprintf('+%d pts — %s (Total: %d pts)', $added, $label, $total);

    // BuddyPress notification (if active)
    if ( function_exists('bp_notifications_add_notification') ) {
        bp_notifications_add_notification([
            'user_id'          => $uid,
            'item_id'          => $uid,
            'component_name'   => 'members',
            'component_action' => 'guide_points_earned',
            'date_notified'    => bp_core_current_time(),
            'is_new'           => 1,
        ]);
    }

    // Also store as a WP usermeta notification log
    $log   = get_user_meta( $uid, 'guide_notifications', true ) ?: [];
    $log[] = [ 'msg' => $msg, 'time' => current_time('mysql') ];
    // Keep last 50
    if ( count($log) > 50 ) { $log = array_slice($log, -50); }
    update_user_meta( $uid, 'guide_notifications', $log );

    wp_send_json_success('Notification added');
});

// ============================================
// Guide: Get Interests HTML (AJAX)
// ============================================
add_action('wp_ajax_get_guide_interests_html', function () {

    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in'); return;
    }

    $uid   = get_current_user_id();
    $saved = get_user_meta($uid, 'user_categories_priority', true);
    $saved = is_array($saved) ? $saved : [];

    $categories = get_categories([
        'hide_empty'   => false,
        'slug__not_in' => ['uncategorized'],
    ]);

    ob_start();
    foreach ($categories as $cat) {
        $checked  = isset($saved[$cat->term_id]);
        $priority = $saved[$cat->term_id] ?? '';
        ?>
        <div class="list-item" style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
            <input type="checkbox"
                   name="user_categories[]"
                   value="<?= esc_attr($cat->term_id); ?>"
                   <?php checked($checked); ?>>
            <label style="flex:1;"><?= esc_html($cat->name); ?></label>
            <select name="user_categories_priority[<?= esc_attr($cat->term_id); ?>]">
                <option value="">Priority</option>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <option value="<?= $i; ?>" <?= selected($priority, $i, false); ?>>
                        <?= $i . ($i==1?'st':($i==2?'nd':($i==3?'rd':'th'))); ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
        <?php
    }
    $html = ob_get_clean();

    wp_send_json_success(['html' => $html]);
});

// ============================================
// Retroactive Guide Points Backfill
// Root cause: award_points used mm_spg_*_completed as dedup, but save handlers
// set those flags BEFORE award_points ran — so every award silently returned
// already_awarded=true and wrote nothing. Fix: use mm_spg_pts_* for award tracking.
// This hook runs once per user and credits any missed guide step points.
// ============================================
add_action( 'wp_loaded', function () {
    if ( ! is_user_logged_in() || wp_doing_ajax() || wp_doing_cron() ) return;

    $uid = get_current_user_id();
    if ( get_user_meta( $uid, 'mm_spg_guide_pts_backfill_done', true ) ) return;

    $steps = [
        'interest_completed'      => 10,
        'business_card_completed' => 10,
        'social_links_completed'  => 10,
        'onboarding_completed'    => 10,
    ];

    foreach ( $steps as $step => $pts ) {
        $step_done   = get_user_meta( $uid, 'mm_spg_' . $step,     true ); // set by save handler
        $pts_awarded = get_user_meta( $uid, 'mm_spg_pts_' . $step, true ); // set by award handler

        if ( $step_done && ! $pts_awarded ) {
            // Write to the gamification system (what the wallet reads) if available,
            // otherwise fall back to mm_spg_points so something is recorded locally.
            if ( function_exists( 'mm_award_points_and_notify' ) ) {
                mm_award_points_and_notify( $uid, $step );
            } else {
                $current = (int) get_user_meta( $uid, 'mm_spg_points', true );
                update_user_meta( $uid, 'mm_spg_points', $current + $pts );

                $history = get_user_meta( $uid, 'mm_spg_points_history', true );
                if ( ! is_array( $history ) ) { $history = []; }
                $history[] = [
                    'points'    => $pts,
                    'reason'    => $step,
                    'date'      => current_time( 'mysql' ),
                    'timestamp' => time(),
                ];
                update_user_meta( $uid, 'mm_spg_points_history', $history );
            }
            update_user_meta( $uid, 'mm_spg_pts_' . $step, 1 );
        }
    }

    update_user_meta( $uid, 'mm_spg_guide_pts_backfill_done', 1 );
} );

// ============================================
// Points Check REST API
// GET  /wp-json/guide/v1/points?user_id=<id>
// Auth: Basic base64(user:app-password) or cookie after browser login
// ============================================
add_action( 'rest_api_init', function () {
    register_rest_route( 'guide/v1', '/points', [
        'methods'             => WP_REST_Server::READABLE,
        'permission_callback' => function () {
            // Must be logged in; admin can query any user, others only self
            return is_user_logged_in();
        },
        'args' => [
            'user_id' => [
                'type'              => 'integer',
                'default'           => 0,
                'sanitize_callback' => 'absint',
            ],
        ],
        'callback' => function ( WP_REST_Request $request ) {
            $user_id = (int) $request->get_param( 'user_id' );
            if ( ! $user_id ) {
                $user_id = get_current_user_id();
            }

            // Non-admins can only view their own points
            if ( $user_id !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
                return new WP_Error(
                    'forbidden',
                    'You can only view your own points.',
                    [ 'status' => 403 ]
                );
            }

            $user = get_userdata( $user_id );
            if ( ! $user ) {
                return new WP_Error( 'not_found', 'User not found.', [ 'status' => 404 ] );
            }

            $total   = (int) get_user_meta( $user_id, 'mm_spg_points', true );
            $history = get_user_meta( $user_id, 'mm_spg_points_history', true );
            if ( ! is_array( $history ) ) {
                $history = [];
            }

            $flags = [
                'interest_completed'      => (bool) get_user_meta( $user_id, 'mm_spg_interest_completed',      true ),
                'business_card_completed' => (bool) get_user_meta( $user_id, 'mm_spg_business_card_completed', true ),
                'social_links_completed'  => (bool) get_user_meta( $user_id, 'mm_spg_social_links_completed',  true ),
                'onboarding_completed'    => (bool) get_user_meta( $user_id, 'mm_spg_onboarding_completed',    true ),
            ];

            return rest_ensure_response( [
                'user_id'      => $user_id,
                'display_name' => $user->display_name,
                'email'        => $user->user_email,
                'total_points' => $total,
                'earned_flags' => $flags,
                'history'      => $history,
            ] );
        },
    ] );
} );

// ============================================
// Guide: Personalized Path Shortcode
// ============================================
add_shortcode('guide_personalized_path', function () {

    if (!is_user_logged_in()) return '';

    $uid = get_current_user_id();

    $interests = get_user_meta($uid, 'user_categories_priority', true);
    if (empty($interests) || !is_array($interests)) return '';

    asort($interests);

    $selected_slugs = [];
    foreach ($interests as $term_id => $priority) {
        $term = get_term($term_id, 'category');
        if ($term && !is_wp_error($term)) {
            $selected_slugs[] = strtolower($term->name);
        }
    }

    $paths = [

        'communications, business & marketing' => [
            'title' => 'Communications, Business & Marketing',
            'icon'  => '📢',
            'links' => [
                ['label' => 'Digital Business Card',   'url' => 'https://personalempowermentteams.me/digital-business-card/'],
                ['label' => 'Videos Playlists',        'url' => 'https://personalempowermentteams.me/videos/'],
                ['label' => 'Social Management',       'url' => 'https://personalempowermentteams.me/modify-links/'],
                ['label' => 'Marketing Tools (100+)',  'url' => 'https://personalempowermentteams.me/tools/'],
                ['label' => 'AI Tools',                'url' => 'https://personalempowermentteams.me/artificial-intelligence/'],
                ['label' => 'Collaboration',           'url' => 'https://personalempowermentteams.me/collaboration/'],
                ['label' => 'Reputation Marketing',    'url' => 'https://personalempowermentteams.me/reputation/'],
                ['label' => 'Marketplace',             'url' => 'https://personalempowermentteams.me/joshuajoseph/store/'],
                ['label' => 'Wallet',                  'url' => 'https://personalempowermentteams.me/wallet/'],
                ['label' => 'Teams',                   'url' => 'https://personalempowermentteams.me/focus-points/'],
                ['label' => 'FAQs',                    'url' => 'https://personalempowermentteams.me/faqs/'],
                ['label' => 'Sales Agreement',         'url' => 'https://personalempowermentteams.me/sales-agreement-form/'],
                ['label' => 'Participation Agreement', 'url' => 'https://personalempowermentteams.me/participation-agreement-form/'],
            ],
        ],

        'income development' => [
            'title' => 'Income Development',
            'icon'  => '💰',
            'links' => [
                ['label' => 'Digital Business Card',   'url' => 'https://personalempowermentteams.me/digital-business-card/'],
                ['label' => 'Videos Playlists',        'url' => 'https://personalempowermentteams.me/videos/'],
                ['label' => 'Social Management',       'url' => 'https://personalempowermentteams.me/modify-links/'],
                ['label' => 'Marketing Tools (100+)',  'url' => 'https://personalempowermentteams.me/tools/'],
                ['label' => 'AI Tools',                'url' => 'https://personalempowermentteams.me/artificial-intelligence/'],
                ['label' => 'Collaboration',           'url' => 'https://personalempowermentteams.me/collaboration/'],
                ['label' => 'Reputation Marketing',    'url' => 'https://personalempowermentteams.me/reputation/'],
                ['label' => 'Marketplace',             'url' => 'https://personalempowermentteams.me/joshuajoseph/store/'],
                ['label' => 'Wallet',                  'url' => 'https://personalempowermentteams.me/wallet/'],
                ['label' => 'Teams',                   'url' => 'https://personalempowermentteams.me/focus-points/'],
                ['label' => 'FAQs',                    'url' => 'https://personalempowermentteams.me/faqs/'],
                ['label' => 'Sales Agreement',         'url' => 'https://personalempowermentteams.me/sales-agreement-form/'],
                ['label' => 'Participation Agreement', 'url' => 'https://personalempowermentteams.me/participation-agreement-form/'],
            ],
        ],

        'sales careers' => [
            'title' => 'Sales Careers',
            'icon'  => '💼',
            'links' => [
                ['label' => 'Careers Page',            'url' => 'https://personalempowermentteams.me/careers/'],
                ['label' => 'Videos Playlists',        'url' => 'https://youtu.be/Lx5kkc5lMFc?list=PLI38LwhCUSlZKUTB9tRRNAfYCnBD8bwVO'],
                ['label' => 'Digital Business Card',   'url' => 'https://personalempowermentteams.me/digital-business-card/'],
                ['label' => 'Marketplace',             'url' => 'https://personalempowermentteams.me/joshuajoseph/store/'],
                ['label' => 'Sales Agreement',         'url' => 'https://personalempowermentteams.me/sales-agreement-form/'],
                ['label' => 'Participation Agreement', 'url' => 'https://personalempowermentteams.me/participation-agreement-form/'],
            ],
        ],

        'sustainable communities' => [
            'title' => 'Sustainable Communities',
            'icon'  => '🏘️',
            'links' => [
                ['label' => 'Housing & Communities',   'url' => 'https://personalempowermentteams.me/housing/'],
                ['label' => 'Videos Playlists',        'url' => 'https://personalempowermentteams.me/videos/'],
                ['label' => 'FAQs',                    'url' => 'https://personalempowermentteams.me/faqs/'],
            ],
        ],

        'personal empowerment teams' => [
            'title' => 'Personal Empowerment Teams',
            'icon'  => '🤝',
            'links' => [
                ['label' => 'Teams & Focus Points',    'url' => 'https://personalempowermentteams.me/focus-points/'],
                ['label' => 'Videos Playlists',        'url' => 'https://personalempowermentteams.me/videos/'],
                ['label' => 'FAQs',                    'url' => 'https://personalempowermentteams.me/faqs/'],
            ],
        ],

        'nde, spirituality & empowerment' => [
            'title' => 'NDE, Spirituality & Empowerment',
            'icon'  => '✨',
            'links' => [
                ['label' => 'Teams & Focus Points',    'url' => 'https://personalempowermentteams.me/focus-points/'],
                ['label' => 'Videos Playlists',        'url' => 'https://personalempowermentteams.me/videos/'],
                ['label' => 'FAQs',                    'url' => 'https://personalempowermentteams.me/faqs/'],
            ],
        ],

        'narcissistic abuse healing' => [
            'title' => 'Narcissistic Abuse Healing',
            'icon'  => '💚',
            'links' => [
                ['label' => 'Teams & Focus Points',    'url' => 'https://personalempowermentteams.me/focus-points/'],
                ['label' => 'Videos Playlists',        'url' => 'https://personalempowermentteams.me/videos/'],
                ['label' => 'FAQs',                    'url' => 'https://personalempowermentteams.me/faqs/'],
            ],
        ],

        'men & women empowerment' => [
            'title' => 'Men & Women Empowerment',
            'icon'  => '⚡',
            'links' => [
                ['label' => 'Teams & Focus Points',    'url' => 'https://personalempowermentteams.me/focus-points/'],
                ['label' => 'Videos Playlists',        'url' => 'https://personalempowermentteams.me/videos/'],
                ['label' => 'FAQs',                    'url' => 'https://personalempowermentteams.me/faqs/'],
            ],
        ],

        'habits & addictions' => [
            'title' => 'Habits & Addictions',
            'icon'  => '🔄',
            'links' => [
                ['label' => 'Teams & Focus Points',    'url' => 'https://personalempowermentteams.me/focus-points/'],
                ['label' => 'Videos Playlists',        'url' => 'https://personalempowermentteams.me/videos/'],
                ['label' => 'FAQs',                    'url' => 'https://personalempowermentteams.me/faqs/'],
            ],
        ],

        'diabetes, health & fitness' => [
            'title' => 'Diabetes, Health & Fitness',
            'icon'  => '🏃',
            'links' => [
                ['label' => 'Teams & Focus Points',    'url' => 'https://personalempowermentteams.me/focus-points/'],
                ['label' => 'Videos Playlists',        'url' => 'https://personalempowermentteams.me/videos/'],
                ['label' => 'FAQs',                    'url' => 'https://personalempowermentteams.me/faqs/'],
            ],
        ],

        'support and empowerment' => [
            'title' => 'Support and Empowerment',
            'icon'  => '🤲',
            'links' => [
                ['label' => 'Teams & Focus Points',    'url' => 'https://personalempowermentteams.me/focus-points/'],
                ['label' => 'Videos Playlists',        'url' => 'https://personalempowermentteams.me/videos/'],
                ['label' => 'FAQs',                    'url' => 'https://personalempowermentteams.me/faqs/'],
            ],
        ],
    ];

    $matched = [];
    foreach ($selected_slugs as $slug) {
        $slug_clean = strtolower(trim($slug));

        if (isset($paths[$slug_clean])) {
            $matched[] = $paths[$slug_clean];
            continue;
        }

        foreach ($paths as $key => $path) {
            similar_text($slug_clean, $key, $percent);
            if ($percent > 70) {
                $matched[] = $path;
                break;
            }
        }
    }

    if (empty($matched)) return '';

    ob_start();
    ?>
    <div class="gpp-wrap">
        <div class="gpp-attention-badge">Click the below mentioned links to complete your signup process by completing these tasks</div>
        <div class="gpp-cards">
            <?php foreach ($matched as $path): ?>
            <div class="gpp-card">
                <div class="gpp-card-header">
                    <span class="gpp-icon"><?php echo $path['icon']; ?></span>
                    <h3 class="gpp-card-title"><?php echo esc_html($path['title']); ?></h3>
                </div>
                <ul class="gpp-links">
                    <?php foreach ($path['links'] as $link): ?>
                    <li>
                        <a href="<?php echo esc_url($link['url']); ?>" target="_blank" rel="noopener">
                            <span class="gpp-arrow">▶</span> <?php echo esc_html($link['label']); ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>
        <style>
            @keyframes gppGlow {
                0%,100% { box-shadow: 0 0 0 2px rgba(37,99,235,.5), 0 4px 20px rgba(37,99,235,.18); }
                50%      { box-shadow: 0 0 0 4px rgba(37,99,235,.85), 0 8px 32px rgba(37,99,235,.35); }
            }
            @keyframes gppBadgePulse {
                0%,100% { opacity: 1; transform: scale(1); }
                50%      { opacity: .88; transform: scale(1.015); }
            }
            @keyframes gppArrow {
                0%,100% { transform: translateX(0); }
                50%      { transform: translateX(3px); }
            }
            .gpp-wrap {
                padding: 0; border-radius: 12px; overflow: hidden;
                animation: gppGlow 2.8s ease-in-out infinite;
            }
            .gpp-attention-badge {
                background: linear-gradient(135deg, #f59e0b, #fbbf24);
                color: #1a0800; font-weight: 900; font-size: .76rem;
                padding: 7px 14px; text-align: center; letter-spacing: .02em;
                animation: gppBadgePulse 2.2s ease-in-out infinite;
                cursor: default;
            }
            .gpp-header {
                display: flex; align-items: center; gap: 10px;
                padding: 12px 16px 10px;
                background: linear-gradient(135deg,#1a3e6e,#2563eb);
            }
            .gpp-header-icon { font-size: 1.4rem; }
            .gpp-main-title  { font-size: .95rem; font-weight: 800; color: #fff; margin: 0; line-height: 1.2; }
            .gpp-subtitle    { font-size: .7rem; color: rgba(255,255,255,.82); margin: 2px 0 0; }
            .gpp-cards { display: flex; flex-direction: column; gap: 0; border: 1px solid #dbe8f8; border-top: none; }
            .gpp-card { background: #fff; border-bottom: 1px solid #e8f0fb; padding: 12px 14px; transition: background .2s; }
            .gpp-card:last-child { border-bottom: none; border-radius: 0 0 12px 12px; }
            .gpp-card:hover { background: #f0f6ff; }
            .gpp-card-header { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
            .gpp-icon       { font-size: 1.1rem; }
            .gpp-card-title { font-size: .82rem; font-weight: 800; color: #1a3e6e; margin: 0; }
            .gpp-links      { list-style: none; padding: 0; margin: 0; }
            .gpp-links li   { margin-bottom: 5px; }
            .gpp-links li a {
                color: #2563eb; text-decoration: none; font-size: .78rem; font-weight: 600;
                display: flex; align-items: center; gap: 5px;
                transition: color .15s, gap .2s;
            }
            .gpp-links li a:hover { color: #1a3e6e; text-decoration: underline; gap: 9px; }
            .gpp-arrow { font-size: .6rem; color: #2563eb; animation: gppArrow 1.4s ease-in-out infinite; display: inline-block; }
            .gpp-links li a:hover .gpp-arrow { animation: none; color: #1a3e6e; }
        </style>
    </div>
    <?php
    return ob_get_clean();
});

add_action('wp_footer', function() {
    if (!is_page('guide')) return;
    ?>
    <script>
    var currentPage = 1;
    var totalPages  = 4;

    function showPage(n) {
        document.querySelectorAll('.book-container .pages').forEach(function(p) {
            p.classList.remove('current');
        });
        var target = document.querySelector('.book-container .page' + n);
        if (target) target.classList.add('current');
        currentPage = n;
        if (currentPage === totalPages) {
            document.querySelector('.nextPageBtn').textContent = 'Go to Profile';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        showPage(1);
        loadInterests();
        fitAllText();
    });

    function loadInterests() {
        var container = document.getElementById('guide-interests-list');
        if (!container) return;
        var data = new FormData();
        data.append('action', 'get_guide_interests_html');
        data.append('nonce', (typeof guideFormVars !== 'undefined') ? guideFormVars.nonce : '');
        fetch((typeof guideFormVars !== 'undefined' ? guideFormVars.ajaxurl : '/wp-admin/admin-ajax.php'), {
            method: 'POST', body: data
        })
        .then(function(r){ return r.json(); })
        .then(function(res){
            if (res.success) {
                container.innerHTML = res.data.html;
                showPage(1);
            }
        })
        .catch(function(){ console.warn('Could not load interests'); });
    }

    function handleNextPage() {
        if (currentPage === 1) {
            saveInterests(function(){ showPage(2); });
        } else if (currentPage === 2) {
            savePage2(function(){ showPage(3); });
        } else if (currentPage === 3) {
            savePage3(function(){ showPage(4); });
        } else if (currentPage === 4) {
            window.location.href = 'https://personalempowermentteams.me/modify-profile/';
        }
    }

    function saveInterests(callback) {
        var checkboxes = document.querySelectorAll('#guide-interests-list input[name="user_categories[]"]:checked');
        var selects    = document.querySelectorAll('#guide-interests-list select[name^="user_categories_priority"]');
        var formData   = new FormData();
        formData.append('action', 'save_guide_form_data');
        formData.append('nonce', (typeof guideFormVars !== 'undefined') ? guideFormVars.nonce : '');
        checkboxes.forEach(function(cb) { formData.append('user_categories[]', cb.value); });
        selects.forEach(function(sel)   { formData.append(sel.name, sel.value); });
        fetch((typeof guideFormVars !== 'undefined' ? guideFormVars.ajaxurl : '/wp-admin/admin-ajax.php'), {
            method: 'POST', body: formData
        })
        .then(function(r){ return r.json(); })
        .then(function(res){
            if (res.success && callback) callback();
            else alert('Error saving interests');
        });
    }

    function savePage2(callback) {
        var formData = new FormData();
        formData.append('action', 'save_guide_form_data');
        formData.append('nonce', (typeof guideFormVars !== 'undefined') ? guideFormVars.nonce : '');
        document.querySelectorAll('#guide-interests-list input[name="user_categories[]"]:checked')
            .forEach(function(cb) { formData.append('user_categories[]', cb.value); });
        document.querySelectorAll('#guide-interests-list select[name^="user_categories_priority"]')
            .forEach(function(sel) { formData.append(sel.name, sel.value); });
        formData.append('title',    document.getElementById('guide-title').value);
        formData.append('about',    document.getElementById('guide-about').value);
        formData.append('address',  document.getElementById('guide-address').value);
        formData.append('keywords', document.getElementById('guide-keywords').value);
        formData.append('hashtags', document.getElementById('guide-hashtags').value);
        fetch((typeof guideFormVars !== 'undefined' ? guideFormVars.ajaxurl : '/wp-admin/admin-ajax.php'), {
            method: 'POST', body: formData
        })
        .then(function(r){ return r.json(); })
        .then(function(res){
            if (res.success && callback) callback();
            else alert('Error saving profile details');
        });
    }

    function addSocialLink() {
        var container = document.getElementById('pg4-links-container');
        var row = document.createElement('div');
        row.style.cssText = 'display:flex;gap:8px;margin-bottom:8px;';
        row.innerHTML = '<input type="text" placeholder="Platform (e.g. Twitter)" class="sl-platform" style="flex:1;padding:6px;">'
                      + '<input type="text" placeholder="URL" class="sl-url" style="flex:2;padding:6px;">'
                      + '<button type="button" onclick="this.parentElement.remove()" style="padding:6px 10px;">✕</button>';
        container.appendChild(row);
    }

    function savePage3(callback) {
        var rows  = document.querySelectorAll('#pg4-links-container > div');
        var links = [];
        rows.forEach(function(row) {
            var platform = row.querySelector('.sl-platform').value.trim();
            var url      = row.querySelector('.sl-url').value.trim();
            if (platform && url) links.push({ platform: platform, url: url });
        });
        var formData = new FormData();
        formData.append('action', 'save_guide_form_data');
        formData.append('nonce', (typeof guideFormVars !== 'undefined') ? guideFormVars.nonce : '');
        document.querySelectorAll('#guide-interests-list input[name="user_categories[]"]:checked')
            .forEach(function(cb) { formData.append('user_categories[]', cb.value); });
        document.querySelectorAll('#guide-interests-list select[name^="user_categories_priority"]')
            .forEach(function(sel) { formData.append(sel.name, sel.value); });
        formData.append('social_links', JSON.stringify(links));
        fetch((typeof guideFormVars !== 'undefined' ? guideFormVars.ajaxurl : '/wp-admin/admin-ajax.php'), {
            method: 'POST', body: formData
        })
        .then(function(r){ return r.json(); })
        .then(function(res){
            if (res.success && callback) callback();
            else alert('Error saving social links');
        });
    }

    function fitAllText() {
        document.querySelectorAll('.fixText').forEach(function(el) {
            var parent = el.parentElement;
            if (!parent) return;
            el.style.fontSize = '14px';
            var maxW = parent.clientWidth, maxH = parent.clientHeight;
            if (!maxW || !maxH) return;
            var low = 1, high = Math.min(maxW, maxH) * 2;
            while (low < high - 1) {
                var mid = Math.floor((low + high) / 2);
                el.style.fontSize = mid + 'px';
                if (el.scrollWidth <= maxW && el.scrollHeight <= maxH) low = mid;
                else high = mid;
            }
            el.style.fontSize = low + 'px';
        });
    }
    window.addEventListener('resize', fitAllText);
    </script>
    <?php
});

// ============================================
// Personalized Path Spotlight — modify-profile
// On every page load/reload, dims the page and
// scrolls to + spotlights the .gpp-wrap widget
// for ~2.5s so users notice their action plan.
// ============================================
add_action( 'wp_footer', function () {
    if ( ! is_page( 'modify-profile' ) ) return;
    if ( ! is_user_logged_in() ) return;
    ?>
    <style>
    #gpp-spot-ov {
        position:fixed; inset:0;
        background:rgba(0,0,0,.72);
        z-index:99990;
        opacity:1;
        transition:opacity .75s ease;
        pointer-events:none;
    }
    #gpp-spot-ov.gpp-out { opacity:0; }
    .gpp-spot-active {
        position:relative !important;
        z-index:99999 !important;
        outline:2.5px solid #f0c060 !important;
        outline-offset:5px !important;
        border-radius:12px !important;
        animation:gppSpotPulse 2.6s ease forwards;
    }
    @keyframes gppSpotPulse {
        0%   { box-shadow:0 0 0 0   rgba(240,192,96,.65), 0 0 32px rgba(240,192,96,.35); }
        50%  { box-shadow:0 0 0 10px rgba(240,192,96,.18), 0 0 40px rgba(240,192,96,.25); }
        100% { box-shadow:0 0 0 16px rgba(240,192,96,0),   0 0 0   rgba(240,192,96,0); }
    }
    </style>
    <script>
    (function(){
        function runSpotlight(){
            var gpp = document.querySelector('.gpp-wrap');
            if(!gpp) return;

            // Lift every positioned ancestor above the overlay
            var el = gpp.parentElement;
            while(el && el !== document.body){
                if(window.getComputedStyle(el).position !== 'static'){
                    el.style.zIndex = '99999';
                }
                el = el.parentElement;
            }

            // Full-page dark overlay
            var ov = document.createElement('div');
            ov.id = 'gpp-spot-ov';
            document.body.appendChild(ov);

            // Highlight the widget
            gpp.classList.add('gpp-spot-active');

            // Scroll to it after a brief paint delay
            setTimeout(function(){
                gpp.scrollIntoView({ behavior:'smooth', block:'center' });
            }, 150);

            // Fade out after 2.6s
            setTimeout(function(){
                ov.classList.add('gpp-out');
                gpp.classList.remove('gpp-spot-active');
                setTimeout(function(){ if(ov.parentNode) ov.remove(); }, 750);
            }, 2600);
        }

        if(document.readyState === 'loading'){
            document.addEventListener('DOMContentLoaded', runSpotlight);
        } else {
            runSpotlight();
        }
    })();
    </script>
    <?php
} );


