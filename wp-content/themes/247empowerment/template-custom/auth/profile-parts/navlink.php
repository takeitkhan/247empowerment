
<?php
$leaderboard_api_nonce = isset($args['leaderboard_api_nonce']) ? (string) $args['leaderboard_api_nonce'] : '';
$leaderboard_api_root = isset($args['leaderboard_api_root']) ? untrailingslashit((string) $args['leaderboard_api_root']) : untrailingslashit((string) rest_url('api/v1/spg'));
$leaderboard_card_id = 'portal-leaderboard-' . wp_unique_id();
?>


<div
    class="bg-white leaderboard-card custom-card mt-4"
    id="<?php echo esc_attr($leaderboard_card_id); ?>"
    data-api-root="<?php echo esc_attr($leaderboard_api_root); ?>"
    data-api-nonce="<?php echo esc_attr($leaderboard_api_nonce); ?>">
    <div class="d-flex align-items-start justify-content-between gap-3 pb-3 border-underline">
        <div>
            <p class="leaderboard-card__eyebrow mb-1">Community Leaders</p>
            <h5 class="portal-title mb-0">Leaderboard</h5>
        </div>
        <span class="leaderboard-card__badge" data-role="badge">Top 5</span>
    </div>

    <p class="leaderboard-card__copy" data-role="copy">
        Loading member rankings...
    </p>

    <div class="leaderboard-card__summary" data-role="summary" hidden>
        <div>
            <span class="leaderboard-card__summary-label">Your Rank</span>
            <strong class="leaderboard-card__summary-rank" data-role="summary-rank">#0</strong>
        </div>
        <div class="leaderboard-card__summary-points">
            <strong data-role="summary-points">0</strong>
            <span>pts</span>
        </div>
        <div class="leaderboard-card__summary-progress" data-role="summary-progress">0% complete</div>
    </div>

    <div class="leaderboard-list" data-role="list" hidden></div>
    <p class="leaderboard-card__empty mb-0" data-role="status">Loading leaderboard...</p>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const card = document.getElementById(<?php echo wp_json_encode($leaderboard_card_id); ?>);

    if (!card) {
        return;
    }

    const apiRoot = (card.dataset.apiRoot || '').replace(/\/$/, '');
    const nonce = card.dataset.apiNonce || '';
    const badge = card.querySelector('[data-role="badge"]');
    const copy = card.querySelector('[data-role="copy"]');
    const summary = card.querySelector('[data-role="summary"]');
    const summaryRank = card.querySelector('[data-role="summary-rank"]');
    const summaryPoints = card.querySelector('[data-role="summary-points"]');
    const summaryProgress = card.querySelector('[data-role="summary-progress"]');
    const list = card.querySelector('[data-role="list"]');
    const status = card.querySelector('[data-role="status"]');

    if (!apiRoot || !nonce || !badge || !copy || !summary || !summaryRank || !summaryPoints || !summaryProgress || !list || !status) {
        return;
    }

    const leaderboardUrl = `${apiRoot}/leaderboard?nonce=${encodeURIComponent(nonce)}&limit=5&offset=0`;
    const userRankUrl = `${apiRoot}/leaderboard/user-rank?nonce=${encodeURIComponent(nonce)}&context_size=2`;
    const statsUrl = `${apiRoot}/leaderboard/stats?nonce=${encodeURIComponent(nonce)}`;

    const escapeHtml = function (value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    };

    const formatPoints = function (value) {
        const number = Number(value || 0);
        return new Intl.NumberFormat().format(number);
    };

    const formatCompletion = function (value) {
        const number = Number(value || 0);
        return Number.isInteger(number) ? String(number) : number.toFixed(2);
    };

    const createItemMarkup = function (entry) {
        const hasPhoto = !!entry.profile_photo;
        const avatarMarkup = hasPhoto
            ? `<img src="${escapeHtml(entry.profile_photo)}" alt="${escapeHtml(entry.display_name)}">`
            : escapeHtml((entry.display_name || '?').trim().charAt(0).toUpperCase() || '?');

        return `
            <div class="leaderboard-item${Number(entry.rank) <= 3 ? ' is-featured' : ''}">
                <div class="leaderboard-item__rank">#${escapeHtml(entry.rank)}</div>
                <div class="leaderboard-item__avatar">${avatarMarkup}</div>
                <div class="leaderboard-item__body">
                    <div class="leaderboard-item__name">${escapeHtml(entry.display_name || entry.username || 'Member')}</div>
                    <div class="leaderboard-item__label">Completion ${escapeHtml(formatCompletion(entry.completion))}%</div>
                </div>
                <div class="leaderboard-item__points">
                    <strong>${escapeHtml(formatPoints(entry.points))}</strong>
                    <span>pts</span>
                </div>
            </div>
        `;
    };

    Promise.all([
        fetch(leaderboardUrl, { credentials: 'same-origin' }).then((response) => response.json()),
        fetch(userRankUrl, { credentials: 'same-origin' }).then((response) => response.json()),
        fetch(statsUrl, { credentials: 'same-origin' }).then((response) => response.json())
    ])
        .then(function ([leaderboardResponse, userRankResponse, statsResponse]) {
            const entries = Array.isArray(leaderboardResponse?.data) ? leaderboardResponse.data : [];
            const totalUsers = Number(statsResponse?.data?.total_users || leaderboardResponse?.pagination?.total || 0);
            const currentUserRank = userRankResponse?.data?.user_rank || null;

            badge.textContent = `Top ${entries.length || 5}`;
            copy.textContent = totalUsers > 0
                ? `See where you stand among ${new Intl.NumberFormat().format(totalUsers)} members on the platform.`
                : 'Member rankings will appear here as platform activity grows.';

            if (currentUserRank) {
                summaryRank.textContent = `#${currentUserRank.rank}`;
                summaryPoints.textContent = formatPoints(currentUserRank.points);
                summaryProgress.textContent = `${formatCompletion(currentUserRank.completion)}% complete`;
                summary.hidden = false;
            } else {
                summary.hidden = true;
            }

            if (entries.length) {
                list.innerHTML = entries.map(createItemMarkup).join('');
                list.hidden = false;
                status.hidden = true;
            } else {
                list.innerHTML = '';
                list.hidden = true;
                status.textContent = 'No leaderboard activity yet.';
                status.hidden = false;
            }
        })
        .catch(function () {
            copy.textContent = 'Leaderboard data is unavailable right now.';
            status.textContent = 'Unable to load leaderboard.';
            status.hidden = false;
            list.hidden = true;
            summary.hidden = true;
        });
});
</script>


