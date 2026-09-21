<?php
/**
 * Template Name: Zoom Debug Page
 * Template Post Type: page
 * 
 * Debug page for troubleshooting Zoom issues
 */

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url());
    exit;
}

get_header_based_on_login();

$current_user = wp_get_current_user();
$user_id = get_current_user_id();

?>

<div class="mt-5 mb-5 container">
    <div class="row">
        <div class="mx-auto col-lg-8">
            <div class="card">
                <div class="bg-primary text-white card-header">
                    <h3 class="mb-0">Zoom Debug Information</h3>
                </div>
                <div class="card-body">
                    <h4 class="mb-3">User Information</h4>
                    <table class="table table-sm">
                        <tr>
                            <td><strong>User ID:</strong></td>
                            <td><?php echo esc_html($user_id); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Username:</strong></td>
                            <td><?php echo esc_html($current_user->user_login); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Email:</strong></td>
                            <td><?php echo esc_html($current_user->user_email); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Roles:</strong></td>
                            <td><?php echo esc_html(implode(', ', $current_user->roles)); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Capabilities Check (manage_options):</strong></td>
                            <td><?php echo current_user_can('manage_options') ? '✅ YES' : '❌ NO'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Capabilities Check (edit_posts):</strong></td>
                            <td><?php echo current_user_can('edit_posts') ? '✅ YES' : '❌ NO'; ?></td>
                        </tr>
                    </table>

                    <h4 class="mt-4 mb-3">Guide Completion Status</h4>
                    <table class="table table-sm">
                        <?php
                        $interests = get_user_meta($user_id, 'user_categories_priority', true);
                        $about = get_user_meta($user_id, 'guide_about', true) ?: get_user_meta($user_id, 'about_me', true);
                        $title = get_user_meta($user_id, 'guide_title', true) ?: get_user_meta($user_id, 'designation', true);
                        ?>
                        <tr>
                            <td><strong>Interests Selected:</strong></td>
                            <td><?php echo is_array($interests) && count($interests) > 0 ? '✅ YES (' . count($interests) . ')' : '❌ NO'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>About Me Filled:</strong></td>
                            <td><?php echo !empty($about) ? '✅ YES' : '❌ NO'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Title/Designation Filled:</strong></td>
                            <td><?php echo !empty($title) ? '✅ YES' : '❌ NO'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Guide Complete:</strong></td>
                            <td>
                                <?php
                                $guide_complete = is_array($interests) && count($interests) > 0 && !empty($about) && !empty($title);
                                echo $guide_complete ? '✅ YES' : '❌ NO (will be redirected to /guide/ on other pages)';
                                ?>
                            </td>
                        </tr>
                    </table>

                    <h4 class="mt-4 mb-3">Zoom Connection Status</h4>
                    <table class="table table-sm">
                        <?php
                        $zoom_connected = function_exists('zoom_is_connected') ? zoom_is_connected($user_id) : false;
                        $zoom_token = get_user_meta($user_id, '_zoom_access_token', true);
                        $zoom_scopes = get_user_meta($user_id, '_zoom_scopes', true);
                        ?>
                        <tr>
                            <td><strong>Zoom Connected:</strong></td>
                            <td><?php echo $zoom_connected ? '✅ YES' : '❌ NO'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Access Token Exists:</strong></td>
                            <td><?php echo !empty($zoom_token) ? '✅ YES' : '❌ NO'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Scopes Granted:</strong></td>
                            <td><?php echo !empty($zoom_scopes) ? '✅ ' . esc_html(substr($zoom_scopes, 0, 50)) : '❌ NO'; ?></td>
                        </tr>
                    </table>

                    <h4 class="mt-4 mb-3">Class/Function Checks</h4>
                    <table class="table table-sm">
                        <tr>
                            <td><strong>UserProfileData Class:</strong></td>
                            <td><?php echo class_exists('UserProfileData') ? '✅ EXISTS' : '❌ NOT FOUND'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Zoom_Menu_Walker Class:</strong></td>
                            <td><?php echo class_exists('Zoom_Menu_Walker') ? '✅ EXISTS' : '❌ NOT FOUND'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>zoom_is_connected Function:</strong></td>
                            <td><?php echo function_exists('zoom_is_connected') ? '✅ EXISTS' : '❌ NOT FOUND'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>get_header_based_on_login Function:</strong></td>
                            <td><?php echo function_exists('get_header_based_on_login') ? '✅ EXISTS' : '❌ NOT FOUND'; ?></td>
                        </tr>
                    </table>

                    <h4 class="mt-4 mb-3">Template Test</h4>
                    <div class="alert alert-info">
                        <p><strong>Testing template parts...</strong></p>
                        <ul>
                            <?php
                            try {
                                $profile = (new UserProfileData($current_user->ID))->getProfile();
                                echo '<li>✅ UserProfileData loading successful</li>';
                            } catch (Exception $e) {
                                echo '<li>❌ UserProfileData error: ' . esc_html($e->getMessage()) . '</li>';
                            }

                            $template_path = get_template_directory() . '/template-custom/auth/common-parts/editprofilemenu.php';
                            if (file_exists($template_path)) {
                                echo '<li>✅ editprofilemenu.php exists</li>';
                            } else {
                                echo '<li>❌ editprofilemenu.php NOT FOUND</li>';
                            }
                            ?>
                        </ul>
                    </div>

                    <div class="mt-4 alert alert-warning">
                        <p><strong>Note:</strong> This is a debug page. Please compare your information with the admin user account to identify differences.</p>
                    </div>

                    <a href="<?php echo esc_url(home_url('/my-zoom-meetings/')); ?>" class="mt-3 btn btn-primary">Go to My Zoom Meetings</a>
                    <a href="<?php echo esc_url(home_url('/connect-zoom-account/')); ?>" class="mt-3 btn btn-secondary">Go to Connect Zoom Account</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer_based_on_login(); ?>
