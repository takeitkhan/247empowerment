<?php

/**
 * Template Name: All Notifications
 */

if (!is_user_logged_in()) {
    wp_redirect(home_url('/signin'));
    exit;
}

get_header_based_on_login();

$notifications_instance = Notifications::getInstance();
$current_user = wp_get_current_user();

// Get current logged-in user ID (used as a fallback if no slug is provided)
$current_user_id = get_current_user_id();

// 1. Get the user slug from the query variable
$user_slug = get_query_var('user_profile');

// 2. Determine the target user
if ($user_slug) {
    // If a slug is present, try to get the user by their slug (login or nicename)
    $user = get_user_by('slug', $user_slug);
} else {
    // If no slug, fall back to the currently logged-in user
    $user = get_user_by('ID', $current_user_id);
}

// 3. Instantiate the UserProfileData class and get the profile array
if ($user) {
    // We pass the WP_User object to the class constructor, or the ID/slug depending on the class's constructor.
    // Given your original line: $profile = (new UserProfileData($user_slug))->getProfile();
    // We'll update it to pass the $user object for better data handling, assuming the class supports it.
    // If the class REQUIRES a slug, use $user_slug or $user->user_login.

    // Option A: If UserProfileData takes a WP_User object (Recommended)
    $profile_data_instance = new UserProfileData($user);

    // Option B: If UserProfileData only takes the slug (Sticking closer to your original code)
    // Use the slug if present, otherwise use the current user's login.
    $target_identifier = $user_slug ? $user_slug : $user->user_login;
    $profile_data_instance = new UserProfileData($target_identifier);

    // Get the profile array
    $profile = $profile_data_instance->getProfile();
} else {
    // Set variables to null if no user could be determined
    $user = null;
    $profile = null;
}

// Pagination parameters
$paged = max(1, get_query_var('paged', 1));
$per_page = 10; // notifications per page

$all_notifications = $notifications_instance->getNotifications($current_user_id);

// Sort latest first
usort($all_notifications, function ($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Pagination slice
$total_notifications = count($all_notifications);
$total_pages = ceil($total_notifications / $per_page);
$offset = ($paged - 1) * $per_page;
$notifications_to_show = array_slice($all_notifications, $offset, $per_page);
?>
<div class="container profile-page pt20">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3">
            <?php get_template_part('template-custom/auth/common-parts/editprofilemenu', null, ['profile' => $profile]); ?>
            <?php get_template_part('template-custom/auth/profile-parts/navlink', null, ['profile' => $profile]); ?>
        </div>
        <div class="mb-0 rounded-end-0 col-lg-9">
            <div class="bg-white custom-card post-search">
                <div class="gap-3 post-row">
                    <div>
                        <h5 class="pb-4 text-start portal-title">All Notifications</h5>
                    </div>
                    <?php if (!empty($notifications_to_show)) : ?>
                        <div class="list-group">
                            <?php foreach ($notifications_to_show as $notif) :
                                $is_unread = empty($notif['read']);
                                $created_time = human_time_diff(strtotime($notif['created_at']), current_time('timestamp')) . ' ago';
                            ?>
                                <div class="list-group-item list-group-item-action <?= $is_unread ? 'fw-bold bg-light' : ''; ?>">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span><?= esc_html($notif['message']); ?></span>
                                        <small class="text-muted"><?= esc_html($created_time); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination -->
                        <nav aria-label="Notification pagination" class="mt-4">
                            <ul class="justify-content-center pagination">
                                <?php if ($paged > 1) : ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?php echo get_pagenum_link($paged - 1); ?>">Previous</a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $total_pages; $i++) : ?>
                                    <li class="page-item <?= ($i === $paged) ? 'active' : ''; ?>">
                                        <a class="page-link" href="<?php echo get_pagenum_link($i); ?>"><?= $i; ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($paged < $total_pages) : ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?php echo get_pagenum_link($paged + 1); ?>">Next</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>

                    <?php else : ?>
                        <p class="text-muted text-center">You have no notifications.</p>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</div>
<?php get_footer_based_on_login(); ?>