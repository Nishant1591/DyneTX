<?php

class Meow_WR2X_Rest
{
	private $core;
	private $engine;
	private $namespace = 'wp-retina-2x/v1';

	public function __construct( $core, $engine ) {
		$this->core = $core;
		$this->engine = $engine;

		// FOR DEBUG
		// For experiencing the UI behavior on a slower install.
		// sleep(1);
		// For experiencing the UI behavior on a buggy install.
		// trigger_error( "Error", E_USER_ERROR);
		// trigger_error( "Warning", E_USER_WARNING);
		// trigger_error( "Notice", E_USER_NOTICE);
		// trigger_error( "Deprecated", E_USER_DEPRECATED);

		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
	}

	function rest_api_init() {
		if ( !current_user_can( 'upload_files' ) ) {
			return;
		} 

		// SETTINGS
		register_rest_route( $this->namespace, '/update_option/', array(
			'methods' => 'POST',
			'permission_callback' => array( $this->core, 'can_access_settings' ),
			'callback' => array( $this, 'rest_update_option' )
		) );
		register_rest_route( $this->namespace, '/all_settings/', array(
			'methods' => 'GET',
			'permission_callback' => array( $this->core, 'can_access_settings' ),
			'callback' => array( $this, 'rest_all_settings' )
		) );
		register_rest_route( $this->namespace, '/easy_io_link/', array(
			'methods' => 'POST',
			'permission_callback' => array( $this->core, 'can_access_settings' ),
			'callback' => array( $this, 'rest_easy_io_link' )
		) );
		register_rest_route( $this->namespace, '/easy_io_unlink/', array(
			'methods' => 'POST',
			'permission_callback' => array( $this->core, 'can_access_settings' ),
			'callback' => array( $this, 'rest_easy_io_unlink' )
		) );
		register_rest_route( $this->namespace, '/easy_io_stats/', array(
			'methods' => 'POST',
			'permission_callback' => array( $this->core, 'can_access_settings' ),
			'callback' => array( $this, 'rest_easy_io_stats' )
		) );
		register_rest_route( $this->namespace, '/check_optimizers/', array(
			'methods' => 'GET',
			'permission_callback' => array( $this->core, 'can_access_settings' ),
			'callback' => array( $this, 'rest_check_optimizers' )
		) );
		register_rest_route( $this->namespace, '/ai_image_sizes/', array(
			'methods' => 'POST',
			'permission_callback' => array( $this->core, 'can_access_settings' ),
			'callback' => array( $this, 'rest_ai_image_sizes' )
		) );
		register_rest_route( $this->namespace, '/ai_retina_sizes/', array(
			'methods' => 'POST',
			'permission_callback' => array( $this->core, 'can_access_settings' ),
			'callback' => array( $this, 'rest_ai_retina_sizes' )
		) );
		register_rest_route( $this->namespace, '/get_logs', array(
			'methods' => 'GET',
			'permission_callback' => array( $this->core, 'can_access_features' ),
			'callback' => array( $this, 'rest_get_logs' )
		) );
		register_rest_route( $this->namespace, '/clear_logs', array(
			'methods' => 'GET',
			'permission_callback' => array( $this->core, 'can_access_features' ),
			'callback' => array( $this, 'rest_clear_logs' )
		) );

		// STATS & LISTING
		register_rest_route( $this->namespace, '/stats', array(
			'methods' => 'GET',
			'permission_callback' => array( $this->core, 'can_access_features' ),
			'callback' => array( $this, 'rest_get_stats' ),
			'args' => array(
				'search' => array( 'required' => false ),
			),
		) );
		register_rest_route( $this->namespace, '/media', array(
			'methods' => 'GET',
			'permission_callback' => array( $this->core, 'can_access_features' ),
			'callback' => array( $this, 'rest_media' ),
			'args' => array(
				'limit' => array( 'required' => false, 'default' => 10 ),
				'skip' => array( 'required' => false, 'default' => 20 ),
				'filterBy' => array( 'required' => false, 'default' => 'all' ),
				'orderBy' => array( 'required' => false, 'default' => 'id' ),
				'order' => array( 'required' => false, 'default' => 'desc' ),
				'search' => array( 'required' => false ),
				'offset' => array( 'required' => false ),
				'order' => array( 'required' => false ),
				'search' => array( 'required' => false )
			)
		) );
		register_rest_route( $this->namespace, '/get_all_ids', array(
			'methods' => 'POST',
			'permission_callback' => array( $this->core, 'can_access_features' ),
			'callback' => array( $this, 'rest_get_all_ids' )
		) );

		// ACTIONS
		register_rest_route( $this->namespace, '/refresh', array(
			'methods' => 'POST',
			'permission_callback' => array( $this->core, 'can_access_features' ),
			'callback' => array( $this, 'rest_refresh' )
		) );
		register_rest_route( $this->namespace, '/details', array(
			'methods' => 'POST',
			'permission_callback' => array( $this->core, 'can_access_features' ),
			'callback' => array( $this, 'rest_get_details' )
		) );
		register_rest_route( $this->namespace, '/webp_details', array(
			'methods' => 'POST',
			'permission_callback' => array( $this->core, 'can_access_features' ),
			'callback' => array( $this, 'rest_get_webp_details' )
		) );
		register_rest_route( $this->namespace, '/build_retina', array(
			'methods' => 'POST',
			'permission_callback' => array( $this->core, 'can_access_features' ),
			'callback' => array( $this, 'rest_build_retina' )
		) );
		register_rest_route( $this->namespace, '/build_webp', array(
			'methods' => 'POST',
			'permission_callback' => array( $this->core, 'can_access_features' ),
			'callback' => array( $this, 'rest_build_webp' )
		) );
		register_rest_route( $this->namespace, '/regenerate', array(
			'methods' => 'POST',
			'permission_callback' => array( $this->core, 'can_access_features' ),
			'callback' => array( $this, 'rest_regenerate' )
		) );
		register_rest_route( $this->namespace, '/optimize', array(
			'methods' => 'POST',
			'permission_callback' => array( $this->core, 'can_access_features' ),
			'callback' => array( $this, 'rest_optimize' )
		) );
		register_rest_route( $this->namespace, '/delete_retina', array(
			'methods' => 'POST',
			'permission_callback' => array( $this->core, 'can_access_features' ),
			'callback' => array( $this, 'rest_delete_retina' )
		) );
		register_rest_route( $this->namespace, '/delete_webp', array(
			'methods' => 'POST',
			'permission_callback' => array( $this->core, 'can_access_features' ),
			'callback' => array( $this, 'rest_delete_webp' )
		) );
		register_rest_route( $this->namespace, '/ignore', array(
			'methods' => 'POST',
			'permission_callback' => array( $this->core, 'can_access_features' ),
			'callback' => array( $this, 'rest_ignore' )
		) );
		register_rest_route( $this->namespace, '/replace', array(
			'methods' => 'POST',
			'permission_callback' => '__return_true',
			'callback' => array( $this, 'rest_replace' )
		) );
  }

