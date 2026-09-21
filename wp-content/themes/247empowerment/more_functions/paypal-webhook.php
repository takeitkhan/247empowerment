<?php
/**
 * PayPal Subscription Webhook
 * -----------------------------------------------------------
 * Endpoint: /?mm_paypal_webhook=1
 * Register the URL in the PayPal app dashboard and subscribe to
 * at least these events:
 *   BILLING.SUBSCRIPTION.ACTIVATED
 *   BILLING.SUBSCRIPTION.CANCELLED
 *   BILLING.SUBSCRIPTION.EXPIRED
 *   BILLING.SUBSCRIPTION.SUSPENDED
 *   PAYMENT.SALE.COMPLETED          (per renewal payment)
 *
 * Store the webhook ID once via PayPal admin → settings:
 *   update_option('paypal_webhook_id', 'WH-xxxxxx');
 *
 * Signature is verified against PayPal's /v1/notifications/verify-webhook-signature.
 */

if (!defined('ABSPATH')) exit;

add_action('init', function () {
    if (!isset($_GET['mm_paypal_webhook'])) return;
    mm_pp_webhook_handle();
    exit;
});

function mm_pp_webhook_handle()
{
    $raw = file_get_contents('php://input');
    if (!$raw) {
        status_header(400);
        echo 'empty';
        return;
    }

    $event = json_decode($raw, true);
    if (!is_array($event)) {
        status_header(400);
        echo 'invalid';
        return;
    }

    // --- verify signature ---
    $webhook_id = get_option('paypal_webhook_id');
    if ($webhook_id) {
        $verify = mm_pp_request('POST', '/v1/notifications/verify-webhook-signature', [
            'auth_algo'         => $_SERVER['HTTP_PAYPAL_AUTH_ALGO']         ?? '',
            'cert_url'          => $_SERVER['HTTP_PAYPAL_CERT_URL']          ?? '',
            'transmission_id'   => $_SERVER['HTTP_PAYPAL_TRANSMISSION_ID']   ?? '',
            'transmission_sig'  => $_SERVER['HTTP_PAYPAL_TRANSMISSION_SIG']  ?? '',
            'transmission_time' => $_SERVER['HTTP_PAYPAL_TRANSMISSION_TIME'] ?? '',
            'webhook_id'        => $webhook_id,
            'webhook_event'     => $event,
        ]);
        if (is_wp_error($verify) || ($verify['verification_status'] ?? '') !== 'SUCCESS') {
            status_header(401);
            error_log('[mm-paypal-webhook] signature verify failed');
            echo 'bad signature';
            return;
        }
    }

    $type = $event['event_type'] ?? '';
    $sub  = $event['resource']['id'] ?? '';
    // For PAYMENT.SALE.COMPLETED the subscription id lives in billing_agreement_id
    if (!$sub && isset($event['resource']['billing_agreement_id'])) {
        $sub = $event['resource']['billing_agreement_id'];
    }

    if (!$sub) {
        status_header(204);
        echo 'no subscription id';
        return;
    }

    $user_id = mm_pp_find_user_by_subscription($sub);
    if (!$user_id) {
        status_header(204);
        echo 'unknown subscription';
        return;
    }

    switch ($type) {
        case 'BILLING.SUBSCRIPTION.ACTIVATED':
            mm_pp_update_sub_status($user_id, $sub, 'ACTIVE');
            // Fire notification hook
            do_action('mm_subscription_activated', $user_id, $sub, $event);
            break;

        case 'BILLING.SUBSCRIPTION.CANCELLED':
        case 'BILLING.SUBSCRIPTION.EXPIRED':
        case 'BILLING.SUBSCRIPTION.SUSPENDED':
            $new_status = str_replace('BILLING.SUBSCRIPTION.', '', $type);
            mm_pp_update_sub_status($user_id, $sub, $new_status);
            mm_pp_revoke_course_access_if_no_active_sub($user_id, $sub);
            // Fire notification hook
            do_action('mm_subscription_status_changed', $user_id, $sub, $new_status, $event);
            break;

        case 'PAYMENT.SALE.COMPLETED':
            mm_pp_log_renewal($user_id, $sub, $event['resource']);
            // Fire notification hook for renewal payment
            do_action('mm_subscription_renewed', $user_id, $sub, $event['resource']);
            break;
    }

    status_header(200);
    echo 'ok';
}

