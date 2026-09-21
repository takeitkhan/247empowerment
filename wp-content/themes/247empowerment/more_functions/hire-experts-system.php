<?php
/**
 * Hire Experts System
 * Handles expert hire requests from wizard form
 * Stores in database and sends email notifications
 */

// Register Custom Post Type for Expert Requests
add_action('init', 'register_expert_request_post_type');
function register_expert_request_post_type() {
    $args = array(
        'label'              => 'Expert Requests',
        'description'        => 'Submitted requests to hire experts',
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'menu_position'      => 25,
        'menu_icon'          => 'dashicons-briefcase',
        'supports'           => array('title', 'editor', 'custom-fields'),
        'hierarchical'       => false,
        'rewrite'            => array('slug' => 'expert-request'),
        'capability_type'    => 'post',
    );
    register_post_type('expert_request', $args);
}

// Add custom columns to admin list
add_filter('manage_expert_request_posts_columns', 'expert_request_columns');
function expert_request_columns($columns) {
    $columns = array(
        'cb'           => $columns['cb'],
        'title'        => 'Project',
        'name'         => 'Name',
        'email'        => 'Email',
        'phone'        => 'Phone',
        'date'         => 'Date',
    );
    return $columns;
}

// Populate custom columns
add_action('manage_expert_request_posts_custom_column', 'expert_request_column_content', 10, 2);
function expert_request_column_content($column, $post_id) {
    $data = get_post_meta($post_id, '_expert_request_data', true);
    
    switch ($column) {
        case 'name':
            echo esc_html($data['name'] ?? '');
            break;
        case 'email':
            echo '<a href="mailto:' . esc_attr($data['email'] ?? '') . '">' . esc_html($data['email'] ?? '') . '</a>';
            break;
        case 'phone':
            echo esc_html($data['phone'] ?? '');
            break;
    }
}

// Make columns sortable
add_filter('manage_edit-expert_request_sortable_columns', 'expert_request_sortable_columns');
function expert_request_sortable_columns($columns) {
    $columns['name'] = 'name';
    $columns['email'] = 'email';
    return $columns;
}

// AJAX handler for form submission
add_action('wp_ajax_submit_expert_request', 'handle_expert_request_submission');
add_action('wp_ajax_nopriv_submit_expert_request', 'handle_expert_request_submission');

