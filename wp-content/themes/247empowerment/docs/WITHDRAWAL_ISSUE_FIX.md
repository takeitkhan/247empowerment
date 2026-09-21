# Withdrawal System Issue Fix - Complete Analysis & Solution

## সমস্যা সংক্ষিপ্তকরণ

আপনার রিপোর্ট করা দুটি সমস্যা:

### সমস্যা ১: Balance Deduct হচ্ছে কিন্তু Withdrawal Failed
- User এর balance $30 থেকে $29 হয়ে গেছে
- কিন্তু withdrawal request status "Failed" দেখাচ্ছে
- টাকা কোথাও যায়নি কিন্তু balance থেকে কেটে গেছে

### সমস্যা ২: কেনো Failed হলো তা জানার কোনো উপায় নেই
- User দেখতে পারে না কেনো withdrawal ফেইল হয়েছে
- Admin ও error details দেখতে পারছেন না

---

## Root Cause Analysis

### কেনো এই সমস্যা হচ্ছে?

**PayoutSystem.php এর `handle_approve_withdrawal()` method এ:**

```php
// Line 362-367
error_log('Deducting balance from user account');
// Deduct balance when approved
payout_deduct_balance($withdrawal->user_id, $withdrawal->amount, 'Withdrawal request approved - ID: ' . $withdrawal_id);

error_log('Updating withdrawal status to processing');
// Update status to processing
$this->update_withdrawal_status($withdrawal_id, 'processing');
```

**সমস্যা:** Balance deduct হয় **Approve করার সময়**, কিন্তু:
1. PayPal payout fail হলেও balance revert হয় না
2. Failed withdrawal ও কোনো error message store হয় না

**PayPalAPI.php এর `process_withdrawal()` method এ:**

```php
// Line 141-150 (পুরনো কোড)
if (is_wp_error($response)) {
    $this->payout_system->log_audit($withdrawal_id, 'payout_failed', 'API error: ' . $response->get_error_message());
    $this->payout_system->update_withdrawal_status($withdrawal_id, 'failed');
    do_action('payout_payment_failed', $withdrawal->user_id, $withdrawal_id, $response->get_error_message());
    return $response;
}
```

**সমস্যা:**
1. PayPal payout fail হলে balance revert করা হয় না
2. Error message `admin_notes` field এ save হয় না
3. User কোনো error details দেখতে পারে না

---

## সমাধান বাস্তবায়িত হয়েছে

### সমাধান ১: Balance Refund Logic যোগ করা (PayPalAPI.php)

#### সব error scenarios এ balance revert করা হচ্ছে:

**1) Token Error:**
```php
if (is_wp_error($token)) {
    $error_msg = $token->get_error_message();
    error_log('PayPal token error: ' . $error_msg);
    
    $this->payout_system->log_audit($withdrawal_id, 'payout_failed', 'Token error: ' . $error_msg);
    $this->payout_system->update_withdrawal_status($withdrawal_id, 'failed', null, 'Token Error: ' . $error_msg);
    
    // CRITICAL: Refund the balance back to user
    error_log('Refunding balance of $' . $withdrawal->amount . ' to user ' . $withdrawal->user_id);
    payout_refund_withdrawal($withdrawal->user_id, $withdrawal->amount, 'PayPal token error: ' . $error_msg);
    
    do_action('payout_payment_failed', $withdrawal->user_id, $withdrawal_id, $error_msg);
    return $token;
}
```

**2) API Connection Error:**
```php
if (is_wp_error($response)) {
    $error_msg = $response->get_error_message();
    error_log('PayPal API request error: ' . $error_msg);
    
    $this->payout_system->log_audit($withdrawal_id, 'payout_failed', 'API Error: ' . $error_msg);
    $this->payout_system->update_withdrawal_status($withdrawal_id, 'failed', null, 'API Error: ' . $error_msg);
    
    // CRITICAL: Refund the balance back to user
    error_log('Refunding balance of $' . $withdrawal->amount . ' to user ' . $withdrawal->user_id);
    payout_refund_withdrawal($withdrawal->user_id, $withdrawal->amount, 'PayPal API error: ' . $error_msg);
    
    do_action('payout_payment_failed', $withdrawal->user_id, $withdrawal_id, $error_msg);
    return $response;
}
```

