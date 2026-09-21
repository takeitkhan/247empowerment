# Withdrawal System - Developer Reference Guide

## কোড রিফারেন্স

### 1. PayPalAPI.php - Balance Refund Logic

#### Location: `/wp-content/themes/247empowerment/inc/PayPalAPI.php`

#### Key Changes:

##### Token Error Handling (Line ~68-79)
```php
public function process_withdrawal($withdrawal_id) {
    $withdrawal = $this->payout_system->get_withdrawal($withdrawal_id);

    if (!$withdrawal) {
        return new WP_Error('not_found', 'Withdrawal not found');
    }

    // Get access token
    $token = $this->get_access_token();
    if (is_wp_error($token)) {
        $error_msg = $token->get_error_message();
        error_log('PayPal token error: ' . $error_msg);
        
        $this->payout_system->log_audit($withdrawal_id, 'payout_failed', 'Token error: ' . $error_msg);
        // IMPORTANT: Save error message to admin_notes
        $this->payout_system->update_withdrawal_status($withdrawal_id, 'failed', null, 'Token Error: ' . $error_msg);
        
        // CRITICAL: Refund balance
        payout_refund_withdrawal($withdrawal->user_id, $withdrawal->amount, 'PayPal token error: ' . $error_msg);
        
        do_action('payout_payment_failed', $withdrawal->user_id, $withdrawal_id, $error_msg);
        return $token;
    }
    // ... rest of code
}
```

##### API Error Handling (Line ~112-126)
```php
// Send payout request
$response = wp_remote_post($this->api_url . '/payments/payouts', [
    'headers' => [
        'Authorization' => 'Bearer ' . $token,
        'Content-Type' => 'application/json',
    ],
    'body' => json_encode($payout_data),
    'sslverify' => true,
    'timeout' => 15,
]);

if (is_wp_error($response)) {
    $error_msg = $response->get_error_message();
    error_log('PayPal API request error: ' . $error_msg);
    
    $this->payout_system->log_audit($withdrawal_id, 'payout_failed', 'API Error: ' . $error_msg);
    // IMPORTANT: Save error message to admin_notes
    $this->payout_system->update_withdrawal_status($withdrawal_id, 'failed', null, 'API Error: ' . $error_msg);
    
    // CRITICAL: Refund balance
    payout_refund_withdrawal($withdrawal->user_id, $withdrawal->amount, 'PayPal API error: ' . $error_msg);
    
    do_action('payout_payment_failed', $withdrawal->user_id, $withdrawal_id, $error_msg);
    return $response;
}
```

##### PayPal Response Error (Line ~145-169)
```php
$body = json_decode(wp_remote_retrieve_body($response), true);
$http_code = wp_remote_retrieve_response_code($response);

$this->payout_system->log_audit(
    $withdrawal_id, 
    'payout_response', 
    json_encode($body)
);

if ($http_code >= 400 || isset($body['name'])) {
    $error_msg = $body['message'] ?? $body['name'] ?? 'Unknown error';
    error_log('PayPal payout failed: ' . $error_msg);
    
    $error_details = [
        'http_code' => $http_code,
        'error_message' => $error_msg,
        'response_body' => $body,
        'timestamp' => current_time('mysql')
    ];
    $this->payout_system->log_audit($withdrawal_id, 'payout_failed', json_encode($error_details));
    
    // IMPORTANT: Save error message to admin_notes
    $this->payout_system->update_withdrawal_status($withdrawal_id, 'failed', null, $error_msg);
    
    // CRITICAL: Refund balance
    payout_refund_withdrawal($withdrawal->user_id, $withdrawal->amount, 'PayPal payout failed - Error: ' . $error_msg);
    
    do_action('payout_payment_failed', $withdrawal->user_id, $withdrawal_id, $error_msg);
    return new WP_Error('paypal_error', $error_msg);
}
```

### 2. PayoutSystem.php - Enhanced Status Update

#### Location: `/wp-content/themes/247empowerment/inc/PayoutSystem.php`

#### Updated Method: `update_withdrawal_status()`
```php
/**
 * Update withdrawal status with optional error notes
 * 
 * @param int $withdrawal_id The withdrawal request ID
 * @param string $status The new status (pending, approved, processing, paid, rejected, failed)
 * @param string $transaction_id Optional PayPal batch ID (for successful payouts)
 * @param string $admin_notes Optional error message or notes to display to admin/user
 * 
 * @return int|false Number of rows affected, or false on error
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
```

