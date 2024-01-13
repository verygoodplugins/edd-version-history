<?php

/**
 * The admin functionality of the plugin.
 *
 * @link       https://verygoodplugins.com
 * @since      1.0.0
 *
 * @package    EDD_Version_History
 * @subpackage EDD_Version_History/admin
 */
class EDD_Version_History_Admin {

	/**
	 * Get things started.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_action( 'save_post_download', array( $this, 'clear_cached_tags' ) );
	}

	/**
	 * Clears the cached copy of the git tags on Download save.
	 *
	 * @since 1.0.0
	 *
	 * @param int $download_id The download ID.
	 */
	public function clear_cached_tags( $download_id ) {

		delete_post_meta( $download_id, 'edd_download_tags' );
	}

}