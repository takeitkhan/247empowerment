<?php
/**
 * Plugin Name: MM Referral Chat
 * Description: Chat system for users connected via referral relationships
 * Version: 1.0.7
 * Author: MM Team
 * License: MIT
 * Text Domain: mm-referral-chat
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // No direct access
}

// Define plugin constants
define('MM_REFERRAL_CHAT_PATH', plugin_dir_path(__FILE__));
define('MM_REFERRAL_CHAT_URL', plugin_dir_url(__FILE__));
define('MM_REFERRAL_CHAT_VERSION', '1.0.7');

// Include required files
require_once MM_REFERRAL_CHAT_PATH . 'includes/class-chat-database.php';
require_once MM_REFERRAL_CHAT_PATH . 'includes/class-chat-manager.php';
require_once MM_REFERRAL_CHAT_PATH . 'includes/class-message-handler.php';
require_once MM_REFERRAL_CHAT_PATH . 'includes/class-chat-ajax.php';

/**
 * Main Plugin Class
 */
class MM_Referral_Chat
{
    private static $instance = null;

    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        // Plugin activation
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        // Initialize on WordPress load
        add_action('wp_loaded', [$this, 'init']);
    }

    /**
     * Plugin activation - Create database tables
     */
    public function activate()
    {
        try {
            MM_Chat_Database::create_tables();
            error_log('MM Chat: Plugin activated successfully');
        } catch (Exception $e) {
            error_log('MM Chat: Activation error - ' . $e->getMessage());
        }
    }

    /**
     * Plugin deactivation
     */
    public function deactivate()
    {
        // Cleanup if needed
    }

    /**
     * Initialize plugin
     */
    public function init()
    {
        // Load text domain
        load_plugin_textdomain('mm-referral-chat', false, dirname(plugin_basename(__FILE__)) . '/languages');

        // Initialize classes
        if (!is_admin()) {
            $this->init_frontend();
        }

        // Initialize AJAX handlers
        MM_Chat_AJAX::init();
    }

    /**
     * Initialize frontend
     */
    private function init_frontend()
    {
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets'], 20);

        // Add chat to footer
        add_action('wp_footer', [$this, 'render_chat_interface']);
        add_action('wp_footer', [$this, 'ensure_ai_chat_widget'], 15);
    }

    /**
     * Force mm-ai-chat widget HTML + assets when plugin is present
     */
    public function ensure_ai_chat_widget()
    {
        if (!is_user_logged_in()) {
            return;
        }

        $enabled = get_option('mm_ai_chat_enabled');
        if ($enabled === false || $enabled === '' || $enabled === '0') {
            return;
        }

        $ai_dir = WP_PLUGIN_DIR . '/mm-ai-chat';
        if (!is_dir($ai_dir)) {
            return;
        }

        if (!defined('MM_AI_CHAT_PLUGIN_URL')) {
            define('MM_AI_CHAT_PLUGIN_URL', plugins_url('/', $ai_dir . '/mm-ai-chat.php'));
        }
        if (!defined('MM_AI_CHAT_VERSION')) {
            define('MM_AI_CHAT_VERSION', '1.0.1');
        }

        if (!class_exists('MM_AI_Chat_Widget')) {
            require_once $ai_dir . '/public/class-widget.php';
        }

        // mm-ai-chat plugin registers its own wp_footer render; only render if missing
        if (!has_action('wp_footer', ['MM_AI_Chat_Widget', 'render_widget'])) {
            MM_AI_Chat_Widget::render_widget();
        }
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_assets()
    {
        if (!is_user_logged_in()) {
            return;
        }

        // Chat CSS
        wp_enqueue_style(
            'mm-referral-chat-styles',
            MM_REFERRAL_CHAT_URL . 'assets/css/chat-styles.css',
            [],
            MM_REFERRAL_CHAT_VERSION
        );

        // Ensure mm-ai-chat widget assets load (purple bubble)
        $script_deps = ['jquery'];
        $ai_enabled  = (bool) get_option('mm_ai_chat_enabled');
        $ai_dir      = WP_PLUGIN_DIR . '/mm-ai-chat';

        if ($ai_enabled && is_dir($ai_dir)) {
            if (!defined('MM_AI_CHAT_PLUGIN_URL')) {
                define('MM_AI_CHAT_PLUGIN_URL', plugins_url('/', $ai_dir . '/mm-ai-chat.php'));
            }
            if (!defined('MM_AI_CHAT_VERSION')) {
                define('MM_AI_CHAT_VERSION', '1.0.1');
            }

            $js_path = $ai_dir . '/public/assets/js/chat-widget.js';
            $js_ver  = file_exists($js_path) ? (string) filemtime($js_path) : MM_AI_CHAT_VERSION;

            if (!class_exists('MM_AI_Chat_Enqueue')) {
                require_once $ai_dir . '/public/class-enqueue.php';
            }
            if (class_exists('MM_AI_Chat_Enqueue')) {
                MM_AI_Chat_Enqueue::enqueue_styles();
                MM_AI_Chat_Enqueue::enqueue_scripts();
            } else {
                wp_enqueue_style(
                    'mm-ai-chat-widget',
                    MM_AI_CHAT_PLUGIN_URL . 'public/assets/css/chat-widget.css',
                    [],
                    MM_AI_CHAT_VERSION
                );
                wp_enqueue_script(
                    'mm-ai-chat-widget',
                    MM_AI_CHAT_PLUGIN_URL . 'public/assets/js/chat-widget.js',
                    ['jquery'],
                    $js_ver,
                    true
                );
                wp_localize_script('mm-ai-chat-widget', 'mmAiChatData', [
                    'restUrl'           => rest_url('mm-ai-chat/v1'),
                    'nonce'             => wp_create_nonce('wp_rest'),
                    'chatNonce'         => wp_create_nonce('mm_ai_chat_nonce'),
                    'userId'            => get_current_user_id(),
                    'escalationEnabled' => get_option('mm_ai_chat_escalation_enabled'),
                    'offlineEnabled'    => get_option('mm_ai_chat_offline_questions_enabled'),
                ]);
            }

            if (wp_script_is('mm-ai-chat-widget', 'registered') || wp_script_is('mm-ai-chat-widget', 'enqueued')) {
                $script_deps[] = 'mm-ai-chat-widget';
            }
        }

        // Chat JS
        wp_enqueue_script(
            'mm-referral-chat-script',
            MM_REFERRAL_CHAT_URL . 'assets/js/chat-interface.js',
            $script_deps,
            MM_REFERRAL_CHAT_VERSION,
            true
        );

        // Get partners data to pass to frontend
        $current_user_id = get_current_user_id();
        $partners = MM_Chat_Manager::get_chat_partners($current_user_id, 50);
        
        $formatted_partners = [];
        foreach ($partners as $partner) {
            if (!$partner || !($partner instanceof WP_User)) {
                continue;
            }
            
            // Skip current user
            if ($partner->ID === $current_user_id) {
                continue;
            }
            
            $formatted_partners[] = [
                'id' => $partner->ID,
                'name' => $partner->display_name,
                'username' => $partner->user_login,
                'avatar' => get_user_meta($partner->ID, 'profile_photo', true),
                'profile_url' => home_url('/' . $partner->user_login),
            ];
        }

        // Localize script
        wp_localize_script('mm-referral-chat-script', 'mmChat', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'currentUserId' => $current_user_id,
            'nonce' => wp_create_nonce('mm_chat_nonce'),
            'pollingInterval' => 5000, // 5 seconds (increased from 3 to reduce blinking)
            'partners' => $formatted_partners, // Pre-loaded partners
            'aiChatUrl' => home_url('/chat-with-ai/'),
            'aiChatEnabled' => $ai_enabled,
            'aiRestUrl' => $ai_enabled ? rest_url('mm-ai-chat/v1') : '',
            'aiRestNonce' => $ai_enabled ? wp_create_nonce('wp_rest') : '',
            'aiWidgetScript' => ($ai_enabled && is_dir($ai_dir))
                ? plugins_url('mm-ai-chat/public/assets/js/chat-widget.js')
                : '',
            'chatRoomUrl' => home_url('/chat-room/'),
            'aiEscalationEnabled' => (bool) get_option('mm_ai_chat_escalation_enabled'),
            'aiOfflineEnabled' => (bool) get_option('mm_ai_chat_offline_questions_enabled'),
            'userEmail' => wp_get_current_user()->user_email ?? '',
            'userName' => wp_get_current_user()->display_name ?? '',
        ]);
    }

    /**
     * Render chat interface in footer
     */
    public function render_chat_interface()
    {
        if (!is_user_logged_in()) {
            return;
        }

        include MM_REFERRAL_CHAT_PATH . 'templates/chat-interface.php';
    }
}

// Boot plugin
MM_Referral_Chat::get_instance();
