<?php
$profile = isset($args['profile']) ? $args['profile'] : [];
$user = isset($args['user']) ? $args['user'] : null;

$profileData = new UserProfileData($user);
$referrals = $profileData::getReferredUsersBy($user);

$total_referrals = count_users()['total_users'];

$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

if ($search) {
    $search_terms = array_filter(explode(' ', strtolower($search))); // ['soyaad', 'khan']

    $referrals = array_filter($referrals, function ($user) use ($search_terms) {
        $first_name = strtolower(get_user_meta($user->ID, 'first_name', true));
        $last_name = strtolower(get_user_meta($user->ID, 'last_name', true));
        $display_name = strtolower($user->display_name);
        $user_login = strtolower($user->user_login);

        $haystack = "{$first_name} {$last_name} {$display_name} {$user_login}";

        // Match if ALL words are found
        foreach ($search_terms as $term) {
            if (!str_contains($haystack, $term)) {
                return false;
            }
        }

        return true;
    });

    $referrals = array_values($referrals); // Re-index    
}

?>
<?php
$max_display_users = 15;
$referred_users_count = count($referrals);
// Note: get_template_directory_uri() must be defined in your environment
$default_profile_img = get_template_directory_uri() . '/assets/img/loggedin_images/banner.jpg';
?>

<div class="bg-white upcoming-events custom-card">
    <div class="pb-4">
        <h5 class="text-start portal-title">Referral Partners (<?php echo $referred_users_count; ?>)</h5>
    </div>
    <div class="d-flex flex-column gap-3">
        <?php
        $counter = 0;
        // Loop over the new $referrals array
        foreach ($referrals as $user_data) {

             $user_categories = $user_data['user_categories'] ?? [];

            // Initialize the names array
            $user_category_names =  $user_data['user_category_names'] ?? [];

            if ($counter >= $max_display_users) {
                break;
            }
            // 1. Get Full Name (First Name + Last Name)
            $first_name = isset($user_data['first_name']) ? $user_data['first_name'] : '';
            $last_name = isset($user_data['last_name']) ? $user_data['last_name'] : '';
            $full_name = trim($first_name . ' ' . $last_name);

            // Fallback to display_name if no full name parts are found
            if (empty($full_name)) {
                $full_name = $user_data['display_name'];
            }

            // 2. Get User Identifier for URL
            $username_for_url = !empty($user_data['user_nicename']) ? $user_data['user_nicename'] : $user_data['username'];

            // 3. Get Profile Photo URL (Use provided URL or default)
            $profile_photo_url = !empty($user_data['profile_photo']) ? $user_data['profile_photo'] : $default_profile_img;
        ?>
            <div>
                <div class="">
                    <a href="<?php echo esc_url(site_url('/u/' . $username_for_url)); ?>" class="d-flex align-items-center gap-3 text-dark text-decoration-none">
                        <div class="position-relative img44">
                            <img src="<?php echo esc_url($profile_photo_url); ?>" class="rounded-circle w-100 h-100 object-fit-cover" alt="<?php echo esc_attr($full_name); ?> Profile">
                        </div>
                        <div class="d-flex flex-column post-user">
                            <span class="p_name"><?php echo esc_html($full_name); ?></span>
                            <p class="n-text">
                                <?php
                                if (!empty($user_category_names)) {
                                    $category_name = esc_html($user_category_names[0]);
                                }

                                if ($category_name !== null) {
                                    echo $category_name;
                                } else {
                                    echo "---";
                                }
                                ?>
                            </p>
                        </div>
                    </a>
                </div>
            </div>
        <?php
            $counter++;
        }
        ?>
    </div>
    <?php if ($referred_users_count > $max_display_users) : ?>
        <div>
            <button class="d-flex align-items-center justify-content-center gap-2 pt-3 w-100 more-option">
                <img src="<?php echo get_template_directory_uri(); ?>/assets/img/nd/loading.png" alt=""> More</button>
        </div>
    <?php endif; ?>
</div>