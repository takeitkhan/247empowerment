<?php

/**
 * Template Name: Logged In Events
 */

if (!is_user_logged_in()) {
    wp_redirect(home_url('/signin'));
    exit;
}
get_header_based_on_login();

$current_user = wp_get_current_user();

// Get current logged-in user ID (used as a fallback if no slug is provided)
$current_user_id = get_current_user_id();

// 1. Get the user slug from the query variable (for /username/events/ URLs)
$user_slug = get_query_var('event_user');

// 2. Determine the target user
if ($user_slug) {
    // If a slug is present, try to get the user by their slug (login or nicename)
    $user = get_user_by('slug', $user_slug);
} else {
    // If no slug, fall back to the currently logged-in user
    $user = get_user_by('ID', $current_user_id);
}

// 3. Instantiate the UserProfileData class and get the profile array
if ($user) {
    // We pass the WP_User object to the class constructor, or the ID/slug depending on the class's constructor.
    // Given your original line: $profile = (new UserProfileData($user_slug))->getProfile();
    // We'll update it to pass the $user object for better data handling, assuming the class supports it.
    // If the class REQUIRES a slug, use $user_slug or $user->user_login.

    // Option A: If UserProfileData takes a WP_User object (Recommended)
    $profile_data_instance = new UserProfileData($user);

    // Option B: If UserProfileData only takes the slug (Sticking closer to your original code)
    // Use the slug if present, otherwise use the current user's login.
    $target_identifier = $user_slug ? $user_slug : $user->user_login;
    $profile_data_instance = new UserProfileData($target_identifier);

    // Get the profile array
    $profile = $profile_data_instance->getProfile();
} else {
    // Set variables to null if no user could be determined
    $user = null;
    $profile = null;
}
?>

