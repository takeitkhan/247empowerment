<?php
// Use $user_id from including file or current user if not set
if (!isset($user_id)) {
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
}

if (!$user_id) {
    echo '<p>Please log in to see your feed.</p>';
    return;
}

$profile_instance = new UserProfileData($user_id);

echo '<pre>';
print_r($profile_instance->getProfile());
echo '</pre>';


$referral_users = $profile_instance->getReferredUsers();

$user_ids = [$user_id];
foreach ($referral_users as $ref_user) {
    $user_ids[] = $ref_user->ID;
}
$user_ids = array_unique($user_ids);

$args = [
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'author__in'     => $user_ids,
    'posts_per_page' => 10,
    'orderby'        => 'date',
    'order'          => 'DESC',
];

$query = new WP_Query($args);

if ($query->have_posts()):
    while ($query->have_posts()): $query->the_post();

        $post_id = get_the_ID();

        // Check privacy before displaying the post
        if (!UserProfileData::canViewPost($post_id, get_current_user_id())) {
            continue; // Skip this post if user doesn't have permission
        }

        // Clean duplicate reactions for current user
        if (is_user_logged_in()) {
            clean_user_reactions($post_id, get_current_user_id());
        }

        $post_time = get_the_date('F j, Y \a\t g:i A');
        $post_author_id = get_post_field('post_author', $post_id);
        $post_image = get_the_post_thumbnail_url($post_id, 'large');

        // Get author profile data similarly
        $author_profile = new UserProfileData($post_author_id);
        $author_data = $author_profile->getProfile();

        // Fallbacks if no profile data
        $author_first_name = $author_data['first_name'] ?? get_the_author_meta('first_name', $post_author_id);
        $author_last_name  = $author_data['last_name'] ?? get_the_author_meta('last_name', $post_author_id);
        $author_photo      = get_user_meta($post_author_id, 'profile_photo', true) ?: get_template_directory_uri() . '/assets/img/loggedin_images/banner.jpg';

        // Prepare URLs for delete link
        $redirect_url = urlencode(is_singular() ? get_permalink() : (wp_get_referer() ?: home_url()));
        $delete_url = wp_nonce_url(
            admin_url('admin-post.php?action=delete_custom_post&post_id=' . $post_id . '&redirect_to=' . $redirect_url),
            'delete_post_' . $post_id
        );
