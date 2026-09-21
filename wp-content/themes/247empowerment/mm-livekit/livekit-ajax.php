<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Helper: create a meeting linked to an event post (assumes an events CPT exists)
function mm_livekit_create_meeting( $event_id, $options = array() ) {
    $room_name = isset( $options['room_name'] ) ? sanitize_text_field( $options['room_name'] ) : 'event-' . $event_id . '-' . wp_generate_uuid4();
    $is_video = isset( $options['is_video'] ) ? boolval( $options['is_video'] ) : true;
    $max_participants = isset( $options['max_participants'] ) ? intval( $options['max_participants'] ) : 40;

    update_post_meta( $event_id, '_mm_livekit_room', $room_name );
    update_post_meta( $event_id, '_mm_livekit_is_video', $is_video ? '1' : '0' );
    update_post_meta( $event_id, '_mm_livekit_max_participants', $max_participants );
    update_post_meta( $event_id, '_mm_livekit_host_id', get_current_user_id() );

    // initial reserved count 0
    set_transient( 'mm_livekit_room_' . md5( $room_name ) . '_count', 0, 5 * MINUTE_IN_SECONDS );

    return array(
        'room_name' => $room_name,
        'is_video' => $is_video,
        'max_participants' => $max_participants,
    );
}

// AJAX: create meeting for an event
add_action( 'wp_ajax_mm_create_meeting', function() {
    check_ajax_referer( 'mm_livekit_nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'You must be logged in to create a meeting.' );
    }

    $event_id = isset( $_POST['event_id'] ) ? intval( $_POST['event_id'] ) : 0;
    if ( ! $event_id ) {
        wp_send_json_error( 'Missing event id.' );
    }

    $is_video = isset( $_POST['is_video'] ) ? boolval( $_POST['is_video'] ) : true;
    $max_participants = isset( $_POST['max_participants'] ) ? intval( $_POST['max_participants'] ) : 40;
    $room_name = isset( $_POST['room_name'] ) ? sanitize_text_field( $_POST['room_name'] ) : '';

    $result = mm_livekit_create_meeting( $event_id, array(
        'room_name' => $room_name,
        'is_video' => $is_video,
        'max_participants' => $max_participants,
    ) );

    // mark active immediately when created via AJAX (host-initiated)
    update_post_meta( $event_id, '_mm_livekit_active', '1' );

    wp_send_json_success( $result );
} );

// AJAX: reserve a slot before joining (prevents exceeding configured capacity)
add_action( 'wp_ajax_nopriv_mm_reserve_slot', 'mm_ajax_reserve_slot' );
add_action( 'wp_ajax_mm_reserve_slot', 'mm_ajax_reserve_slot' );
function mm_ajax_reserve_slot() {
    check_ajax_referer( 'mm_livekit_nonce', 'nonce' );

    $room = isset( $_POST['room'] ) ? sanitize_text_field( $_POST['room'] ) : '';
    if ( empty( $room ) ) {
        wp_send_json_error( 'Missing room' );
    }

    $key = 'mm_livekit_room_' . md5( $room ) . '_count';
    $count = intval( get_transient( $key ) );

    // find attached event to get max limit (try postmeta lookup)
    $max = 40; // default
    $posts = get_posts( array(
        'post_type' => 'any',
        'meta_key' => '_mm_livekit_room',
        'meta_value' => $room,
        'posts_per_page' => 1,
        'fields' => 'ids',
    ) );
    if ( ! empty( $posts ) ) {
        $max_meta = get_post_meta( $posts[0], '_mm_livekit_max_participants', true );
        if ( $max_meta ) {
            $max = intval( $max_meta );
        }
    }

    if ( $count >= $max ) {
        wp_send_json_error( 'Room is full' );
    }

    $count++;
    set_transient( $key, $count, 5 * MINUTE_IN_SECONDS );

    wp_send_json_success( array( 'reserved' => true, 'count' => $count, 'max' => $max ) );
}

