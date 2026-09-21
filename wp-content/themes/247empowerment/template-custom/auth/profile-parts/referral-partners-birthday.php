<?php
$current_user = get_user_by('ID', get_current_user_id());
$referred_users = UserProfileData::getReferredUsersBy($current_user);

// echo '<pre>';
// print_r($referred_users);
// echo '</pre>';

$birthday_users = array_filter($referred_users, function ($user) {
    // Get user ID whether $user is object or array
    $user_id = is_object($user) ? $user['id'] : ($user['id'] ?? 0);

    if (!$user_id) return false;

    $dob = get_user_meta($user_id, 'dob', true);

    if (empty($dob)) {
        error_log("Missing DOB for user ID {$user_id}");
        return false;
    }

    $dob_date = date('m-d', strtotime($dob));
    $today = date('m-d');

    return $dob_date === $today;
});

// echo '<pre>';
// print_r($birthday_users);
// echo '</pre>';
?>

<div class="bg-white custom-box-shadow p-3 custom-border-radius h-100">
    <h3 class="fw-bold" style="font-size: 20px">Birthdays</h3>
    <?php if (!empty($birthday_users)): ?>
        <ul class="mt-3 list-unstyled">
            <?php foreach ($birthday_users as $user): ?>
                <?php
                // Using your custom array keys
                $user_id = $user['id'] ?? 0;
                $user_login = $user['username'] ?? '';

                if (!$user_id || !$user_login) continue;

                $profile = (new UserProfileData($user_login))->getProfile();
                $dob = $user['dob'] ?? '';
                ?>
                <li class="d-flex align-items-center mb-2">
                    <?php if (!empty($profile['profile_photo'])): ?>
                        <img src="<?= esc_url($profile['profile_photo']); ?>" alt="<?= esc_attr($profile['display_name'] ?? ''); ?>" class="me-2 rounded-circle" width="32" height="32">
                    <?php endif; ?>
                    <div>
                        <a href="<?= esc_url($profile['profile_url'] ?? '#'); ?>" class="text-decoration-none fw-semibold">
                            🎂 <?= esc_html($profile['display_name'] ?? ''); ?>
                        </a>                        
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="mt-2 text-muted">No birthdays today.</p>
    <?php endif; ?>
</div>