<?php
$logs        = $args['logs'] ?? [];
$balance     = $args['balance'] ?? 0;
$page        = $args['page'] ?? 1;
$total_pages = $args['total_pages'] ?? 1; // default to 1 to avoid warnings
?>

<?php if (empty($logs)): ?>
    <div class="bg-white mt-4 custom-card">
        <p>You haven't earned any referral commissions yet.</p>
    </div>
<?php else: ?>
    <div class="bg-white mt-4 custom-card">
        <div class="post-search">
            <div class="gap-3 post-row">
                <div>
                    <h5 class="pb-4 text-start portal-title">Referral Commissions</h5>
                    <p>Your referral earning history showing below.</p>
                </div>
            </div>
            <div class="mt-3">
                <div class="d-flex flex-column gap-3">
                    <?php foreach (array_reverse($logs) as $log):
                        $referred_user = get_user_by('ID', $log['referred_user_id']);
                        // echo '<pre>';
                        // var_dump($referred_user);
                        // echo '</pre>';
                    ?>
                        <?php
                        // Get user slug (profile owner)
                        $current_user = get_userdata(get_current_user_id());
                        $user_slug = $current_user ? ($current_user->user_nicename ?: $current_user->user_login) : ($profile['user_nicename'] ?? ($profile['username'] ?? ''));

                        // Get post slug
                        $post = get_post($log['earned_for_id']);
                        $post_slug = $post ? $post->post_name : '';

                        // Build the full profile link
                        $profile_link = home_url("/u/{$user_slug}/store/{$post_slug}/");

                        $referred_slug = '';
                        if ($referred_user instanceof WP_User) {
                            $referred_slug = $referred_user->user_nicename ?: $referred_user->user_login;
                        }
                        ?>


                        <div class="d-flex justify-content-between bg-light mt-1 p-2 rounded">
                            <div class="d-inline-flex align-items-center gap-2">
                                <!-- <img class="img24" src="<?php //echo get_template_directory_uri(); 
                                                                ?>/assets/img/nd/user.svg" alt=""> -->
                                <span class="gap-5">
                                    Your referred user <a href="<?= esc_url(home_url('/u/' . $referred_slug . '/')); ?>">
                                        <?= esc_html($referred_user ? $referred_user->display_name : 'User #' . $log['referred_user_id']); ?>
                                    </a> purchased a course (
                                    <a href="<?= esc_url($profile_link); ?>">
                                        <?= esc_html($log['earned_for']); ?>
                                    </a>
                                    ) from 24/7 Empowerment's store at <?= esc_html(date('F j, Y H:i', strtotime($log['date']))); ?>
                                </span>
                            </div>
                            <span class="ml-1 text-primary-color text-end fs18 fw-bold">+$<?= number_format((float)$log['amount'], 2); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php if ($total_pages > 1): ?>
    <div class="d-flex justify-content-center gap-2 mt-3">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?ref_page=<?= $i ?>"
                class="px-3 py-1 border rounded <?= $i == $page ? 'bg-primary text-white' : 'bg-white' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
<?php endif; ?>