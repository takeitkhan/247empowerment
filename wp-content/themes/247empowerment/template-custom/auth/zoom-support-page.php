<?php
/**
 * Template Name: Zoom Support Page
 * Template Post Type: page
 * 
 * Zoom Integration Support & Help Center
 */

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url());
    exit;
}

get_header_based_on_login();

$current_user = wp_get_current_user();
$page_slug = basename(get_permalink());
$page_slug = trim($page_slug, '/');

// Dynamic page title mapping
$page_titles = [
    'zoom-help' => 'Zoom Help Center',
    'zoom-support' => 'Zoom Support & Documentation',
    'zoom-faq' => 'Zoom FAQs',
    'help' => 'Need Help?',
];

$page_title = $page_titles[$page_slug] ?? 'Zoom Help Center';
$page_subtitle = get_post_meta(get_the_ID(), '_page_subtitle', true) ?? 'Learn how to get started with Zoom integration';
?>

<div class="pt-4 pb-4 container profile-page">
    <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-lg-3">
            <?php 
            $profile = (new UserProfileData($current_user->ID))->getProfile();
            get_template_part('template-custom/auth/common-parts/editprofilemenu', null, ['profile' => $profile]); 
            ?>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">
            <!-- Header Section -->
            <div class="bg-white mb-4 p-4 border-bottom rounded">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h3 class="mb-2">
                            <i class="bi bi-lightbulb"></i>
                            <?php echo esc_html($page_title); ?>
                        </h3>
                        <p class="mb-0 text-muted">
                            <?php echo esc_html($page_subtitle); ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Help Section -->
            <div class="bg-white p-4 rounded">
                <div class="mb-4 text-center help-header">
                    <h4 class="mb-2">
                        <i class="bi bi-lightbulb" style="color: #ffc107; font-size: 1.75rem;"></i>
                    </h4>
                    <h5 class="fw-600"><?php echo esc_html($page_title); ?></h5>
                    <p class="text-muted small"><?php echo esc_html($page_subtitle); ?></p>
                </div>

                <div class="row g-4">
                    <!-- Quick Links Card -->
                    <div class="col-lg-6">
                        <div class="h-100 help-card">
                            <div class="help-card-header">
                                <i class="bi bi-lightning-fill"></i>
                                Quick Links
                            </div>
                            <div class="help-card-body">
                                <div class="help-step">
                                    <div class="step-content">
                                        <p><a href="<?php echo site_url('/zoom-connect/'); ?>" style="color: #0066cc; text-decoration: none;"><strong>🔗 Connect Your Zoom</strong></a></p>
                                        <p class="text-muted small">Link your Zoom account to the portal</p>
                                    </div>
                                </div>
                                <div class="help-step">
                                    <div class="step-content">
                                        <p><a href="<?php echo site_url('/zoom-meetings/'); ?>" style="color: #0066cc; text-decoration: none;"><strong>📅 View All Meetings</strong></a></p>
                                        <p class="text-muted small">See your upcoming Zoom meetings</p>
                                    </div>
                                </div>
                                <div class="help-step">
                                    <div class="step-content">
                                        <p><a href="<?php echo site_url('/zoom-booking/'); ?>" style="color: #0066cc; text-decoration: none;"><strong>📞 Schedule New Meeting</strong></a></p>
                                        <p class="text-muted small">Create a new Zoom meeting</p>
                                    </div>
                                </div>
                                <div class="help-step">
                                    <div class="step-content">
                                        <p><a href="<?php echo site_url('/zoom-search/'); ?>" style="color: #0066cc; text-decoration: none;"><strong>🔍 Search Meetings</strong></a></p>
                                        <p class="text-muted small">Find specific meetings by topic or ID</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Available Features Card -->
                    <div class="col-lg-6">
                        <div class="h-100 help-card">
                            <div class="help-card-header privacy">
                                <i class="bi bi-tools"></i>
                                Available Features
                            </div>
                            <div class="help-card-body">
                                <div class="privacy-item">
                                    <div class="privacy-icon">
                                        <i class="bi bi-person-check"></i>
                                    </div>
                                    <div class="privacy-content">
                                        <p><strong>Manage Contacts</strong></p>
                                        <p class="text-muted small"><a href="<?php echo site_url('/zoom-contacts/'); ?>" style="color: #0066cc; text-decoration: none;">View your Zoom contacts</a></p>
                                    </div>
                                </div>

                                <div class="privacy-item">
                                    <div class="privacy-icon">
                                        <i class="bi bi-pencil-square"></i>
                                    </div>
                                    <div class="privacy-content">
                                        <p><strong>Reschedule Meetings</strong></p>
                                        <p class="text-muted small"><a href="<?php echo site_url('/zoom-reschedule/'); ?>" style="color: #0066cc; text-decoration: none;">Update meeting times</a></p>
                                    </div>
                                </div>

                                <div class="privacy-item">
                                    <div class="privacy-icon">
                                        <i class="bi bi-trash"></i>
                                    </div>
                                    <div class="privacy-content">
                                        <p><strong>Cancel Meetings</strong></p>
                                        <p class="text-muted small"><a href="<?php echo site_url('/zoom-cancel/'); ?>" style="color: #0066cc; text-decoration: none;">Delete meetings</a></p>
                                    </div>
                                </div>

                                <div class="privacy-item">
                                    <div class="privacy-icon">
                                        <i class="bi bi-key"></i>
                                    </div>
                                    <div class="privacy-content">
                                        <p><strong>Web SDK Token</strong></p>
                                        <p class="text-muted small"><a href="<?php echo site_url('/zoom-zak-token/'); ?>" style="color: #0066cc; text-decoration: none;">Get ZAK token</a></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Page Custom Content (if any) -->
                <?php 
                $post_content = get_the_content();
                if (!empty($post_content)) {
                    echo '<div class="mt-5 pt-4 border-top">';
                    echo apply_filters('the_content', $post_content);
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </div>
</div>

<style>
/* Zoom Help Section Styles */
.help-header {
    padding-bottom: 2rem;
}

.help-header h5 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #212529;
}

