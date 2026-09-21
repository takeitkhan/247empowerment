<?php
/**
 * Modern Enhanced Posting Modal - Phase 1
 * Features: Better UI, Character Counter, Privacy Selector, Image Preview, Emoji Picker, Status Indicators
 * 
 * Structure:
 * - Opening card with input trigger
 * - Modal with two tabs: Instant Post & Schedule Post
 * - Each tab has: Text editor (left) + Options/Preview (right)
 * - Shared components: character counter, formatting toolbar, emoji picker, image upload, privacy selector
 */

// Define current user
$current_user = wp_get_current_user();
$user_photo = get_user_meta(get_current_user_id(), 'profile_photo', true) ?: get_template_directory_uri() . '/assets/img/loggedin_images/banner.jpg';
$template_uri = get_template_directory_uri();
?>

<div class="mb-3 custom-card post-search">
    <div class="d-flex align-items-center gap-3 mb-2 post-row">
        <div>
            <div class="position-relative img44">
                <img src="<?php echo esc_url(get_user_meta(get_current_user_id(), 'profile_photo', true) ?: get_template_directory_uri() . '/assets/img/loggedin_images/banner.jpg'); ?>" class="rounded-circle w-100 h-100 object-fit-cover" alt="Profile">
                <img class="position-absolute active-icon" src="<?php echo get_template_directory_uri(); ?>/assets/img/nd/active_icon.png" alt="Active">
            </div>
        </div>
        <input
            id="txt"
            type="text"
            class="w-100 input"
            placeholder="What's on your mind?"
            data-bs-toggle="modal"
            data-bs-target="#createPostModalRedesigned"
            readonly>
    </div>

    <!-- Modern Input Modal -->
    <div class="modal fade" id="createPostModalRedesigned" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content posting-modal-redesigned">
                <form id="create-post-form-redesigned" enctype="multipart/form-data">
                    <!-- Modal Header with User Info -->
                    <div class="modal-header posting-modal-header">
                        <div class="d-flex align-items-center gap-3 flex-grow-1">
                            <div class="position-relative img44">
                                <img src="<?php echo esc_url(get_user_meta(get_current_user_id(), 'profile_photo', true) ?: get_template_directory_uri() . '/assets/img/loggedin_images/banner.jpg'); ?>" class="rounded-circle w-100 h-100 object-fit-cover" alt="Profile">
                                <img class="position-absolute active-icon" src="<?php echo get_template_directory_uri(); ?>/assets/img/nd/active_icon.png" alt="Active">
                            </div>
                            <div class="d-flex flex-column post-user">
                                <?php $profile = wp_get_current_user(); ?>
                                <span class="p_name fw-bold"><?php echo esc_html($profile->first_name . ' ' . $profile->last_name); ?></span>
                                <span class="small text-muted">Public account</span>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <!-- Tabs Navigation -->
                    <div class="posting-modal-tabs">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="instant-tab" data-bs-toggle="tab" data-bs-target="#instantPost" type="button" role="tab">
                                    <i class="bi bi-send me-2"></i>Instant Post
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="schedule-tab" data-bs-toggle="tab" data-bs-target="#schedulePost" type="button" role="tab">
                                    <i class="bi bi-calendar-event me-2"></i>Schedule Post
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- Tab Content -->
                    <div class="tab-content posting-modal-content">
                        <!-- INSTANT POST TAB -->
                        <div class="tab-pane fade show active" id="instantPost" role="tabpanel">
                            <div class="modal-body posting-modal-body">
                                <input type="hidden" name="action" value="create_post">
                                <input type="hidden" name="create_post_nonce" value="<?php echo wp_create_nonce('create_post_action'); ?>">
                                <input type="hidden" name="post_status_type" value="publish">

                                <!-- Main Content Area -->
                                <div class="row g-3">
                                    <!-- Left: Text Editor -->
                                    <div class="col-lg-6">
                                        <div class="posting-editor">
                                            <textarea 
                                                name="post_content" 
                                                id="post-content-instant"
                                                class="posting-textarea" 
                                                rows="10" 
                                                placeholder="What's on your mind? Share your thoughts..."></textarea>
                                            
                                            <!-- Character Counter -->
                                            <div class="character-counter-container mt-2">
                                                <div class="character-counter">
                                                    <span id="char-count">0</span>/<span id="char-limit">2000</span>
                                                </div>
                                                <div class="character-progress">
                                                    <div class="progress">
                                                        <div id="char-progress-bar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Text Formatting Toolbar -->
                                            <div class="posting-toolbar mt-3">
                                                <div class="d-flex gap-2 flex-wrap">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary formatting-btn" data-format="bold" title="Bold">
                                                        <i class="bi bi-type-bold"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary formatting-btn" data-format="italic" title="Italic">
                                                        <i class="bi bi-type-italic"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary formatting-btn" data-format="underline" title="Underline">
                                                        <i class="bi bi-type-underline"></i>
                                                    </button>
                                                    <div class="vr"></div>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary formatting-btn" data-format="ul" title="Bullet List">
                                                        <i class="bi bi-list-ul"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary formatting-btn" data-format="ol" title="Numbered List">
                                                        <i class="bi bi-list-ol"></i>
                                                    </button>
                                                    <div class="vr"></div>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary formatting-btn" id="emoji-btn" title="Add Emoji">
                                                        <i class="bi bi-emoji-smile"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Emoji Picker -->
                                            <div id="emoji-picker-container" class="emoji-picker-wrapper" style="display: none;">
                                                <emoji-picker id="emoji-picker"></emoji-picker>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Right: Preview & Options -->
                                    <div class="col-lg-6">
                                        <!-- Post Preview -->
                                        <div class="posting-preview-box">
                                            <div class="preview-label">
                                                <i class="bi bi-eye me-2"></i>Preview
                                            </div>
                                            <div class="preview-content" id="post-preview">
                                                <p class="text-muted text-center py-4">Your post preview will appear here</p>
                                            </div>
                                        </div>

                                        <!-- Image Upload Section -->
                                        <div class="posting-image-section mt-3">
                                            <div class="image-upload-box" id="image-upload-box">
                                                <input type="file" name="post_image" id="photoUpload" class="d-none" accept="image/*,video/*">
                                                <label for="photoUpload" class="image-upload-label">
                                                    <div class="upload-icon">
                                                        <i class="bi bi-image"></i>
                                                    </div>
                                                    <div class="upload-text">
                                                        <p class="mb-1 fw-bold">Add Image or Video</p>
                                                        <p class="small text-muted mb-0">or drag and drop</p>
                                                    </div>
                                                </label>
                                            </div>
                                            
                                            <!-- Image Preview with Thumbnail -->
                                            <div class="image-preview-container mt-3" id="image-preview-container" style="display: none;">
                                                <div class="position-relative">
                                                    <img id="image-preview" src="" alt="Preview" class="img-fluid rounded" style="max-height: 200px; object-fit: cover; width: 100%;">
                                                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2 remove-image-btn">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                                <div class="mt-2 image-info">
                                                    <small id="image-name" class="text-muted"></small>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Enhanced Privacy Selector -->
                                        <div class="posting-privacy-section mt-3">
                                            <label class="form-label fw-bold mb-2">
                                                <i class="bi bi-shield-check me-2"></i>Who can see this?
                                            </label>
                                            <div class="privacy-options">
                                                <div class="privacy-option">
                                                    <input type="radio" name="post_privacy" id="privacy-only-me" value="only_me" checked>
                                                    <label for="privacy-only-me" class="privacy-label">
                                                        <span class="privacy-icon">
                                                            <i class="bi bi-lock-fill"></i>
                                                        </span>
                                                        <span class="privacy-text">
                                                            <strong>Only Me</strong>
                                                            <small class="d-block text-muted">Private, only you can see</small>
                                                        </span>
                                                    </label>
                                                </div>
                                                <div class="privacy-option">
                                                    <input type="radio" name="post_privacy" id="privacy-referral" value="referral_partners">
                                                    <label for="privacy-referral" class="privacy-label">
                                                        <span class="privacy-icon">
                                                            <i class="bi bi-people-fill"></i>
                                                        </span>
                                                        <span class="privacy-text">
                                                            <strong>Referral Partners</strong>
                                                            <small class="d-block text-muted">Share with your network</small>
                                                        </span>
                                                    </label>
                                                </div>
                                                <div class="privacy-option">
                                                    <input type="radio" name="post_privacy" id="privacy-public" value="public">
                                                    <label for="privacy-public" class="privacy-label">
                                                        <span class="privacy-icon">
                                                            <i class="bi bi-globe"></i>
                                                        </span>
                                                        <span class="privacy-text">
                                                            <strong>Public</strong>
                                                            <small class="d-block text-muted">Anyone can see this</small>
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal Footer - Instant Post -->
                            <div class="modal-footer posting-modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-info posting-submit-btn" id="submit-instant-post">
                                    <i class="bi bi-send me-2"></i>Share Now
                                </button>
                            </div>
                        </div>

                        <!-- SCHEDULE POST TAB -->
                        <div class="tab-pane fade" id="schedulePost" role="tabpanel">
                            <div class="modal-body posting-modal-body">
                                <input type="hidden" name="action" value="create_post">
                                <input type="hidden" name="create_post_nonce" value="<?php echo wp_create_nonce('create_post_action'); ?>">
                                <input type="hidden" name="post_status_type" value="scheduled">
                                <input type="hidden" name="schedule_timestamp" id="schedule_timestamp">

                                <div class="row g-3">
                                    <!-- Left: Text Editor -->
                                    <div class="col-lg-6">
                                        <div class="posting-editor">
                                            <textarea 
                                                name="post_content" 
                                                id="post-content-schedule"
                                                class="posting-textarea" 
                                                rows="10" 
                                                placeholder="What's on your mind? Share your thoughts..."></textarea>
                                            
                                            <!-- Character Counter -->
                                            <div class="character-counter-container mt-2">
                                                <div class="character-counter">
                                                    <span id="char-count-schedule">0</span>/<span id="char-limit-schedule">2000</span>
                                                </div>
                                                <div class="character-progress">
                                                    <div class="progress">
                                                        <div id="char-progress-bar-schedule" class="progress-bar" role="progressbar" style="width: 0%"></div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Text Formatting Toolbar -->
                                            <div class="posting-toolbar mt-3">
                                                <div class="d-flex gap-2 flex-wrap">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary formatting-btn" data-format="bold" title="Bold">
                                                        <i class="bi bi-type-bold"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary formatting-btn" data-format="italic" title="Italic">
                                                        <i class="bi bi-type-italic"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary formatting-btn" data-format="underline" title="Underline">
                                                        <i class="bi bi-type-underline"></i>
                                                    </button>
                                                    <div class="vr"></div>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary formatting-btn" data-format="ul" title="Bullet List">
                                                        <i class="bi bi-list-ul"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary formatting-btn" data-format="ol" title="Numbered List">
                                                        <i class="bi bi-list-ol"></i>
                                                    </button>
                                                    <div class="vr"></div>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary formatting-btn emoji-btn-schedule" title="Add Emoji">
                                                        <i class="bi bi-emoji-smile"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Emoji Picker -->
                                            <div id="emoji-picker-container-schedule" class="emoji-picker-wrapper" style="display: none;">
                                                <emoji-picker id="emoji-picker-schedule"></emoji-picker>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Right: Schedule Options & Preview -->
                                    <div class="col-lg-6">
                                        <!-- Schedule DateTime Selection -->
                                        <div class="posting-schedule-section">
                                            <div class="schedule-label">
                                                <i class="bi bi-calendar-event me-2"></i>Schedule Date & Time
                                            </div>
                                            
                                            <div class="schedule-inputs mt-3">
                                                <div class="mb-3">
                                                    <label for="schedule-date" class="form-label">Date</label>
                                                    <input type="date" id="schedule-date" class="form-control schedule-date" min="">
                                                </div>

                                                <div class="mb-3">
                                                    <label for="schedule-time" class="form-label">Time</label>
                                                    <input type="time" id="schedule-time" class="form-control schedule-time">
                                                </div>

                                                <div class="mb-3">
                                                    <label for="schedule-timezone" class="form-label">Timezone</label>
                                                    <select id="schedule-timezone" class="form-select schedule-timezone">
                                                        <option value="UTC">UTC</option>
                                                        <option value="America/New_York">Eastern Time (EST/EDT)</option>
                                                        <option value="America/Chicago">Central Time (CST/CDT)</option>
                                                        <option value="America/Denver">Mountain Time (MST/MDT)</option>
                                                        <option value="America/Los_Angeles">Pacific Time (PST/PDT)</option>
                                                        <option value="Europe/London">London (GMT/BST)</option>
                                                        <option value="Europe/Paris">Paris (CET/CEST)</option>
                                                        <option value="Asia/Tokyo">Tokyo (JST)</option>
                                                        <option value="Asia/Singapore">Singapore (SGT)</option>
                                                        <option value="Australia/Sydney">Sydney (AEDT/AEST)</option>
                                                    </select>
                                                </div>

                                                <!-- Scheduled Time Preview -->
                                                <div class="schedule-preview-box alert alert-info">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <i class="bi bi-info-circle"></i>
                                                        <div>
                                                            <small>Scheduled for: <strong id="schedule-preview-time">Select a date and time</strong></small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Post Preview -->
                                        <div class="posting-preview-box mt-3">
                                            <div class="preview-label">
                                                <i class="bi bi-eye me-2"></i>Preview
                                            </div>
                                            <div class="preview-content" id="post-preview-schedule">
                                                <p class="text-muted text-center py-4">Your post preview will appear here</p>
                                            </div>
                                        </div>

                                        <!-- Image Upload Section -->
                                        <div class="posting-image-section mt-3">
                                            <div class="image-upload-box" id="image-upload-box-schedule">
                                                <input type="file" name="post_image" id="photoUpload-schedule" class="d-none" accept="image/*,video/*">
                                                <label for="photoUpload-schedule" class="image-upload-label">
                                                    <div class="upload-icon">
                                                        <i class="bi bi-image"></i>
                                                    </div>
                                                    <div class="upload-text">
                                                        <p class="mb-1 fw-bold">Add Image or Video</p>
                                                        <p class="small text-muted mb-0">or drag and drop</p>
                                                    </div>
                                                </label>
                                            </div>
                                            
                                            <!-- Image Preview with Thumbnail -->
                                            <div class="image-preview-container" id="image-preview-container-schedule" style="display: none;">
                                                <div class="position-relative">
                                                    <img id="image-preview-schedule" src="" alt="Preview" class="img-fluid rounded" style="max-height: 200px; object-fit: cover; width: 100%;">
                                                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2 remove-image-btn">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                                <div class="mt-2 image-info">
                                                    <small id="image-name-schedule" class="text-muted"></small>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Enhanced Privacy Selector -->
                                        <div class="posting-privacy-section mt-3">
                                            <label class="form-label fw-bold mb-2">
                                                <i class="bi bi-shield-check me-2"></i>Who can see this?
                                            </label>
                                            <div class="privacy-options">
                                                <div class="privacy-option">
                                                    <input type="radio" name="post_privacy" id="privacy-only-me-schedule" value="only_me" checked>
                                                    <label for="privacy-only-me-schedule" class="privacy-label">
                                                        <span class="privacy-icon">
                                                            <i class="bi bi-lock-fill"></i>
                                                        </span>
                                                        <span class="privacy-text">
                                                            <strong>Only Me</strong>
                                                            <small class="d-block text-muted">Private, only you can see</small>
                                                        </span>
                                                    </label>
                                                </div>
                                                <div class="privacy-option">
                                                    <input type="radio" name="post_privacy" id="privacy-referral-schedule" value="referral_partners">
                                                    <label for="privacy-referral-schedule" class="privacy-label">
                                                        <span class="privacy-icon">
                                                            <i class="bi bi-people-fill"></i>
                                                        </span>
                                                        <span class="privacy-text">
                                                            <strong>Referral Partners</strong>
                                                            <small class="d-block text-muted">Share with your network</small>
                                                        </span>
                                                    </label>
                                                </div>
                                                <div class="privacy-option">
                                                    <input type="radio" name="post_privacy" id="privacy-public-schedule" value="public">
                                                    <label for="privacy-public-schedule" class="privacy-label">
                                                        <span class="privacy-icon">
                                                            <i class="bi bi-globe"></i>
                                                        </span>
                                                        <span class="privacy-text">
                                                            <strong>Public</strong>
                                                            <small class="d-block text-muted">Anyone can see this</small>
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal Footer - Schedule Post -->
                            <div class="modal-footer posting-modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-info posting-submit-btn" id="submit-schedule-post">
                                    <i class="bi bi-calendar-event me-2"></i>Schedule Post
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
