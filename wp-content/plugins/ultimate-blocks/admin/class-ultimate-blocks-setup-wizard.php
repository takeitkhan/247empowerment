<?php
/**
 * Setup Wizard for Ultimate Blocks.
 *
 * @package ultimate-blocks
 */

namespace Ultimate_Blocks\admin;

/**
 * Manages the first-run setup wizard page.
 */
class Setup_Wizard {

	/**
	 * Admin page slug.
	 */
	const PAGE_SLUG = 'ub-setup-wizard';

	/**
	 * Option key that marks the wizard as completed.
	 */
	const COMPLETED_OPTION = 'ultimate_blocks_setup_wizard_completed';

	/**
	 * Register all WordPress hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_redirect' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'wp_ajax_ub_wizard_complete', array( __CLASS__, 'handle_complete' ) );
		add_action( 'wp_ajax_ub_wizard_install_plugin', array( __CLASS__, 'handle_install_plugin' ) );
		add_action( 'wp_ajax_ub_wizard_toggle_blocks', array( __CLASS__, 'handle_toggle_blocks' ) );
		add_action( 'wp_ajax_ub_wizard_fs_skip', array( __CLASS__, 'handle_fs_skip' ) );
		add_action( 'wp_ajax_ub_wizard_fs_optin', array( __CLASS__, 'handle_fs_optin' ) );
	}

	/**
	 * Register a hidden admin page for the wizard.
	 * Using null parent hides it from the sidebar but keeps it accessible.
	 *
	 * @return void
	 */
	public static function register_page() {
		add_submenu_page(
			'_',
			__( 'Ultimate Blocks Setup', 'ultimate-blocks' ),
			__( 'Ultimate Blocks Setup', 'ultimate-blocks' ),
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Redirect new installs to the wizard on first admin load.
	 *
	 * @return void
	 */
	public static function maybe_redirect() {
		// Skip during bulk activation, AJAX, WP-CLI, or if already completed.
		if (
			isset( $_GET['activate-multi'] ) ||
			wp_doing_ajax() ||
			( defined( 'WP_CLI' ) && WP_CLI ) ||
			get_option( self::COMPLETED_OPTION )
		) {
			return;
		}

		if ( get_transient( '_welcome_redirect_ub' ) ) {
			delete_transient( '_welcome_redirect_ub' );
			wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
			exit;
		}
	}

	/**
	 * Enqueue wizard styles and scripts on the wizard page only.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public static function enqueue_assets( $hook ) {
		if ( strpos( $hook, self::PAGE_SLUG ) === false ) {
			return;
		}

		// Remove default WordPress notices that would appear behind the wizard.
		remove_action( 'admin_notices', 'update_nag', 3 );

		wp_enqueue_style(
			'ub-setup-wizard',
			trailingslashit( ULTIMATE_BLOCKS_URL ) . 'dist/setup-wizard.css',
			array(),
			ULTIMATE_BLOCKS_VERSION
		);

		// Dependencies and version are generated at build time so the wizard
		// always loads against the React instance WordPress provides.
		$wizard_asset_path = ULTIMATE_BLOCKS_PATH . 'dist/setup-wizard.build.asset.php';
		$wizard_asset      = file_exists( $wizard_asset_path )
			? require $wizard_asset_path
			: array(
				'dependencies' => array( 'react', 'wp-element' ),
				'version'      => ULTIMATE_BLOCKS_VERSION,
			);

		wp_enqueue_script(
			'ub-setup-wizard',
			trailingslashit( ULTIMATE_BLOCKS_URL ) . 'dist/setup-wizard.build.js',
			$wizard_asset['dependencies'],
			$wizard_asset['version'],
			true
		);

		$blocks       = get_option( 'ultimate_blocks', array() );
		$initial_step = isset( $_GET['step'] ) ? (int) $_GET['step'] : 1; // phpcs:ignore WordPress.Security.NonceVerification

		wp_localize_script(
			'ub-setup-wizard',
			'ubSetupWizardData',
			array(
				'blocks'      => $blocks,
				'initialStep' => $initial_step,
				'nonces'      => array(
					'toggleBlocks'  => wp_create_nonce( 'ub_wizard_toggle_blocks' ),
					'complete'      => wp_create_nonce( 'ub_wizard_complete' ),
					'installPlugin' => wp_create_nonce( 'ub_wizard_install_plugin' ),
					'fsSkip'        => wp_create_nonce( 'ub_wizard_fs_skip' ),
					'fsOptin'       => wp_create_nonce( 'ub_wizard_fs_optin' ),
				),
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'settingsUrl' => admin_url( 'admin.php?page=ultimate-blocks-settings' ),
				'logoUrl'     => trailingslashit( ULTIMATE_BLOCKS_URL ) . 'admin/images/logos/menu-icon-colored.svg',
				'freemius'    => self::get_freemius_optin_data(),
			)
		);
	}

	/**
	 * Build Freemius opt-in data for the wizard's newsletter step.
	 *
	 * Exposes the form action URL, all hidden field values (for new users posted
	 * to freemius.com), or the WP nonce (for returning users posted locally),
	 * plus the skip URL.  The React component uses this to render real Freemius
	 * forms rather than a custom newsletter form.
	 *
	 * @return array
	 */
	private static function get_freemius_optin_data() {
		$ub_fs = \Ultimate_Blocks\includes\pro_manager\Pro_Manager::init_freemius();

		$wizard_url     = admin_url( 'admin.php?page=' . self::PAGE_SLUG );
		$wizard_step4   = add_query_arg( 'step', '4', $wizard_url );
		$unique_affix   = $ub_fs->get_unique_affix();

		// URL used by the Skip link – Freemius checks the nonce in the URL.
		$skip_url = wp_nonce_url(
			add_query_arg(
				array(
					'fs_action' => $unique_affix . '_skip_activation',
				),
				$wizard_url
			),
			$unique_affix . '_skip_activation'
		);

		$result = array(
			'isConnected'             => $ub_fs->is_registered() || $ub_fs->is_anonymous(),
			'uniqueAffix'             => $unique_affix,
			'skipUrl'                 => $skip_url,
			'activateWithCurrentUser' => false,
		);

		if ( $result['isConnected'] ) {
			return $result;
		}

		$current_wp_user = wp_get_current_user();
		$fs_user         = \Freemius::_get_user_by_email( $current_wp_user->user_email );

		$activate_with_current_user = is_object( $fs_user );
		$result['activateWithCurrentUser'] = $activate_with_current_user;

		if ( $activate_with_current_user ) {
			// Existing Freemius user: POST to the local admin page with the
			// _activate_existing action, which Freemius processes on page load.
			$result['activateExistingNonce']  = wp_create_nonce( $unique_affix . '_activate_existing' );
			$result['activateExistingAction'] = $unique_affix . '_activate_existing';
			$result['activateFormUrl']        = $wizard_step4;
		} else {
			// New Freemius user: POST to freemius.com with opt-in params.
			// The return_url points back to the wizard at step 4 so Freemius
			// redirects here after creating the account.  Freemius' own
			// _install_with_new_user() hook (runs on admin_init) finalises the
			// install when it sees the fs_action=_activate_new param in the URL.
			$return_url   = add_query_arg(
				array(
					'fs_action' => $unique_affix . '_activate_new',
					'step'      => '4',
				),
				$wizard_url
			);

			$optin_params                             = $ub_fs->get_opt_in_params(
				array( 'return_url' => fs_nonce_url( $return_url, $unique_affix . '_activate_new' ) )
			);
			$optin_params['is_extensions_tracking_allowed'] = '1';
			$optin_params['is_diagnostic_tracking_allowed'] = '1';

			$result['optInFormAction'] = defined( 'WP_FS__ADDRESS' ) ? WP_FS__ADDRESS . '/action/service/user/install/' : 'https://freemius.com/action/service/user/install/';
			$result['optInParams']     = $optin_params;
		}

		return $result;
	}

	/**
	 * Render the wizard page markup.
	 *
	 * @return void
	 */
	public static function render_page() {
		require_once ULTIMATE_BLOCKS_PATH . 'admin/templates/setup-wizard.php';
	}

	/**
	 * AJAX: Save bulk block toggle selections made in Step 1.
	 *
	 * @return void
	 */
	public static function handle_toggle_blocks() {
		check_ajax_referer( 'ub_wizard_toggle_blocks', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		$block_statuses = isset( $_POST['blocks'] ) ? (array) $_POST['blocks'] : array();

		$current_blocks = get_option( 'ultimate_blocks', array() );

		foreach ( $current_blocks as &$block ) {
			$name = $block['name'];
			if ( array_key_exists( $name, $block_statuses ) ) {
				$block['active'] = (bool) $block_statuses[ $name ];
			}
		}
		unset( $block );

		update_option( 'ultimate_blocks', $current_blocks );

		wp_send_json_success();
	}

	/**
	 * AJAX: Mark the wizard as completed.
	 *
	 * @return void
	 */
	public static function handle_complete() {
		check_ajax_referer( 'ub_wizard_complete', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		update_option( self::COMPLETED_OPTION, true );
		wp_send_json_success();
	}

	/**
	 * AJAX: Complete the Freemius opt-in without a full page reload.
	 *
	 * Uses Freemius::opt_in() server-side (same API as the hidden forms).
	 *
	 * @return void
	 */
	public static function handle_fs_optin() {
		check_ajax_referer( 'ub_wizard_fs_optin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		$ub_fs = \Ultimate_Blocks\includes\pro_manager\Pro_Manager::init_freemius();

		if ( $ub_fs->is_registered() || $ub_fs->is_anonymous() ) {
			wp_send_json_success();
		}

		\FS_Permission_Manager::instance( $ub_fs )->update_permissions_tracking_flag(
			array(
				\FS_Permission_Manager::PERMISSION_DIAGNOSTIC  => true,
				\FS_Permission_Manager::PERMISSION_EXTENSIONS => true,
			)
		);

		$result = $ub_fs->opt_in(
			false,
			false,
			false,
			false,
			false,
			false,
			false,
			null,
			array(),
			false
		);

		if ( is_object( $result ) && isset( $result->error ) ) {
			$message = ( is_object( $result->error ) && ! empty( $result->error->message ) )
				? $result->error->message
				: __( 'Failed to connect to Freemius.', 'ultimate-blocks' );
			wp_send_json_error( array( 'message' => $message ) );
		}

		if ( false === $result ) {
			wp_send_json_error(
				array(
					'message' => __( 'Failed to connect to Freemius.', 'ultimate-blocks' ),
				)
			);
		}

		wp_send_json_success();
	}

	/**
	 * AJAX: Call Freemius skip_connection() so the opt-in is dismissed
	 * without requiring a full page reload.
	 *
	 * @return void
	 */
	public static function handle_fs_skip() {
		check_ajax_referer( 'ub_wizard_fs_skip', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		$ub_fs = \Ultimate_Blocks\includes\pro_manager\Pro_Manager::init_freemius();

		if ( ! $ub_fs->is_registered() && ! $ub_fs->is_anonymous() ) {
			$ub_fs->skip_connection();
		}

		wp_send_json_success();
	}

	/**
	 * AJAX: Install and activate a companion plugin from wordpress.org.
	 *
	 * @return void
	 */
	public static function handle_install_plugin() {
		check_ajax_referer( 'ub_wizard_install_plugin', 'nonce' );

		if ( ! current_user_can( 'install_plugins' ) || ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', 'ultimate-blocks' ) );
		}

		$plugin_slug = isset( $_POST['plugin_slug'] ) ? sanitize_key( wp_unslash( $_POST['plugin_slug'] ) ) : '';

		if ( empty( $plugin_slug ) ) {
			wp_send_json_error( __( 'Invalid plugin slug.', 'ultimate-blocks' ) );
		}

		// Check if already installed.
		$installed_plugins = get_plugins();
		foreach ( $installed_plugins as $plugin_file => $plugin_data ) {
			if ( strpos( $plugin_file, $plugin_slug . '/' ) === 0 ) {
				activate_plugin( $plugin_file );
				wp_send_json_success( array( 'status' => 'activated' ) );
			}
		}

		// Fetch plugin data from the WP repository.
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		$api = plugins_api(
			'plugin_information',
			array(
				'slug'   => $plugin_slug,
				'fields' => array(
					'sections'     => false,
					'tags'         => false,
					'compatibility' => false,
				),
			)
		);

		if ( is_wp_error( $api ) ) {
			wp_send_json_error( $api->get_error_message() );
		}

		$skin     = new \WP_Ajax_Upgrader_Skin();
		$upgrader = new \Plugin_Upgrader( $skin );
		$result   = $upgrader->install( $api->download_link );

		if ( is_wp_error( $result ) || ( $result !== true && ! is_null( $result ) ) ) {
			$error = is_wp_error( $result ) ? $result->get_error_message() : __( 'Installation failed.', 'ultimate-blocks' );
			wp_send_json_error( $error );
		}

		$plugin_file = $upgrader->plugin_info();
		if ( $plugin_file ) {
			$activate = activate_plugin( $plugin_file );
			if ( is_wp_error( $activate ) ) {
				wp_send_json_error( $activate->get_error_message() );
			}
		}

		wp_send_json_success( array( 'status' => 'installed' ) );
	}
}
