<?php
/**
 * Hire Experts System - Complete Multi-Journey Wizard
 * Stores all form submissions to database with email notifications
 */

// Register Custom Post Type for Expert Requests
add_action('init', 'register_expert_request_post_type');
function register_expert_request_post_type() {
    $args = array(
        'label'              => 'Expert Requests',
        'description'        => 'Submitted requests to hire experts with full journey data',
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
        'services'     => 'Services',
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
            echo esc_html($data['contact']['fullName'] ?? '');
            break;
        case 'email':
            echo esc_html($data['contact']['email'] ?? '');
            break;
        case 'phone':
            echo esc_html($data['contact']['phone'] ?? '');
            break;
        case 'services':
            $goals = $data['goals'] ?? [];
            echo esc_html(implode(', ', $goals));
            break;
    }
}

// AJAX handler for form submission
add_action('wp_ajax_submit_expert_request', 'handle_expert_request_submission');
add_action('wp_ajax_nopriv_submit_expert_request', 'handle_expert_request_submission');
function handle_expert_request_submission() {
    check_ajax_referer('expert_request_nonce', 'nonce');
    
    // Collect all form data from POST
    $formData = array(
        'goals'           => isset($_POST['goals']) ? json_decode(stripslashes($_POST['goals']), true) : [],
        'journeyAnswers'  => isset($_POST['journeyAnswers']) ? json_decode(stripslashes($_POST['journeyAnswers']), true) : [],
        'globalAnswers'   => isset($_POST['globalAnswers']) ? json_decode(stripslashes($_POST['globalAnswers']), true) : [],
        'contact'         => array(
            'fullName'        => sanitize_text_field($_POST['fullName'] ?? ''),
            'projectName'     => sanitize_text_field($_POST['projectName'] ?? ''),
            'company'         => sanitize_text_field($_POST['company'] ?? ''),
            'country'         => sanitize_text_field($_POST['country'] ?? ''),
            'email'           => sanitize_email($_POST['email'] ?? ''),
            'phone'           => sanitize_text_field($_POST['phone'] ?? ''),
            'preferredContact' => sanitize_text_field($_POST['preferredContact'] ?? ''),
            'availability'    => sanitize_text_field($_POST['availability'] ?? ''),
            'notes'           => sanitize_textarea_field($_POST['notes'] ?? ''),
        ),
    );
    
    // Validate required fields
    if (empty($formData['contact']['fullName']) || empty($formData['contact']['email']) || empty($formData['contact']['projectName'])) {
        wp_send_json_error(array('message' => 'Missing required fields'));
        wp_die();
    }
    
    if (!is_email($formData['contact']['email'])) {
        wp_send_json_error(array('message' => 'Invalid email address'));
        wp_die();
    }
    
    // Create CPT post
    $post_id = wp_insert_post(array(
        'post_type'    => 'expert_request',
        'post_title'   => $formData['contact']['projectName'],
        'post_status'  => 'publish',
        'post_author'  => get_current_user_id(),
        'post_content' => 'Expert Request - ' . implode(', ', $formData['goals']),
    ));
    
    if (is_wp_error($post_id)) {
        wp_send_json_error(array('message' => 'Failed to save request'));
        wp_die();
    }
    
    // Store complete data as post meta
    update_post_meta($post_id, '_expert_request_data', $formData);
    
    // Send email notifications
    send_expert_request_emails($formData, $post_id);
    
    // Generate mailto link for backup
    $mailto_link = generate_expert_request_mailto($formData);
    
    wp_send_json_success(array(
        'message' => 'Thank you! Your request has been submitted. Check your email for a copy, and our team will follow up shortly.',
        'redirect' => add_query_arg('success', '1', home_url('/hire-an-expert/')),
        'mailto_link' => $mailto_link,
    ));
    wp_die();
}

// Send email notifications to admin and user
function send_expert_request_emails($formData, $post_id) {
    $contact = $formData['contact'];
    $goals = $formData['goals'] ?? [];
    
    // Email subject
    $subject = 'New Expert Hire Request - ' . $contact['projectName'];
    
    // Build email body with all journey data
    $email_body = build_expert_request_email_body($formData, $post_id);
    
    // Admin emails
    $admin_emails = array('takeitkhan@gmail.com', 'resortfinancing@aol.com');
    $headers = array('Content-Type: text/html; charset=UTF-8');
    
    // Send to admins
    foreach ($admin_emails as $admin_email) {
        wp_mail($admin_email, $subject, $email_body, $headers);
    }
    
    // Send confirmation to submitter
    $confirmation_body = "Thank you, {$contact['fullName']}!\n\n";
    $confirmation_body .= "We've received your expert hire request for: " . implode(', ', $goals) . "\n";
    $confirmation_body .= "Our team will review it and contact you at {$contact['email']} or {$contact['phone']} shortly.\n\n";
    $confirmation_body .= "Project details:\n" . $email_body;
    
    wp_mail($contact['email'], 'Confirmation: Your Expert Request - ' . $subject, $confirmation_body, $headers);
}

