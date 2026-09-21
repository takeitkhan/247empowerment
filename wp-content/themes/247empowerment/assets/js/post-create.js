jQuery(document).ready(function($) {
    $('#photoUpload').on('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#image-preview').attr('src', e.target.result).show();
            }
            reader.readAsDataURL(file);
        } else {
            $('#image-preview').hide();
        }
    });
});

jQuery(document).ready(function ($) {
    $('#create-post-form').on('submit', function (e) {
        e.preventDefault();

        var formData = new FormData(this);

        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                console.log(res); // debug response
                if (res.success) {
                    //alert('Post created successfully! ID: ' + res.data.post_id);
                    location.reload();
                } else {
                    alert('Error: ' + (res.data?.message || 'Something went wrong'));
                }
            },
            error: function (xhr, status, err) {
                console.error('AJAX error:', status, err);
                //alert('AJAX error occurred.');
            }
        });
    });
});



jQuery(document).ready(function ($) {
    $('#createPostForm').on('submit', function (e) {
        e.preventDefault();

        let content = $('textarea[name="post_content"]').val();
        let image = $('#photoUpload')[0].files[0];

        let formData = new FormData(this);
        formData.set('action', 'create_post');
        formData.set('create_post_nonce', ajax_object.nonce);

        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success(res) {
                if (res.success) {
                    alert('Post created successfully!');
                    location.reload();
                } else {
                    alert(res.data?.message || 'Something went wrong');
                }
            },
            error(xhr, status, err) {
                console.error('AJAX error:', status, err);
                alert('AJAX error occurred.');
            }
        });
    });
});


jQuery(document).ready(function ($) {
    $('.read-more-text').on('click', function () {
        let $this = $(this);
        let parent = $this.closest('.post-content-text');
        let full = parent.data('full');
        let trimmed = parent.data('trimmed');

        if ($this.hasClass('expanded')) {
            // Collapse to trimmed
            parent.html(trimmed + ' <span class="read-more-text text-primary-color" style="cursor:pointer;"> Read more</span>');
        } else {
            // Expand to full
            parent.html(full + '<br><span class="read-more-text text-primary-color expanded" style="cursor:pointer;"> Show less</span>');
        }
    });
});
// Handle privacy update
jQuery(document).ready(function($) {
    $('.save-privacy-btn').on('click', function() {
        const postId = $(this).data('post-id');
        const privacyValue = $(`#privacyModal${postId} .post-privacy-select`).val();
        
        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'update_post_privacy',
                post_id: postId,
                privacy: privacyValue
            },
            success: function(res) {
                if (res.success) {
                    alert('Privacy updated successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (res.data?.message || 'Failed to update privacy'));
                }
            },
            error: function(xhr, status, err) {
                console.error('AJAX error:', status, err);
                alert('Error updating privacy');
            }
        });
    });
});

// Handle reactions
jQuery(document).ready(function($) {
    $(document).on('click', '.reaction-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const postId = $(this).data('post-id');
        const reaction = $(this).data('reaction');

        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'add_reaction',
                post_id: postId,
                reaction: reaction,
                nonce: ajax_object.reaction_nonce
            },
            success: function(res) {
                if (res.success) {
                    // Update UI without full reload
                    location.reload();
                } else {
                    alert('Error: ' + (res.data?.message || 'Failed to add reaction'));
                }
            },
            error: function(xhr, status, err) {
                console.error('AJAX error:', status, err);
            }
        });
    });

    // Comment Toggle Button
    $(document).on('click', '.comment-toggle-btn', function(e) {
        e.preventDefault();
        const postId = $(this).data('post-id');
        const $commentSection = $(`#comments-${postId}`);
        
        if ($commentSection.is(':visible')) {
            $commentSection.slideUp(300);
        } else {
            $commentSection.slideDown(300);
        }
    });
});