// AJAX: release slot when leaving
add_action( 'wp_ajax_nopriv_mm_release_slot', 'mm_ajax_release_slot' );
add_action( 'wp_ajax_mm_release_slot', 'mm_ajax_release_slot' );
function mm_ajax_release_slot() {
    check_ajax_referer( 'mm_livekit_nonce', 'nonce' );

    $room = isset( $_POST['room'] ) ? sanitize_text_field( $_POST['room'] ) : '';
    if ( empty( $room ) ) {
        wp_send_json_error( 'Missing room' );
    }

    $key = 'mm_livekit_room_' . md5( $room ) . '_count';
    $count = intval( get_transient( $key ) );
    if ( $count > 0 ) {
        $count--;
        set_transient( $key, $count, 5 * MINUTE_IN_SECONDS );
    }

    wp_send_json_success( array( 'released' => true, 'count' => $count ) );
}

// AJAX: save YouTube broadcast settings (host only)
add_action( 'wp_ajax_mm_save_broadcast', function() {
    check_ajax_referer( 'mm_livekit_nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Must be logged in' );
    }

    $event_id = isset( $_POST['event_id'] ) ? intval( $_POST['event_id'] ) : 0;
    $rtmp_url = isset( $_POST['rtmp_url'] ) ? esc_url_raw( $_POST['rtmp_url'] ) : '';
    $enabled = isset( $_POST['enabled'] ) ? boolval( $_POST['enabled'] ) : false;

    if ( ! $event_id ) {
        wp_send_json_error( 'Missing event id' );
    }

    update_post_meta( $event_id, '_mm_livekit_rtmp_url', $rtmp_url );
    update_post_meta( $event_id, '_mm_livekit_rtmp_enabled', $enabled ? '1' : '0' );

    wp_send_json_success( array( 'saved' => true ) );
} );

// Utility: return meeting meta for an event or room
function mm_livekit_get_meeting_by_event_or_room( $identifier ) {
    // if identifier is numeric treat as post id
    if ( is_numeric( $identifier ) ) {
        $post_id = intval( $identifier );
        $room = get_post_meta( $post_id, '_mm_livekit_room', true );
        return array(
            'post_id' => $post_id,
            'room' => $room,
            'is_video' => get_post_meta( $post_id, '_mm_livekit_is_video', true ),
            'max' => get_post_meta( $post_id, '_mm_livekit_max_participants', true ),
        );
    }

    // otherwise search for post with matching room
    $posts = get_posts( array(
        'post_type' => 'any',
        'meta_key' => '_mm_livekit_room',
        'meta_value' => sanitize_text_field( $identifier ),
        'posts_per_page' => 1,
        'fields' => 'ids',
    ) );
    if ( ! empty( $posts ) ) {
        $post_id = $posts[0];
        return mm_livekit_get_meeting_by_event_or_room( $post_id );
    }

    return null;
}

// Activation handler for scheduled meetings (called by WP cron)
add_action( 'mm_livekit_activate_meeting', function( $post_id ) {
    $post_id = intval( $post_id );
    if ( ! $post_id ) return;

    $requested_room = get_post_meta( $post_id, '_mm_livekit_room_requested', true );
    $is_video = get_post_meta( $post_id, '_mm_livekit_is_video', true );
    $max = get_post_meta( $post_id, '_mm_livekit_max_participants', true );

    $options = array(
        'room_name' => $requested_room ?: '',
        'is_video' => $is_video === '1' ? true : false,
        'max_participants' => $max ? intval( $max ) : 40,
    );

    $res = mm_livekit_create_meeting( $post_id, $options );
    if ( ! empty( $res['room_name'] ) ) {
        update_post_meta( $post_id, '_mm_livekit_active', '1' );
        update_post_meta( $post_id, '_mm_livekit_room', $res['room_name'] );
    }
} );

