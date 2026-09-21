<?php
$notifications_instance = Notifications::getInstance();
$user_id = get_current_user_id();

// Get unread count & all notifications
$unread_count = $notifications_instance->getUnreadCount($user_id);
$all_notifications = $notifications_instance->getNotifications($user_id);

// echo '<pre>';
// print_r($all_notifications);
// echo '</pre>';

// Sort latest first
usort($all_notifications, function ($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
?>

<div class="dropdown">
    <button
        class="position-relative bg-supporting rounded-circle img44 btn-custom btn-focus"
        type="button"
        data-bs-toggle="dropdown"
        data-bs-auto-close="outside"
        aria-expanded="false">
        <img class="object-fit-contain notification-png" src="<?= esc_url(get_template_directory_uri() . '/assets/img/nd/notification.png'); ?>" alt="">

        <?php if ($unread_count > 0) : ?>
            <span class="notif-bubble"><?= esc_html($unread_count); ?></span>
        <?php endif; ?>
    </button>

    <div class="shadow border-0 rounded-3 dropdown-menu notification-width dropdown-menu-end custom-card">
        <ul class="p-0">
            <div class="d-flex align-items-center justify-content-between pb-4">
                <p class="mb-0 text-black fw-bold">Notifications</p>
                <?php if ($unread_count > 0) : ?>
                    <span class="text-blue-color fs14 mark-all-read" style="cursor:pointer;">Mark all as read</span>
                <?php endif; ?>
            </div>

            <?php if (!empty($all_notifications)) : ?>
                <?php
                // Sort latest first
                usort($all_notifications, function ($a, $b) {
                    return strtotime($b['created_at']) - strtotime($a['created_at']);
                });

                foreach ($all_notifications as $notif) :
                    $is_unread = empty($notif['read']);
                    $created_time = human_time_diff(strtotime($notif['created_at']), current_time('timestamp')) . ' ago';
                    $user_img = get_template_directory_uri() . '/assets/img/loggedin_images/banner.jpg';
                ?>
                    <div class="d-flex align-items-center gap-3 pb-3 <?= $is_unread ? 'unread' : ''; ?>">
                        <div class="d-flex align-items-center gap10">
                            <?php if ($is_unread) : ?>
                                <img src="<?= esc_url(get_template_directory_uri() . '/assets/img/nd/circle-notification.png'); ?>" alt="">
                            <?php else : ?>
                                <span class="w14"></span>
                            <?php endif; ?>
                            <div class="position-relative img44">
                                <img src="<?= esc_url($user_img); ?>" class="rounded-circle w-100 h-100 object-fit-cover" alt="Profile">
                                <img class="position-absolute active-icon" src="<?= esc_url(get_template_directory_uri() . '/assets/img/nd/active_icon.png'); ?>" alt="">
                            </div>
                        </div>
                        <div class="d-flex flex-column gap-2 post-user">
                            <span class="p_name"><?= esc_html($notif['message']); ?></span>
                            <p class="mb-0 text-blue-color fs14"><?= esc_html($created_time); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <p class="text-muted text-center">No notifications yet.</p>
            <?php endif; ?>
        </ul>
    </div>
</div>