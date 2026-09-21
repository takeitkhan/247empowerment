<?php
/**
 * Facebook Post Sharing Functions
 * Handles posting to Facebook user's timeline
 */

if ( ! function_exists( 'share_post_to_facebook' ) ) {
    /**
     * Post to Facebook user timeline
     */
    function share_post_to_facebook( $user_id, $post_id, $post_content ) {
        try {
            // Get Facebook token
            if ( ! function_exists( 'get_facebook_token' ) ) {
                error_log( 'Facebook token function not found' );
                return false;
            }

            $token = get_facebook_token( $user_id );
            if ( ! $token ) {
                error_log( 'No Facebook token found for user ' . $user_id );
                return false;
            }

            // Get Facebook user info
            $facebook_user_id = get_user_meta( $user_id, '_facebook_user_id', true );
            if ( ! $facebook_user_id ) {
                error_log( 'No Facebook user ID found for user ' . $user_id );
                return false;
            }

            // Get post data
            $post = get_post( $post_id );
            if ( ! $post ) {
                error_log( 'Post not found: ' . $post_id );
                return false;
            }

            // Prepare message
            $message = wp_strip_all_tags( $post->post_content );
            $message = mb_substr( $message, 0, 500 ) . ( mb_strlen( $message ) > 500 ? '...' : '' );

            // Get featured image URL
            $image_url = '';
            if ( has_post_thumbnail( $post_id ) ) {
                $image_url = get_the_post_thumbnail_url( $post_id, 'large' );
            }

            // Build post URL in WordPress
            $post_url = get_permalink( $post_id );

            // Prepare data for Facebook Graph API
            $post_data = array(
                'message'     => $message,
                'link'        => $post_url,
                'access_token' => $token
            );

            // Add image if available
            if ( $image_url ) {
                $post_data['picture'] = $image_url;
            }

            // Make API call to Facebook
            $response = wp_remote_post(
                'https://graph.facebook.com/v18.0/' . $facebook_user_id . '/feed',
                array(
                    'body'      => $post_data,
                    'timeout'   => 30,
                    'sslverify' => true
                )
            );

            if ( is_wp_error( $response ) ) {
                error_log( 'Facebook API error: ' . $response->get_error_message() );
                return false;
            }

            $body = wp_remote_retrieve_body( $response );
            $result = json_decode( $body, true );

            if ( ! isset( $result['id'] ) ) {
                error_log( 'Facebook post failed. Response: ' . $body );
                return false;
            }

            // Store Facebook post ID in post meta
            update_post_meta( $post_id, '_facebook_post_id', $result['id'] );
            error_log( 'Facebook post successful. ID: ' . $result['id'] );

            return true;

        } catch ( Exception $e ) {
            error_log( 'Exception in share_post_to_facebook: ' . $e->getMessage() );
            return false;
        }
    }
}
