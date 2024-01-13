<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://verygoodplugins.com
 * @since      1.0.0
 *
 * @package    EDD_Version_History
 * @subpackage EDD_Version_History/public
 */
class EDD_Version_History_Public {

	/**
	 * Get things started.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_shortcode( 'edd_version_history', array( $this, 'edd_version_history_shortcode' ) );
		add_filter( 'edd_process_download_args', array( $this, 'process_download_args' ) );
		add_filter( 'edd_requested_file', array( $this, 'requested_file' ), 10, 4 );
	}


	/**
	 * Shortcode to display version history.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Shortcode arguments.
	 * @return string The shortcode output.
	 */
	public function edd_version_history_shortcode( $args ) {

		$defaults = array(
			'download_id'     => false,
			'include_current' => false,
			'limit'           => 10,
		);

		$args = wp_parse_args( $args, $defaults );

		if ( false === $args['download_id'] ) {
			return '[You must specify a <code>download_id</code>]';
		}

		$customer = edd_get_customer_by( 'user_id', get_current_user_id() );

		if ( ! empty( $customer ) ) {
			$orders = edd_get_orders(
				array(
					'customer_id'    => $customer->id,
					'product_id'     => $args['download_id'],
					'number'         => 20,
					'type'           => 'sale',
					'status__not_in' => array( 'trash', 'refunded', 'abandoned' ),
				)
			);
		} else {
			$orders = array();
		}

		if ( empty( $orders ) ) {
			return 'You have not purchased this download.';
		}

		$files = edd_get_download_files( $args['download_id'] );

		$version_history = '';

		foreach ( $files as $key => $file ) {

			if ( ! isset( $file['git_url'] ) ) {
				continue;
			}

			$tags = get_post_meta( $args['download_id'], 'edd_download_tags', true );

			if ( empty( $tags ) ) {

				$tags = edd_git_download_updater()->admin->instance->repos->fetch_tags( $file['git_url'] );

				if ( isset( $tags['error'] ) ) {
					return $tags['error'];
				}

				usort(
					$tags,
					function ( $a, $b ) {
						return version_compare( $b, $a );
					}
				);

				update_post_meta( $args['download_id'], 'edd_download_tags', $tags );
			}

			// Respect the limit parameter.

			$tags = array_slice( $tags, 0, $args['limit'] );

			// For each tag, output the version number and a secure link to download the EDD file.

			$version_history .= '<ul class="edd-version-history" id="edd-version-history-' . esc_attr( $file['git_folder_name'] ) . '">';

			foreach ( $tags as $tag ) {

				if ( false === $args['include_current'] && $file['git_version'] === $tag ) {
					continue;
				}

				$url = edd_get_download_file_url( $orders[0], edd_get_payment_user_email( $orders[0] ), $key . ':' . $tag, $args['download_id'] );

				$version_history .= '<li><a href="' . esc_url( $url ) . '">' . $tag . '</a></li>';

			}

			$version_history .= '</ul>';

		}

		return $version_history;

	}

	/**
	 * Process the download args.
	 *
	 * Extract the file key and version number from the URL
	 *
	 * @since 1.0.0
	 *
	 * @param array $args The download args.
	 * @return array The download args.
	 */
	public function process_download_args( $args ) {

		$file = sanitize_text_field( $_GET['file'] );

		if ( strpos( $file, ':' ) ) {
			$file            = explode( ':', $file );
			$args['file']    = intval( $file[0] );
			$args['version'] = $file[1];
		}

		return $args;
	}

	/**
	 * Load the requested file.
	 *
	 * @since 1.0.0
	 *
	 * @param string $requested_file The requested file.
	 * @param array  $download_files The download files.
	 * @param string $file_key       The file key.
	 * @param array  $args           The download args.
	 * @return string The requested file.
	 */
	public function requested_file( $requested_file, $download_files, $file_key, $args ) {

		if ( ! isset( $args['version'] ) ) {
			return $requested_file;
		}

		if ( $download_files[ $file_key ]['git_version'] === $args['version'] ) {
			return $requested_file; // if it's the current version.
		}

		$new_file_name = str_replace( $download_files[ $file_key ]['git_version'], $args['version'], $download_files[ $file_key ]['name'] );

		$file_path = $this->find_file_recursively( $new_file_name );

		if ( false !== $file_path ) {
			return $file_path; // we found it in the uploads folder.
		}

		// If not, we need to load it from Git.

		$provider = strpos( $download_files[ $file_key ]['git_url'], 'github' ) ? 'github' : 'bitbucket';

		try {
			$provider = edd_git_download_updater()->providerRegistry->getProvider( $provider );
		} catch ( \EDD\GitDownloadUpdater\Exceptions\ResourceNotFoundException $e ) {
			wp_send_json( array(
				'errors' => __( 'Invalid Git provider.', 'edd-git-download-updater' )
			) );
		}

		try {

			edd_git_download_updater()->process_file->url = str_replace( $download_files[ $file_key ]['git_version'], $args['version'], $download_files[ $file_key ]['git_file_asset'] );

			$new_zip = edd_git_download_updater()->process_file->process(
				$args['download'],
				$args['version'],
				$download_files[ $file_key ]['git_url'],
				$file_key,
				$download_files[ $file_key ]['git_folder_name'],
				$new_file_name,
				'',
				'',
				$provider
			);

			$requested_file = $new_zip['url'];

		} catch ( \Exception $e ) {
			wp_send_json( array(
				'errors' => $e->getMessage()
			) );
		}

		return $requested_file;

	}

	/**
	 * Find a file recursively in the EDD uploads folder.
	 *
	 * @since 1.0.0
	 *
	 * @param string $file_name The file name to find.
	 * @return string|bool The file path if found, false if not.
	 */
	public function find_file_recursively( $file_name) {

		$directory = WP_CONTENT_DIR . '/uploads/edd/';

		$iterator = new RecursiveDirectoryIterator( $directory );

		foreach ( new RecursiveIteratorIterator( $iterator ) as $file ) {
			if ( basename( $file ) === $file_name ) {
				return $file->getPathname();
			}
		}

		return false;
	}

}