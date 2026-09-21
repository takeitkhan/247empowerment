<?php
/**
 * Shared user shape used by every endpoint. Builds the "basic profile info"
 * envelope from existing meta keys (avatar, interests, referrer, profile data).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MM_Portal_Formatter {

    /**
     * Build a compact user object suitable for list payloads.
     *
     * @param int|WP_User $user
     * @return array|null
     */
    public static function user( $user ) {
        if ( is_numeric( $user ) ) {
            $user = get_user_by( 'id', (int) $user );
        }
        if ( ! $user instanceof WP_User ) {
            return null;
        }

        $uid = (int) $user->ID;

        return [
            'id'              => $uid,
            'username'        => $user->user_login,
            'display_name'    => $user->display_name,
            'first_name'      => get_user_meta( $uid, 'first_name', true ),
            'last_name'       => get_user_meta( $uid, 'last_name', true ),
            'email'           => self::maybe_email( $user ),
            'bio'             => $user->description,
            'designation'     => (string) get_user_meta( $uid, 'designation', true ),
            'location'        => (string) get_user_meta( $uid, 'place_address', true ),
            'avatar_url'      => self::avatar_url( $uid ),
            'profile_url'     => home_url( '/' . $user->user_login ),
            'interests'       => self::interests( $uid ),
            'registered_date' => $user->user_registered,
        ];
    }

    /**
     * Expanded profile shape (used by GET /users/{id} or detail screens).
     * Delegates to UserProfileData when available for maximum fidelity.
     */
    public static function user_full( $user ) {
        if ( is_numeric( $user ) ) {
            $user = get_user_by( 'id', (int) $user );
        }
        if ( ! $user instanceof WP_User ) {
            return null;
        }

        $base = self::user( $user );

        if ( class_exists( 'UserProfileData' ) ) {
            $profile = ( new UserProfileData( $user ) )->getProfile();
            // Drop the heavy/HTML/secret fields, keep useful extras.
            unset(
                $profile['referred_users'],
                $profile['referred_users_html'],
                $profile['esp_state_google'],
                $profile['esp_state_facebook'],
                $profile['esp_state_linkedin'],
                $profile['esp_state_instagram'],
                $profile['paypal_email']
            );
            return array_merge( $base, [ 'profile' => $profile ] );
        }

        return $base;
    }

    /**
     * Resolve avatar URL preferring (in order):
     * 1. Custom uploaded `profile_photo` meta
     * 2. `mm_spg_avatar` selection (male/female) shipped with mm-spg plugin
     * 3. Gravatar fallback
     */
    public static function avatar_url( $user_id ) {
        $custom = get_user_meta( $user_id, 'profile_photo', true );
        if ( ! empty( $custom ) ) {
            return esc_url_raw( $custom );
        }

        $spg = get_user_meta( $user_id, 'mm_spg_avatar', true );
        if ( $spg && defined( 'MM_SPG_URL' ) ) {
            if ( $spg === 'male' ) {
                return MM_SPG_URL . 'inc/frontend/assets/images/male_avatar.png';
            }
            if ( $spg === 'female' ) {
                return MM_SPG_URL . 'inc/frontend/assets/images/female_avatar.png';
            }
        }

        return get_avatar_url( $user_id, [ 'size' => 128 ] );
    }

    /**
     * Selected interests as [{ id, name, slug, priority }] sorted by priority.
     */
    public static function interests( $user_id ) {
        $term_ids = get_user_meta( $user_id, 'user_categories', true );
        if ( empty( $term_ids ) || ! is_array( $term_ids ) ) {
            return [];
        }
        $priority = get_user_meta( $user_id, 'user_categories_priority', true );
        $priority = is_array( $priority ) ? $priority : [];

        $out = [];
        foreach ( $term_ids as $tid ) {
            $tid  = (int) $tid;
            $term = get_term( $tid, 'category' );
            if ( ! $term || is_wp_error( $term ) ) {
                continue;
            }
            $out[] = [
                'id'       => $tid,
                'name'     => $term->name,
                'slug'     => $term->slug,
                'priority' => isset( $priority[ $tid ] ) ? (int) $priority[ $tid ] : null,
            ];
        }

        usort( $out, function ( $a, $b ) {
            $ap = $a['priority'] ?? PHP_INT_MAX;
            $bp = $b['priority'] ?? PHP_INT_MAX;
            return $ap <=> $bp;
        } );

        return $out;
    }

    /**
     * Respect the `show_email` user meta flag.
     */
    private static function maybe_email( WP_User $user ) {
        if ( (int) get_current_user_id() === (int) $user->ID ) {
            return $user->user_email;
        }
        $show = get_user_meta( $user->ID, 'show_email', true );
        return $show ? $user->user_email : null;
    }

    /**
     * Standardised permission check: must be logged in.
     *
     * Runs inside the route's `permission_callback`, which fires immediately
     * before the route callback — nothing else can intervene between the two.
     * If `is_user_logged_in()` is already true (cookies / app password /
     * upstream auth filter) we accept. Otherwise we resolve the mm-spg token
     * here and call `wp_set_current_user()` so the route callback sees the
     * user as logged in.
     *
     * This is intentionally redundant with the `rest_authentication_errors`
     * filter in `auth.php` — some hosts (caching/security plugins) reset
     * `$current_user` between those filters and `permission_callback`. Doing
     * the resolution here as well guarantees the user sticks.
     */
    public static function permission_logged_in() {
        if ( is_user_logged_in() ) {
            return true;
        }
        if ( function_exists( 'mm_portal_api_resolve_token' ) ) {
            $resolved = mm_portal_api_resolve_token();
            if ( $resolved > 0 ) {
                wp_set_current_user( (int) $resolved );
                return true;
            }
        }
        return new WP_Error(
            'rest_forbidden',
            __( 'You must be logged in. Send the mm-spg login nonce as ?nonce=<token>, header X-MM-Nonce, or sign in via the browser.', 'mm-portal-api' ),
            [ 'status' => 401 ]
        );
    }
}
