jQuery(document).ready(function ($) {
    const $badge = $("#notificationBadge");
    const $container = $("#toastContainer");

    // Position toast container above the badge
    function positionContainer() {
        const offset = $badge.offset();
        const height = $badge.outerHeight();
        $container.css({
            top: offset.top - $container.outerHeight() - 10, // 10px above badge
            left: offset.left
        });
    }

    // Toggle on badge click
    $badge.on("click", function () {
        positionContainer();
        $container.fadeToggle("fast");
    });

    // Optional: reposition on window resize
    $(window).on("resize", function () {
        if ($container.is(":visible")) positionContainer();
    });

    // Mark single notification as read
    $(document).on("click", ".mark-read", function (e) {
        e.preventDefault();
        let notifId = $(this).data("id");

        $.post(notificationsData.ajaxurl, {
            action: "mark_notification_read",
            notification_id: notifId,
            security: notificationsData.nonce
        }, function (response) {
            if (response.success) {
                $(`[data-id="${notifId}"]`).remove();
            }
        });
    });

    // Mark all read
    $(document).on("click", ".mark-all-read", function (e) {
        e.preventDefault();
        $.post(notificationsData.ajaxurl, {
            action: "mark_all_notifications_read",
            security: notificationsData.nonce
        }, function (response) {
            if (response.success) {
                $container.find(".card-body").html('<div class="p-3 text-muted text-center">No notifications found.</div>');
            }
        });
    });

    // Clear all
    $(document).on("click", ".clear-notifications", function (e) {
        e.preventDefault();
        $.post(notificationsData.ajaxurl, {
            action: "clear_all_notifications",
            security: notificationsData.nonce
        }, function (response) {
            if (response.success) {
                $container.find(".card-body").html('<div class="p-3 text-muted text-center">No notifications found.</div>');
            }
        });
    });
});


jQuery(document).ready(function($) {

    // MARK ALL AS READ
    $('.mark-all-read').on('click', function(e) {
        e.preventDefault();

        var $this = $(this);

        $.ajax({
            url: notificationsData.ajaxurl,
            type: 'POST',
            data: {
                action: 'mark_all_notifications_read',
                security: notificationsData.nonce,
            },
            beforeSend: function() {
                $this.text('Marking...'); // Optional loading state
            },
            success: function(response) {
                if(response.success) {
                    // Remove unread bubble
                    $('.notif-bubble').remove();
                    // Update dropdown items: remove "unread" class
                    $('.dropdown-menu .unread').removeClass('unread');
                    // Reset the mark-all text
                    $this.text('All read');
                }
            },
            error: function() {
                $this.text('Mark all as read'); // fallback
            }
        });
    });

});