/* Help Card Styling */
.help-card {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    display: flex;
    flex-direction: column;
}

.help-card:hover {
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    transform: translateY(-4px);
    border-color: #dee2e6;
}

.help-card-header {
    background: linear-gradient(135deg, #0a66c2 0%, #005b96 100%);
    color: white;
    padding: 1.5rem;
    font-size: 1.1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.help-card-header.privacy {
    background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
}

.help-card-header i {
    font-size: 1.5rem;
}

.help-card-body {
    padding: 1.5rem;
    flex: 1;
}

/* Steps */
.help-step {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    align-items: flex-start;
}

.help-step:last-child {
    margin-bottom: 0;
}

.step-content p {
    margin: 0;
    line-height: 1.5;
}

.step-content p:first-child {
    font-size: 0.95rem;
    font-weight: 500;
    color: #212529;
}

.step-content p:last-child {
    font-size: 0.85rem;
    margin-top: 0.25rem;
}

/* Privacy Items */
.privacy-item {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    align-items: flex-start;
}

.privacy-item:last-child {
    margin-bottom: 0;
}

.privacy-icon {
    flex-shrink: 0;
    width: 44px;
    height: 44px;
    background-color: #e8f5e9;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #28a745;
    font-size: 1.25rem;
    min-width: 44px;
}

.privacy-content p {
    margin: 0;
    line-height: 1.5;
}

.privacy-content p:first-child {
    font-size: 0.95rem;
    font-weight: 500;
    color: #212529;
}

.privacy-content p:last-child {
    font-size: 0.85rem;
    margin-top: 0.25rem;
}

/* Responsive Design */
@media (max-width: 992px) {
    .help-card-header {
        padding: 1.25rem;
        font-size: 1rem;
    }

    .help-card-body {
        padding: 1.25rem;
    }
}

@media (max-width: 768px) {
    .help-header {
        padding-bottom: 1.5rem;
    }

    .help-header h5 {
        font-size: 1.25rem;
    }

    .help-step,
    .privacy-item {
        margin-bottom: 1.25rem;
    }

    .step-content p:first-child,
    .privacy-content p:first-child {
        font-size: 0.9rem;
    }
}
</style>

<?php get_footer_based_on_login(); ?>
