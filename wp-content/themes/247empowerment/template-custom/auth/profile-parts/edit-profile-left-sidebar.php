<?php $current_url = home_url(add_query_arg(array(), $wp->request)); ?>

<div class="d-md-block bottom-0 position-sticky col-md-3 d-none">
    <div class="bg-white shadow p-4 rounded" style="height: 90vh; overflow-y: auto;">
        <h5 class="mb-4 text-primary-color fw-semibold">Profile Navigation</h5>
        <ul class="list-group list-group-flush">
            <li class="list-group-item mb-2 px-0 border-0">
                <a href="<?php echo esc_url(home_url('/modify-profile')); ?>" class="d-flex align-items-center text-decoration-none <?php echo (strpos($current_url, '/modify-profile') !== false) ? 'active-nav' : 'text-dark'; ?>">
                    <i class="me-2 bi bi-pencil-fill"></i> Update Profile
                </a>
            </li>
            <li class="list-group-item mb-2 px-0 border-0">
                <a href="<?php echo esc_url(home_url('/modify-links')); ?>" class="d-flex align-items-center text-decoration-none <?php echo (strpos($current_url, '/modify-links') !== false) ? 'active-nav' : 'text-dark'; ?>">
                    <i class="me-2 bi bi-link-45deg"></i> Manage Links
                </a>
            </li>
            <li class="list-group-item mb-2 px-0 border-0">
                <a href="<?php echo esc_url(home_url('/wallet')); ?>" class="d-flex align-items-center text-decoration-none <?php echo (strpos($current_url, '/wallet') !== false) ? 'active-nav' : 'text-dark'; ?>">
                    <i class="me-2 bi bi-wallet-fill"></i> My Wallet
                </a>
            </li>
            <li class="list-group-item mb-2 px-0 border-0">
                <a href="<?php echo esc_url(home_url('/connect-zoom')); ?>" class="d-flex align-items-center text-decoration-none <?php echo (strpos($current_url, '/connect-zoom') !== false) ? 'active-nav' : 'text-dark'; ?>">
                    <i class="me-2 bi bi-camera-video-fill"></i> My Meetings
                </a>
            </li>
            <li class="list-group-item px-0 border-0">
                <a href="<?php echo esc_url(home_url('/change-password')); ?>" class="d-flex align-items-center text-decoration-none <?php echo (strpos($current_url, '/change-password') !== false) ? 'active-nav' : 'text-dark'; ?>">
                    <i class="me-2 bi bi-shield-lock-fill"></i> Change Password
                </a>
            </li>
        </ul>
    </div>
</div>
