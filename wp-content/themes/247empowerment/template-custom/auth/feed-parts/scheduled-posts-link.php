<?php
/**
 * Scheduled Posts Link Component
 * Shows a link to view all scheduled posts
 */

if (!is_user_logged_in()) {
    return;
}

$user_id = get_current_user_id();

// Count scheduled posts for current user (with DISTINCT to avoid duplicates)
$scheduled_args = array(
    'post_type'      => 'post',
    'post_status'    => 'future',
    'author'         => $user_id,
    'posts_per_page' => -1,
    'no_found_rows'  => false,
);

$scheduled_query = new WP_Query($scheduled_args);
$scheduled_count = $scheduled_query->found_posts;

// If there are no scheduled posts, don't show the link
if ($scheduled_count === 0) {
    wp_reset_postdata();
    return;
}

?>
<div class="mb-3 custom-card scheduled-posts-link">
    <div class="d-flex align-items-center justify-content-between p-3">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-calendar-check text-primary" style="font-size: 20px;"></i>
            <div>
                <h6 class="mb-0">Scheduled Posts</h6>
                <small class="text-muted"><?php echo esc_html($scheduled_count); ?> post<?php echo $scheduled_count !== 1 ? 's' : ''; ?> waiting to publish</small>
            </div>
        </div>
        <a href="#scheduledPostsModal" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal">
            <i class="bi bi-arrow-right me-1"></i>View
        </a>
    </div>
</div>

<!-- SCHEDULED POSTS MODAL -->
<div class="modal fade" id="scheduledPostsModal" tabindex="-1" aria-labelledby="scheduledPostsLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h5 class="modal-title" id="scheduledPostsLabel">
                    <i class="bi bi-calendar-check me-2"></i>Scheduled Posts
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body" style="max-height: 500px; overflow-y: auto;">
                <?php
                $scheduled_args['posts_per_page'] = 50;
                $scheduled_query = new WP_Query($scheduled_args);

                if ($scheduled_query->have_posts()):
                    ?>
                    <div class="scheduled-posts-list">
                        <?php
                        while ($scheduled_query->have_posts()): $scheduled_query->the_post();
                            $post_id = get_the_ID();
                            $post_date = get_the_date('F j, Y \a\t g:i A');
                            $post_title = get_the_title() ?: '(No title)';
                            $post_excerpt = wp_trim_words(get_the_excerpt() ?: get_the_content(), 15, '...');
                            ?>
                            <div class="scheduled-post-item d-flex justify-content-between align-items-start p-3 border-bottom">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">
                                        <a href="<?php the_permalink(); ?>" target="_blank" class="text-decoration-none">
                                            <?php echo esc_html($post_title); ?>
                                        </a>
                                    </h6>
                                    <p class="small text-muted mb-2"><?php echo esc_html($post_excerpt); ?></p>
                                    <div class="d-flex gap-3">
                                        <small class="text-info">
                                            <i class="bi bi-calendar-event"></i> 
                                            <?php echo esc_html($post_date); ?>
                                        </small>
                                    </div>
                                </div>
                                <div class="ms-2 d-flex gap-1 flex-column">
                                    <button type="button" class="btn btn-outline-secondary btn-sm edit-scheduled-post" 
                                            data-post-id="<?php echo esc_attr($post_id); ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editScheduledPostModal" 
                                            title="Edit post">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-sm delete-scheduled-post" 
                                            data-post-id="<?php echo esc_attr($post_id); ?>" 
                                            title="Delete scheduled post">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <?php
                        endwhile;
                        ?>
                    </div>
                    <?php
                else:
                    echo '<p class="text-center text-muted">No scheduled posts found.</p>';
                endif;

                wp_reset_postdata();
                ?>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary edit-scheduled-post" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- EDIT SCHEDULED POST MODAL -->
