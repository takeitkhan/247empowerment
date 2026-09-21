<?php
/**
 * Image Upload Component
 * Variables (required):
 * - $upload_id: ID for file input
 * - $preview_container_id: ID for preview container
 * - $preview_id: ID for preview image
 * - $preview_name_id: ID for image name display
 */
?>

<div class="posting-image-section mt-3">
    <div class="image-upload-box" id="image-upload-box<?php echo isset($upload_id) && strpos($upload_id, 'schedule') !== false ? '-schedule' : ''; ?>">
        <input type="file" name="post_image" id="<?php echo esc_attr($upload_id); ?>" class="d-none" accept="image/*">
        <label for="<?php echo esc_attr($upload_id); ?>" class="image-upload-label">
            <div class="upload-icon">
                <i class="bi bi-image"></i>
            </div>
            <div class="upload-text">
                <p class="mb-1 fw-bold">Add Image</p>
                <p class="small text-muted mb-0">or drag and drop</p>
            </div>
        </label>
    </div>
    
    <!-- Image Preview with Thumbnail -->
    <div class="image-preview-container" id="<?php echo esc_attr($preview_container_id); ?>" style="display: none;">
        <div class="position-relative">
            <img id="<?php echo esc_attr($preview_id); ?>" src="" alt="Preview" class="img-fluid rounded" style="max-height: 200px; object-fit: cover; width: 100%;">
            <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2 remove-image-btn" aria-label="Remove image">
                <i class="bi bi-trash"></i>
            </button>
        </div>
        <div class="mt-2 image-info">
            <small id="<?php echo esc_attr($preview_name_id); ?>" class="text-muted"></small>
        </div>
    </div>
</div>

<!-- JAVASCRIPT: Image Upload & Preview Handler -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadInput = document.getElementById('<?php echo esc_attr($upload_id); ?>');
    const previewContainer = document.getElementById('<?php echo esc_attr($preview_container_id); ?>');
    const previewImage = document.getElementById('<?php echo esc_attr($preview_id); ?>');
    const imageName = document.getElementById('<?php echo esc_attr($preview_name_id); ?>');
    const uploadBox = document.querySelector('#image-upload-box<?php echo isset($upload_id) && strpos($upload_id, 'schedule') !== false ? '-schedule' : ''; ?>');

    if (!uploadInput) return;

    // Handle file selection
    uploadInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file && file.type.startsWith('image/')) {
            displayImagePreview(file);
        } else if (file) {
            alert('Please select a valid image file');
            this.value = '';
        }
    });

    // Handle drag and drop
    if (uploadBox) {
        uploadBox.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.backgroundColor = '#f0f0f0';
        });

        uploadBox.addEventListener('dragleave', function() {
            this.style.backgroundColor = '';
        });

        uploadBox.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.backgroundColor = '';
            const files = e.dataTransfer.files;
            if (files[0] && files[0].type.startsWith('image/')) {
                uploadInput.files = files;
                uploadInput.dispatchEvent(new Event('change', { bubbles: true }));
            } else {
                alert('Please drop a valid image file');
            }
        });
    }

    // Remove image button
    const removeBtn = previewContainer?.querySelector('.remove-image-btn');
    if (removeBtn) {
        removeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            uploadInput.value = '';
            previewContainer.style.display = 'none';
            uploadBox.style.display = 'block';
        });
    }

    // Display preview function
    function displayImagePreview(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImage.src = e.target.result;
            imageName.textContent = file.name;
            previewContainer.style.display = 'block';
            uploadBox.style.display = 'none';
        };
        reader.readAsDataURL(file);
    }
});
</script>
