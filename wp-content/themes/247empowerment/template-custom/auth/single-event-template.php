<?php

/**
 * Template Name: Single Event Template
 */
if (!is_user_logged_in()) {
    wp_redirect(home_url('/signin'));
    exit;
}
$current_user = wp_get_current_user();
$current_user_id = get_current_user_id();

// get query vars
$event_user   = get_query_var('event_user');  // Primary: /username/events/slug
$user_slug    = $event_user; // Use event_user for user slug
$is_shareable = get_query_var('shareable'); // '1' if shareable URL
$event_slug   = get_query_var('event_slug');

// resolve user
if ($user_slug) {
    $user = get_user_by('slug', $user_slug);
} else {
    $user = get_user_by('ID', $current_user_id);
}

// instantiate profile data safely
if ($user) {
    $target_identifier = $user_slug ? $user_slug : $user->user_login;
    $profile_data_instance = new UserProfileData($target_identifier);
    $profile = $profile_data_instance->getProfile();
} else {
    $user = null;
    $profile = null;
}

// Find event by slug
$event = get_page_by_path($event_slug, OBJECT, 'event');

if (!$event) {
    if (!$is_shareable) {
        get_header_based_on_login();
    }
    echo '<p class="text-danger">Event not found.</p>';
    if (!$is_shareable) {
        get_footer_based_on_login();
    }
    exit;
}

// Redirect non-logged-in users to login for non-shareable URLs
if (!is_user_logged_in() && !$is_shareable) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

setup_postdata($event);

// Get meta fields (with fallbacks)
$event_date     = get_post_meta($event->ID, 'event_date', true);
$event_time     = get_post_meta($event->ID, 'event_time', true);
$event_duration = get_post_meta($event->ID, 'event_duration', true);
$event_link     = get_post_meta($event->ID, 'event_link', true);
$event_price    = get_post_meta($event->ID, 'event_price', true);
$registration_type = get_post_meta($event->ID, 'registration_type', true);
$location       = get_post_meta($event->ID, 'event_location', true); // optional

$is_host = ( (int) $event->post_author === (int) $current_user_id );

// Attendee list: prefer generic event attendee meta. If absent, leave empty.
$attendee_ids = array_unique( array_map( 'intval', get_post_meta( $event->ID, '_event_attendee_id', false ) ) );
$attendees = [];
foreach ( $attendee_ids as $aid ) {
    $attendee = get_userdata( $aid );
    if ( $attendee ) {
        $attendees[] = $attendee;
    }
}

// Get taxonomy terms
$categories = wp_get_post_terms($event->ID, 'event_category', ['fields' => 'names']);
$category_list = !empty($categories) ? implode(', ', $categories) : '—';

// Thumbnail
$thumbnail_url = get_the_post_thumbnail_url($event->ID, 'large') ?: get_template_directory_uri() . '/assets/img/default-event.jpg';

// Share link
$shareable_link = home_url("/u/{$event_user}/event/{$event_slug}/?shareable=1");

?>


<?php if ($is_shareable): ?>

    <?php
    $path = $_SERVER['REQUEST_URI'] ?? '';
    $segments = explode('/', trim($path, '/'));
    $referrer_username = $segments[0] ?? null;

    $login_url = home_url('/signin');
    $register_url = home_url('/signup');

    if ($referrer_username) {
        $login_url    = add_query_arg('ref', $referrer_username, $login_url);
        $register_url = add_query_arg('ref', $referrer_username, $register_url);
    }

    // include the shareable event partial (this file should itself be valid PHP/HTML)
    include __DIR__ . '/event-parts/shareable-event.php';
    ?>

