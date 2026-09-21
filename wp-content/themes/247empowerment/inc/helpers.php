<?php
/**
 * Helper Functions
 */

/**
 * Trigger WordPress action hook
 * Wrapper around do_action() for custom hooks
 * 
 * @param string $hook Action hook name
 * @param mixed ...$args Arguments to pass to hooked functions
 */
if (!function_exists('mm_trigger_action')) {
    function mm_trigger_action($hook, ...$args) {
        do_action($hook, ...$args);
    }
}

/**
 * Get header based on login status with safety fallback
 */
if (!function_exists('get_header_based_on_login')) {
    function get_header_based_on_login() {
        $header_type = is_user_logged_in() ? 'portal' : 'main';
        
        // Checks if header-portal.php or header-main.php exists
        if (locate_template("header-{$header_type}.php") !== '') {
            get_header($header_type);
        } else {
            get_header(); // Fallback to standard header.php
        }
    }
}

/**
 * Get footer based on login status with safety fallback
 */
if (!function_exists('get_footer_based_on_login')) {
    function get_footer_based_on_login() {
        $footer_type = is_user_logged_in() ? 'portal' : 'main';
        
        // Checks if footer-portal.php or footer-main.php exists
        if (locate_template("footer-{$footer_type}.php") !== '') {
            get_footer($footer_type);
        } else {
            get_footer(); // Fallback to standard footer.php
        }
    }
}

/**
 * Custom Error Handler - Graceful Error Display
 * 
 * Displays errors without using WordPress' default error page
 * (which adds id="error-page" to body and breaks theme design)
 * 
 * @param string $message Error message to display
 * @param string $title Optional title for the error page
 * @param int $status_code HTTP status code (default 404)
 */
if (!function_exists('mm_handle_error')) {
    function mm_handle_error($message, $title = 'Error', $status_code = 404) {
        // Set HTTP status header
        status_header($status_code);
        
        // Get the theme's header and footer
        get_header_based_on_login();
        
        // Display error message within theme template
        ?>
        <div class="mx-auto px-4 py-12 container">
            <div class="mx-auto max-w-2xl">
                <div class="bg-red-50 p-8 border border-red-200 rounded-lg">
                    <h1 class="mb-4 font-bold text-red-800 text-4xl"><?php echo esc_html($title); ?></h1>
                    <p class="text-red-700 text-lg"><?php echo wp_kses_post($message); ?></p>
                    <div class="mt-6">
                        <a href="<?php echo esc_url(home_url()); ?>" class="inline-block bg-blue-600 hover:bg-blue-700 px-6 py-3 rounded-lg text-white transition">
                            <?php _e('Back to Home', 'textdomain'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
        
        get_footer_based_on_login();
        
        // Stop execution
        exit;
    }
}

/**
 * Resolve a public user identifier from /u/{identifier} style URLs.
 *
 * Tries user_nicename (slug) first, then user_login for backward compatibility.
 *
 * @param string $identifier Public profile identifier.
 * @return WP_User|false
 */
if (!function_exists('mm_resolve_public_user')) {
    function mm_resolve_public_user($identifier) {
        $identifier = is_string($identifier) ? sanitize_title_for_query($identifier) : '';
        if ($identifier === '') {
            return false;
        }

        $user = get_user_by('slug', $identifier);
        if (!$user) {
            $user = get_user_by('login', $identifier);
        }

        return $user;
    }
}
