<?php
$profile = isset($args['profile']) ? $args['profile'] : [];
$user = isset($args['user']) ? $args['user'] : null;

// Fetch profile photo (or default)
$profile_photo = get_user_meta($user->ID, 'profile_photo', true) ?: get_template_directory_uri() . '/assets/img/loggedin_images/banner.jpg';

// Fetch cover photo (or default)
$cover_photo = get_user_meta($user->ID, 'cover_photo', true) ?: get_template_directory_uri() . '/assets/img/loggedin_images/banner.jpg';

// Fetch display data
$full_name = trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? '')) ?: $user->display_name;
$location = get_user_meta($user->ID, 'place_display_name', true);
$profession = get_user_meta($user->ID, 'about_me_short', true);
?>
<div class="bg-white upcoming-events">
    <!-- Cover Photo Section -->
    <div class="position-relative mb-4">
        <div style="height: 150px; overflow: hidden; border-radius: 8px;">
            <img id="cover-photo-preview" class="w-100 h-100 object-fit-cover"
                src="<?php echo esc_url($cover_photo); ?>"
                alt="Cover Photo">
        </div>
        <?php if (is_user_logged_in()) : ?>
            <input type="file" id="cover-photo-input" name="cover_photo" class="d-none" accept="image/*">
            <button type="button" id="trigger-cover-upload-btn" class="btn btn-sm btn-primary position-absolute bottom-0 end-0 m-2">
                <i class="fa fa-camera"></i> Change Cover
            </button>
        <?php endif; ?>
    </div>

    <!-- Profile Photo Section -->
    <div class="d-flex flex-column align-items-center">
        <div class="pb-4">
            <div class="img100">
                <img id="profile-photo-preview" class="rounded-circle w-100 h-100 object-fit-cover"
                    src="<?php echo esc_url($profile_photo); ?>"
                    alt="<?php echo esc_attr($full_name); ?>">
            </div>
        </div>

        <?php if (is_user_logged_in()) : ?>
            <!-- Upload Photo -->
            <form method="post" enctype="multipart/form-data" id="profile-photo-form">
                <input type="file" id="profile-photo-input" name="profile_photo" class="d-none" accept="image/*">
                <button type="button" id="trigger-upload-btn" class="d-inline-flex align-items-center gap-2 btn custom-btn">
                    <i class="fa fa-camera"></i>
                    <span>Change Photo</span>
                </button>

                <?php wp_nonce_field('update_profile_photo_nonce', 'profile_photo_nonce'); ?>
            </form>

            <!-- Delete Photo -->
            <div class="d-flex align-items-center mt-2">
                <img src="<?php echo get_template_directory_uri(); ?>/assets/img/nd/delete.png"
                    alt="" class="me-1 img-fluid img20">
                <button type="button" id="delete-photo-btn"
                    class="bg-transparent border-0 text-blue-color custom-btn-size">
                    Delete photo
                </button>
            </div>
        <?php endif; ?>


        <!-- Profile Info -->
        <div>
            <div class="d-flex flex-column align-items-center gap6">
                <span class="fs20"><?php echo esc_html($full_name); ?></span>

                <?php if ($location): ?>
                    <span class="text-color-neutral fs14"><?php echo esc_html($location); ?></span>
                <?php endif; ?>
                <?php if ($profile['is_sales_person'] == 1): ?>
                    <span class="border rounded-pill bg-outline-primary badge"
                        style="border-color: #05489C !important; color: #05489C !important;">
                        Sales Person
                    </span>
                <?php endif; ?>

                <?php if ($profession): ?>
                    <span class="text-color-neutral fs14"><?php echo esc_html($profession); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

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
     */
    function handlePhotoUpload(file, action, fieldName) {
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
                    location.reload();
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
        // Profile photo upload
        const profileInput = document.getElementById('profile-photo-input');
        if (profileInput) {
            profileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file && !isUploading) {
                    handlePhotoUpload(file, 'upload_profile_photo', 'profile_photo');
                }
            }, false);
        }

        // Cover photo upload
        const coverInput = document.getElementById('cover-photo-input');
        if (coverInput) {
            coverInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file && !isUploading) {
                    handlePhotoUpload(file, 'upload_cover_photo', 'cover_photo');
                }
            }, false);
        }

        // Trigger profile photo input
        const triggerBtn = document.getElementById('trigger-upload-btn');
        if (triggerBtn) {
            triggerBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (!isUploading) {
                    document.getElementById('profile-photo-input').click();
                }
            }, false);
        }

        // Trigger cover photo input
        const triggerCoverBtn = document.getElementById('trigger-cover-upload-btn');
        if (triggerCoverBtn) {
            triggerCoverBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (!isUploading) {
                    document.getElementById('cover-photo-input').click();
                }
            }, false);
        }

        // Delete profile photo
        const deleteBtn = document.getElementById('delete-photo-btn');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (confirm('Are you sure you want to delete your profile photo?') && !isUploading) {
                    isUploading = true;
                    const formData = new FormData();
                    formData.append('action', 'delete_profile_photo');
                    formData.append('nonce', '<?php echo wp_create_nonce("update_profile_photo_nonce"); ?>');
                    
                    fetch(ajaxurl, {
                        method: 'POST',
                        body: formData,
                    })
                    .then(res => res.json())
                    .then(response => {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Delete failed: ' + (response.data?.message || 'Unknown error'));
                        }
                    })
                    .catch(err => {
                        console.error('Error:', err);
                        alert('Delete error: ' + err.message);
                    })
                    .finally(() => {
                        isUploading = false;
                    });
                }
            }, false);
        }
    });
</script>