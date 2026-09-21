<?php
/**
 * REST: 1:1 chats — list, start, send, history, mark-read, long-poll.
 *
 * Storage layer is reused from the `mm-referral-chat` plugin (tables
 * {prefix}chat_conversations / {prefix}chat_messages). If that plugin is not
 * active, this file degrades gracefully: routes return a 503 with a clear
 * "chat backend not available" error so the front-end can surface it.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MM_Portal_REST_Chats {

    public static function register_routes() {
        $ns = MM_PORTAL_API_NAMESPACE;
        $perm = [ 'MM_Portal_Formatter', 'permission_logged_in' ];

        register_rest_route( $ns, '/chats', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ __CLASS__, 'list_chats' ],
                'permission_callback' => $perm,
                'args'                => [
                    'page'     => [ 'default' => 1,  'sanitize_callback' => 'absint' ],
                    'per_page' => [ 'default' => 20, 'sanitize_callback' => 'absint' ],
                ],
            ],
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ __CLASS__, 'start_chat' ],
                'permission_callback' => $perm,
                'args'                => [
                    'user_id' => [ 'required' => true, 'sanitize_callback' => 'absint' ],
                ],
            ],
        ] );

        register_rest_route( $ns, '/chats/unread-count', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ __CLASS__, 'unread_count' ],
            'permission_callback' => $perm,
        ] );

        register_rest_route( $ns, '/chats/(?P<id>\d+)/messages', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ __CLASS__, 'get_messages' ],
                'permission_callback' => $perm,
                'args'                => [
                    'limit'     => [ 'default' => 50, 'sanitize_callback' => 'absint' ],
                    'before_id' => [ 'default' => 0,  'sanitize_callback' => 'absint' ],
                    'since_id'  => [ 'default' => 0,  'sanitize_callback' => 'absint' ],
                    'wait'      => [ 'default' => 0,  'sanitize_callback' => 'absint' ],
                ],
            ],
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ __CLASS__, 'send_message' ],
                'permission_callback' => $perm,
                'args'                => [
                    'message' => [ 'required' => true, 'sanitize_callback' => 'sanitize_textarea_field' ],
                ],
            ],
        ] );

        register_rest_route( $ns, '/chats/(?P<id>\d+)/read', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [ __CLASS__, 'mark_read' ],
            'permission_callback' => $perm,
        ] );
    }

    /* -------------------------------------------------------------------
     * Endpoints
     * ------------------------------------------------------------------- */

    public static function list_chats( WP_REST_Request $req ) {
        if ( $err = self::ensure_backend() ) { return $err; }

        $page     = max( 1, (int) $req->get_param( 'page' ) );
        $per_page = min( 100, max( 1, (int) $req->get_param( 'per_page' ) ) );
        $offset   = ( $page - 1 ) * $per_page;
        $uid      = get_current_user_id();

        $rows = MM_Chat_Database::get_user_conversations( $uid, $per_page, $offset );
        $out  = [];
        foreach ( (array) $rows as $row ) {
            $other = MM_Portal_Formatter::user( (int) $row->other_user_id );
            if ( ! $other ) {
                continue;
            }
            $last = $row->last_message ?? null;
            $out[] = [
                'id'           => (int) $row->id,
                'other_user'   => $other,
                'last_message' => $last ? [
                    'id'         => (int) $last->id,
                    'sender_id'  => (int) $last->sender_id,
                    'text'       => mb_substr( (string) $last->message, 0, 140 ),
                    'created_at' => $last->created_at,
                    'is_mine'    => ( (int) $last->sender_id === $uid ),
                ] : null,
                'unread_count' => (int) ( $row->unread_count ?? 0 ),
                'updated_at'   => $row->updated_at,
            ];
        }

        return rest_ensure_response( [
            'chats'    => $out,
            'page'     => $page,
            'per_page' => $per_page,
            'count'    => count( $out ),
        ] );
    }

    public static function start_chat( WP_REST_Request $req ) {
        if ( $err = self::ensure_backend() ) { return $err; }

        $me    = get_current_user_id();
        $other = (int) $req->get_param( 'user_id' );

        if ( $other <= 0 || $other === $me || ! get_user_by( 'id', $other ) ) {
            return new WP_Error( 'rest_invalid_user', __( 'Invalid user.', 'mm-portal-api' ), [ 'status' => 400 ] );
        }

        /**
         * Allow site code to restrict who can initiate a chat (e.g. only
         * referral partners). Return false to deny.
         *
         * @param bool $allowed   Default true.
         * @param int  $me
         * @param int  $other
         */
        $allowed = apply_filters( 'mm_portal_api_can_chat', true, $me, $other );
        if ( ! $allowed ) {
            return new WP_Error( 'rest_chat_forbidden', __( 'You cannot start a chat with this user.', 'mm-portal-api' ), [ 'status' => 403 ] );
        }

        $conv = MM_Chat_Database::get_or_create_conversation( $me, $other );
        if ( ! $conv ) {
            return new WP_Error( 'rest_chat_create_failed', __( 'Could not start chat.', 'mm-portal-api' ), [ 'status' => 500 ] );
        }

        return rest_ensure_response( [
            'id'         => (int) $conv->id,
            'other_user' => MM_Portal_Formatter::user( $other ),
            'created_at' => $conv->created_at,
            'updated_at' => $conv->updated_at,
        ] );
    }

    public static function get_messages( WP_REST_Request $req ) {
        if ( $err = self::ensure_backend() ) { return $err; }

        $conv_id  = (int) $req['id'];
        $uid      = get_current_user_id();
        $limit    = min( 200, max( 1, (int) $req->get_param( 'limit' ) ) );
        $before   = (int) $req->get_param( 'before_id' );
        $since_id = (int) $req->get_param( 'since_id' );
        $wait     = min( 25, max( 0, (int) $req->get_param( 'wait' ) ) );

        if ( ! self::user_in_conversation( $conv_id, $uid ) ) {
            return new WP_Error( 'rest_forbidden', __( 'Not a participant of this chat.', 'mm-portal-api' ), [ 'status' => 403 ] );
        }

        // --- Realtime mode: long-poll for messages newer than since_id. -----
        if ( $since_id > 0 ) {
            $deadline = time() + $wait;
            $sleep_ms = 1500; // poll DB every 1.5s
            do {
                $rows = self::fetch_messages_since( $conv_id, $since_id, $limit );
                if ( ! empty( $rows ) ) {
                    return rest_ensure_response( [
                        'messages' => array_map( [ __CLASS__, 'format_message' ], $rows ),
                        'mode'     => 'poll',
                    ] );
                }
                if ( $wait <= 0 || time() >= $deadline ) {
                    break;
                }
                if ( connection_aborted() ) {
                    break;
                }
                usleep( $sleep_ms * 1000 );
            } while ( true );

            return rest_ensure_response( [ 'messages' => [], 'mode' => 'poll' ] );
        }

        // --- History mode: paginate backwards using before_id. --------------
        $rows = self::fetch_messages_history( $conv_id, $before, $limit );

        // Side effect: mark conversation read for the viewer.
        MM_Chat_Database::mark_conversation_as_read( $conv_id, $uid );

        return rest_ensure_response( [
            'messages' => array_map( [ __CLASS__, 'format_message' ], $rows ),
            'mode'     => 'history',
            'has_more' => count( $rows ) === $limit,
        ] );
    }

    public static function send_message( WP_REST_Request $req ) {
        if ( $err = self::ensure_backend() ) { return $err; }

        $conv_id = (int) $req['id'];
        $uid     = get_current_user_id();
        $text    = trim( (string) $req->get_param( 'message' ) );

        if ( $text === '' ) {
            return new WP_Error( 'rest_invalid_message', __( 'Message is empty.', 'mm-portal-api' ), [ 'status' => 400 ] );
        }
        if ( ! self::user_in_conversation( $conv_id, $uid ) ) {
            return new WP_Error( 'rest_forbidden', __( 'Not a participant of this chat.', 'mm-portal-api' ), [ 'status' => 403 ] );
        }

        $result = MM_Message_Handler::send_message( $conv_id, $uid, $text );
        if ( empty( $result['success'] ) ) {
            return new WP_Error( 'rest_send_failed', $result['error'] ?? __( 'Send failed.', 'mm-portal-api' ), [ 'status' => 500 ] );
        }

        do_action( 'mm_portal_api_message_sent', $conv_id, $uid, $result['message'] );

        return rest_ensure_response( [
            'message' => $result['message'],
        ] );
    }

    public static function mark_read( WP_REST_Request $req ) {
        if ( $err = self::ensure_backend() ) { return $err; }

        $conv_id = (int) $req['id'];
        $uid     = get_current_user_id();
        if ( ! self::user_in_conversation( $conv_id, $uid ) ) {
            return new WP_Error( 'rest_forbidden', __( 'Not a participant of this chat.', 'mm-portal-api' ), [ 'status' => 403 ] );
        }
        MM_Chat_Database::mark_conversation_as_read( $conv_id, $uid );
        return rest_ensure_response( [ 'success' => true ] );
    }

    public static function unread_count() {
        if ( $err = self::ensure_backend() ) { return $err; }
        return rest_ensure_response( [
            'unread_count' => (int) MM_Chat_Database::get_total_unread_count( get_current_user_id() ),
        ] );
    }

    /* -------------------------------------------------------------------
     * Internals
     * ------------------------------------------------------------------- */

    /**
     * Verifies the storage plugin (`mm-referral-chat`) is loaded.
     */
    private static function ensure_backend() {
        if (
            class_exists( 'MM_Chat_Database' ) &&
            class_exists( 'MM_Message_Handler' )
        ) {
            return null;
        }
        return new WP_Error(
            'rest_chat_backend_unavailable',
            __( 'Chat backend (mm-referral-chat plugin) is not active.', 'mm-portal-api' ),
            [ 'status' => 503 ]
        );
    }

    private static function user_in_conversation( $conv_id, $uid ) {
        global $wpdb;
        $table = $wpdb->prefix . 'chat_conversations';
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT user_1_id, user_2_id FROM $table WHERE id = %d",
            $conv_id
        ) );
        if ( ! $row ) {
            return false;
        }
        return ( (int) $row->user_1_id === (int) $uid ) || ( (int) $row->user_2_id === (int) $uid );
    }

    private static function fetch_messages_since( $conv_id, $since_id, $limit ) {
        global $wpdb;
        $table = $wpdb->prefix . 'chat_messages';
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $table
              WHERE conversation_id = %d AND id > %d
              ORDER BY id ASC
              LIMIT %d",
            $conv_id, $since_id, $limit
        ) );
    }

    private static function fetch_messages_history( $conv_id, $before_id, $limit ) {
        global $wpdb;
        $table = $wpdb->prefix . 'chat_messages';
        if ( $before_id > 0 ) {
            $rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM $table
                  WHERE conversation_id = %d AND id < %d
                  ORDER BY id DESC
                  LIMIT %d",
                $conv_id, $before_id, $limit
            ) );
        } else {
            $rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM $table
                  WHERE conversation_id = %d
                  ORDER BY id DESC
                  LIMIT %d",
                $conv_id, $limit
            ) );
        }
        // Return ascending so the client can append directly.
        return array_reverse( (array) $rows );
    }

    private static function format_message( $row ) {
        $uid = get_current_user_id();
        return [
            'id'              => (int) $row->id,
            'conversation_id' => (int) $row->conversation_id,
            'sender_id'       => (int) $row->sender_id,
            'is_mine'         => ( (int) $row->sender_id === $uid ),
            'text'            => wp_kses_post( $row->message ),
            'is_read'         => (bool) $row->is_read,
            'read_at'         => $row->read_at,
            'created_at'      => $row->created_at,
        ];
    }
}
