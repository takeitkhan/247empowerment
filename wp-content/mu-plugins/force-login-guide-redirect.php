<?php
/**
 * Plugin Name: Force Login Redirect to Guide
 * Description: Redirects users to the guide page after successful login.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'login_redirect',
	function ( $redirect_to, $requested_redirect_to, $user ) {
		if ( is_wp_error( $user ) ) {
			return $redirect_to;
		}

		return 'https://personalempowermentteams.me/guide/';
	},
	99,
	3
);
