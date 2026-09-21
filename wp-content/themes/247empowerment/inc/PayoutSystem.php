<?php
/**
 * Payout System for Withdrawal Requests
 * Handles user withdrawal requests and PayPal integration
 */

class PayoutSystem {

    const TABLE_NAME = 'withdrawal_requests';
    const MIN_WITHDRAWAL = 0.20;
    const MAX_WITHDRAWAL = 5000;
    private static $admin_menu_registered = false;

    public function __construct() {
        add_action('init', [$this, 'register_post_types']);
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('wp_ajax_submit_withdrawal', [$this, 'handle_withdrawal_request']);
        add_action('wp_ajax_approve_withdrawal', [$this, 'handle_approve_withdrawal']);
        add_action('wp_ajax_reject_withdrawal', [$this, 'handle_reject_withdrawal']);
        add_action('wp_ajax_retry_withdrawal', [$this, 'handle_retry_withdrawal']);
        add_action('wp_footer', [$this, 'enqueue_frontend_assets']);
    }

    /**
     * Create withdrawal requests table on plugin/theme activation
     */
    public static function activate() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            paypal_email VARCHAR(255) NOT NULL,
            status ENUM('pending', 'approved', 'processing', 'paid', 'rejected', 'failed') DEFAULT 'pending',
            transaction_id VARCHAR(255) DEFAULT NULL,
            admin_notes LONGTEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY user_id (user_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // Create audit log table
        $audit_table = $wpdb->prefix . 'payout_audit_log';
        $audit_sql = "CREATE TABLE IF NOT EXISTS $audit_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            withdrawal_id BIGINT(20) UNSIGNED NOT NULL,
            admin_id BIGINT(20) UNSIGNED,
            action VARCHAR(50) NOT NULL,
            notes LONGTEXT DEFAULT NULL,
            response_data LONGTEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY withdrawal_id (withdrawal_id),
            KEY admin_id (admin_id),
            FOREIGN KEY (withdrawal_id) REFERENCES $table_name(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        dbDelta($audit_sql);
    }

    /**
     * Register admin menu for payouts
     */
    public function register_admin_menu() {
        // Prevent duplicate menu registration
        if (self::$admin_menu_registered) {
            return;
        }
        self::$admin_menu_registered = true;

        add_menu_page(
            'Payouts',
            'Payouts',
            'manage_options',
            'payout-requests',
            [$this, 'render_admin_page'],
            'dashicons-money-alt',
            25
        );

        add_submenu_page(
            'payout-requests',
            'Withdrawal Requests',
            'Withdrawal Requests',
            'manage_options',
            'payout-requests',
            [$this, 'render_admin_page']
        );

        add_submenu_page(
            'payout-requests',
            'Payout Settings',
            'Settings',
            'manage_options',
            'payout-settings',
            [$this, 'render_settings_page']
        );

        add_submenu_page(
            'payout-requests',
            'Documentation',
            'Documentation',
            'manage_options',
            'payout-documentation',
            [$this, 'render_documentation_page']
        );

        add_submenu_page(
            'payout-requests',
            'Clear Test Data',
            'Clear Test Data',
            'manage_options',
            'payout-clear-data',
            [$this, 'render_clear_data_page']
        );
    }

    /**
     * Register custom post types
     */
    public function register_post_types() {
        // Not using CPT for payouts, using custom table instead
    }

    /**
     * Handle withdrawal request submission
     */
    public function handle_withdrawal_request() {
        try {
            // Enable error logging for debugging
            error_log('=== WITHDRAWAL REQUEST START ===');
            error_log('POST data: ' . json_encode($_POST));
            error_log('Is user logged in: ' . (is_user_logged_in() ? 'yes' : 'no'));

            // Verify nonce properly without dying
            $nonce = $_POST['nonce'] ?? $_POST['payout_nonce'] ?? '';
            error_log('Nonce received: ' . $nonce);
            error_log('Nonce verification result: ' . (wp_verify_nonce($nonce, 'payout_security') ? 'PASS' : 'FAIL'));

            if (!is_user_logged_in()) {
                error_log('User not logged in');
                wp_send_json_error('User not logged in');
            }

            if (!$nonce || !wp_verify_nonce($nonce, 'payout_security')) {
                error_log('Nonce verification failed');
                wp_send_json_error('Security check failed. Please refresh the page and try again.');
            }

            $user_id = get_current_user_id();
            $amount = floatval($_POST['amount'] ?? 0);
            error_log('User ID: ' . $user_id . ', Amount: ' . $amount);
            
            // Get PayPal email from user meta instead of form input
            $paypal_email = get_user_meta($user_id, 'paypal_email', true);
            error_log('PayPal email: ' . $paypal_email);

            // Validation
            if ($amount < self::MIN_WITHDRAWAL) {
                error_log('Amount below minimum: ' . $amount . ' < ' . self::MIN_WITHDRAWAL);
                wp_send_json_error("Minimum withdrawal amount is \$" . self::MIN_WITHDRAWAL);
            }

            if ($amount > self::MAX_WITHDRAWAL) {
                error_log('Amount above maximum: ' . $amount . ' > ' . self::MAX_WITHDRAWAL);
                wp_send_json_error("Maximum withdrawal amount is \$" . self::MAX_WITHDRAWAL);
            }

            if (!$paypal_email || !is_email($paypal_email)) {
                error_log('Invalid PayPal email: ' . $paypal_email);
                wp_send_json_error('Please set your PayPal email in your profile settings before requesting a withdrawal');
            }

            // Check balance
            $balance = floatval(get_user_meta($user_id, 'referral_commission', true) ?: 0);
            error_log('User balance: ' . $balance);
            
            if ($amount > $balance) {
                error_log('Insufficient balance: ' . $amount . ' > ' . $balance);
                wp_send_json_error("Insufficient balance. Your balance: \$" . number_format($balance, 2));
            }

            // Check rate limiting (max 1 request per day)
            $last_request = $this->get_last_withdrawal_request($user_id);
            if ($last_request) {
                error_log('Last request time: ' . $last_request->created_at);
                $time_diff = strtotime('now') - strtotime($last_request->created_at);
                error_log('Time since last request: ' . $time_diff . ' seconds');
                
                if ($time_diff < 86400) {
                    error_log('Rate limiting triggered');
                    wp_send_json_error('You can only submit one withdrawal request per day');
                }
            }

            // Create withdrawal request
            error_log('Creating withdrawal request...');
            $withdrawal_id = $this->create_withdrawal_request($user_id, $amount, $paypal_email);
            error_log('Withdrawal ID created: ' . $withdrawal_id);

            if ($withdrawal_id) {
                do_action('payout_withdrawal_requested', $user_id, $withdrawal_id, $amount, $paypal_email);
                error_log('=== WITHDRAWAL REQUEST SUCCESS ===');
                wp_send_json_success([
                    'message' => 'Withdrawal request submitted successfully',
                    'withdrawal_id' => $withdrawal_id
                ]);
            } else {
                error_log('Failed to get insertion ID');
                wp_send_json_error('Failed to create withdrawal request');
            }
        } catch (Exception $e) {
            error_log('Exception in handle_withdrawal_request: ' . $e->getMessage());
            error_log('Exception trace: ' . $e->getTraceAsString());
            wp_send_json_error('An unexpected error occurred: ' . $e->getMessage());
        }
    }

    /**
     * Create a new withdrawal request
     */
    public function create_withdrawal_request($user_id, $amount, $paypal_email) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        error_log('Database table name: ' . $table_name);
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '" . $table_name . "'") === $table_name;
        error_log('Table exists: ' . ($table_exists ? 'yes' : 'no'));
        
        if (!$table_exists) {
            error_log('Table does not exist, attempting to create it');
            self::activate(); // Try to create the table
        }

        error_log('Attempting to insert: user_id=' . $user_id . ', amount=' . $amount . ', email=' . $paypal_email);
        
        $result = $wpdb->insert(
            $table_name,
            [
                'user_id' => $user_id,
                'amount' => $amount,
                'paypal_email' => $paypal_email,
                'status' => 'pending'
            ],
            ['%d', '%f', '%s', '%s']
        );

        if ($result === false) {
            error_log('Database insert failed. Error: ' . $wpdb->last_error);
            error_log('Query: ' . $wpdb->last_query);
            return false;
        }
        
        error_log('Insert successful, ID: ' . $wpdb->insert_id);
        return $wpdb->insert_id;
    }

