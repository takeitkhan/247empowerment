window.mmUnreadCount = parseInt(jQuery('#notif-unread-count').text() || 0, 10);
window.mmPendingLoginSound = false; // ✅ REQUIRED

jQuery(document).ready(function ($) {
    const $badge = $('#notificationDropdown > button');
    const $container = $('#notificationList');

    /* =========================================================
     * UNIFIED GAMIFICATION ACTION DISPATCHER
     * ======================================================= */

    function triggerGamificationAction($el) {
        const actionKey = $el.data('action-key');

        if (!actionKey) return;

        // Prevent double firing
        if ($el.data('mm-fired')) return;
        $el.data('mm-fired', true);

        $.post(notificationsData.ajaxurl, {
            action: 'mm_gamification_action',
            action_key: actionKey,
            security: notificationsData.nonce
        }, function (response) {

            if (response.success && response.data && response.data.notification) {
                mmPushNotification(response.data.notification);
            }

        }).always(function () {
            // Allow re-fire only if needed later
            setTimeout(() => {
                $el.removeData('mm-fired');
            }, 1000);
        });
    }

    // Click-based actions (buttons, links)
    $(document).on('click', '.mm-action', function (e) {
        e.preventDefault();
        triggerGamificationAction($(this));
    });

    // Change-based actions (inputs, selects)
    $(document).on('change', '.mm-action-input', function () {
        triggerGamificationAction($(this));
    });

    /* =========================================================
     * NOTIFICATION DROPDOWN UI
     * ======================================================= */

    function positionContainer() {
        if (!$badge.length || !$container.length) return;

        const offset = $badge.offset();
        $container.css({
            top: offset.top - $container.outerHeight() - 10,
            left: offset.left
        });
    }


    $(window).on('resize', function () {
        if ($container.is(':visible')) positionContainer();
    });

    /* =========================================================
     * MARK SINGLE NOTIFICATION AS READ
     * ======================================================= */

    $(document).on('click', '.mark-read', function (e) {
        e.preventDefault();

        const notifId = $(this).data('id');
        const $item = $(this).closest('[data-id]');

        $.post(notificationsData.ajaxurl, {
            action: 'mark_notification_read',
            notification_id: notifId,
            security: notificationsData.nonce
        }, function (response) {
            if (response.success) {
                $item.remove();
            }
        });
    });

    /* =========================================================
     * MARK ALL AS READ
     * ======================================================= */

    $(document).on('click', '.mark-all-read', function (e) {
        e.preventDefault();

        const $btn = $(this);
        $btn.text('Marking...');

        $.post(notificationsData.ajaxurl, {
            action: 'mark_all_notifications_read',
            security: notificationsData.nonce
        }, function (response) {

            if (response.success) {

                // reset counter
                window.mmUnreadCount = 0;

                // remove badge
                $('#notif-unread-count').remove();

                // remove unread class
                $('#notificationList .unread').removeClass('unread');

                // remove button
                $('.mark-all-read').remove();
            }

            $btn.text('All read');
        }).fail(function () {
            $btn.text('Mark all as read');
        });
    });


    /* =========================================================
     * CLEAR ALL NOTIFICATIONS
     * ======================================================= */

    $(document).on('click', '.clear-notifications', function (e) {
        e.preventDefault();

        $.post(notificationsData.ajaxurl, {
            action: 'clear_all_notifications',
            security: notificationsData.nonce
        }, function (response) {
            if (response.success) {
                $container.html(
                    '<div class="p-3 text-muted text-center">No notifications found.</div>'
                );
                $('.notif-bubble').remove();
            }
        });
    });

});

/* =========================================================
 * NOTIFICATION SOUND SETUP
 * ======================================================= */

let mmNotificationAudio = null;
let mmAudioUnlocked = false;

// Initialize audio immediately
function initializeNotificationAudio() {
    if (!mmNotificationAudio && typeof notificationsData !== 'undefined' && notificationsData.sound) {
        try {
            mmNotificationAudio = new Audio(notificationsData.sound);
            mmNotificationAudio.volume = 0.5;
            mmNotificationAudio.preload = 'auto';
            console.log('✅ Notification audio initialized');
            return true;
        } catch (error) {
            console.error('❌ Failed to initialize notification audio:', error);
            return false;
        }
    }
    return false;
}

