jQuery(document).ready(function($) {
    const swalBase = {
        customClass: { popup: 'swal-compact' },
        width: 320,
        padding: '12px'
    };

    const showError = (message) => {
        if (window.Swal) {
            Swal.fire({ icon: 'error', title: 'Error', text: message, ...swalBase });
        } else {
            alert(message);
        }
    };

    const showSuccess = (message) => {
        if (window.Swal) {
            Swal.fire({ icon: 'success', title: message, timer: 1200, showConfirmButton: false, ...swalBase });
        }
    };

    function loadComments(postId) {
        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: { action: 'load_post_comments', post_id: postId },
            success: function(res) {
                if (res.success) {
                    const $commentSection = $(`#comments-${postId}`);
                    const newHTML = res.data.comments;
                    $commentSection.fadeOut(200, function() {
                        $(this).html(newHTML).fadeIn(200);
                        attachCommentHandlers();
                    });
                    $(`#comment-count-${postId}`).text(res.data.count + ' Comment' + (res.data.count !== 1 ? 's' : ''));
                }
            },
            error: function(err) {
                console.error('Failed to load comments:', err);
            }
        });
    }

    // Add main comment on Enter key
    $(document).on('keypress', '.comment-input', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            const $input = $(this);
            const postId = $input.data('post-id');
            const commentText = $input.val();

            if (!commentText.trim()) {
                $input.focus();
                return;
            }

            const originalPlaceholder = $input.attr('placeholder');
            $input.attr('placeholder', 'Posting...').prop('disabled', true);

            $.ajax({
                url: ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'add_post_comment',
                    post_id: postId,
                    comment: commentText,
                    parent_id: 0,
                    nonce: ajax_object?.nonce
                },
                success: function(res) {
                    if (res.success) {
                        $input.val('');
                        $input.attr('placeholder', originalPlaceholder).prop('disabled', false);
                        loadComments(postId);
                        const $feedback = $('<div class="alert alert-success" style="font-size:12px;padding:6px 12px;margin-bottom:8px;">Comment posted!</div>');
                        $input.closest('.mt-2').before($feedback);
                        setTimeout(() => $feedback.fadeOut(300, function() { $(this).remove(); }), 2000);
                    } else {
                        showError(res.data?.message || 'Failed to add comment');
                        $input.attr('placeholder', originalPlaceholder).prop('disabled', false);
                    }
                },
                error: function(xhr, status, err) {
                    console.error('AJAX error:', status, err);
                    showError('Unable to post comment');
                    $input.attr('placeholder', originalPlaceholder).prop('disabled', false);
                }
            });
        }
    });

    // Reply handler and other comment handlers
    function attachCommentHandlers() {
        $(document).off('click', '.reply-btn').on('click', '.reply-btn', function(e) {
            e.preventDefault();
            const commentId = $(this).data('comment-id');
            $('.reply-input-container').not(`#reply-container-${commentId}`).slideUp(300);
            $(`#reply-container-${commentId}`).slideToggle(300, function() {
                if ($(this).is(':visible')) { $(this).find('input').focus(); }
            });
        });

        $(document).off('keypress', '.comment-reply-input').on('keypress', '.comment-reply-input', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                const $input = $(this);
                const parentId = $input.data('parent-id');
                const commentText = $input.val();
                const postId = $input.closest('.comment-section').attr('id')?.replace('comments-', '');

                if (!postId || !parentId) { console.error('Missing post ID or parent ID'); return; }
                if (!commentText.trim()) { $input.focus(); return; }

                const originalPlaceholder = $input.attr('placeholder');
                $input.attr('placeholder', 'Posting...').prop('disabled', true);

                $.ajax({
                    url: ajax_object.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'add_post_comment',
                        post_id: postId,
                        comment: commentText,
                        parent_id: parentId,
                        nonce: ajax_object?.nonce
                    },
                    success: function(res) {
                        if (res.success) {
                            $input.val('');
                            $input.closest('.reply-input-container').slideUp(300);
                            loadComments(postId);
                        } else {
                            showError(res.data?.message || 'Failed to add reply');
                            $input.attr('placeholder', originalPlaceholder).prop('disabled', false);
                        }
                    },
                    error: function(xhr, status, err) {
                        console.error('AJAX error:', status, err);
                        showError('Failed to add reply');
                        $input.attr('placeholder', originalPlaceholder).prop('disabled', false);
                    }
                });
            }
        });

        $(document).off('click', '.comment-options-btn').on('click', '.comment-options-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const $btn = $(this);
            const commentId = $btn.data('comment-id');
            if (!commentId || !window.Swal) { return; }

            const $commentItem = $btn.closest('.comment-item');
            const $commentSection = $btn.closest('.comment-section');
            const postId = $commentSection.attr('id')?.replace('comments-', '');
            const nonce = $commentSection.data('nonce') || ajax_object?.nonce;
            const currentText = $commentItem.data('comment-content') || $commentItem.find('.comment-bubble p').text().trim();

            Swal.fire({ title: 'Comment Options', icon: 'info', showCancelButton: true, showDenyButton: true, confirmButtonText: 'Edit', denyButtonText: 'Delete', ...swalBase }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'Edit Comment', input: 'textarea', inputValue: currentText, inputPlaceholder: 'Update your comment...', showCancelButton: true, confirmButtonText: 'Save', inputValidator: (value) => { if (!value || !value.trim()) { return 'Comment cannot be empty'; } return null; }, ...swalBase, customClass: { popup: 'swal-compact swal-compact-edit', input: 'swal-compact-edit-input' } }).then((editResult) => {
                        if (!editResult.isConfirmed) return;
                        $.ajax({ url: ajax_object.ajax_url, type: 'POST', data: { action: 'update_post_comment', comment_id: commentId, comment: editResult.value, nonce: nonce }, success: function(res) {
                                if (res.success) {
                                    if (postId) { loadComments(postId); } else { $commentItem.find('.comment-bubble p').text(res.data.comment || editResult.value); $commentItem.attr('data-comment-content', res.data.comment || editResult.value); }
                                    showSuccess('Comment updated');
                                } else { showError(res.data?.message || 'Failed to update comment'); }
                            }, error: function(xhr, status, err) { console.error('AJAX error:', status, err); showError('Failed to update comment'); }
                        });
                    });
                } else if (result.isDenied) {
                    Swal.fire({ title: 'Delete comment?', text: 'This action cannot be undone.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete', ...swalBase }).then((deleteResult) => {
                        if (!deleteResult.isConfirmed) return;
                        $.ajax({ url: ajax_object.ajax_url, type: 'POST', data: { action: 'delete_post_comment', comment_id: commentId, nonce: nonce }, success: function(res) {
                                if (res.success) { if (postId) { loadComments(postId); } else { $commentItem.remove(); } showSuccess('Comment deleted'); } else { showError(res.data?.message || 'Failed to delete comment'); }
                            }, error: function(xhr, status, err) { console.error('AJAX error:', status, err); showError('Failed to delete comment'); }
                        });
                    });
                }
            });
        });

        $(document).off('click', '.comment-like-btn').on('click', '.comment-like-btn', function(e) {
            e.preventDefault();
            const $btn = $(this);
            $btn.toggleClass('text-primary text-muted');
        });
    }

    // Initialize by loading comments for all posts present
    $('.comment-section').each(function() {
        const postId = $(this).attr('id')?.replace('comments-', '');
        if (postId) { loadComments(postId); }
    });

    // Attach handlers once for any static content
    attachCommentHandlers();
});
