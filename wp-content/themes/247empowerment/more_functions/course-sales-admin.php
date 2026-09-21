<?php
/**
 * Admin Dashboard: Course Sales Report
 * --------------------------------------------------------------
 * Adds a top-level admin menu "Course Sales" that lists every
 * purchase + subscription across all users.
 *
 * Data sources (user meta):
 *   - course_purchase_log   → appended on every purchase / subscription event
 *   - active_subscriptions  → keyed by PayPal subscription_id (live status)
 *
 * Tabs:
 *   - All Sales (combined, with filters)
 *   - Subscriptions (live PayPal subscription status)
 *   - Per-Course summary (totals)
 */

if (!defined('ABSPATH')) exit;

// -----------------------------------------------------------
// 1. Register menu
// -----------------------------------------------------------
add_action('admin_menu', function () {
    add_menu_page(
        'Course Sales',
        'Course Sales',
        'manage_options',
        'mm-course-sales',
        'mm_course_sales_page',
        'dashicons-cart',
        26
    );
});

// -----------------------------------------------------------
// 2. Aggregate helper: collect every purchase log entry across all users
// -----------------------------------------------------------
function mm_collect_course_sales()
{
    global $wpdb;

    // Get all users who have a purchase log OR an active subscription.
    $user_ids = $wpdb->get_col(
        "SELECT DISTINCT user_id FROM {$wpdb->usermeta}
         WHERE meta_key IN ('course_purchase_log', 'active_subscriptions')"
    );

    $rows = [];

    foreach ($user_ids as $uid) {
        $log = get_user_meta($uid, 'course_purchase_log', true);
        if (!is_array($log)) continue;
        $user = get_userdata($uid);
        if (!$user) continue;

        foreach ($log as $entry) {
            $type = $entry['type'] ?? 'purchase'; // legacy entries without 'type' are one-time
            $rows[] = [
                'timestamp'       => $entry['timestamp'] ?? '',
                'user_id'         => $uid,
                'user_name'       => $user->display_name,
                'user_email'      => $user->user_email,
                'course_id'       => $entry['course_id'] ?? 0,
                'course_title'    => $entry['course_id'] ? (get_the_title((int)$entry['course_id']) ?: '(deleted)') : '',
                'variation_label' => $entry['variation_label'] ?? '',
                'variation_index' => $entry['variation_index'] ?? -1,
                'type'            => $type,
                'billing'         => $entry['billing'] ?? 'onetime',
                'amount'          => $entry['amount'] ?? '',
                'order_id'        => $entry['order_id'] ?? '',
                'subscription_id' => $entry['subscription_id'] ?? '',
                'status'          => $entry['status'] ?? '',
            ];
        }
    }

    // newest first
    usort($rows, function ($a, $b) {
        return strcmp($b['timestamp'], $a['timestamp']);
    });

    return $rows;
}

// -----------------------------------------------------------
// 3. Render page
// -----------------------------------------------------------
function mm_course_sales_page()
{
    if (!current_user_can('manage_options')) return;

    $tab = $_GET['tab'] ?? 'all';
    $base = admin_url('admin.php?page=mm-course-sales');

    // Handle CSV export
    if (isset($_GET['export']) && $_GET['export'] === 'csv' && check_admin_referer('mm_sales_export')) {
        mm_course_sales_export_csv();
        exit;
    }
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Course Sales</h1>
        <a href="<?= esc_url(wp_nonce_url(add_query_arg(['export' => 'csv', 'tab' => $tab], $base), 'mm_sales_export')); ?>"
           class="page-title-action">Export CSV</a>

        <h2 class="nav-tab-wrapper">
            <a href="<?= esc_url(add_query_arg('tab', 'all', $base)); ?>"
               class="nav-tab <?= $tab === 'all' ? 'nav-tab-active' : '' ?>">All Sales</a>
            <a href="<?= esc_url(add_query_arg('tab', 'subscriptions', $base)); ?>"
               class="nav-tab <?= $tab === 'subscriptions' ? 'nav-tab-active' : '' ?>">Active Subscriptions</a>
            <a href="<?= esc_url(add_query_arg('tab', 'by-course', $base)); ?>"
               class="nav-tab <?= $tab === 'by-course' ? 'nav-tab-active' : '' ?>">By Course</a>
        </h2>

        <?php
        if ($tab === 'subscriptions') {
            mm_render_subscriptions_tab();
        } elseif ($tab === 'by-course') {
            mm_render_by_course_tab();
        } else {
            mm_render_all_sales_tab();
        }
        ?>
    </div>
    <?php
}

