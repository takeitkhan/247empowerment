<?php
$profile = isset($args['profile']) ? $args['profile'] : [];
?>
<div class="profile-left bg-white custom-card">
    <div class="d-flex align-items-center justify-content-between pb-4 u-title">
        <h5 class="portal-title">About</h5>
        <img src="<?php echo get_template_directory_uri(); ?>/assets/img/nd/post_option_icon.png" alt="">
    </div>
    <ul class="d-flex flex-column gap-2 nav">
        <?php if (!empty($profile['location'])) : ?>
            <li class="d-flex align-items-center gap-2">
                <img src="<?php echo get_template_directory_uri(); ?>/assets/img/nd/location_p.png" class="icon-img" alt="Location">
                <a href="#" class="p-0 p-link"><?php echo esc_html($profile['location']); ?></a>
            </li>
        <?php endif; ?>

        <?php if (!empty($profile['occupation'])) : ?>
            <li class="d-flex align-items-center gap-2">
                <img src="<?php echo get_template_directory_uri(); ?>/assets/img/nd/bag.png" class="icon-img" alt="Occupation">
                <a href="#" class="p-0 p-link"><?php echo esc_html($profile['occupation']); ?></a>
            </li>
        <?php endif; ?>

        <?php if (!empty($profile['website'])) : ?>
            <li class="d-flex align-items-center justify-content-between gap-2">
                <div class="d-flex gap-2">
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/img/nd/link.png" class="icon-img" alt="Website">
                    <a href="<?php echo esc_url($profile['website']); ?>" target="_blank" class="p-0 p-link text-primary">
                        <?php echo esc_html(wp_trim_words($profile['website'], 3, '...')); ?>
                    </a>
                </div>
                <img class="icon-img" src="<?php echo get_template_directory_uri(); ?>/assets/img/nd/copy.png" alt="Copy">
            </li>
        <?php endif; ?>
    </ul>
</div>

<div class="bg-white custom-card navbar-link">
    <?php
    wp_nav_menu([
        'theme_location' => 'profilemenu',
        'container'      => false,
        'menu_class'     => 'nav d-flex flex-column gap-2',
        'walker'         => new Profile_Menu_Walker(),
    ]);
    ?>
</div>