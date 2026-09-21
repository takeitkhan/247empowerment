<?php
// Get the username from the URL (author_name is default WP var)
$profile = $args['profile'] ?? null;
$username = $profile['user_nicename'] ?? ($profile['username'] ?? '');

// Get the user profile instance
$profile_instance = new UserProfileData($username);
$profile = $profile_instance->getProfile();

// Validate user exists
if (!$profile || empty($profile['id'])) {
    echo '<p class="p-3">User not found.</p>';
    return;
}

$user_id = $profile['id'];

// ✅ FIXED: Fetch user posts (both authored AND wall posts from friends)
// Strategy: Fetch authored posts, then manually fetch wall posts and merge

// 1. Get posts authored by the profile owner
$authored_args = [
    'post_type' => 'post',
    'post_status' => 'publish',
    'posts_per_page' => 10,
    'author' => $user_id,
    'orderby' => 'date',
    'order' => 'DESC',
];

$authored_posts = new WP_Query($authored_args);
error_log('📊 Authored posts for user ' . $user_id . ': ' . $authored_posts->found_posts);

// 2. Get posts on profile owner's wall (by friends)
$wall_args = [
    'post_type' => 'post',
    'post_status' => 'publish',
    'posts_per_page' => 10,
    'meta_query' => [
        [
            'key' => '_post_wall_owner_id',
            'value' => $user_id,
            'compare' => '='
        ]
    ],
    'orderby' => 'date',
    'order' => 'DESC',
];

$wall_posts = new WP_Query($wall_args);
error_log('📊 Wall posts for user ' . $user_id . ': ' . $wall_posts->found_posts);

// 3. Merge the results
$all_posts = array_merge($authored_posts->posts, $wall_posts->posts);

// 4. Sort by date descending
usort($all_posts, function ($a, $b) {
    return strtotime($b->post_date) - strtotime($a->post_date);
});

// 5. Limit to 10
$all_posts = array_slice($all_posts, 0, 10);