// -----------------------------------------------------------
// 4. Tab: All Sales
// -----------------------------------------------------------
function mm_render_all_sales_tab()
{
    $rows = mm_collect_course_sales();

    // Filters
    $f_course = isset($_GET['f_course']) ? intval($_GET['f_course']) : 0;
    $f_type   = sanitize_text_field($_GET['f_type'] ?? '');
    $f_user   = sanitize_text_field($_GET['f_user'] ?? '');

    if ($f_course) $rows = array_filter($rows, fn($r) => (int)$r['course_id'] === $f_course);
    if ($f_type)   $rows = array_filter($rows, fn($r) => $r['type'] === $f_type);
    if ($f_user)   $rows = array_filter($rows, function ($r) use ($f_user) {
        $q = strtolower($f_user);
        return strpos(strtolower($r['user_name']), $q) !== false
            || strpos(strtolower($r['user_email']), $q) !== false;
    });

    // Totals (exclude non-financial events)
    $total_amount = 0;
    foreach ($rows as $r) {
        if (in_array($r['type'], ['subscription_status'], true)) continue;
        $total_amount += floatval($r['amount']);
    }

    // Course dropdown options
    $courses = get_posts(['post_type' => 'course', 'numberposts' => -1, 'post_status' => 'publish']);
    ?>
    <form method="get" class="mm-sales-filter" style="margin:15px 0;">
        <input type="hidden" name="page" value="mm-course-sales">
        <input type="hidden" name="tab" value="all">
        <select name="f_course">
            <option value="0">All courses</option>
            <?php foreach ($courses as $c): ?>
                <option value="<?= esc_attr($c->ID) ?>" <?= selected($f_course, $c->ID, false) ?>>
                    <?= esc_html($c->post_title) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="f_type">
            <option value="">All types</option>
            <option value="purchase" <?= selected($f_type, 'purchase', false) ?>>One-time</option>
            <option value="subscription" <?= selected($f_type, 'subscription', false) ?>>Subscription</option>
            <option value="renewal" <?= selected($f_type, 'renewal', false) ?>>Renewal</option>
            <option value="subscription_status" <?= selected($f_type, 'subscription_status', false) ?>>Status change</option>
        </select>
        <input type="search" name="f_user" value="<?= esc_attr($f_user) ?>" placeholder="User name or email">
        <?php submit_button('Filter', 'secondary', '', false); ?>
        <?php if ($f_course || $f_type || $f_user): ?>
            <a class="button" href="<?= esc_url(admin_url('admin.php?page=mm-course-sales&tab=all')) ?>">Reset</a>
        <?php endif; ?>
    </form>

    <p>
        <strong><?= count($rows) ?></strong> record(s).
        Approx gross: <strong>$<?= number_format($total_amount, 2) ?></strong>
    </p>

    <table class="widefat striped">
        <thead>
            <tr>
                <th>Date</th>
                <th>User</th>
                <th>Course</th>
                <th>Variation</th>
                <th>Type</th>
                <th>Amount</th>
                <th>PayPal ID</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="8"><em>No records.</em></td></tr>
        <?php else: foreach ($rows as $r): ?>
            <tr>
                <td><?= esc_html($r['timestamp']) ?></td>
                <td>
                    <a href="<?= esc_url(get_edit_user_link($r['user_id'])) ?>"><?= esc_html($r['user_name']) ?></a><br>
                    <small><?= esc_html($r['user_email']) ?></small>
                </td>
                <td>
                    <?php if ($r['course_id']): ?>
                        <a href="<?= esc_url(get_edit_post_link($r['course_id'])) ?>"><?= esc_html($r['course_title']) ?></a>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td><?= esc_html($r['variation_label']) ?></td>
                <td>
                    <?php
                    $badges = [
                        'purchase'            => 'background:#d1e7dd;color:#0a3622;',
                        'subscription'        => 'background:#cfe2ff;color:#084298;',
                        'renewal'             => 'background:#e7d6ff;color:#3d0a75;',
                        'subscription_status' => 'background:#f8d7da;color:#58151c;',
                    ];
                    $style = $badges[$r['type']] ?? '';
                    ?>
                    <span style="padding:2px 8px;border-radius:3px;font-size:11px;<?= esc_attr($style) ?>">
                        <?= esc_html($r['type']) ?>
                    </span>
                    <?php if ($r['billing'] && $r['billing'] !== 'onetime'): ?>
                        <br><small><?= esc_html($r['billing']) ?></small>
                    <?php endif; ?>
                </td>
                <td><?= $r['amount'] !== '' ? '$' . esc_html($r['amount']) : '—' ?></td>
                <td>
                    <?php if ($r['order_id']): ?>
                        <code><?= esc_html($r['order_id']) ?></code>
                    <?php elseif ($r['subscription_id']): ?>
                        <code><?= esc_html($r['subscription_id']) ?></code>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td><?= esc_html($r['status']) ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    <?php
}

