<?php
/**
 * Modern Enhanced Posting Modal - Phase 1 (Reorganized)
 * 
 * STRUCTURE:
 * 1. Trigger Card - Quick access to create post
 * 2. Modal Container
 *    2a. Header (User info + Close)
 *    2b. Tab Navigation (Instant | Schedule)
 *    2c. Tab Content
 *        - Instant Post Tab
 *        - Schedule Post Tab
 *    2d. Footer (Actions)
 * 
 * DESIGN PRINCIPLES:
 * - DRY (Don't Repeat Yourself): Shared components are consistent
 * - Two-column layout: Text editor (L) + Options (R)
 * - Progressive disclosure: Options collapse by tab type
 * - Clear visual hierarchy
 */

$current_user = wp_get_current_user();
$current_user_id = get_current_user_id();
$user_photo = get_user_meta($current_user_id, 'profile_photo', true) ?: get_template_directory_uri() . '/assets/img/loggedin_images/banner.jpg';
$template_uri = get_template_directory_uri();
$nonce = wp_create_nonce('create_post_action');

// ✅ NEW: Get profile from args (who's profile we're posting on)
$profile = $args['profile'] ?? null;
$wall_owner_id = ($profile && isset($profile['id'])) ? intval($profile['id']) : $current_user_id;
$is_posting_on_friend_wall = ($wall_owner_id !== $current_user_id);

// Enqueue posting modal styles
wp_enqueue_style('posting-modal', $template_uri . '/assets/css/posting-modal.css', [], '1.0.0');
?>

<!-- ============================================
     1. TRIGGER CARD - Opens Modal
     ============================================ -->
<div class="mb-3 custom-card post-search">
    <div class="d-flex align-items-center gap-3 mb-2 post-row">
        <div>
            <div class="position-relative img44">
                <img src="<?php echo esc_url($user_photo); ?>" class="rounded-circle w-100 h-100 object-fit-cover" alt="Profile">
                <img class="position-absolute active-icon" src="<?php echo esc_url($template_uri); ?>/assets/img/nd/active_icon.png" alt="Active">
            </div>
        </div>
        <input
            id="post-trigger"
            type="text"
            class="w-100 input"
            placeholder="<?php echo $is_posting_on_friend_wall ? 'Post on ' . esc_attr($profile['first_name'] . ' ' . $profile['last_name']) . '\'s wall' : 'This Area is for One-Click Concurrent and Scheduled Posting to Targeted Audiences'; ?>"
            data-bs-toggle="modal"
            data-bs-target="#createPostModalRedesigned"
            readonly>
    </div>
</div>

<!-- ============================================
     2. MODAL CONTAINER
     ============================================ -->
