<?php
/* Template Name: Referral Assets */

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url());
    exit;
}

get_header_based_on_login();

$user = wp_get_current_user();
$referral_link = add_query_arg('ref', $user->ID, home_url('/register'));
?>

<div class="pt-5 text-center container">
    <h3>Your Referral Assets</h3>
    <p>Share your referral digitally or offline</p>

    <div class="mt-4 p-4 card">
        <p><strong>Referral Link</strong></p>
        <input type="text" class="text-center form-control" value="<?= esc_url($referral_link); ?>" readonly>

        <div class="d-flex justify-content-center gap-3 mt-4">
            <a href="<?= home_url('/download-referral-card'); ?>" class="btn btn-primary">
                Download Digital Card (JPG)
            </a>

            <a href="<?= home_url('/download-referral-qr'); ?>" class="btn-outline-primary btn">
                Download QR Code (PNG)
            </a>
        </div>
    </div>
</div>

<?php get_footer_based_on_login(); ?>