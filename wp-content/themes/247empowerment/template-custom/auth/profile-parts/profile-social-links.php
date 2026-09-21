<?php
$profile = isset($args['profile']) ? $args['profile'] : [];
$social_links = $profile['social_links'] ?? [];
?>
<div class="profile-left bg-white custom-card">
    <div class="d-flex align-items-center justify-content-between pb-4 u-title">
        <h5 class="portal-title">Social Management</h5>
    </div>
    <?php if (!empty($social_links)): ?>
        <ul class="d-flex flex-column gap-2 nav">
            <?php
            $iconMap = [
                'facebook' => 'facebook.svg',
                'youtube' => 'youtube.svg',
                'linkedin' => 'linked.svg',
                'x' => 'x.svg',
                'instagram' => 'insta.svg',
                'google_business' => 'googlebusinness.svg',
                'yelp' => 'yelp.svg',
                'meetup' => 'meetup.svg',
                'website' => 'website.svg',
                'tiktok' => 'tiktok.svg', // If you add tiktok icon later
                'twitch' => 'twitch.svg', // If you add twitch icon later
                'pinterest' => 'pinterest.svg',
                'snapchat' => 'snapchat.svg', // If you add snapchat icon later
                'whatsapp' => 'whats.svg',
                'whatsapp_business' => 'whats.svg',
                'zoom' => 'zoom.svg',
                'discord' => 'discord.svg', // If you add discord icon later
                'github' => 'github.svg',
                'google' => 'google.svg', // If you add google icon later
                'custom' => 'link.svg',
                'other' => 'link.svg',
                'email' => 'link.svg',
                'phone' => 'link.svg',
                'telegram' => 'telegram.svg',
                'meet' => 'meet.svg',
                'calendar' => 'link.svg',
                'default' => 'link.svg',
                'bluesky' => 'bluesky.svg',                
            ];
            ?>
            <?php foreach ($social_links as $link): ?>
                <?php
                $iconFile = $iconMap[$link['platform']] ?? 'link.svg';
                ?>
                <li class="d-flex align-items-center gap-2">
                    <a href="<?php echo esc_url($link['url']); ?>" target="_blank" class="d-flex align-items-center gap-2 text-decoration-none">
                        <img src="<?php echo get_template_directory_uri(); ?>/assets/img/nd/logo/<?php echo $iconFile; ?>" class="icon-img" alt="<?php echo esc_attr($link['platform']); ?>">
                        <span class="text-dark"><?php echo esc_html($link['label']); ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No social management added yet.</p>
    <?php endif; ?>
</div>