<div class="modal fade" id="createPostModalRedesigned" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl posting-modal-dialog">
        <div class="modal-content posting-modal-redesigned">
            <form id="create-post-form-redesigned" enctype="multipart/form-data">

                <!-- 2a. MODAL HEADER with Schedule Toggle -->
                <div class="modal-header posting-modal-header">
                    <div class="d-flex flex-grow-1 align-items-center gap-3">
                        <div class="position-relative img44">
                            <img src="<?php echo esc_url($user_photo); ?>" class="rounded-circle w-100 h-100 object-fit-cover" alt="Profile">
                            <img class="position-absolute active-icon" src="<?php echo esc_url($template_uri); ?>/assets/img/nd/active_icon.png" alt="Active">
                        </div>
                        <div class="d-flex flex-column post-user">
                            <span class="p_name fw-bold"><?php echo esc_html($current_user->first_name . ' ' . $current_user->last_name); ?></span>
                            <?php if ($is_posting_on_friend_wall): ?>
                                <span class="text-muted small" id="audienceLabel">Posting on <?php echo esc_html($profile['first_name'] . ' ' . $profile['last_name']); ?>'s wall</span>
                            <?php else: ?>
                                <span class="text-muted small" id="audienceLabel">Only Me</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- SCHEDULE TOGGLE - Right side of header (Bootstrap 5) -->
                    <div class="d-flex align-items-center gap-3 ms-3">
                        <!-- PREVIEW TOGGLE BUTTON -->
                        <button type="button" class="btn-outline-secondary btn btn-sm preview-toggle-btn" id="previewToggleBtn" style="font-size: 12px; white-space: nowrap;">
                            <i class="me-1 bi bi-eye"></i>Preview
                        </button>
                        
                        <!-- SCHEDULE TOGGLE -->
                        <label class="form-check-label posting-schedule-label-header" for="postingScheduleToggle" style="margin: 0; cursor: pointer; font-size: 12px; color: #65676b; white-space: nowrap;">
                            <i class="me-1 bi bi-calendar-event"></i>Schedule
                        </label>
                        <div class="form-check form-switch" style="margin: 0;">
                            <input class="form-check-input posting-schedule-toggle" type="checkbox" id="postingScheduleToggle" role="switch">
                        </div>
                    </div>
                    
                    <button type="button" class="ms-2 btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- 2c. UNIFIED CONTENT - Two Views: Edit & Preview -->
                <div class="posting-modal-content-unified">
                    
                    <!-- EDIT VIEW -->
                    <div class="modal-body posting-modal-body edit-view" id="editView">
                        <div class="row g-3">
                            <!-- LEFT COLUMN: TEXT EDITOR & OPTIONS -->
                            <div class="col-lg-6">
                                <div class="posting-editor">
                                    <textarea 
                                        name="post_content" 
                                        id="post-content"
                                        class="posting-textarea" 
                                        rows="10" 
                                        placeholder="This Area is for One-Click Concurrent and Scheduled Posting to Targeted Audiences"></textarea>
                                    
                                    <!-- CHARACTER COUNTER -->
                                    <?php include 'create-post-parts/components/character-counter.php'; ?>
                                    
                                    <!-- TEXT FORMATTING TOOLBAR -->
                                    <?php // include 'create-post-parts/components/formatting-toolbar.php'; ?>
                                    
                                    <!-- EMOJI PICKER -->
                                    <div id="emoji-picker-container" class="emoji-picker-wrapper" style="display: none;">
                                        <emoji-picker id="emoji-picker"></emoji-picker>
                                    </div>
                                </div>

                                <!-- IMAGE UPLOAD SECTION -->
                                <?php 
                                    $upload_id = 'photoUpload';
                                    $preview_container_id = 'image-preview-container';
                                    $preview_id = 'image-preview';
                                    $preview_name_id = 'image-name';
                                    include 'create-post-parts/components/image-upload.php'; 
                                ?>
                            </div>

                            <!-- RIGHT COLUMN: DYNAMIC CONTENT -->
                            <div class="col-lg-6">
                                <!-- POST AUDIENCE SECTION -->
                                <div class="xposting-audience-section">
                                    <?php include 'create-post-parts/components/privacy-selector.php'; ?>
                                </div>

                                <!-- SCHEDULE SECTION (Hidden by default) -->
                                <div class="" id="scheduleSection" style="display: none; margin-top: 20px;">
                                    <div class="schedule-block">
                                        <h6 class="schedule-title">
                                            <i class="me-2 bi bi-calendar-event"></i>Schedule Date & Time
                                        </h6>
                                        <?php include 'create-post-parts/components/schedule-datetime.php'; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PREVIEW VIEW (Hidden by default) -->
                    <div class="modal-body posting-modal-body preview-view" id="previewView" style="display: none; padding: 16px;">
                        
                        <!-- User Info -->
                        <div class="d-flex align-items-center gap-2 mb-3 preview-user-info">
                            <div class="position-relative" style="width: 44px; height: 44px;">
                                <img id="previewUserPhoto" src="" alt="User" class="rounded-circle w-100 h-100 object-fit-cover">
                            </div>
                            <div>
                                <p class="mb-0" style="font-weight: 600; font-size: 14px;" id="previewUserName"></p>
                                <p class="mb-0 text-muted" style="font-size: 12px;" id="previewAudience"></p>
                            </div>
                        </div>

                        <!-- Post Content Area -->
                        <div style="max-height: 400px; overflow-y: auto;">
                            <!-- Post Text -->
                            <div class="mb-3 preview-post-text">
                                <p id="previewText" style="line-height: 1.6; color: #050505; word-wrap: break-word; white-space: pre-wrap; font-size: 14px;"></p>
                            </div>

                            <!-- Post Image (if any) -->
                            <div class="mb-3 preview-post-image" style="display: none;" id="previewImageContainer">
                                <img id="previewPostImage" src="" alt="Post" style="width: 100%; border-radius: 8px; max-height: 300px; object-fit: cover;">
                            </div>

                            <!-- Post Meta -->
                            <div class="preview-post-meta" style="padding-top: 12px; border-top: 1px solid #e9ecef; font-size: 12px; color: #65676b;">
                                <p id="previewMeta" style="margin: 0;"></p>
                            </div>
                        </div>
                    </div>

                    <!-- MODAL FOOTER -->
                    <div class="modal-footer posting-modal-footer">
                        <input type="hidden" name="action" value="create_post">
                        <input type="hidden" name="create_post_nonce" value="<?php echo wp_create_nonce('create_post_action'); ?>">
                        <input type="hidden" name="post_status_type" id="postStatusType" value="publish">
                        <input type="hidden" name="schedule_timestamp" id="schedule_timestamp">
                        <!-- ✅ NEW: Track which profile wall this post is being posted on -->
                        <input type="hidden" name="wall_owner_id" id="wall_owner_id" value="<?php echo intval($wall_owner_id); ?>">
                        
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-info posting-submit-btn" id="submitPostBtn">
                            <i class="me-2 bi bi-send"></i><span id="submitBtnText">Share Now</span>
                        </button>
                    </div>
                </div>

                <!-- JAVASCRIPT: Toggle Schedule Section & Update Audience Label & Preview View -->
                <!-- NOTE: Form submission is handled by modal-handler.js to avoid duplicate handlers -->
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        // ============================================
                        // PREVIEW TOGGLE
                        // ============================================
                        const previewToggleBtn = document.getElementById('previewToggleBtn');
                        const editView = document.getElementById('editView');
                        const previewView = document.getElementById('previewView');

                        if (previewToggleBtn) {
                            previewToggleBtn.addEventListener('click', function() {
                                const isShowingEdit = editView.style.display !== 'none';
                                
                                if (isShowingEdit) {
                                    // Show preview
                                    updatePreviewData();
                                    editView.style.display = 'none';
                                    previewView.style.display = 'block';
                                    previewToggleBtn.classList.add('active');
                                    previewToggleBtn.innerHTML = '<i class="me-1 bi bi-pencil"></i>Edit';
                                } else {
                                    // Show edit
                                    editView.style.display = 'block';
                                    previewView.style.display = 'none';
                                    previewToggleBtn.classList.remove('active');
                                    previewToggleBtn.innerHTML = '<i class="me-1 bi bi-eye"></i>Preview';
                                }
                            });
                        }

                        function updatePreviewData() {
                            // Get textarea content and preserve line breaks
                            const textarea = document.getElementById('post-content');
                            const previewText = document.getElementById('previewText');
                            if (textarea && previewText) {
                                const content = textarea.value || '(No content yet)';
                                previewText.textContent = content;
                            }

                            // Get image if uploaded - check if image container is visible or has a valid src
                            const imagePreviewContainer = document.querySelector('.image-preview-container');
                            const uploadedImage = document.getElementById('image-preview');
                            const previewImageContainer = document.getElementById('previewImageContainer');
                            const previewPostImage = document.getElementById('previewPostImage');
                            const imageName = document.getElementById('image-name');
                            
                            if (imagePreviewContainer && imagePreviewContainer.style.display !== 'none' && uploadedImage && uploadedImage.src) {
                                previewPostImage.src = uploadedImage.src;
                                previewImageContainer.style.display = 'block';
                            } else {
                                previewImageContainer.style.display = 'none';
                            }

                            // Get user info - SCOPE TO MODAL TO AVOID PICKING UP OTHER USERS
                            const modal = document.getElementById('createPostModalRedesigned');
                            const userNameSpan = modal.querySelector('.modal-header .p_name');
                            const audienceLabelSpan = document.getElementById('audienceLabel');
                            const userPhotoImg = modal.querySelector('.modal-header img[alt="Profile"]');
                            const previewUserName = document.getElementById('previewUserName');
                            const previewAudience = document.getElementById('previewAudience');
                            const previewUserPhoto = document.getElementById('previewUserPhoto');

                            if (userNameSpan && previewUserName) {
                                previewUserName.textContent = userNameSpan.textContent;
                            }

                            if (audienceLabelSpan && previewAudience) {
                                previewAudience.textContent = audienceLabelSpan.textContent;
                            }

                            if (userPhotoImg && previewUserPhoto) {
                                previewUserPhoto.src = userPhotoImg.src;
                            }

                            // Set meta info
                            const now = new Date();
                            const timeStr = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
                            const previewMeta = document.getElementById('previewMeta');
                            if (previewMeta) {
                                previewMeta.textContent = `Just now • ${timeStr}`;
                            }
                        }

                        // Update preview in real-time as user types
                        const textarea = document.getElementById('post-content');
                        if (textarea) {
                            textarea.addEventListener('input', function() {
                                // Only update if preview is visible
                                if (previewView && previewView.style.display !== 'none') {
                                    updatePreviewData();
                                }
                            });
                        }

                        // Update preview when privacy changes
                        const privacyRadios = document.querySelectorAll('input[name="post_privacy"]');
                        privacyRadios.forEach(radio => {
                            radio.addEventListener('change', function() {
                                if (previewView && previewView.style.display !== 'none') {
                                    updatePreviewData();
                                }
                            });
                        });

                        // ============================================
                        // SCHEDULE TOGGLE
                        // ============================================
                        const toggleInput = document.getElementById('postingScheduleToggle');
                        const scheduleSection = document.getElementById('scheduleSection');
                        const postStatusInput = document.getElementById('postStatusType');
                        const submitBtnText = document.getElementById('submitBtnText');

                        if (toggleInput) {
                            toggleInput.addEventListener('change', function() {
                                if (this.checked) {
                                    // Schedule mode ON
                                    scheduleSection.style.display = 'block';
                                    postStatusInput.value = 'scheduled';
                                    submitBtnText.textContent = 'Schedule Post';
                                } else {
                                    // Schedule mode OFF
                                    scheduleSection.style.display = 'none';
                                    postStatusInput.value = 'publish';
                                    submitBtnText.textContent = 'Share Now';
                                }
                            });
                        }

                        // ============================================
                        // PRIVACY AUDIENCE LABEL UPDATE
                        // ============================================
                        const privacyContainer = document.getElementById('privacyOptionsContainer');
                        const audienceLabel = document.getElementById('audienceLabel');
                        const privacyInputs = document.querySelectorAll('input[name="post_privacy"]');

                        if (privacyInputs.length > 0) {
                            privacyInputs.forEach(input => {
                                input.addEventListener('change', function() {
                                    if (this.checked) {
                                        const label = this.getAttribute('data-audience-label');
                                        if (label && audienceLabel) {
                                            audienceLabel.textContent = label;
                                        }
                                    }
                                });
                            });
                        }

                        // Set initial label
                        const checkedInput = document.querySelector('input[name="post_privacy"]:checked');
                        if (checkedInput && audienceLabel) {
                            const label = checkedInput.getAttribute('data-audience-label');
                            if (label) {
                                audienceLabel.textContent = label;
                            }
                        }
                    });
                </script>
            </form>
        </div>
    </div>
</div>
