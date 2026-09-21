<?php
/**
 * Payout Notifications and Emails
 */

class PayoutNotifications {

    public function __construct() {
        // Withdrawal requested
        add_action('payout_withdrawal_requested', [$this, 'send_request_email'], 10, 4);
        
        // Withdrawal rejected
        add_action('payout_withdrawal_rejected', [$this, 'send_rejection_email'], 10, 3);
        
        // Payment successful
        add_action('payout_payment_success', [$this, 'send_success_email'], 10, 4);
        
        // Payment failed
        add_action('payout_payment_failed', [$this, 'send_failure_email'], 10, 3);
    }

    /**
     * Send email when withdrawal is requested
     */
    public function send_request_email($user_id, $withdrawal_id, $amount, $paypal_email) {
        $user = get_user_by('ID', $user_id);
        if (!$user) return;

        $subject = 'Withdrawal Request Received - ' . get_bloginfo('name');
        
        $message = $this->email_template('withdrawal_requested', [
            'user_name' => $user->display_name,
            'amount' => number_format($amount, 2),
            'paypal_email' => $paypal_email,
            'withdrawal_id' => $withdrawal_id,
            'date' => current_time('Y-m-d H:i:s'),
            'home_url' => home_url(),
        ]);

        wp_mail($user->user_email, $subject, $message, $this->get_email_headers());
    }

    /**
     * Send email when withdrawal is rejected
     */
    public function send_rejection_email($user_id, $withdrawal_id, $amount) {
        $user = get_user_by('ID', $user_id);
        if (!$user) return;

        $subject = 'Withdrawal Request Rejected - ' . get_bloginfo('name');
        
        $message = $this->email_template('withdrawal_rejected', [
            'user_name' => $user->display_name,
            'amount' => number_format($amount, 2),
            'withdrawal_id' => $withdrawal_id,
            'home_url' => home_url(),
        ]);

        wp_mail($user->user_email, $subject, $message, $this->get_email_headers());
    }

    /**
     * Send email when payment is successful
     */
    public function send_success_email($user_id, $withdrawal_id, $amount, $batch_id) {
        $user = get_user_by('ID', $user_id);
        if (!$user) return;

        $subject = '✓ Your Withdrawal Has Been Processed! - ' . get_bloginfo('name');
        
        $message = $this->email_template('withdrawal_success', [
            'user_name' => $user->display_name,
            'amount' => number_format($amount, 2),
            'withdrawal_id' => $withdrawal_id,
            'batch_id' => $batch_id,
            'date' => current_time('Y-m-d H:i:s'),
            'home_url' => home_url(),
        ]);

        wp_mail($user->user_email, $subject, $message, $this->get_email_headers());
    }

    /**
     * Send email when payment fails
     */
    public function send_failure_email($user_id, $withdrawal_id, $error) {
        $user = get_user_by('ID', $user_id);
        if (!$user) return;

        $subject = 'Withdrawal Failed - ' . get_bloginfo('name');
        
        $message = $this->email_template('withdrawal_failed', [
            'user_name' => $user->display_name,
            'withdrawal_id' => $withdrawal_id,
            'error' => $error,
            'home_url' => home_url(),
            'support_email' => get_option('admin_email'),
        ]);

        wp_mail($user->user_email, $subject, $message, $this->get_email_headers());
    }

    /**
     * Email template handler
     */
    private function email_template($type, $data) {
        switch ($type) {
            case 'withdrawal_requested':
                return sprintf('
Dear %s,

Your withdrawal request has been received and is now pending approval.

Withdrawal Details:
- Amount: $%s
- PayPal Email: %s
- Withdrawal ID: %s
- Requested: %s

Your request will be reviewed and processed within 1-3 business days.

You can check the status anytime at: %s

Best regards,
%s Team
                ', 
                    $data['user_name'],
                    $data['amount'],
                    $data['paypal_email'],
                    $data['withdrawal_id'],
                    $data['date'],
                    $data['home_url'],
                    get_bloginfo('name')
                );

            case 'withdrawal_rejected':
                return sprintf('
Dear %s,

Unfortunately, your withdrawal request #%s for $%s has been rejected.

The amount has been returned to your account balance.

If you have any questions, please contact our support team.

Best regards,
%s Team
                ', 
                    $data['user_name'],
                    $data['withdrawal_id'],
                    $data['amount'],
                    get_bloginfo('name')
                );

            case 'withdrawal_success':
                return sprintf('
Dear %s,

Great news! Your withdrawal has been successfully processed and sent to your PayPal account.

Withdrawal Details:
- Amount: $%s
- Withdrawal ID: %s
- PayPal Batch ID: %s
- Processed: %s

The funds should appear in your PayPal account within 1-3 business days, depending on your bank.

Visit: %s

Best regards,
%s Team
                ', 
                    $data['user_name'],
                    $data['amount'],
                    $data['withdrawal_id'],
                    $data['batch_id'],
                    $data['date'],
                    $data['home_url'],
                    get_bloginfo('name')
                );

            case 'withdrawal_failed':
                return sprintf('
Dear %s,

Unfortunately, there was an issue processing your withdrawal request #%s.

Error: %s

Your amount remains in your account. Please try again later or contact our support team at %s.

Best regards,
%s Team
                ', 
                    $data['user_name'],
                    $data['withdrawal_id'],
                    $data['error'],
                    $data['support_email'],
                    get_bloginfo('name')
                );

            default:
                return '';
        }
    }

    /**
     * Get email headers
     */
    private function get_email_headers() {
        return [
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
        ];
    }
}
