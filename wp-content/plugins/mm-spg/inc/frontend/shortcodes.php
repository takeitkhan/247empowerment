<?php

add_action('wp_ajax_mm_spg_render_shortcode', 'mm_spg_render_shortcode');

function mm_spg_render_shortcode()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in');
    }

    $shortcode = sanitize_text_field($_POST['shortcode'] ?? '');

    if (empty($shortcode)) {
        wp_send_json_error('Empty shortcode');
    }

    ob_start();
    echo do_shortcode($shortcode);
    $html = ob_get_clean();

    wp_send_json_success([
        'html' => $html,
    ]);
}

add_shortcode('mm_spg_interest_form', function () {

    if (!is_user_logged_in()) {
        return;
    }

    $user_id = get_current_user_id();

    /* =========================
       LOAD DATA
    ========================= */
    $categories = get_categories([
        'hide_empty'   => false,
        'slug__not_in' => ['uncategorized'],
    ]);

    $saved_priorities = get_user_meta($user_id, 'user_categories_priority', true);
    $saved_priorities = is_array($saved_priorities) ? $saved_priorities : [];

    ob_start();
?>

    <form class="mm-spg-interest-form" data-step="interests">
        <?php wp_nonce_field('mm_spg_interest_save', 'mm_spg_interest_nonce'); ?>

        <label class="mb-3 form-label fw-bold">
            Please Select your Primary Interest:
        </label>

        <div class="mb-2 text-danger text-center mm-spg-error" style="display:none;"></div>

        <div class="row g-2">
            <?php foreach ($categories as $cat):
                $priority = $saved_priorities[$cat->term_id] ?? '';
            ?>
                <div class="col-12 col-md-6">
                    <div class="d-flex align-items-center gap-2 bg-light p-2 border rounded h-100">

                        <input
                            type="checkbox"
                            class="ms-1 form-check-input"
                            name="user_categories[]"
                            value="<?= esc_attr($cat->term_id); ?>"
                            <?= checked(isset($saved_priorities[$cat->term_id]), true, false); ?>>

                        <label class="flex-grow-1 mb-0 text-truncate form-check-label"
                            title="<?= esc_attr($cat->name); ?>">
                            <?= esc_html($cat->name); ?>
                        </label>

                        <select
                            name="user_categories_priority[<?= esc_attr($cat->term_id); ?>]"
                            class="form-select-sm form-select"
                            style="max-width: 90px;">
                            <option value="">Priority</option>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?= $i ?>" <?= selected($priority, $i); ?>>
                                    <?= $i ?><?= $i === 1 ? 'st' : ($i === 2 ? 'nd' : ($i === 3 ? 'rd' : 'th')) ?>
                                </option>
                            <?php endfor; ?>
                        </select>

                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- <button type="submit" name="mm_spg_interest_submit" class="mt-3 btn btn-primary">
            Save Interests
        </button> -->
    </form>


<?php
    return ob_get_clean();
});

add_action('wp_ajax_mm_spg_save_interests', 'mm_spg_save_interests');

function mm_spg_save_interests()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in');
    }

    check_ajax_referer('mm_spg_interest_save', 'mm_spg_interest_nonce');

    $user_id = get_current_user_id();

    $selected = array_map('intval', $_POST['user_categories'] ?? []);
    $priorities_raw = $_POST['user_categories_priority'] ?? [];

    /* -------------------------
       RULE 1: At least one checkbox
    -------------------------- */
    if (empty($selected)) {
        wp_send_json_error('Please select at least one interest.');
    }

    $priorities = [];
    $has_first_priority = false;

    foreach ($priorities_raw as $term_id => $priority) {
        if (!in_array((int)$term_id, $selected, true)) {
            continue;
        }

        $priority = (int) $priority;

        if ($priority >= 1 && $priority <= 5) {
            $priorities[(int)$term_id] = $priority;

            if ($priority === 1) {
                $has_first_priority = true;
            }
        }
    }


    /* -------------------------
       RULE 2: At least one 1st priority
    -------------------------- */
    if (!$has_first_priority) {
        wp_send_json_error('Please assign at least one interest as 1st priority.');
    }

    /* -------------------------
       RULE 3: No duplicate priorities
    -------------------------- */
    if (count($priorities) !== count(array_unique($priorities))) {
        wp_send_json_error('Duplicate priorities are not allowed.');
    }

    if (count($priorities) !== count($selected)) {
        wp_send_json_error('Each selected interest must have a priority.');
    }


    /* -------------------------
       SAVE
    -------------------------- */
    update_user_meta($user_id, 'user_categories', $selected);
    update_user_meta($user_id, 'user_categories_priority', $priorities);

    update_user_meta($user_id, 'mm_spg_interest_completed', 1);

    // Phase flow
    delete_user_meta($user_id, 'mm_spg_phase_3_started');
    update_user_meta($user_id, 'mm_spg_phase_2_completed', 1);
    update_user_meta($user_id, 'mm_spg_step', 0);
    update_user_meta($user_id, 'mm_spg_status', 'active');

    wp_send_json_success('Interests saved successfully.');
}