<?php else: ?>

    <?php get_header_based_on_login(); ?>
    <div class="container profile-page pt20">
        <div class="row">
            <div class="col-lg-3">
                <?php get_template_part('template-custom/auth/feed-parts/profile-card', null, ['profile' => $profile]); ?>
                <?php get_template_part('template-custom/auth/profile-parts/navlink', null, ['profile' => $profile]); ?>
            </div>

            <div class="bg-white mb-0 rounded col-lg-9 custom-card">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="post-search">
                            <div class="gap-3 post-row">
                                <div>
                                    <h1 class="pb-4 text-start portal-title">
                                        <?= esc_html(get_the_title($event)); ?>
                                    </h1>
                                </div>
                                <div class="d-flex flex-column flex-sm-row justify-content-between u-title">
                                    <div>
                                        <a href="<?= esc_url(home_url("/u/{$event_user}/events")); ?>" class="d-flex align-items-center gap-2 w-100 text-primary-color fs18 fw-medium">
                                            <img class="object-fit-contain w14" src="<?= get_template_directory_uri(); ?>/assets/img/nd/back-emp.png" alt=""> Go back
                                        </a>
                                    </div>
                                    <div class="d-flex">
                                        <div>
                                            <button id="copyLinkBtn" class="w-100 text-blue-color custom-btn-size" data-link="<?= esc_url($shareable_link); ?>">
                                                <img src="<?= get_template_directory_uri(); ?>/assets/img/nd/copy-link.png" class="mr12" alt="">Copy link
                                            </button>
                                        </div>
                                        <div>
                                            <button class="w-100 custom-btn-size background-primary" data-bs-toggle="modal" data-bs-target="#createEventModal">
                                                <img src="<?= get_template_directory_uri(); ?>/assets/img/nd/pen.png" class="mr12" alt="">Edit event
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-column mt-4">
                            <div class="pb-4">
                                <div class="img271">
                                    <img class="w-100 h-100 object-fit-cover" src="<?= esc_url($thumbnail_url); ?>" alt="<?= esc_attr(get_the_title($event)); ?>">
                                </div>
                            </div>

                            <div class="pb-4 event-details">
                                <?php if ($event_date) : ?>
                                    <p><span class="text-blue-color fw-medium">Date:</span> <?= esc_html($event_date); ?></p>
                                <?php endif; ?>

                                <?php if ($category_list) : ?>
                                    <p><span class="text-blue-color fw-medium">Category:</span> <?= esc_html($category_list); ?></p>
                                <?php endif; ?>

                                <?php if ($location) : ?>
                                    <p><span class="text-blue-color fw-medium">Location:</span> <?= esc_html($location); ?></p>
                                <?php endif; ?>

                                <?php if ($event_duration) : ?>
                                    <p><span class="text-blue-color fw-medium">Duration:</span> <?= esc_html($event_duration); ?></p>
                                <?php endif; ?>

                                <?php if ($event_time) : ?>
                                    <p><span class="text-blue-color fw-medium">Time:</span> <?= esc_html($event_time); ?></p>
                                <?php endif; ?>
                            </div>

                            <div>
                                <?= apply_filters('the_content', $event->post_content); ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="upcoming-events">
                            <div class="d-flex u-title">
                                <h5 class="pb-4 portal-title"><?= $registration_type === 'paid' ? 'Paid Registration' : 'Free Registration'; ?></h5>
                            </div>

                            <div class="d-flex flex-column gap-3 pb-4 border-underline">
                                <div>
                                    <p>Join this event and connect with like-minded people. Don’t miss your chance to participate!</p>
                                </div>
                            </div>

                            <div class="my-4">
                                <p class="d-flex align-items-center gap-3">
                                    <span class="fs24">Price:</span>
                                    <span class="fs32"><?= $event_price ? esc_html($event_price) : 'Free'; ?></span>
                                </p>
                            </div>

                            <!-- Conditional buttons: Join Meeting (if active) / Book Event -->
                            <?php
                            $livekit_room = get_post_meta($event->ID, '_mm_livekit_room', true);
                            $livekit_active = get_post_meta($event->ID, '_mm_livekit_active', true) === '1';
                            $livekit_scheduled = get_post_meta($event->ID, '_mm_livekit_scheduled', true) === '1';
                            $livekit_max = intval( get_post_meta($event->ID, '_mm_livekit_max_participants', true) ?: 0 );
                            $registered_count = count( get_post_meta($event->ID, '_event_attendee_id', false) );

                            if ( $livekit_room && $livekit_active ) : ?>
                                <div class="pb-4">
                                    <a href="<?php echo esc_url( home_url('/room?name=' . rawurlencode($livekit_room) ) ); ?>" class="custom-btn">Join Meeting</a>
                                </div>
                            <?php else: ?>
                                <?php if ( $livekit_scheduled ) :
                                    $scheduled_for = get_post_meta($event->ID, '_mm_livekit_scheduled_for', true );
                                    $when_text = $scheduled_for ? date_i18n( get_option('date_format') . ' ' . get_option('time_format'), intval($scheduled_for) ) : 'Scheduled';
                                ?>
                                    <div class="pb-2"><small class="text-muted">Meeting scheduled for: <?php echo esc_html( $when_text ); ?></small></div>
                                <?php endif; ?>

                                <div class="pb-4">
                                    <?php if ( $registration_type === 'paid' ): ?>
                                        <button id="bookEventBtn" class="custom-btn">Book Event</button>
                                    <?php else: ?>
                                        <button id="bookEventBtn" class="custom-btn">Register for Event</button>
                                    <?php endif; ?>
                                    <?php if ( in_array( $current_user_id, $attendee_ids ) ): ?>
                                        <button id="cancelBookingBtn" class="custom-btn-outline">Cancel Booking</button>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div class="pb-4">
                                <button class="custom-btn">Add People</button>
                            </div>

                            <div class="d-flex align-content-center justify-content-center pb-4">
                                <button class="custom-btn-outline text-blue-color fs18 fw-medium">
                                    <img class="mr12" src="<?= get_template_directory_uri(); ?>/assets/img/nd/share_png.png" alt=""> Share
                                </button>
                            </div>

                            <div>
                                <p class="pb-4 text-center fs20">Limited spots available — register now!</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Guest List Section -->
                <?php if ( ! empty( $attendees ) ) : ?>
                    <div class="mt-4 row">
                        <div class="col-lg-8">
                            <h4 class="pb-3 text-blue-color fw-medium">Attendees (<?php echo count( $attendees ); ?>)</h4>
                            <div class="d-flex flex-wrap gap-3">
                                <?php foreach ( $attendees as $attendee ) :
                                    $avatar = get_avatar_url( $attendee->ID, [ 'size' => 48 ] );
                                    $profile_link = home_url( "/u/{$attendee->user_login}/" );
                                ?>
                                    <div class="card" style="width: 140px; text-align: center; padding: 10px; border: 1px solid #e0e0e0; border-radius: 6px;">
                                        <a href="<?php echo esc_url( $profile_link ); ?>" style="text-decoration: none;">
                                            <img src="<?php echo esc_url( $avatar ); ?>" alt="" style="width: 48px; height: 48px; border-radius: 50%; margin-bottom: 8px;">
                                            <p style="margin: 0; font-size: 13px; font-weight: 500; color: #333;"><?php echo esc_html( $attendee->display_name ); ?></p>
                                            <p style="margin: 0; font-size: 11px; color: #999;">@<?php echo esc_html( $attendee->user_login ); ?></p>
                                            <!-- Show contact info only to the host -->
                                            <?php if ( $is_host ) : ?>
                                                <p style="margin: 6px 0 0 0; font-size: 10px; color: #555; word-break: break-all;">
                                                    <?php echo esc_html( $attendee->user_email ); ?>
                                                </p>
                                            <?php endif; ?>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
