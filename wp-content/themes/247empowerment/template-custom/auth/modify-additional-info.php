<?php
/* Template Name: Additional Profile Details */

get_header_based_on_login();

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url());
    exit;
}


$current_user = wp_get_current_user();

// Get current logged-in user ID (used as a fallback if no slug is provided)
$current_user_id = get_current_user_id();

// 1. Get the user slug from the query variable
$user_slug = get_query_var('user_profile');

// 2. Determine the target user
if ($user_slug) {
    // If a slug is present, try to get the user by their slug (login or nicename)
    $user = get_user_by('slug', $user_slug);
} else {
    // If no slug, fall back to the currently logged-in user
    $user = get_user_by('ID', $current_user_id);
}

// 3. Instantiate the UserProfileData class and get the profile array
if ($user) {
    // We pass the WP_User object to the class constructor, or the ID/slug depending on the class's constructor.
    // Given your original line: $profile = (new UserProfileData($user_slug))->getProfile();
    // We'll update it to pass the $user object for better data handling, assuming the class supports it.
    // If the class REQUIRES a slug, use $user_slug or $user->user_login.

    // Option A: If UserProfileData takes a WP_User object (Recommended)
    $profile_data_instance = new UserProfileData($user);

    // Option B: If UserProfileData only takes the slug (Sticking closer to your original code)
    // Use the slug if present, otherwise use the current user's login.
    $target_identifier = $user_slug ? $user_slug : $user->user_login;
    $profile_data_instance = new UserProfileData($target_identifier);

    // Get the profile array
    $profile = $profile_data_instance->getProfile();
} else {
    // Set variables to null if no user could be determined
    $user = null;
    $profile = null;
}

// Fetch existing meta
$designation = get_user_meta($current_user_id, 'designation', true);
$about_short = get_user_meta($current_user_id, 'digital_card_about', true);
$keywords = get_user_meta($current_user_id, 'user_keywords', true);
$hashtags = get_user_meta($current_user_id, 'user_hashtags', true);

// Save form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_additional_details'])) {

    if (!isset($_POST['additional_details_nonce']) || !wp_verify_nonce($_POST['additional_details_nonce'], 'additional_details_action')) {
        echo '<div class="alert alert-danger">Security check failed.</div>';
    } else {

        // 0) Designation
        $designation = sanitize_text_field($_POST['designation'] ?? '');
        update_user_meta($current_user_id, 'designation', $designation);

        // 1) About me short (MAX 150 chars)
        $about_short = sanitize_text_field($_POST['about_me_short']);
        $about_short = mb_substr($about_short, 0, 150); // safer for UTF-8
        update_user_meta($current_user_id, 'digital_card_about', $about_short);

        // 2) Keywords (comma separated)
        $keywords = sanitize_text_field($_POST['user_keywords']);
        update_user_meta($current_user_id, 'user_keywords', $keywords);

        // 3) Hashtags (validated)
        $hashtags_input = sanitize_text_field($_POST['user_hashtags']);
        $hashtags_array = array_filter(array_map('trim', explode(',', $hashtags_input)));

        $clean_hashtags = [];
        foreach ($hashtags_array as $tag) {
            if ($tag !== '') {
                if ($tag[0] !== '#') {
                    $tag = '#' . $tag;
                }
                $clean_hashtags[] = $tag;
            }
        }

        update_user_meta($current_user_id, 'user_hashtags', implode(', ', $clean_hashtags));

        //echo '<div class="mt-3 alert alert-success">Additional profile details updated successfully.</div>';
    }
}

?>

