<?php
defined('ABSPATH') || exit;

/**
 * Class Mauriel_Media
 *
 * Handles all image and media management for directory listings: logo, cover
 * photo, gallery, and embedded video URLs. Every upload is funnelled through
 * WordPress core functions so the media library stays consistent.
 *
 * @package MaurielServiceDirectory
 * @since   1.0.0
 */
class Mauriel_Media {

	// -------------------------------------------------------------------------
	// Retrieval helpers
	// -------------------------------------------------------------------------

	/**
	 * Returns the URL of a listing's logo image.
	 *
	 * @param  int    $listing_id
	 * @param  string $size  WordPress image size slug (default 'thumbnail').
	 * @return string  URL string, or placeholder URL if no logo is set.
	 */
	public static function get_logo_url( $listing_id, $size = 'thumbnail' ) {
		$listing_id = absint( $listing_id );
		$logo_id    = (int) get_post_meta( $listing_id, '_mauriel_logo_id', true );

		if ( $logo_id ) {
			$url = wp_get_attachment_image_url( $logo_id, $size );
			if ( $url ) {
				return $url;
			}
		}

		return self::get_placeholder_url();
	}

	/**
	 * Returns the URL of a listing's cover image.
	 *
	 * @param  int    $listing_id
	 * @param  string $size  WordPress image size slug (default 'large').
	 * @return string  URL string, or placeholder URL if no cover is set.
	 */
	public static function get_cover_url( $listing_id, $size = 'large' ) {
		$listing_id = absint( $listing_id );
		$cover_id   = (int) get_post_meta( $listing_id, '_mauriel_cover_id', true );

		if ( $cover_id ) {
			$url = wp_get_attachment_image_url( $cover_id, $size );
			if ( $url ) {
				return $url;
			}
		}

		return self::get_placeholder_url();
	}

	/**
	 * Returns an array of gallery image URLs for a listing.
	 *
	 * @param  int    $listing_id
	 * @param  string $size  WordPress image size slug (default 'medium').
	 * @return array  Associative array keyed by attachment ID: [ attachment_id => url ]
	 */
	public static function get_gallery_urls( $listing_id, $size = 'medium' ) {
		$listing_id  = absint( $listing_id );
		$gallery_raw = get_post_meta( $listing_id, '_mauriel_gallery_ids', true );

		if ( ! $gallery_raw ) {
			return array();
		}

		$gallery_ids = json_decode( $gallery_raw, true );
		if ( ! is_array( $gallery_ids ) || empty( $gallery_ids ) ) {
			return array();
		}

		$urls = array();
		foreach ( $gallery_ids as $attachment_id ) {
			$attachment_id = absint( $attachment_id );
			if ( ! $attachment_id ) {
				continue;
			}
			$url = wp_get_attachment_image_url( $attachment_id, $size );
			if ( $url ) {
				$urls[ $attachment_id ] = $url;
			}
		}

		return $urls;
	}

	/**
	 * Returns the URL of the plugin's built-in placeholder image.
	 *
	 * @return string
	 */
	public static function get_placeholder_url() {
		return MAURIEL_URL . 'assets/images/placeholder-listing.png';
	}

	// -------------------------------------------------------------------------
	// Upload handlers
	// -------------------------------------------------------------------------

	/**
	 * Handles a logo image upload for a listing.
	 *
	 * @param  int   $listing_id    Post ID of the mauriel_listing.
	 * @param  array $file          Entry from $_FILES (e.g. $_FILES['logo']).
	 * @param  int   $user_id       User ID performing the upload.
	 * @return int|WP_Error         Attachment ID or WP_Error on failure.
	 */
	public static function handle_logo_upload( $listing_id, array $file, $user_id ) {
		$ownership_check = self::verify_ownership( $listing_id, $user_id );
		if ( is_wp_error( $ownership_check ) ) {
			return $ownership_check;
		}

		$attachment_id = self::process_upload( $listing_id, $file );
		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		// Delete the old logo attachment to keep the media library clean.
		$old_logo_id = (int) get_post_meta( $listing_id, '_mauriel_logo_id', true );
		if ( $old_logo_id && $old_logo_id !== $attachment_id ) {
			wp_delete_attachment( $old_logo_id, true );
		}

		update_post_meta( $listing_id, '_mauriel_logo_id', $attachment_id );

		return $attachment_id;
	}

	/**
	 * Handles a cover image upload for a listing.
	 *
	 * @param  int   $listing_id
	 * @param  array $file
	 * @param  int   $user_id
	 * @return int|WP_Error  Attachment ID or WP_Error.
	 */
	public static function handle_cover_upload( $listing_id, array $file, $user_id ) {
		$ownership_check = self::verify_ownership( $listing_id, $user_id );
		if ( is_wp_error( $ownership_check ) ) {
			return $ownership_check;
		}

		$attachment_id = self::process_upload( $listing_id, $file );
		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		$old_cover_id = (int) get_post_meta( $listing_id, '_mauriel_cover_id', true );
		if ( $old_cover_id && $old_cover_id !== $attachment_id ) {
			wp_delete_attachment( $old_cover_id, true );
		}

		update_post_meta( $listing_id, '_mauriel_cover_id', $attachment_id );

		return $attachment_id;
	}