?>
        <div class="bg-white mb-3 post-card">
            <div class="p-3">
                <!-- Post Header with Author and Menu -->
                <div class="d-flex align-items-start justify-content-between">
                    <div class="d-flex post-author">
                        <div class="me-3 post-author-img">
                            <img
                                class="rounded-circle w-100 h-100 object-fit-cover"
                                src="<?php echo esc_url($author_photo); ?>"
                                alt="<?php echo esc_attr($author_first_name . ' ' . $author_last_name); ?>" />
                        </div>
                        <div>
                            <h5 class="post-author-name">
                                <?php echo esc_html($author_first_name . ' ' . $author_last_name); ?>
                            </h5>
                            <div class="d-flex align-items-center gap-2">
                                <span class="post-time"><?php echo esc_html($post_time); ?></span>
                                <img src="<?php echo get_template_directory_uri(); ?>/assets/img/loggedin_images/dot2.png" alt="" />
                                <img src="<?php echo get_template_directory_uri(); ?>/assets/img/loggedin_images/earth.png" alt="" />
                            </div>
                        </div>
                    </div>

                    <?php
                    $current_user_id = get_current_user_id();
                    $post_author_id = get_post_field('post_author', $post_id);
                    $post_privacy = get_post_meta($post_id, '_post_privacy', true) ?: 'only_me';

                    if ($current_user_id === (int) $post_author_id): ?>
                        <!-- Three-dot dropdown menu -->
                        <div class="dropdown">
                            <button class="p-0 border-0 text-dark btn btn-link" type="button" id="postOptions<?php echo $post_id; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-three-dots fs-5"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="postOptions<?php echo $post_id; ?>">
                                <li>
                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#privacyModal<?php echo $post_id; ?>">
                                        Edit Privacy
                                    </a>
                                </li>
                                <li>
                                    <a class="text-danger dropdown-item delete-post-btn"
                                        href="#"
                                        data-post-id="<?php echo $post_id; ?>"
                                        data-nonce="<?php echo wp_create_nonce('delete_post_nonce_' . $post_id); ?>"
                                        onclick="if(confirm('Are you sure you want to delete this post?')) { deletePostAjax(this); } return false;">
                                        Delete
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <!-- Privacy Edit Modal -->
                        <div class="modal fade" id="privacyModal<?php echo $post_id; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Post Privacy</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <select class="form-select post-privacy-select" data-post-id="<?php echo $post_id; ?>" data-current-privacy="<?php echo esc_attr($post_privacy); ?>">
                                            <option value="only_me" <?php selected($post_privacy, 'only_me'); ?>>Only Me</option>
                                            <option value="referral_partners" <?php selected($post_privacy, 'referral_partners'); ?>>Only Referral Partners</option>
                                            <option value="public" <?php selected($post_privacy, 'public'); ?>>Public</option>
                                        </select>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="button" class="btn btn-primary save-privacy-btn" data-post-id="<?php echo $post_id; ?>">Save</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Post Content -->
                <?php
                $raw_content = get_the_content();
                $allowed_tags = ['br' => []];
                $sanitized_content = wp_kses($raw_content, $allowed_tags);
                $formatted_content = nl2br($sanitized_content);
                $trimmed_content = wp_trim_words(strip_tags($formatted_content), 40, '...');
                ?>
                <p class="mt-3 mb-1 post-content-text"
                    data-full="<?php echo esc_attr($formatted_content); ?>"
                    data-trimmed="<?php echo esc_attr($trimmed_content); ?>">
                    <?php echo $trimmed_content; ?>
                    <span class="text-primary read-more-text" style="cursor:pointer;"> See more</span>
                </p>
            </div>

            <?php if ($post_image): ?>
                <div class="post-content-img">
                    <img class="w-100 h-100 object-fit-cover"
                        src="<?php echo esc_url($post_image); ?>"
                        alt="" />
                </div>
            <?php endif; ?>

            <!-- Reactions Section -->
            <?php
            $reaction_counts = get_post_reaction_counts($post_id);
            $user_reactions = is_user_logged_in() ? get_user_reactions($post_id, get_current_user_id()) : [];
            $total_reactions = array_sum($reaction_counts);
            ?>
            <div class="mt-3 pt-2 border-top post-actions">
                <div class="d-flex align-items-center justify-content-between mb-2 px-2">
                    <span class="text-muted small" id="reaction-count-<?php echo $post_id; ?>"><?php echo $total_reactions; ?> Reaction<?php echo $total_reactions !== 1 ? 's' : ''; ?></span>
                    <span class="text-muted small" id="comment-count-<?php echo $post_id; ?>">0 Comments</span>
                </div>

                <!-- Reaction Buttons -->
                <div class="d-flex flex-wrap gap-2 px-2 pb-1">
                    <?php if (function_exists('mm_social_poster_badge')) { mm_social_poster_badge($post_id); } ?>
                    <button class="btn btn-sm btn-light reaction-btn <?php echo in_array('like', $user_reactions) ? 'active' : ''; ?>" data-post-id="<?php echo $post_id; ?>" data-reaction="like" title="Like">
                        👍 Like
                    </button>
                    <button class="btn btn-sm btn-light reaction-btn <?php echo in_array('love', $user_reactions) ? 'active' : ''; ?>" data-post-id="<?php echo $post_id; ?>" data-reaction="love" title="Love">
                        ❤️ Love
                    </button>
                    <button class="btn btn-sm btn-light reaction-btn <?php echo in_array('happy', $user_reactions) ? 'active' : ''; ?>" data-post-id="<?php echo $post_id; ?>" data-reaction="happy" title="Happy">
                        😄 Haha
                    </button>
                    <button class="btn btn-sm btn-light reaction-btn <?php echo in_array('wow', $user_reactions) ? 'active' : ''; ?>" data-post-id="<?php echo $post_id; ?>" data-reaction="wow" title="Wow">
                        😮 Wow
                    </button>
                    <button class="btn btn-sm btn-light reaction-btn <?php echo in_array('sad', $user_reactions) ? 'active' : ''; ?>" data-post-id="<?php echo $post_id; ?>" data-reaction="sad" title="Sad">
                        😢 Sad
                    </button>
                    <button class="btn btn-sm btn-light reaction-btn <?php echo in_array('angry', $user_reactions) ? 'active' : ''; ?>" data-post-id="<?php echo $post_id; ?>" data-reaction="angry" title="Angry">
                        😠 Angry
                    </button>
                </div>

                <!-- Comments Section -->
                <div class="px-2 pt-1 border-top">
                    <div class="comment-section" id="comments-<?php echo $post_id; ?>"></div>

                    <!-- Comment Input -->
                    <?php if (is_user_logged_in()): ?>
                    <div class="mt-1 pt-1 border-top">
                        <div class="d-flex align-items-flex-start gap-2">
                            <img src="<?php echo esc_url(get_user_meta(get_current_user_id(), 'profile_photo', true) ?: get_template_directory_uri() . '/assets/img/loggedin_images/banner.jpg'); ?>" alt="You" class="rounded-circle" width="32" height="32" style="object-fit: cover;">
                            <div class="flex-grow-1">
                                <input type="text" class="form-control form-control-sm comment-input" data-post-id="<?php echo $post_id; ?>" placeholder="Write a comment..." style="font-size: 12px;">
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
<?php
    endwhile;
    wp_reset_postdata();
