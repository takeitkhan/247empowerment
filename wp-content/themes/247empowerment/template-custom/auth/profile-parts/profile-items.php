<div class="bg-white p-3 about-card">
    <?php
    $current_user = wp_get_current_user();
    $username = $current_user->user_nicename;
    ?>

    <div class="d-flex align-items-center justify-content-between">
        <h3 class="m-0 fw-bold about-title">About</h3>

        <!-- Three-dot dropdown menu -->
        <div class="dropdown">
            <button class="p-0 text-dark btn btn-link" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-three-dots fs-5"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                <li>
                    <a class="d-flex align-items-center dropdown-item" href="<?php echo esc_url(home_url("/wallet")); ?>">
                        <i class="me-2 bi bi-wallet2"></i> Wallet
                    </a>
                </li>
                <li>
                    <a class="d-flex align-items-center dropdown-item" href="<?php echo esc_url(home_url("/$username/referrals")); ?>">
                        <i class="me-2 bi bi-people"></i> Referral Partners
                    </a>
                </li>
                <li>
                    <a class="d-flex align-items-center dropdown-item" href="<?php echo esc_url('/modify-profile'); ?>">
                        <i class="me-2 bi bi-person-lines-fill"></i> Update Profile
                    </a>
                </li>
                <li>
                    <a class="d-flex align-items-center dropdown-item" href="<?php echo esc_url(home_url('/modify-links')); ?>">
                        <i class="me-2 bi bi-link-45deg"></i> Social Management
                    </a>
                </li>

            </ul>
        </div>
    </div>

    <div class="info-item">
        <span>
            <img class="info-icon" src="<?php echo get_template_directory_uri(); ?>/assets/img/loggedin_images/subtract.png" alt="icon" />
        </span>
        <span><?php echo esc_html($profile['about_me_short'] ?: 'No about me provided.'); ?></span>
    </div>

    <?php
    $about_me_raw = trim($profile['about_me'] ?? '');
    $about_me_words = explode(' ', strip_tags($about_me_raw));
    $about_me_trimmed = implode(' ', array_slice($about_me_words, 0, 10));
    $should_truncate = count($about_me_words) > 10 || strlen($about_me_raw) > 150;
    ?>

    <div class="mt-3 info-item">
        <span>
            <img class="info-icon" src="<?php echo get_template_directory_uri(); ?>/assets/img/loggedin_images/subtract.png" alt="icon" />
        </span>
        <span>
            <?php if (!$about_me_raw): ?>
                No detailed bio available.
            <?php elseif ($should_truncate): ?>
                <span class="about-short"><?php echo esc_html(mb_strimwidth($about_me_raw, 0, 150, '...')); ?></span>
                <span class="about-full d-none"><?php echo esc_html($about_me_raw); ?></span>
                <a href="#" class="ms-2 text-primary-color see-toggle">See more</a>
            <?php else: ?>
                <?php echo esc_html($about_me_raw); ?>
            <?php endif; ?>
        </span>
    </div>


    <div class="xmargin-left-30 mt-3">
        <div class="d-flex align-items-start overflow-hidden">
            <?php
            $referredUsers = $profile['referred_users'];
            $referralCount = count($referredUsers);
            $maxVisible = 3;
            $count = 0;
            ?>
            <?php if ($referralCount > 0) : ?>
                <div class="referred-users">
                    <?php echo $profile['referred_users_html']; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php if (get_user_meta($profile['id'], 'show_full_address', true) === '1') { ?>
            <?php if (!empty($profile['location'])): ?>
                <div class="info-item">
                    <span>
                        <img class="info-icon" src="<?php echo get_template_directory_uri(); ?>/assets/img/loggedin_images/location.png" alt="location icon" />
                    </span>
                    <span><?php echo esc_html($profile['location']); ?></span>
                </div>
            <?php endif; ?>

            <?php
            $lat = get_user_meta($profile['id'], 'latitude', true);
            $lng = get_user_meta($profile['id'], 'longitude', true);
            ?>

            <?php if (!empty($lat) && !empty($lng)): ?>
                <div class="position-relative mt-2 map-container">
                    <iframe
                        width="100%"
                        height="180"
                        style="border:0"
                        loading="lazy"
                        allowfullscreen
                        src="https://maps.google.com/maps?q=<?php echo $lat; ?>,<?php echo $lng; ?>&z=15&output=embed">
                    </iframe>

                    <img
                        class="position-absolute end-0 map-icon"
                        src="<?php echo get_template_directory_uri(); ?>/assets/img/loggedin_images/subtract.png"
                        alt="map icon" />
                </div>
            <?php endif; ?>
        <?php } ?>

        <div class="info-item">
            <span>
                <img class="info-icon" src="<?php echo get_template_directory_uri(); ?>/assets/img/loggedin_images/website.png" alt="website icon" />
            </span>
            <span>
                <a class="text-decoration-none" href="<?php echo $profileUrl; ?>" target="_blank" rel="noopener noreferrer">
                    <?php echo $profileUrl; ?>
                </a>
            </span>
        </div>

        <?php if (get_user_meta($profile['id'], 'show_phone', true) === '1' && !empty($profile['phone'])): ?>
            <div class="info-item">
                <span>
                    <img class="info-icon" src="<?php echo get_template_directory_uri(); ?>/assets/img/loggedin_images/phone.png" alt="phone icon" />
                </span>
                <span><?php echo esc_html($profile['phone']); ?></span>
            </div>
        <?php endif; ?>

        <?php if (get_user_meta($profile['id'], 'show_email', true) === '1' && !empty($profile['email'])): ?>
            <div class="info-item">
                <span>
                    <img class="info-icon" src="<?php echo get_template_directory_uri(); ?>/assets/img/loggedin_images/email2.png" alt="email icon" />
                </span>
                <span>
                    <a class="text-decoration-none" href="mailto:<?php echo esc_attr($profile['email']); ?>">
                        <?php echo esc_html($profile['email']); ?>
                    </a>
                </span>
            </div>
        <?php endif; ?>


        <div class="info-item">
            <span>
                <img class="info-icon" src="<?php echo get_template_directory_uri(); ?>/assets/img/loggedin_images/industry.png" alt="industry icon" />
            </span>

            <?php
            $categories = $profile['user_category_names'];
            if (is_array($categories)) {
                echo esc_html(implode(', ', $categories));
            } elseif (is_string($categories) && $categories !== '') {
                echo esc_html($categories);
            } else {
                echo 'Industry - Industry';
            }
            ?>
        </div>
        <div class="xd-flex align-items-center justify-content-between mt-2 xz-0">
            <h3 class="fw-bold about-title">Collaboration Section</h3>
        </div>
        <div class="info-item">
            <?php
            $public_slug = $profile['user_nicename'] ?? ($profile['username'] ?? '');
            $profile_url = home_url('/u/' . $public_slug . '/meetings');
            ?>
            <ul class="d-flex flex-column gap-2 list-unstyled">
                <li>
                    <a href="<?php echo esc_url($profile_url); ?>" target="_blank" class="text-decoration-none">
                        Book a Meeting
                    </a>
                </li>
            </ul>

        </div>

        <div class="d-flex align-items-center justify-content-between mt-2 xz-0">
            <h3 class="mb-0 fw-bold about-title">Social Management</h3>
            <button class="d-flex align-items-center justify-content-center btn-outline-secondary btn btn-sm"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#socialLinksCollapse"
                aria-expanded="false"
                aria-controls="socialLinksCollapse"
                id="socialLinksToggle"
                style="width: 32px; height: 32px; padding: 0; font-weight: bold; font-size: 20px; line-height: 1;">
                +
            </button>
        </div>

        <div class="collapse" id="socialLinksCollapse">
            <div class="mt-2 info-item">
                <?php if (!empty($social_links)): ?>
                    <ul class="d-flex flex-column gap-2 list-unstyled">
                        <?php
                        $iconMap = [
                            'facebook' => 'bi-facebook',
                            'youtube' => 'bi-youtube',
                            'linkedin' => 'bi-linkedin',
                            'x' => 'bi-twitter',
                            'instagram' => 'bi-instagram',
                            'google_business' => 'bi-geo-alt-fill',
                            'yelp' => 'bi-star-fill',
                            'meetup' => 'bi-people-fill',
                            'website' => 'bi-globe',
                            'tiktok' => 'bi-tiktok',
                            'twitch' => 'bi-twitch',
                            'pinterest' => 'bi-pinterest',
                            'snapchat' => 'bi-snapchat',
                            'whatsapp' => 'bi-whatsapp',
                            'whatsapp_business' => 'bi-whatsapp',
                            'zoom' => 'bi-camera-video-fill',
                            'discord' => 'bi-discord',
                            'github' => 'bi-github',
                            'google' => 'bi-google',
                            'custom' => 'bi-link-45deg',
                            'other' => 'bi-question-circle-fill',
                            'email' => 'bi-envelope-fill',
                            'phone' => 'bi-telephone-fill',
                            'telegram' => 'bi-telegram',
                            'signal' => 'bi-shield-lock-fill',
                            'viber' => 'bi-phone-vibrate-fill',
                            'sheet' => 'bi-table',
                            'slack' => 'bi-slack',
                            'reddit' => 'bi-reddit',
                            'messenger' => 'bi-messenger',
                            'meet' => 'bi-camera-video',
                            'calendar' => 'bi-calendar-event',
                            'default' => 'bi-link-45deg',
                        ];
                        ?>
                        <?php foreach ($social_links as $link): ?>
                            <?php $iconClass = $iconMap[$link['platform']] ?? 'bi-link-45deg'; ?>
                            <li>
                                <a href="<?php echo esc_url($link['url']); ?>" target="_blank" class="d-flex align-items-center gap-2 text-decoration-none">
                                    <i class="bi <?php echo esc_attr($iconClass); ?> text-primary-color fs-5"></i>
                                    <span class="text-dark"><?php echo esc_html($link['label']); ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No social management added yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggles = document.querySelectorAll('.see-toggle');
        toggles.forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                const parent = toggle.closest('.info-item');
                const shortText = parent.querySelector('.about-short');
                const fullText = parent.querySelector('.about-full');

                if (shortText.classList.contains('d-none')) {
                    shortText.classList.remove('d-none');
                    fullText.classList.add('d-none');
                    toggle.textContent = 'See more';
                } else {
                    shortText.classList.add('d-none');
                    fullText.classList.remove('d-none');
                    toggle.textContent = 'See less';
                }
            });
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('socialLinksToggle');
        const collapseEl = document.getElementById('socialLinksCollapse');

        collapseEl.addEventListener('show.bs.collapse', () => {
            toggleBtn.textContent = '−'; // minus sign
        });

        collapseEl.addEventListener('hide.bs.collapse', () => {
            toggleBtn.textContent = '+'; // plus sign
        });
    });
</script>