// Handle comments
jQuery(document).ready(function($) {
    const swalBase = {
        customClass: {
            popup: 'swal-compact'
        },
        width: 320,
        padding: '12px'
    };

    const showError = (message) => {
        if (window.Swal) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message,
                ...swalBase
            });
        }
    };

    const showSuccess = (message) => {
        if (window.Swal) {
            Swal.fire({
                icon: 'success',
                title: message,
                timer: 1200,
                showConfirmButton: false,
                ...swalBase
            });
        }
    };

    // Load comments on page load
    $('.comment-section').each(function() {
        const postId = $(this).attr('id')?.replace('comments-', '');
        if (postId) {
            loadComments(postId);
        }
    });

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

            // Show loading state
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
                        nonce: $input.data('nonce') || ajax_object?.nonce
                },
                success: function(res) {
                    if (res.success) {
                        $input.val('');
                        $input.attr('placeholder', originalPlaceholder).prop('disabled', false);
                        loadComments(postId);
                        
                        // Show brief success feedback
                        const $feedback = $('<div class="alert alert-success alert-dismissible fade show" style="font-size: 12px; padding: 6px 12px; margin-bottom: 8px;">Comment posted!</div>');
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

    // Attach handlers initially and after comments load
    attachCommentHandlers();

    // Comment Options Menu
    $(document).on('click', '.comment-options-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const $btn = $(this);
        const commentId = $btn.data('comment-id');

        if (!commentId || !window.Swal) {
            return;
        }

        const $commentItem = $btn.closest('.comment-item');
        const $commentSection = $btn.closest('.comment-section');
        const postId = $commentSection.attr('id')?.replace('comments-', '');
        const nonce = $commentSection.data('nonce') || ajax_object?.nonce;
        const currentText = $commentItem.data('comment-content') || $commentItem.find('.comment-bubble p').text().trim();

        Swal.fire({
            title: 'Comment Options',
            icon: 'info',
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonText: 'Edit',
            denyButtonText: 'Delete',
            ...swalBase
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Edit Comment',
                    input: 'textarea',
                    inputValue: currentText,
                    inputPlaceholder: 'Update your comment...',
                    showCancelButton: true,
                    confirmButtonText: 'Save',
                    inputValidator: (value) => {
                        if (!value || !value.trim()) {
                            return 'Comment cannot be empty';
                        }
                        return null;
                    },
                    ...swalBase,
                    customClass: {
                        popup: 'swal-compact swal-compact-edit',
                        input: 'swal-compact-edit-input'
                    }
                }).then((editResult) => {
                    if (!editResult.isConfirmed) return;

                    $.ajax({
                        url: ajax_object.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'update_post_comment',
                            comment_id: commentId,
                            comment: editResult.value,
                            nonce: nonce
                        },
                        success: function(res) {
                            if (res.success) {
                                if (postId) {
                                    loadComments(postId);
                                } else {
                                    $commentItem.find('.comment-bubble p').text(res.data.comment || editResult.value);
                                    $commentItem.attr('data-comment-content', res.data.comment || editResult.value);
                                }
                                showSuccess('Comment updated');
                            } else {
                                showError(res.data?.message || 'Failed to update comment');
                            }
                        },
                        error: function(xhr, status, err) {
                            console.error('AJAX error:', status, err);
                            showError('Failed to update comment');
                        }
                    });
                });
            } else if (result.isDenied) {
                Swal.fire({
                    title: 'Delete comment?',
                    text: 'This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Delete',
                    ...swalBase
                }).then((deleteResult) => {
                    if (!deleteResult.isConfirmed) return;

                    $.ajax({
                        url: ajax_object.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'delete_post_comment',
                            comment_id: commentId,
                            nonce: nonce
                        },
                        success: function(res) {
                            if (res.success) {
                                if (postId) {
                                    loadComments(postId);
                                } else {
                                    $commentItem.remove();
                                }
                                showSuccess('Comment deleted');
                            } else {
                                showError(res.data?.message || 'Failed to delete comment');
                            }
                        },
                        error: function(xhr, status, err) {
                            console.error('AJAX error:', status, err);
                            showError('Failed to delete comment');
                        }
                    });
                });
            }
        });
    });

    function loadComments(postId) {
        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'load_post_comments',
                post_id: postId
            },
            success: function(res) {
                if (res.success) {
                    const $commentSection = $(`#comments-${postId}`);
                    const newHTML = res.data.comments;
                    
                    // Fade out old comments
                    $commentSection.fadeOut(200, function() {
                        $(this).html(newHTML).fadeIn(200);
                        
                        // Reattach event handlers after DOM update
                        attachCommentHandlers();
                    });
                    
                    // Update count
                    $(`#comment-count-${postId}`).text(res.data.count + ' Comment' + (res.data.count !== 1 ? 's' : ''));
                }
            },
            error: function(err) {
                console.error('Failed to load comments:', err);
            }
        });
    }
    
    // Attach event handlers for dynamically loaded comments
    function attachCommentHandlers() {
        // Show reply input when Reply button is clicked
        $(document).off('click', '.reply-btn').on('click', '.reply-btn', function(e) {
            e.preventDefault();
            const commentId = $(this).data('comment-id');
            const authorName = $(this).data('author-name');
            
            // Hide all other reply inputs first
            $('.reply-input-container').not(`#reply-container-${commentId}`).slideUp(300);
            
            // Toggle this reply input
            $(`#reply-container-${commentId}`).slideToggle(300, function() {
                if ($(this).is(':visible')) {
                    $(this).find('input').focus();
                }
            });
        });
        
        // Add reply comment on Enter key
        $(document).off('keypress', '.comment-reply-input').on('keypress', '.comment-reply-input', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                const $input = $(this);
                const parentId = $input.data('parent-id');
                const commentText = $input.val();
                
                // Find post ID by traversing up
                const postId = $input.closest('.comment-section').attr('id')?.replace('comments-', '');

                if (!postId || !parentId) {
                    console.error('Missing post ID or parent ID');
                    return;
                }

                if (!commentText.trim()) {
                    $input.focus();
                    return;
                }

                // Add loading state
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
                            nonce: $input.closest('.comment-section').data('nonce') || ajax_object?.nonce
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
        
        // Comment Like button
        $(document).off('click', '.comment-like-btn').on('click', '.comment-like-btn', function(e) {
            e.preventDefault();
            const $btn = $(this);
            const commentId = $btn.data('comment-id');
            
            $btn.toggleClass('text-primary text-muted');
            
            // You can add AJAX here to save like in database
            // For now, it's just visual feedback
        });
    }

    // Load comments for all posts on page load
    $('.comment-section').each(function() {
        const postId = $(this).attr('id')?.replace('comments-', '');
        if (postId) {
            loadComments(postId);
        }
    });

    // Emoji picker (if emoji library is available)
    $(document).on('click', '.emoji-icon', function(e) {
        e.preventDefault();
        const $input = $(this).siblings('input.comment-reply-input');
        // Add emoji picker implementation here
        $input.focus();
    });
});