	/**
	 * Handles a gallery image upload and appends it to the listing's gallery.
	 *
	 * @param  int   $listing_id
	 * @param  array $file
	 * @param  int   $user_id
	 * @return int|WP_Error  New attachment ID or WP_Error.
	 */
	public static function handle_gallery_upload( $listing_id, array $file, $user_id ) {
		$ownership_check = self::verify_ownership( $listing_id, $user_id );
		if ( is_wp_error( $ownership_check ) ) {
			return $ownership_check;
		}

		// Enforce per-listing gallery limit (configurable, default 20).
		$limit       = absint( get_option( 'mauriel_gallery_image_limit', 20 ) );
		$gallery_raw = get_post_meta( $listing_id, '_mauriel_gallery_ids', true );
		$gallery_ids = $gallery_raw ? json_decode( $gallery_raw, true ) : array();
		if ( ! is_array( $gallery_ids ) ) {
			$gallery_ids = array();
		}

		if ( count( $gallery_ids ) >= $limit ) {
			return new WP_Error(
				'gallery_limit',
				sprintf(
					/* translators: %d: maximum number of gallery images */
					__( 'Gallery limit reached. Maximum %d images allowed.', 'mauriel-service-directory' ),
					$limit
				)
			);
		}

		$attachment_id = self::process_upload( $listing_id, $file );
		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		$gallery_ids[] = $attachment_id;
		update_post_meta( $listing_id, '_mauriel_gallery_ids', wp_json_encode( $gallery_ids ) );

		return $attachment_id;
	}

	/**
	 * Removes a single image from a listing's gallery and deletes the attachment.
	 *
	 * @param  int $listing_id
	 * @param  int $attachment_id
	 * @param  int $user_id
	 * @return bool|WP_Error  True on success, WP_Error on failure.
	 */
	public static function remove_gallery_image( $listing_id, $attachment_id, $user_id ) {
		$ownership_check = self::verify_ownership( $listing_id, $user_id );
		if ( is_wp_error( $ownership_check ) ) {
			return $ownership_check;
		}

		$listing_id    = absint( $listing_id );
		$attachment_id = absint( $attachment_id );

		$gallery_raw = get_post_meta( $listing_id, '_mauriel_gallery_ids', true );
		$gallery_ids = $gallery_raw ? json_decode( $gallery_raw, true ) : array();
		if ( ! is_array( $gallery_ids ) ) {
			$gallery_ids = array();
		}

		$key = array_search( $attachment_id, $gallery_ids, false );
		if ( false === $key ) {
			return new WP_Error(
				'not_in_gallery',
				__( 'This image is not in the listing\'s gallery.', 'mauriel-service-directory' )
			);
		}

		unset( $gallery_ids[ $key ] );
		$gallery_ids = array_values( $gallery_ids ); // Re-index.

		update_post_meta( $listing_id, '_mauriel_gallery_ids', wp_json_encode( $gallery_ids ) );

		// Delete the media library attachment.
		wp_delete_attachment( $attachment_id, true );

		return true;
	}

	// -------------------------------------------------------------------------
	// Video embed
	// -------------------------------------------------------------------------

	/**
	 * Sanitises a video embed URL, restricting to whitelisted providers.
	 *
	 * Supported:
	 *   - YouTube  (youtube.com, youtu.be)
	 *   - Vimeo    (vimeo.com)
	 *
	 * Any other HTTPS URL is accepted as-is (fallback for self-hosted or other
	 * providers) but still validated as a proper URL.
	 *
	 * @param  string $url  Raw video URL provided by the user.
	 * @return string|false  Sanitised embed URL or false if the URL is invalid.
	 */
	public static function sanitize_video_embed( $url ) {
		$url = trim( (string) $url );

		if ( '' === $url ) {
			return false;
		}

		$url = esc_url_raw( $url );

		if ( '' === $url ) {
			return false;
		}

		// Only allow HTTPS.
		if ( 0 !== strpos( $url, 'https://' ) ) {
			return false;
		}

		$host = wp_parse_url( $url, PHP_URL_HOST );
		$host = strtolower( (string) $host );

		// ------------------------------------------------------------------
		// YouTube — convert watch URL to nocookie embed.
		// ------------------------------------------------------------------
		if ( 'www.youtube.com' === $host || 'youtube.com' === $host ) {
			$parsed = wp_parse_url( $url );
			wp_parse_str( isset( $parsed['query'] ) ? $parsed['query'] : '', $query_vars );

			$video_id = isset( $query_vars['v'] ) ? preg_replace( '/[^a-zA-Z0-9_\-]/', '', $query_vars['v'] ) : '';

			if ( '' === $video_id ) {
				// Might already be an embed URL.
				if ( preg_match( '#/embed/([a-zA-Z0-9_\-]+)#', $url, $m ) ) {
					$video_id = $m[1];
				}
			}

			if ( '' === $video_id ) {
				return false;
			}

			return 'https://www.youtube-nocookie.com/embed/' . $video_id;
		}

		if ( 'youtu.be' === $host ) {
			$path     = ltrim( wp_parse_url( $url, PHP_URL_PATH ), '/' );
			$video_id = preg_replace( '/[^a-zA-Z0-9_\-]/', '', $path );

			if ( '' === $video_id ) {
				return false;
			}

			return 'https://www.youtube-nocookie.com/embed/' . $video_id;
		}

		// ------------------------------------------------------------------
		// Vimeo — convert player URL to standard embed.
		// ------------------------------------------------------------------
		if ( 'vimeo.com' === $host || 'www.vimeo.com' === $host || 'player.vimeo.com' === $host ) {
			if ( preg_match( '#/(\d+)#', $url, $m ) ) {
				return 'https://player.vimeo.com/video/' . $m[1];
			}
			return false;
		}

		// ------------------------------------------------------------------
		// Any other HTTPS URL — return sanitised.
		// ------------------------------------------------------------------
		return $url;
	}