<div class="container profile-page pt20">
    <div class="row">
        <div class="col-lg-3">
            <?php get_template_part('template-custom/auth/feed-parts/profile-card', null, ['profile' => $profile]); ?>
            <?php get_template_part('template-custom/auth/profile-parts/navlink', null, ['profile' => $profile]); ?>
        </div>

        <!-- Main Feed -->
        <div class="mb-4 col-lg-9">
            <div class="custom-card post-search">
                <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center gap-3 text-start res-card">
                    <div class="gap-3 mb-2 post-row">
                        <div class="d-flex pb-3 u-title">
                            <h5 class="portal-title">Events</h5>
                        </div>
                        <p>Browse upcoming events from your favorite organizers.</p>
                    </div>
                    <!-- Create Event Button -->
                    <?php if (is_user_logged_in()): ?>
                        <div>
                            <button class="d-flex align-items-center justify-content-center gap-2 w-100 custom-btn"
                                data-bs-toggle="modal" data-bs-target="#createEventModal">
                                <img class="pe-1" src="<?= get_template_directory_uri(); ?>/assets/img/nd/plus.png" alt=""> Create an event
                            </button>
                        </div>
                    <?php endif; ?>

                    <!-- Create Event Modal -->
                    <div class="modal fade" id="createEventModal" tabindex="-1" aria-labelledby="createEventModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="shadow border-0 rounded-4 modal-content custom-card">
                                <div class="mb-0 pb-0 border-0 modal-header">
                                    <h5 class="text-start portal-title" id="createEventModalLabel">Create new event</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    <form class="row g-3" method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                        <input type="hidden" name="action" value="submit_event_form">
                                        <?php wp_nonce_field('event_submission', 'event_submission_nonce'); ?>

                                        <!-- Event Name -->
                                        <div class="col-12">
                                            <label class="form-label">Event Name <span>*</span></label>
                                            <input type="text" name="event_title" class="form-control input" placeholder="ex. Empower Growth Webinar" required>
                                        </div>

                                        <!-- Event Type -->
                                        <div class="d-flex flex-column post-user col-12">
                                            <label class="form-label">Event Category <span>*</span></label>

                                            <?php
                                            $event_categories = get_terms([
                                                'taxonomy'   => 'event_category',
                                                'hide_empty' => false,
                                            ]);

                                            $selected_slug = ''; // For new posts, leave empty
                                            ?>

                                            <select name="event_category" class="bg-neutral-color border-0 w-auto input" required>
                                                <option value="">Select Category</option>
                                                <?php foreach ($event_categories as $cat) : ?>
                                                    <option value="<?php echo esc_attr($cat->slug); ?>" <?php selected($selected_slug, $cat->slug); ?>>
                                                        <?php echo esc_html($cat->name); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Date, Time, Duration -->
                                        <div class="col-md-4">
                                            <label class="form-label">Date <span>*</span></label>
                                            <input type="date" name="event_date" class="form-control input" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Time <span>*</span></label>
                                            <input type="time" name="event_time" class="form-control input" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Schedule as LiveKit Meeting?</label>
                                            <div class="d-flex align-items-center gap-2">
                                                <input type="checkbox" id="livekit_meeting" name="livekit_meeting" value="1">
                                                <label for="livekit_meeting" class="mb-0">Yes, schedule a video meeting</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Duration <span>*</span></label>
                                            <input type="text" name="event_duration" class="form-control input" placeholder="ex. 3h 45min" required>
                                        </div>

                                        <!-- Description -->
                                        <div class="col-12">
                                            <label class="form-label">Description (optional)</label>
                                            <textarea name="event_description" class="form-control input" rows="3" placeholder="ex. An inspiring group session to boost confidence and break mental barriers"></textarea>
                                        </div>

                                        <!-- Event Link -->
                                        <div class="col-12">
                                            <label class="form-label">Event Link <span>*</span></label>
                                            <input type="url" name="event_link" class="form-control input" placeholder="ex. zoom.com/djslslkdnbkfn" required>
                                        </div>

                                        <!-- LiveKit options (shown when scheduling a meeting) -->
                                        <div class="col-12 livekit-options" style="display:none; margin-top:8px;">
                                            <div class="row g-2">
                                                <div class="col-md-4">
                                                    <label class="form-label">Video Enabled</label>
                                                    <select name="mm_is_video" class="form-control input">
                                                        <option value="1" selected>Yes (audio+video)</option>
                                                        <option value="0">Audio Only</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Max Participants</label>
                                                    <input type="number" name="mm_max_participants" class="form-control input" value="40" min="2" max="200">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Room Name (optional)</label>
                                                    <input type="text" name="mm_room_name" class="form-control input" placeholder="auto-generated if left empty">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Event Cover -->
                                        <div class="col-12">
                                            <label class="form-label">Event Cover (optional)</label>
                                            <label for="eventCover" class="d-flex align-items-center justify-content-center cursor-pointer event-cover-upload gap12">
                                                <img src="<?= get_template_directory_uri(); ?>/assets/img/nd/gallery.png" alt="Upload" class="icon-img">
                                                <span>Upload image</span>
                                            </label>
                                            <input type="file" name="eventCover" class="form-control d-none" id="eventCover" accept="image/*">

                                            <!-- Immediate preview -->
                                            <div id="eventCoverPreviewWrap" class="position-relative mt-2 d-none">
                                                <img id="eventCoverPreview" src="" alt="Event cover preview" class="border rounded img-fluid" style="max-height:220px;object-fit:cover;">
                                                <button type="button" id="eventCoverRemove" class="position-absolute border btn btn-sm btn-light" style="top:6px;right:6px;" aria-label="Remove image">
                                                    &times;
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Registration Type -->
                                        <div class="d-flex flex-column flex-md-row align-content-center gap-2 gap-md-5 col-12">
                                            <div>
                                                <label class="form-label">Registration Type <span>*</span></label>
                                                <div class="d-flex align-items-center gap-5 mt-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="registrationType" id="free" value="Free" checked>
                                                        <label class="form-check-label" for="free">Free</label>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2 form-check">
                                                        <input class="form-check-input" type="radio" name="registrationType" id="paid" value="Paid">
                                                        <label class="form-check-label" for="paid">Paid</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div>
                                                <label class="form-label">Price (if paid)</label>
                                                <input type="text" name="event_price" class="w-100 form-control input" placeholder="ex. $75">
                                            </div>
                                        </div>

                                        <!-- Submit -->
                                        <div class="border-0 modal-footer">
                                            <button type="button" class="w-auto text-blue-color custom-btn-size" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="w-auto custom-btn">Create Event</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <?php if (isset($_GET['event_submitted']) && $_GET['event_submitted'] === 'true'): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        alert('✅ Event submitted successfully!');
                    });
                </script>
            <?php endif; ?>

            <script>
                // Event Cover: immediate preview on file select
                (function () {
                    document.addEventListener('DOMContentLoaded', function () {
                        var input      = document.getElementById('eventCover');
                        var previewImg = document.getElementById('eventCoverPreview');
                        var wrap       = document.getElementById('eventCoverPreviewWrap');
                        var removeBtn  = document.getElementById('eventCoverRemove');
                        if (!input || !previewImg || !wrap) return;

                        input.addEventListener('change', function () {
                            var file = this.files && this.files[0];
                            if (!file || !file.type || file.type.indexOf('image/') !== 0) {
                                wrap.classList.add('d-none');
                                previewImg.src = '';
                                return;
                            }
                            var reader = new FileReader();
                            reader.onload = function (e) {
                                previewImg.src = e.target.result;
                                wrap.classList.remove('d-none');
                            };
                            reader.readAsDataURL(file);
                        });

                        if (removeBtn) {
                            removeBtn.addEventListener('click', function () {
                                input.value = '';
                                previewImg.src = '';
                                wrap.classList.add('d-none');
                            });
                        }
                    });
                })();
            </script>
            <script>
                // Toggle LiveKit options visibility when checkbox checked
                document.addEventListener('DOMContentLoaded', function(){
                    var lk = document.getElementById('livekit_meeting');
                    var opts = document.querySelectorAll('.livekit-options');
                    if (!lk) return;
                    function update(){
                        opts.forEach(function(el){ el.style.display = lk.checked ? 'block' : 'none'; });
                    }
                    lk.addEventListener('change', update);
                    update();
                });
            </script>
            <div class="d-flex flex-column">
                <?php
                $store_user = get_query_var('store_user');
                $category_slug = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';

                $current_user = wp_get_current_user();
                // Get referral partners
                $referred_users = UserProfileData::getReferredUsersBy($current_user);

                // Collect author IDs: include current user + referred users
                $author_ids = [$current_user->ID];

                if (!empty($referred_users)) {
                    foreach ($referred_users as $ref_user) {
                        $author_ids[] = $ref_user['id']; // from your enriched array
                    }
                }

                $args = [
                    'post_type'      => 'event',
                    'posts_per_page' => -1,
                    'author__in'     => $author_ids, // 👈 include both personal + referral partner events
                ];

                if (!empty($category_slug)) {
                    $args['tax_query'] = [
                        [
                            'taxonomy' => 'event_category',
                            'field'    => 'slug',
                            'terms'    => $category_slug,
                        ],
                    ];
                }

                $query = new WP_Query($args);

                if ($query->have_posts()) : ?>

                    <?php
                    while ($query->have_posts()) : $query->the_post();

                        $event_date = get_field('event_date');
                        $location   = get_field('location');
                        $thumbnail_url = get_the_post_thumbnail_url(get_the_ID(), 'medium') ?: '/img/banner.jpg';
                        $event_user = get_query_var('event_user'); // username from URL
                        $author_user_nicename = get_the_author_meta('user_nicename');
                        $event_slug = get_post_field('post_name', get_the_ID());
                        $event_registration_type = get_post_field('registration_type', get_the_ID());
                        $event_price             = get_post_meta(get_the_ID(), 'event_price', true);
                        $categories = wp_get_post_terms(get_the_ID(), 'event_category', ['fields' => 'names']);
                        $category_list = !empty($categories) ? implode(', ', $categories) : '';

                        // echo '<pre>';
                        // //     var_dump( wp_get_post_terms( get_the_ID(), 'event_category', [ 'fields' => 'slugs' ] ) );
                        // var_dump($event_registration_type);
                        // echo '</pre>';

                        if ($event_registration_type === 'Paid' && !empty($event_price)) {
                            $display_type = '$' . number_format((float)$event_price, 2); // Format price
                        } else {
                            $display_type = 'Free';
                        }
                        $custom_permalink = home_url("/u/{$author_user_nicename}/event/{$event_slug}/");
                    ?>
                        <div class="bg-white p-3 custom-card">
                            <div class="d-md-flex">
                                <!-- Image -->
                                <div class="flex-shrink-0 img240">
                                    <a href="<?= esc_url($custom_permalink); ?>">
                                        <img class="w-100 h-100 object-fit-cover" src="<?= esc_url($thumbnail_url); ?>" alt="<?= esc_attr(get_the_title()); ?>">
                                    </a>
                                </div>

                                <div class="d-flex flex-grow-1 flex-column justify-content-between ms-3">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div>
                                            <?php
                                            $author_id   = get_the_author_meta('ID');
                                            $author_name = get_the_author_meta('display_name');
                                            $label       = '';

                                            if ($author_id == $current_user->ID) {
                                                $label = 'Created by You';
                                            } else {
                                                $is_referral_partner = false;
                                                if (!empty($referred_users)) {
                                                    foreach ($referred_users as $ref_user) {
                                                        if ($ref_user['id'] == $author_id) {
                                                            $is_referral_partner = true;
                                                            break;
                                                        }
                                                    }
                                                }
                                                if ($is_referral_partner) {
                                                    $label = "Created by Referral Partner";
                                                } else {
                                                    $label = "Created by {$author_name}";
                                                }
                                            }
                                            ?>
                                            <span class="fs14 emp-color">
                                                <?= esc_html($label); ?>
                                            </span>

                                            <!-- Event Title -->
                                            <h5 class="mt-1 mb-1">
                                                <a href="<?= esc_url($custom_permalink); ?>">
                                                    <?= esc_html(get_the_title()); ?>
                                                </a>
                                            </h5>

                                            <!-- Event Excerpt -->
                                            <p class="mb-1">
                                                <?= esc_html(get_the_excerpt()); ?>
                                            </p>

                                            <!-- Event Date & Location -->
                                            <div class="d-flex flex-column gap-2">
                                                <?php if ($event_date) : ?>
                                                    <div>
                                                        <span class="text-blue-color fw-medium">Date:</span>
                                                        <span><?= esc_html($event_date); ?></span>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($category_list) : ?>
                                                    <div>
                                                        <span class="text-blue-color fw-medium">Category:</span>
                                                        <span><?= esc_html($category_list); ?></span>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($location) : ?>
                                                    <div>
                                                        <span class="text-blue-color fw-medium">Location:</span>
                                                        <span><?= esc_html($location); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                        </div>

                                        <!-- Event Type (far right top) -->
                                        <div class="ms-3">
                                            <span class="gradient-fs24">
                                                <?= esc_html($display_type); ?>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Bottom: View Event Button (aligned right) -->
                                    <div class="d-flex justify-content-end mt-3">
                                        <a href="<?= esc_url($custom_permalink); ?>" class="text-white custom-btn-size background-primary">
                                            View Event
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php endwhile; ?>

                    <?php wp_reset_postdata(); ?>
                <?php else : ?>
                    <p>No events found.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Upcoming Events -->
        <!-- <div class="col-lg-3">
            <div class="bg-white upcoming-events custom-card">
                <div class="d-flex align-items-center justify-content-between pb-4 u-title">
                    <h5 class="portal-title">Popular events</h5>
                    <span class="">12</span>
                </div>
                <div class="d-flex align-items-center gap-3 pb-3 border-underline event">
                    <span class="event-date">Oct 20</span>
                    <div>
                        <span class="fw-medium">Birthday</span><br>
                        <span class="fs14">Dr. Alicia Stone</span>
                    </div>
                </div>
                <div>
                    <button class="d-flex align-items-center justify-content-center gap-2 pt-3 w-100 more-option"><img src="./images/loading.png" alt=""> More</button>
                </div>
            </div>
        </div> -->
        <!-- <div>
            <nav aria-label="Page navigation example" class="d-flex justify-content-center py-5">
                <ul class="mb-0 pagination custom-pagination">
                    <li class="page-item">
                        <a class="page-link" href="#" aria-label="Previous">
                            <img src="./images/prev.png" alt="">
                        </a>
                    </li>
                    <li class="page-item"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item"><a class="page-link" href="#">...</a></li>
                    <li class="page-item"><a class="page-link" href="#">17</a></li>
                    <li class="page-item">
                        <a class="page-link" href="#" aria-label="Next">
                            <img src="./images/next.png" alt="">
                        </a>
                    </li>
                </ul>
            </nav>
        </div> -->
    </div>
</div>
<?php get_footer_based_on_login(); ?>