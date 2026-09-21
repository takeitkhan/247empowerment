<?php
/**
 * MM SPG User Profile Customization
 * Adds phone number field to user profile in admin
 * Displays phone in users list
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add phone column to Users List
 */
add_filter('manage_users_columns', function($columns) {
    $columns['phone'] = 'Phone';
    return $columns;
});

/**
 * Populate phone column in Users List
 */
add_action('manage_users_custom_column', function($output, $column_name, $user_id) {
    if ($column_name === 'phone') {
        $phone = get_user_meta($user_id, 'phone', true);
        return !empty($phone) ? esc_html($phone) : '-';
    }
    return $output;
}, 10, 3);

/**
 * Make phone column sortable
 */
add_filter('manage_users_sortable_columns', function($sortable_columns) {
    $sortable_columns['phone'] = 'phone';
    return $sortable_columns;
});

/**
 * Add phone field to user profile edit page
 */
add_action('show_user_profile', function($user) {
    $phone = get_user_meta($user->ID, 'phone', true);
    ?>
    <h3>Additional Information</h3>
    <table class="form-table">
        <tr>
            <th><label for="phone">Phone Number</label></th>
            <td>
                <input type="tel" name="phone" id="phone" class="regular-text" value="<?php echo esc_attr($phone); ?>" placeholder="+1 (555) 123-4567" />
                <p class="description">Enter your phone number.</p>
            </td>
        </tr>
    </table>
    <?php
});

/**
 * Add phone field to edit user admin page (for admin editing other users)
 */
add_action('edit_user_profile', function($user) {
    $phone = get_user_meta($user->ID, 'phone', true);
    ?>
    <h3>Additional Information</h3>
    <table class="form-table">
        <tr>
            <th><label for="phone">Phone Number</label></th>
            <td>
                <input type="tel" name="phone" id="phone" class="regular-text" value="<?php echo esc_attr($phone); ?>" placeholder="+1 (555) 123-4567" />
                <p class="description">Enter the user's phone number.</p>
            </td>
        </tr>
    </table>
    <?php
});

/**
 * Save phone field on user profile update
 */
add_action('personal_options_update', function($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return;
    }

    $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
    update_user_meta($user_id, 'phone', $phone);
});

/**
 * Save phone field when admin edits another user
 */
add_action('edit_user_profile_update', function($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return;
    }

    $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
    update_user_meta($user_id, 'phone', $phone);
});