#### Usage Examples:
```php
// Success case
$this->update_withdrawal_status($withdrawal_id, 'paid', $batch_id);

// Failure case with error message
$this->update_withdrawal_status($withdrawal_id, 'failed', null, 'PayPal API Error: Connection timeout');

// Rejection case
$this->update_withdrawal_status($withdrawal_id, 'rejected', null, 'Insufficient documentation');
```

### 3. payout-balance.php - Refund Function

#### Location: `/wp-content/themes/247empowerment/inc/payout-balance.php`

#### Key Function: `payout_refund_withdrawal()`
```php
/**
 * Refund withdrawal request (add balance back)
 */
function payout_refund_withdrawal($user_id, $amount, $reason = '') {
    return payout_add_balance($user_id, $amount, $reason ?: 'Withdrawal refund');
}

/**
 * Add amount to user balance with logging
 */
function payout_add_balance($user_id, $amount, $reason = '') {
    $current_balance = payout_get_user_balance($user_id);
    $new_balance = $current_balance + floatval($amount);
    payout_set_user_balance($user_id, $new_balance, $reason);
    
    return true;
}

/**
 * Update user balance with comprehensive logging
 */
function payout_set_user_balance($user_id, $amount, $reason = '') {
    $balance = floatval($amount);
    update_user_meta($user_id, 'referral_commission', $balance);
    
    // Log balance change
    $balance_logs = get_user_meta($user_id, 'balance_change_logs', true);
    if (!is_array($balance_logs)) {
        $balance_logs = [];
    }
    
    $balance_logs[] = [
        'previous_balance' => payout_get_user_balance($user_id),
        'new_balance' => $balance,
        'change' => $amount,
        'reason' => $reason,
        'timestamp' => current_time('mysql'),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ];
    
    // Keep only last 100 logs
    $balance_logs = array_slice($balance_logs, -100);
    update_user_meta($user_id, 'balance_change_logs', $balance_logs);
    
    error_log("Balance updated for user $user_id: $reason | New balance: $balance");
}
```

---

## Admin Panel Changes

### Admin Withdrawal List Table

#### Location: `PayoutSystem.php` - `render_admin_page()` method

#### New "Details" Column:
```php
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
```

#### How it looks:
```
┌─────────────────────────────────┐
│ Withdrawal #123                 │
├─────────────────────────────────┤
│ Status: Failed ❌               │
│ Details:                        │
│   View Error ▼                 │
│   ├─ Error:                     │
│   └─ "Invalid PayPal account"   │
└─────────────────────────────────┘
```

---

## Frontend Changes

### User Withdrawal History Table

#### Location: `withdrawal-form.php`

#### New "Details" Column:
```php
<td style="padding: 10px; font-size: 12px;">
    <?php if ($withdrawal->status === 'failed' && !empty($withdrawal->admin_notes)) { ?>
        <details style="cursor: pointer; color: #d32f2f;">
            <summary style="font-weight: bold;">⚠️ Failed - Click for details</summary>
            <div style="background: #ffebee; padding: 8px; margin-top: 5px; border-left: 3px solid #d32f2f; border-radius: 3px; margin-top: 8px;">
                <strong style="color: #d32f2f;">Error Reason:</strong><br>
                <span style="color: #c62828;"><?php echo esc_html($withdrawal->admin_notes); ?></span>
                <p style="margin: 10px 0 0 0; font-size: 11px; color: #666;">
                    ✓ Your balance has been restored.<br>
                    Please contact support if you need assistance.
                </p>
            </div>
        </details>
    <?php } ?>
</td>
```

#### How it looks:
```
┌──────────────────────────────────────────────────────┐
│ Recent Withdrawal Requests                           │
├──────────────────────────────────────────────────────┤
│ Amount: $1.00                                        │
│ Status: Failed ❌                                    │
│ Requested: Feb 19, 2026                              │
│ Details:                                             │
│   ⚠️ Failed - Click for details                     │
│   ├─ Error Reason:                                   │
│   │  "PayPal API Error: ..."                         │
│   └─ ✓ Your balance has been restored.              │
└──────────────────────────────────────────────────────┘
```

---

## Database Schema

### withdrawal_requests Table

