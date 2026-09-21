<?php
/**
 * REST: portal users + referral partners.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MM_Portal_REST_Users {

    public static function register_routes() {
        register_rest_route( MM_PORTAL_API_NAMESPACE, '/users', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ __CLASS__, 'list_users' ],
            'permission_callback' => [ 'MM_Portal_Formatter', 'permission_logged_in' ],
            'args'                => [
                'page'     => [ 'default' => 1,  'sanitize_callback' => 'absint' ],
                'per_page' => [ 'default' => 20, 'sanitize_callback' => 'absint' ],
                'search'   => [ 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ],
                'interest' => [ 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ],
            ],
        ] );

        register_rest_route( MM_PORTAL_API_NAMESPACE, '/users/(?P<id>\d+)', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ __CLASS__, 'get_user' ],
            'permission_callback' => [ 'MM_Portal_Formatter', 'permission_logged_in' ],
        ] );

        register_rest_route( MM_PORTAL_API_NAMESPACE, '/referrals', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ __CLASS__, 'list_referrals' ],
            'permission_callback' => [ 'MM_Portal_Formatter', 'permission_logged_in' ],
        ] );
    }

    /**
     * GET /users
     */
    public static function list_users( WP_REST_Request $req ) {
        $page     = max( 1, (int) $req->get_param( 'page' ) );
        $per_page = min( 100, max( 1, (int) $req->get_param( 'per_page' ) ) );
        $search   = trim( (string) $req->get_param( 'search' ) );
        $interest = trim( (string) $req->get_param( 'interest' ) );

        $args = [
            'number'  => $per_page,
            'paged'   => $page,
            'orderby' => 'display_name',
            'order'   => 'ASC',
            'exclude' => [ get_current_user_id() ],
        ];

        if ( $search !== '' ) {
            $args['search']         = '*' . esc_attr( $search ) . '*';
            $args['search_columns'] = [ 'user_login', 'user_nicename', 'user_email', 'display_name' ];
        }

        // Filter by interest (category term id or slug saved in `user_categories` meta).
        if ( $interest !== '' ) {
            $term_id = is_numeric( $interest )
                ? (int) $interest
                : ( ( $t = get_term_by( 'slug', $interest, 'category' ) ) ? (int) $t->term_id : 0 );

            if ( $term_id ) {
                $args['meta_query'] = [
                    [
                        'key'     => 'user_categories',
                        'value'   => sprintf( ':%d;', $term_id ), // serialized array safety net
                        'compare' => 'LIKE',
                    ],
                ];
            }
        }

        $query = new WP_User_Query( $args );
        $users = array_map( [ 'MM_Portal_Formatter', 'user' ], $query->get_results() );
        $users = array_values( array_filter( $users ) );

        $total       = (int) $query->get_total();
        $total_pages = $per_page > 0 ? (int) ceil( $total / $per_page ) : 1;

        $response = rest_ensure_response( [
            'users'       => $users,
            'page'        => $page,
            'per_page'    => $per_page,
            'total'       => $total,
            'total_pages' => $total_pages,
        ] );
        $response->header( 'X-WP-Total', (string) $total );
        $response->header( 'X-WP-TotalPages', (string) $total_pages );
        return $response;
    }

    /**
     * GET /users/{id}
     */
    public static function get_user( WP_REST_Request $req ) {
        $id   = (int) $req['id'];
        $data = MM_Portal_Formatter::user_full( $id );
        if ( ! $data ) {
            return new WP_Error( 'rest_user_not_found', __( 'User not found.', 'mm-portal-api' ), [ 'status' => 404 ] );
        }
        return rest_ensure_response( $data );
    }

    /**
     * GET /referrals — users referred by current user (via `referral_partners`
     * meta primarily, with `referrer` fallback). Delegates to UserProfileData
     * when available so we stay consistent with the rest of the site.
     */
    public static function list_referrals() {
        $current = wp_get_current_user();
        $partners = [];

        if ( class_exists( 'UserProfileData' ) ) {
            // Returns enriched array entries with `id`.
            $enriched = UserProfileData::getReferredUsersBy( $current );
            foreach ( (array) $enriched as $row ) {
                $id = is_array( $row ) ? ( $row['id'] ?? null ) : ( $row->ID ?? null );
                if ( $id ) {
                    $partners[ (int) $id ] = true;
                }
            }
        } else {
            // Fallback: read partners meta + meta_query on `referrer`.
            $stored = get_user_meta( $current->ID, 'referral_partners', true );
            if ( is_array( $stored ) ) {
                foreach ( $stored as $id ) {
                    $partners[ (int) $id ] = true;
                }
            }
            $by_meta = get_users( [
                'meta_query' => [
                    [
                        'key'     => 'referrer',
                        'value'   => [ (string) $current->ID, $current->user_login ],
                        'compare' => 'IN',
                    ],
                ],
                'number'     => 500,
                'fields'     => 'ID',
            ] );
            foreach ( $by_meta as $id ) {
                $partners[ (int) $id ] = true;
            }
        }

        unset( $partners[ (int) $current->ID ] );

        $users = array_values( array_filter( array_map(
            [ 'MM_Portal_Formatter', 'user' ],
            array_keys( $partners )
        ) ) );

        // Sort by display name for stable UI ordering.
        usort( $users, fn( $a, $b ) => strcasecmp( $a['display_name'], $b['display_name'] ) );

        return rest_ensure_response( [
            'users' => $users,
            'count' => count( $users ),
        ] );
    }
}