function handle_expert_request_submission() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'expert_request_nonce')) {
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }

    // Sanitize input
    $name = sanitize_text_field($_POST['name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    $form_data = sanitize_text_field($_POST['formData'] ?? '{}');

    // Validate required fields
    if (empty($name) || empty($email) || empty($phone)) {
        wp_send_json_error(['message' => 'All fields are required']);
        return;
    }

    // Validate email
    if (!is_email($email)) {
        wp_send_json_error(['message' => 'Invalid email address']);
        return;
    }

    // Create post
    $post_title = sanitize_text_field($_POST['projectTitle'] ?? 'Expert Request - ' . $name);
    
    $post_id = wp_insert_post(array(
        'post_type'    => 'expert_request',
        'post_title'   => $post_title,
        'post_content' => wp_kses_post(stripslashes($form_data)),
        'post_status'  => 'publish',
    ));

    if (is_wp_error($post_id)) {
        wp_send_json_error(['message' => 'Failed to save request']);
        return;
    }

    // Store metadata
    $request_data = array(
        'name'      => $name,
        'email'     => $email,
        'phone'     => $phone,
        'form_data' => $form_data,
        'submitted' => current_time('mysql'),
    );

    update_post_meta($post_id, '_expert_request_data', $request_data);

    // Send email notifications
    send_expert_request_emails($post_id, $request_data);

    wp_send_json_success([
        'message'  => 'Thank you! Your request has been submitted. We\'ll review it and get back to you soon.',
        'post_id'  => $post_id,
        'redirect' => home_url('/hire-an-expert/?success=1'),
    ]);
}

// Email notification function
function send_expert_request_emails($post_id, $request_data) {
    $to = array('takeitkhan@gmail.com', 'resortfinancing@aol.com');
    $subject = 'New Expert Hire Request - ' . $request_data['name'];
    
    $message = "
    <h2>New Expert Hire Request</h2>
    
    <h3>Submitted By:</h3>
    <ul>
        <li><strong>Name:</strong> " . esc_html($request_data['name']) . "</li>
        <li><strong>Email:</strong> <a href='mailto:" . esc_attr($request_data['email']) . "'>" . esc_html($request_data['email']) . "</a></li>
        <li><strong>Phone:</strong> " . esc_html($request_data['phone']) . "</li>
    </ul>
    
    <h3>Project Details:</h3>
    <div style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>
        " . wp_kses_post($request_data['form_data']) . "
    </div>
    
    <hr>
    
    <p><strong><a href='" . esc_url(admin_url('post.php?post=' . $post_id . '&action=edit')) . "'>View Full Request in WordPress Admin</a></strong></p>
    
    <p style='color: #888; font-size: 12px;'>
        Submitted on: " . esc_html($request_data['submitted']) . "
    </p>
    ";

    $headers = array('Content-Type: text/html; charset=UTF-8');

    // Send to both email addresses
    foreach ($to as $email) {
        wp_mail($email, $subject, $message, $headers);
    }

    // Also send confirmation email to the person who submitted
    $confirmation_subject = 'We Received Your Expert Hire Request';
    $confirmation_message = "
    <h2>Thank You for Your Request</h2>
    
    <p>Hi " . esc_html($request_data['name']) . ",</p>
    
    <p>We've received your request to hire an expert. Our team will review your project details and get back to you shortly at this email address or phone number.</p>
    
    <h3>Your Submission Details:</h3>
    <ul>
        <li><strong>Name:</strong> " . esc_html($request_data['name']) . "</li>
        <li><strong>Email:</strong> " . esc_html($request_data['email']) . "</li>
        <li><strong>Phone:</strong> " . esc_html($request_data['phone']) . "</li>
    </ul>
    
    <p>If you have any questions in the meantime, feel free to reach out to us.</p>
    
    <p>Best regards,<br>The Personal Empowerment Teams</p>
    ";

    wp_mail($request_data['email'], $confirmation_subject, $confirmation_message, $headers);
}

// Enqueue nonce for AJAX
add_action('wp_enqueue_scripts', 'enqueue_expert_request_script');
function enqueue_expert_request_script() {
    if (is_page('hire-an-expert')) {
        wp_localize_script('jquery', 'expertRequestData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('expert_request_nonce'),
        ));
    }
}

// Add custom metabox in admin
add_action('add_meta_boxes', 'add_expert_request_metabox');
function add_expert_request_metabox() {
    add_meta_box(
        'expert_request_details',
        'Request Details',
        'render_expert_request_metabox',
        'expert_request',
        'normal',
        'default'
    );
}

function render_expert_request_metabox($post) {
    $data = get_post_meta($post->ID, '_expert_request_data', true);
    
    if (empty($data)) return;
    
    ?>
    <div style="padding: 10px;">
        <p>
            <strong>Name:</strong> <?php echo esc_html($data['name']); ?><br>
            <strong>Email:</strong> <a href="mailto:<?php echo esc_attr($data['email']); ?>"><?php echo esc_html($data['email']); ?></a><br>
            <strong>Phone:</strong> <?php echo esc_html($data['phone']); ?><br>
            <strong>Submitted:</strong> <?php echo esc_html($data['submitted']); ?>
        </p>
    </div>
    <?php
}

// Auto-create /hire-an-expert page on theme activation
add_action('after_setup_theme', 'create_hire_expert_page_if_missing');
function create_hire_expert_page_if_missing() {
    // Check if page already exists
    $existing_page = get_page_by_path('hire-an-expert');
    
    if (!$existing_page) {
        $page_data = array(
            'post_title'    => 'Hire an Expert',
            'post_name'     => 'hire-an-expert',
            'post_content'  => 'Expert hiring form integrated below.',
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_author'   => 1,
        );
        
        $page_id = wp_insert_post($page_data);
        
        if (!is_wp_error($page_id)) {
            // Set the custom page template
            update_post_meta($page_id, '_wp_page_template', 'template-hire-expert.php');
        }
    }
}
