<?php
$current_user = wp_get_current_user();
$username = $current_user->user_nicename;
$referrals_url = site_url("/u/{$username}/referrals/");

// Get the search query
$search_query = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

// Example: recent users (replace with your real logic)
$recent_users = get_users([
    'number' => 2,
    'orderby' => 'registered',
    'order' => 'DESC',
]);

// Example: upcoming events (replace with your event post type)
$events = get_posts([
    'post_type' => 'event',
    'posts_per_page' => 2,
    'orderby' => 'meta_value',
    'meta_key' => 'event_date', // if you store event date as meta
    'order' => 'ASC',
]);
?>

<div class="position-relative search-wrapper">

    <!-- Search Button -->
    <div>
        <button
            class="d-inline-flex position-relative align-items-center justify-content-center bg-supporting rounded-circle active img44 search-icon btn-custom"
            type="button"
            id="toggleSearchBox">
            <img
                class="text-center search-icon"
                src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/nd/searach.png'); ?>"
                alt="Search">
        </button>
    </div>

    <!-- Search Box -->
    <div class="shadow-sm search-box custom-card" id="searchBox" style="display: none;">
        <form method="get" action="<?php echo esc_url($referrals_url); ?>">
            <input
                type="text"
                name="search"
                value="<?php echo esc_attr($search_query); ?>"
                class="input"
                placeholder="Search..."
                id="searchInput" />
        </form>

        <div class="search-results">

            <!-- Recent Users -->
            <?php if (!empty($recent_users)) : ?>
                <div class="my-3">
                    <span class="text-color-neutral">Recent</span>
                </div>
                <?php foreach ($recent_users as $user) :
                    $avatar = get_avatar_url($user->ID);
                    $profile_link = site_url("/u/{$user->user_nicename}/");
                ?>
                    <a href="<?php echo esc_url($profile_link); ?>" class="d-flex align-items-center gap-3 pb-3 text-reset">
                        <div class="position-relative img44">
                            <img src="<?php echo esc_url($avatar); ?>" class="rounded-circle w-100 h-100 object-fit-cover" alt="<?php echo esc_attr($user->display_name); ?>">
                            <img class="position-absolute active-icon" src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/nd/active_icon.png'); ?>" alt="">
                        </div>
                        <div class="d-flex flex-column post-user">
                            <span class="p_name"><?php echo esc_html($user->display_name); ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Events -->
            <?php if (!empty($events)) : ?>
                <div class="my-3">
                    <span class="text-color-neutral">Event</span>
                </div>
                <?php foreach ($events as $event) :
                    $event_date = get_post_meta($event->ID, 'event_date', true);
                    $event_date = $event_date ? date('M d', strtotime($event_date)) : '';
                ?>
                    <div class="d-flex align-items-center gap-3 pb-3 border-underline event">
                        <span class="event-date"><?php echo esc_html($event_date); ?></span>
                        <div>
                            <span class="fw-medium"><?php echo esc_html(get_the_title($event)); ?></span><br>
                            <span class="fs14"><?php echo esc_html(get_post_meta($event->ID, 'event_host', true)); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
    (function() {

        const toggleBtn = document.getElementById('toggleSearchBox');
        const searchBox = document.getElementById('searchBox');
        const input = document.getElementById('searchInput');

        if (!toggleBtn || !searchBox || !input) return;

        /* Toggle search box */
        toggleBtn.addEventListener('click', function() {
            const isOpen = searchBox.style.display === 'block';
            searchBox.style.display = isOpen ? 'none' : 'block';

            if (!isOpen) {
                setTimeout(() => input.focus(), 100);
            }
        });

        /* Elastic-style search redirect */
        let debounceTimer = null;

        input.addEventListener('keyup', function(e) {

            const query = this.value.trim();

            // Enter key → immediate redirect
            if (e.key === 'Enter') {
                e.preventDefault();
                redirect(query);
                return;
            }

            // Elastic debounce (redirect after typing pause)
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                if (query.length >= 2) {
                    redirect(query);
                }
            }, 600);
        });

        function redirect(query) {
            if (!query) return;

            const url = new URL("<?php echo esc_url($referrals_url); ?>");
            url.searchParams.set('search', query);

            window.location.href = url.toString();
        }

    })();
</script>