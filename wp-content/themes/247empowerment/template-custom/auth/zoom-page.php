<?php
/**
 * Template Name: Zoom Integration Page
 * Template Post Type: page
 * 
 * Universal Zoom Integration Page Template
 * Dynamically loads shortcodes based on page slug
 */

// Register fatal error handler to catch any unexpected errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
        error_log('[ZOOM PAGE FATAL] ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line']);
        // Output is already sent at this point, but at least log it
    }
});

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url());
    exit;
}

get_header_based_on_login();

$current_user = wp_get_current_user();
$user_id = get_current_user_id();
$page_slug = basename(get_permalink());
// Clean up trailing slashes
$page_slug = trim($page_slug, '/');

// Dynamic page title mapping
$page_titles = [
    'connect-zoom-account' => 'Connect Zoom Account',
    'zoom-meetings' => 'My Zoom Meetings',
    'zoom-booking' => 'Book a Meeting',
    'zoom-search' => 'Search Meetings',
    'zoom-contacts' => 'Manage Contacts',
    'zoom-calendar' => 'Meeting Calendar',
    'zoom-all-meetings' => 'All Meetings & Archive',
    'zoom-zak-token' => 'Web SDK Token',
    'zoom-my-bookings' => 'My Bookings',
    'zoom-members' => 'Member Directory',
    'zoom-cancel-meeting' => 'Cancel Meeting',
    'zoom-reschedule-meeting' => 'Reschedule Meeting',
    'zoom-meeting-details' => 'Meeting Details',
];

$page_title = $page_titles[$page_slug] ?? 'Zoom Integration';
$page_subtitle = get_post_meta(get_the_ID(), '_page_subtitle', true) ?? 'Manage your Zoom meetings and integrations';

// Mapping of page slugs to shortcodes
$shortcode_mapping = [
    'connect-zoom'              => '[zoom_connect_button]',
    'connect-zoom-account'      => '[zoom_connect_button]',
    'my-zoom-meetings'          => '[zoom_all_meetings]',
    'zoom-meetings'             => '[zoom_all_meetings]',
    'book-meeting'              => '[zoom_book_appointment]',
    'zoom-book'                 => '[zoom_book_appointment]',
    'search-meetings'           => '[zoom_search_meetings]',
    'zoom-search'               => '[zoom_search_meetings]',
    'zoom-contacts'             => '[zoom_show_contacts]',
    'cancel-meeting'            => '[zoom_cancel_meeting]',
    'reschedule-meeting'        => '[zoom_reschedule_meeting]',
    'meeting-details'           => '[zoom_meeting_details]',
    'zoom-token'                => '[zoom_zak_token]',
];

// Get custom shortcode from page meta (if set)
$custom_shortcode = get_post_meta(get_the_ID(), '_zoom_shortcode', true);

// Determine which shortcode to display
$shortcode_to_display = $custom_shortcode ?: ($shortcode_mapping[$page_slug] ?? '');

// Get default page content
$post_content = get_the_content();
?>