else:
    echo '<p class="p-3">No posts found.</p>';
endif;
?>

<script>
    // 🗑️ DELETE POST AJAX FUNCTION
    function deletePostAjax(button) {
        const postId = button.getAttribute('data-post-id');
        const nonce = button.getAttribute('data-nonce');
        
        if (!postId) {
            alert('Error: No post ID');
            return false;
        }
        
        // Show loading
        const originalText = button.textContent;
        button.textContent = 'Deleting...';
        button.disabled = true;
        
        // Create FormData object for proper form submission
        const formData = new FormData();
        formData.append('action', 'delete_user_post');
        formData.append('post_id', postId);
        formData.append('nonce', nonce);
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            credentials: 'same-origin',  // ✅ Include cookies/session
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP error, status = ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Response:', data);
            if (data.success) {
                console.log('✅ Post deleted:', postId);
                const postCard = button.closest('.post-card');
                if (postCard) {
                    postCard.style.opacity = '0.5';
                    setTimeout(() => {
                        postCard.remove();
                        alert('Post deleted successfully');
                        location.reload();
                    }, 300);
                } else {
                    alert('Post deleted successfully');
                    location.reload();
                }
            } else {
                console.error('❌ Delete failed:', data.data?.message);
                alert('Error: ' + (data.data?.message || 'Failed to delete post'));
                button.textContent = originalText;
                button.disabled = false;
            }
        })
        .catch(error => {
            console.error('❌ AJAX error:', error);
            alert('Error: ' + error.message);
            button.textContent = originalText;
            button.disabled = false;
        });
        
        return false;
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Delegate click on all current and future read-more-text spans
        document.body.addEventListener('click', function(event) {
            if (event.target.classList.contains('read-more-text')) {
                const span = event.target;
                const p = span.parentElement;

                const fullText = p.getAttribute('data-full');
                const trimmedText = p.getAttribute('data-trimmed');

                if (span.textContent.trim() === 'See more') {
                    p.innerHTML = fullText + ' <span class="text-primary read-more-text" style="cursor:pointer;"> See less</span>';
                } else {
                    p.innerHTML = trimmedText + ' <span class="text-primary read-more-text" style="cursor:pointer;"> See more</span>';
                }
            }
        });
    });
</script>