```sql
CREATE TABLE wp_withdrawal_requests (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT(20) UNSIGNED NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    paypal_email VARCHAR(255) NOT NULL,
    status ENUM('pending', 'approved', 'processing', 'paid', 'rejected', 'failed') DEFAULT 'pending',
    transaction_id VARCHAR(255) DEFAULT NULL,          -- PayPal Batch ID on success
    admin_notes LONGTEXT DEFAULT NULL,                 -- Error message on failure
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY user_id (user_id),
    KEY status (status),
    KEY created_at (created_at)
);
```

### payout_audit_log Table

```sql
CREATE TABLE wp_payout_audit_log (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    withdrawal_id BIGINT(20) UNSIGNED NOT NULL,
    admin_id BIGINT(20) UNSIGNED,
    action VARCHAR(50) NOT NULL,
    notes LONGTEXT DEFAULT NULL,
    response_data LONGTEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY withdrawal_id (withdrawal_id),
    KEY admin_id (admin_id),
    FOREIGN KEY (withdrawal_id) REFERENCES wp_withdrawal_requests(id) ON DELETE CASCADE
);
```

---

## Error Logging

### WordPress Debug Log

All errors logged to `/wp-content/debug.log`:

```log
[19-Feb-2026 14:23:45 UTC] =S WITHDRAWAL REQUEST START ===
[19-Feb-2026 14:23:45 UTC] POST data: {"nonce":"...","amount":1}
[19-Feb-2026 14:23:45 UTC] User ID: 5, Amount: 1
[19-Feb-2026 14:23:45 UTC] Withdrawal ID created: 123
[19-Feb-2026 14:23:46 UTC] === APPROVE WITHDRAWAL START ===
[19-Feb-2026 14:23:46 UTC] Withdrawal ID: 123, Notes: 
[19-Feb-2026 14:23:46 UTC] Deducting balance from user account
[19-Feb-2026 14:23:46 UTC] Balance updated for user 5: Withdrawal request approved - ID: 123 | New balance: 29
[19-Feb-2026 14:23:46 UTC] Updating withdrawal status to processing
[19-Feb-2026 14:23:46 UTC] Triggering payout_process_paypal action
[19-Feb-2026 14:23:47 UTC] PayPal API request error: cURL error 28: Operation timed out
[19-Feb-2026 14:23:47 UTC] Refunding balance of $1 to user 5
[19-Feb-2026 14:23:47 UTC] Balance updated for user 5: PayPal API error: cURL error 28: Operation timed out | New balance: 30
```

---

## Testing Guide

### Test Case 1: Successful Withdrawal
```php
// Credentials: Valid PayPal sandbox account
// Amount: $1.00
// Expected:
// - Status: paid
// - Balance: Deducted
// - Batch ID: Stored in transaction_id
```

### Test Case 2: PayPal Account Error
```php
// Credentials: Invalid PayPal account email
// Amount: $1.00
// Expected:
// - Status: failed
// - Balance: Restored
// - admin_notes: "RECEIVER_ACCOUNT_INVALID"
// - User sees: "Failed - Click for details"
```

### Test Case 3: API Timeout
```php
// Setup: Temporarily disable PayPal API
// Amount: $1.00
// Expected:
// - Status: failed
// - Balance: Restored
// - admin_notes: "API Error: Connection timeout"
```

---

## Debugging Tips

### Check User Balance
```php
$user_id = 5;
$balance = payout_get_user_balance($user_id);
error_log("User $user_id balance: " . $balance);
```

### Check Withdrawal Details
```php
$payout_system = new PayoutSystem();
$withdrawal = $payout_system->get_withdrawal(123);
echo "Withdrawal: ";
print_r($withdrawal);
```

### Check Balance Change Logs
```php
$logs = get_user_meta($user_id, 'balance_change_logs', true);
echo "Balance change history: ";
print_r($logs);
```

### Check Audit Trail
```php
global $wpdb;
$audits = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}payout_audit_log WHERE withdrawal_id = %d ORDER BY created_at DESC",
    123
));
echo "Audit logs: ";
print_r($audits);
```

---

## Support Contact

For issues or questions about the withdrawal system:
- Check `/wp-content/debug.log` for errors
- Review audit logs in `/wp-admin/admin.php?page=payout-requests`
- View balance history in user meta `balance_change_logs`
