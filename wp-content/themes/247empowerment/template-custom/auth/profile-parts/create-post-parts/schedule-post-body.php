<?php
/**
 * Schedule Post Tab Body
 * Two-column layout:
 * - Left (60%): Text editor, character counter, formatting toolbar, emoji picker, image upload, privacy selector
 * - Right (40%): Schedule section, preview
 */

$nonce = wp_create_nonce('create_post_action');
$template_uri = get_template_directory_uri();
?>

<input type="hidden" name="action" value="create_post">
<input type="hidden" name="create_post_nonce" value="<?php echo esc_attr($nonce); ?>">
<input type="hidden" name="post_status_type" value="scheduled">
<input type="hidden" name="schedule_timestamp" id="schedule_timestamp">

<div class="row g-2">
    <!-- LEFT COLUMN: TEXT EDITOR & OPTIONS -->
    <div class="col-lg-6">
        <div class="posting-editor">
            <textarea 
                name="post_content" 
                id="post-content-schedule"
                class="posting-textarea" 
                rows="10" 
                placeholder="What's on your mind? Share your thoughts..."></textarea>
            
            <!-- CHARACTER COUNTER -->
            <?php 
                $counter_id = 'char-count-schedule';
                $limit_id = 'char-limit-schedule';
                $bar_id = 'char-progress-bar-schedule';
                include 'components/character-counter.php'; 
            ?>
            
            <!-- TEXT FORMATTING TOOLBAR -->
            <?php include 'components/formatting-toolbar.php'; ?>
            
            <!-- EMOJI PICKER -->
            <div id="emoji-picker-container-schedule" class="emoji-picker-wrapper" style="display: none;">
                <emoji-picker id="emoji-picker-schedule"></emoji-picker>
            </div>
        </div>

        <!-- IMAGE UPLOAD SECTION -->
        <?php 
            $upload_id = 'photoUpload-schedule';
            $preview_container_id = 'image-preview-container-schedule';
            $preview_id = 'image-preview-schedule';
            $preview_name_id = 'image-name-schedule';
            include 'components/image-upload.php'; 
        ?>
        
        <!-- PRIVACY SELECTOR -->
        <?php 
            $privacy_prefix = '-schedule';
            include 'components/privacy-selector.php'; 
        ?>
    </div>

    <!-- RIGHT COLUMN: SCHEDULE + PREVIEW -->
    <div class="col-lg-6">
        <!-- SCHEDULE DATE & TIME SECTION -->
        <?php include 'components/schedule-datetime.php'; ?>
        
        <!-- POST PREVIEW BOX -->
        <?php 
            $preview_id = 'post-preview-schedule';
            include 'components/preview-box.php'; 
        ?>
    </div>
</div>