    /**
     * Get last withdrawal request for rate limiting
     */
    public function get_last_withdrawal_request($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d AND status IN ('pending', 'approved', 'processing', 'paid') ORDER BY created_at DESC LIMIT 1",
            $user_id
        ));
    }

    /**
     * Get withdrawal requests for user
     */
    public function get_user_withdrawals($user_id, $limit = 20) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC LIMIT %d",
            $user_id,
            $limit
        ));
    }

    /**
     * Get all withdrawal requests for admin
     */
    public function get_all_withdrawals($status = null, $limit = 50, $offset = 0) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;

        $query = "SELECT wr.*, u.user_login, u.user_email 
                  FROM $table_name wr 
                  LEFT JOIN $wpdb->users u ON wr.user_id = u.ID";

        if ($status) {
            $query .= $wpdb->prepare(" WHERE wr.status = %s", $status);
        }

        $query .= " ORDER BY wr.created_at DESC LIMIT %d OFFSET %d";

        return $wpdb->get_results($wpdb->prepare($query, $limit, $offset));
    }

    /**
     * Handle approval of withdrawal
     */
    public function handle_approve_withdrawal() {
        error_log('=== APPROVE WITHDRAWAL START ===');
        error_log('POST data: ' . json_encode($_POST));
        
        // Verify nonce properly without dying
        $nonce = $_POST['nonce'] ?? $_POST['payout_nonce'] ?? '';
        error_log('Nonce: ' . $nonce);
        
        if (!$nonce || !wp_verify_nonce($nonce, 'payout_security')) {
            error_log('Nonce verification failed');
            wp_send_json_error('Security check failed. Please refresh the page and try again.');
        }

        if (!current_user_can('manage_options')) {
            error_log('User does not have manage_options capability');
            wp_send_json_error('Unauthorized');
        }

        $withdrawal_id = intval($_POST['withdrawal_id'] ?? 0);
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');
        
        error_log('Withdrawal ID: ' . $withdrawal_id . ', Notes: ' . $notes);

        if (!$withdrawal_id) {
            error_log('Invalid withdrawal ID');
            wp_send_json_error('Invalid withdrawal ID');
        }

        $withdrawal = $this->get_withdrawal($withdrawal_id);
        if (!$withdrawal) {
            error_log('Withdrawal not found for ID: ' . $withdrawal_id);
            wp_send_json_error('Withdrawal not found');
        }

        error_log('Updating withdrawal status to processing');
        // Update status to processing (do NOT deduct balance yet - will be deducted when PayPal payment succeeds)
        $this->update_withdrawal_status($withdrawal_id, 'processing', null, $notes);
        $this->log_audit($withdrawal_id, 'approved', $notes, get_current_user_id());

        error_log('Triggering payout_process_paypal action');
        // Trigger PayPal payout
        do_action('payout_process_paypal', $withdrawal_id);
        
        // Fire notification hook for withdrawal approved
        do_action('mm_withdrawal_approved', $withdrawal->user_id, $withdrawal_id, $withdrawal->amount);

        error_log('=== APPROVE WITHDRAWAL SUCCESS ===');
        wp_send_json_success('Withdrawal approved and processing');
    }

    /**
     * Handle rejection of withdrawal
     */
    public function handle_reject_withdrawal() {
        error_log('=== REJECT WITHDRAWAL START ===');
        error_log('POST data: ' . json_encode($_POST));
        
        // Verify nonce properly without dying
        $nonce = $_POST['nonce'] ?? $_POST['payout_nonce'] ?? '';
        error_log('Nonce: ' . $nonce);
        
        if (!$nonce || !wp_verify_nonce($nonce, 'payout_security')) {
            error_log('Nonce verification failed');
            wp_send_json_error('Security check failed. Please refresh the page and try again.');
        }

        if (!current_user_can('manage_options')) {
            error_log('User does not have manage_options capability');
            wp_send_json_error('Unauthorized');
        }

        $withdrawal_id = intval($_POST['withdrawal_id'] ?? 0);
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');
        
        error_log('Withdrawal ID: ' . $withdrawal_id . ', Notes: ' . $notes);

        if (!$withdrawal_id) {
            error_log('Invalid withdrawal ID');
            wp_send_json_error('Invalid withdrawal ID');
        }

        $withdrawal = $this->get_withdrawal($withdrawal_id);
        if (!$withdrawal) {
            error_log('Withdrawal not found for ID: ' . $withdrawal_id);
            wp_send_json_error('Withdrawal not found');
        }

        error_log('Updating withdrawal status to rejected');
        // Update status to rejected
        $this->update_withdrawal_status($withdrawal_id, 'rejected');
        $this->log_audit($withdrawal_id, 'rejected', $notes, get_current_user_id());

        error_log('Triggering payout_withdrawal_rejected action');
        do_action('payout_withdrawal_rejected', $withdrawal->user_id, $withdrawal_id, $withdrawal->amount);
        
        // Fire notification hook for withdrawal rejected
        do_action('mm_withdrawal_rejected', $withdrawal->user_id, $withdrawal_id, $notes ?: 'No reason provided');

        error_log('=== REJECT WITHDRAWAL SUCCESS ===');
        wp_send_json_success('Withdrawal rejected');
    }

    /**
     * Handle retry of a failed withdrawal
     */
    public function handle_retry_withdrawal() {
        error_log('=== RETRY WITHDRAWAL START ===');
        error_log('POST data: ' . json_encode($_POST));
        
        // Verify nonce properly without dying
        $nonce = $_POST['nonce'] ?? $_POST['payout_nonce'] ?? '';
        error_log('Nonce: ' . $nonce);
        
        if (!$nonce || !wp_verify_nonce($nonce, 'payout_security')) {
            error_log('Nonce verification failed');
            wp_send_json_error('Security check failed. Please refresh the page and try again.');
        }

        if (!current_user_can('manage_options')) {
            error_log('User does not have manage_options capability');
            wp_send_json_error('Unauthorized');
        }

        $withdrawal_id = intval($_POST['withdrawal_id'] ?? 0);
        
        error_log('Withdrawal ID: ' . $withdrawal_id);

        if (!$withdrawal_id) {
            error_log('Invalid withdrawal ID');
            wp_send_json_error('Invalid withdrawal ID');
        }

        $withdrawal = $this->get_withdrawal($withdrawal_id);
        if (!$withdrawal) {
            error_log('Withdrawal not found for ID: ' . $withdrawal_id);
            wp_send_json_error('Withdrawal not found');
        }

        if ($withdrawal->status !== 'failed') {
            error_log('Withdrawal is not in failed status, current status: ' . $withdrawal->status);
            wp_send_json_error('Only failed withdrawals can be retried. Current status: ' . $withdrawal->status);
        }

        error_log('Updating withdrawal status to pending for retry');
        // Reset to pending status to trigger approval workflow again
        $this->update_withdrawal_status($withdrawal_id, 'pending', null, 'Retry initiated by admin');
        $this->log_audit($withdrawal_id, 'retry', 'Retry initiated', get_current_user_id());

        error_log('Triggering payout_withdrawal_retry action');
        do_action('payout_withdrawal_retry', $withdrawal->user_id, $withdrawal_id, $withdrawal->amount);

        error_log('=== RETRY WITHDRAWAL SUCCESS ===');
        wp_send_json_success('Withdrawal moved back to pending for retry. Please approve again.');
    }


    /**
     * Get single withdrawal
     */
    public function get_withdrawal($withdrawal_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $withdrawal_id
        ));
    }

    /**
     * Update withdrawal status
     */
    public function update_withdrawal_status($withdrawal_id, $status, $transaction_id = null, $admin_notes = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;

        $update_data = array_filter([
            'status' => $status,
            'transaction_id' => $transaction_id,
            'admin_notes' => $admin_notes
        ], function($value) {
            return $value !== null;
        });

        return $wpdb->update(
            $table_name,
            $update_data,
            ['id' => $withdrawal_id],
            array_fill(0, count($update_data), '%s'),
            ['%d']
        );
    }

    /**
     * Log audit action
     */
    public function log_audit($withdrawal_id, $action, $notes = '', $admin_id = 0) {
        global $wpdb;
        $audit_table = $wpdb->prefix . 'payout_audit_log';

        return $wpdb->insert(
            $audit_table,
            [
                'withdrawal_id' => $withdrawal_id,
                'admin_id' => $admin_id ?: get_current_user_id(),
                'action' => $action,
                'notes' => $notes
            ],
            ['%d', '%d', '%s', '%s']
        );
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        // Enqueue SweetAlert2
        wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.all.min.js', [], '11.10.0', true);
        wp_enqueue_style('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.min.css', [], '11.10.0');

        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : null;
        $paged = max(1, isset($_GET['paged']) ? intval($_GET['paged']) : 1);
        $per_page = 20;
        $offset = ($paged - 1) * $per_page;

        $withdrawals = $this->get_all_withdrawals($status, $per_page, $offset);
        ?>
        <div class="wrap">
            <h1>Withdrawal Requests</h1>
            
            <div class="top tablenav">
                <form method="get">
                    <input type="hidden" name="page" value="payout-requests">
                    <select name="status">
                        <option value="">All Status</option>
                        <option value="pending" <?php selected($status, 'pending'); ?>>Pending</option>
                        <option value="approved" <?php selected($status, 'approved'); ?>>Approved</option>
                        <option value="processing" <?php selected($status, 'processing'); ?>>Processing</option>
                        <option value="paid" <?php selected($status, 'paid'); ?>>Paid</option>
                        <option value="rejected" <?php selected($status, 'rejected'); ?>>Rejected</option>
                        <option value="failed" <?php selected($status, 'failed'); ?>>Failed</option>
                    </select>
                    <?php submit_button('Filter', 'button', 'submit', false); ?>
                </form>
            </div>

            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Amount</th>
                        <th>PayPal Email</th>
                        <th>Status</th>
                        <th>Details</th>
                        <th>Requested</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($withdrawals as $withdrawal) { 
                        // Determine status color
                        $status_colors = [
                            'pending' => '#ff9800',
                            'approved' => '#2196F3',
                            'processing' => '#9C27B0',
                            'paid' => '#27ae60',
                            'rejected' => '#f44336',
                            'failed' => '#f44336'
                        ];
                        $status_color = $status_colors[$withdrawal->status] ?? '#999';
                    ?>
                        <tr>
                            <td><?php echo $withdrawal->id; ?></td>
                            <td><?php echo $withdrawal->user_login; ?> (<?php echo $withdrawal->user_email; ?>)</td>
                            <td>$<?php echo number_format($withdrawal->amount, 2); ?></td>
                            <td><?php echo esc_html($withdrawal->paypal_email); ?></td>
                            <td>
                                <span style="background: <?php echo $status_color; ?>; color: white; padding: 4px 8px; border-radius: 3px; font-size: 12px;">
                                    <?php echo ucfirst($withdrawal->status); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($withdrawal->admin_notes) && $withdrawal->status === 'failed') { ?>
                                    <details style="cursor: pointer;">
                                        <summary style="color: #f44336; font-weight: bold;">View Error</summary>
                                        <div style="background: #fff3cd; padding: 8px; margin-top: 8px; border-left: 3px solid #f44336; border-radius: 3px; font-size: 12px;">
                                            <strong>Error:</strong><br>
                                            <?php echo nl2br(esc_html($withdrawal->admin_notes)); ?>
                                        </div>
                                    </details>
                                <?php } elseif (!empty($withdrawal->transaction_id)) { ?>
                                    <small style="color: #666;">
                                        <strong>Batch ID:</strong><br>
                                        <code><?php echo esc_html($withdrawal->transaction_id); ?></code>
                                    </small>
                                <?php } else { ?>
                                    <small style="color: #999;">-</small>
                                <?php } ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($withdrawal->created_at)); ?></td>
                            <td>
                                <?php if ($withdrawal->status === 'pending') { ?>
                                    <button class="button button-primary approve-btn" data-id="<?php echo $withdrawal->id; ?>">Approve</button>
                                    <button class="button button-danger reject-btn" data-id="<?php echo $withdrawal->id; ?>">Reject</button>
                                <?php } elseif ($withdrawal->status === 'failed') { ?>
                                    <button class="button button-primary retry-btn" data-id="<?php echo $withdrawal->id; ?>">Retry</button>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <script>
        jQuery(document).ready(function($) {
            const nonce = '<?php echo wp_create_nonce('payout_security'); ?>';
            
            // Approve button handler
            $(document).on('click', '.approve-btn', function(e) {
                e.preventDefault();
                const withdrawal_id = $(this).data('id');
                const $btn = $(this);
                
                console.log('Approve button clicked, ID:', withdrawal_id);
                
                if (typeof Swal === 'undefined') {
                    if (!confirm('Are you sure you want to approve this withdrawal?')) {
                        return;
                    }
                    const notes = prompt('Enter notes (optional):');
                    submitApproval(withdrawal_id, notes || '');
                    return;
                }
                
                Swal.fire({
                    title: 'Approve Withdrawal?',
                    html: 'Are you sure you want to approve this withdrawal?<br><br><input type="text" id="approval_notes" class="swal2-input" placeholder="Enter notes (optional)">',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Approve',
                    confirmButtonColor: '#27ae60',
                    cancelButtonText: 'Cancel',
                    didOpen: () => {
                        const input = Swal.getHtmlContainer().querySelector('#approval_notes');
                        input.focus();
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const notes = document.getElementById('approval_notes').value;
                        submitApproval(withdrawal_id, notes);
                    }
                });
                
                function submitApproval(id, notes) {
                    Swal.fire({
                        title: 'Processing...',
                        html: 'Approving withdrawal...',
                        icon: 'info',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'approve_withdrawal',
                            nonce: nonce,
                            withdrawal_id: id,
                            notes: notes
                        },
                        success: function(response) {
                            console.log('Approve response:', response);
                            if (response.success) {
                                Swal.fire({
                                    title: 'Success!',
                                    html: 'Withdrawal approved successfully!<br>Processing with PayPal. Balance will be deducted upon successful payment.',
                                    icon: 'success',
                                    timer: 2000,
                                    timerProgressBar: true,
                                    didClose: () => {
                                        location.reload();
                                    }
                                });
                            } else {
                                Swal.fire('Error', response.data || 'Unknown error', 'error');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX Error:', error, xhr.responseText);
                            Swal.fire('Error', 'An error occurred: ' + error, 'error');
                        }
                    });
                }
            });
            
            // Retry button handler
            $(document).on('click', '.retry-btn', function(e) {
                e.preventDefault();
                const withdrawal_id = $(this).data('id');
                
                console.log('Retry button clicked, ID:', withdrawal_id);
                
                if (typeof Swal === 'undefined') {
                    if (!confirm('Are you sure you want to retry this withdrawal?')) {
                        return;
                    }
                    submitRetry(withdrawal_id);
                    return;
                }
                
                Swal.fire({
                    title: 'Retry Withdrawal?',
                    html: 'Are you sure you want to retry processing this withdrawal?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Retry',
                    confirmButtonColor: '#2196F3',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitRetry(withdrawal_id);
                    }
                });
                
                function submitRetry(id) {
                    Swal.fire({
                        title: 'Processing...',
                        html: 'Retrying withdrawal...',
                        icon: 'info',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'retry_withdrawal',
                            nonce: nonce,
                            withdrawal_id: id
                        },
                        success: function(response) {
                            console.log('Retry response:', response);
                            if (response.success) {
                                Swal.fire({
                                    title: 'Success!',
                                    html: 'Withdrawal retry initiated.',
                                    icon: 'success',
                                    timer: 2000,
                                    timerProgressBar: true,
                                    didClose: () => {
                                        location.reload();
                                    }
                                });
                            } else {
                                Swal.fire('Error', response.data || 'Unknown error', 'error');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX Error:', error, xhr.responseText);
                            Swal.fire('Error', 'An error occurred: ' + error, 'error');
                        }
                    });
                }
            });

            // Reject button handler
            $(document).on('click', '.reject-btn', function(e) {
                e.preventDefault();
                const withdrawal_id = $(this).data('id');
                const $btn = $(this);
                
                console.log('Reject button clicked, ID:', withdrawal_id);
                
                if (typeof Swal === 'undefined') {
                    if (!confirm('Are you sure you want to reject this withdrawal?')) {
                        return;
                    }
                    const notes = prompt('Enter rejection reason:');
                    if (!notes) {
                        alert('Please provide a rejection reason');
                        return;
                    }
                    submitRejection(withdrawal_id, notes);
                    return;
                }
                
                Swal.fire({
                    title: 'Reject Withdrawal?',
                    html: 'Are you sure you want to reject this withdrawal?<br><br><textarea id="rejection_reason" class="swal2-textarea" placeholder="Enter rejection reason (required)" style="width: 100%; height: 100px; padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-family: inherit;"></textarea>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Reject',
                    confirmButtonColor: '#f44336',
                    cancelButtonText: 'Cancel',
                    didOpen: () => {
                        const textarea = Swal.getHtmlContainer().querySelector('#rejection_reason');
                        textarea.focus();
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const reason = document.getElementById('rejection_reason').value.trim();
                        if (!reason) {
                            Swal.fire('Error', 'Please provide a rejection reason', 'error');
                            return;
                        }
                        submitRejection(withdrawal_id, reason);
                    }
                });
                
                function submitRejection(id, notes) {
                    Swal.fire({
                        title: 'Processing...',
                        html: 'Rejecting withdrawal...',
                        icon: 'info',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'reject_withdrawal',
                            nonce: nonce,
                            withdrawal_id: id,
                            notes: notes
                        },
                        success: function(response) {
                            console.log('Reject response:', response);
                            if (response.success) {
                                Swal.fire({
                                    title: 'Rejected!',
                                    html: 'Withdrawal has been rejected.<br>User balance remains unchanged.',
                                    icon: 'success',
                                    timer: 2000,
                                    timerProgressBar: true,
                                    didClose: () => {
                                        location.reload();
                                    }
                                });
                            } else {
                                Swal.fire('Error', response.data || 'Unknown error', 'error');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX Error:', error, xhr.responseText);
                            Swal.fire('Error', 'An error occurred: ' + error, 'error');
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_admin_referer('payout_settings_nonce');

            update_option('payout_min_amount', floatval($_POST['min_amount'] ?? 0.20));
            update_option('payout_max_amount', floatval($_POST['max_amount'] ?? 5000));
            update_option('payout_paypal_client_id', sanitize_text_field($_POST['paypal_client_id'] ?? ''));
            update_option('payout_paypal_mode', sanitize_text_field($_POST['paypal_mode'] ?? 'sandbox'));
            
            // Only update secret if a new value is provided
            if (!empty($_POST['paypal_secret'])) {
                update_option('payout_paypal_secret', sanitize_text_field($_POST['paypal_secret']));
            }

            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }

        $min_amount = get_option('payout_min_amount', 0.20);
        $max_amount = get_option('payout_max_amount', 5000);
        $client_id = get_option('payout_paypal_client_id', '');
        $mode = get_option('payout_paypal_mode', 'sandbox');
        $secret = get_option('payout_paypal_secret', '');
        $has_secret = !empty($secret);
        ?>
        <div class="wrap">
            <h1>Payout Settings</h1>
            <form method="post" action="">
                <?php wp_nonce_field('payout_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th><label for="min_amount">Minimum Withdrawal Amount</label></th>
                        <td><input type="number" step="0.01" name="min_amount" value="<?php echo $min_amount; ?>" /></td>
                    </tr>
                    <tr>
                        <th><label for="max_amount">Maximum Withdrawal Amount</label></th>
                        <td><input type="number" step="0.01" name="max_amount" value="<?php echo $max_amount; ?>" /></td>
                    </tr>
                    <tr>
                        <th><label for="paypal_mode">PayPal Mode</label></th>
                        <td>
                            <select name="paypal_mode">
                                <option value="sandbox" <?php selected($mode, 'sandbox'); ?>>Sandbox (Testing)</option>
                                <option value="live" <?php selected($mode, 'live'); ?>>Live</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="paypal_client_id">PayPal Client ID</label></th>
                        <td><input type="text" name="paypal_client_id" value="<?php echo esc_attr($client_id); ?>" style="width: 400px;" /></td>
                    </tr>
                    <tr>
                        <th><label for="paypal_secret">PayPal Secret</label></th>
                        <td>
                            <input type="password" name="paypal_secret" placeholder="<?php echo $has_secret ? 'Leave blank to keep current secret' : 'Enter PayPal Secret'; ?>" style="width: 400px;" />
                            <?php if ($has_secret) { ?>
                                <p style="margin-top: 8px; color: #28a745;"><span style="font-weight: bold;">✓</span> Secret is set and saved</p>
                            <?php } ?>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Save Settings'); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render documentation page
     */
    public function render_documentation_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        // Docs directory
        $docs_dir = get_template_directory() . '/docs/';
        
        // Documentation index
        $docs = [
            'WITHDRAWAL_SOLUTION_SUMMARY.md' => 'Solution Summary',
            'WITHDRAWAL_ISSUE_FIX.md' => 'Technical Analysis',
            'WITHDRAWAL_BEFORE_AFTER.md' => 'Before & After Comparison',
            'WITHDRAWAL_DEVELOPER_GUIDE.md' => 'Developer Guide',
            'WITHDRAWAL_DEPLOYMENT_CHECKLIST.md' => 'Deployment Checklist',
            'README.md' => 'Documentation Index',
        ];

        ?>
        <div class="wrap">
            <h1>📚 Withdrawal System Documentation</h1>
            
            <div style="background: #f1f1f1; border: 1px solid #ccc; padding: 20px; margin: 20px 0; border-radius: 5px;">
                <h2 style="margin-top: 0;">Quick Start</h2>
                <p>Complete documentation for the withdrawal system fix covering balance refund and error visibility issues.</p>
                <ul>
                    <li><strong>For Management:</strong> Start with <em>Solution Summary</em></li>
                    <li><strong>For Developers:</strong> Check <em>Developer Guide</em></li>
                    <li><strong>For Deployment:</strong> Follow <em>Deployment Checklist</em></li>
                    <li><strong>For Understanding:</strong> Read <em>Technical Analysis</em> and <em>Before & After</em></li>
                </ul>
            </div>

            <h2>📖 Available Documentation</h2>
            
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Document</th>
                        <th>Description</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($docs as $filename => $title) {
                        $filepath = $docs_dir . $filename;
                        $exists = file_exists($filepath);
                        $size = $exists ? round(filesize($filepath) / 1024, 1) . ' KB' : 'N/A';
                        $lines = $exists ? count(file($filepath)) : 0;
                    ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($title); ?></strong><br>
                                <small style="color: #666;"><?php echo esc_html($filename); ?></small>
                            </td>
                            <td>
                                <?php if ($exists) {
                                    echo '<span style="color: #28a745;">✓ Available</span><br>';
                                    echo '<small style="color: #666;">Size: ' . esc_html($size) . ' | Lines: ' . intval($lines) . '</small>';
                                } else {
                                    echo '<span style="color: #dc3545;">✗ Not Found</span>';
                                } ?>
                            </td>
                            <td>
                                <?php if ($exists) { ?>
                                    <button class="button button-primary" onclick="jQuery('#doc-<?php echo sanitize_html_class($filename); ?>').slideToggle();">View</button>
                                <?php } else { ?>
                                    <span style="color: #999;">N/A</span>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

            <h2 style="margin-top: 40px;">📂 File Locations</h2>
            <div style="background: #f5f5f5; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 12px;">
                <p><strong>Documentation:</strong><br>/wp-content/themes/247empowerment/docs/</p>
                <p><strong>Related Code:</strong><br>
                    /wp-content/themes/247empowerment/inc/PayPalAPI.php<br>
                    /wp-content/themes/247empowerment/inc/PayoutSystem.php<br>
                    /wp-content/themes/247empowerment/template-custom/frontend/withdrawal-form.php
                </p>
            </div>

            <h2 style="margin-top: 40px;">✅ Implementation Status</h2>
            <div style="background: #e8f5e9; border: 1px solid #4caf50; padding: 15px; border-radius: 5px;">
                <p style="margin: 0;"><strong style="color: #2e7d32;">✓ All documentation ready for implementation</strong></p>
                <p style="margin: 10px 0 0 0; font-size: 12px; color: #555;">Documentation has been converted to English and is available in the theme folder. Follow the Deployment Checklist for step-by-step implementation.</p>
            </div>

            <h2 style="margin-top: 40px;">🔗 Quick Links</h2>
            <ul>
                <li><a href="<?php echo esc_url(admin_url('admin.php?page=payout-requests')); ?>" class="button">← Back to Withdrawals</a></li>
                <li><a href="<?php echo esc_url(admin_url('admin.php?page=payout-settings')); ?>" class="button">Settings</a></li>
            </ul>
        </div>

        <style>
            .wrap table td { vertical-align: top; }
            .wrap .widefat tbody tr:hover { background-color: #f9f9f9; }
        </style>
        <?php
    }

    /**
     * Render clear test data page
     */
    public function render_clear_data_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        // Handle form submission
        if (isset($_POST['action']) && $_POST['action'] === 'clear_withdrawal_data') {
            check_admin_referer('clear_withdrawal_nonce');
            
            global $wpdb;
            
            $results = array();
            
            try {
                // Disable foreign key constraints temporarily
                $wpdb->query("SET FOREIGN_KEY_CHECKS=0");
                
                // Clear payout audit log table first (references withdrawal_requests)
                $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}payout_audit_log");
                $results['audit_log'] = 0;
                
                // Then clear withdrawal requests table
                $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}withdrawal_requests");
                $results['withdrawal_requests'] = 0;
                
                // Re-enable foreign key constraints
                $wpdb->query("SET FOREIGN_KEY_CHECKS=1");
                
                // Clear user meta balance change logs (optional)
                if (isset($_POST['clear_balance_logs']) && $_POST['clear_balance_logs'] === '1') {
                    $users = get_users();
                    foreach ($users as $user) {
                        delete_user_meta($user->ID, 'balance_change_logs');
                    }
                    $results['balance_logs_cleared'] = true;
                }
                
                $results['success'] = true;
                $results['timestamp'] = current_time('mysql');
                
                // Display success message
                echo '<div class="notice notice-success is-dismissible"><p>';
                echo '✅ Test data cleared successfully!<br>';
                echo 'Withdrawal Requests: ' . $results['withdrawal_requests'] . ' remaining<br>';
                echo 'Audit Logs: ' . $results['audit_log'] . ' remaining<br>';
                if (isset($results['balance_logs_cleared']) && $results['balance_logs_cleared']) {
                    echo 'Balance change logs cleared for all users<br>';
                }
                echo 'Timestamp: ' . $results['timestamp'];
                echo '</p></div>';
            } catch (Exception $e) {
                wp_die('Error clearing data: ' . esc_html($e->getMessage()));
            }
        }

        // Get current counts
        global $wpdb;
        $withdrawal_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}withdrawal_requests");
        $audit_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}payout_audit_log");
        ?>

        <div class="wrap">
            <h1>Clear Withdrawal Test Data</h1>
            
            <div class="card">
                <div class="card-body" style="padding: 20px;">
                    <h2>Current Status</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Withdrawal Requests</th>
                            <td><strong><?php echo $withdrawal_count; ?></strong> records</td>
                        </tr>
                        <tr>
                            <th scope="row">Audit Logs</th>
                            <td><strong><?php echo $audit_count; ?></strong> records</td>
                        </tr>
                    </table>
                    
                    <hr>
                    
                    <h2>Clear Test Data</h2>
                    <p style="color: #dc3545; font-weight: bold;">⚠️ WARNING: This action cannot be undone!</p>
                    
                    <form method="POST">
                        <?php wp_nonce_field('clear_withdrawal_nonce'); ?>
                        <input type="hidden" name="action" value="clear_withdrawal_data">
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="clear_requests">
                                        <input type="checkbox" id="clear_requests" name="clear_requests" value="1" checked disabled>
                                        Clear Withdrawal Requests Table
                                    </label>
                                </th>
                                <td>Will delete all withdrawal requests</td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="clear_audit">
                                        <input type="checkbox" id="clear_audit" name="clear_audit" value="1" checked disabled>
                                        Clear Audit Logs Table
                                    </label>
                                </th>
                                <td>Will delete all payout audit logs</td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="clear_balance_logs">
                                        <input type="checkbox" id="clear_balance_logs" name="clear_balance_logs" value="1">
                                        Clear Balance Change Logs (Optional)
                                    </label>
                                </th>
                                <td>Clears transaction history from all users (keeps current balance)</td>
                            </tr>
                        </table>
                        
                        <p>
                            <button type="submit" class="button button-primary button-large" style="background-color: #dc3545;">
                                Clear Test Data
                            </button>
                        </p>
                    </form>
                    
                    <div style="margin-top: 30px; padding: 15px; background: #f1f1f1; border-left: 4px solid #0073aa;">
                        <h3>What This Does:</h3>
                        <ul style="margin: 10px 0; padding-left: 20px;">
                            <li>Deletes all withdrawal requests (fresh state for testing)</li>
                            <li>Deletes all audit logs (clean history)</li>
                            <li>Optionally clears transaction history (keeps current user balances)</li>
                            <li>Does NOT reset user balances - those remain as-is for testing</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <style>
        .card {
            background: white;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin: 20px 0;
            box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
        }

        .card-body {
            border-bottom: 1px solid #eee;
        }

        .card-body h2 {
            margin-top: 0;
            color: #333;
        }
        </style>

        <?php
    }

    /**
     * Convert markdown to HTML (simple implementation)
     */
    private function markdown_to_html($markdown) {
        // Escape HTML first
        $html = htmlspecialchars($markdown);

        // Headers
        $html = preg_replace('/^### (.*?)$/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^## (.*?)$/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^# (.*?)$/m', '<h1>$1</h1>', $html);

        // Bold and italic
        $html = preg_replace('/\*\*(.*?)\*\*/m', '<strong>$1</strong>', $html);
        $html = preg_replace('/\*(.*?)\*/m', '<em>$1</em>', $html);
        $html = preg_replace('/__(.*?)__/m', '<strong>$1</strong>', $html);
        $html = preg_replace('/_(.*?)_/m', '<em>$1</em>', $html);

        // Code blocks
        $html = preg_replace('/```(.*?)```/s', '<pre><code>$1</code></pre>', $html);
        $html = preg_replace('/`([^`]+)`/m', '<code>$1</code>', $html);

        // Links
        $html = preg_replace('/\[(.*?)\]\((.*?)\)/m', '<a href="$2">$1</a>', $html);

        // Line breaks
        $html = preg_replace('/\n\n+/', '</p><p>', $html);
        $html = '<p>' . $html . '</p>';

        // Lists
        $html = preg_replace('/<p>- (.*?)<\/p>/m', '<ul><li>$1</li></ul>', $html);
        $html = preg_replace('/<\/ul>\s*<ul>/', '', $html);

        return $html;
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Ensure jQuery is loaded
        wp_enqueue_script('jquery');
        
        // Localize script data for AJAX
        wp_localize_script('jquery', 'PayoutData', [
            'nonce' => wp_create_nonce('payout_security'),
            'ajaxurl' => admin_url('admin-ajax.php')
        ]);
    }
}
