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

        // Redirect to PDF download page after submission
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
