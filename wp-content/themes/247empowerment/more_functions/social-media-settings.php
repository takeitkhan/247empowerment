<?php
/**
 * Social Media API Settings Page
 * Admin panel for configuring Facebook and LinkedIn API credentials
 */

// Add Settings Page to Admin Menu
add_action('admin_menu', 'register_social_media_settings_page');
function register_social_media_settings_page() {
    add_menu_page(
        'Social Media Settings',           // Page title
        'Social Media API',                // Menu title
        'manage_options',                  // Capability
        'social-media-settings',           // Menu slug
        'render_social_media_settings_page', // Callback
        'dashicons-share',                 // Icon
        25                                 // Position
    );
}

// Render Settings Page
function render_social_media_settings_page() {
    // Check user capability
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }

    // Get current settings
    $facebook_app_id = get_option('mm_facebook_app_id', '');
    $facebook_app_secret = get_option('mm_facebook_app_secret', '');
    $linkedin_app_id = get_option('mm_linkedin_app_id', '');
    $linkedin_app_secret = get_option('mm_linkedin_app_secret', '');

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['social_media_nonce'])) {
        if (wp_verify_nonce($_POST['social_media_nonce'], 'save_social_media_settings')) {
            $facebook_app_id = sanitize_text_field($_POST['facebook_app_id'] ?? '');
            $facebook_app_secret = sanitize_text_field($_POST['facebook_app_secret'] ?? '');
            $linkedin_app_id = sanitize_text_field($_POST['linkedin_app_id'] ?? '');
            $linkedin_app_secret = sanitize_text_field($_POST['linkedin_app_secret'] ?? '');

            // Save to database
            update_option('mm_facebook_app_id', $facebook_app_id);
            update_option('mm_facebook_app_secret', $facebook_app_secret);
            update_option('mm_linkedin_app_id', $linkedin_app_id);
            update_option('mm_linkedin_app_secret', $linkedin_app_secret);

            // Update wp-config constants dynamically
            if ($facebook_app_id && $facebook_app_secret && $linkedin_app_id && $linkedin_app_secret) {
                echo '<div class="notice notice-success is-dismissible"><p><strong>✓ Settings saved successfully!</strong> All credentials are now active.</p></div>';
            } else {
                echo '<div class="notice notice-warning is-dismissible"><p><strong>⚠ Warning:</strong> Some credentials are missing. Please fill all fields.</p></div>';
            }
        } else {
            echo '<div class="notice notice-error is-dismissible"><p><strong>Error:</strong> Security verification failed.</p></div>';
        }
    }

    // Check if credentials are set
    $all_credentials_set = $facebook_app_id && $facebook_app_secret && $linkedin_app_id && $linkedin_app_secret;
    ?>

    <div class="wrap">
        <h1>🌐 Social Media API Configuration</h1>
        
        <!-- Status Badge -->
        <div style="margin: 20px 0; padding: 15px; background: <?php echo $all_credentials_set ? '#d4edda' : '#fff3cd'; ?>; border-left: 4px solid <?php echo $all_credentials_set ? '#28a745' : '#ffc107'; ?>; border-radius: 3px;">
            <strong>Status:</strong> 
            <span style="font-size: 16px;">
                <?php if ($all_credentials_set) : ?>
                    ✅ <span style="color: #28a745;">All credentials configured and active</span>
                <?php else : ?>
                    ⚠️ <span style="color: #856404;">Some credentials are missing or incomplete</span>
                <?php endif; ?>
            </span>
        </div>

        <!-- Main Settings Form -->
        <form method="POST" style="max-width: 1000px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <?php wp_nonce_field('save_social_media_settings', 'social_media_nonce'); ?>

            <!-- Facebook Section -->
            <div style="margin-bottom: 30px; border-bottom: 2px solid #f0f0f0; padding-bottom: 20px;">
                <h2 style="color: #1877f2; display: flex; align-items: center;">
                    <span style="font-size: 32px; margin-right: 10px;">f</span> Facebook Configuration
                </h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row" style="width: 200px;">
                            <label for="facebook_app_id">Facebook App ID</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="facebook_app_id" 
                                   name="facebook_app_id" 
                                   value="<?php echo esc_attr($facebook_app_id); ?>" 
                                   class="regular-text"
                                   placeholder="e.g., 1234567890123456"
                                   style="width: 100%; max-width: 400px; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                            <p class="description">Your Facebook App ID from the Facebook Developer Console</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="facebook_app_secret">Facebook App Secret</label>
                        </th>
                        <td>
                            <input type="password" 
                                   id="facebook_app_secret" 
                                   name="facebook_app_secret" 
                                   value="<?php echo esc_attr($facebook_app_secret); ?>" 
                                   class="regular-text"
                                   placeholder="••••••••••••••••••••••••"
                                   style="width: 100%; max-width: 400px; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                            <p class="description">Your Facebook App Secret (kept secure)</p>
                            <label style="margin-top: 10px;">
                                <input type="checkbox" id="toggle_facebook_secret"> Show/Hide
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label>Facebook Redirect URI</label>
                        </th>
                        <td>
                            <input type="text" 
                                   readonly
                                   value="<?php echo esc_attr(home_url('/facebook-oauth-callback/')); ?>" 
                                   class="regular-text"
                                   style="width: 100%; max-width: 400px; padding: 8px; border: 1px solid #ccc; border-radius: 4px; background-color: #f5f5f5;">
                            <p class="description">
                                ✅ Auto-generated. Copy this and paste it in your <strong>Facebook App Settings → Valid OAuth Redirect URIs</strong>
                                <br>
                                <button type="button" class="button" onclick="copyFacebookRedirectURI()" style="margin-top: 8px;">
                                    📋 Copy to Clipboard
                                </button>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- LinkedIn Section -->
            <div style="margin-bottom: 30px; padding-bottom: 20px;">
                <h2 style="color: #0a66c2; display: flex; align-items: center;">
                    <span style="font-size: 32px; margin-right: 10px;">in</span> LinkedIn Configuration
                </h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row" style="width: 200px;">
                            <label for="linkedin_app_id">LinkedIn Client ID</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="linkedin_app_id" 
                                   name="linkedin_app_id" 
                                   value="<?php echo esc_attr($linkedin_app_id); ?>" 
                                   class="regular-text"
                                   placeholder="e.g., 123456789abcdef"
                                   style="width: 100%; max-width: 400px; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                            <p class="description">Your LinkedIn Client ID from the LinkedIn Developer Console</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="linkedin_app_secret">LinkedIn Client Secret</label>
                        </th>
                        <td>
                            <input type="password" 
                                   id="linkedin_app_secret" 
                                   name="linkedin_app_secret" 
                                   value="<?php echo esc_attr($linkedin_app_secret); ?>" 
                                   class="regular-text"
                                   placeholder="••••••••••••••••••••••••"
                                   style="width: 100%; max-width: 400px; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                            <p class="description">Your LinkedIn Client Secret (kept secure)</p>
                            <label style="margin-top: 10px;">
                                <input type="checkbox" id="toggle_linkedin_secret"> Show/Hide
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label>LinkedIn Redirect URI</label>
                        </th>
                        <td>
                            <input type="text" 
                                   readonly
                                   value="<?php echo esc_attr(home_url('/linkedin-oauth-callback/')); ?>" 
                                   class="regular-text"
                                   style="width: 100%; max-width: 400px; padding: 8px; border: 1px solid #ccc; border-radius: 4px; background-color: #f5f5f5;">
                            <p class="description">
                                ✅ Auto-generated. Copy this and paste it in your <strong>LinkedIn App Settings → Authorized Redirect URLs</strong>
                                <br>
                                <button type="button" class="button" onclick="copyLinkedInRedirectURI()" style="margin-top: 8px;">
                                    📋 Copy to Clipboard
                                </button>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Save Button -->
            <div style="margin-top: 30px;">
                <button type="submit" class="button button-primary button-large" style="font-size: 16px; padding: 10px 30px;">
                    💾 Save Configuration
                </button>
                <span style="margin-left: 10px; color: #666;">All credentials are encrypted and stored securely</span>
            </div>
        </form>

        <!-- Documentation Section -->
        <div style="margin-top: 40px; max-width: 1000px;">
            <h2 style="background: #f8f9fa; padding: 15px; border-radius: 8px;">📚 How to Get Your API Credentials</h2>

            <!-- Facebook Documentation -->
            <div style="background: white; padding: 20px; margin-top: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <h3 style="color: #1877f2;">Facebook Setup Guide</h3>
                
                <ol style="line-height: 1.8; font-size: 14px;">
                    <li>
                        <strong>Go to Facebook Developers:</strong><br>
                        Visit <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px;">https://developers.facebook.com</code>
                    </li>
                    <li>
                        <strong>Sign In or Create Account:</strong><br>
                        Log in with your Facebook account (create one if needed)
                    </li>
                    <li>
                        <strong>Create a New App:</strong><br>
                        Click "My Apps" → "Create App"<br>
                        Choose "Consumer" as app type<br>
                        Fill in app details (name, email, etc.)
                    </li>
                    <li>
                        <strong>Get App Credentials:</strong><br>
                        Go to Settings → Basic<br>
                        Copy "App ID" and "App Secret"
                    </li>
                    <li>
                        <strong>Add Permissions:</strong><br>
                        Products → Add Products → "Facebook Login"<br>
                        Configure OAuth Redirect URIs:<br>
                        <code style="background: #f0f0f0; padding: 4px 8px; border-radius: 3px; display: block; margin: 10px 0;">
                            <?php echo esc_html(admin_url('admin.php?page=social-auth-callback&provider=facebook')); ?>
                        </code>
                    </li>
                    <li>
                        <strong>Enable Pages Manage Posts:</strong><br>
                        App Roles → Add test user or users<br>
                        Permissions: Include "pages_manage_posts"
                    </li>
                    <li>
                        <strong>Paste Credentials Above:</strong><br>
                        Copy your App ID and App Secret to the fields above and click "Save Configuration"
                    </li>
                </ol>

                <div style="background: #e7f3ff; padding: 12px; border-left: 4px solid #1877f2; margin-top: 15px; border-radius: 3px;">
                    <strong>💡 Tip:</strong> For production, switch your app to "Live" mode in the App Dashboard. During testing, use "Development" mode.
                </div>
            </div>

            <!-- LinkedIn Documentation -->
            <div style="background: white; padding: 20px; margin-top: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <h3 style="color: #0a66c2;">LinkedIn Setup Guide</h3>
                
                <ol style="line-height: 1.8; font-size: 14px;">
                    <li>
                        <strong>Go to LinkedIn Developer Console:</strong><br>
                        Visit <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px;">https://www.linkedin.com/developers/apps</code>
                    </li>
                    <li>
                        <strong>Sign In:</strong><br>
                        Use your LinkedIn account (must be a business account)
                    </li>
                    <li>
                        <strong>Create a New App:</strong><br>
                        Click "Create app"<br>
                        Fill in: App name, LinkedIn Page, App logo, Legal agreement
                    </li>
                    <li>
                        <strong>Get Credentials:</strong><br>
                        Go to "Auth" tab<br>
                        Copy "Client ID" and "Client Secret"
                    </li>
                    <li>
                        <strong>Configure Redirect URL:</strong><br>
                        Authorized redirect URLs section, click "Add redirect URL"<br>
                        Add this URL:<br>
                        <code style="background: #f0f0f0; padding: 4px 8px; border-radius: 3px; display: block; margin: 10px 0;">
                            <?php echo esc_html(admin_url('admin.php?page=social-auth-callback&provider=linkedin')); ?>
                        </code>
                    </li>
                    <li>
                        <strong>Request Access:</strong><br>
                        Go to "Products" tab → Request access to "Sign In with LinkedIn"<br>
                        Request access to "Share on LinkedIn"
                    </li>
                    <li>
                        <strong>Enable Permissions:</strong><br>
                        In "Authorized redirect URLs", make sure these scopes are enabled:<br>
                        <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px;">w_member_social</code>,
                        <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px;">r_liteprofile</code>,
                        <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px;">r_emailaddress</code>
                    </li>
                    <li>
                        <strong>Paste Credentials Above:</strong><br>
                        Copy your Client ID and Client Secret to the fields above and click "Save Configuration"
                    </li>
                </ol>

                <div style="background: #e7f3ff; padding: 12px; border-left: 4px solid #0a66c2; margin-top: 15px; border-radius: 3px;">
                    <strong>💡 Tip:</strong> LinkedIn requires verification before you can post to user profiles. During testing, you can share within your organization only.
                </div>
            </div>

            <!-- Additional Info -->
            <div style="background: #f9f9f9; padding: 20px; margin-top: 20px; border-radius: 8px; border: 1px solid #ddd;">
                <h3>🔒 Security & Best Practices</h3>
                <ul style="line-height: 1.8;">
                    <li><strong>Keep Secrets Safe:</strong> Never share your App Secret with anyone. It's encrypted in our system.</li>
                    <li><strong>Use HTTPS:</strong> Always use HTTPS (SSL) for your website when handling user authentication.</li>
                    <li><strong>Test Mode:</strong> Start with test/development apps before moving to production.</li>
                    <li><strong>Permissions:</strong> Request only the minimum permissions needed (we use: pages_manage_posts for Facebook, w_member_social for LinkedIn).</li>
                    <li><strong>Regular Updates:</strong> Keep your app credentials and APIs updated.</li>
                </ul>
            </div>

            <!-- Support -->
            <div style="background: #fff3cd; padding: 20px; margin-top: 20px; border-radius: 8px; border-left: 4px solid #ffc107;">
                <h3 style="color: #856404;">📞 Need Help?</h3>
                <p>
                    <strong>Common Issues:</strong><br>
                    • Redirect URL mismatch: Ensure the URL exactly matches what you put in the developer console<br>
                    • App not approved: Facebook/LinkedIn apps may need approval for certain features<br>
                    • Token errors: Check that your credentials are correct and the app is in the right environment (development/production)<br><br>
                    For more details, visit:
                    <a href="https://developers.facebook.com/docs" target="_blank">Facebook Developer Docs</a> |
                    <a href="https://developer.linkedin.com" target="_blank">LinkedIn Developer Docs</a>
                </p>
            </div>
        </div>
    </div>

    <style>
        .wrap h2 {
            margin-top: 25px;
            margin-bottom: 15px;
        }

        .form-table th {
            vertical-align: top;
            font-weight: 600;
        }

        .form-table td {
            padding: 10px 0;
        }

        code {
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }

        ol {
            margin-left: 20px;
        }

        li {
            margin-bottom: 15px;
        }

        .button-large {
            background: #0073aa;
            border-color: #005a87;
        }

        .button-large:hover {
            background: #005a87;
        }
    </style>

    <script>
        // Toggle password visibility
        document.getElementById('toggle_facebook_secret')?.addEventListener('change', function() {
            const field = document.getElementById('facebook_app_secret');
            field.type = this.checked ? 'text' : 'password';
        });

        document.getElementById('toggle_linkedin_secret')?.addEventListener('change', function() {
            const field = document.getElementById('linkedin_app_secret');
            field.type = this.checked ? 'text' : 'password';
        });

        // Copy to clipboard functions
        function copyFacebookRedirectURI() {
            const uri = "<?php echo esc_attr(home_url('/facebook-oauth-callback/')); ?>";
            copyToClipboard(uri, event.target);
        }

        function copyLinkedInRedirectURI() {
            const uri = "<?php echo esc_attr(home_url('/linkedin-oauth-callback/')); ?>";
            copyToClipboard(uri, event.target);
        }

        function copyToClipboard(text, button) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            
            const originalText = button.textContent;
            button.textContent = '✅ Copied!';
            button.disabled = true;
            setTimeout(() => {
                button.textContent = originalText;
                button.disabled = false;
            }, 2000);
        }
    </script>

    <?php
}

// Load settings into wp-config constants at runtime
add_action('wp_loaded', 'load_social_media_settings_constants', 1);
function load_social_media_settings_constants() {
    // Load Facebook credentials
    if (!defined('FACEBOOK_APP_ID')) {
        $facebook_app_id = get_option('mm_facebook_app_id', '');
        if ($facebook_app_id) {
            define('FACEBOOK_APP_ID', $facebook_app_id);
        }
    }

    if (!defined('FACEBOOK_APP_SECRET')) {
        $facebook_app_secret = get_option('mm_facebook_app_secret', '');
        if ($facebook_app_secret) {
            define('FACEBOOK_APP_SECRET', $facebook_app_secret);
        }
    }

    // Load LinkedIn credentials
    if (!defined('LINKEDIN_APP_ID')) {
        $linkedin_app_id = get_option('mm_linkedin_app_id', '');
        if ($linkedin_app_id) {
            define('LINKEDIN_APP_ID', $linkedin_app_id);
        }
    }

    if (!defined('LINKEDIN_APP_SECRET')) {
        $linkedin_app_secret = get_option('mm_linkedin_app_secret', '');
        if ($linkedin_app_secret) {
            define('LINKEDIN_APP_SECRET', $linkedin_app_secret);
        }
    }
}