**3) PayPal Response Error (HTTP 400+):**
```php
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
    
    // Update withdrawal status with error reason
    $this->payout_system->update_withdrawal_status($withdrawal_id, 'failed', null, $error_msg);
    
    // CRITICAL: Refund the balance back to user
    error_log('Refunding balance of $' . $withdrawal->amount . ' to user ' . $withdrawal->user_id);
    payout_refund_withdrawal($withdrawal->user_id, $withdrawal->amount, 'PayPal payout failed - Error: ' . $error_msg);
    
    do_action('payout_payment_failed', $withdrawal->user_id, $withdrawal_id, $error_msg);
    return new WP_Error('paypal_error', $error_msg);
}
```

### সমাধান ২: Error Messages Store করা (PayoutSystem.php)

`update_withdrawal_status()` method modify করা হয়েছে:

```php
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
```

এখন error message `admin_notes` field এ save হচ্ছে।

### সমাধান ৩: Admin Panel এ Error Details দেখানো (PayoutSystem.php)

Admin page এর table update করা হয়েছে:

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
    <?php } ?>
</td>
```

এখন Admin ক্লিক করে "View Error" দেখতে পাবেন error reason।

### সমাধান ৪: Frontend এ User Error Details দেখানো (withdrawal-form.php)

User এর withdrawal history table এ error reasons দেখানো হচ্ছে:

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

এখন User withdrawal history তে ক্লিক করে error reason দেখতে পাবেন এবং জানবেন balance restore হয়েছে।

---

## সব পরিবর্তনের সারসংক্ষেপ

| ফাইল | পরিবর্তন | উপকার |
|------|---------|--------|
| [PayPalAPI.php](PayPalAPI.php) | ✓ Balance refund logic যোগ | Failed withdrawal এ balance revert হবে |
| [PayPalAPI.php](PayPalAPI.php) | ✓ Error message admin_notes এ store | Admin error reason দেখতে পাবে |
| [PayoutSystem.php](PayoutSystem.php) | ✓ update_withdrawal_status() enhance | admin_notes parameter support |
| [PayoutSystem.php](PayoutSystem.php) | ✓ Admin page table update | Failed withdrawal এ error দেখানো হবে |
| [withdrawal-form.php](withdrawal-form.php) | ✓ Recent withdrawals table update | User error reason দেখতে পাবে |

---

## কিভাবে Test করবেন?

### Test Case 1: PayPal Token Error
```php
// PayPalAPI.php এ সেট করুন:
get_option('payout_paypal_client_id', ''); // Invalid credentials
```

**প্রত্যাশিত ফলাফল:**
- ✓ Withdrawal status: Failed
- ✓ User balance: Restored
- ✓ Error message: "Token Error: ..." দেখা যাবে

### Test Case 2: PayPal API Error
```php
// Invalid PayPal account সাথে test করুন
```

**প্রত্যাশিত ফলাফল:**
- ✓ Withdrawal status: Failed
- ✓ User balance: Restored
- ✓ Admin ও User error reason দেখতে পাবেন

---

## Database Changes

কোনো migration প্রয়োজন নেই। `admin_notes` field ইতিমধ্যে table এ আছে:

```sql
CREATE TABLE IF NOT EXISTS wp_withdrawal_requests (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    ...
    admin_notes LONGTEXT DEFAULT NULL,
    ...
);
```

---

## আগামী কাজ (Optional Enhancements)

1. **Email Notification** - User কে email পাঠান যখন withdrawal fails এবং balance restore হয়
2. **Retry Mechanism** - Failed withdrawal গুলির জন্য "Retry" button যোগ করা
3. **Webhook Support** - PayPal webhooks এর মাধ্যমে real-time status update
4. **Better Logging** - Syslog বা external log service এ detailed logs পাঠানো

---

## ফলাফল

✅ **সমস্যা ১ সমাধান:** Failed withdrawal এ balance এখন automatic restore হবে
✅ **সমস্যা ২ সমাধান:** User ও Admin error reason দেখতে পাবেন
✅ **নতুন বৈশিষ্ট্য:** Failed withdrawal গুলির জন্য বিস্তারিত error logging এবং audit trail
