<?php
/**
 * Wallet sub-section: Active subscriptions list + cancel button.
 * Rendered via get_template_part() from template-custom/auth/wallet.php
 * when ?wallet_section=subscriptions.
 */
if (!defined('ABSPATH')) exit;

$user_id = get_current_user_id();
$subs    = get_user_meta($user_id, 'active_subscriptions', true);
$subs    = is_array($subs) ? $subs : [];

// newest first
uasort($subs, function ($a, $b) {
    return strcmp($b['started'] ?? '', $a['started'] ?? '');
});

$nonce = wp_create_nonce('mm_sub_cancel');
?>
<div class="bg-white mb-0 custom-card">
    <div class="d-flex align-items-center justify-content-between u-title">
        <h5 class="mb-0 portal-title">My Subscriptions</h5>
    </div>

    <?php if (empty($subs)) : ?>
        <p class="mt-3 mb-0 text-muted">You have no active subscriptions yet.</p>
    <?php else : ?>
        <div class="table-responsive mt-3">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Plan</th>
                        <th>Billing</th>
                        <th>Amount</th>
                        <th>Started</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($subs as $sid => $s):
                    $course_title = get_the_title((int)$s['course_id']) ?: '(deleted)';
                    $status       = $s['status'] ?? 'UNKNOWN';
                    $can_cancel   = in_array($status, ['ACTIVE', 'APPROVED', 'APPROVAL_PENDING'], true);
                    $badge_class  = [
                        'ACTIVE'           => 'bg-success',
                        'APPROVAL_PENDING' => 'bg-warning text-dark',
                        'APPROVED'         => 'bg-info text-dark',
                        'CANCELLED'        => 'bg-secondary',
                        'EXPIRED'          => 'bg-secondary',
                        'SUSPENDED'        => 'bg-danger',
                    ][$status] ?? 'bg-light text-dark';
                ?>
                    <tr>
                        <td><?= esc_html($course_title) ?></td>
                        <td><?= esc_html($s['variation_label'] ?? '') ?></td>
                        <td><?= esc_html(ucfirst($s['billing'] ?? '')) ?></td>
                        <td>$<?= esc_html($s['price'] ?? '') ?></td>
                        <td><?= esc_html($s['started'] ?? '') ?></td>
                        <td><span class="badge <?= esc_attr($badge_class) ?>"><?= esc_html($status) ?></span></td>
                        <td>
                            <?php if ($can_cancel): ?>
                                <button type="button"
                                        class="btn-outline-danger btn btn-sm mm-sub-cancel"
                                        data-sub="<?= esc_attr($sid) ?>">
                                    Cancel
                                </button>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
(function(){
    var nonce = '<?= esc_js($nonce) ?>';
    var ajax  = '<?= esc_js(admin_url('admin-ajax.php')) ?>';

    document.querySelectorAll('.mm-sub-cancel').forEach(function(btn){
        btn.addEventListener('click', function(){
            if (!confirm('Cancel this subscription? You will keep access until the current billing period ends on PayPal.')) return;

            btn.disabled = true;
            btn.textContent = 'Cancelling...';

            fetch(ajax, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'cancel_course_subscription',
                    subscription_id: btn.getAttribute('data-sub'),
                    nonce: nonce
                })
            })
            .then(function(r){ return r.json(); })
            .then(function(res){
                if (res && res.success) {
                    location.reload();
                } else {
                    alert((res && res.data && res.data.message) || 'Cancel failed');
                    btn.disabled = false;
                    btn.textContent = 'Cancel';
                }
            })
            .catch(function(err){
                alert('Network error');
                btn.disabled = false;
                btn.textContent = 'Cancel';
            });
        });
    });
})();
</script>
