<?php
/* Template Name: Withdrawal Request */
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url());
    exit;
}

get_header_based_on_login();

$current_user_id = get_current_user_id();

// Get the user slug from the query variable
$user_slug = get_query_var('user_profile');

// Determine the target user
if ($user_slug) {
    $user = get_user_by('slug', $user_slug);
} else {
    $user = get_user_by('ID', $current_user_id);
}

// Get profile data
if ($user) {
    $profile_data_instance = new UserProfileData($user);
    $target_identifier = $user_slug ? $user_slug : $user->user_login;
    $profile_data_instance = new UserProfileData($target_identifier);
    $profile = $profile_data_instance->getProfile();
} else {
    $user = null;
    $profile = null;
}

$profile = $profile ?: [];

// Get user data
$user_id = $current_user_id;
$balance = floatval(get_user_meta($user_id, 'referral_commission', true) ?: 0);
$min_amount = floatval(get_option('payout_min_amount', 5));
$max_amount = floatval(get_option('payout_max_amount', 5000));
$saved_paypal_email = get_user_meta($user_id, 'paypal_email', true);

// Get withdrawal history
$payout_system = new PayoutSystem();
$withdrawals = $payout_system->get_user_withdrawals($user_id, 5);
?>

<div class="container profile-page pt20">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3">
            <?php get_template_part('template-custom/auth/common-parts/editprofilemenu', null, ['profile' => $profile]); ?>
            <?php get_template_part('template-custom/auth/profile-parts/navlink', null, ['profile' => $profile]); ?>
        </div>
        <div class="mb-0 rounded-end-0 col-lg-6">
            <div class="bg-white custom-card post-search">
                <div class="gap-3 post-row">
                    <div>
                        <h5 class="pb-4 text-start portal-title">Withdrawal Request</h5>
                    </div>

                    <!-- Current Balance -->
                    <div class="mb-4" style="background: #f5f5f5; padding: 20px; border-radius: 5px;">
                        <p style="margin: 0; font-size: 14px; color: #666;">Current Balance</p>
                        <h3 style="margin: 10px 0 0 0; font-size: 28px; color: #27ae60;">
                            $<?php echo number_format($balance, 2); ?>
                        </h3>
                    </div>

                    <!-- PayPal Email Status -->
                    <div class="mb-4">
                        <?php if ($saved_paypal_email) { ?>
                            <div style="padding: 12px; background: #e8f5e9; border: 1px solid #81c784; border-radius: 4px; margin-bottom: 10px;">
                                <p style="margin: 0; color: #2e7d32; font-size: 14px;">
                                    <strong>✓ PayPal Email:</strong> <?php echo esc_html($saved_paypal_email); ?>
                                </p>
                                <p style="margin: 5px 0 0 0; font-size: 12px; color: #558b2f;">
                                    <a href="<?php echo esc_url(home_url('/modify-paypal-email')); ?>" style="color: #558b2f; text-decoration: underline;">Change PayPal Email</a>
                                </p>
                            </div>
                        <?php } else { ?>
                            <div style="padding: 12px; background: #fff3e0; border: 1px solid #ffb74d; border-radius: 4px; margin-bottom: 10px;">
                                <p style="margin: 0; color: #e65100; font-size: 14px;">
                                    <strong>⚠️ No PayPal email set.</strong> You must <a href="<?php echo esc_url(home_url('/modify-paypal-email')); ?>" style="color: #e65100; text-decoration: underline; font-weight: bold;">add your PayPal email</a> before requesting a withdrawal.
                                </p>
                            </div>
                        <?php } ?>
                    </div>

                    <!-- Withdrawal Form -->
                    <?php if ($saved_paypal_email) { ?>
                    <div class="mb-4">
                        <form id="withdrawal-form" class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Withdrawal Amount (USD):</label>
                                <input 
                                    type="number" 
                                    id="amount" 
                                    name="amount" 
                                    class="form-control input"
                                    placeholder="Enter amount"
                                    min="<?php echo $min_amount; ?>"
                                    max="<?php echo min($max_amount, $balance); ?>"
                                    step="0.01"
                                    required
                                />
                                <small class="form-text text-muted">
                                    Min: $<?php echo number_format($min_amount, 2); ?> | Max: $<?php echo number_format(min($max_amount, $balance), 2); ?>
                                </small>
                            </div>

                            <div class="col-12">
                                <div style="background: #e3f2fd; border-left: 4px solid #2196F3; padding: 12px; border-radius: 4px; font-size: 14px;">
                                    <p style="margin: 0;">
                                        ⏱️ Your withdrawal will be processed within 1-3 business days after approval.
                                    </p>
                                </div>
                            </div>

                            <div class="col-12">
                                <button 
                                    type="submit" 
                                    class="w-auto custom-btn"
                                    data-nonce="<?php echo wp_create_nonce('payout_security'); ?>"
                                    data-ajaxurl="<?php echo admin_url('admin-ajax.php'); ?>"
                                >
                                    Request Withdrawal
                                </button>
                            </div>

                            <div id="form-message" style="margin-top: 15px; padding: 12px; border-radius: 4px; display: none;" class="col-12">
                            </div>
                        </form>
                    </div>
                    <?php } ?>

                    <!-- Recent Withdrawals -->
                    <?php if ($withdrawals) { ?>
                    <div class="mt-5">
                        <h6 class="pb-3 text-start portal-title">Recent Withdrawal Requests</h6>
                        <div style="overflow-x: auto;">
                            <table class="table table-sm">
                                <thead>
                                    <tr style="background: #f5f5f5;">
                                        <th style="padding: 10px; text-align: left;">Amount</th>
                                        <th style="padding: 10px; text-align: left;">Status</th>
                                        <th style="padding: 10px; text-align: left;">Requested</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($withdrawals as $withdrawal) { 
                                        $status_color = [
                                            'pending' => '#ff9800',
                                            'approved' => '#2196F3',
                                            'processing' => '#9C27B0',
                                            'paid' => '#27ae60',
                                            'rejected' => '#f44336',
                                            'failed' => '#f44336'
                                        ][$withdrawal->status] ?? '#999';
                                        ?>
                                        <tr style="border-bottom: 1px solid #eee;">
                                            <td style="padding: 10px;">$<?php echo number_format($withdrawal->amount, 2); ?></td>
                                            <td style="padding: 10px;">
                                                <span style="background: <?php echo $status_color; ?>; color: white; padding: 4px 8px; border-radius: 3px; font-size: 12px;">
                                                    <?php echo ucfirst($withdrawal->status); ?>
                                                </span>
                                            </td>
                                            <td style="padding: 10px;">
                                                <?php echo date('M d, Y', strtotime($withdrawal->created_at)); ?>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    console.log('Modify withdrawal form script initializing...');
    
    if (typeof jQuery === 'undefined') {
        console.error('jQuery not loaded!');
        return;
    }
    
    const $ = jQuery;
    
    $(document).ready(function() {
        console.log('Document ready, binding withdrawal form...');
        
        const $form = $('#withdrawal-form');
        const $submitBtn = $form.find('[type="submit"]');
        
        if (!$submitBtn.length) {
            console.error('Submit button not found');
            return;
        }
        
        const nonce = $submitBtn.data('nonce');
        const ajaxurl = $submitBtn.data('ajaxurl');
        
        console.log('Form data:', { nonce, ajaxurl });
        
        if (!nonce || !ajaxurl) {
            console.error('Nonce or AJAX URL not found');
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', 'System not initialized. Please refresh the page.', 'error');
            }
            return;
        }
        
        $form.on('submit', function(e) {
            console.log('Form submitted!');
            e.preventDefault();

            const amount = parseFloat($('#amount').val());

            if (!amount || amount <= 0) {
                Swal.fire('Error', 'Please enter a valid amount', 'error');
                return;
            }

            // Show loading state
            Swal.fire({
                title: 'Processing...',
                html: 'Submitting your withdrawal request...',
                icon: 'info',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            console.log('Sending withdrawal request:', {
                action: 'submit_withdrawal',
                amount: amount,
                nonce: nonce
            });

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'submit_withdrawal',
                    nonce: nonce,
                    amount: amount
                },
                success: function(response) {
                    console.log('AJAX success:', response);
                    if (response.success) {
                        Swal.fire({
                            title: 'Success!',
                            html: response.data.message || 'Withdrawal request submitted successfully',
                            icon: 'success',
                            timer: 2000,
                            timerProgressBar: true,
                            didClose: () => {
                                location.reload();
                            }
                        });
                    } else {
                        const errorMsg = response.data || 'Error occurred';
                        Swal.fire('Error', errorMsg, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', {status, error, xhr});
                    Swal.fire('Error', 'An error occurred. Please try again.', 'error');
                }
            });
        });
        
        console.log('Withdrawal form bound successfully');
    });
})();
</script>

<?php get_footer_based_on_login(); ?>
