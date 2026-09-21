<?php
$balance = $args['balance'] ?? 0;
$current_points = $args['current_points'] ?? 0;
?>
<div class="bg-white custom-card post-search">
    <div class="gap-3 post-row">
        <div>
            <h5 class="pb-4 text-start portal-title">Wallet Overview</h5>
            <p>Track your earnings, referral, and reward points.</p>
        </div>
    </div>
</div>
<div class="">
    <div class="d-flex d-flex-wallet gap20">
        <div class="bg-white mb-0 w-50 custom-card">
            <div>
                <div class="d-flex align-items-center gap-2">
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/img/nd/referral.svg" alt="">
                    <span class="fs20 fw-bold">Wallet</span>
                </div>
                <p class="mt-2">Ask about our $600 Referrals.</p>
                <div class="py-3">
                    <p class="d-flex align-items-center justify-content-between">
                        <span class="fw-medium">Balance:</span>
                        <span class="text-primary-color fs24 fw-bold">
                            $<?= number_format($balance ?? 0, 2); ?>
                        </span>
                    </p>
                </div>
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-primary-color fs18 fw-medium">Transaction</span>
                    <a href="<?php echo esc_url(add_query_arg('wallet_section', 'referral-commission', get_permalink())); ?>" class="custom-btn">View</a>
                    <!-- <form action="withdraw.php" method="post" class="m-0">
                        <button type="submit" class="w-auto custom-btn">Withdraw</button>
                    </form> -->
                </div>
            </div>
        </div>

        <div class="bg-white mb-0 w-50 custom-card">
            <div>
                <div class="d-flex align-items-center gap-2">
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/img/nd/earn.svg" alt="">
                    <span class="fs20 fw-bold">Earned Points</span>
                </div>
                <p class="mt-2">100 Reward Points Equal $10 Redeemable in our Marketplace</p>

                <div class="py-3">
                    <p class="d-flex align-items-center justify-content-between">
                        <span class="fw-medium">Balance:</span>
                        <span class="text-primary-color fs24 fw-bold">
                            <?= esc_html($current_points); ?> pts
                        </span>
                    </p>
                </div>

                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-primary-color fs18 fw-medium">Rewards</span>
                    <a href="<?php echo esc_url(add_query_arg('wallet_section', 'earned-points', get_permalink())); ?>" class="custom-btn">View</a>
                    <!-- <form action="withdraw.php" method="post" class="m-0">
                        <button type="submit" class="w-auto custom-btn">Withdraw</button>
                    </form> -->
                </div>
            </div>
        </div>
    </div>
</div>