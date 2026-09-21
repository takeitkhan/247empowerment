<?php
function save_sales_agreement_data()
{
    if (isset($_POST['save_agreement']) && is_user_logged_in()) {

        $user_id = get_current_user_id();

        // Save dates only if not already saved
        if (!get_user_meta($user_id, 'agreement_effective_date', true)) {
            update_user_meta($user_id, 'agreement_effective_date', sanitize_text_field($_POST['effective_date']));
        }
        if (!get_user_meta($user_id, 'agreement_signature_date', true)) {
            update_user_meta($user_id, 'agreement_signature_date', sanitize_text_field($_POST['signature_date']));
        }

        // Always save name and signature
        update_user_meta($user_id, 'agreement_printed_name', sanitize_text_field($_POST['printed_name']));
        update_user_meta($user_id, 'agreement_signature', sanitize_text_field($_POST['signature']));

        // -----------------------------------------
        // 👉 Mark user as Sales Person
        // -----------------------------------------
        update_user_meta($user_id, 'is_sales_person', 1);

        // -----------------------------------------
        // 👉 Add a notification for user
        // -----------------------------------------
        Notifications::getInstance()->addNotification(
            $user_id,
            'success',
            'Your sales agreement has been submitted successfully!',
            ['action' => 'sales_agreement_submitted']
        );

        // -----------------------------------------
        // 👉 Redirect to PDF download page
        // -----------------------------------------
        wp_redirect(site_url('/download-sales-agreement-pdf/'));
        exit;
    }
}
add_action('init', 'save_sales_agreement_data');

add_filter('wp_nav_menu_items', 'conditional_menu_item', 10, 2);
function conditional_menu_item($items, $args)
{
    // Check if this is the correct menu location
    if ($args->theme_location === 'megarightmenu') {
        $user_id = get_current_user_id();
        $agreement_printed_name = get_user_meta($user_id, 'agreement_printed_name', true);
        $agreement_signature    = get_user_meta($user_id, 'agreement_signature', true);

        if ($agreement_printed_name && $agreement_signature) {
            // Add a new menu item safely
            $pdf_url = esc_url('/download-sales-agreement-pdf');
            $items .= '<li class="menu-item"><a target="_blank" href="' . $pdf_url . '">Download Signed Sales Agreement</a></li>';
        }
    }
    return $items;
}



/**
 * Save Participation Agreement Form
 */
function save_participation_agreement_form() {

    if (!isset($_POST['save_participation_agreement']) || !is_user_logged_in()) {
        return;
    }

    $user_id = get_current_user_id();

    /* -----------------------------------------
     * 1. BASIC PARTICIPANT INFO
     * ----------------------------------------- */
    $basic_fields = [
        'pa_participant_fullname',
        'pa_participant_email',
        'pa_participant_phone',
        'pa_participant_emergency',
        'pa_participant_career',
    ];

    foreach ($basic_fields as $field) {
        if (!empty($_POST[$field])) {
            $value = $field === 'pa_participant_email'
                        ? sanitize_email($_POST[$field])
                        : sanitize_text_field($_POST[$field]);

            update_user_meta($user_id, $field, $value);
        }
    }

    /* -----------------------------------------
     * 2. AVAILABLE DAYS / AVAILABLE TIMES
     * ----------------------------------------- */
    if (!empty($_POST['pa_available_days']) && is_array($_POST['pa_available_days'])) {
        update_user_meta($user_id, 'pa_available_days', array_map('sanitize_text_field', $_POST['pa_available_days']));
    }

    if (!empty($_POST['pa_available_times']) && is_array($_POST['pa_available_times'])) {
        update_user_meta($user_id, 'pa_available_times', array_map('sanitize_text_field', $_POST['pa_available_times']));
    }

    /* -----------------------------------------
     * 3. TEXTAREAS + RADIO FIELDS
     * ----------------------------------------- */
    $fields = [
        'pa_goal_12_months',
        'pa_learn_about_us',
        'pa_suggestions',
        'pa_differentiates_us',
        'pa_interested_parts',
        'pa_inspiration',
        'pa_skills_gifts',
        'pa_barriers',
        'pa_goals',
        'pa_read_faq',
        'pa_media_consent',
        'pa_referred',
        'pa_referrer_name',
    ];

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_user_meta($user_id, $field, sanitize_text_field($_POST[$field]));
        }
    }

    /* -----------------------------------------
     * 4. CHECKBOX DECLARATIONS
     * ----------------------------------------- */
    $checkboxes = [
        'pa_declare_ready',
        'pa_declare_prepared',
        'pa_declare_responsibility',
        'pa_declare_confidentiality',
        'pa_declare_no_guarantees',
        'pa_declare_emotional_stable',
    ];

    foreach ($checkboxes as $checkbox) {
        // Checkboxes only submit value if checked
        $value = isset($_POST[$checkbox]) ? 'yes' : 'no';
        update_user_meta($user_id, $checkbox, $value);
    }

    /* -----------------------------------------
     * 5. SIGNATURE FIELDS
     * ----------------------------------------- */

    // Only save date fields once.
    if (!get_user_meta($user_id, 'pa_effective_date', true) && isset($_POST['pa_effective_date'])) {
        update_user_meta($user_id, 'pa_effective_date', sanitize_text_field($_POST['pa_effective_date']));
    }

    if (!get_user_meta($user_id, 'pa_signature_date', true) && isset($_POST['pa_signature_date'])) {
        update_user_meta($user_id, 'pa_signature_date', sanitize_text_field($_POST['pa_signature_date']));
    }

    // Always update printed name & signature
    if (isset($_POST['pa_printed_name'])) {
        update_user_meta($user_id, 'pa_printed_name', sanitize_text_field($_POST['pa_printed_name']));
    }

    if (isset($_POST['pa_address'])) {
        update_user_meta($user_id, 'pa_address', sanitize_text_field($_POST['pa_address']));
    }

    if (isset($_POST['pa_signature'])) {
        update_user_meta($user_id, 'pa_signature', sanitize_text_field($_POST['pa_signature']));
    }

    /* -----------------------------------------
     * 6. REDIRECT AFTER SUCCESS
     * ----------------------------------------- */
    wp_redirect(site_url('/download-participation-agreement-pdf/'));
    exit;
}
add_action('init', 'save_participation_agreement_form');


add_filter('wp_nav_menu_items', 'conditional_participation_menu_item', 10, 2);
function conditional_participation_menu_item($items, $args) {

    if ($args->theme_location === 'megarightmenu') {

        $user_id = get_current_user_id();

        $printed_name = get_user_meta($user_id, 'pa_printed_name', true);
        $signature    = get_user_meta($user_id, 'pa_signature', true);

        if (!empty($printed_name) && !empty($signature)) {
            $pdf_url = esc_url('/download-participation-agreement-pdf/');
            $items .= '<li class="menu-item"><a target="_blank" href="' . $pdf_url . '">Download Participation Agreement</a></li>';
        }
    }

    return $items;
}
