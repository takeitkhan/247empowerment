<?php
$profile = isset($args['profile']) ? $args['profile'] : [];
$user = isset($args['user']) ? $args['user'] : null;
$profileUrl = esc_url($profile['profile_url']);
?>
<div class="bg-white mt-0 ps-0 pe-0 pt-0 custom-card">
    <div class="profile-top">
        <div class="position-relative profile_img">
            <img class="w-100 h-100 object-fit-cover banner-img"
                src="<?php echo esc_url(get_user_meta($user->ID, 'profile_cover_photo', true) ?: get_template_directory_uri() . '/assets/img/loggedin_images/banner.jpg'); ?>"
                alt="Cover Photo">

            <?php if (is_user_logged_in()) : ?>
                <button class="position-absolute d-flex align-items-center gap-2 bg-white text-black custom-btn-size edit-cover-photo" id="edit-cover-photo-btn">
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/img/nd/camera.png" alt="">
                    <span class="d-md-block d-none">Edit cover photo</span>
                </button>
                <input type="file" id="cover-photo-input" name="cover_photo" accept="image/*" style="display:none;">
            <?php endif; ?>
        </div>
    </div>

    <!-- Profile Header Section -->
    <div class="d-flex flex-column flex-md-row align-items-center justify-content-between profile-section">
        <div class="d-flex flex-column flex-md-row align-items-center gap-3">
            <div class="position-relative profile-img-size img177">
                <img src="<?php echo esc_url(get_user_meta($user->ID, 'profile_photo', true) ?: get_template_directory_uri() . '/assets/img/loggedin_images/banner.jpg'); ?>"
                    class="rounded-circle w-100 h-100 object-fit-cover img-p"
                    id="profile-photo-preview"
                    alt="Profile">

                <?php if (is_user_logged_in()) : ?>
                    <button type="button" class="position-absolute bg-transparent p-0 border-0" id="edit-profile-photo-btn" style="bottom:0; right:0;">
                        <img src="<?php echo get_template_directory_uri(); ?>/assets/img/nd/profile_camera.png" alt="">
                    </button>
                    <input type="file" id="profile-photo-input" name="profile_photo" accept="image/*" style="display:none;">
                <?php endif; ?>
            </div>

            <div class="d-flex flex-column align-items-center align-items-lg-start mt-3 post-user">
                <span class="profile-title">
                    <?php echo esc_html($profile['first_name'] . ' ' . $profile['last_name']); ?>
                    <?php if ($profile['is_sales_person'] == 1): ?>
                        <span class="border rounded-pill bg-outline-primary badge"
                            style="border-color: #05489C !important; color: #05489C !important;">
                            Sales Person
                        </span>
                    <?php endif; ?>
                </span>

                <?php
                $referredUsers = $profile['referred_users'] ?? [];
                $referralCount = count($referredUsers);
                ?>
                <span class='mt-1 p-r fw-medium'>
                    <span><?php echo esc_html($referralCount); ?></span> referral partner<?php echo $referralCount === 1 ? '' : 's'; ?>
                </span>
                <span class="mt-1 p-r fw-medium fs18">
                    <a href="<?php echo $profileUrl; ?>"
                        class="text-secondary-color text-decoration-none"
                        onclick="copyPersonalLink(event, '<?php echo esc_url($profileUrl); ?>')">
                        @<?php echo esc_html($profile['username']); ?>
                        <img class="mx-2" src="<?php echo get_template_directory_uri(); ?>/assets/img/nd/copy.png"
                            alt="Copy Personal Link" style="cursor: pointer;">
                    </a>
                </span>
            </div>
        </div>

        <div>
            <?php if (is_user_logged_in()) : ?>
                <a href="<?php echo site_url('/modify-profile/'); ?>" class="d-inline-flex align-items-center m-lg-0 mt-3 custom-btn-size custom-button">
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/img/nd/pen.png" class="me-2" alt="">
                    Edit your profile
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function copyPersonalLink(event, link) {
        event.preventDefault(); // prevent redirect
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(link)
                .then(() => showToast("Link copied to clipboard!"))
                .catch(() => showToast("Failed to copy link", true));
        } else {
            // fallback for older browsers
            const textarea = document.createElement("textarea");
            textarea.value = link;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand("copy");
            document.body.removeChild(textarea);
            showToast("Link copied to clipboard!");
        }
    }

    function showToast(message, isError = false) {
        Toastify({
            text: message,
            duration: 3000,
            close: true,
            gravity: "bottom",
            position: "left",
            backgroundColor: isError ? "#e74c3c" : "#4CAF50",
            stopOnFocus: true,
        }).showToast();
    }
</script>