	// -------------------------------------------------------------------------
	// MIME types
	// -------------------------------------------------------------------------

	/**
	 * Returns the array of allowed image MIME types for listing uploads.
	 *
	 * @return string[]
	 */
	public static function allowed_mime_types() {
		return array(
			'image/jpeg',
			'image/png',
			'image/gif',
			'image/webp',
		);
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * Verifies that $user_id is the owner of $listing_id or has admin caps.
	 *
	 * @param  int $listing_id
	 * @param  int $user_id
	 * @return true|WP_Error
	 */
	private static function verify_ownership( $listing_id, $user_id ) {
		$listing_id = absint( $listing_id );
		$user_id    = absint( $user_id );

		$stored_owner = (int) get_post_meta( $listing_id, '_mauriel_owner_id', true );
		$is_admin     = user_can( $user_id, 'manage_options' )
			|| user_can( $user_id, 'mauriel_admin' );

		if ( $stored_owner !== $user_id && ! $is_admin ) {
			return new WP_Error(
				'unauthorized',
				__( 'You do not have permission to manage media for this listing.', 'mauriel-service-directory' )
			);
		}

		return true;
	}

	/**
	 * Processes a raw file upload and inserts it into the WordPress media library
	 * as an attachment parented to the listing post.
	 *
	 * @param  int   $listing_id  Parent post ID.
	 * @param  array $file        $_FILES-style array.
	 * @return int|WP_Error       Attachment ID or WP_Error.
	 */
	private static function process_upload( $listing_id, array $file ) {
		// Validate MIME type before passing to WP handler.
		$file_type = wp_check_filetype( $file['name'] );
		if ( ! in_array( $file_type['type'], self::allowed_mime_types(), true ) ) {
			return new WP_Error(
				'invalid_mime_type',
				sprintf(
					/* translators: %s: comma-separated list of allowed MIME types */
					__( 'Only the following file types are allowed: %s', 'mauriel-service-directory' ),
					implode( ', ', self::allowed_mime_types() )
				)
			);
		}

		// Enforce file size limit (default 5 MB).
		$max_size_bytes = absint( get_option( 'mauriel_max_upload_size_mb', 5 ) ) * 1024 * 1024;
		if ( $file['size'] > $max_size_bytes ) {
			return new WP_Error(
				'file_too_large',
				sprintf(
					/* translators: %d: max file size in MB */
					__( 'File size exceeds the %dMB limit.', 'mauriel-service-directory' ),
					absint( get_option( 'mauriel_max_upload_size_mb', 5 ) )
				)
			);
		}

		// Use WordPress' built-in upload handler.
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}
		if ( ! function_exists( 'media_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
		}

		$upload_overrides = array( 'test_form' => false );
		$uploaded         = wp_handle_upload( $file, $upload_overrides );

		if ( ! $uploaded || isset( $uploaded['error'] ) ) {
			return new WP_Error(
				'upload_failed',
				isset( $uploaded['error'] )
					? $uploaded['error']
					: __( 'Upload failed for an unknown reason.', 'mauriel-service-directory' )
			);
		}

		// Insert into media library.
		$attachment = array(
			'post_mime_type' => $uploaded['type'],
			'post_title'     => sanitize_file_name( pathinfo( $uploaded['file'], PATHINFO_FILENAME ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
			'post_parent'    => $listing_id,
		);

		$attachment_id = wp_insert_attachment( $attachment, $uploaded['file'], $listing_id );

		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		// Generate image sub-sizes.
		$metadata = wp_generate_attachment_metadata( $attachment_id, $uploaded['file'] );
		wp_update_attachment_metadata( $attachment_id, $metadata );

		return $attachment_id;
	}
}