// -----------------------------------------------------------
// 5. Tab: Active Subscriptions (live PayPal status)
// -----------------------------------------------------------
function mm_render_subscriptions_tab()
{
    global $wpdb;
    $user_ids = $wpdb->get_col(
        "SELECT DISTINCT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'active_subscriptions'"
    );

    $rows = [];
    foreach ($user_ids as $uid) {
        $subs = get_user_meta($uid, 'active_subscriptions', true);
        if (!is_array($subs)) continue;
        $user = get_userdata($uid);
        if (!$user) continue;
        foreach ($subs as $sid => $s) {
            $rows[] = array_merge($s, [
                'sub_id'     => $sid,
                'user_id'    => $uid,
                'user_name'  => $user->display_name,
                'user_email' => $user->user_email,
            ]);
        }
    }
    usort($rows, fn($a, $b) => strcmp($b['started'] ?? '', $a['started'] ?? ''));

    $active_count = 0;
    foreach ($rows as $r) if (($r['status'] ?? '') === 'ACTIVE') $active_count++;
    ?>
    <p>
        <strong><?= count($rows) ?></strong> total subscription record(s),
        <strong><?= $active_count ?></strong> currently ACTIVE.
    </p>

    <table class="widefat striped">
        <thead>
            <tr>
                <th>Started</th>
                <th>User</th>
                <th>Course</th>
                <th>Plan</th>
                <th>Billing</th>
                <th>Price</th>
                <th>Status</th>
                <th>Subscription ID</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="8"><em>No subscriptions yet.</em></td></tr>
        <?php else: foreach ($rows as $r): ?>
            <tr>
                <td><?= esc_html($r['started'] ?? '') ?></td>
                <td>
                    <a href="<?= esc_url(get_edit_user_link($r['user_id'])) ?>"><?= esc_html($r['user_name']) ?></a><br>
                    <small><?= esc_html($r['user_email']) ?></small>
                </td>
                <td>
                    <?php $title = get_the_title((int)($r['course_id'] ?? 0)); ?>
                    <a href="<?= esc_url(get_edit_post_link((int)($r['course_id'] ?? 0))) ?>">
                        <?= esc_html($title ?: '(deleted)') ?>
                    </a>
                </td>
                <td><?= esc_html($r['variation_label'] ?? '') ?></td>
                <td><?= esc_html($r['billing'] ?? '') ?></td>
                <td>$<?= esc_html($r['price'] ?? '') ?></td>
                <td>
                    <?php
                    $status = $r['status'] ?? '';
                    $style = [
                        'ACTIVE'    => 'background:#d1e7dd;color:#0a3622;',
                        'CANCELLED' => 'background:#e2e3e5;color:#41464b;',
                        'EXPIRED'   => 'background:#e2e3e5;color:#41464b;',
                        'SUSPENDED' => 'background:#f8d7da;color:#58151c;',
                    ][$status] ?? '';
                    ?>
                    <span style="padding:2px 8px;border-radius:3px;font-size:11px;<?= esc_attr($style) ?>">
                        <?= esc_html($status) ?>
                    </span>
                </td>
                <td><code><?= esc_html($r['sub_id']) ?></code></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    <?php
}

