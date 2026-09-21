<?php
/**
 * Template Name: Guides
 * Template Post Type: page
 * 
 * User Guides Page Template
 * 
 * Displays educational guides for users with tab-based navigation
 * 
 * @package 247empowerment
 */

if (!is_user_logged_in()) {
    wp_redirect(home_url('/signin'));
    exit;
}

get_header_based_on_login();

// Get current section from query parameter
$current_section = isset($_GET['section']) ? sanitize_text_field($_GET['section']) : 'collaboration';

// Define available sections
$sections = [
    'collaboration' => [
        'title' => '🎬 Collaboration Features',
        'icon' => '👥',
        'description' => 'Learn how to create, join, and manage meetings effectively'
    ],
    'streaming' => [
        'title' => '📺 Meeting Streaming',
        'icon' => '📡',
        'description' => 'Stream your meetings to YouTube and reach a wider audience'
    ],
];

// Validate section exists
if (!isset($sections[$current_section])) {
    $current_section = 'collaboration';
}
?>

<div class="guides-page-wrapper">
    <div class="guides-page-layout">
        
        <!-- Left Sidebar Navigation -->
        <div class="guides-sidebar">
            <aside class="guides-sidebar-card">
                <div class="guides-sidebar-header">
                    <h5>📚 User Guides</h5>
                    <p>Learn how to make the most of our platform</p>
                </div>
                
                <nav class="guides-nav-list">
                    <?php foreach ($sections as $section_key => $section_info) : 
                        $is_active = $current_section === $section_key;
                        $section_url = add_query_arg('section', $section_key, get_permalink());
                    ?>
                        <a href="<?php echo esc_url($section_url); ?>" 
                            class="guides-nav-item <?php echo $is_active ? 'active' : ''; ?>"
                            title="<?php echo esc_attr($section_info['description']); ?>">
                            <span class="guides-nav-icon"><?php echo $section_info['icon']; ?></span>
                            <span class="guides-nav-label"><?php echo esc_html($section_info['title']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </aside>
        </div>

        <!-- Main Content Area -->
        <main class="guides-content">
            
            <!-- Section Header -->
            <div class="guides-header">
                <h1><?php echo esc_html($sections[$current_section]['title']); ?></h1>
                <p class="guides-subtitle"><?php echo esc_html($sections[$current_section]['description']); ?></p>
            </div>

            <!-- Section Content -->
            <div class="guides-body">
                <?php
                // Display section content based on current section
                if ($current_section === 'collaboration') {
                    include get_template_directory() . '/template-custom/auth/guides-parts/section-collaboration.php';
                } elseif ($current_section === 'streaming') {
                    include get_template_directory() . '/template-custom/auth/guides-parts/section-streaming.php';
                } else {
                    ?>
                    <div class="guides-placeholder">
                        <p>Content coming soon. Please check back later.</p>
                    </div>
                    <?php
                }
                ?>
            </div>

        </main>

    </div>
</div>

<style>
.guides-page-wrapper {
    background-color: #f8fafc;
    min-height: 100vh;
    padding: 20px;
}

.guides-page-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 30px;
    max-width: 1400px;
    margin: 0 auto;
}

/* Sidebar */
.guides-sidebar {
    position: sticky;
    top: 20px;
    height: fit-content;
}

.guides-sidebar-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

.guides-sidebar-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-bottom: 2px solid rgba(0, 0, 0, 0.1);
}

.guides-sidebar-header h5 {
    margin: 0 0 5px 0;
    font-size: 18px;
    font-weight: 600;
}

.guides-sidebar-header p {
    margin: 0;
    font-size: 13px;
    opacity: 0.9;
}

.guides-nav-list {
    display: flex;
    flex-direction: column;
    padding: 10px;
}

.guides-nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    text-decoration: none;
    color: #334155;
    border-radius: 8px;
    transition: all 0.2s ease;
    font-size: 14px;
}

.guides-nav-item:hover {
    background-color: #f1f5f9;
    color: #667eea;
}

.guides-nav-item.active {
    background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
    color: #667eea;
    font-weight: 600;
    border-left: 3px solid #667eea;
    padding-left: 13px;
}

.guides-nav-icon {
    font-size: 18px;
    min-width: 24px;
}

.guides-nav-label {
    flex: 1;
}

/* Main Content */
.guides-content {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    padding: 40px;
}

