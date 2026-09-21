/**
 * Notifications JS - Frontend API interactions
 */

(function($) {
    'use strict';

    const NotificationAPI = {
        // Get unread count and update badge
        updateUnreadCount: function() {
            $.post(MM_NOTIFICATIONS.ajaxurl, {
                action: 'mm_get_unread_count',
                nonce: MM_NOTIFICATIONS.nonce,
            }, function(response) {
                if (response.success) {
                    const count = response.data.count;
                    const badge = $('#mm-notification-badge');
                    
                    if (count > 0) {
                        badge.text(count > 99 ? '99+' : count).show();
                    } else {
                        badge.hide();
                    }
                }
            });
        },

        // Load notifications list
        loadNotifications: function(page = 1, filters = {}) {
            $.post(MM_NOTIFICATIONS.ajaxurl, {
                action: 'mm_get_notifications',
                nonce: MM_NOTIFICATIONS.nonce,
                page: page,
                type: filters.type || '',
                category: filters.category || '',
                read: filters.read || '',
            }, function(response) {
                if (response.success) {
                    NotificationAPI.renderNotifications(response.data);
                }
            });
        },

        // Render notifications list
        renderNotifications: function(data) {
            const container = $('#mm-notifications-list');
            if (!container.length) return;

            let html = '';

            if (data.notifications.length === 0) {
                html = '<div class="text-center py-5"><p class="text-muted">No notifications</p></div>';
            } else {
                data.notifications.forEach(notif => {
                    const isRead = notif.read_at !== null;
                    const bgClass = isRead ? '' : 'bg-warning-light';
                    const readClass = isRead ? 'read' : 'unread';

                    html += `
                        <div class="notification-item ${readClass} ${bgClass}" data-id="${notif.id}">
                            <div class="d-flex justify-content-between align-items-start p-3 border-bottom">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center">
                                        <span class="notification-icon" style="font-size: 20px; margin-right: 10px;">
                                            ${notif.icon}
                                        </span>
                                        <div>
                                            <h6 class="mb-1">
                                                ${notif.title}
                                                ${!isRead ? '<span class="badge bg-danger ms-2">New</span>' : ''}
                                            </h6>
                                            <p class="mb-1 text-muted small">${notif.message}</p>
                                            <small class="text-muted">
                                                ${new Date(notif.created_at).toLocaleDateString()} 
                                                ${new Date(notif.created_at).toLocaleTimeString()}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <div class="notification-actions">
                                    ${notif.action_url ? `
                                        <a href="${notif.action_url}" class="btn btn-sm btn-outline-primary me-2">
                                            ${notif.action_label || 'View'}
                                        </a>
                                    ` : ''}
                                    <button class="btn btn-sm btn-outline-danger mark-read-btn" data-id="${notif.id}">
                                        ${isRead ? '↩️' : '✓ Read'}
                                    </button>
                                    <button class="btn btn-sm btn-outline-dark delete-btn" data-id="${notif.id}">
                                        ✕
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                });
            }

            container.html(html);

            // Attach event listeners with event delegation
            $(document).off('click', '.mark-read-btn').on('click', '.mark-read-btn', function(e) {
                e.preventDefault();
                const notifId = $(this).data('id');
                NotificationAPI.markAsRead(notifId);
            });

            $(document).off('click', '.delete-btn').on('click', '.delete-btn', function(e) {
                e.preventDefault();
                const notifId = $(this).data('id');
                if (confirm('Delete this notification?')) {
                    NotificationAPI.deleteNotification(notifId);
                }
            });

            // Pagination
            if (data.total_pages > 1) {
                let paginationHtml = '<nav aria-label="Page navigation"><ul class="pagination">';
                
                for (let i = 1; i <= data.total_pages; i++) {
                    paginationHtml += `
                        <li class="page-item ${i === data.page ? 'active' : ''}">
                            <a class="page-link" href="#" data-page="${i}">${i}</a>
                        </li>
                    `;
                }
                
                paginationHtml += '</ul></nav>';
                $('#mm-notifications-pagination').html(paginationHtml);
            }

            // Use event delegation for pagination clicks
            $(document).off('click', '.page-link').on('click', '.page-link', function(e) {
                e.preventDefault();
                const page = $(this).data('page');
                NotificationAPI.loadNotifications(page);
                $('html, body').animate({ scrollTop: 0 }, 'fast');
            });
        },

        // Mark as read
        markAsRead: function(notifId) {
            $.post(MM_NOTIFICATIONS.ajaxurl, {
                action: 'mm_mark_notification_read',
                nonce: MM_NOTIFICATIONS.nonce,
                notification_id: notifId,
            }, function(response) {
                if (response.success) {
                    $(`[data-id="${notifId}"]`).addClass('read').removeClass('unread');
                    NotificationAPI.updateUnreadCount();
                }
            });
        },

        // Mark all as read
        markAllAsRead: function() {
            $.post(MM_NOTIFICATIONS.ajaxurl, {
                action: 'mm_mark_all_notifications_read',
                nonce: MM_NOTIFICATIONS.nonce,
            }, function(response) {
                if (response.success) {
                    $('.notification-item').addClass('read').removeClass('unread');
                    NotificationAPI.updateUnreadCount();
                }
            });
        },

        // Delete notification
        deleteNotification: function(notifId) {
            $.post(MM_NOTIFICATIONS.ajaxurl, {
                action: 'mm_delete_notification',
                nonce: MM_NOTIFICATIONS.nonce,
                notification_id: notifId,
            }, function(response) {
                if (response.success) {
                    $(`[data-id="${notifId}"]`).fadeOut(300, function() {
                        $(this).remove();
                    });
                    NotificationAPI.updateUnreadCount();
                }
            });
        },

        // Delete all notifications
        deleteAllNotifications: function() {
            if (!confirm('Delete all notifications? This cannot be undone.')) {
                return;
            }

            $.post(MM_NOTIFICATIONS.ajaxurl, {
                action: 'mm_delete_all_notifications',
                nonce: MM_NOTIFICATIONS.nonce,
            }, function(response) {
                if (response.success) {
                    $('#mm-notifications-list').html(
                        '<div class="text-center py-5"><p class="text-muted">No notifications</p></div>'
                    );
                    NotificationAPI.updateUnreadCount();
                }
            });
        },
    };

    // Initialize on page load
    $(document).ready(function() {
        // Update badge on page load
        if ($('#mm-notification-badge').length) {
            NotificationAPI.updateUnreadCount();
            
            // Update every 30 seconds
            setInterval(function() {
                NotificationAPI.updateUnreadCount();
            }, 30000);
        }

        // Load notifications if on notifications page
        if ($('#mm-notifications-list').length) {
            NotificationAPI.loadNotifications();

            // Mark all as read button
            $(document).off('click', '#mm-mark-all-read-btn').on('click', '#mm-mark-all-read-btn', function(e) {
                e.preventDefault();
                NotificationAPI.markAllAsRead();
            });

            // Delete all button
            $(document).off('click', '#mm-delete-all-btn').on('click', '#mm-delete-all-btn', function(e) {
                e.preventDefault();
                NotificationAPI.deleteAllNotifications();
            });

            // Filter form - use event delegation
            $(document).off('change', '#mm-notifications-filter').on('change', '#mm-notifications-filter', function() {
                const filters = {
                    type: $('#notification-type').val(),
                    category: $('#notification-category').val(),
                    read: $('#notification-read').val(),
                };
                NotificationAPI.loadNotifications(1, filters);
            });
        }
    });

    // Expose to window
    window.NotificationAPI = NotificationAPI;

})(jQuery);
