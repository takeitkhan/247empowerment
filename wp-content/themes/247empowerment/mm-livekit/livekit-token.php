<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ফায়ারবেস JWT লাইব্রেরি ব্যবহার করা (যা ওয়ার্ডপ্রেসে অলরেডি এভেইলেবল)
use Firebase\JWT\JWT;

function mm_generate_livekit_token( $room_name, $identity, $name ) {
    $api_key    = MM_LIVEKIT_API_KEY;
    $api_secret = MM_LIVEKIT_API_SECRET;

    $issued_at = time();
    $expiration = $issued_at + 3600; // ১ ঘণ্টার মেয়াদ

    $payload = array(
        'iss' => $api_key,
        'sub' => $identity,
        'nbf' => $issued_at,
        'exp' => $expiration,
        'name' => $name,
        'video' => array(
            'room' => $room_name,
            'roomJoin' => true,
            'canPublish' => true,
            'canSubscribe' => true,
        )
    );

    try {
        // Firebase JWT লাইব্রেরি দিয়ে টোকেন সাইন করা
        if ( class_exists( 'Firebase\JWT\JWT' ) ) {
            return JWT::encode( $payload, $api_secret, 'HS256' );
        }
    } catch ( Exception $e ) {
        error_log( 'LiveKit Token Error: ' . $e->getMessage() );
    }

    return '';
}