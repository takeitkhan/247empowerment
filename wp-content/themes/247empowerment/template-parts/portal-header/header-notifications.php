<?php
$notifications_instance = Notifications::getInstance();
$user_id = get_current_user_id();

$unread_count = $notifications_instance->getUnreadCount($user_id);
$all_notifications = $notifications_instance->getNotifications($user_id);

// Sort latest first
usort($all_notifications, function ($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Limit dropdown notifications
$limit = 5;
$notifications_to_show = array_slice($all_notifications, 0, $limit);
?>

<div class="dropdown" id="notificationDropdown">
    <button
        class="position-relative bg-supporting rounded-circle img44 btn-custom btn-focus"
        type="button"
        data-bs-toggle="dropdown"
        aria-expanded="false">
        <img class="object-fit-contain notification-png" src="<?= esc_url(get_template_directory_uri() . '/assets/img/nd/notification.png'); ?>" alt="">
        <?php if ($unread_count > 0) : ?>
            <span class="notif-bubble" id="notif-unread-count">
                <?= esc_html($unread_count); ?>
            </span>
        <?php endif; ?>
    </button>

    <div class="shadow-lg border-0 rounded-3 dropdown-menu notification-width dropdown-menu-end custom-card">
        <ul class="px-4 py-3" id="notificationList">
            <div class="d-flex align-items-center justify-content-between mb-2 pb-3 border-bottom border-light">
                <p class="mb-0 text-black fw-bold">Notifications</p>
                <?php if ($unread_count > 0) : ?>
                    <span class="text-blue-color fs14 mark-all-read" style="cursor:pointer;">Mark all as read</span>
                <?php endif; ?>
            </div>

            <?php if (!empty($notifications_to_show)) : ?>
                <?php foreach ($notifications_to_show as $notif) :
                    $is_unread = empty($notif['read']);
                    $created_time = human_time_diff(strtotime($notif['created_at']), current_time('timestamp')) . ' ago';
                    $user_img = get_template_directory_uri() . '/assets/img/loggedin_images/banner.jpg';
                ?>
                    <div class="d-flex align-items-center border-bottom border-1 border-light gap-3 py-1 px-0 <?= $is_unread ? 'unread' : ''; ?>">
                        <div class="d-flex align-items-center">
                            <?php if ($is_unread) : ?>
                                <img src="<?= esc_url(get_template_directory_uri() . '/assets/img/nd/circle-notification.png'); ?>" alt="">
                            <?php else : ?>
                                <span class="w14__X"></span>
                            <?php endif; ?>
                            <div class="position-relative img44">
                                <img src="<?= esc_url($user_img); ?>" class="rounded-circle w-100 h-100 object-fit-cover" alt="Profile">
                                <img class="position-absolute active-icon" src="<?= esc_url(get_template_directory_uri() . '/assets/img/nd/active_icon.png'); ?>" alt="Active">
                            </div>
                        </div>
                        <div class="d-flex flex-column post-user">
                            <span class="p_name fs16"><?= esc_html($notif['message']); ?></span>
                            <span class="mb-0 text-blue-color fs14"><?= esc_html($created_time); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- See all link -->
                <div class="mt-3 pt-2 text-center">
                    <a href="<?php echo site_url('/notifications/'); ?>" class="text-blue-color text-decoration-none fs14">
                        See all notifications
                    </a>
                </div>

            <?php else : ?>
                <p class="py-3 text-muted text-center">No notifications yet.</p>
            <?php endif; ?>
        </ul>
    </div>
</div>