if (!empty($all_posts)):
    foreach ($all_posts as $post):
        // Manually set up global post object for template tags to work
        setup_postdata($post);

        $post_id = $post->ID;

        // Check privacy before displaying the post
        if (!UserProfileData::canViewPost($post_id, get_current_user_id())) {
            continue; // Skip this post if user doesn't have permission
        }

        // Clean duplicate reactions for current user
        if (is_user_logged_in()) {
            clean_user_reactions($post_id, get_current_user_id());
        }

        $post_time = get_the_date('F j, Y \a\t g:i A', $post_id);
        $post_author = get_the_author_meta('display_name', $post->post_author);
        $post_image = get_the_post_thumbnail_url($post_id, 'large');

        // ✅ NEW: Get wall owner info if this is a wall post
        $wall_owner_id = get_post_meta($post_id, '_post_wall_owner_id', true);
        $is_wall_post = !empty($wall_owner_id);
        $post_author_id = get_post_field('post_author', $post_id);
        ?>


        <div class="post custom-card">
            <!-- Post Header -->
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <div class="position-relative img44">
                        <?php
                        // ✅ NEW: Show author's profile photo for wall posts, profile owner's for regular posts
                        $photo_user_id = $is_wall_post ? $post_author_id : $user_id;
                        ?>
                        <img src="<?php echo esc_url(get_user_meta($photo_user_id, 'profile_photo', true) ?: get_template_directory_uri() . '/assets/img/loggedin_images/banner.jpg'); ?>"
                            class="rounded-circle w-100 h-100 object-fit-cover" alt="Profile">
                        <img class="position-absolute active-icon"
                            src="<?php echo get_template_directory_uri(); ?>/assets/img/nd/active_icon.png" alt="">
                    </div>
                    <div class="d-flex flex-column post-user">
                        <?php if ($is_wall_post): ?>
                            <!-- Show author name with "posted on wall" indicator -->
                            <span class="p_name">
                                <?php
                                $author_profile = new UserProfileData($post_author_id);
                                $author_data = $author_profile->getProfile();
                                echo esc_html($author_data['first_name'] . ' ' . $author_data['last_name']);
                                ?>
                                <small class="text-muted" style="font-weight: normal;"> posted on wall</small>
                            </span>
                        <?php else: ?>
                            <span
                                class="p_name"><?php echo esc_html($profile['first_name'] . ' ' . $profile['last_name']); ?></span>
                        <?php endif; ?>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span><?php echo esc_html($post_time); ?></span>
                            <?php display_post_status_badge($post_id); ?>
                        </div>
                        <?php display_scheduled_time_info($post_id); ?>
                    </div>
                </div>
                <div class="dropdown">
                    <button class="p-0 border-0 text-dark btn btn-link" type="button" id="postOptions<?php echo $post_id; ?>"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?php echo get_template_directory_uri(); ?>/assets/img/nd/post_option_icon.png"
                            alt="Post Options">
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="postOptions<?php echo $post_id; ?>">
                        <?php
                        $current_user_id = get_current_user_id();
                        $is_author = (int) $current_user_id === (int) $post_author_id;
                        $is_wall_owner = $is_wall_post && (int) $current_user_id === (int) $wall_owner_id;
                        $can_delete = $is_author || $is_wall_owner;

                        // Debug logging
                        error_log('🔍 Post ' . $post_id . ' menu check: current_user=' . $current_user_id . ', author=' . $post_author_id . ', wall_owner=' . $wall_owner_id . ', is_author=' . ($is_author ? 'true' : 'false') . ', is_wall_owner=' . ($is_wall_owner ? 'true' : 'false') . ', can_delete=' . ($can_delete ? 'true' : 'false'));

                        // Show edit privacy only if user is the post author
                        if ($is_author):
                            ?>
                            <li>
                                <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                    data-bs-target="#privacyModal<?php echo $post_id; ?>">
                                    Edit Privacy
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                        <?php endif; ?>

                        <?php
                        // Show delete if user is author OR wall owner
                        if ($can_delete):
                            ?>
                            <li>
                                <a class="text-danger dropdown-item delete-post-btn" href="#"
                                    data-post-id="<?php echo $post_id; ?>">
                                    Delete Post
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Privacy Edit Modal -->
                <?php
                $post_privacy = get_post_meta($post_id, '_post_privacy', true) ?: 'only_me';
                ?>
                <div class="modal fade" id="privacyModal<?php echo $post_id; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Post Privacy</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <select class="form-select post-privacy-select" data-post-id="<?php echo $post_id; ?>"
                                    data-current-privacy="<?php echo esc_attr($post_privacy); ?>">
                                    <option value="only_me" <?php selected($post_privacy, 'only_me'); ?>>Only Me</option>
                                    <option value="referral_partners" <?php selected($post_privacy, 'referral_partners'); ?>>
                                        Only Referral Partners</option>
                                    <option value="public" <?php selected($post_privacy, 'public'); ?>>Public</option>
                                </select>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary save-privacy-btn"
                                    data-post-id="<?php echo $post_id; ?>">Save</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Post Content -->
            <?php
            $raw_content = get_the_content();
            $allowed_tags = ['br' => []];
            $sanitized_content = wp_kses($raw_content, $allowed_tags);
            $formatted_content = nl2br($sanitized_content);
            $trimmed_content = wp_trim_words(strip_tags($formatted_content), 40, '...');
            ?>
            <p class="mt-2 mb-2 post-content-text" data-full="<?php echo esc_attr($formatted_content); ?>"
                data-trimmed="<?php echo esc_attr($trimmed_content); ?>">
                <?php echo $trimmed_content; ?>
                <?php if (wp_strip_all_tags($formatted_content) !== $trimmed_content): ?>
                    <span class="text-primary read-more-text" style="cursor:pointer;"> Read more</span>
                <?php endif; ?>
            </p>

            <!-- Post Image -->
            <?php if ($post_image): ?>
                <div class="mt-3 profile-pic post-image-container">
                    <img src="<?php echo esc_url($post_image); ?>" class="w-100 h-100 object-fit-cover" alt="Post Image">
                </div>
            <?php endif; ?>

            <!-- Reactions Section -->
            <?php
            $reaction_counts = get_post_reaction_counts($post_id);
            $user_reactions = is_user_logged_in() ? get_user_reactions($post_id, get_current_user_id()) : [];
            $total_reactions = array_sum($reaction_counts);

            // Get comments count
            $comments_count = get_comments([
                'post_id' => $post_id,
                'status' => 'approve',
                'count' => true
            ]);

            $template_uri = get_template_directory_uri();
            $icons_path = $template_uri . '/assets/img/nd/icons';
            $comment_nonce = wp_create_nonce('post_comment_nonce');
            ?>

            <hr class="my-2 border-underline" />

            <!-- Reactions & Comments Stats -->
            <div class="d-flex gap-4 px-3 py-2 text-muted post-stats small">
                <?php if ($total_reactions > 0): ?>
                    <span class="d-flex align-items-center gap-1 reaction-stat">
                        <img src="<?php echo esc_url($icons_path . '/reaction.svg'); ?>" alt="Reactions"
                            style="width: 16px; height: 16px;">
                        <span><?php echo esc_html($total_reactions); ?></span>
                    </span>
                <?php endif; ?>

                <?php if ($comments_count > 0): ?>
                    <span class="d-flex align-items-center gap-1 comment-stat">
                        <img src="<?php echo esc_url($icons_path . '/comments.svg'); ?>" alt="Comments"
                            style="width: 16px; height: 16px;">
                        <span id="comment-count-<?php echo $post_id; ?>"><?php echo esc_html($comments_count); ?></span>
                    </span>
                <?php endif; ?>
            </div>

            <hr class="my-0 border-underline" />

            <!-- Action Buttons -->
            <div class="d-flex flex-wrap gap-2 px-2 pb-2">
                <button class="btn btn-sm btn-light reaction-btn" data-post-id="<?php echo $post_id; ?>" data-reaction="like"
                    title="Like">
                    <img src="<?php echo esc_url($icons_path . '/reaction.svg'); ?>" alt="Like"
                        style="width: 16px; height: 16px; margin-right: 4px;"> Like
                </button>
                <button class="btn btn-sm btn-light reaction-btn" data-post-id="<?php echo $post_id; ?>" data-reaction="love"
                    title="Love">
                    <img src="<?php echo esc_url($icons_path . '/reaction.svg'); ?>" alt="Love"
                        style="width: 16px; height: 16px; margin-right: 4px;"> Love
                </button>
                <button class="btn btn-sm btn-light reaction-btn" data-post-id="<?php echo $post_id; ?>" data-reaction="happy"
                    title="Happy">
                    <img src="<?php echo esc_url($icons_path . '/reaction.svg'); ?>" alt="Happy"
                        style="width: 16px; height: 16px; margin-right: 4px;"> Haha
                </button>
                <button class="btn btn-sm btn-light reaction-btn" data-post-id="<?php echo $post_id; ?>" data-reaction="wow"
                    title="Wow">
                    <img src="<?php echo esc_url($icons_path . '/reaction.svg'); ?>" alt="Wow"
                        style="width: 16px; height: 16px; margin-right: 4px;"> Wow
                </button>
                <button class="btn btn-sm btn-light reaction-btn" data-post-id="<?php echo $post_id; ?>" data-reaction="sad"
                    title="Sad">
                    <img src="<?php echo esc_url($icons_path . '/reaction.svg'); ?>" alt="Sad"
                        style="width: 16px; height: 16px; margin-right: 4px;"> Sad
                </button>
                <button class="btn btn-sm btn-light reaction-btn" data-post-id="<?php echo $post_id; ?>" data-reaction="angry"
                    title="Angry">
                    <img src="<?php echo esc_url($icons_path . '/reaction.svg'); ?>" alt="Angry"
                        style="width: 16px; height: 16px; margin-right: 4px;"> Angry
                </button>
            </div>

            <hr class="my-0 border-underline" />

            <!-- Comments Section -->
            <div class="px-3 pt-3">
                <!-- Comments Container -->
                <div class="mb-3 comment-section" id="comments-<?php echo $post_id; ?>"
                    data-nonce="<?php echo esc_attr($comment_nonce); ?>"></div>

                <!-- Comment Input -->
                <?php if (is_user_logged_in()): ?>
                    <div class="mt-2 pt-2 border-top">
                        <div class="d-flex align-items-start gap-2">
                            <div class="position-relative" style="width: 36px; height: 36px; flex-shrink: 0;">
                                <img src="<?php echo esc_url(get_user_meta(get_current_user_id(), 'profile_photo', true) ?: get_template_directory_uri() . '/assets/img/loggedin_images/banner.jpg'); ?>"
                                    alt="You" class="rounded-circle w-100 h-100" style="object-fit: cover;">
                            </div>
                            <div class="position-relative flex-grow-1">
                                <input type="text" class="form-control comment-input" data-post-id="<?php echo $post_id; ?>"
                                    data-nonce="<?php echo esc_attr($comment_nonce); ?>" placeholder="Write a comment..."
                                    style="border-radius: 18px; padding: 10px 14px; border: 1px solid #e0e0e0; background-color: #f0f2f5; font-size: 14px;">
                                <img src="<?php echo esc_url($icons_path . '/smile.svg'); ?>" alt="Emoji" class="position-absolute"
                                    style="right: 10px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; cursor: pointer;">
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>



        <?php
    endforeach;
    wp_reset_postdata();
else:
    echo '<p class="p-3">No posts found.</p>';
endif;
?>

<!-- ✅ NEW: JavaScript for post deletion -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const deleteButtons = document.querySelectorAll('.delete-post-btn');

        deleteButtons.forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();

                const postId = this.getAttribute('data-post-id');

                if (!confirm('Are you sure you want to delete this post?')) {
                    return;
                }

                // Make AJAX call to delete post
                fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'delete_post',
                        post_id: postId
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Find and remove the post from DOM - traverse up to find the .post container
                            const postElement = this.closest('.post');
                            if (postElement) {
                                postElement.style.opacity = '0.5';
                                postElement.style.pointerEvents = 'none';
                                setTimeout(() => {
                                    postElement.remove();
                                    // Show success message
                                    if (typeof Toastify !== 'undefined') {
                                        Toastify({
                                            text: 'Post deleted successfully',
                                            duration: 3000,
                                            gravity: 'top',
                                            position: 'right',
                                            backgroundColor: '#dc3545'
                                        }).showToast();
                                    } else {
                                        alert('Post deleted successfully');
                                    }
                                }, 300);
                            }
                        } else {
                            alert('Error: ' + (data.data?.message || 'Failed to delete post'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while deleting the post');
                    });
            });
        });
    });
</script>