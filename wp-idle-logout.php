<?php
/*
	Plugin Name: Idle Logout
	Description: Automatically logs out inactive users.
	Version: 1.0.0
	Author: Cooper Dukes @INNEO
	Author URI: http://inneosg.com/
	License: GPL2

	Copyright 2013 Cooper Dukes (email : wp@cooperdukes.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define( 'WP_IDLE_LOGOUT_MAX_INACTIVITY_SECONDS', 60*60 );

/**
 * Checks for User Idleness
 *
 * Tests if the user is logged in on 'init'.
 * If true, checks if the 'wp_idle_logout_last_active_time' meta is set.
 * If it isn't, the meta is created for the current time.
 * If it is, the timestamp is checked against the inactivity period.
 *
 */
function wp_idle_logout_check_for_inactivity() {
	if ( is_user_logged_in() ) {
		$user_id = get_current_user_id();
		$time = get_user_meta( $user_id, 'wp_idle_logout_last_active_time', true );

		if ( $time ) {
			if ( (int) $time + WP_IDLE_LOGOUT_MAX_INACTIVITY_SECONDS < time() ) {
				wp_redirect( wp_login_url() . '?idle=1' );
				wp_logout();
				wp_idle_logout_clear_activity_meta( $user_id );
				exit;
			} else {
				update_user_meta( $user_id, 'wp_idle_logout_last_active_time', time() );
			}

		} else {
			update_user_meta( $user_id, 'wp_idle_logout_last_active_time', time() );
		}
	}
}
add_action( 'init', 'wp_idle_logout_check_for_inactivity' );


/**
 * Delete Inactivity Meta
 *
 * Deletes the 'wp_idle_logout_last_active_time' meta when called.
 * Used on normal logout and on idleness logout.
 *
 */
function wp_idle_logout_clear_activity_meta( $user_id = false ) {
	if ( !$user_id ) {
		$user_id = get_current_user_id();
	}
	delete_user_meta( $user_id, 'wp_idle_logout_last_active_time' );
}
add_action( 'clear_auth_cookie', 'wp_idle_logout_clear_activity_meta' );


/**
 * Show Notification on Logout
 *
 * Overwrites the default WP login message, when 'idle' query string is present
 *
 */
function wp_idle_logout_idle_message( $message ) {
	if( !empty( $_GET['idle'] ) ) {
        return '<p class="message">You have been logged out due to inactivity.</p>';
	} else {
		return $message;
	}
}
add_filter( 'login_message', 'wp_idle_logout_idle_message' );