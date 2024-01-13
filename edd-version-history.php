<?php

/**
 * Plugin Name: Easy Digital Downloads - Download Version History
 * Description: Adds a download version history shortcode to Easy Digital Downloads.
 * Plugin URI: https://github.com/verygoodplugins/edd-version-history/
 * Version: 1.0.0
 * Author: Very Good Plugins
 * Author URI: https://verygoodplugins.com/
*/

/**
 * @copyright Copyright (c) 2023. All rights reserved.
 *
 * @license   Released under the GPL license http://www.opensource.org/licenses/gpl-license.php
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * **********************************************************************
 */

// deny direct access.
if ( ! function_exists( 'add_action' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

final class EDD_Version_History {

	/** Singleton *************************************************************/

	/**
	 * Public interfaces and settings.
	 *
	 * @var EDD_Version_History_Public
	 * @since 1.0.0
	 */
	public $public;

	/**
	 * Admin interfaces and settings.
	 *
	 * @var EDD_Version_History_Admin
	 * @since 1.0.0
	 */
	public $admin;

	/**
	 * @var EDD_Version_History The one true EDD_Version_History
	 * @since 1.0.0
	 */
	private static $instance;


	/**
	 * Main EDD_Version_History Instance
	 *
	 * Insures that only one instance of EDD_Version_History exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since 1.0.0
	 * @static var array $instance
	 * @return EDD_Version_History The one true EDD_Version_History
	 */

	public static function instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof EDD_Version_History ) ) {

			self::$instance = new EDD_Version_History();
			self::$instance->setup_constants();
			self::$instance->includes();

			if ( function_exists( 'edd_git_download_updater' ) ) {
				self::$instance->public = new EDD_Version_History_Public();
				self::$instance->admin  = new EDD_Version_History_Admin();
			} else {
				add_action( 'admin_notices', array( $this, 'missing_required_plugin_notice' ) );
			}
		}

		return self::$instance;
	}

	/**
	 * Throw error on object clone
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @access protected
	 * @return void
	 */

	public function __clone() {
		// Cloning instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'edd-version-history' ), '1.0' );
	}

	/**
	 * Disable unserializing of the class
	 *
	 * @access protected
	 * @return void
	 */

	public function __wakeup() {
		// Unserializing instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'edd-version-history' ), '1.0' );
	}

	/**
	 * Setup plugin constants.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function setup_constants() {

		if ( ! defined( 'EDD_VERSION_HISTORY_DIR_PATH' ) ) {
			define( 'EDD_VERSION_HISTORY_DIR_PATH', plugin_dir_path( __FILE__ ) );
		}

		if ( ! defined( 'EDD_VERSION_HISTORY_PLUGIN_PATH' ) ) {
			define( 'EDD_VERSION_HISTORY_PLUGIN_PATH', plugin_basename( __FILE__ ) );
		}

		if ( ! defined( 'EDD_VERSION_HISTORY_DIR_URL' ) ) {
			define( 'EDD_VERSION_HISTORY_DIR_URL', plugin_dir_url( __FILE__ ) );
		}

	}

	/**
	 * Include required files.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function includes() {
		require_once EDD_VERSION_HISTORY_DIR_PATH . 'includes/class-edd-version-history-public.php';
		require_once EDD_VERSION_HISTORY_DIR_PATH . 'includes/class-edd-version-history-admin.php';
	}

	/**
	 * Display a notice if the EDD Git Download Updater plugin is not installed.
	 *
	 * @since 1.0.0
	 */
	public function missing_required_plugin_notice() {

		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'The EDD Git Download Updater plugin is required for the Download Version History plugin to work. Please install and activate the EDD Git Download Updater plugin.', 'edd-version-history' ) . '</p></div>';
	}
}

/**
* The main function responsible for returning the one true EDD Version History
* Instance to functions everywhere.
*
* Use this function like you would a global variable, except without needing
* to declare the global.
*/

if ( ! function_exists( 'edd_version_history' ) ) {

	function edd_version_history() {
		return EDD_Version_History::instance();
	}

	edd_version_history();

}