<?php endif; ?>

<?php wp_reset_postdata(); ?>

<?php if (!$is_shareable): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var button = document.getElementById('copyLinkBtn');
            var link = <?php echo json_encode($shareable_link); ?>;

            if (button) {
                button.addEventListener('click', function() {
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(link)
                            .then(function() {
                                alert("Sharable link copied!");
                            })
                            .catch(function() {
                                fallbackCopy(link);
                            });
                    } else {
                        fallbackCopy(link);
                    }
                });
            }

            function fallbackCopy(text) {
                var textarea = document.createElement("textarea");
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                try {
                    document.execCommand('copy');
                    alert("Sharable link copied!");
                } catch (err) {
                    alert("Failed to copy the link.");
                }
                document.body.removeChild(textarea);
            }
        });
    </script>
    <script>
        (function(){
            // Booking / Register actions
            async function ajax(action, data){
                data = data || {};
                data.action = action;
                data.nonce = '<?php echo wp_create_nonce('mm_livekit_nonce'); ?>';
                const res = await fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', credentials: 'same-origin', body: new URLSearchParams(data) });
                return res.json();
            }

            var bookBtn = document.getElementById('bookEventBtn');
            var cancelBtn = document.getElementById('cancelBookingBtn');
            var eventId = <?php echo intval($event->ID); ?>;

            if ( bookBtn ){
                bookBtn.addEventListener('click', async function(){
                    bookBtn.disabled = true; bookBtn.innerText = 'Booking...';
                    const r = await ajax('mm_book_event', { event_id: eventId });
                    if ( r && r.success ){
                        alert('Registered'); location.reload();
                    } else {
                        alert(r.data || 'Could not register'); bookBtn.disabled = false; bookBtn.innerText = 'Register for Event';
                    }
                });
            }

            if ( cancelBtn ){
                cancelBtn.addEventListener('click', async function(){
                    cancelBtn.disabled = true; cancelBtn.innerText = 'Cancelling...';
                    const r = await ajax('mm_cancel_booking', { event_id: eventId });
                    if ( r && r.success ){
                        alert('Booking cancelled'); location.reload();
                    } else {
                        alert(r.data || 'Could not cancel'); cancelBtn.disabled = false; cancelBtn.innerText = 'Cancel Booking';
                    }
                });
            }
        })();
    </script>
<?php endif; ?>

<?php get_footer_based_on_login(); ?>