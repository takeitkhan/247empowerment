<?php

/**
 * Template Name: User Profile Template
 */

error_log('DEBUG user-profile.php loaded, is_user_logged_in = ' . (is_user_logged_in() ? 'YES' : 'NO'));

if (!is_user_logged_in()) :
    error_log('DEBUG user-profile.php: Loading shareable-profile.php (non-logged-in)');
    include __DIR__ . '/profile-parts/shareable-profile.php';
    return;
else:
    get_header_based_on_login();
    // Get the user slug (username) from the URL
    $user_slug = get_query_var('user_profile');
    $user = mm_resolve_public_user($user_slug);
    // Pass the resolved WP_User object so UserProfileData always finds the profile
    // regardless of nicename/login differences.
    $profile = $user ? (new UserProfileData($user))->getProfile() : null;

    if (!is_array($profile)) {
        $profile = [];
    }
    $social_links = $profile['social_links'] ?? [];

    if ($user) :
?>
        <div class="container profile-page pt20">
            <?php get_template_part('template-custom/auth/profile-parts/cover-photo-section', null, ['profile' => $profile, 'user' => $user]);  ?>
            <div class="row">
                <div class="col-lg-3">
                    <?php get_template_part('template-custom/auth/profile-parts/profile-card', null, ['profile' => $profile]); ?>
                    <?php get_template_part('template-custom/auth/profile-parts/profile-social-links', null, ['profile' => $profile]); ?>
                    <?php get_template_part('template-custom/auth/profile-parts/navlink', null, ['profile' => $profile]); ?>
                </div>
                <div class="mb-4 col-lg-9">
                    <?php get_template_part('template-custom/auth/profile-parts/create-post', null, ['profile' => $profile]); ?>
                    <?php get_template_part('template-custom/auth/profile-parts/posts', null, ['profile' => $profile]); ?>
                </div>
                <!-- <div class="col-lg-3">
                    <?php //get_template_part('template-custom/auth/profile-parts/referral-partners', null, ['profile' => $profile, 'user' => $user]); ?>
                </div> -->
            </div>
        </div>
    <?php
    else :
        echo '<p>User not found.</p>';
    endif;
    ?>

    <script>
        var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
        var isUploading = false; // Prevent duplicate requests

        /**
         * Shows the gamification modal and header notification.
         * @param {object} notification - The notification data from the server.
         */
        function showGamificationNotification(notification) {
            if (!notification) return;

            Toastify({
                text: notification.message,
                duration: 5000,
                gravity: "top",
                position: "right",
                backgroundColor: "linear-gradient(to right, #00b09b, #96c93d)",
                stopOnFocus: true,
            }).showToast();

            const modal = document.getElementById('gamificationPointsModal');
            if (modal) {
                const modalTitle = document.getElementById('gamification-modal-title');
                const modalMessage = document.getElementById('gamification-modal-message');

                if (modalTitle) modalTitle.innerText = notification.title;
                if (modalMessage) modalMessage.innerHTML = notification.message;

                var gamificationModal = new bootstrap.Modal(modal);
                gamificationModal.show();
            }
        }

        /**
         * Handles the file upload via AJAX and processes the response.
         * @param {File} file - The file to upload.
         * @param {string} action - The AJAX action name ('upload_profile_photo' or 'upload_cover_photo').
         * @param {string} fieldName - The name of the file field ('profile_photo' or 'cover_photo').
         * @param {string} previewSelector - The CSS selector for the image preview element.
         */
        function handlePhotoUpload(file, action, fieldName, previewSelector) {
            // Prevent duplicate uploads
            if (isUploading) {
                console.warn('Upload already in progress');
                return;
            }

            isUploading = true;
            const formData = new FormData();
            formData.append('action', action);
            formData.append(fieldName, file);
            formData.append('nonce', '<?php echo wp_create_nonce("update_profile_photo_nonce"); ?>');

            fetch(ajaxurl, {
                    method: 'POST',
                    body: formData,
                })
                .then(res => res.json())
                .then(response => {
                    if (response.success) {
                        const previewElement = document.querySelector(previewSelector);
                        if (previewElement) {
                            previewElement.src = response.data.url;
                        }
                        showGamificationNotification(response.data.notification);
                    } else {
                        alert('Upload failed: ' + (response.data?.message || 'Unknown error'));
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('Upload error: ' + err.message);
                })
                .finally(() => {
                    isUploading = false;
                });
        }

        document.addEventListener('DOMContentLoaded', function() {
            // --- Cover Photo Upload ---
            const coverEditBtn = document.getElementById('edit-cover-photo-btn');
            const coverInput = document.getElementById('cover-photo-input');

            if (coverEditBtn && coverInput) {
                coverEditBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (!isUploading) {
                        coverInput.click();
                    }
                }, false);

                coverInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file && !isUploading) {
                        handlePhotoUpload(file, 'upload_cover_photo', 'cover_photo', '.profile_img img');
                    }
                }, false);
            }

            // --- Profile Photo Upload ---
            const profileEditBtn = document.getElementById('edit-profile-photo-btn');
            const profileInput = document.getElementById('profile-photo-input');

            if (profileEditBtn && profileInput) {
                profileEditBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (!isUploading) {
                        profileInput.click();
                    }
                }, false);

                profileInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file && !isUploading) {
                        handlePhotoUpload(file, 'upload_profile_photo', 'profile_photo', '.img-p');
                    }
                }, false);
            }
        });
    </script>

    <?php get_footer_based_on_login(); ?>

<?php endif; ?>