// AJAX: check meeting status by event_id or room
add_action( 'wp_ajax_nopriv_mm_check_meeting_status', 'mm_ajax_check_meeting_status' );
add_action( 'wp_ajax_mm_check_meeting_status', 'mm_ajax_check_meeting_status' );
function mm_ajax_check_meeting_status() {
    check_ajax_referer( 'mm_livekit_nonce', 'nonce' );

    $event_id = isset( $_POST['event_id'] ) ? intval( $_POST['event_id'] ) : 0;
    $room = isset( $_POST['room'] ) ? sanitize_text_field( $_POST['room'] ) : '';

    $data = array();
    if ( $event_id ) {
        $data['event_id'] = $event_id;
        $data['room'] = get_post_meta( $event_id, '_mm_livekit_room', true );
        $data['requested_room'] = get_post_meta( $event_id, '_mm_livekit_room_requested', true );
        $data['active'] = get_post_meta( $event_id, '_mm_livekit_active', true ) === '1' ? 1 : 0;
        $data['scheduled'] = get_post_meta( $event_id, '_mm_livekit_scheduled', true ) === '1' ? 1 : 0;
        $data['scheduled_for'] = get_post_meta( $event_id, '_mm_livekit_scheduled_for', true );
        $data['host_id'] = get_post_meta( $event_id, '_mm_livekit_host_id', true );
        $data['max'] = get_post_meta( $event_id, '_mm_livekit_max_participants', true );
    } elseif ( $room ) {
        $meeting = mm_livekit_get_meeting_by_event_or_room( $room );
        if ( $meeting ) {
            $data = $meeting;
            $data['active'] = get_post_meta( $meeting['post_id'], '_mm_livekit_active', true ) === '1' ? 1 : 0;
            $data['scheduled'] = get_post_meta( $meeting['post_id'], '_mm_livekit_scheduled', true ) === '1' ? 1 : 0;
            $data['scheduled_for'] = get_post_meta( $meeting['post_id'], '_mm_livekit_scheduled_for', true );
            $data['host_id'] = get_post_meta( $meeting['post_id'], '_mm_livekit_host_id', true );
        }
    }

    wp_send_json_success( $data );
}

// AJAX: book (register) for an event
add_action( 'wp_ajax_mm_book_event', 'mm_ajax_book_event' );
function mm_ajax_book_event() {
    check_ajax_referer( 'mm_livekit_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'login_required' );
    }
    $user_id = get_current_user_id();
    $event_id = isset( $_POST['event_id'] ) ? intval( $_POST['event_id'] ) : 0;
    if ( ! $event_id ) wp_send_json_error( 'missing_event' );

    // Check if already registered
    $existing = get_post_meta( $event_id, '_event_attendee_id', false );
    if ( in_array( $user_id, $existing ) ) {
        wp_send_json_success( array( 'already' => true ) );
    }

    // check capacity if livekit meeting
    $max = get_post_meta( $event_id, '_mm_livekit_max_participants', true );
    if ( $max ) {
        $count_registered = count( $existing );
        if ( $count_registered >= intval( $max ) ) {
            wp_send_json_error( 'full' );
        }
    }

    add_post_meta( $event_id, '_event_attendee_id', $user_id );

    wp_send_json_success( array( 'booked' => true ) );
}

// AJAX: cancel booking
add_action( 'wp_ajax_mm_cancel_booking', 'mm_ajax_cancel_booking' );
function mm_ajax_cancel_booking() {
    check_ajax_referer( 'mm_livekit_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'login_required' );
    }
    $user_id = get_current_user_id();
    $event_id = isset( $_POST['event_id'] ) ? intval( $_POST['event_id'] ) : 0;
    if ( ! $event_id ) wp_send_json_error( 'missing_event' );

    $existing = get_post_meta( $event_id, '_event_attendee_id', false );
    if ( ! in_array( $user_id, $existing ) ) {
        wp_send_json_error( 'not_registered' );
    }

    // remove one instance of the user id meta
    delete_post_meta( $event_id, '_event_attendee_id', $user_id );

    wp_send_json_success( array( 'cancelled' => true ) );
}