// Generate professional email body with all form data
function build_expert_request_email_body($formData, $post_id) {
    $contact = $formData['contact'];
    $goals = $formData['goals'] ?? [];
    $journeyAnswers = $formData['journeyAnswers'] ?? [];
    $globalAnswers = $formData['globalAnswers'] ?? [];
    
    $html = '<h2>Expert Hire Request Details</h2>';
    
    // Contact info
    $html .= '<h3>Contact Information</h3>';
    $html .= '<p><strong>Name:</strong> ' . esc_html($contact['fullName']) . '</p>';
    $html .= '<p><strong>Email:</strong> ' . esc_html($contact['email']) . '</p>';
    if (!empty($contact['phone'])) {
        $html .= '<p><strong>Phone:</strong> ' . esc_html($contact['phone']) . '</p>';
    }
    if (!empty($contact['company'])) {
        $html .= '<p><strong>Company:</strong> ' . esc_html($contact['company']) . '</p>';
    }
    if (!empty($contact['country'])) {
        $html .= '<p><strong>Country:</strong> ' . esc_html($contact['country']) . '</p>';
    }
    if (!empty($contact['preferredContact'])) {
        $html .= '<p><strong>Preferred Contact:</strong> ' . esc_html($contact['preferredContact']) . '</p>';
    }
    if (!empty($contact['availability'])) {
        $html .= '<p><strong>Availability:</strong> ' . esc_html($contact['availability']) . '</p>';
    }
    
    // Services selected
    $html .= '<h3>Services Needed</h3>';
    $html .= '<p>' . esc_html(implode(', ', $goals)) . '</p>';
    
    // Journey-specific answers
    if (!empty($journeyAnswers)) {
        $html .= '<h3>Project Details</h3>';
        foreach ($journeyAnswers as $journey => $answers) {
            $html .= '<h4>' . esc_html(ucwords(str_replace('-', ' ', $journey))) . '</h4>';
            $html .= '<ul>';
            foreach ($answers as $key => $value) {
                if (empty($value)) continue;
                $label = esc_html(ucwords(str_replace('_', ' ', $key)));
                $val = is_array($value) ? implode(', ', $value) : $value;
                $html .= '<li><strong>' . $label . ':</strong> ' . esc_html($val) . '</li>';
            }
            $html .= '</ul>';
        }
    }
    
    // Global answers
    if (!empty($globalAnswers)) {
        $html .= '<h3>Project Scope</h3>';
        if (!empty($globalAnswers['budget'])) {
            $html .= '<p><strong>Budget:</strong> ' . esc_html($globalAnswers['budget']) . '</p>';
        }
        if (!empty($globalAnswers['timeline'])) {
            $html .= '<p><strong>Timeline:</strong> ' . esc_html($globalAnswers['timeline']) . '</p>';
        }
        if (!empty($globalAnswers['combinedNotes'])) {
            $html .= '<p><strong>Notes:</strong> ' . nl2br(esc_html($globalAnswers['combinedNotes'])) . '</p>';
        }
    }
    
    // Additional notes
    if (!empty($contact['notes'])) {
        $html .= '<h3>Additional Notes</h3>';
        $html .= '<p>' . nl2br(esc_html($contact['notes'])) . '</p>';
    }
    
    // Admin link
    $admin_link = admin_url('post.php?post=' . $post_id . '&action=edit');
    $html .= '<hr>';
    $html .= '<p><a href="' . esc_url($admin_link) . '">View in Admin</a></p>';
    
    return $html;
}

// Generate mailto link as backup (client-side will use this)
function generate_expert_request_mailto($formData) {
    $contact = $formData['contact'];
    $goals = $formData['goals'] ?? [];
    
    $subject = 'New project inquiry — ' . $contact['projectName'];
    
    $lines = array();
    $lines[] = 'Full name: ' . $contact['fullName'];
    $lines[] = 'Project name: ' . $contact['projectName'];
    if (!empty($contact['company'])) $lines[] = 'Company: ' . $contact['company'];
    if (!empty($contact['country'])) $lines[] = 'Country: ' . $contact['country'];
    $lines[] = 'Email: ' . $contact['email'];
    if (!empty($contact['phone'])) $lines[] = 'Phone: ' . $contact['phone'];
    $lines[] = '';
    $lines[] = 'Services: ' . implode(', ', $goals);
    
    // Journey details
    if (!empty($formData['journeyAnswers'])) {
        foreach ($formData['journeyAnswers'] as $journey => $answers) {
            $lines[] = '';
            $lines[] = '— ' . ucwords(str_replace('-', ' ', $journey)) . ' —';
            foreach ($answers as $key => $value) {
                if (empty($value)) continue;
                $label = ucwords(str_replace('_', ' ', $key));
                $val = is_array($value) ? implode(', ', $value) : $value;
                $lines[] = $label . ': ' . $val;
            }
        }
    }
    
    // Global answers
    if (!empty($formData['globalAnswers'])) {
        $lines[] = '';
        if (!empty($formData['globalAnswers']['budget'])) {
            $lines[] = 'Overall budget: ' . $formData['globalAnswers']['budget'];
        }
        if (!empty($formData['globalAnswers']['timeline'])) {
            $lines[] = 'Timeline: ' . $formData['globalAnswers']['timeline'];
        }
    }
    
    if (!empty($contact['notes'])) {
        $lines[] = '';
        $lines[] = 'Notes: ' . $contact['notes'];
    }
    
    $lines[] = '';
    $lines[] = 'Submitted: ' . current_time('mysql');
    
    return 'mailto:takeitkhan@gmail.com?cc=resortfinancing@aol.com&subject=' . urlencode($subject) . '&body=' . urlencode(implode("\n", $lines));
}

