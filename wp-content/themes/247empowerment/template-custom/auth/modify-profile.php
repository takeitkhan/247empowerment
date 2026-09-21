<?php
/* Template Name: Modify Profile */
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url());
    exit;
}

$current_user = wp_get_current_user();

/**
 * ✅ HANDLE POST FIRST — NO OUTPUT ABOVE THIS
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (
        !isset($_POST['frontend_profile_update_nonce']) ||
        !wp_verify_nonce($_POST['frontend_profile_update_nonce'], 'frontend_profile_update')
    ) {
        mm_handle_error('Security check failed', 'Security Error', 403);
    }

    handle_frontend_profile_update(get_current_user_id(), $_POST);

    // ✅ Redirect safely
    wp_safe_redirect(add_query_arg('updated', '1', wp_get_referer() ?: get_permalink()));
    exit;
}
/**
 * ✅ NOW SAFE TO OUTPUT HTML
 */
get_header_based_on_login();

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


$profile = $profile ?: [];
$val = fn($key, $default = '') => esc_attr($profile[$key] ?? $default);
/**
echo '<pre>';
var_dump($profile);
echo '</pre>';
**/
?>
<div class="container profile-page pt20">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3">
            <?php get_template_part('template-custom/auth/common-parts/editprofilemenu', null, ['profile' => $profile]); ?>
            <?php //get_template_part('template-custom/auth/profile-parts/navlink', null, ['profile' => $profile]); ?>
        </div>
        <div class="mb-0 rounded-end-0 col-lg-6">
            <div class="bg-white custom-card post-search">
                <div class="gap-3 post-row">
                    <div>
                        <h5 class="pb-4 text-start portal-title">Update Profile</h5>
                    </div>

                    <div>
                        <form method="post" id="frontend-profile-form" class="row g-3">
                            <?php wp_nonce_field('frontend_profile_update', 'frontend_profile_update_nonce'); ?>

                            <div class="col-12 col-md-6">
                                <label class="form-label">First Name:</label>
                                <input type="text" name="first_name" class="form-control input"
                                    value="<?= $val('first_name'); ?>" placeholder="Enter your first name" required>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Last Name:</label>
                                <input type="text" name="last_name" class="form-control input"
                                    value="<?= $val('last_name'); ?>" placeholder="Enter your last name" required>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Email:</label>
                                <input type="email" name="email" class="form-control input"
                                    value="<?= $val('email'); ?>" placeholder="Enter your email" required>
                                <div class="mt-2 form-check">
                                    <input type="checkbox" class="form-check-input" id="show_email" name="show_email" value="1"
                                        <?php checked($profile['show_email'] ?? false); ?>>
                                    <label class="form-check-label" for="show_email">Show Email on profile</label>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Phone number:</label>
                                <input type="text" name="phone" class="form-control input"
                                    value="<?= $val('phone'); ?>" placeholder="Enter your phone number" required>
                                <div class="mt-2 form-check">
                                    <input type="checkbox" class="form-check-input" id="show_phone" name="show_phone" value="1"
                                        <?php checked($profile['show_phone'] ?? false); ?>>
                                    <label class="form-check-label" for="show_phone">Show phone number on profile</label>
                                </div>
                            </div>

                            <!-- DOB -->
                            <div class="col-12 col-md-6">
                                <label class="form-label">Date of Birth:</label>
                                <input type="text" name="dob" id="dob" class="form-control input"
                                    value="<?= $val('dob'); ?>">

                                <div class="mt-2 form-check">
                                    <input type="checkbox"
                                        class="form-check-input"
                                        id="show_dob"
                                        name="show_dob"
                                        value="1"
                                        <?php checked($val('show_dob') ?? false); ?>>
                                    <label class="form-check-label" for="show_dob">
                                        Show date of birth on profile
                                    </label>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">About me (one liner):</label>
                                <input type="text" name="about_me_short" class="form-control input"
                                    value="<?= $val('about_me_short'); ?>"
                                    placeholder="About me" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label">About me (full description):</label>
                                <div class="position-relative">
                                    <textarea name="about_me" id="about_me_full" class="form-control" rows="8" placeholder="Write your full description here..."><?= esc_textarea(html_entity_decode($profile['about_me'] ?? '')); ?></textarea>
                                    <small class="text-muted">Supports Markdown formatting: Bold, Headings, Lists, Images, Video links, and Hyperlinks.</small>
                                </div>
                            </div>

                            <!-- <div id="place-autocomplete-card">
                                <p>Search for a place here:</p>
                            </div>

                            <div id="map" style="height: 300px; border-radius: 10px;"></div> -->
                            <?php
                            /** 
                            <div class="col-12">
                                <label class="form-label">Latitude:</label>
                                <input type="text" name="latitude" id="latitude" class="form-control input"
                                    value="<?php echo esc_attr(get_user_meta($current_user->ID, 'latitude', true)); ?>" placeholder="Enter latitude">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Longitude:</label>
                                <input type="text" name="longitude" id="longitude" class="form-control input"
                                    value="<?php echo esc_attr(get_user_meta($current_user->ID, 'longitude', true)); ?>" placeholder="Enter longitude">
                            </div>

                            <!-- Location -->
                            <div class="col-12">
                                <label class="form-label">Full Address:</label>
                                <input type="text" name="location" id="location" class="form-control input"
                                    value="<?php echo esc_attr(get_user_meta($current_user->ID, 'location', true)); ?>" placeholder="Enter your full address information">
                                <div class="mt-2 form-check">
                                    <input class="form-check-input" type="checkbox" id="show_full_address" name="show_full_address" value="1"
                                        <?php checked($profile['show_full_address'] ?? false); ?>>
                                    <label class="form-check-label" for="show_full_address">Show full address on profile</label>
                                </div>
                            </div>
                             **/
                            ?>

                            <div class="col-12 col-md-12">
                                <label class="form-label">Address:</label>
                                <input type="text" name="place_display_name" class="form-control input"
                                    value="<?php echo esc_attr($profile['place_display_name'] ?? ''); ?>"
                                    placeholder="Enter your address">
                                <div class="mt-2 form-check">
                                    <input class="form-check-input" type="checkbox" id="show_full_address" name="show_full_address" value="1"
                                        <?php checked($profile['show_full_address'] ?? false); ?>>
                                    <label class="form-check-label" for="show_full_address">Show full address on profile</label>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Referrer:</label>
                                <input type="text" class="form-control input" value="<?php echo esc_attr( $profile['referred_users'][0]->user_login ?? '' ); ?>" disabled>
                            </div>


                            <div class="col-12">
                                <label class="form-label">Please priorities your interests:</label>
                                <div class="row">
                                    <?php
                                    $categories = get_categories([
                                        'hide_empty' => false,
                                        'slug__not_in' => ['uncategorized'],
                                    ]);
                                    $priorities = get_user_meta($current_user->ID, 'user_categories_priority', true);
                                    $priorities = is_array($priorities) ? $priorities : [];
                                    ?>

                                    <?php foreach ($categories as $cat) :
                                        $priority = $priorities[$cat->term_id] ?? '';
                                    ?>
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <input
                                                type="checkbox"
                                                class="form-check-input"
                                                name="user_categories[]"
                                                value="<?= esc_attr($cat->term_id); ?>"
                                                <?= checked(isset($priorities[$cat->term_id]), true, false); ?>>

                                            <label class="flex-grow-1 form-check-label">
                                                <?= esc_html($cat->name); ?>
                                            </label>

                                            <select
                                                name="user_categories_priority[<?= esc_attr($cat->term_id); ?>]"
                                                class="w-auto form-select-sm form-select">
                                                <option value="">Priority</option>
                                                <option value="1" <?= selected($priority, 1); ?>>1st</option>
                                                <option value="2" <?= selected($priority, 2); ?>>2nd</option>
                                                <option value="3" <?= selected($priority, 3); ?>>3rd</option>
                                                <option value="4" <?= selected($priority, 4); ?>>4th</option>
                                                <option value="5" <?= selected($priority, 5); ?>>5th</option>
                                            </select>
                                        </div>
                                    <?php endforeach; ?>

                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-3 mt-3 col-12">
                                <!-- <button type="button" class="w-auto text-blue-color custom-btn-size" data-bs-dismiss="modal">Cancel</button> -->
                                <button type="submit" name="update_profile" class="w-auto custom-btn">Update</button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="rounded-start-0 col-lg-3">
            <?php
            $gpp_html = do_shortcode('[guide_personalized_path]');
            if ( ! empty( $gpp_html ) ) : ?>
            <div class="mb-3"><?php echo $gpp_html; ?></div>
            <?php endif; ?>
            <?php get_template_part('template-custom/auth/editprofile-parts/profile-photo-form', null, ['profile' => $profile, 'user' => $user]); ?>
        </div>
    </div>
