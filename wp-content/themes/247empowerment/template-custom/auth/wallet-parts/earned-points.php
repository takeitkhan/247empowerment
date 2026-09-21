<?php
$points_logs    = $args['points_logs'] ?? [];
$current_points = $args['current_points'] ?? 0;
$page           = $args['page'] ?? 1;
$total_pages    = $args['total_pages'] ?? 1;
?>

<?php if (empty($points_logs)): ?>
    <div class="bg-white mt-4 custom-card">
        <p>You haven't earned any rewards points yet.</p>
    </div>
<?php else: ?>
    <div class="bg-white mt-4 custom-card">
        <div class="post-search">
            <div class="gap-3 post-row">
                <div>
                    <h5 class="pb-4 text-start portal-title"><?php esc_html_e( 'Rewards Points', '247empowerment' ); ?></h5>
                    <p><?php esc_html_e( 'Your referral earning history showing below.', '247empowerment' ); ?></p>
                </div>
            </div>
            <div class="mt-3">
                <div class="d-flex flex-column gap-3">
                    <?php
                    foreach ($points_logs as $log) :
                        $activity_message = $log['activity'] ?? 'Unknown Action';
                        $points_earned    = $log['points'] ?? 0;
                        $date_earned      = $log['date'] ?? 'now';
                        
                        // Replace {points} placeholder, similar to notifications
                        $activity_message = str_replace('{points}', (int)$points_earned, $activity_message);
                    ?>
                        <div class="d-flex justify-content-between bg-light mt-1 p-2 rounded">
                            <div class="d-inline-flex align-items-center gap-2">
                                <span class="gap-5">
                                    <?php echo esc_html($activity_message); ?> - <small class="text-muted"><?php echo esc_html(human_time_diff(strtotime($date_earned), current_time('timestamp'))) . ' ago'; ?></small>
                                </span>
                            </div>
                            <span class="ml-1 text-primary-color text-end fs18 fw-bold">
                                +<?php echo esc_html($points_earned); ?> pts
                            </span>
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