add_shortcode('mm_spg_social_links_form', function () {

    if (!is_user_logged_in()) {
        return '<p>Please log in to manage your links.</p>';
    }

    $user_id = get_current_user_id();

    $platform_options = [
        'facebook'  => 'Facebook',
        'instagram' => 'Instagram',
        'linkedin'  => 'LinkedIn',
        'twitter'   => 'Twitter / X',
        'youtube'   => 'YouTube',
        'website'   => 'Website',
    ];

    $saved_links = get_user_meta($user_id, 'custom_social_links', true);
    $saved_links = is_array($saved_links) ? $saved_links : [];

    ob_start();
?>
    <form method="post" class="mm-spg-social-links-form">
        <?php wp_nonce_field('mm_spg_links_save', 'mm_spg_links_nonce'); ?>

        <label class="mb-2 form-label">Social Management</label>

        <div id="social-links-group">
            <?php foreach ($saved_links as $index => $item): ?>
                <div class="align-items-center mb-2 row g-2 social-link-row">
                    <div class="col-md-3">
                        <select name="links[<?= $index ?>][platform]" class="form-select">
                            <?php foreach ($platform_options as $value => $label): ?>
                                <option value="<?= esc_attr($value); ?>" <?= selected($item['platform'], $value); ?>>
                                    <?= esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <input type="text"
                            name="links[<?= $index ?>][label]"
                            class="form-control"
                            value="<?= esc_attr($item['label']); ?>"
                            placeholder="Custom label">
                    </div>

                    <div class="col-md-4">
                        <input type="url"
                            name="links[<?= $index ?>][url]"
                            class="form-control"
                            value="<?= esc_url($item['url']); ?>"
                            placeholder="https://example.com">
                    </div>

                    <div class="col-md-2">
                        <button type="button" class="w-100 btn btn-danger remove-link">
                            Remove
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="d-flex gap-3">
            <button type="button" class="mt-3 btn btn-secondary btn-sm" id="mm-spg-add-social-link">+ Add Link</button>

            <!-- <button type="submit" name="mm_spg_links_submit" class="mt-3 btn btn-info btn-sm">
                Update Links
            </button> -->
        </div>

    </form>
<?php
    return ob_get_clean();
});


