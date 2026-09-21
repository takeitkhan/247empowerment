<?php
$actions = [];
if (function_exists('mm_get_all_gamification_actions')) {
    $actions = mm_get_all_gamification_actions();
}

// A map of action_key to icon filename.
$icon_map = [
    'user_register'        => 'user.svg',
    'first_login'          => 'login.svg',
    'daily_login'          => 'calander.svg',
    'profile_photo_upload' => 'gallery.svg',
    'cover_photo_upload'   => 'cover.svg',
    // Add other mappings here for custom actions if you want specific icons.
    'default'              => 'star.svg' // A default icon.
];
?>
<div class="bg-white mt-4 custom-card">
    <div class="post-search">
        <div class="gap-3 post-row">
            <div>
                <h5 class="pb-4 text-start portal-title">How to earn points</h5>
                <p>Complete actions below to level up and unlock rewards. New ways to earn points will appear here automatically.</p>
            </div>
        </div>
        <div class="mt-3">
            <div class="d-flex flex-column gap-3">
                <?php if (!empty($actions)) : ?>
                    <?php foreach ($actions as $action) : ?>
                        <?php
                        $icon_file = $icon_map[$action->action_key] ?? $icon_map['default'];
                        $icon_url = get_template_directory_uri() . '/assets/img/nd/' . $icon_file;
                        ?>
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex flex-grow-1 align-items-center gap-2">
                                <div style="width: 30px; text-align: center;">
                                    <img class="img24" src="<?php echo esc_url($icon_url); ?>" alt="">
                                </div>
                                <div class="flex-grow-1">
                                    <span class=""><?php echo esc_html($action->custom_message); ?></span>
                                </div>
                            </div>
                            <div class="text-primary-color text-end fs18 fw-bold" style="width: 90px; flex-shrink: 0;"><?php echo '+' . esc_html($action->points); ?> pts</div>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p>No point-earning actions have been configured yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>