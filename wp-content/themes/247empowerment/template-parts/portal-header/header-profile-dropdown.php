<?php
// PHP logic to retrieve user data once at the beginning
$current_user = is_user_logged_in() ? wp_get_current_user() : null;
$is_admin = $current_user && current_user_can('administrator');

if ($current_user) {
    // Determine the user's full name, falling back to display_name
    $first_name = $current_user->first_name;
    $last_name = $current_user->last_name;
    $full_name = trim($first_name . ' ' . $last_name) ?: $current_user->display_name;

    // Get the actual profile photo URL from user meta, or use a default
    // Note: It's common to use get_avatar_url for a user's profile picture if using Gravatar or a plugin.
    // If you use 'profile_photo' meta key, use it as is.
    $profile_photo_url = get_user_meta($current_user->ID, 'profile_photo', true);

    // Fallback if the profile photo meta key is empty (using a generic avatar/default theme image)
    if (empty($profile_photo_url)) {
        // Use get_avatar_url if you prefer Gravatar/default WordPress avatars
        // $profile_photo_url = get_avatar_url($current_user->ID);
        // OR use the theme's default banner image as your fallback
        $profile_photo_url = get_template_directory_uri() . '/assets/img/loggedin_images/banner.jpg';
    }

    // Determine profile link slug (using user_login or user_nicename for custom URL structure)
    $user_slug = $current_user->user_nicename ?: $current_user->user_login;
    $profile_url = home_url('/u/' . $user_slug);

    // Use slug if present, otherwise current user login.
    $target_identifier = $user_slug ?: $current_user->user_login;
    $profile_data_instance = new UserProfileData($target_identifier);

    // Ensure profile is always an array before using offsets.
    $profile = $profile_data_instance->getProfile();
    if (!is_array($profile)) {
        $profile = [];
    }
}
?>

<div class="dropdown">

    <button type="button" class="d-flex align-items-center gap-2 rounded-circle dropdown-toggle btn-custom"
        data-bs-toggle="dropdown"
        aria-expanded="false">
        <div class="position-relative img44">
            <img src="<?php echo esc_url($current_user ? $profile_photo_url : get_template_directory_uri() . '/assets/img/loggedin_images/banner.jpg'); ?>"
                class="rounded-circle w-100 h-100 object-fit-cover"
                alt="Profile">
            <img class="position-absolute active-icon"
                src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/nd/active_icon.png'); ?>"
                alt="Online Status">
        </div>
    </button>

    <div class="shadow border-0 rounded-3 dropdown-menu profile-width dropdown-menu-end custom-card">
        <ul class="p-0">
            <?php if (is_user_logged_in() && $current_user) : ?>

                <li class="list-unstyled">
                    <a class="dropdown-item" href="<?php echo esc_url($profile_url); ?>">
                        <div class="d-flex align-items-center">

                            <!-- Profile Image -->
                            <div class="position-relative me-2 img44">
                                <img src="<?php echo esc_url($profile_photo_url); ?>"
                                    class="rounded-circle w-100 h-100 object-fit-cover"
                                    alt="<?php echo esc_attr($full_name); ?>">
                            </div>

                            <!-- Name + Sales Person indicator -->
                            <div class="d-flex flex-column">
                                <!-- Name -->
                                <span class="fs20 fw-large">
                                    <?php echo esc_html($full_name); ?>
                                </span>

                                <!-- Sales Person (Under Name) -->
                                <?php if (is_array($profile) && !empty($profile['is_sales_person'])): ?>
                                    <span class="mt-1 rounded-pill badge fw-small"
                                        style="border: 1px solid #05489C; color: #05489C; background-color: transparent;">
                                        Sales Person
                                    </span>
                                <?php endif; ?>
                            </div>

                        </div>
                    </a>

                </li>

                <li class="py-2 pt-0 border-underline list-unstyled">
                    <a class="d-flex align-content-center dropdown-item gap10 fs18 fw-medium"
                        href="<?php echo esc_url(home_url('/modify-profile')); ?>">
                        <div class="img24">
                            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/nd/user_profile.png'); ?>"
                                class="w-100 h-100 object-fit-contain" alt="Update Profile Icon">
                        </div>
                        Update your profile
                    </a>
                </li>

                <li class="py-2 list-unstyled">
                    <a class="d-flex align-content-center dropdown-item gap10 fs18 fw-medium"
                        href="<?php echo esc_url(home_url('/report')); ?>">
                        <div class="img24">
                            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/nd/circle-alert.png'); ?>"
                                class="w-100 h-100 object-fit-contain" alt="Report Icon">
                        </div>
                        Report an issue
                    </a>
                </li>

                <li class="py-2 border-underline list-unstyled">
                    <a class="d-flex align-content-center dropdown-item gap10 fs18 fw-medium"
                        href="<?php echo esc_url(home_url('/suggestion')); ?>">
                        <div class="img24">
                            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/nd/suggestion.png'); ?>"
                                class="w-100 h-100 object-fit-contain" alt="Suggestion Icon">
                        </div>
                        Make a suggestion
                    </a>
                </li>

                <?php if ($is_admin) : ?>
                    <li class="py-2 list-unstyled">
                        <a class="d-flex align-content-center dropdown-item gap10 fs18 fw-medium"
                            href="<?php echo esc_url(admin_url()); ?>">
                            <div class="img24">
                                <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/nd/nav-pen.png'); ?>"
                                    class="w-100 h-100 object-fit-contain" alt="Dashboard Icon">
                            </div>
                            Dashboard
                        </a>
                    </li>
                <?php endif; ?>

                <li class="py-2 list-unstyled">
                    <a class="d-flex align-content-center dropdown-item gap10 fs18 fw-medium"
                        href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>">
                        <div class="img24">
                            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/nd/log-out.png'); ?>"
                                class="w-100 h-100 object-fit-contain" alt="Logout Icon">
                        </div>
                        Sign Out
                    </a>
                </li>
            <?php else : ?>
                <li class="py-2 list-unstyled">
                    <a class="d-flex align-content-center dropdown-item gap10 fs18 fw-medium"
                        href="<?php echo esc_url(wp_login_url(get_permalink())); ?>">
                        Login / Register
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>

</div>