// Play notification sound with fallback approach
function playNotificationSound() {
    if (!mmNotificationAudio) {
        initializeNotificationAudio();
    }

    if (mmNotificationAudio) {
        mmNotificationAudio.currentTime = 0;
        
        // Modern approach with promise
        const playPromise = mmNotificationAudio.play();
        
        if (playPromise !== undefined) {
            playPromise
                .then(() => {
                    console.log('✅ Notification sound played successfully');
                    mmAudioUnlocked = true;
                })
                .catch(err => {
                    console.warn('⚠️ Sound play blocked, waiting for user interaction:', err.name);
                    // Sound blocked by browser, will attempt after user interaction
                    window.mmPendingLoginSound = true;
                });
        } else {
            // Older browsers
            console.log('📻 Using legacy audio play method');
            mmAudioUnlocked = true;
        }
    }
}

// Unlock audio and play any pending sound after first user interaction
jQuery(document).one('click keydown touchstart', function () {
    console.log('🔓 Audio context unlocked by user interaction');
    
    // Initialize if not done yet
    if (!mmNotificationAudio) {
        initializeNotificationAudio();
    }
    
    mmAudioUnlocked = true;

    // 🔇 Disabled: Notification sound playing on every click
    // if (window.mmPendingLoginSound && mmNotificationAudio) {
    //     console.log('🔊 Playing delayed login notification sound');
    //     playNotificationSound();
    //     window.mmPendingLoginSound = false;
    // }
});

// Try to initialize audio on page load
jQuery(document).ready(function() {
    initializeNotificationAudio();
    
    // 🔇 Disabled: Playing sound on page load
    // if (window.mmPendingLoginSound) {
    //     setTimeout(() => {
    //         console.log('📌 Attempting to play pending login sound');
    //         playNotificationSound();
    //     }, 500);
    // }
});

/* =========================================================
 * GLOBAL PUSH NOTIFICATION HANDLER
 * ======================================================= */

window.mmPushNotification = function (notification) {
    if (!notification || !notification.message) {
        console.warn('⚠️ Invalid notification data');
        return;
    }

    console.log('📬 New notification received:', notification);

    const $list = jQuery('#notificationList');
    const $count = jQuery('#notif-unread-count');

    // � Disabled: Notification sound on new notification
    // console.log('🔊 Notification arrived - attempting to play sound');
    // playNotificationSound();

    // ✅ Increment unread counter
    window.mmUnreadCount++;

    if ($count.length) {
        $count.text(window.mmUnreadCount);
    } else {
        jQuery('.notification-png').after(
            `<span class="notif-bubble" id="notif-unread-count">${window.mmUnreadCount}</span>`
        );
    }

    // Build notification HTML
    const html = `
        <div class="d-flex align-items-center border-bottom border-2 border-light gap-3 py-2 unread">
            <div class="d-flex align-items-center gap10">
                <img src="${notificationsData.circleIcon}">
                <div class="position-relative img44">
                    <img src="${notificationsData.userImg}" class="rounded-circle w-100 h-100 object-fit-cover">
                    <img class="position-absolute active-icon" src="${notificationsData.activeIcon}">
                </div>
            </div>
            <div class="d-flex flex-column post-user">
                <span class="p_name fs16">${notification.message}</span>
                <span class="mb-0 text-blue-color fs14">just now</span>
            </div>
        </div>
    `;

    $list.find('.text-muted').remove();
    $list.prepend(html);
};


jQuery(document).ready(function () {

    const $badge = jQuery('#notif-unread-count');

    // If unread exists on load, assume login notification
    if ($badge.length && window.mmUnreadCount > 0) {
        console.log('🔔 Login notification detected, unread count:', window.mmUnreadCount);
        window.mmPendingLoginSound = true;
    } else {
        console.log('📊 Notification status - Badge exists:', $badge.length > 0, 'Unread count:', window.mmUnreadCount);
    }
});