  	function rest_get_logs() {
		$logs = $this->core->get_logs();
		return new WP_REST_Response( [ 'success' => true, 'data' => $logs ], 200 );
	}

	function rest_clear_logs() {
		$this->core->clear_logs();
		return new WP_REST_Response( [ 'success' => true ], 200 );
	}

	function check_upload( $tmpfname, $filename ) {
		if ( !current_user_can( 'upload_files' ) ) {
			$this->core->log( "You do not have permission to upload files." );
			unlink( $tmpfname );
			return __( "You do not have permission to upload files.", 'wp-retina-2x' );
		}
		$file_info = getimagesize( $tmpfname );
		if ( empty( $file_info ) ) {
			$this->core->log( "The file is not an image or the upload went wrong." );
			unlink( $tmpfname );
			return __( "The file is not an image or the upload went wrong.", 'wp-retina-2x' );
		}
		$filedata = wp_check_filetype_and_ext( $tmpfname, $filename );
		if ( $filedata["ext"] == "" ) {
			$this->core->log( "You cannot use this file (wrong extension? wrong type?)." );
			unlink( $tmpfname );
			return __( "You cannot use this file (wrong extension? wrong type?).", 'wp-retina-2x' );
		}
		return null;
	}

	function rest_replace( $request ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$params = $request->get_body_params();
		$mediaId = $params['mediaId'];
		$files = $request->get_file_params();
		$file = $files['file'];
		$tmpfname = $file['tmp_name'];

		// Check errors
		if ( empty( $mediaId ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => "The Media ID is required." ] );
		}
		// Being allowed to upload files says nothing about this particular media, so make sure
		// the user can actually edit it before replacing anyone's image.
		$mediaId = (int)$mediaId;
		if ( get_post_type( $mediaId ) !== 'attachment' || !current_user_can( 'edit_post', $mediaId ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => "You are not allowed to modify this media." ] );
		}
		if ( empty( $tmpfname ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => "A file is required." ] );
		}
		$error = $this->check_upload( $tmpfname, $file['name'] );
		if ( !empty( $error ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => $error ], 200 );
		}

		$meta = wp_get_attachment_metadata( $mediaId );
		$current_file = get_attached_file( $mediaId );
		do_action( 'wr2x_before_replace', $mediaId, $tmpfname );
		$this->engine->delete_retina_attachment( $mediaId, false );
		$this->engine->delete_webp_attachment( $mediaId, false );
		$this->engine->delete_webp_retina_attachment( $mediaId );
		$pathinfo = pathinfo( $current_file );
		$basepath = $pathinfo['dirname'];

		// Let's clean everything first
		if ( wp_attachment_is_image( $mediaId ) ) {
			$sizes = $this->core->get_image_sizes();
			foreach ( $sizes as $name => $attr ) {
				if ( isset( $meta['sizes'][$name] ) && isset( $meta['sizes'][$name]['file'] ) && 
					file_exists( trailingslashit( $basepath ) . $meta['sizes'][$name]['file'] ) ) {
					$normal_file = trailingslashit( $basepath ) . $meta['sizes'][$name]['file'];
					$pathinfo = pathinfo( $normal_file );
					$retina_file = trailingslashit( $pathinfo['dirname'] ) . $pathinfo['filename'] . $this->core->retina_extension() . $pathinfo['extension'];
					$webp_file = trailingslashit( $pathinfo['dirname'] ) . $pathinfo['filename'] . "." . $pathinfo['extension'] . $this->core->webp_avif_extension();
					$webp_retina_file = trailingslashit( $pathinfo['dirname'] ) . $pathinfo['filename'] . $this->core->retina_extension() . $pathinfo['extension'] . $this->core->webp_avif_extension();

					// Test if the file exists and if it is actually a file (and not a dir)
					// Some old WordPress Media Library are sometimes broken and link to directories
					if ( file_exists( $normal_file ) && is_file( $normal_file ) )
						unlink( $normal_file );
					if ( file_exists( $retina_file ) && is_file( $retina_file ) )
						unlink( $retina_file );
					if ( file_exists( $webp_file ) && is_file( $webp_file ) )
						unlink( $webp_file );
					if ( file_exists( $webp_retina_file ) && is_file( $webp_retina_file ) )
						unlink( $webp_retina_file );
				}
			}
		}
		if ( file_exists( $current_file ) )
			unlink( $current_file );

		// Insert the new file and delete the temporary one
		rename( $tmpfname, $current_file );
		chmod( $current_file, 0644 );

		// Generate the images
		wp_update_attachment_metadata( $mediaId, wp_generate_attachment_metadata( $mediaId, $current_file ) );
		$meta = wp_get_attachment_metadata( $mediaId );
		$this->engine->generate_retina_images( $meta );
		$this->engine->generate_modern_format( $meta );
		$this->engine->generate_webp_retina_images( $meta );

		// Increase the version number
		$this->core->increase_media_version( $mediaId );

		// Finalize
		do_action( 'wr2x_replace', $mediaId );
		$info = $this->core->get_media_status_one( $mediaId );
		return new WP_REST_Response( [ 'success' => true, 'data' => $info  ], 200 );
	}


	function rest_all_settings() {
		return new WP_REST_Response( [ 'success' => true, 'data' => $this->core->get_options() ], 200 );
	}

	function find_whereis_command() {
		$test_output = null;
		$test_result_code = null;
		exec( 'whereis whereis', $test_output, $test_result_code );
	
		if ( $test_result_code !== 127 ) {
			return 'whereis';
		}
	
		$which_output = null;
		$which_result_code = null;
		exec( 'which whereis', $which_output, $which_result_code );
	
		if ( $which_result_code !== 127 && ! empty( $which_output ) ) {
			return $which_output[0];
		}
	
		// If which is not available or didn't find whereis, let's try some default paths
		$default_paths = array(
			'/bin/whereis',
			'/sbin/whereis',
			'/usr/bin/whereis',
			'/usr/sbin/whereis',
			'/bin/whereis',
			'/usr/local/bin/whereis',
		);
	
		foreach ( $default_paths as $path ) {
			if ( is_executable( $path ) ) {
				return $path;
			}
		}
	
		return false;
	}
	
	function rest_ai_image_sizes() {
		global $mwai;
		if ( empty( $mwai ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => 'AI Engine is not available.' ], 400 );
		}

		$sizes = $this->core->get_image_sizes( ARRAY_A );
		$theme = wp_get_theme()->get( 'Name' );

		$sizes_description = "";
		foreach ( $sizes as $size ) {
			$status = $size['enabled'] ? 'enabled' : 'disabled';
			$sizes_description .= "- {$size['name']}: {$size['width']}x{$size['height']} ({$status})\n";
		}

		$prompt = "You are a WordPress image optimization expert. The site uses the theme '{$theme}'. " .
			"Here are all registered image sizes:\n\n{$sizes_description}\n" .
			"Your goal: recommend the **minimal, optimal set** of sizes. WordPress uses srcset to let the browser " .
			"pick the best image for each viewport — sizes don't need to be pixel-perfect, they just need to provide " .
			"good coverage across common screen widths (mobile, tablet, desktop).\n\n" .
			"Guidelines:\n" .
			"- KEEP: thumbnail (used in admin), medium, large — these are WordPress core and widely used.\n" .
			"- medium_large (768px): rarely useful, usually redundant between medium and large.\n" .
			"- 1536x1536 and 2048x2048: WordPress scaled sizes, often redundant if large is already 1024px+.\n" .
			"- Look for REDUNDANT sizes: if two sizes are close in width (within ~100-150px), the smaller one is likely enough.\n" .
			"- Theme/plugin sizes: keep only if they serve a unique breakpoint not already covered.\n" .
			"- Fewer sizes = faster uploads, less disk space, and srcset still works perfectly.\n" .
			"- Be aggressive about disabling truly redundant sizes, but conservative with clearly unique breakpoints.\n\n" .
			"Reply ONLY with valid JSON in this exact format, no other text:\n" .
			"{ \"sizes\": [ { \"name\": \"size_name\", \"enabled\": true/false, \"reason\": \"short reason\" } ] }";

		try {
			$response = $mwai->simpleTextQuery( $prompt );
			// Extract JSON from response (in case of markdown wrapping)
			if ( preg_match( '/\{[\s\S]*\}/', $response, $matches ) ) {
				$response = $matches[0];
			}
			$data = json_decode( $response, true );
			if ( empty( $data ) || !isset( $data['sizes'] ) ) {
				return new WP_REST_Response( [ 'success' => false, 'message' => 'Could not parse AI response.' ], 500 );
			}
			return new WP_REST_Response( [ 'success' => true, 'data' => $data['sizes'] ], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => $e->getMessage() ], 500 );
		}
	}

	function rest_ai_retina_sizes() {
		global $mwai;
		if ( empty( $mwai ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => 'AI Engine is not available.' ], 400 );
		}

		$sizes = $this->core->get_image_sizes( ARRAY_A );
		$theme = wp_get_theme()->get( 'Name' );

		// Collect enabled sizes sorted by width for coverage analysis
		$enabled_sizes = array_filter( $sizes, function( $s ) { return $s['enabled']; } );
		$widths = array_map( function( $s ) { return (int) $s['width']; }, $enabled_sizes );

		$sizes_description = "";
		foreach ( $enabled_sizes as $size ) {
			$retina_status = $size['retina'] ? 'retina enabled' : 'retina disabled';
			$retina_width = (int) $size['width'] * 2;
			// Check if another enabled size already covers the @2x width (within 20%)
			$covered_by = null;
			foreach ( $enabled_sizes as $other ) {
				if ( $other['name'] === $size['name'] ) continue;
				$other_w = (int) $other['width'];
				if ( $other_w >= $retina_width * 0.8 && $other_w <= $retina_width * 1.2 ) {
					$covered_by = $other['name'];
					break;
				}
			}
			$coverage_note = $covered_by ? " [NOTE: @2x={$retina_width}px is ~covered by '{$covered_by}' at {$other_w}px]" : "";
			$sizes_description .= "- {$size['name']}: {$size['width']}x{$size['height']} ({$retina_status}){$coverage_note}\n";
		}

		$prompt = "You are a WordPress image optimization expert. The site uses the theme '{$theme}'. " .
			"Here are the enabled image sizes:\n\n{$sizes_description}\n" .
			"Your goal: recommend which sizes should have **Retina (@2x) images** generated.\n\n" .
			"KEY INSIGHT: WordPress uses srcset, so the browser picks the best available image for the screen. " .
			"If a size's @2x width (~double) is already covered by another existing normal size, " .
			"retina is REDUNDANT for that size — srcset will serve the larger normal image on HiDPI screens anyway. " .
			"Sizes marked with [NOTE: @2x covered by...] are candidates for skipping retina.\n\n" .
			"Other guidelines:\n" .
			"- thumbnail: usually only shown in admin, retina rarely needed.\n" .
			"- Very large sizes (1536+): @2x would require 3000+ px originals, usually impractical.\n" .
			"- Medium and large are most commonly seen by visitors — retina is most impactful there if not already covered.\n" .
			"- Balance quality vs. disk space: each retina image doubles storage for that size.\n\n" .
			"Reply ONLY with valid JSON in this exact format, no other text:\n" .
			"{ \"sizes\": [ { \"name\": \"size_name\", \"enabled\": true/false, \"reason\": \"short reason\" } ] }";

		try {
			$response = $mwai->simpleTextQuery( $prompt );
			if ( preg_match( '/\{[\s\S]*\}/', $response, $matches ) ) {
				$response = $matches[0];
			}
			$data = json_decode( $response, true );
			if ( empty( $data ) || !isset( $data['sizes'] ) ) {
				return new WP_REST_Response( [ 'success' => false, 'message' => 'Could not parse AI response.' ], 500 );
			}
			return new WP_REST_Response( [ 'success' => true, 'data' => $data['sizes'] ], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => $e->getMessage() ], 500 );
		}
	}

	function rest_check_optimizers() {
		if ( ! function_exists( 'exec' ) ) {
			return new WP_REST_Response( array( 
				'success' => true,
				'message' => 'Cannot check optimizers. The "exec" function is not available on your server.',
			), 200 );
		}
	
		$optimizers = array(
			'jpegoptim',
			'jpegtran',
			'optipng',
			'pngquant',
			'svgo',
			'gifsicle',
		);
	
		$data = array();
		$whereis_command = $this->find_whereis_command();
	
		if ( ! $whereis_command ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Cannot check optimizers. The "whereis" command is not available on your server.',
				),
				200
			);
		}
	
		foreach ( $optimizers as $optimizer ) {
			$output      = null;
			$result_code = null;
	
			// Use the determined 'whereis' command
			$command = escapeshellcmd( $whereis_command ) . ' ' . escapeshellarg( $optimizer );
			exec( $command, $output, $result_code );
	
			if ( isset( $output[0] ) ) {
				$exploded = explode( ':', $output[0] );
			} else {
				$exploded = array();
			}
	
			if ( count( $exploded ) >= 2 ) {
				list( $name, $value ) = $exploded;
				$data[ $optimizer ]['result'] = ! empty( trim( $value ) );
			} else {
				$data[ $optimizer ]['result'] = false;
			}
	
			if ( $result_code === 127 ) { // Should not happen since we checked for 'whereis' earlier, but let's be sure
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => 'Cannot check some optimizers. The "whereis" command is not available on your server.',
					),
					200
				);
			}
		}
	
		// Check for WebP and AVIF support
		$formats = array( 'webp', 'avif' );
	
		foreach ( $formats as $format ) {
			$enabled_format = false;
	
			if ( extension_loaded( 'imagick' ) && class_exists( 'Imagick' ) ) {
				$image = new Imagick();
				if ( in_array( strtoupper( $format ), $image->queryFormats() ) ) {
					$enabled_format = true;
					$data[ "Imagick($format)" ]['result'] = true;
				}
			}
	
			if (
				extension_loaded( 'gd' ) &&
				function_exists( 'imagecreatefromjpeg' ) &&
				function_exists( 'imagecreatefrompng' ) &&
				function_exists( 'imagecreatefromgif' ) &&
				function_exists( 'imageistruecolor' ) &&
				function_exists( 'imagepalettetotruecolor' ) &&
				function_exists( "image$format" ) // Dynamic function check for GD format support
			) {
				$enabled_format = true;
				$data[ "GD($format)" ]['result'] = true;
			}
	
			if ( ! $enabled_format ) {
				$libraries = array();
				if ( extension_loaded( 'imagick' ) ) {
					$libraries[] = 'Imagick';
				}
				if ( extension_loaded( 'gd' ) ) {
					$libraries[] = 'GD';
				}
				$library = ! empty( $libraries ) ? implode( ' or ', $libraries ) : 'Imagick or GD';
				$data[ $format ] = array(
					'result'  => false,
					'message' => "Needs to enable $format in $library on your server.",
				);
			}
		}
	
		return new WP_REST_Response( array( 'success' => true, 'data' => $data ), 200 );
	}

	function count_issues($search) {
		return count( $this->core->get_issues($search) );
	}

	function count_ignored($search) {
		return count( $this->core->get_ignores($search) );
	}

	function count_optimize_issues( $search ) {
		return count( $this->core->get_optimize_issues( $search ) );
	}

	function count_all($search) {
		global $wpdb;
		$whereSql = '';
		if ($search) {
			$whereSql = $wpdb->prepare("AND post_title LIKE %s ", ( '%' . $search . '%' ));
		}
		// Exclude ignored items
		$ignored = array_filter( $this->core->get_ignores($search) );
		$excludeSql = '';
		if ( !empty( $ignored ) ) {
			$excludeSql = 'AND p.ID NOT IN (' . implode( ',', $ignored ) . ')';
		}
		return (int)$wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts p 
			WHERE post_type='attachment'
			AND post_mime_type LIKE 'image/%'
			$whereSql
			$excludeSql"
		);
	}

	function rest_get_stats( $request ) {
		$search = sanitize_text_field( $request->get_param('search') );
		return new WP_REST_Response( [ 'success' => true, 'data' => array(
			'ignored' => $this->count_ignored( $search ),
			'optimizeIssues' => $this->count_optimize_issues( $search ),
			'all' => $this->count_all( $search )
		) ], 200 );
	}

	function rest_get_all_ids( $request ) {
		global $wpdb;
		$params = $request->get_json_params();
		$issuesOnly = isset( $params['issuesOnly'] ) ? (bool)$params['issuesOnly'] : false;
		if ( $issuesOnly ) {
			$ids = array_values( $this->core->get_issues() );
		}
		else {
			$wpml = $this->core->create_sql_if_wpml_original();
			$ids = $wpdb->get_col( "SELECT ID FROM $wpdb->posts p 
				WHERE post_type='attachment'
				AND post_type = 'attachment' {$wpml}
				AND post_status='inherit'
				AND ( post_mime_type = 'image/jpeg' OR
				post_mime_type = 'image/png' OR
				post_mime_type = 'image/gif' )"
			);
		}
		return new WP_REST_Response( [ 'success' => true, 'data' => $ids ], 200 );
	}

	function rest_refresh() {
		$this->core->calculate_issues();
		$this->core->calculate_optimize_issues();
		return new WP_REST_Response( [ 'success' => true ], 200 );
	}

	/**
	 * Get the status for many Media IDs.
	 *
	 * @param integer $skip
	 * @param integer $limit
	 * @return void
	 */
	function get_media_status( $skip = 0, $limit = 10, $filterBy = 'all', $orderBy = 'id', $order = 'desc', $search = '' ) {
		global $wpdb;
		$whereIsIn = '';
		if ( $filterBy !== 'all' ) {

			$filtered = $this->get_filtered_post_ids( $filterBy );
			if( empty( $filtered ) ) {
				return array();
			}

			$in = array_filter( $filtered );
			if ( empty( $in ) ) {
				return array();
			}
			
			$whereIsIn = 'AND p.ID IN (' . implode( ',', $in ) . ')';
		}
		else {
			// For 'all', exclude ignored items
			$ignored = array_filter( $this->core->get_ignores() );
			if ( !empty( $ignored ) ) {
				$whereIsIn = 'AND p.ID NOT IN (' . implode( ',', $ignored ) . ')';
			}
		}
		$orderSql = 'ORDER BY p.ID DESC';
		if ($orderBy === 'post_title') {
			$orderSql = 'ORDER BY post_title ' . ( $order === 'asc' ? 'ASC' : 'DESC' );
		}
		else if ($orderBy === 'current_filename') {
			$orderSql = 'ORDER BY current_filename ' . ( $order === 'asc' ? 'ASC' : 'DESC' );
		}
		$entries = [];
		if ( empty( $search ) ) {
			$sql = $wpdb->prepare( "SELECT p.ID, p.post_title, 
				MAX(CASE WHEN pm.meta_key = '_wp_attachment_metadata' THEN pm.meta_value END) AS metadata
				FROM $wpdb->posts p
				INNER JOIN $wpdb->postmeta pm ON pm.post_id = p.ID
				WHERE post_type = 'attachment'
				AND pm.meta_key = '_wp_attachment_metadata'
				AND p.post_mime_type LIKE 'image/%%'
				$whereIsIn
				GROUP BY p.ID
				$orderSql
				LIMIT %d, %d", $skip, $limit 
			);
			$entries = $wpdb->get_results( $sql );
		}
		else {
			$sql = $wpdb->prepare( "SELECT p.ID, p.post_title, 
				MAX(CASE WHEN pm.meta_key = '_wp_attachment_metadata' THEN pm.meta_value END) AS metadata
				FROM $wpdb->posts p
				INNER JOIN $wpdb->postmeta pm ON pm.post_id = p.ID
				WHERE post_type = 'attachment'
				AND pm.meta_key = '_wp_attachment_metadata'
				AND p.post_mime_type LIKE 'image/%%'
				$whereIsIn
				AND p.post_title LIKE %s
				GROUP BY p.ID
				$orderSql
				LIMIT %d, %d", ( '%' . $search . '%' ), $skip, $limit 
			);
			$entries = $wpdb->get_results( $sql );
		}
		foreach ( $entries as $entry ) {
			$entry->ID = (int)$entry->ID;
			$entry->info = $this->core->retina_info( $entry->ID, ARRAY_A );
			$entry->webp_info = $this->core->webp_info( $entry->ID, ARRAY_A );
			$entry->webp_retina_info = $this->core->webp_retina_info( $entry->ID, ARRAY_A );
			$entry->thumbnail_url = wp_get_attachment_thumb_url( $entry->ID );
			$entry->url = wp_get_attachment_url( $entry->ID );
			$entry->metadata = unserialize( $entry->metadata );
			$entry->metadata = $this->core->postprocess_metadata( $entry->metadata );
			$attached_file = get_attached_file( $entry->ID );
			$entry->filesize = $attached_file ? size_format( filesize( $attached_file ), 2 ) : 0;
			$version = get_post_meta( $entry->ID, '_media_version', true );
			$entry->optimized = get_post_meta( $entry->ID, '_wr2x_optimize', true );
			$entry->version = (int)$version;
		}
		return $entries;
	}

	function get_filtered_post_ids( $filterBy ) {
		switch ( $filterBy ) {
			case 'ignored':
				return $this->core->get_ignores();
				break;

			case 'optimizeIssues':
				return $this->core->get_optimize_issues();
				break;

			default:
				return null;
				break;
		}
	}

	function rest_media( $request ) {
		$limit = trim( $request->get_param('limit') );
		$skip = trim( $request->get_param('skip') );
		$filterBy = trim( $request->get_param('filterBy') );
		$orderBy = trim( $request->get_param('orderBy') );
		$order = trim( $request->get_param('order') );
		$search = sanitize_text_field( $request->get_param('search') );
		$entries = $this->get_media_status( $skip, $limit, $filterBy, $orderBy, $order, $search );
		$total = 0;
		if ( $filterBy == 'ignored' ) {
			$total = $this->count_ignored($search);
		}
		else if ( $filterBy == 'all' ) {
			$total = $this->count_all($search);
		}
		return new WP_REST_Response( [ 'success' => true, 'data' => $entries, 'total' => $total ], 200 );
	}

	function rest_get_details( $request ) {
		// Check errors
		$params = $request->get_json_params();
		$mediaId = isset( $params['mediaId'] ) ? (int)$params['mediaId'] : null;
		if ( empty( $mediaId ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => "The Media ID is required." ] );
		}

		// Prepare result
		$info = $this->core->retina_info( $mediaId, ARRAY_A );
		return new WP_REST_Response( [ 'success' => true, 'data' => $info ], 200 );
	}

	function rest_get_webp_details( $request ) {
		// Check errors
		$params = $request->get_json_params();
		$mediaId = isset( $params['mediaId'] ) ? (int)$params['mediaId'] : null;
		if ( empty( $mediaId ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => "The Media ID is required." ] );
		}

		// Prepare result
		$info = $this->core->webp_info( $mediaId, ARRAY_A );
		return new WP_REST_Response( [ 'success' => true, 'data' => $info ], 200 );
	}

	

	function rest_build_retina( $request ) {
		// Check errors
		$params = $request->get_json_params();
		$mediaId = isset( $params['mediaId'] ) ? (int)$params['mediaId'] : null;
		if ( empty( $mediaId ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => "The Media ID is required." ] );
		}

		// Build retina
		do_action( 'wr2x_before_regenerate', $mediaId );
		$this->engine->delete_retina_attachment( $mediaId, false );
		$this->engine->delete_webp_retina_attachment( $mediaId, false );
		$meta = wp_get_attachment_metadata( $mediaId );
		$this->engine->generate_retina_images( $meta );
		$this->engine->generate_webp_retina_images( $meta );
		do_action( 'wr2x_regenerate', $mediaId );

		// Prepare result
		$info = $this->core->get_media_status_one( $mediaId );
		return new WP_REST_Response( [ 'success' => true, 'data' => $info  ], 200 );
	}

	function rest_delete_retina( $request ) {
		if ( !current_user_can( 'upload_files' ) ) {
			$this->core->log( "You do not have permission to upload files." );
			return __( "You do not have permission to upload files.", 'wp-retina-2x' );
		}
		$params = $request->get_json_params();

		// Check errors
		$mediaId = isset( $params['mediaId'] ) ? (int)$params['mediaId'] : null;
		if ( empty( $mediaId ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => "The Media ID is required." ] );
		}

		// Delete Retina
		$this->engine->delete_retina_fullsize( $mediaId );
		$this->engine->delete_retina_attachment( $mediaId, true );
		$this->engine->delete_webp_retina_attachment( $mediaId, true );
		$meta = wp_get_attachment_metadata( $mediaId );
		$info = $this->core->get_media_status_one( $mediaId );
		return new WP_REST_Response( [ 'success' => true, 'data' => $info  ], 200 );
	}

	function rest_build_webp( $request ) {
		// Check errors
		$params = $request->get_json_params();
		$mediaId = isset( $params['mediaId'] ) ? (int)$params['mediaId'] : null;
		if ( empty( $mediaId ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => "The Media ID is required." ] );
		}

		// Build WebP and WebP Retina
		do_action( 'wr2x_before_regenerate', $mediaId );
		$this->engine->delete_webp_attachment( $mediaId, false );
		$this->engine->delete_webp_retina_attachment( $mediaId, false );
		$meta = wp_get_attachment_metadata( $mediaId );
		$this->engine->generate_modern_format( $meta );
		$this->engine->generate_webp_retina_images( $meta );
		do_action( 'wr2x_regenerate', $mediaId );

		// Prepare result
		$info = $this->core->get_media_status_one( $mediaId );
		return new WP_REST_Response( [ 'success' => true, 'data' => $info  ], 200 );
	}

	function rest_delete_webp( $request ) {
		if ( !current_user_can( 'upload_files' ) ) {
			$this->core->log( "You do not have permission to upload files." );
			return __( "You do not have permission to upload files.", 'wp-retina-2x' );
		}
		$params = $request->get_json_params();

		// Check errors
		$mediaId = isset( $params['mediaId'] ) ? (int)$params['mediaId'] : null;
		if ( empty( $mediaId ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => "The Media ID is required." ] );
		}

		// Delete WebP and WebP Retina
		$this->engine->delete_webp_fullsize( $mediaId );
		$this->engine->delete_webp_attachment( $mediaId, true );
		$this->engine->delete_webp_retina_attachment( $mediaId, true );
		$info = $this->core->get_media_status_one( $mediaId );
		return new WP_REST_Response( [ 'success' => true, 'data' => $info  ], 200 );
	}

	function rest_ignore( $request ) {
		$params = $request->get_json_params();
		$mediaIds = is_array( $params['mediaIds'] ) ? $params['mediaIds'] : array( $params['mediaIds'] );

		// Check errors
		if ( empty( $mediaIds ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => "At least one Media ID is required." ] );
		}

		// Toggle ignore status and collect media status
		$infos = [];
		foreach( $mediaIds as $mediaId ) {
			if ( $this->core->is_ignore( $mediaId ) ) {
				$this->core->remove_ignore( $mediaId );
			}
			else {
				$this->core->add_ignore( $mediaId );
			}
			// Return updated media status
			$infos[] = $this->core->get_media_status_one( $mediaId );
		}

		return new WP_REST_Response( [ 'success' => true, 'data' => $infos  ], 200 );
	}

	function rest_regenerate( $request ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$params = $request->get_json_params();
		$mediaId = isset( $params['mediaId'] ) ? (int)$params['mediaId'] : null;
		$optimized = isset( $params['optimized'] ) ? (bool)$params['optimized'] : false;
		$ai_generate = isset( $params['ai_generate'] ) ? (bool)$params['ai_generate'] : false;

		// Check errors
		if ( empty( $mediaId ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => "The Media ID is required." ] );
		}


		$response =[ 
			'success' => true,
			'data' => [],
		];

		
		// Regenerate
		if ( $optimized ) {
			$this->core->regenerate_thumbnails_optimized( $mediaId );
		}

		if( !$optimized && !$ai_generate ) {
			$this->core->regenerate_thumbnails( $mediaId );
		}

		if ( !$optimized && $ai_generate ) {
			$res = $this->core->regenerate_thumbnails_ai( $mediaId );
			
			if ( is_wp_error( $res ) ) {
				$response['success'] = false;
				$response['message'] = $res->get_error_message();

				return new WP_REST_Response( $response, 200 );
			}

			$response['history'] = $res;
		}

		$info = $this->core->get_media_status_one( $mediaId );
		$response['data'] = $info;

		return new WP_REST_Response( $response, 200 );
	}

	function rest_optimize( $request ) {
		$params = $request->get_json_params();
		$mediaId = isset( $params['mediaId'] ) ? (int)$params['mediaId'] : null;

		// Check errors
		if ( empty( $mediaId ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => "The Media ID is required." ] );
		}

		// Regenerate
		$optimizer = new Meow_WR2X_Optimize( $this->core );
		$optimizer->optimize_image( $mediaId );
		$info = $this->core->get_media_status_one( $mediaId );
		return new WP_REST_Response( [ 'success' => true, 'data' => $info  ], 200 );
	}

	function rest_update_option( $request ) {
		$value = null;
		$old_options = [];
		$custom_image_size_changes = [];
		try {
			$params = $request->get_json_params();
			$value = $params['options'];

			// Check the changes of the option, custom_image_sizes.
			$old_options = $this->core->get_option( 'custom_image_sizes' );
			$new_options = $value['custom_image_sizes'];
			$custom_image_size_changes = $this->core->get_custom_image_size_changes( $old_options, $new_options );

			$options = $this->core->update_options( $value );
			$success = !!$options;

			if ( $success && !empty( $custom_image_size_changes ) ) {
				foreach ( $custom_image_size_changes as $change ) {
					$values = $change['value'];
					//TODO: Should use core->add_image_sizes instead
					$this->core->register_custom_image_size(
						$change['type'],
						$values['name'],
						$values['width'],
						$values['height'],
						$values['crop']
					);
				}
				$options = $this->core->sanitize_options( $options );
			}

			$message = __( $success ? 'OK' : "Could not update options.", 'wp-retina-2x' );
			return new WP_REST_Response([ 'success' => $success, 'message' => $message, 'options' => $options ], 200 );
		}
		catch ( Exception $e ) {
			if ( !empty( $custom_image_size_changes ) && !empty( $value ) ) {
				// Rollback the options when the change was custom_image_sizes.
				$this->core->update_options( array_merge( $value, ['custom_image_sizes' => $old_options ] ) );
			}
			return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 500 );
		}
	}

	function rest_easy_io_unlink( $request ) {
		$options = $this->core->get_options();
		$options['easyio_domain'] = '';
		$options['easyio_plan'] = '';
		$options['webp_force_with_easyio'] = false;
		$this->core->update_options( $options );
		return new WP_REST_Response([ 'success' => true ], 200 );
	}

	function rest_easy_io_link( $request ) {
		try {
			$error_message = null;
			$site_url = get_site_url();
			$home_url = get_home_url();
			$url = 'http://optimize.exactlywww.com/exactdn/activate.php';
			$ssl = wp_http_supports( array( 'ssl' ) );
			if ( $ssl ) {
				$url = set_url_scheme( $url, 'https' );
			}
			//add_filter( 'http_headers_useragent', 'perfect_images', PHP_INT_MAX );
			$result = wp_remote_post( $url, array( 'timeout' => 10, 'body'    => array( 'site_url' => $site_url, 'home_url' => $home_url ) ) );

			if ( is_wp_error( $result ) ) {
				$error_message = $result->get_error_message();
			} 
			else if ( !empty( $result['body'] ) && strpos( $result['body'], 'domain' ) !== false ) {
				$response = json_decode( $result['body'], true );
				$options = $this->core->get_options();
				if ( !empty( $response['domain'] ) ) {
					$options['easyio_domain'] = $response['domain'];
					if ( !empty( $response['plan_id'] ) ) {
						$options['easyio_plan'] = (int)$response['plan_id'];
					}
					$this->core->update_options( $options );

					// Clear cache
					// From https://github.com/nosilver4u/ewww-image-optimizer/blob/master/classes/class-exactdn.php#L298
					if ( 'external' === get_option( 'elementor_css_print_method' ) ) {
						update_option( 'elementor_css_print_method', 'internal' );
					}
					if ( function_exists( 'et_get_option' ) && function_exists( 'et_update_option' ) && 
						'on' === et_get_option( 'et_pb_static_css_file', 'on' ) ) {
						et_update_option( 'et_pb_static_css_file', 'off' );
						et_update_option( 'et_pb_css_in_footer', 'off' );
					}
					if ( function_exists( 'envira_flush_all_cache' ) ) {
						envira_flush_all_cache();
					}
				}
			} 
			else if ( !empty( $result['body'] ) && false !== strpos( $result['body'], 'error' ) ) {
				$response = json_decode( $result['body'], true );
				$error_message = $response['error'];
			}
			if ( $error_message ) {
				return new WP_REST_Response([ 'success' => false, 'message' => $error_message ], 200 );
			}
			return new WP_REST_Response([ 'success' => true, 'logs' => json_decode( $result['body'] ) ], 200 );
		} 
		catch ( Exception $e ) {
			return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 500 );
		}
	}

	function rest_easy_io_stats( $request ) {
		try {
			$error_message = null;
			$stats = null;
			$url = 'http://optimize.exactlywww.com/exactdn/savings.php';
			$ssl = wp_http_supports( array( 'ssl' ) );
			if ( $ssl ) {
				$url = set_url_scheme( $url, 'https' );
			}
			$easyio_domain = $this->core->get_option( 'easyio_domain' );
			$result = wp_remote_post( $url, array( 'timeout' => 10, 'body' => array( 'alias' => $easyio_domain ) ) );
			if ( is_wp_error( $result ) ) {
				$error_message = $result->get_error_message();
			} 
			else if ( !empty( $result['body'] ) ) {
				$stats = json_decode( $result['body'], true );
			} 
			else if ( !empty( $result['body'] ) && false !== strpos( $result['body'], 'error' ) ) {
				$response = json_decode( $result['body'], true );
				$error_message = $response['error'];
			}
			if ( $error_message ) {
				return new WP_REST_Response([ 'success' => false, 'message' => $error_message ], 200 );
			}
			return new WP_REST_Response([ 'success' => true, 'stats' => $stats ], 200 );
		} 
		catch ( Exception $e ) {
			return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 500 );
		}
	}

}

?>