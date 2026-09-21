<?php
if (!is_user_logged_in()) {
    wp_redirect(home_url('/signin'));
    exit;
}

get_header_based_on_login();

/* -------------------------------------------------
 * Resolve target user
 * ------------------------------------------------- */
$referral_user_slug = get_query_var('referral_user');
$current_user_id    = get_current_user_id();

$user = $referral_user_slug
    ? get_user_by('slug', $referral_user_slug)
    : get_user_by('id', $current_user_id);

if (!$user) {
    echo '<div class="py-5 container"><h2>User not found</h2></div>';
    get_footer_based_on_login();
    return;
}

/* -------------------------------------------------
 * Params
 * ------------------------------------------------- */
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$sort   = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'recent';

/* -------------------------------------------------
 * Fetch + normalize referrals
 * ------------------------------------------------- */
$raw_referrals = UserProfileData::getReferredUsersBy($user);
$referrals = [];

foreach ($raw_referrals as $ref) {
    $ref_id = is_array($ref)
        ? ($ref['id'] ?? 0)
        : ($ref->ID ?? 0);

    if (!$ref_id) continue;

    $profile = (new UserProfileData($ref_id))->getProfile();
    if ($profile) {
        $referrals[] = $profile;
    }
}

/* -------------------------------------------------
 * Search filter
 * ------------------------------------------------- */
if ($search) {
    $terms = array_filter(explode(' ', strtolower($search)));

    $referrals = array_filter($referrals, function ($p) use ($terms) {
        $haystack = strtolower(
            $p['first_name'] . ' ' .
                $p['last_name'] . ' ' .
                $p['username'] . ' ' .
                ($p['about_me_short'] ?? '')
        );

        foreach ($terms as $t) {
            if (!str_contains($haystack, $t)) {
                return false;
            }
        }
        return true;
    });

    $referrals = array_values($referrals);
}

/* -------------------------------------------------
 * Sorting
 * ------------------------------------------------- */
usort($referrals, function ($a, $b) use ($sort) {

    switch ($sort) {
        case 'name_asc':
            return strcasecmp($a['first_name'] . $a['last_name'], $b['first_name'] . $b['last_name']);

        case 'name_desc':
            return strcasecmp($b['first_name'] . $b['last_name'], $a['first_name'] . $a['last_name']);

        case 'recent':
        default:
            return $b['id'] <=> $a['id']; // newest first
    }
});

$referred_users_count = count($referrals);
?>