// Admin metabox
add_action('add_meta_boxes', 'add_expert_request_metabox');
function add_expert_request_metabox() {
    add_meta_box('expert_request_details', 'Request Details', 'render_expert_request_metabox', 'expert_request', 'normal', 'default');
}

function render_expert_request_metabox($post) {
    $data = get_post_meta($post->ID, '_expert_request_data', true);
    
    if (empty($data)) return;
    
    $contact = $data['contact'] ?? array();
    $goals = $data['goals'] ?? array();
    $journey_answers = $data['journeyAnswers'] ?? array();
    $global_answers = $data['globalAnswers'] ?? array();
    
    ?>
    <div style="padding: 10px;">
        <h4>Contact Information</h4>
        <p>
            <strong>Name:</strong> <?php echo esc_html($contact['fullName'] ?? ''); ?><br>
            <strong>Email:</strong> <a href="mailto:<?php echo esc_attr($contact['email'] ?? ''); ?>"><?php echo esc_html($contact['email'] ?? ''); ?></a><br>
            <strong>Phone:</strong> <?php echo esc_html($contact['phone'] ?? ''); ?><br>
            <strong>Company:</strong> <?php echo esc_html($contact['company'] ?? ''); ?><br>
            <strong>Country:</strong> <?php echo esc_html($contact['country'] ?? ''); ?><br>
            <strong>Availability:</strong> <?php echo esc_html($contact['availability'] ?? ''); ?><br>
            <strong>Preferred Contact:</strong> <?php echo esc_html($contact['preferredContact'] ?? ''); ?>
        </p>
        
        <h4>Services</h4>
        <p><?php echo esc_html(implode(', ', $goals)); ?></p>
        
        <h4>Journey-Specific Details</h4>
        <?php foreach ($journey_answers as $journey => $answers): ?>
            <h5><?php echo esc_html(ucwords(str_replace('-', ' ', $journey))); ?></h5>
            <ul>
                <?php foreach ($answers as $key => $value): 
                    if (empty($value)) continue;
                    $label = ucwords(str_replace('_', ' ', $key));
                    $val = is_array($value) ? implode(', ', $value) : $value;
                ?>
                    <li><strong><?php echo esc_html($label); ?>:</strong> <?php echo esc_html($val); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
        
        <?php if (!empty($global_answers)): ?>
            <h4>Global Details</h4>
            <p>
                <strong>Budget:</strong> <?php echo esc_html($global_answers['budget'] ?? ''); ?><br>
                <strong>Timeline:</strong> <?php echo esc_html($global_answers['timeline'] ?? ''); ?><br>
                <strong>Notes:</strong> <?php echo nl2br(esc_html($global_answers['combinedNotes'] ?? '')); ?>
            </p>
        <?php endif; ?>
        
        <?php if (!empty($contact['notes'])): ?>
            <h4>Additional Notes</h4>
            <p><?php echo nl2br(esc_html($contact['notes'])); ?></p>
        <?php endif; ?>
    </div>
    <?php
}

// Enqueue nonce for AJAX
add_action('wp_enqueue_scripts', 'enqueue_expert_request_nonce');
function enqueue_expert_request_nonce() {
    if (is_page('hire-an-expert')) {
        wp_localize_script('jquery', 'expertRequestData', array(
            'nonce' => wp_create_nonce('expert_request_nonce'),
            'ajaxurl' => admin_url('admin-ajax.php'),
        ));
    }
}

// Auto-create /hire-an-expert page on theme activation
add_action('after_setup_theme', 'create_hire_expert_page_if_missing');
function create_hire_expert_page_if_missing() {
    $existing_page = get_page_by_path('hire-an-expert');
    
    if (!$existing_page) {
        $page_data = array(
            'post_title'    => 'Hire an Expert',
            'post_name'     => 'hire-an-expert',
            'post_content'  => 'Expert hiring intake form.',
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_author'   => 1,
        );
        
        $page_id = wp_insert_post($page_data);
        
        if (!is_wp_error($page_id)) {
            update_post_meta($page_id, '_wp_page_template', 'template-hire-expert.php');
        }
    }
}