<div class="modal fade" id="editScheduledPostModal" tabindex="-1" aria-labelledby="editScheduledPostLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h5 class="modal-title" id="editScheduledPostLabel">
                    <i class="bi bi-pencil-square me-2"></i>Edit Scheduled Post
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body">
                <form id="editScheduledPostForm">
                    <input type="hidden" id="editPostId" name="edit_post_id">
                    <input type="hidden" id="editPostNonce" name="edit_post_nonce" value="<?php echo esc_attr(wp_create_nonce('edit_scheduled_post_action')); ?>">
                    
                    <!-- Post Title -->
                    <div class="mb-3">
                        <label for="editPostTitle" class="form-label fw-bold">Post Title</label>
                        <input type="text" class="form-control" id="editPostTitle" name="post_title" placeholder="Enter post title (optional)">
                    </div>

                    <!-- Post Content -->
                    <div class="mb-3">
                        <label for="editPostContent" class="form-label fw-bold">Post Content</label>
                        <textarea class="form-control" id="editPostContent" name="post_content" rows="6" placeholder="Enter post content"></textarea>
                    </div>

                    <!-- Post Privacy -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Privacy Level</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="post_privacy" id="editPrivacyOnlyMe" value="only_me">
                            <label class="form-check-label" for="editPrivacyOnlyMe">
                                <i class="bi bi-lock me-1"></i> Only Me
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="post_privacy" id="editPrivacyPartners" value="referral_partners">
                            <label class="form-check-label" for="editPrivacyPartners">
                                <i class="bi bi-people me-1"></i> Referral Partners
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="post_privacy" id="editPrivacyPublic" value="public">
                            <label class="form-check-label" for="editPrivacyPublic">
                                <i class="bi bi-globe me-1"></i> Public
                            </label>
                        </div>
                    </div>

                    <!-- Schedule Date & Time -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Scheduled Date & Time</label>
                        <div class="row">
                            <div class="col-md-6">
                                <input type="date" class="form-control" id="editScheduleDate" name="schedule_date">
                            </div>
                            <div class="col-md-6">
                                <input type="time" class="form-control" id="editScheduleTime" name="schedule_time">
                            </div>
                        </div>
                        <small class="text-muted mt-1 d-block">Current schedule: <span id="currentScheduleTime">-</span></small>
                    </div>
                </form>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveEditedPostBtn">
                    <i class="bi bi-check-circle me-1"></i>Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .scheduled-posts-link {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border: 1px solid #dee2e6 !important;
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .scheduled-posts-link:hover {
        background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .scheduled-post-item {
        transition: background-color 0.2s ease;
    }

    .scheduled-post-item:hover {
        background-color: #f8f9fa;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle edit button clicks
        const editButtons = document.querySelectorAll('.edit-scheduled-post');
        
        editButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const postId = this.getAttribute('data-post-id');
                
                // Show loading
                console.log('Loading post data for ID:', postId);
                
                // Fetch post data
                fetch(ajax_object.ajax_url || '/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'get_scheduled_post_data',
                        post_id: postId,
                        edit_post_nonce: document.querySelector('#editScheduledPostForm #editPostNonce')?.value || ''
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Populate form with post data
                        document.getElementById('editPostId').value = postId;
                        document.getElementById('editPostTitle').value = data.data.post_title || '';
                        document.getElementById('editPostContent').value = data.data.post_content || '';
                        
                        // Set privacy level
                        const privacyValue = data.data.post_privacy || 'only_me';
                        document.querySelector(`input[name="post_privacy"][value="${privacyValue}"]`).checked = true;
                        
                        // Set schedule date and time
                        if (data.data.post_date) {
                            const dateObj = new Date(data.data.post_date);
                            const date = dateObj.toISOString().split('T')[0];
                            const time = dateObj.toTimeString().slice(0, 5);
                            document.getElementById('editScheduleDate').value = date;
                            document.getElementById('editScheduleTime').value = time;
                            document.getElementById('currentScheduleTime').textContent = data.data.post_date;
                        }
                    } else {
                        alert('Error: ' + (data.data?.message || 'Could not load post data'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading post data. Check console for details.');
                });
            });
        });
        
        // Handle save button
        const saveBtn = document.getElementById('saveEditedPostBtn');
        if (saveBtn) {
            saveBtn.addEventListener('click', function() {
                const postId = document.getElementById('editPostId').value;
                const title = document.getElementById('editPostTitle').value;
                const content = document.getElementById('editPostContent').value;
                const privacy = document.querySelector('input[name="post_privacy"]:checked').value;
                const scheduleDate = document.getElementById('editScheduleDate').value;
                const scheduleTime = document.getElementById('editScheduleTime').value;
                
                // Validate
                if (!content.trim()) {
                    alert('Please enter post content');
                    return;
                }
                
                if (!scheduleDate || !scheduleTime) {
                    alert('Please select schedule date and time');
                    return;
                }
                
                // Calculate timestamp
                const dateTime = new Date(scheduleDate + 'T' + scheduleTime);
                const timestamp = Math.floor(dateTime.getTime() / 1000);
                
                // Show loading
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
                
                // Send update request
                fetch(ajax_object.ajax_url || '/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'update_scheduled_post',
                        post_id: postId,
                        post_title: title,
                        post_content: content,
                        post_privacy: privacy,
                        schedule_timestamp: timestamp,
                        edit_post_nonce: document.getElementById('editPostNonce').value
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Close modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('editScheduledPostModal'));
                        if (modal) {
                            modal.hide();
                        }
                        // Reload page
                        location.reload();
                    } else {
                        alert('Error: ' + (data.data?.message || 'Could not update post'));
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Save Changes';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating post. Check console for details.');
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Save Changes';
                });
            });
        }
        
        // Handle delete button clicks
        const deleteButtons = document.querySelectorAll('.delete-scheduled-post');
        
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const postId = this.getAttribute('data-post-id');
                
                if (!confirm('Are you sure you want to delete this scheduled post?')) {
                    return;
                }
                
                const button = this;
                button.disabled = true;
                button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
                
                // Send AJAX request to delete
                fetch(ajax_object.ajax_url || '/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'delete_scheduled_post',
                        post_id: postId,
                        nonce: button.closest('.modal-body')?.querySelector('[data-nonce]')?.getAttribute('data-nonce') || ''
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the post item from DOM
                        const postItem = button.closest('.scheduled-post-item');
                        postItem.style.opacity = '0';
                        postItem.style.transition = 'opacity 0.3s ease';
                        setTimeout(() => {
                            postItem.remove();
                            
                            // Check if there are any posts left
                            const remainingPosts = document.querySelectorAll('.scheduled-post-item').length;
                            if (remainingPosts === 0) {
                                location.reload(); // Reload to hide the card if no posts left
                            }
                        }, 300);
                    } else {
                        alert('Error: ' + (data.data?.message || 'Could not delete post'));
                        button.disabled = false;
                        button.innerHTML = '<i class="bi bi-trash"></i>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting post. Check console for details.');
                    button.disabled = false;
                    button.innerHTML = '<i class="bi bi-trash"></i>';
                });
            });
        });
    });
</script>