<div class="container profile-page pt20">
    <div class="row">
        <div class="col-lg-3">
            <?php get_template_part('template-custom/auth/feed-parts/profile-card', null, ['profile' => $profile, 'user' => $user]); ?>
            <?php get_template_part('template-custom/auth/profile-parts/navlink', null, ['profile' => $profile]); ?>
        </div>
        <div class="col-lg-9">
            <div class="bg-white referral_partner custom-card">
                <div class="w-100">
                    <div class="upcoming-events">
                        <div class="d-flex justify-content-start pb-3 text-start">
                            <h5 class="portal-title">
                                <?php echo esc_html($user->first_name . ' ' . $user->last_name); ?>’s Referral Partners
                            </h5>
                        </div>
                        <div class="pb-4">
                            <?php echo $referred_users_count; ?> referral partners
                        </div>

                    </div>
                </div>
                <div class="">
                    <div class="d-flex align-items-center justify-content-between gap-3 filter-bar">
                        <!-- Sort Section -->
                        <div class="d-flex align-items-center gap-2 w-100">
                            <p class="fs14">Sort by</p>

                            <div class="select-wrapper">

                                <div class="d-flex align-items-center gap-0">
                                    <img class="" src="<?= get_template_directory_uri(); ?>/assets/img/nd/filter.png" alt="">
                                    <div class="">
                                        <select class="custom-select" id="sort-select" name="sort">
                                            <option value="recent" <?= $sort === 'recent' ? 'selected' : '' ?>>
                                                Recently joined
                                            </option>
                                            <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>
                                                Name (A-Z)
                                            </option>
                                            <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>
                                                Name (Z-A)
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Search Section -->
                        <div class="flex-grow-1 w-100 search-wrapper">
                            <input type="text" id="search-input" class="form-control input" placeholder="Search by name" value="<?php echo esc_attr($search); ?>">
                        </div>
                    </div>

                </div>
            </div>

            <div class="bg-white custom-card">
                <div class="flex flex-column gap-3" id="referrals-grid">
                    <?php
                    $count = 0;
                    foreach ($referrals as $profile) :
                        if ($count >= 8) break;
                        
                        $photo = $profile['profile_photo']
                            ?: 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($profile['email']))) . '?s=150&d=mm';
                    ?>
                        <div class="d-flex flex-column flex-lg-row justify-content-between py-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="img60">
                                    <img src="<?= esc_url($photo); ?>"
                                        class="w-100 h-100 object-fit-cover"
                                        alt="<?= esc_attr($profile['first_name']); ?>">
                                </div>

                                <div class="post-user">
                                    <a href="<?= esc_url($profile['profile_url']); ?>" class="fw-bold">
                                        <?= esc_html($profile['first_name'] . ' ' . $profile['last_name']); ?>
                                    </a>
                                    <?php if ($profile['about_me_short']) { ?>
                                        <span class="px-2">
                                            <i class="far fa-bookmark"></i>
                                            <?= esc_html($profile['about_me_short']); ?>
                                        </span>
                                    <?php } ?>

                                    <?php if (!empty($profile['user_category_names'])) : ?>
                                        <div class="fs14">
                                            <i class="fas fa-briefcase"></i> <?= esc_html(implode(', ', $profile['user_category_names'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="dropdown">
                                <button class="btn" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <button class="dropdown-item remove-partner-btn"
                                            data-user-id="<?= esc_attr($profile['id']); ?>">
                                            Remove Partner
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    <?php
                        $count++;
                    endforeach;
                    ?>
                </div>

                <?php if ($referred_users_count > 8): ?>
                    <div class="mt-4 text-center">
                        <button id="load-more-referrals" class="custom-btn">Load More</button>
                    </div>
                <?php endif; ?>
            </div>


        </div>

        <!-- Upcoming Events -->
        <!-- <div class="col-lg-3">
            <div class="bg-white upcoming-events custom-card">
                <div class="d-flex align-items-center justify-content-between pb-4 u-title">
                    <h5 class="portal-title">Events from partners</h5>
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
                    <button class="d-flex align-items-center justify-content-center gap-2 pt-3 w-100 more-option"><img src="<?= get_template_directory_uri(); ?>/assets/img/nd/loading.png" alt=""> More</button>
                </div>
            </div>
        </div> -->

    </div>
</div>

<script>
    document.getElementById('sort-select')?.addEventListener('change', function() {
        const params = new URLSearchParams(window.location.search);
        params.set('sort', this.value);
        params.delete('offset'); // reset pagination
        window.location.search = params.toString();
    });


    let offset = 8;
    document.getElementById('load-more-referrals')?.addEventListener('click', function() {
        const btn = this;
        btn.disabled = true;

        const sort = document.getElementById('sort-select')?.value || 'recent';
        const search = document.getElementById('search-input')?.value || '';

        fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=load_more_referrals' +
                '&user=<?php echo $user->ID; ?>' +
                '&offset=' + offset +
                '&sort=' + sort +
                '&search=' + encodeURIComponent(search)
            )
            .then(res => res.json())
            .then(res => {
                if (res.success && res.data) {
                    document.getElementById('referrals-grid').insertAdjacentHTML('beforeend', res.data);
                    offset += 8;
                    btn.disabled = false;
                } else {
                    btn.textContent = 'No more referrals';
                }
            });
    });
    document.getElementById('search-input')?.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();

            const params = new URLSearchParams(window.location.search);
            const value = this.value.trim();

            if (value) {
                params.set('search', value);
            } else {
                params.delete('search');
            }

            params.delete('offset'); // reset pagination
            window.location.search = params.toString();
        }
    });
</script>


<?php get_footer_based_on_login(); ?>