<div class="container profile-page pt20">
    <div class="row">

        <!-- Sidebar -->
        <div class="col-lg-3">
            <?php get_template_part('template-custom/auth/common-parts/editprofilemenu', null, ['profile' => $profile]); ?>
            <?php get_template_part('template-custom/auth/profile-parts/navlink', null, ['profile' => $profile]); ?>
        </div>

        <!-- Main Content -->
        <div class="col-lg-6">
            <div class="bg-white p-4 custom-card">
                <div>
                    <h5 class="pb-4 text-start portal-title">Additional Profile Details For Digital Business Card</h5>
                </div>

                <form method="post" class="row g-3" id="additional-details-form">
                    <?php wp_nonce_field('additional_details_action', 'additional_details_nonce'); ?>

                    <!-- Designation -->
                    <div class="col-12">
                        <label class="form-label">Title</label>
                        <input type="text"
                            name="designation"
                            class="form-control input"
                            value="<?php echo esc_attr($designation); ?>"
                            placeholder="e.g. Founder & CEO"
                            maxlength="60"
                            required>
                    </div>
                    <!-- About Me Short (150 Max) -->
                    <div class="col-12">
                        <label class="form-label">About Me (Max 150 characters)</label>
                        <textarea name="about_me_short" id="about_me_short"
                            maxlength="150"
                            required
                            class="form-control input"
                            rows="3"><?php echo esc_textarea($about_short); ?></textarea>
                        <div class="text-muted small">
                            <span id="charCount">0</span>/150 characters
                        </div>
                    </div>

                    <!-- Keywords -->
                    <div class="col-12">
                        <label class="form-label">Keywords (comma separated)</label>

                        <div id="keyword-tags" class="d-flex flex-wrap gap-2 form-control" style="min-height:44px; padding:6px;">
                            <!-- Render existing keywords -->
                            <?php if ($keywords): ?>
                                <?php foreach (explode(',', $keywords) as $kw): ?>
                                    <span class="bg-light border text-dark badge keyword-tag">
                                        <?php echo trim($kw); ?>
                                        <button type="button" class="ms-1 btn-close remove-tag" style="font-size:10px;"></button>
                                    </span>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <!-- Input area -->
                            <input type="text" id="keywordInput" class="flex-grow-1 border-0" style="min-width:120px;" placeholder="Type and press comma">
                        </div>

                        <!-- Hidden field for form submission -->
                        <input type="hidden" name="user_keywords" id="keywords-hidden">
                    </div>


                    <!-- Hashtags -->
                    <div class="col-12">
                        <label class="form-label">Hashtags (comma separated):</label>

                        <div id="hashtag-tags" class="d-flex flex-wrap gap-2 form-control"
                            style="min-height:44px; padding:6px;">

                            <!-- Load existing hashtags -->
                            <?php if ($hashtags): ?>
                                <?php foreach (explode(',', $hashtags) as $tag): ?>
                                    <?php $tag = trim($tag); ?>
                                    <span class="bg-light border text-dark badge hashtag-tag">
                                        <?php echo esc_html($tag); ?>
                                        <button type="button" class="ms-1 btn-close remove-hashtag" style="font-size:10px;"></button>
                                    </span>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <!-- Input area for new tags -->
                            <input type="text" id="hashtagInput"
                                class="flex-grow-1 border-0"
                                style="min-width:120px;"
                                placeholder="Type and press comma">
                        </div>

                        <!-- Hidden field to store merged hashtags -->
                        <input type="hidden" name="user_hashtags" id="hashtags-hidden">

                        <div class="mt-1 text-muted small">
                            Must start with # — auto-corrects missing hashtags.
                        </div>
                    </div>


                    <!-- Submit -->
                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" name="save_additional_details" class="custom-btn">
                            Save Details
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Side (Profile Photo Area) -->
        <div class="rounded-start-0 col-lg-3">
            <?php get_template_part('template-custom/auth/editprofile-parts/profile-photo-form', null, ['profile' => $profile, 'user' => $user]); ?>
        </div>

    </div>
</div>

