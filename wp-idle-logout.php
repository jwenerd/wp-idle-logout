<?php
/*
	Plugin Name: Idle Logout
	Description: Automatically logs out inactive users.
	Version: 1.0.1
	Author: Cooper Dukes @INNEO
	Author URI: http://inneosg.com/
	License: GPL2

	Copyright 2013 Cooper Dukes (email : plugins@inneosg.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/


class WP_Idle_Logout {
	/**
	 * Name space
	 */
	const ID = 'wp_idle_logout_';

	/**
	 * Default idle time
	 */
	const default_idle_time = 3600;

	/**
	 * Default idle message
	 */
	const default_idle_message = 'You have been logged out due to inactivity.';

	/**
	 * Add actions and filters
	 *
	 */
	public function __construct() {;
		add_action( 'init', array(&$this, 'check_for_inactivity') );
		add_action( 'clear_auth_cookie', array(&$this, 'clear_activity_meta') );
		add_filter( 'login_message', array(&$this, 'idle_message') );

		add_action( 'admin_menu', array(&$this, 'options_menu') );
		add_action( 'admin_init', array(&$this, 'initialize_options') );
	}

	/**
	 * Retreives the maximum allowed idle time setting
	 *
	 * Checks if idle time is set in plugin options
	 * If not, uses the default time
	 * Returns $time in seconds, as integer
	 *
	 */
	private function get_idle_time_setting() {
		$time = get_option(self::ID . '_idle_time');
		if ( empty($time) || !is_numeric($time) ) {
			$time = self::default_idle_time;
		}
		return (int) $time;
	}

	/**
	 * Retreives the idle messsage
	 *
	 * Checks if idle message is set in plugin options
	 * If not, uses the default message
	 * Returns $message
	 *
	 */
	private function get_idle_message_setting() {
		$message = nl2br( get_option(self::ID . '_idle_message') );
		if ( empty($message) ) {
			$message = self::default_idle_message;
		}
		return $message;
	}

	/**
	 * Checks for User Idleness
	 *
	 * Tests if the user is logged in on 'init'.
	 * If true, checks if the 'last_active_time' meta is set.
	 * If it isn't, the meta is created for the current time.
	 * If it is, the timestamp is checked against the inactivity period.
	 *
	 */
	public function check_for_inactivity() {
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
			$time = get_user_meta( $user_id, self::ID . '_last_active_time', true );

			if ( is_numeric($time) ) {
				if ( (int) $time + $this->get_idle_time_setting() < time() ) {
					wp_redirect( wp_login_url() . '?idle=1' );
					wp_logout();
					$this->clear_activity_meta( $user_id );
					exit;
				} else {
					update_user_meta( $user_id, self::ID . '_last_active_time', time() );
				}
			} else {
				delete_user_meta( $user_id, self::ID . '_last_active_time' );
				update_user_meta( $user_id, self::ID . '_last_active_time', time() );
			}
		}
	}

	/**
	 * Delete Inactivity Meta
	 *
	 * Deletes the 'last_active_time' meta when called.
	 * Used on normal logout and on idleness logout.
	 *
	 */
	public function clear_activity_meta( $user_id = false ) {
		if ( !$user_id ) {
			$user_id = get_current_user_id();
		}
		delete_user_meta( $user_id, self::ID . '_last_active_time' );
	}

	/**
	 * Show Notification on Logout
	 *
	 * Overwrites the default WP login message, when 'idle' query string is present
	 *
	 */
	public function idle_message( $message ) {
		if ( !empty( $_GET['idle'] ) ) {
			return $message . '<p class="message">' . $this->get_idle_message_setting() . '</p>';
		} else {
			return $message;
		}
	}

	/**
	 * Admin options
	 * Add menu
	 *
	 */
	public function options_menu() {
		add_options_page(
			'WP Idle Logout Options',
			'Idle Logout',
			'manage_options',
			self::ID . '_options',
			array(&$this, 'options_page')
		);
	}

	/**
	 * Admin options
	 * Add page to Settings area
	 *
	 */
	public function options_page() {
		echo'<div class="wrap"> ';
			echo'<h2>WP Idle Logout Options</h2>';
			echo'<form method="post" action="options.php">';
				settings_fields( self::ID . '_options' );
				do_settings_sections( self::ID . '_options' );
				submit_button();
			echo'</form>';
		echo'</div>';
	}

	/**
	 * Admin options
	 * Add options to plugin options page
	 *
	 */
	public function initialize_options() {
		add_settings_section(
			self::ID . '_options_section',
			null,
			null,
			self::ID . '_options'
		);

		add_option( self::ID . '_idle_time' );

		add_settings_field(
			self::ID . '_idle_time',
			'Idle Time',
			array(&$this, 'render_idle_time_option'),
			self::ID . '_options',
			self::ID . '_options_section'
		);

		register_setting(
			self::ID . '_options',
			self::ID . '_idle_time',
			'absint'
		);

		add_option( self::ID . '_idle_message' );

		add_settings_field(
			self::ID . '_idle_message',
			'Idle Message',
			array(&$this, 'render_idle_message_option'),
			self::ID . '_options',
			self::ID . '_options_section'
		);

		register_setting(
			self::ID . '_options',
			self::ID . '_idle_message',
			'wp_kses_post'
		);
	}

	/**
	 * Admin options
	 * Render idle time option field
	 *
	 */
	public function render_idle_time_option() {
		echo '<input type="text" name="' . self::ID . '_idle_time" class="small-text" value="' . get_option(self::ID . '_idle_time') . '" />';
		echo '<p class="description">How long (in seconds) should users be idle for before being logged out?</p>';
	}

	/**
	 * Admin options
	 * Render idle message option field
	 *
	 */
	public function render_idle_message_option() {
		echo '<textarea name="' . self::ID . '_idle_message" class="regular-text" rows="5" cols="50">' . get_option(self::ID . '_idle_message') . ' </textarea>';
		echo '<p class="description">Overrides the default message shown to idle users when redirected to the login screen.</p>';
	}
}

$WP_Idle_Logout = new WP_Idle_Logout();