.guides-header {
    margin-bottom: 40px;
    padding-bottom: 30px;
    border-bottom: 2px solid #e2e8f0;
}

.guides-header h1 {
    margin: 0 0 10px 0;
    font-size: 32px;
    color: #1e293b;
}

.guides-subtitle {
    margin: 0;
    font-size: 16px;
    color: #64748b;
}

.guides-body {
    margin-bottom: 40px;
}

.guides-body h2 {
    font-size: 24px;
    color: #1e293b;
    margin-top: 30px;
    margin-bottom: 15px;
}

.guides-body h3 {
    font-size: 18px;
    color: #334155;
    margin-top: 25px;
    margin-bottom: 12px;
}

.guides-body p {
    color: #475569;
    line-height: 1.7;
    margin-bottom: 15px;
}

.guides-body ul, .guides-body ol {
    color: #475569;
    margin-bottom: 20px;
    padding-left: 25px;
}

.guides-body li {
    margin-bottom: 10px;
    line-height: 1.6;
}

.guides-body strong {
    color: #1e293b;
    font-weight: 600;
}

.guides-body code {
    background: #f1f5f9;
    padding: 2px 6px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    color: #667eea;
    font-size: 14px;
}

.guides-body pre {
    background: #1e293b;
    color: #e2e8f0;
    padding: 20px;
    border-radius: 8px;
    overflow-x: auto;
    margin-bottom: 20px;
    font-size: 14px;
}

.guides-body img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 20px 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.guides-info-box {
    background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%);
    border-left: 4px solid #2563eb;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
}

.guides-info-box strong {
    color: #1e40af;
}

.guides-warning-box {
    background: linear-gradient(135deg, #fef3c7 0%, #fed7aa 100%);
    border-left: 4px solid #f59e0b;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
}

.guides-success-box {
    background: linear-gradient(135deg, #dcfce7 0%, #d1fae5 100%);
    border-left: 4px solid #22c55e;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
}

.guides-step {
    display: flex;
    gap: 20px;
    margin: 20px 0;
    padding: 20px;
    background: #f8fafc;
    border-radius: 8px;
}

.guides-step-number {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 50%;
    font-weight: 600;
    flex-shrink: 0;
}

.guides-step-content {
    flex: 1;
}

.guides-step-content h4 {
    margin: 0 0 8px 0;
    color: #1e293b;
    font-size: 16px;
}

.guides-step-content p {
    margin: 0;
    color: #475569;
    font-size: 14px;
}

/* Related Guides */
.guides-related {
    margin-top: 40px;
    padding-top: 30px;
    border-top: 2px solid #e2e8f0;
}

.guides-related h3 {
    margin-top: 0;
}

.guides-related-items {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.guides-related-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 15px;
    background: #f8fafc;
    border-radius: 8px;
    text-decoration: none;
    color: #334155;
    transition: all 0.2s ease;
    border: 1px solid #e2e8f0;
}

.guides-related-item:hover {
    background: linear-gradient(135deg, #667eea10 0%, #764ba210 100%);
    color: #667eea;
    border-color: #667eea;
}

.guides-related-icon {
    font-size: 24px;
}

.guides-related-title {
    flex: 1;
    font-size: 14px;
    font-weight: 600;
}

.guides-related-arrow {
    color: #cbd5e1;
    transition: all 0.2s ease;
}

.guides-related-item:hover .guides-related-arrow {
    color: #667eea;
}

/* Support CTA */
.guides-support-cta {
    margin-top: 40px;
    padding: 25px;
    background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%);
    border-left: 4px solid #667eea;
    border-radius: 8px;
    text-align: center;
}

.guides-support-cta h3 {
    margin-top: 0;
    color: #7c3aed;
}

.guides-support-cta p {
    margin: 0;
    color: #6b21a8;
}

.guides-support-cta a {
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
}

.guides-support-cta a:hover {
    text-decoration: underline;
}

.guides-placeholder {
    text-align: center;
    padding: 40px;
    color: #94a3b8;
    font-style: italic;
}

/* Responsive */
@media (max-width: 768px) {
    .guides-page-layout {
        grid-template-columns: 1fr;
        gap: 20px;
    }

    .guides-sidebar {
        position: static;
    }

    .guides-content {
        padding: 25px;
    }

    .guides-header h1 {
        font-size: 24px;
    }

    .guides-related-items {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
get_footer_based_on_login();
?>