// -----------------------------------------------------------
// 6. Tab: By-course totals
// -----------------------------------------------------------
function mm_render_by_course_tab()
{
    $rows = mm_collect_course_sales();

    $by_course = [];
    foreach ($rows as $r) {
        if (!$r['course_id']) continue;
        if (in_array($r['type'], ['subscription_status'], true)) continue;
        $cid = (int)$r['course_id'];
        if (!isset($by_course[$cid])) {
            $by_course[$cid] = [
                'title'         => $r['course_title'],
                'onetime_count' => 0,
                'sub_count'     => 0,
                'renewals'      => 0,
                'gross'         => 0,
                'buyers'        => [],
            ];
        }
        $by_course[$cid]['gross']     += floatval($r['amount']);
        $by_course[$cid]['buyers'][$r['user_id']] = true;
        if ($r['type'] === 'subscription')  $by_course[$cid]['sub_count']++;
        elseif ($r['type'] === 'renewal')   $by_course[$cid]['renewals']++;
        else                                $by_course[$cid]['onetime_count']++;
    }
    uasort($by_course, fn($a, $b) => $b['gross'] <=> $a['gross']);
    ?>
    <table class="widefat striped">
        <thead>
            <tr>
                <th>Course</th>
                <th>Unique Buyers</th>
                <th>One-time</th>
                <th>Subscriptions</th>
                <th>Renewals</th>
                <th>Gross Revenue</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($by_course)): ?>
            <tr><td colspan="6"><em>No sales yet.</em></td></tr>
        <?php else: foreach ($by_course as $cid => $d): ?>
            <tr>
                <td><a href="<?= esc_url(get_edit_post_link($cid)) ?>"><?= esc_html($d['title']) ?></a></td>
                <td><?= count($d['buyers']) ?></td>
                <td><?= (int)$d['onetime_count'] ?></td>
                <td><?= (int)$d['sub_count'] ?></td>
                <td><?= (int)$d['renewals'] ?></td>
                <td><strong>$<?= number_format($d['gross'], 2) ?></strong></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    <?php
}

// -----------------------------------------------------------
// 7. CSV export
// -----------------------------------------------------------
function mm_course_sales_export_csv()
{
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    $rows = mm_collect_course_sales();

    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="course-sales-' . date('Y-m-d') . '.csv"');

    $out = fopen('php://output', 'w');
    fputcsv($out, [
        'Date', 'User', 'Email', 'Course', 'Variation',
        'Type', 'Billing', 'Amount', 'Order ID', 'Subscription ID', 'Status'
    ]);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['timestamp'], $r['user_name'], $r['user_email'],
            $r['course_title'], $r['variation_label'],
            $r['type'], $r['billing'], $r['amount'],
            $r['order_id'], $r['subscription_id'], $r['status'],
        ]);
    }
    fclose($out);
}
