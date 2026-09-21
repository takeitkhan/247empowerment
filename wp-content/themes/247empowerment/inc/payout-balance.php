<?php
/**
 * Balance Management Functions
 * Handles user balance updates, withdrawals, and refunds
 */

/**
 * Get user's current balance
 */
function payout_get_user_balance($user_id) {
    $balance = floatval(get_user_meta($user_id, 'referral_commission', true) ?: 0);
    return $balance;
}

/**
 * Update user's balance
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

/**
 * Deduct amount from user balance
 */
function payout_deduct_balance($user_id, $amount, $reason = '') {
    $current_balance = payout_get_user_balance($user_id);
    
    if ($amount > $current_balance) {
        error_log("Cannot deduct $amount from user $user_id. Balance: $current_balance");
        return false;
    }
    
    $new_balance = $current_balance - floatval($amount);
    payout_set_user_balance($user_id, $new_balance, $reason);
    
    return true;
}

/**
 * Add amount to user balance
 */
function payout_add_balance($user_id, $amount, $reason = '') {
    $current_balance = payout_get_user_balance($user_id);
    $new_balance = $current_balance + floatval($amount);
    payout_set_user_balance($user_id, $new_balance, $reason);
    
    return true;
}

/**
 * Refund withdrawal request (add balance back)
 */
function payout_refund_withdrawal($user_id, $amount, $reason = '') {
    return payout_add_balance($user_id, $amount, $reason ?: 'Withdrawal refund');
}
?>