add_shortcode('mm_spg_additional_profile_details', function () {

    if (!is_user_logged_in()) {
        return '<p>Please log in.</p>';
    }

    $user_id = get_current_user_id();

    $designation  = get_user_meta($user_id, 'designation', true);
    $about_short  = get_user_meta($user_id, 'digital_card_about', true);
    $keywords     = get_user_meta($user_id, 'user_keywords', true);
    $hashtags     = get_user_meta($user_id, 'user_hashtags', true);
    $place_display_name = get_user_meta($user_id, 'place_display_name', true);
    $show_full_address  = get_user_meta($user_id, 'show_full_address', true);

    ob_start();
?>
    <form class="mm-spg-additional-profile-form" novalidate>

        <?php wp_nonce_field('mm_spg_save_additional_profile', 'mm_spg_additional_nonce'); ?>
        <!-- Designation -->
        <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text"
                name="designation"
                class="form-control"
                maxlength="60"
                value="<?= esc_attr($designation); ?>"
                required>
        </div>

        <!-- About -->
        <div class="mb-3">
            <label class="form-label">About Me (Max 150)</label>
            <textarea name="about_me_short"
                id="about_me_short"
                class="form-control"
                maxlength="150"
                rows="3"
                required><?= esc_textarea($about_short); ?></textarea>
            <div class="text-muted small">
                <span id="charCount">0</span>/150
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Address</label>
            <input type="text"
                name="place_display_name"
                class="form-control"
                maxlength="120"
                value="<?= esc_attr($place_display_name); ?>">

            <div class="mt-2 form-check">
                <input class="form-check-input"
                    type="checkbox"
                    id="show_full_address"
                    name="show_full_address"
                    value="1"
                    <?= checked($show_full_address, '1', false); ?>>
                <label class="form-check-label" for="show_full_address">
                    Show full address on profile
                </label>
            </div>
        </div>

        <!-- Keywords -->
        <div class="mb-3">
            <label class="form-label">Keywords</label>

            <div id="keyword-tags"
                class="d-flex flex-wrap gap-2 form-control mm-spg-keyword-tags"
                style="min-height:44px; padding:6px;">
                <?php if ($keywords): ?>
                    <?php foreach (explode(',', $keywords) as $kw): ?>
                        <span class="bg-light border text-dark badge keyword-tag">
                            <?= esc_html(trim($kw)); ?>
                            <button type="button" class="btn-close remove-tag"></button>
                        </span>
                    <?php endforeach; ?>
                <?php endif; ?>
                <input type="text" id="keywordInput" class="flex-grow-1 border-0">
            </div>

            <input type="hidden" name="user_keywords" id="keywords-hidden">
        </div>

        <!-- Hashtags -->
        <div class="mb-3">
            <label class="form-label">Hashtags</label>

            <div id="hashtag-tags"
                class="d-flex flex-wrap gap-2 form-control mm-spg-hashtag-tags"
                style="min-height:44px; padding:6px;">
                <?php if ($hashtags): ?>
                    <?php foreach (explode(',', $hashtags) as $tag): ?>
                        <span class="bg-light border text-dark badge hashtag-tag">
                            <?= esc_html(trim($tag)); ?>
                            <button type="button" class="btn-close remove-hashtag"></button>
                        </span>
                    <?php endforeach; ?>
                <?php endif; ?>
                <input type="text" id="hashtagInput" class="flex-grow-1 border-0">
            </div>

            <input type="hidden" name="user_hashtags" id="hashtags-hidden">
        </div>
    </form>
<?php
    return ob_get_clean();
});

add_action('wp_ajax_mm_spg_save_additional_profile', function () {

    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in');
    }

    check_ajax_referer(
        'mm_spg_save_additional_profile',
        'mm_spg_additional_nonce'
    );

    $title = trim($_POST['designation'] ?? '');
    $about = trim($_POST['about_me_short'] ?? '');
    $address = trim($_POST['place_display_name'] ?? '');
    $keywords = trim($_POST['user_keywords'] ?? '');
    $hashtags = trim($_POST['user_hashtags'] ?? '');

    if ($title === '') {
        wp_send_json_error('Title is required.');
    }

    if ($about === '') {
        wp_send_json_error('About section is required.');
    }

    if ($address === '' && $keywords === '' && $hashtags === '') {
        wp_send_json_error('Please add at least one: Address, Keyword, or Hashtag.');
    }

    $user_id = get_current_user_id();

    // Title / Designation
    update_user_meta(
        $user_id,
        'designation',
        sanitize_text_field($_POST['designation'] ?? '')
    );

    // About (max 150 chars)
    $about = sanitize_text_field($_POST['about_me_short'] ?? '');
    update_user_meta(
        $user_id,
        'digital_card_about',
        mb_substr($about, 0, 150)
    );

    // Keywords (comma separated)
    update_user_meta(
        $user_id,
        'user_keywords',
        sanitize_text_field($_POST['user_keywords'] ?? '')
    );

    // Hashtags (clean + normalize)
    $hashtags_input = sanitize_text_field($_POST['user_hashtags'] ?? '');
    $hashtags_array = array_filter(array_map('trim', explode(',', $hashtags_input)));

    $clean_hashtags = [];
    foreach ($hashtags_array as $tag) {
        if ($tag !== '') {
            if (mb_substr($tag, 0, 1) !== '#') {
                $tag = '#' . $tag;
            }
            $clean_hashtags[] = $tag;
        }
    }

    update_user_meta(
        $user_id,
        'user_hashtags',
        implode(', ', $clean_hashtags)
    );

    update_user_meta(
        $user_id,
        'mm_spg_additional_profile_completed',
        1
    );

    wp_send_json_success('Profile details saved successfully.');
});