</div>
<script>
    jQuery(document).ready(function($) {
        flatpickr("#dob", {
            dateFormat: "Y-m-d",
            maxDate: "today",
            altInput: true,
            altFormat: "F j, Y",
            yearRange: [1940, new Date().getFullYear()]
        });
    });
</script>



<!-- Auto-Save Script -->
<script>
    // Dummy function for Google Maps callback (suppress error)
    window.initProfileMap = function() {};
    
    const formId = "frontend-profile-form";
    let form = null;
    
    // Function to initialize when elements are ready
    function initializeAutoSave() {
        form = document.getElementById(formId);
        
        if (!form) {
            setTimeout(initializeAutoSave, 500);
            return;
        }
        
        setupAutoSave();
    }
    
    function setupAutoSave() {
        // Direct AJAX save to database
        function saveToDatabase(fieldName, fieldValue) {
            console.log("💾 Saving:", fieldName);
            
            // Get nonce from form
            const nonceElement = document.querySelector('[name="frontend_profile_update_nonce"]');
            if (!nonceElement) {
                console.error("❌ Nonce element not found");
                return;
            }
            const nonce = nonceElement.value;
            
            const data = new FormData();
            data.append('action', 'save_profile_field');
            data.append('field_name', fieldName);
            data.append('field_value', fieldValue);
            data.append('nonce', nonce);
            
            const url = typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php';
            console.log("📡 AJAX URL:", url);
            console.log("📋 Data:", { field_name: fieldName, field_value: fieldValue, nonce: nonce });
            
            fetch(url, {
                method: 'POST',
                body: data
            })
            .then(response => {
                console.log("📥 Response status:", response.status);
                return response.json();
            })
            .then(result => {
                console.log("📥 Response data:", result);
                if (result.success) {
                    console.log("✅ Saved:", fieldName);
                    showAlert();
                } else {
                    console.error("❌ Error:", result.data);
                }
            })
            .catch(error => console.error("❌ Fetch Error:", error));
        }
        
        function showAlert() {
            // Simple message display
            console.log("✅ Field saved successfully!");
        }
        
        // Attach blur listeners to all input fields
        const allInputs = form.querySelectorAll("input[type='text'], input[type='email'], input[type='date'], textarea");
        
        allInputs.forEach((field) => {
            field.addEventListener("blur", function(e) {
                if (this.value.length > 0) {
                    saveToDatabase(this.name, this.value);
                }
            });
        });
        
        // Checkbox changes
        form.addEventListener("change", function(e) {
            if (e.target.type === "checkbox") {
                saveToDatabase(e.target.name, e.target.checked ? "1" : "0");
            }
        });
        
        console.log("✅ Auto-Save Ready");
    }
    
    // Start initialization
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initializeAutoSave);
    } else {
        initializeAutoSave();
    }
</script>

<?php get_footer_based_on_login(); ?>