<div class="pt-4 pb-4 container profile-page">
    <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-lg-3">
            <?php 
            try {
                if (!class_exists('UserProfileData')) {
                    throw new Exception('UserProfileData class not found');
                }
                $profile = (new UserProfileData($current_user->ID))->getProfile();
                error_log('[ZOOM PAGE] Profile retrieved successfully for user ' . $current_user->ID);
                
                // Check if template part exists before loading
                $template_path = get_template_directory() . '/template-custom/auth/common-parts/editprofilemenu.php';
                if (!file_exists($template_path)) {
                    throw new Exception('Template part not found: editprofilemenu.php');
                }
                
                get_template_part('template-custom/auth/common-parts/editprofilemenu', null, ['profile' => $profile]);
            } catch (Exception $e) {
                $error_msg = $e->getMessage() ?: 'Unknown error in sidebar rendering';
                error_log('[ZOOM SIDEBAR ERROR] ' . $error_msg . ' - ' . $e->getFile() . ':' . $e->getLine());
                echo '<p style="color: red;"><strong>Sidebar Error:</strong> ' . esc_html($error_msg) . '</p>';
            } catch (Throwable $t) {
                error_log('[ZOOM SIDEBAR THROWABLE] ' . $t->getMessage() . ' - ' . $t->getFile() . ':' . $t->getLine());
                echo '<p style="color: red;"><strong>Sidebar Error (Throwable):</strong> ' . esc_html($t->getMessage()) . '</p>';
            }
            ?>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">
            <!-- Header Section -->
            <div class="bg-white mb-4 p-4 border-bottom rounded">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h3 class="mb-2">
                            <i class="bi bi-camera-video"></i>
                            <?php echo esc_html($page_title); ?>
                        </h3>
                        <p class="mb-0 text-muted">
                            <?php echo esc_html($page_subtitle); ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="bg-white p-4 rounded">
                <?php
                try {
                    $user_roles = $current_user->roles ?? [];
                    $is_subscriber = in_array('subscriber', $user_roles);
                    $is_zoom_connected = !empty(get_user_meta($user_id, 'zoom_access_token', true));

                    // 🛠️ ১. সাবস্ক্রাইবার ইউজারদের জন্য কাস্টম এরিয়া (কোনো শর্টকোড ক্র্যাশ করতে পারবে না)
                    if ($is_subscriber) {
                        
                        if ($page_slug === 'connect-zoom-account' || $page_slug === 'connect-zoom') {
                            
                            echo '<div class="text-center p-5 zoom-custom-connect">';
                            echo '<h4 class="mb-3">🔗 Connect Your Zoom Account</h4>';
                            echo '<p class="text-muted mb-4">Please authorize and connect your Zoom account to start scheduling meetings.</p>';
                            
                            // ওঅথ (OAuth) লিঙ্ক জেনারেট করার চেষ্টা (প্লাগইনের মেথড অনুযায়ী)
                            // আপনার জুম প্লাগইন যদি 'Zoom_OAuth' বা এমন কিছু ব্যবহার করে, তবে তার লিঙ্ক এখানে দিন।
                            // আপাতত এটি একটি ডাইনামিক বাটন যা ক্র্যাশ ছাড়াই নিরাপদে রেন্ডার হবে।
                            if (class_exists('Zoom_Conference_Helper') || function_exists('zoom_get_login_url')) {
                                // প্লাগইনের ফাংশন থাকলে তার লিঙ্ক বসবে
                                $zoom_auth_url = function_exists('zoom_get_login_url') ? zoom_get_login_url() : '#';
                            } else {
                                // কাস্টম রিডাইরেক্ট লিঙ্ক (ওয়ার্ডপ্রেস জুম প্লাগইনের ডিফল্ট ওঅথ স্ট্রাকচার)
                                $zoom_auth_url = admin_url('admin-post.php?action=zoom_connect'); 
                            }

                            echo '<a href="' . esc_url($zoom_auth_url) . '" class="btn btn-primary btn-lg px-5 py-3 rounded-pill shadow-sm">';
                            echo '<i class="bi bi-camera-video-fill me-2"></i> Connect Zoom via OAuth';
                            echo '</a>';
                            echo '</div>';
                            
                        } else {
                            // কানেক্ট পেজ ছাড়া অন্য পেজে আসলে
                            if (!$is_zoom_connected) {
                                echo '<div class="text-center p-5">';
                                echo '<h4 class="text-warning mb-3">⚠️ Zoom Account Not Connected</h4>';
                                echo '<p class="text-muted mb-4">You need to connect your Zoom account first to view this page.</p>';
                                echo '<a href="' . site_url('/connect-zoom-account/') . '" class="btn btn-primary">Go to Connect Account</a>';
                                echo '</div>';
                            } else {
                                // জুম কানেক্টেড থাকলে নিরাপদে শর্টকোড রান করার চেষ্টা
                                if (!empty($shortcode_to_display)) {
                                    try {
                                        echo do_shortcode($shortcode_to_display);
                                    } catch (Throwable $inst_t) {
                                        echo '<div class="alert alert-warning">Unable to load this Zoom section. Please contact support.</div>';
                                    }
                                }
                            }
                        }

                    } else {
                        // 🛠️ ২. এডমিন বা অন্যান্য রোলের জন্য আগের মতোই ডাইনামিকলি প্লাগইন লোড হবে
                        if (!empty($shortcode_to_display)) {
                            echo do_shortcode($shortcode_to_display);
                        } elseif (!empty($post_content)) {
                            echo apply_filters('the_content', $post_content);
                        } else {
                            echo '<div class="zoom-sections">';
                            echo '<h4 class="mb-4">🔗 Your Zoom Account</h4>';
                            echo do_shortcode('[zoom_connect_button]');
                            echo '<hr class="my-5">';
                            echo '<h4 class="mb-4">📅 Upcoming Meetings</h4>';
                            echo do_shortcode('[zoom_all_meetings]');
                            echo '</div>';
                        }
                    }

                } catch (Exception $e) {
                    error_log('[ZOOM PAGE ERROR] ' . $e->getMessage());
                    echo '<div class="alert alert-danger">An exception occurred.</div>';
                } catch (Throwable $t) {
                    error_log('[ZOOM PAGE THROWABLE] ' . $t->getMessage());
                    echo '<div class="alert alert-danger">A structural system error occurred.</div>';
                }
                ?>
            </div>

            <!-- Help Link -->
            <div class="mt-5 pt-4 border-top text-center">
                <p class="text-muted">Need help? <a href="<?php echo site_url('/zoom-help/'); ?>" class="text-decoration-none">Visit our Help Center</a></p>
            </div>
        </div>
    </div>
</div>

<style>
/* Zoom Page Styles */
</style>

<?php get_footer_based_on_login(); ?>