/**
 * Find the WP user who owns this PayPal subscription id.
 */
function mm_pp_find_user_by_subscription($subscription_id)
{
    global $wpdb;
    // active_subscriptions user meta is a serialized array keyed by sub id.
    // Search LIKE is acceptable here (subscription ids are unique).
    $like = '%' . $wpdb->esc_like($subscription_id) . '%';
    $row = $wpdb->get_var($wpdb->prepare(
        "SELECT user_id FROM {$wpdb->usermeta}
         WHERE meta_key = 'active_subscriptions' AND meta_value LIKE %s LIMIT 1",
        $like
    ));
    return $row ? (int) $row : 0;
}

function mm_pp_update_sub_status($user_id, $subscription_id, $new_status)
{
    $subs = get_user_meta($user_id, 'active_subscriptions', true);
    if (!is_array($subs) || !isset($subs[$subscription_id])) return;

    $subs[$subscription_id]['status']     = $new_status;
    $subs[$subscription_id]['updated']    = current_time('mysql');
    update_user_meta($user_id, 'active_subscriptions', $subs);

    // log
    $log = get_user_meta($user_id, 'course_purchase_log', true);
    if (!is_array($log)) $log = [];
    $log[] = [
        'type'            => 'subscription_status',
        'subscription_id' => $subscription_id,
        'status'          => $new_status,
        'timestamp'       => current_time('mysql'),
    ];
    update_user_meta($user_id, 'course_purchase_log', $log);
}

function mm_pp_log_renewal($user_id, $subscription_id, $resource)
{
    $log = get_user_meta($user_id, 'course_purchase_log', true);
    if (!is_array($log)) $log = [];
    $log[] = [
        'type'            => 'renewal',
        'subscription_id' => $subscription_id,
        'amount'          => $resource['amount']['total'] ?? '',
        'currency'        => $resource['amount']['currency'] ?? 'USD',
        'txn_id'          => $resource['id'] ?? '',
        'timestamp'       => current_time('mysql'),
    ];
    update_user_meta($user_id, 'course_purchase_log', $log);
}

/**
 * When a subscription ends, if the user has no other ACTIVE subscription for
 * that course AND they never made a one-time purchase of it, remove it from
 * `purchased_courses` so the Buy button reappears.
 */
function mm_pp_revoke_course_access_if_no_active_sub($user_id, $subscription_id)
{
    $subs = get_user_meta($user_id, 'active_subscriptions', true);
    if (!is_array($subs) || !isset($subs[$subscription_id])) return;

    $course_id = (int) $subs[$subscription_id]['course_id'];

    // Any other ACTIVE subscription for this course?
    foreach ($subs as $sid => $s) {
        if ($sid === $subscription_id) continue;
        if ((int) $s['course_id'] === $course_id && ($s['status'] ?? '') === 'ACTIVE') {
            return; // still has access via another sub
        }
    }

    // Did they ever purchase this course one-time?
    $log = get_user_meta($user_id, 'course_purchase_log', true);
    if (is_array($log)) {
        foreach ($log as $entry) {
            if ((($entry['type'] ?? '') !== 'subscription') &&
                (($entry['type'] ?? '') !== 'subscription_status') &&
                (($entry['type'] ?? '') !== 'renewal') &&
                (int) ($entry['course_id'] ?? 0) === $course_id) {
                return; // paid for it, keep access
            }
        }
    }

    // Revoke
    $purchased = get_user_meta($user_id, 'purchased_courses', true);
    if (is_array($purchased)) {
        $purchased = array_values(array_filter($purchased, fn($id) => (int)$id !== $course_id));
        update_user_meta($user_id, 'purchased_courses', $purchased);
    }
}
