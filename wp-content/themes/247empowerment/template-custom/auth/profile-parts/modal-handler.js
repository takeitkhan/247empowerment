/**
 * Modern Posting Modal - JavaScript Handler
 * Phase 1: Design Improvements
 * Features: Character Counter, Emoji Picker, Image Handling, Live Preview
 */

(function($) {
    'use strict';

    // Configuration
    const CONFIG = {
        MAX_CHARS: 2000,
        IMAGE_SIZES: {
            PREVIEW: 'large',
            THUMBNAIL: 'medium'
        }
    };

    /**
     * Initialize Modal Components
     */
    $(document).ready(function() {
        console.log('✓ Modal Handler Script Loaded');
        initializeCharacterCounters();
        initializeFormattingToolbar();
        initializeEmojiPickers();
        initializeImageUpload();
        initializePrivacySelector();
        initializePostPreview();
        initializeScheduleDateTime();
        initializeSocialShareOptions();
        initializeFormSubmission();
        console.log('✓ All modal components initialized');
    });

    /**
     * CHARACTER COUNTER
     */
    function initializeCharacterCounters() {
        // Single textarea for unified modal structure
        $('#post-content').on('input', function() {
            updateCharacterCounter($(this), '#char-count', '#char-progress-bar');
        });
    }

    function updateCharacterCounter($textarea, $countSelector, $progressSelector) {
        const text = $textarea.val();
        const length = text.length;
        const percentage = Math.min((length / CONFIG.MAX_CHARS) * 100, 100);

        // Update counter display
        $(document).find($countSelector).text(length);

        // Update progress bar
        const $progress = $(document).find($progressSelector);
        $progress.css('width', percentage + '%');

        // Change color based on usage
        $progress.removeClass('warning danger');
        if (length > CONFIG.MAX_CHARS * 0.8) {
            $progress.addClass('warning');
        }
        if (length > CONFIG.MAX_CHARS) {
            $progress.addClass('danger');
            $textarea.val(text.substring(0, CONFIG.MAX_CHARS));
        }
    }

    /**
     * TEXT FORMATTING TOOLBAR
     */
    function initializeFormattingToolbar() {
        $('.formatting-btn').on('click', function(e) {
            e.preventDefault();
            const format = $(this).data('format');
            applyTextFormat(format);
        });
    }

    function applyTextFormat(format) {
        // Get the active textarea
        const $activeTab = $('.tab-pane.active');
        const $textarea = $activeTab.find('.posting-textarea');
        
        if (!$textarea.length) return;

        const textarea = $textarea[0];
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = textarea.value;
        const selectedText = text.substring(start, end);

        let newText = selectedText;
        let offset = 0;

        switch(format) {
            case 'bold':
                newText = `**${selectedText}**`;
                offset = 2;
                break;
            case 'italic':
                newText = `*${selectedText}*`;
                offset = 1;
                break;
            case 'underline':
                newText = `__${selectedText}__`;
                offset = 2;
                break;
            case 'ul':
                newText = selectedText ? `\n• ${selectedText}` : '\n• ';
                break;
            case 'ol':
                newText = selectedText ? `\n1. ${selectedText}` : '\n1. ';
                break;
        }

        textarea.value = text.substring(0, start) + newText + text.substring(end);
        textarea.selectionStart = textarea.selectionEnd = start + offset + (format === 'bold' || format === 'italic' || format === 'underline' ? selectedText.length : 0);
        textarea.focus();

        // Update character counter and preview
        $textarea.trigger('input');
    }

    /**
     * EMOJI PICKER
     */
    function initializeEmojiPickers() {
        // Instant post emoji button
        $('#emoji-btn').on('click', function(e) {
            e.preventDefault();
            toggleEmojiPicker($('#emoji-picker-container'));
        });

        // Schedule post emoji button
        $('.emoji-btn-schedule').on('click', function(e) {
            e.preventDefault();
            toggleEmojiPicker($('#emoji-picker-container-schedule'));
        });

        // Handle emoji selection
        $(document).on('emoji-click', function(e) {
            const emoji = e.detail.unicode;
            insertEmojiToTextarea(emoji);
        });
    }

    function toggleEmojiPicker($container) {
        $container.toggleClass('d-none');
        $container.slideToggle(200);
    }

    function insertEmojiToTextarea(emoji) {
        const $activeTab = $('.tab-pane.active');
        const $textarea = $activeTab.find('.posting-textarea');
        
        if (!$textarea.length) return;

        const textarea = $textarea[0];
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = textarea.value;

        textarea.value = text.substring(0, start) + emoji + text.substring(end);
        textarea.selectionStart = textarea.selectionEnd = start + emoji.length;
        textarea.focus();

        // Update counter and preview
        $textarea.trigger('input');
    }

    /**
     * IMAGE UPLOAD & PREVIEW
     */
    function initializeImageUpload() {
        // File input change
        $('#photoUpload, #photoUpload-schedule').on('change', function() {
            handleImageUpload($(this));
        });

        // Drag and drop
        $('.image-upload-box, #image-upload-box, #image-upload-box-schedule').on('dragover', function(e) {
            e.preventDefault();
            $(this).addClass('dragover');
        }).on('dragleave', function() {
            $(this).removeClass('dragover');
        }).on('drop', function(e) {
            e.preventDefault();
            $(this).removeClass('dragover');
            
            const files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                const fileInput = $(this).find('input[type="file"]')[0];
                fileInput.files = files;
                $(fileInput).trigger('change');
            }
        });

        // Remove image button
        $(document).on('click', '.remove-image-btn', function(e) {
            e.preventDefault();
            $(this).closest('.image-preview-container').slideUp(200, function() {
                $(this).remove();
                const $uploadBox = $(this).closest('.col-lg-6').find('.image-upload-box');
                $uploadBox.slideDown(200);
            });
            
            // Clear file input
            const $activeTab = $('.tab-pane.active');
            $activeTab.find('input[type="file"]').val('');
        });
    }

    function handleImageUpload($fileInput) {
        const file = $fileInput[0].files[0];
        if (!file) return;

        // Validate file
        const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/webm'];
        if (!validTypes.includes(file.type)) {
            alert('Please upload a valid image or video file');
            return;
        }

        // Check file size (max 50MB)
        if (file.size > 50 * 1024 * 1024) {
            alert('File size must be less than 50MB');
            return;
        }

        // Get container for preview
        const $activeTab = $('.tab-pane.active');
        const $container = $activeTab.find('.image-preview-container');
        const $uploadBox = $activeTab.find('.image-upload-box');

        // Read and display image
        const reader = new FileReader();
        reader.onload = function(e) {
            $uploadBox.slideUp(200);
            
            // Update image preview
            $container.find('img').attr('src', e.target.result);
            $container.find('#image-name, #image-name-schedule').text(file.name + ' (' + formatFileSize(file.size) + ')');
            
            if (!$container.is(':visible')) {
                $container.slideDown(200);
            }

            // Update post preview
            updatePostPreview($activeTab.find('.posting-textarea'));
        };
        reader.readAsDataURL(file);
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    /**
     * PRIVACY SELECTOR
     */
    function initializePrivacySelector() {
        // Visual feedback for selected privacy option
        $('.privacy-option input[type="radio"]').on('change', function() {
            $(this).closest('.privacy-options').find('.privacy-label').removeClass('selected');
            $(this).closest('.privacy-option').find('.privacy-label').addClass('selected');
        });
    }

    /**
     * POST PREVIEW
     */
    function initializePostPreview() {
        // Updated for new unified modal structure (no tabs)
        $('#post-content').on('input', function() {
            updatePostPreview($(this));
        });

        // Update preview on privacy change
        $('#privacyOptionsContainer input[type="radio"]').on('change', function() {
            const $textarea = $('#post-content');
            updatePostPreview($textarea);
        });
    }

    function updatePostPreview($textarea) {
        const content = $textarea.val();
        const $preview = $('#previewText');

        if (content && content.trim()) {
            $preview.text(content);
        } else {
            $preview.text('(No content yet)');
        }
    }

    function getPrivacyLabel(privacy) {
        const labels = {
            'only_me': 'Only Me - Private',
            'referral_partners': 'Referral Partners Only',
            'public': 'Public - Everyone'
        };
        return labels[privacy] || 'Public';
    }

    /**
     * SCHEDULE DATE & TIME
     */
    function initializeScheduleDateTime() {
        const $dateInput = $('#schedule-date');
        const $timeInput = $('#schedule-time');
        const $timezoneInput = $('#schedule-timezone');

        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        $dateInput.attr('min', today);

        // Update scheduled time preview
        $dateInput.on('change', updateSchedulePreview);
        $timeInput.on('change', updateSchedulePreview);
        $timezoneInput.on('change', updateSchedulePreview);
    }

    function updateSchedulePreview() {
        const date = $('#schedule-date').val();
        const time = $('#schedule-time').val();
        const $preview = $('#schedule-preview-time');

        if (date && time) {
            const dateObj = new Date(`${date}T${time}`);
            const formatted = dateObj.toLocaleString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });
            
            $preview.text(formatted);

            // Store timestamp
            const timestamp = Math.floor(dateObj.getTime() / 1000);
            $('#schedule_timestamp').val(timestamp);
        } else {
            $preview.text('Select a date and time');
        }
    }

    /**
     * SOCIAL MEDIA SHARE OPTIONS
     */
    function initializeSocialShareOptions() {
        // Check which social accounts are connected via AJAX
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
                action: 'check_social_connections'
            },
            xhrFields: {
                withCredentials: true  // ✅ Send cookies/session with AJAX request
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    
                    // Show social share options if any account is connected
                    if (data.facebook_connected || data.linkedin_connected) {
                        $('#socialShareOptions').show();
                    }
                    
                    // Show Facebook checkbox if connected
                    if (data.facebook_connected) {
                        $('#facebookShareOption').show();
                    } else {
                        $('#facebookShareOption').hide();
                        $('#shareToFacebook').prop('checked', false);
                    }
                    
                    // Show LinkedIn checkbox if connected
                    if (data.linkedin_connected) {
                        $('#linkedinShareOption').show();
                    } else {
                        $('#linkedinShareOption').hide();
                        $('#shareToLinkedin').prop('checked', false);
                    }
                    
                    console.log('Social connections checked:', data);
                }
            },
            error: function(xhr, status, error) {
                console.log('Failed to check social connections:', error);
                // Hide social share options if check fails
                $('#socialShareOptions').hide();
            }
        });
    }

    /**
     * FORM SUBMISSION
     */
    function initializeFormSubmission() {
        // Only attach handler once using .off to remove any existing handlers first
        $('#create-post-form-redesigned').off('submit').on('submit', function(e) {
            e.preventDefault();
            console.log('=== FORM SUBMIT EVENT TRIGGERED ===');

            const $form = $(this);
            
            // For new unified structure (no tabs)
            const content = $('#post-content').val();
            console.log('Form content length:', content ? content.length : 0);
            
            // Validate required fields
            if (!content || !content.trim()) {
                console.warn('Validation failed: No content');
                alert('Please write something before posting');
                return;
            }

            // Check if scheduled
            const isScheduled = $('#postingScheduleToggle').is(':checked');
            console.log('Is scheduled:', isScheduled);
            
            if (isScheduled) {
                const date = $('#schedule-date').val();
                const time = $('#schedule-time').val();
                console.log('Schedule date:', date, 'time:', time);
                if (!date || !time) {
                    console.warn('Validation failed: Missing schedule date/time');
                    alert('Please select a date and time for scheduled post');
                    return;
                }
            }

            console.log('Validation passed, proceeding with submission...');

            // IMPORTANT: Update hidden fields BEFORE submitting
            // Set post_status_type based on schedule
            $('#postStatusType').val(isScheduled ? 'scheduled' : 'publish');
            
            // Ensure schedule_timestamp is set (even if empty for instant posts)
            if (isScheduled) {
                const date = $('#schedule-date').val();
                const time = $('#schedule-time').val();
                if (date && time) {
                    const dateObj = new Date(`${date}T${time}`);
                    const timestamp = Math.floor(dateObj.getTime() / 1000);
                    $('#schedule_timestamp').val(timestamp);
                    console.log('Schedule timestamp set to:', timestamp);
                }
            } else {
                $('#schedule_timestamp').val('');
                console.log('Instant post - schedule_timestamp cleared');
            }

            // Disable submit button
            const $submitBtn = $('#submitPostBtn');
            $submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');

            // Submit form via AJAX
            submitPostForm($form, isScheduled, function(data) {
                console.log('Form submission callback - success');
                // Reset form
                $form[0].reset();
                $('#createPostModalRedesigned').modal('hide');
                $submitBtn.prop('disabled', false);
                
                // Reset button text based on schedule mode
                const btnText = isScheduled ? 
                    '<i class="bi bi-calendar-event me-2"></i>Schedule Post' : 
                    '<i class="bi bi-send me-2"></i>Share Now';
                $submitBtn.html(btnText);

                // Show success message
                showSuccessMessage(isScheduled ? 'Post scheduled successfully!' : 'Post published successfully!');

                // LinkedIn share outcome (MM Social Poster)
                let reloadDelay = 1500;
                const linkedin = data && data.social_shares && data.social_shares.linkedin;
                if (linkedin && linkedin.status === 'success') {
                    showSuccessMessage('Also shared on LinkedIn.');
                } else if (linkedin && linkedin.status === 'failed') {
                    showWarningMessage('Post published, but LinkedIn share failed: ' + (linkedin.error || 'Unknown error'));
                    reloadDelay = 5000;
                }

                // Reload posts
                if (typeof location !== 'undefined') {
                    setTimeout(() => location.reload(), reloadDelay);
                }
            });
        });
    }

    function submitPostForm($form, isScheduled, callback) {
        const formData = new FormData($form[0]);
        
        // Verify action is set
        if (!formData.has('action')) {
            console.warn('⚠ Action field missing, adding it');
            formData.append('action', 'create_post');
        }
        
        // Verify nonce is set
        if (!formData.has('create_post_nonce')) {
            console.warn('⚠ Nonce missing from form! This will fail.');
        }
        
        // Log form data for debugging
        console.log('%c=== SUBMITTING FORM DATA ===', 'background: #2196F3; color: white; padding: 5px 10px; border-radius: 3px;');
        console.log('isScheduled:', isScheduled);
        
        // Create object for cleaner logging
        const formDataObj = {};
        for (let [key, value] of formData.entries()) {
            if (key !== 'post_image') {
                formDataObj[key] = value || '(empty)';
            } else {
                formDataObj[key] = '[File: ' + (value.name || 'unnamed') + ']';
            }
        }
        console.table(formDataObj);
        
        const ajaxUrl = ajax_object && ajax_object.ajax_url ? ajax_object.ajax_url : ajaxurl;
        console.log('AJAX URL:', ajaxUrl);
        console.log('Request will be sent to:', ajaxUrl);
        
        $.ajax({
            type: 'POST',
            url: ajaxUrl,
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function(xhr) {
                console.log('%c[beforeSend]', 'color: orange', 'AJAX request initiated');
            },
            success: function(response) {
                console.log('%c=== AJAX SUCCESS ===', 'background: #4CAF50; color: white; padding: 5px 10px; border-radius: 3px;');
                console.log('Response:', response);
                if (response.success) {
                    callback(response.data || {});
                } else {
                    const errorMsg = response.data?.message || response.data || 'Unknown error';
                    console.error('AJAX Error response:', errorMsg);
                    alert('Error: ' + errorMsg);
                }
            },
            error: function(xhr, status, error) {
                console.error('%c=== AJAX ERROR ===', 'background: #f44336; color: white; padding: 5px 10px; border-radius: 3px;');
                console.error('Status:', status);
                console.error('Error:', error);
                console.error('Response Text:', xhr.responseText);
                console.error('Response Status Code:', xhr.status);
                alert('Error submitting post. Check console for details.');
            }
        });
    }

    function showSuccessMessage(message) {
        const $alert = $(`
            <div class="alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3" role="alert" style="z-index: 9999;">
                <i class="bi bi-check-circle me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `);
        $('body').append($alert);

        setTimeout(() => {
            $alert.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }

    function showWarningMessage(message) {
        const $alert = $(`
            <div class="alert alert-warning alert-dismissible fade show position-fixed top-0 end-0 m-3" role="alert" style="z-index: 9999; margin-top: 4.5rem !important;">
                <i class="bi bi-exclamation-triangle me-2"></i><span class="mm-warning-text"></span>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `);
        $alert.find('.mm-warning-text').text(message);
        $('body').append($alert);

        setTimeout(() => {
            $alert.fadeOut(300, function() {
                $(this).remove();
            });
        }, 6000);
    }

})(jQuery);