<script>
    // Form submission handler to ensure hidden fields are populated
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('additional-details-form');
        if (form) {
            form.addEventListener('submit', function() {
                // Update keywords hidden field
                const keywordContainer = document.getElementById('keyword-tags');
                if (keywordContainer) {
                    const keywords = [...keywordContainer.querySelectorAll('.keyword-tag')]
                        .map(tag => tag.textContent.replace(/\s*×\s*$/, '').trim());
                    document.getElementById('keywords-hidden').value = keywords.join(', ');
                }
                
                // Update hashtags hidden field
                const hashtagContainer = document.getElementById('hashtag-tags');
                if (hashtagContainer) {
                    const hashtags = [...hashtagContainer.querySelectorAll('.hashtag-tag')]
                        .map(tag => tag.textContent.replace(/\s*×\s*$/, '').trim());
                    document.getElementById('hashtags-hidden').value = hashtags.join(', ');
                }
            });
        }
    });

    // Live character count for About Short field
    document.addEventListener('DOMContentLoaded', function() {
        const textarea = document.getElementById('about_me_short');
        const counter = document.getElementById('charCount');

        function updateCounter() {
            counter.textContent = textarea.value.length;
        }

        textarea.addEventListener('input', updateCounter);
        updateCounter(); // init
    });

    // KEYWORDS COMMA BLOCKING
    document.addEventListener("DOMContentLoaded", function() {

        const tagContainer = document.getElementById("keyword-tags");
        const input = document.getElementById("keywordInput");
        const hiddenField = document.getElementById("keywords-hidden");

        function updateHiddenField() {
            const tags = [...tagContainer.querySelectorAll(".keyword-tag")]
                .map(tag => {
                    // Extract text before the close button
                    const text = tag.childNodes[0].nodeValue;
                    return text ? text.trim() : '';
                })
                .filter(tag => tag !== '');
            hiddenField.value = tags.join(", ");
        }

        function addTag(text) {
            const tag = text.trim();
            if (tag === "") return;

            const span = document.createElement("span");
            span.className = "badge bg-light text-dark border keyword-tag";
            span.innerHTML = `${tag} <button type="button" class="ms-1 btn-close remove-tag" style="font-size:10px;"></button>`;

            tagContainer.insertBefore(span, input);
            input.value = "";
            updateHiddenField();
        }

        // Add tag when comma is pressed
        input.addEventListener("keyup", function(e) {
            if (e.key === ",") {
                addTag(input.value.replace(",", ""));
            }
        });

        // Remove tag
        tagContainer.addEventListener("click", function(e) {
            if (e.target.classList.contains("remove-tag")) {
                e.target.parentElement.remove();
                updateHiddenField();
            }
        });

        // Initial update
        updateHiddenField();
    });

    // HASHTAGS COMMA BLOCKING
    document.addEventListener("DOMContentLoaded", function() {

        const tagContainer = document.getElementById("hashtag-tags");
        const input = document.getElementById("hashtagInput");
        const hiddenField = document.getElementById("hashtags-hidden");

        function updateHiddenField() {
            const tags = [...tagContainer.querySelectorAll(".hashtag-tag")]
                .map(tag => {
                    // Extract text before the close button
                    const text = tag.childNodes[0].nodeValue;
                    return text ? text.trim() : '';
                })
                .filter(tag => tag !== '');
            hiddenField.value = tags.join(", ");
        }

        function addTag(text) {
            let tag = text.trim();
            if (tag === "") return;

            // 🔧 Auto-add "#" if missing
            if (!tag.startsWith("#")) {
                tag = "#" + tag;
            }

            const span = document.createElement("span");
            span.className = "badge bg-light text-dark border hashtag-tag";
            span.innerHTML = `${tag} <button type="button" class="ms-1 btn-close remove-hashtag" style="font-size:10px;"></button>`;

            tagContainer.insertBefore(span, input);
            input.value = "";
            updateHiddenField();
        }

        // Add tag when comma pressed
        input.addEventListener("keyup", function(e) {
            if (e.key === ",") {
                addTag(input.value.replace(",", ""));
            }
        });

        // Remove tag on X click
        tagContainer.addEventListener("click", function(e) {
            if (e.target.classList.contains("remove-hashtag")) {
                e.target.parentElement.remove();
                updateHiddenField();
            }
        });

        // Init
        updateHiddenField();
    });
</script>


<?php get_footer_based_on_login(); ?>