<!-- <div class="bg-white custom-card navbar-link profile-menu-wrapper">

    <div class="d-flex align-items-center justify-content-between profile-menu-toggle">
        <h5 class="mb-0 text-start portal-title">Menu</h5>
        <span class="toggle-icon">&#9662;</span>
    </div>

    <div class="profile-menu-content">
        <?php
        // wp_nav_menu([
        //     'theme_location' => 'profilemenu',
        //     'container'      => false,
        //     'menu_class'     => 'nav d-flex flex-column gap-2 menu-edit-profile-menu',
        //     'walker'         => new Profile_Menu_Walker(),
        // ]);
        ?>
    </div>
</div> -->


<?php
/** 
<div class="bg-white custom-card">
    <div class="d-flex align-items-center justify-content-between profile-menu-toggle">
        <h5 class="mb-0 text-start portal-title">Book Appointment</h5>
        <span class="toggle-icon">&#9662;</span>
    </div>
    <?php
    // Zoom Booking Form Section
    $user_id = get_current_user_id();

    if (!$user_id) {
        echo '<div class="alert alert-warning" role="alert">Please log in to book appointments.</div>';
    } elseif (function_exists('zoom_is_connected') && zoom_is_connected($user_id)) {
        // Zoom is connected - show the booking form
        echo do_shortcode('[zoom_book_appointment]');
    } else {
        // Zoom not connected - show connect link
    ?>
        <div class="alert alert-info" role="alert">
            <h6 class="mb-2">
                <i class="bi bi-link-45deg"></i> Connect Zoom Account
            </h6>
            <p class="mb-3 small">
                Connect your Zoom account to book and manage meetings directly from your profile.
            </p>
            <a href="<?php echo esc_url(home_url('/connect-zoom/')); ?>" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-circle"></i> Connect Zoom
            </a>
        </div>
    <?php
    }
    ?>
</div>

 */
?>