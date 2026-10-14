<?php

require trailingslashit( WR2X_PATH ) . 'vendor/autoload.php';
require_once( ABSPATH . 'wp-admin/includes/image.php' );

class Meow_WR2X_Core {

	private static $plugin_option_name = 'wr2x_options';
	private $option_name = 'wr2x_options';

	public $method = false;
	public $retina_sizes = array();
	public $disabled_sizes = array();
	public $webp_sizes = array();
	public $webp_retina_sizes = array();
	public $lazy = false;
	public $admin = null;
	private $engine;

	// The settings for this request, so a get_option() does not rebuild everything each time.
	// Two rules, both learned the hard way:
	//   1. Nothing that reads the options may write them. The reader and the writer calling
	//      each other is what filled the memory in 7.2.0 (get_options never writes, only
	//      update_options does, and get_options() is the only way in).
	//   2. Only stored settings belong in here. Anything computed from the state of WordPress
	//      (the image sizes above all) has to be recomputed on each read: this cache is filled
	//      on plugins_loaded, and themes register their sizes later, on after_setup_theme.
	private $options = null;

	public function __construct() {
		global $wr2x_core;
		global $wr2x_engine;
		if ( !empty( $wr2x_core ) ) {
			return $wr2x_core;
		}
		if ( !empty( $wr2x_engine ) ) {
			return $wr2x_engine;
		}
		$wr2x_core = $this;
		$this->engine = new Meow_WR2X_Engine( $this );
		$wr2x_engine = $this->engine;

		//$this->init();
		add_action( 'plugins_loaded', array( $this, 'init' ) );
		include( trailingslashit( WR2X_PATH ) . 'classes/api.php' );
		if ( class_exists( 'MeowPro_WR2X_Core' ) ) {
			new MeowPro_WR2X_Core( $this, $this->engine );
		}
	}

	function init() {
		$options = $this->get_options();

		// The options are kept in memory for the request, but they belong to one site: on
		// multisite, switching blogs has to start from the other site's settings.
		add_action( 'switch_blog', array( $this, 'reset_options_cache' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );
		add_filter( 'generate_rewrite_rules', array( 'Meow_WR2X_Admin', 'generate_rewrite_rules' ) );
		add_filter( 'retina_validate_src', array( $this, 'validate_src' ) );
		add_filter( 'wp_calculate_image_srcset', array( $this, 'calculate_image_srcset' ), 1000, 5 );

		add_filter( 'wp_calculate_image_srcset', array( $this, 'replace_with_webp_in_srcset' ), 1000, 5 );
		add_filter( 'wp_get_attachment_image_src', array( $this,'replace_with_webp_in_src' ), 1000, 4 );

		// By default the CDN is only applied to the srcset candidates, so the plain src of
		// an image, og:image, download links and anything built from wp_get_attachment_url()
		// keeps hitting the origin. This opt-in extends it to those URLs. Priority 1001 so
		// replace_with_webp_in_src() still receives an origin URL it can map to a file.
		$has_cdn = !empty( $options['easyio_domain'] ) || !empty( $options['cdn_domain'] );
		if ( $has_cdn && ( $options['cdn_all_urls'] ?? false ) ) {
			add_filter( 'wp_get_attachment_url', array( $this, 'cdn_attachment_url' ), 1001, 2 );
			add_filter( 'wp_get_attachment_image_src', array( $this, 'cdn_attachment_image_src' ), 1001, 2 );
		}

		add_action( 'after_setup_theme', array( $this, 'add_image_sizes' ) );

		if ( $options['image_replace'] ) {
			add_filter( 'media_row_actions', array( $this, 'add_action_link'), 10, 2 );
		}

		add_filter( 'attachment_fields_to_edit', array( $this, 'add_replace_image_button'), 10, 2 );
		add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );

		if ( $options['big_image_size_threshold'] ?? false ) {
			add_filter( 'big_image_size_threshold', array( $this, 'big_image_size_threshold' ) );
		}

		// WP 7.1 client-side media processing builds sub-sizes in the browser from the list
		// returned by wp_get_missing_image_subsizes(), and forces intermediate_image_sizes_advanced
		// to an empty array during that upload. Our size suppression has to be declared on both
		// filters or it is silently ignored. They share the ( $sizes, $image_meta ) signature,
		// so the same callbacks work for both.
		if ( $options['gif_thumbnails_disabled'] ?? false ) {
			add_filter( 'intermediate_image_sizes_advanced', array( $this, 'disable_upload_sizes_gif' ), 10, 2);
			add_filter( 'wp_get_missing_image_subsizes', array( $this, 'disable_upload_sizes_gif' ), 10, 2);
		}

		if( $options['avif_thumbnails_disabled'] ?? false ) {
			add_filter( 'intermediate_image_sizes_advanced', array( $this, 'disable_upload_sizes_avif' ), 10, 2);
			add_filter( 'wp_get_missing_image_subsizes', array( $this, 'disable_upload_sizes_avif' ), 10, 2);
		}

		if ( $options['webp_thumbnails_disabled'] ?? false ) {
			add_filter( 'intermediate_image_sizes_advanced', array( $this, 'disable_upload_sizes_webp' ), 10, 2);
			add_filter( 'wp_get_missing_image_subsizes', array( $this, 'disable_upload_sizes_webp' ), 10, 2);
		}

		// Disable Image-Sizes based on Settings.
		if ( !empty( $this->disabled_sizes ) ) {
			$this->disable_image_sizes();
		}

		if ( MeowKit_WR2X_Helpers::is_rest() ) {
			new Meow_WR2X_Rest( $this, $this->engine );
			return;
		}

		if ( $this->method == "Picturefill" ) {
			add_action( 'wp_head', array( $this, 'picture_buffer_start' ) );
			add_action( 'wp_footer', array( $this, 'picture_buffer_end' ) );
		}
		else if ( $this->method == 'HTML Rewrite' ) {
			$is_retina = false;
			if ( isset( $_COOKIE['devicePixelRatio'] ) ) {
				$is_retina = ceil( floatval( $_COOKIE['devicePixelRatio'] ) ) > 1;
			}
			if ( $is_retina || $this->is_debug() ) {
				add_action( 'wp_head', array( $this, 'buffer_start' ) );
				add_action( 'wp_footer', array( $this, 'buffer_end' ) );
			}
		}

		// Admin Screens
		if ( is_admin() ) {
			$this->admin = new Meow_WR2X_Admin( $this );
			$dashboard_enabled = $options['module_dashboard_enabled'] ?? !$options['hide_retina_dashboard'];
			if ( $dashboard_enabled ) {
				new Meow_WR2X_Dashboard( $this );
			}
			if ( !$options["hide_retina_column"] ) {
				new Meow_WR2X_Library( $this );
			}
		}
	}

	function big_image_size_threshold() {
		return false;
	}

	function is_rest() {
		if ( empty( $_SERVER[ 'REQUEST_URI' ] ) )
			return false;
		$rest_prefix = trailingslashit( rest_get_url_prefix() );
		return strpos( $_SERVER[ 'REQUEST_URI' ], $rest_prefix ) !== false ? true : false;
	}

	function add_action_link( $actions, $post ) {
		$actions['wr2x-replace'] = '<span class="wr2x-action-link" data-id="' . $post->ID . '">Replace Image</span>';
		return $actions;
	}

	function add_replace_image_button( $form_fields, $post ) {
		$show_image_button = $this->get_option( 'image_replace' );
		if ( !$show_image_button ) {
			return $form_fields;
		}
		$form_fields['replace_image'] = array(
			'value' => '',
			'required' => false,
			'input' => 'html',
			'html' => '<span class="wr2x-replace-image-button" data-id="' . $post->ID . '"></span>',
			'label' => '',
			'helps' => '',
		);
		return $form_fields;
	}

	function add_metabox() {
		// Show nothing if the contents of the meta box are all disabled.
		$show_image_button = $this->get_option( 'image_replace' );
		if ( !$show_image_button ) {
			return;
		}
		add_meta_box( 'wr2x-metabox', 'Perfect Image', array( $this, 'render_metabox' ), 'attachment', 'side', 'default' );
	}

	function render_metabox( $post ) {
		echo '<div id="wr2x-metabox-item" data-id="' . $post->ID . '"></div>';
	}

	function add_image_sizes() {
		$custom_image_sizes = $this->get_option( 'custom_image_sizes' );
		foreach ( $custom_image_sizes as $details ) {
			
			if ( in_array( $details['crop'], ['yes', 'no'] ) ) {
				$details['crop'] = $details['crop'] === 'yes';
			} else {
				$details['crop'] = explode( '-', $details['crop'] );
			}

			add_image_size( $details['name'], $details['width'], $details['height'], $details['crop'] );
		}
	}

	/**
	 *
	 * REMOVE (DISABLE) IMAGE SIZES
	 *
	 */

	function disable_image_sizes() {
		$wr2x_disabled_sizes = $this->get_option( 'disabled_sizes', array() );
		foreach ( $wr2x_disabled_sizes as $size ) {
			remove_image_size( $size );
		}
		add_filter( 'image_size_names_choose', array( $this, 'unset_image_sizes' ) );
		add_filter( 'intermediate_image_sizes_advanced', array( $this, 'unset_image_sizes' ) );
		// remove_image_size() only works for sizes added via add_image_size(), never for the
		// built-in ones (thumbnail, medium, medium_large, large). Those are suppressed by the
		// filters alone, so wp_get_missing_image_subsizes() must be covered too: it is what
		// WP 7.1 client-side processing reads to decide which sub-sizes the browser generates.
		add_filter( 'wp_get_missing_image_subsizes', array( $this, 'unset_image_sizes' ) );
	}

	function unset_image_sizes( $sizes ) {
		$wr2x_disabled_sizes = $this->get_option( 'disabled_sizes', array() );
		foreach ( $wr2x_disabled_sizes as $size ) {
			unset( $sizes[$size] );
		}
		return $sizes;
	}

	function disable_upload_sizes_gif( $sizes, $metadata ) {
		// wp_get_missing_image_subsizes() can pass metadata without a file (meta error),
		// and wp_check_filetype( null ) is deprecated in PHP 8.1+.
		if ( empty( $metadata['file'] ) ) {
			return $sizes;
		}

		// Get filetype data.
		$filetype = wp_check_filetype( $metadata['file'] );

		// Check if is gif.
		if( $filetype['type'] == 'image/gif' ) {
			// Unset sizes if file is gif.
			$sizes = array();
		}

		// Return sizes you want to create from image (None if image is gif.)
		return $sizes;
	}

	function disable_upload_sizes_avif( $sizes, $metadata ) {
		if ( empty( $metadata['file'] ) ) {
			return $sizes;
		}

		// Get filetype data.
		$filetype = wp_check_filetype( $metadata['file'] );

		// Check if is avif.
		if( $filetype['type'] == 'image/avif' ) {
			// Unset sizes if file is avif.
			$sizes = array();
		}

		// Return sizes you want to create from image (None if image is avif.)
		return $sizes;
	}

	function disable_upload_sizes_webp( $sizes, $metadata ) {
		if ( empty( $metadata['file'] ) ) {
			return $sizes;
		}

		// Get filetype data.
		$filetype = wp_check_filetype( $metadata['file'] );

		// Check if is webp.
		if( $filetype['type'] == 'image/webp' ) {
			// Unset sizes if file is webp.
			$sizes = array();
		}

		// Return sizes you want to create from image (None if image is webp.)
		return $sizes;
	}

	/**
	 *
	 * PICTURE METHOD
	 *
	 */

	function is_supported_image( $url ) {
		$wr2x_supported_image = array( 'jpg', 'jpeg', 'png', 'gif' );
		$ext = strtolower( pathinfo( $url, PATHINFO_EXTENSION ) );
		if ( !in_array( $ext, $wr2x_supported_image ) ) {
			$this->log( "Extension (" . $ext . ") is not " . implode( ', ', $wr2x_supported_image ) . "." );
			return false;
		}
		return true;
	}

	function picture_buffer_start() {
		ob_start( array( $this, "picture_rewrite" ) );
		$this->log( "* HTML REWRITE FOR PICTUREFILL" );
	}

	function picture_buffer_end() {
		@ob_end_flush();
	}

	// Replace the IMG tags by PICTURE tags with SRCSET
	function picture_rewrite( $buffer ) {
		if ( !isset( $buffer ) || trim( $buffer ) === '' )
			return $buffer;
		$html = new KubAT\PhpSimple\HtmlDomParser();
		$killSrc = !$this->get_option( "picturefill_keep_src" );
		$nodes_count = 0;
		$nodes_replaced = 0;
		$html = $html->str_get_html( $buffer );
		if ( !$html ) {
			$this->log( "The HTML buffer is null, another plugin might block the process." );
			return $buffer;
		}

		// IMG TAGS
		foreach( $html->find( 'img' ) as $element ) {
			$nodes_count++;
			$parent = $element->parent();
			if ( $parent->tag == "picture" ) {
				$this->log("The img tag is inside a picture tag. Tag ignored.");
				continue;
			}
			else {
				$valid = apply_filters( "wr2x_validate_src", $element->src );
				if ( empty( $valid ) ) {
					$nodes_count--;
					continue;
				}

				// Original HTML
				$from = substr( $element, 0 );

				// SRC-SET already exists, let's check if LazySize is used
				if ( !empty( $element->srcset ) ) {
					continue;
				}

				// Process of SRC-SET creation
				if ( !$this->is_supported_image( $element->src ) ) {
					$nodes_count--;
					continue;
				}
				$retina_url = $this->get_retina_from_url( $element->src );
				$retina_url = apply_filters( 'wr2x_img_retina_url', $retina_url );
				if ( $retina_url != null ) {
					$retina_url = $this->cdn_this( $retina_url );
					$img_url = $this->cdn_this( $element->src );
					$img_url  = apply_filters( 'wr2x_img_url', $img_url  );
					$element->srcset = "$img_url, $retina_url 2x";

					if ( $killSrc )
						$element->src = null;
					else {
						$img_src = apply_filters( 'wr2x_img_src', $element->src  );
						$element->src = $this->cdn_this( $img_src );
					}
					$to = $element;
					$buffer = str_replace( trim( $from, "</> "), trim( $to, "</> " ), $buffer );
					$this->log( "The img tag '$from' was rewritten to '$to'" );
					$nodes_replaced++;
				}
				else {
					$this->log( "The img tag was not rewritten. No retina for '" . $element->src . "'." );
				}
			}
		}
		$this->log( "$nodes_replaced/$nodes_count img tags were replaced." );

		// INLINE CSS BACKGROUND
		if ( $this->get_option( 'picturefill_css_background', false ) && class_exists( 'MeowPro_WR2X_Core' ) ) {
			// Standard CSS background
			preg_match_all( "/url(?:\(['\"]?)(.*?)(?:['\"]?\))/", $buffer, $matches );
			//error_log( print_r( $matches, 1 ) );
			if ( count( $matches ) == 2 ) {
				$match_css = $matches[0];
				$match_url = $matches[1];
			}
			// Lazy CSS background
			preg_match_all( "/data-background=(?:['\"])(.*?)(?:['\"])/", $buffer, $matches );
			if ( count( $matches ) == 2 ) {
				$match_css = array_merge( $match_css, $matches[0] );
				$match_url = array_merge( $match_url, $matches[1] );
			}
			// Lazy CSS background
			preg_match_all( "/data-bigimg=(?:['\"])(.*?)(?:['\"])/", $buffer, $matches );
			if ( count( $matches ) == 2 ) {
				$match_css = array_merge( $match_css, $matches[0] );
				$match_url = array_merge( $match_url, $matches[1] );
			}
			$nodes_count = 0;
			$nodes_replaced = 0;
			for ( $c = 0; $c < count( $match_css ); $c++ ) {
				$css = $match_css[$c];
				$url = $match_url[$c];
				if ( !$this->is_supported_image( $url ) )
					continue;
				$nodes_count++;
				$retina_url = $this->get_retina_from_url( $url );
				$retina_url = apply_filters( 'wr2x_img_retina_url', $retina_url );
				if ( $retina_url != null ) {
					$retina_url = $this->cdn_this( $retina_url );
					$minibuffer = str_replace( $url, $retina_url, $css );
					$buffer = str_replace( $css, $minibuffer, $buffer );
					$this->log( "The background src '$css' was rewritten to '$minibuffer'" );
					$nodes_replaced++;
				}
				else {
					$this->log( "The background src was not rewritten. No retina for '" . $url . "'." );
				}
			}
			$this->log( "$nodes_replaced/$nodes_count background src were replaced." );
		}

		$html->clear();
		return $buffer;
	}

	/**
	 *
	 * HTML REWRITE METHOD
	 *
	 */

	function buffer_start () {
		ob_start( array( $this, "html_rewrite" ) );
		$this->log( "* HTML REWRITE" );
	}

	function buffer_end () {
		@ob_end_flush();
	}

	// Replace the images by retina images (if available)
	function html_rewrite( $buffer ) {
		if ( !isset( $buffer ) || trim( $buffer ) === '' )
			return $buffer;
		$nodes_count = 0;
		$nodes_replaced = 0;
		$doc = new DOMDocument();
		@$doc->loadHTML( $buffer ); // = ($doc->strictErrorChecking = false;)
		$imageTags = $doc->getElementsByTagName('img');
		foreach ( $imageTags as $tag ) {
			$nodes_count++;
			$img_pathinfo = $this->get_pathinfo_from_image_src( $tag->getAttribute('src') );
			$filepath = trailingslashit( $this->get_upload_root() ) . $img_pathinfo;
			$system_retina = $this->get_retina( $filepath ) ;
			if ( !empty( $system_retina ) ) {
				$retina_pathinfo = $this->cdn_this( ltrim( str_replace( $this->get_upload_root(), "", $system_retina ), '/' ) );
				$buffer = str_replace( $img_pathinfo, $retina_pathinfo, $buffer );
				$this->log( "The img src '$img_pathinfo' was replaced by '$retina_pathinfo'" );
				$nodes_replaced++;
			}
			else {
				$this->log( "The file '$system_retina' was not found. Tag not modified." );
			}
		}
		$this->log( "$nodes_replaced/$nodes_count were replaced." );
		return $buffer;
	}


	// Converts PHP INI size type (e.g. 24M) to int
	function parse_ini_size( $size ) {
		$unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
		$size = preg_replace('/[^0-9\.]/', '', $size);
		if ( $unit )
			return round( $size * pow( 1024, stripos( 'bkmgtpezy', $unit[0] ) ) );
		else
			round( $size );
	}

	function get_max_filesize() {
		if ( defined ('HHVM_VERSION' ) ) {
			$post_max_size = ini_get( 'post_max_size' ) ? (int)$this->parse_ini_size( ini_get( 'post_max_size' ) ) : (int)ini_get( 'hhvm.server.max_post_size' );
			$upload_max_filesize = ini_get( 'upload_max_filesize' ) ? (int)$this->parse_ini_size( ini_get( 'upload_max_filesize' ) ) :
				(int)ini_get( 'hhvm.server.upload.upload_max_file_size' );
		}
		else {
			$post_max_size = (int)$this->parse_ini_size( ini_get( 'post_max_size' ) );
			$upload_max_filesize = (int)$this->parse_ini_size( ini_get( 'upload_max_filesize' ) );
		}
		$max = min( $post_max_size, $upload_max_filesize );
		return $max > 0 ? $max : 66600000;
	}

	/**
	 *
	 * RESPONSIVE IMAGES METHOD
	 *
	 */

	function calculate_image_srcset( $srcset, $size, $image_src, $image_meta, $attachment_id ) {
		if ( $this->get_option( "disable_responsive" ) )
			return null;
		if ( empty( $srcset ) ) {
			return $srcset;
		}
		$retinized_srcset = $srcset;
		$count = 0;
		$total = 0;
		foreach ( $srcset as $s => $cfg ) {
			$total++;
			$retinized_srcset[$s]['url'] = $this->cdn_this( $cfg['url'], $attachment_id );
			if ( $this->method !== "none" ) {
				$retinaForUrl = $this->get_retina_from_url( $cfg['url'] );
				if ( !empty( $retinaForUrl ) ) {
					$retina = $this->cdn_this( $retinaForUrl, $attachment_id );
					if ( !empty( $retina ) ) {
						$count++;
						$retinized_srcset[(int)$s * 2] = array( 'url' => $retina, 'descriptor' => 'w', 'value' => (int)$s * 2 ); 
					}
				}
			}
		}
		$this->log( "WP's srcset: " . $count . " retina files added out of " . $total . " image sizes" );

		
		return $retinized_srcset;
	}

	function replace_with_webp_in_src( $image, $attachment_id, $size, $icon ) {
		if ( $this->get_option( 'webp_method' ) !== 'Responsive' ) {
			return $image;
		}

		if ( !is_array( $image ) || empty( $image ) ) {
			return $image;
		}

		$webp_url = $image[0] . $this->webp_avif_extension();

		if ( $this->from_url_to_system( $webp_url ) ) {
			$image[0] = $webp_url;
		}

		return $image;
	}

	function replace_with_webp_in_srcset( $sources, $size, $image_src, $image_meta, $attachment_id ) {
		if ( $this->get_option( 'disable_responsive' ) ){
			return null;
		}
			
		if ( empty( $sources ) || $this->get_option( 'webp_method' ) !== 'Responsive' ) {
			return $sources;
		}

		$upload_dir = wp_upload_dir();
		$pathinfo = pathinfo( $image_meta['file'] );
		$sizes['original'] = $pathinfo['basename'];
		foreach ( $image_meta['sizes'] as $size => $details ) {
			$sizes[$size] = $details['file'];
		}

		$str_ends_with = function ( string $haystack, string $needle ) {
			$needle_len = mb_strlen( $needle );
			return ( $needle_len === 0 || 0 === substr_compare( $haystack, $needle, - $needle_len ) );
		};

		$image_extensions = array('jpg', 'jpeg', 'png');
		$image_extensions = apply_filters('wr2x_replace_ext_in_srcset', $image_extensions);

		$image_extensions_regex = implode('|', array_map(function($ext) {
			return '\.' . $ext . '$';
		}, $image_extensions));

		foreach ($sources as &$source) {
			if ( preg_match( '/' . $image_extensions_regex . '/', $source['url'] )  ) {
				$filename = null;
				foreach ( $sizes as $key => $size_filename ) {
					if ( $str_ends_with( $source['url'], $size_filename ) ) {
						$filename = $size_filename;
						unset( $sizes[$key] );
						break;
					}
				}
				if ( !$filename || !file_exists( trailingslashit( $upload_dir['basedir'] ) . trailingslashit( $pathinfo['dirname'] ) . $filename . $this->webp_avif_extension() ) ) {
					continue;
				}
				$webp_url = $source['url'] . $this->webp_avif_extension();
				$source['url'] = $webp_url;
			}
		}

		return $sources;
	}

	/**
	 *
	 * ISSUES CALCULATION AND FUNCTIONS
	 *
	 */

	// Compares two images dimensions (resolutions) against each while accepting an margin error
	function are_dimensions_ok( $width, $height, $retina_width, $retina_height ) {
		$w_margin = $width - $retina_width;
		$h_margin = $height - $retina_height;
		return ( $w_margin >= -2 && $h_margin >= -2 );
	}

	// UPDATE THE ISSUE STATUS OF THIS ATTACHMENT
	function update_issue_status( $attachmentId, $issues = null, $info = null, $webp_info = null, $webp_retina_info = null ) {
		if ( $this->is_ignore( $attachmentId ) )
			return;
		if ( $issues == null )
			$issues = $this->get_issues();
		if ( $info == null )
			$info = $this->retina_info( $attachmentId );
		if ( $webp_info == null )
			$webp_info = $this->webp_info( $attachmentId );
		if ( $webp_retina_info == null )
			$webp_retina_info = $this->webp_retina_info( $attachmentId );
		$consideredIssue = in_array( $attachmentId, $issues );
		$realIssue = ( $this->info_has_issues( $info ) || $this->info_has_issues( $webp_info ) || $this->info_has_issues( $webp_retina_info ) );
		if ( $consideredIssue && !$realIssue )
			$this->remove_issue( $attachmentId );
		else if ( !$consideredIssue && $realIssue )
			$this->add_issue( $attachmentId );
		return $realIssue;
	}

	function get_issues($search = '') {
		if ($search) {
			return $this->calculate_issues_by_search($search);
		}
		$issues = get_transient( 'wr2x_issues' );
		if ( !$issues || !is_array( $issues ) ) {
			$issues = array();
			set_transient( 'wr2x_issues', $issues );
		}
		return $issues;
	}

	// CHECK IF THE 'INFO' OBJECT CONTAINS ISSUE (RETURN TRUE OR FALSE)
	function info_has_issues( $info ) {
		foreach ( $info as $aindex => $aval ) {
			if ( is_array( $aval ) || $aval == 'PENDING' )
				return true;
		}
		return false;
	}

	function calculate_issues() {
		global $wpdb;
		$postids = $wpdb->get_col( "
			SELECT p.ID FROM $wpdb->posts p
			WHERE post_status = 'inherit'
			AND post_type = 'attachment'" . $this->create_sql_if_wpml_original() . "
			AND ( post_mime_type = 'image/jpeg' OR
				post_mime_type = 'image/jpg' OR
				post_mime_type = 'image/png' OR
				post_mime_type = 'image/gif' OR
				post_mime_type = 'image/webp' )
		" );
		$ignored = $this->get_ignores();
		$issues = array();
		foreach ( $postids as $id ) {
			if ( in_array( $id, $ignored ) ) {
				continue;
			}
			$info = $this->retina_info( $id );
			$webp_info = $this->webp_info( $id );
			$webp_retina_info = $this->webp_retina_info( $id );
			if ( $this->info_has_issues( $info ) || $this->info_has_issues( $webp_info ) || $this->info_has_issues( $webp_retina_info ) )
				array_push( $issues, $id );
		}
		//set_transient( 'wr2x_ignores', array() );
		set_transient( 'wr2x_issues', $issues );
	}

	function calculate_issues_by_search( $search ) {
		global $wpdb;
		$postids = $wpdb->get_col( $wpdb->prepare( "
			SELECT p.ID FROM $wpdb->posts p
			WHERE post_status = 'inherit'
			AND p.post_title LIKE %s
			AND post_type = 'attachment'" . $this->create_sql_if_wpml_original() . "
			AND ( post_mime_type = 'image/jpeg' OR
				post_mime_type = 'image/jpg' OR
				post_mime_type = 'image/png' OR
				post_mime_type = 'image/gif' OR
				post_mime_type = 'image/webp' )
		", ( '%' . $search . '%' ) ) );
		$ignored = $this->get_ignores();
		$issues = array();
		foreach ( $postids as $id ) {
			if ( in_array( $id, $ignored ) ) {
				continue;
			}
			$info = $this->retina_info( $id );
			$webp_info = $this->webp_info( $id );
			$webp_retina_info = $this->webp_retina_info( $id );
			if ( $this->info_has_issues( $info ) || $this->info_has_issues( $webp_info ) || $this->info_has_issues( $webp_retina_info ) )
				array_push( $issues, $id );

		}
		return $issues;
	}

	function add_issue( $attachmentId ) {
		if ( $this->is_ignore( $attachmentId ) )
			return;
		$issues = $this->get_issues();
		if ( !in_array( $attachmentId, $issues ) ) {
			array_push( $issues, $attachmentId );
			set_transient( 'wr2x_issues', $issues );
		}
		return $issues;
	}

	function remove_issue( $attachmentId, $onlyIgnore = false ) {
		$issues = array_diff( $this->get_issues(), array( $attachmentId ) );
		set_transient( 'wr2x_issues', $issues );
		if ( !$onlyIgnore )
			$this->remove_ignore( $attachmentId );
		return $issues;
	}

	// IGNORE

	function get_ignores( $search = '' ) {
		global $wpdb;
		$query = "SELECT DISTINCT pm.post_id FROM $wpdb->postmeta pm
			INNER JOIN $wpdb->posts p ON pm.post_id = p.ID
			WHERE pm.meta_key = '_wr2x_ignored'
			AND pm.meta_value = '1'
			AND p.post_type = 'attachment'";
		if ( $search ) {
			$query .= $wpdb->prepare( " AND p.post_title LIKE %s", '%' . $search . '%' );
		}
		return $wpdb->get_col( $query );
	}

	function is_ignore( $attachmentId ) {
		return (bool) get_post_meta( $attachmentId, '_wr2x_ignored', true );
	}

	function remove_ignore( $attachmentId ) {
		delete_post_meta( $attachmentId, '_wr2x_ignored' );
		return $this->get_ignores();
	}

	function add_ignore( $attachmentId ) {
		update_post_meta( $attachmentId, '_wr2x_ignored', '1' );
		$this->remove_issue( $attachmentId, true );
		return $this->get_ignores();
	}

	// OPTIMIZE ISSUES

	function get_optimize_issues( $search = '' ) {
		if ( $search ) {
			return $this->calculate_issues_by_search( $search );
		}
		$optimize_issues = get_transient( 'wr2x_optimize_issues' );
		if ( !$optimize_issues || !is_array( $optimize_issues ) ) {
			$optimize_issues = $this->calculate_optimize_issues();
		}
		return $optimize_issues;
	}

	function calculate_optimize_issues() {
		global $wpdb;
		$postids = $wpdb->get_col( "
			SELECT p.ID FROM $wpdb->posts p
			WHERE post_status = 'inherit'
			AND post_type = 'attachment'" . $this->create_sql_if_wpml_original() . "
			AND ( post_mime_type = 'image/jpeg' OR
				post_mime_type = 'image/jpg' OR
				post_mime_type = 'image/png' OR
				post_mime_type = 'image/gif' OR
				post_mime_type = 'image/webp' )
			AND NOT EXISTS ( SELECT meta_id FROM $wpdb->postmeta WHERE post_id = p.ID AND meta_key = '_wr2x_optimize' )
		" );
		$ignored = $this->get_ignores();

		// This is for PHP 7.4+
		//$optimize_issues = array_filter( $postids, fn ( $id ) => !in_array( $id, $ignored ) );

		$optimize_issues = array_filter( $postids, function( $id ) use ( $ignored ) {
			return !in_array( $id, $ignored );
		} );

		set_transient( 'wr2x_optimize_issues', $optimize_issues );
		return $optimize_issues;
	}

	function calculate_optimize_issues_by_search( $search ) {
		global $wpdb;
		$postids = $wpdb->get_col( $wpdb->prepare( "
			SELECT p.ID FROM $wpdb->posts p
			WHERE post_status = 'inherit'
			AND p.post_title LIKE %s
			AND post_type = 'attachment'" . $this->create_sql_if_wpml_original() . "
			AND ( post_mime_type = 'image/jpeg' OR
				post_mime_type = 'image/jpg' OR
				post_mime_type = 'image/png' OR
				post_mime_type = 'image/gif' OR
				post_mime_type = 'image/webp' )
			AND NOT EXISTS ( SELECT meta_id FROM $wpdb->postmeta WHERE post_id = p.ID AND meta_key = '_wr2x_optimize' )
		", ( '%' . $search . '%' ) ) );
		$ignored = $this->get_ignores();

		// This is for PHP 7.4+
		//return array_filter( $postids, fn ( $id ) => in_array( $id, $ignored ) );

		return array_filter( $postids, function( $id ) use ( $ignored ) {
			return in_array( $id, $ignored );
		} );
	}

	function add_optimize_issue( $attachmentId ) {
		if ( $this->is_ignore( $attachmentId ) )
			return;
		$optimize_issues = $this->get_optimize_issues();
		if ( !in_array( $attachmentId, $optimize_issues ) ) {
			array_push( $optimize_issues, $attachmentId );
			set_transient( 'wr2x_optimize_issues', $optimize_issues );
		}
		return $optimize_issues;
	}

	function remove_optimize_issue( $attachmentId ) {
		$optimize_issues = array_diff( $this->get_optimize_issues(), array( $attachmentId ) );
		set_transient( 'wr2x_optimize_issues', $optimize_issues );
		return $optimize_issues;
	}

	function update_optimise_issue_status( $attachment_id ) {
		$optimize_issues = $this->get_optimize_issues();
		$considered_optimize_issue = in_array( $attachment_id, $optimize_issues );
		$optimize_post_meta = get_post_meta( $attachment_id, '_wr2x_optimize', true );
		$is_optimized = !empty( $optimize_post_meta );

		if ( $considered_optimize_issue && $is_optimized )
			$this->remove_optimize_issue( $attachment_id );
		else if ( !$considered_optimize_issue && !$is_optimized )
			$this->add_optimize_issue( $attachment_id );
	}

	/**
	 *
	 * GET DETAILS / INFO
	 *
	 */

	// Get the information about a specific Media (usually for the Retina Dashboard)
	function get_media_status_one( $mediaId ) {
		$mediaId = (int)$mediaId;
		$entry = new stdClass();
		$entry->ID = $mediaId;
		$entry->post_title = get_the_title( $mediaId );
		$entry->metadata = wp_get_attachment_metadata( $mediaId, true );
		$entry->metadata = $this->postprocess_metadata( $entry->metadata );
		$entry->info = $this->retina_info( $mediaId, ARRAY_A );
		$entry->webp_info = $this->webp_info( $mediaId, ARRAY_A );
		$entry->webp_retina_info = $this->webp_retina_info( $mediaId, ARRAY_A );
		$entry->thumbnail_url = wp_get_attachment_thumb_url( $mediaId );
		$entry->url = wp_get_attachment_url( $mediaId );
		$attached_file = get_attached_file( $mediaId );
		$entry->filesize = $attached_file ? size_format( filesize( $attached_file ), 2 ) : 0;
		$version = get_post_meta( $mediaId, '_media_version', true );
		$entry->version = (int)$version;
		$entry->optimized = get_post_meta( $mediaId, '_wr2x_optimize', true );
		return $entry;
	}

	function html_get_basic_retina_info_full( $attachmentId, $retina_info ) {
		$status = ( isset( $retina_info ) && isset( $retina_info['full-size'] ) ) ? $retina_info['full-size'] : 'IGNORED';
		if ( $status == 'EXISTS' ) {
			return '<ul class="meow-sized-images"><li class="meow-bk-blue" title="full-size"></li></ul>';
		}
		else if ( is_array( $status ) ) {
			return '<ul class="meow-sized-images"><li class="meow-bk-orange" title="full-size"></li></ul>';
		}
		else if ( $status == 'IGNORED' ) {
			return __( "N/A", "wp-retina-2x" );
		}
		return $status;
	}

	function format_title( $i, $size ) {
		return $i . ' (' . ( $size['width'] * 2 ) . 'x' . ( $size['height'] * 2 ) . ')';
	}

	static function size_shortname( $name ) {
		if ( $name === 'thumbnail' ) {
			return 'T';
		}
		else if ( $name === 'medium' ) {
			return 'M';
		}
		else if ( $name === 'medium_large' ) {
			return 'ML';
		}
		else if ( $name === 'large' ) {
			return 'L';
		}
		else if ( $name === '1536x1536' ) {
			return 'W1';
		}
		else if ( $name === '2048x2048' ) {
			return 'W2';
		}
		else if ( $name === 'post-thumbnail' ) {
			return 'PT';
		}
		$name = preg_split( '[_-]', $name );
		$short = strtoupper( substr( $name[0], 0, 2 ) );
		if ( count( $name ) > 1 )
			$short .= strtoupper( substr( $name[1], 0, 2 ) );

		return $short;
	}

	// Information for the 'Media Sizes Retina-ized' Column in the Retina Dashboard
	function html_get_basic_retina_info( $attachmentId, $retina_info ) {
		$sizes = $this->get_active_image_sizes();
		$result = '<ul class="meow-sized-images" postid="' . ( is_integer( $attachmentId ) ? $attachmentId : $attachmentId->ID ) . '">';
		foreach ( $sizes as $i => $size ) {
			$status = ( isset( $retina_info ) && isset( $retina_info[$i] ) ) ? $retina_info[$i] : null;
			if ( is_array( $status ) )
				$result .= '<li class="meow-bk-red" title="' . $this->format_title( $i, $size ) . '">'
					. self::size_shortname( $i ) . '</li>';
			else if ( $status == 'EXISTS' )
				$result .= '<li class="meow-bk-blue" title="' . $this->format_title( $i, $size ) . '">'
					. self::size_shortname( $i ) . '</li>';
			else if ( $status == 'PENDING' )
				$result .= '<li class="meow-bk-orange" title="' . $this->format_title( $i, $size ) . '">'
					. self::size_shortname( $i ) . '</li>';
			else if ( $status == 'MISSING' )
				$result .= '<li class="meow-bk-red" title="' . $this->format_title( $i, $size ) . '">'
					. self::size_shortname( $i ) . '</li>';
			else if ( $status == 'IGNORED' )
				$result .= '<li class="meow-bk-gray" title="' . $this->format_title( $i, $size ) . '">'
					. self::size_shortname( $i ) . '</li>';
			else {
				$this->log( "Retina: This status is not recognized: " . $status );
			}
		}
		$result .= '</ul>';
		return $result;
	}

	// Information for Details in the Retina Dashboard
	function html_get_details_retina_info( $post, $retina_info ) {
		if ( !class_exists( 'MeowPro_WR2X_Core' ) ) {
			return __( "PRO VERSION ONLY", 'wp-retina-2x' );
		}

		$sizes = $this->get_image_sizes();
		$total = 0; $possible = 0; $issue = 0; $ignored = 0; $retina = 0;

		$postinfo = get_post( $post, OBJECT );
		$meta = wp_get_attachment_metadata( $post );
		$fullsize_file = get_attached_file( $post );
		$pathinfo_system = pathinfo( $fullsize_file );
		$pathinfo = pathinfo( $meta['file'] );
		$uploads = wp_upload_dir();
		$basepath_url = trailingslashit( $uploads['baseurl'] ) . $pathinfo['dirname'];
		if ( $this->get_option( "full_size" ) ) {
			$sizes['full-size']['file'] = $pathinfo['basename'];
			$sizes['full-size']['width'] = $meta['width'];
			$sizes['full-size']['height'] = $meta['height'];
			$meta['sizes']['full-size']['file'] = $pathinfo['basename'];
			$meta['sizes']['full-size']['width'] = $meta['width'];
			$meta['sizes']['full-size']['height'] = $meta['height'];
		}
		$result = "<p>This screen displays all the image sizes set-up by your WordPress configuration with the Retina details.</p>";
		$result .= "<br /><a target='_blank' href='" . trailingslashit( $uploads['baseurl'] ) . $meta['file'] . "'><img src='" . trailingslashit( $uploads['baseurl'] ) . $meta['file'] . "' height='100px' style='float: left; margin-right: 10px;' /></a><div class='base-info'>";
		$result .= "Title: <b>" . ( $postinfo->post_title ? $postinfo->post_title : '<i>Untitled</i>' ) . "</b><br />";
		$result .= "Full-size: <b>" . $meta['width'] . "×" . $meta['height'] . "</b><br />";
		$result .= "Image URL: <a target='_blank' href='" . trailingslashit( $uploads['baseurl'] ) . $meta['file'] . "'>" . trailingslashit( $uploads['baseurl'] ) . $meta['file'] . "</a><br />";
		$result .= "Image Path: " . $fullsize_file . "<br />";
		$result .= "</div><div style='clear: both;'></div><br />";
		$result .= "<div class='scrollable-info'>";

		foreach ( $sizes as $i => $sizemeta ) {
			$total++;
			$normal_file_system = ""; $retina_file_system = "";
			$normal_file = ""; $retina_file = ""; $width = ""; $height = "";

			if ( isset( $retina_info[$i] ) && $retina_info[$i] == 'IGNORED' ) {
				$status = "IGNORED";
			}
			else if ( !isset( $meta['sizes'] ) ) {
				$statusText  = __( "The metadata is broken! This is not related to the retina plugin. You should probably use a plugin to re-generate the missing metadata and images.", 'wp-retina-2x' );
				$status = "MISSING";
			}
			else if ( !isset( $meta['sizes'][$i] ) ) {
				$statusText  = sprintf( __( "The image size '%s' could not be found. You probably changed your image sizes but this specific image was not re-build. This is not related to the retina plugin. You should probably use a plugin to re-generate the missing metadata and images.", 'wp-retina-2x' ), $i );
				$status = "MISSING";
			}
			else {
				$normal_file_system = trailingslashit( $pathinfo_system['dirname'] ) . $meta['sizes'][$i]['file'];
				$retina_file_system = $this->get_retina( $normal_file_system );
				$normal_file = trailingslashit( $basepath_url ) . $meta['sizes'][$i]['file'];
				$retina_file = $this->get_retina_from_url( $normal_file );
				$status = ( isset( $retina_info ) && isset( $retina_info[$i] ) ) ? $retina_info[$i] : null;
				$width = $meta['sizes'][$i]['width'];
				$height = $meta['sizes'][$i]['height'];
			}

			$result .= "<h3>";

			// Status Icon
			if ( is_array( $status ) && $i == 'full-size' ) {
				$result .= '<div class="meow-sized-image meow-bk-red"></div>';
				$statusText = sprintf( __( "The retina version of the Full-Size image is missing.<br />Full-Size Retina has been checked in the Settings and this image is therefore required.<br />Please drag & drop an image of at least <b>%dx%d</b> in the <b>Full-Size Retina Upload</b> column.", 'wp-retina-2x' ), $status['width'], $status['height'] );
			}
			else if ( is_array( $status ) ) {
				$result .= '<div class="meow-sized-image meow-bk-red"></div>';
				$statusText = sprintf( __( "The Full-Size image is too small (<b>%dx%d</b>) and this size cannot be generated.<br />Please upload an image of at least <b>%dx%d</b>.", 'wp-retina-2x' ), $meta['width'], $meta['height'], $status['width'], $status['height'] );
				$issue++;
			}
			else if ( $status == 'EXISTS' ) {
				$result .= '<div class="meow-sized-image meow-bk-blue"></div>';
				$statusText = "";
				$retina++;
			}
			else if ( $status == 'PENDING' ) {
				$result .= '<div class="meow-sized-image meow-bk-orange"></div>';
				$statusText = __( "The retina image can be created. Please use the 'GENERATE' button.", 'wp-retina-2x' );
				$possible++;
			}
			else if ( $status == 'MISSING' ) {
				$result .= '<div class="meow-sized-image meow-bk-gray"></div>';
				$statusText = __( "The standard image normally created by WordPress is missing.", 'wp-retina-2x' );
				$total--;
			}
			else if ( $status == 'IGNORED' ) {
				$result .= '<div class="meow-sized-image meow-bk-gray"></div>';
				$statusText = __( "This size is ignored by your retina settings.", 'wp-retina-2x' );
				$ignored++;
				$total--;
			}

			$result .= "&nbsp;Size: $i</h3><p>$statusText</p>";

			if ( !is_array( $status ) && $status !== 'IGNORED' && $status !== 'MISSING'  ) {
				$result .= "<table><tr><th>Normal (" . $width . "×" . $height. ")</th><th>Retina 2x (" . $width * 2 . "×" . $height * 2 . ")</th></tr><tr><td><a target='_blank' href='$normal_file'><img src='$normal_file' width='100'></a></td><td><a target='_blank' href='$retina_file'><img src='$retina_file' width='100'></a></td></tr></table>";
				$result .= "<p><small>";
				$result .= "Image URL: <a target='_blank' href='$normal_file'>$normal_file</a><br />";
				$result .= "Retina URL: <a target='_blank' href='$retina_file'>$retina_file</a><br />";
				$result .= "Image Path: $normal_file_system<br />";
				$result .= "Retina Path: $retina_file_system<br />";
				$result .= "</small></p>";
			}
		}
		$result .= "</table>";
		$result .= "</div>";
		return $result;
	}

	// Get WordPress upload directory
	function get_upload_root() {
		$uploads = wp_upload_dir();
		return $uploads['basedir'];
	}

	function get_upload_root_url() {
		$uploads = wp_upload_dir();
		return $uploads['baseurl'];
	}

	// Get WordPress directory
	function get_wordpress_root() {
		return ABSPATH;
	}

	// Return the retina file if there is any (system path)
	function get_retina( $file ) {
		$pathinfo = pathinfo( $file ) ;
		if ( empty( $pathinfo ) || !isset( $pathinfo['dirname'] ) ) {
			if ( empty( $file ) ) {
				$this->log( "An empty filename was given to \$this->get_retina()." );
			}
			else {
				$this->log( "Pathinfo is null for " . $file . "." );
			}
			return null;
		}
		$retina_file = trailingslashit( $pathinfo['dirname'] ) . $pathinfo['filename'] .
			$this->retina_extension() . ( isset( $pathinfo['extension'] ) ? $pathinfo['extension'] : "" );
		if ( file_exists( $retina_file ) ){
			return $retina_file;
		}
		$this->log( "Retina file at '{$retina_file}' does not exist." );
		return null;
	}

	// Return the webp file if there is any (system path)
	function get_webp( $file ) {
		$pathinfo = pathinfo( $file ) ;
		if ( empty( $pathinfo ) || !isset( $pathinfo['dirname'] ) ) {
			if ( empty( $file ) ) {
				$this->log( "An empty filename was given to \$this->get_webp()." );
			}
			else {
				$this->log( "Pathinfo is null for " . $file . "." );
			}
			return null;
		}
		$webp_file = trailingslashit( $pathinfo['dirname'] )
			. $pathinfo['filename']
			. ( isset( $pathinfo['extension'] ) ? "." . $pathinfo['extension'] : "" )
			. $this->webp_avif_extension();
		if ( file_exists( $webp_file ) )
			return $webp_file;
		$this->log( "WebP file at '{$webp_file}' does not exist." );
		return null;
	}

	// Return the webp retina file if there is any (system path)
	function get_webp_retina( $file ) {
		$pathinfo = pathinfo( $file ) ;
		if ( empty( $pathinfo ) || !isset( $pathinfo['dirname'] ) ) {
			if ( empty( $file ) ) {
				$this->log( "An empty filename was given to \$this->get_webp_retina()." );
			}
			else {
				$this->log( "Pathinfo is null for " . $file . "." );
			}
			return null;
		}
		$webp_retina_file = trailingslashit( $pathinfo['dirname'] )
			. $pathinfo['filename']
			. $this->retina_extension()
			. ( isset( $pathinfo['extension'] ) ? $pathinfo['extension'] : "" )
			. $this->webp_avif_extension();
		if ( file_exists( $webp_retina_file ) ){
			return $webp_retina_file;
		}
		$this->log( "WebP Retina file at '{$webp_retina_file}' does not exist." );
		return null;
	}

	function get_retina_from_remote_url( $url ) {
		$over_http = $this->get_option( 'over_http_check', false ) && class_exists( 'MeowPro_WR2X_Core' );
		if ( !$over_http )
			return null;
		$potential_retina_url = $this->rewrite_url_to_retina( $url );
		$response = wp_remote_head( $potential_retina_url, array(
			'user-agent' => "MeowApps-Retina",
			'sslverify' => false,
			'timeout' => 10
		));
		if ( is_array( $response ) && is_array( $response['response'] ) && isset( $response['response']['code'] ) ) {
			if ( $response['response']['code'] == 200 ) {
				$this->log( "Retina URL: " . $potential_retina_url, true);
				return $potential_retina_url;
			}
			else
				$this->log( "Remote head failed with code " . $response['response']['code'] . "." );
		}
		$this->log( "Retina URL couldn't be found (URL -> Retina URL).", true);
	}

	// Return retina URL from the image URL
	function get_retina_from_url( $url ) {
		$this->log( "Standard URL: " . $url, true);
		$over_http = $this->get_option( 'over_http_check', false ) && class_exists( 'MeowPro_WR2X_Core' );
		$filepath = $this->from_url_to_system( $url );
		if ( empty ( $filepath ) )
			return $this->get_retina_from_remote_url( $url );
		$this->log( "Standard PATH: " . $filepath, true);
		$system_retina = $this->get_retina( $filepath );
		if ( empty ( $system_retina ) )
			return $this->get_retina_from_remote_url( $url );
		$this->log( "Retina PATH: " . $system_retina, true);
		$retina_url = $this->rewrite_url_to_retina( $url );
		$retina_url = $this->rewrite_retina_to_optimized( $retina_url );
		$this->log( "Retina URL: " . $retina_url, true);
		return $retina_url;
	}

	// Get the filepath from the URL
	function from_url_to_system( $url ) {
		$img_pathinfo = $this->get_pathinfo_from_image_src( $url );
		$filepath = trailingslashit( $this->get_wordpress_root() ) . $img_pathinfo;
		if ( file_exists( $filepath ) )
			return $filepath;
		$filepath = trailingslashit( $this->get_upload_root() ) . $img_pathinfo;
		if ( file_exists( $filepath ) )
			return $filepath;
		$this->log( "Standard PATH couldn't be found (URL -> System).", true);
		return null;
	}

	function rewrite_url_to_retina( $url ) {
		$whereisdot = strrpos( $url, '.' );
		$url = substr( $url, 0, $whereisdot ) . $this->retina_extension() . substr( $url, $whereisdot + 1 );

		return $url;
	}

	function rewrite_retina_to_optimized( $url ) {
		if ( $this->get_option( 'disable_responsive' ) ){
			return $url;
		}
		if ( $this->get_option( 'webp_method' ) !== 'Responsive' ) {
			return $url;
		}

		$opt_url = $url . $this->webp_avif_extension();
		//check if the file exists
		$opt_path = $this->from_url_to_system( $opt_url );
		if ( $opt_path ) {
			$this->log( "🟢 Optimized URL for Retina: " . $opt_url );
			return $opt_url;
		}

		//if the file doesn't exist, return the original URL
		return $url;
	}

	// Clean the PathInfo of the IMG SRC.
	// IMPORTANT: This function STRIPS THE UPLOAD FOLDER if it's found
	// REASON: The reason is that on some installs the uploads folder is linked to a different "unlogical" physical folder
	// http://wordpress.org/support/topic/cant-find-retina-file-with-custom-uploads-constant?replies=3#post-5078892
	function get_pathinfo_from_image_src( $image_src ) {
		$uploads_url = trailingslashit( $this->get_upload_root_url() );
		if ( strpos( $image_src, $uploads_url ) === 0 ){
			return ltrim( substr( $image_src, strlen( $uploads_url ) ), '/');
		}
		else if ( strpos( $image_src, wp_make_link_relative( $uploads_url ) ) === 0 ){
			return ltrim( substr( $image_src, strlen( wp_make_link_relative( $uploads_url ) ) ), '/');
		}
		$img_info = parse_url( $image_src );
		// The fix multisite is proposed by Emile 
		// (https://secure.helpscout.net/conversation/1420887033/6577?folderId=1781329)
		return ltrim( $this->fix_multisite($img_info['path']), '/' );
	}

	function fix_multisite( $path ){
		if ( !is_multisite() ) {
			return $path;
		}
		$current_blog = get_blog_details()->path;
		return '/' . str_replace( $current_blog, '', $path );
	}

	// Front-end only: editors, REST clients and cron jobs keep the origin URLs, otherwise
	// the media library, the block editor and third-party APIs would receive CDN URLs and
	// could store them. REST_REQUEST is only defined once the request is parsed, which is
	// why this is checked at call time rather than when the filters are registered.
	function is_frontend_request() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return false;
		}
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return false;
		}
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return false;
		}
		return true;
	}

	function cdn_attachment_url( $url, $attachment_id ) {
		if ( !$this->is_frontend_request() || !$this->is_cdn_url_allowed( $url, $attachment_id ) ) {
			return $url;
		}
		return $this->cdn_this( $url, $attachment_id );
	}

	function cdn_attachment_image_src( $image, $attachment_id ) {
		if ( is_array( $image ) && !empty( $image[0] ) && $this->is_frontend_request()
			&& $this->is_cdn_url_allowed( $image[0], $attachment_id ) ) {
			$image[0] = $this->cdn_this( $image[0], $attachment_id );
		}
		return $image;
	}

	// wp_get_attachment_url() is used for every attachment, not only images: PDFs, zips,
	// audio, files sold through EDD or WooCommerce... An image CDN like Easy IO either
	// 404s on those or proxies them for nothing, so only images are rewritten.
	function is_cdn_url_allowed( $url, $attachment_id = null ) {
		$mime = !empty( $attachment_id ) ? get_post_mime_type( $attachment_id ) : false;
		if ( empty( $mime ) ) {
			$path = parse_url( $url, PHP_URL_PATH );
			$filetype = wp_check_filetype( $path ? basename( $path ) : '' );
			$mime = $filetype['type'];
		}
		$allowed = !empty( $mime ) && strpos( $mime, 'image/' ) === 0;
		return apply_filters( 'wr2x_cdn_url_allowed', $allowed, $url, $attachment_id, $mime );
	}

	// Rename this filename with CDN
	function cdn_this( $url, $mediaId = null ) {

		$cdn_domain = "";
		$cdn_params = array();
		$wr2x_easyio_domain = $this->get_option( 'easyio_domain', '' );
		$wr2x_cdn_domain = $this->get_option( 'cdn_domain', '' );

		// CDN Domain
		if ( !empty( $wr2x_easyio_domain ) ) {
			$cdn_domain = "{$wr2x_easyio_domain}";
			if ( $this->get_option( 'easyio_lossless', false ) ) {
				$cdn_params['lossy'] = 0;
			}
		}
		else if ( !empty( $wr2x_cdn_domain ) ) {
			$cdn_domain = $wr2x_cdn_domain;
		}
		else {
			return $url;
		}

		// Version
		if ( !empty( $mediaId ) ) {
			$version = get_post_meta( $mediaId, '_media_version', true );
			if ( $version ) {
				$cdn_params['version'] = $version;
			}
		}

		// Parse the incoming URL to extract its host
		$parsed_url = parse_url( $url );
		if ( empty( $parsed_url['host'] ) ) {
			return $url;
		}

		// Perform additional CDN check (Issue #1631 by Martin)
		if ( $parsed_url['host'] === $cdn_domain ) {
			$this->log( "URL already has CDN: $url" );
			return $url;
		}

		// Only rewrite URLs that point to the uploads directory
		$uploads_path = parse_url( $this->get_upload_root_url(), PHP_URL_PATH );
		if ( empty( $uploads_path ) || strpos( $parsed_url['path'] ?? '', $uploads_path ) === false ) {
			return $url;
		}

		$this->log( "URL before CDN: $url" );
		$separator = empty( $parsed_url['query'] ) ? '?' : '&';
		$queryUrl = !empty( $cdn_params ) ? ( $separator . http_build_query( $cdn_params ) ) : '';
		$url_host = $parsed_url['host'];
		$new_url = str_replace( '//' . $url_host, '//' . $cdn_domain, $url ) . $queryUrl;
		$this->log( "URL with CDN: $new_url" );
		return $new_url;
	}

	// function admin_menu() {
	// 	add_options_page( 'Retina', 'Retina', 'manage_options', 'wr2x_settings', 'wr2x_settings_page' );
	// }

	function get_image_sizes( $output_type = OBJECT, $options = null ) {
		$sizes = array();
		$options = empty( $options ) ? $this->get_options() : $options;

		$webp_enabled = $options['module_webp_enabled'];
		$retina_enabled = $options['module_retina_enabled'];
		$disabled_sizes = $options['disabled_sizes'] ?? array();
		$retina_sizes = $options['retina_sizes'] ?? array();
		$webp_sizes = $options['webp_sizes'] ?? array();
		$webp_retina_sizes = $options['webp_retina_sizes'] ?? array();

		global $_wp_additional_image_sizes;
		foreach ( get_intermediate_image_sizes() as $s ) {

			// Get the information
			$crop = false;
			if ( isset( $_wp_additional_image_sizes[$s] ) ) {
				$width = intval( $_wp_additional_image_sizes[$s]['width'] );
				$height = intval( $_wp_additional_image_sizes[$s]['height'] );
				$crop = $_wp_additional_image_sizes[$s]['crop'];
			} 
			else {
				$width = intval( get_option( $s . '_size_w' ) );
				$height = intval( get_option( $s . '_size_h' ) );
				$crop = intval( get_option( $s . '_crop' ) );
			}

			// A disabled size has no retina and no webp (sanitize_options keeps the lists clean).
			$enabled = !in_array( $s, $disabled_sizes );
			$retina = $enabled && in_array( $s, $retina_sizes );
			$webp = $webp_enabled && $enabled && in_array( $s, $webp_sizes );
			$webp_retina = $webp_enabled && $retina && in_array( $s, $webp_retina_sizes );

			$sizes[$s] = array( 
				'width' => $width, 
				'height' => $height, 
				'crop' => $crop,
				'enabled' => $enabled,
				'retina_enabled' => $retina && $retina_enabled,
				'retina' => $retina,
				'webp' => $webp,
				'webp_retina' => $webp_retina,
				'name' => $s,
				'shortname' => Meow_WR2X_Core::size_shortname( $s )
			);
		}

		// Let's re-add the disabled sizes
		$disabled_to_add = array();
		foreach ( $disabled_sizes as $size ) {
			if ( !array_key_exists( $size, $sizes ) ) {
				$disabled_to_add[$size] = array( 
					'enabled' => false,
					'retina' => false,
					'webp' => false,
					'name' => $size,
					'shortname' => Meow_WR2X_Core::size_shortname( $size )
				);
			}
		}

		usort( $disabled_to_add, array( $this, 'sizes_sort_func' ) );
		$sizes = array_merge( $sizes, $disabled_to_add );

		if ( $output_type === ARRAY_A ) {
			return array_values( $sizes );
		}

		return $sizes;
	}

	function sizes_sort_func( $a, $b ) {
		return strncmp( $a['shortname'], $b['shortname'], 10 );
	}

	function get_active_image_sizes() {
		$sizes = $this->get_image_sizes();
		$active_sizes = array();
		// $ignore = $this->get_option( "ignore_sizes", array() );
		// if ( empty( $ignore ) )
		// 	$ignore = array();
		// $ignore = array_keys( $ignore );
		foreach ( $sizes as $name => $attr ) {
			$validSize = !empty( $attr['width'] ) || !empty( $attr['height'] );
			if ( $validSize && $attr['retina'] ) {
				$active_sizes[$name] = $attr;
			}
		}
		return $active_sizes;
	}

	// Regenerate the Thumbnails

	function regenerate_thumbnails_ai( $mediaId ) {

		require_once ABSPATH . 'wp-admin/includes/image.php';
	
		do_action( 'wr2x_before_generate_thumbnails', $mediaId );
		
		$file       = get_attached_file( $mediaId );
		$meta       = wp_generate_attachment_metadata( $mediaId, $file );
		
		// Thumbnails that were already generated
		$generated_sizes = isset( $meta['sizes'] ) ? $meta['sizes'] : array();
	
		// All registered sizes
		$needed_sizes = wp_get_registered_image_subsizes();
		$option_sizes = $this->get_option( 'sizes' );
	
		// Remove disabled sizes from the needed list
		foreach ( $option_sizes as $size ) {
			if ( array_key_exists( $size['name'], $needed_sizes ) && ! $size['enabled'] ) {
				unset( $needed_sizes[ $size['name'] ] );
			}
		}
	
		// Identify missing sizes
		$missing_sizes = array_diff_key( $needed_sizes, $generated_sizes );
	
		// If no sizes are missing, we are done
		if ( empty( $missing_sizes ) ) {
			do_action( 'wr2x_generate_thumbnails', $mediaId );
			return new WP_Error( 'no_missing_sizes', 'No missing sizes found' );
		}
	
		// Determine largest width/height needed
		$max_width  = 0;
		$max_height = 0;
		foreach ( $missing_sizes as $size_data ) {
			$max_width  = max( $max_width, $size_data['width'] );
			$max_height = max( $max_height, $size_data['height'] );
		}

		// Add some more width/height to account for the max sizes thumbnails as well
		$max_width  += 150;
		$max_height += 150;
	
		// Use AI upscale
		$ai_service = $this->get_option( 'ai_service', 'none' );
		$ai_result  = $this->use_ai_upscale( $mediaId, $ai_service, [ 'width' => $max_width, 'height' => $max_height ] );
	
		// If AI fails, fallback
		if ( ! $ai_result['status'] ) {
			$msg = '❌ AI upscaling failed for media ID ' . $mediaId . ': ' . $ai_result['message'];
			$this->log( $msg );

			wp_update_attachment_metadata( $mediaId, $meta );
			do_action( 'wr2x_generate_thumbnails', $mediaId );
			return new WP_Error( 'ai_upscale_failed', $msg );
		}
	
		// Download the AI upscaled image
		$ai_image_url = $ai_result['data'];

		// Check if the download_url() function is available
		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		$tmp_file     = download_url( $ai_image_url );
	
		if ( is_wp_error( $tmp_file ) ) {
			$msg = '❌ AI image download failed for media ID ' . $mediaId . ': ' . $tmp_file->get_error_message();
			$this->log( $msg );

			// Download failed
			wp_update_attachment_metadata( $mediaId, $meta );
			do_action( 'wr2x_generate_thumbnails', $mediaId );
			return new WP_Error( 'ai_image_download_failed', $msg );
		}
	
		// Backup the original file
		$original_dir      = pathinfo( $file, PATHINFO_DIRNAME );
		$original_filename = basename( $file );
		$backup_path       = trailingslashit( $original_dir ) . 'wr2x-ai-upscale-backup-' . $original_filename;
	
		if ( ! copy( $file, $backup_path ) ) {
			$msg = '❌ Backup failed for media ID ' . $mediaId;
			$this->log( $msg );

			// Cannot backup original
			@unlink( $tmp_file );
			wp_update_attachment_metadata( $mediaId, $meta );
			do_action( 'wr2x_generate_thumbnails', $mediaId );
			return new WP_Error( 'backup_failed', $msg );
		}
	
		// Replace the original file with the AI image
		@unlink( $file ); // remove the original (we have a backup)
		if ( ! @rename( $tmp_file, $file ) ) {
			$msg = '❌ AI image move failed for media ID ' . $mediaId;
			$this->log( $msg );

			// Cannot move AI image to original location
			// Restore backup
			@copy( $backup_path, $file );
			@unlink( $backup_path );
			@unlink( $tmp_file );
			wp_update_attachment_metadata( $mediaId, $meta );
			do_action( 'wr2x_generate_thumbnails', $mediaId );
			return new WP_Error( 'ai_image_move_failed', $msg );
		}
	
		// Now generate metadata as if the original file is the large AI image
		$ai_meta = wp_generate_attachment_metadata( $mediaId, $file );
	
		// Restore the original file
		@unlink( $file );
		@rename( $backup_path, $file );

		$history = $this->get_option( 'ai_history', [] );

		// Merge the missing sizes from AI meta into the original meta
		if ( ! empty( $ai_meta['sizes'] ) ) {
			foreach ( $ai_meta['sizes'] as $size_name => $size_meta ) {
				if ( array_key_exists( $size_name, $missing_sizes ) ) {
					$meta['sizes'][$size_name] = $size_meta;

					$history[ $mediaId ][ $size_name ] = [
						'time' => time(),
						'operation' => 'upscale',
						'service' => $ai_service,
		
						'width' => $size_meta['width'],
						'height' => $size_meta['height'],
					];
				}
			}
		}

		// Update the history for the generated sizes
		$this->update_option( 'ai_history', $history );
	
		// Update the attachment metadata with the new sizes
		wp_update_attachment_metadata( $mediaId, $meta );
	
		do_action( 'wr2x_generate_thumbnails', $mediaId );

		return $history;
	}


	function regenerate_thumbnails( $mediaId ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';

		do_action( 'wr2x_before_generate_thumbnails', $mediaId );
		
		$file = get_attached_file( $mediaId );
		$meta = wp_generate_attachment_metadata( $mediaId, $file );

		// Delete the history for the AI generated sizes
		$history = $this->get_option( 'ai_history', [] );
		if ( isset( $history[ $mediaId ] ) ) {
			unset( $history[ $mediaId ] );
			$this->update_option( 'ai_history', $history );
		}

		//error_log( "meta: " . print_r( $meta, true ) );	

		wp_update_attachment_metadata( $mediaId, $meta );
		do_action( 'wr2x_generate_thumbnails', $mediaId );
	}

	function regenerate_thumbnails_optimized( $media_id ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$file = get_attached_file( $media_id );
		$meta = wp_get_attachment_metadata( $media_id );

		if ( ! is_array( $meta ) || ! isset( $meta['sizes'] ) ) {
			$meta = array( 'sizes' => array() );
		}

		$needed_sizes = wp_get_registered_image_subsizes();
		$disabled_sizes = $this->get_option( 'disabled_sizes', array() );
		$dir = dirname( $file );

		// Remove disabled sizes from needed list (remove_image_size doesn't
		// work for WP built-in sizes, so they may still be in the list)
		foreach ( $disabled_sizes as $size ) {
			unset( $needed_sizes[ $size ] );
		}

		// Clean up thumbnails for disabled or unregistered sizes (ghost sizes
		// from old themes/plugins that are no longer active)
		foreach ( array_keys( $meta['sizes'] ) as $size ) {
			if ( in_array( $size, $disabled_sizes ) || !isset( $needed_sizes[ $size ] ) ) {
				$image_path = path_join( $dir, $meta['sizes'][ $size ]['file'] );
				if ( file_exists( $image_path ) ) {
					@unlink( $image_path );
				}
				unset( $meta['sizes'][ $size ] );
			}
		}

		// Regenerate only missing enabled sizes
		foreach ( $needed_sizes as $size => $size_data ) {
			$existing_file = $meta['sizes'][ $size ]['file'] ?? '';
			$existing_path = $existing_file ? path_join( $dir, $existing_file ) : '';
			if ( $existing_path && file_exists( $existing_path ) && filesize( $existing_path ) > 0 ) {
				continue;
			}
			$resized = image_make_intermediate_size( $file, $size_data['width'], $size_data['height'], $size_data['crop'] ?? true );
			if ( $resized ) {
				$meta['sizes'][ $size ] = $resized;
			}
		}

		wp_update_attachment_metadata( $media_id, $meta );
		do_action( 'wr2x_generate_thumbnails', $media_id );
	}


	// #region AI Features

	function use_ai_upscale( $mediaId, $service, $size = [ 'width' => "200%", 'height' => "200%" ]) {
		// TODO: Create a class for each AI Services
		// * For now, let's just create a function for each service

		switch ( $service ) {
			case 'none':
				return [ 'status' => false, 'message' => 'No AI Service selected.' ];
			case 'ai_engine':
				//return $this->ai_engine_upscale( $mediaId );
				return [ 'status' => false, 'message' => 'AI Engine is not available yet.' ];
			case 'claid':
				return $this->claid_upscale( $mediaId, $size );
			default:
				return false;
		}
	}

	function claid_upscale( $mediaId, $size ) {

		$response = array(
			'status'  => false,
			'message' => 'Upscale failed.',
			'data'    => null
		);
	
		$claid_key    = $this->get_option( 'claid_api_key', '' );
		$claid_host   = $this->get_option( 'claid_host', '' );
		$claid_upload = $this->get_option( 'claid_remote_upload', 'upload' );    
	
		if ( empty( $claid_key ) || empty( $claid_host ) ) {
			$response['message'] = 'CLAID API Key or Host is empty.';
			return $response;
		}
	
		$host = rtrim( $claid_host, '/' );
		$url  = "https://$host/v1-beta1/image/edit";
	
		// Prepare operations and output
		$operations = array(
			"restorations" => array(
				"upscale"=> $this->get_option( 'claid_restoration_type', 'photo' )
			),
			"resizing" => array(
				"width"  => $size['width'],
				"height" => $size['height'],
				"fit"    => $this->get_option( 'claid_upscale_type', 'bounds' )
			)
		);
	
		$output = array(
			"format" => array(
				"type"    => "jpeg",
				"quality" => 90
			)
		);
	
		if ( $claid_upload === 'upload' ) {
			// Append '/upload' to the URL for local uploads
			$url .= "/upload";
	
			// Get the file path
			$file_path = get_attached_file( $mediaId );
			if ( empty( $file_path ) ) {
				$response['message'] = 'File path is empty.';
				return $response;
			}
	
			// Prepare multipart/form-data body
			$boundary = wp_generate_password( 24 );
			$headers = array(
				'Authorization' => 'Bearer ' . $claid_key,
				'Content-Type'  => 'multipart/form-data; boundary=' . $boundary,
			);
	
			$body = '';
			// Add file part
			$body .= '--' . $boundary . "\r\n";
			$body .= 'Content-Disposition: form-data; name="file"; filename="' . basename( $file_path ) . '"' . "\r\n";
			$body .= 'Content-Type: ' . mime_content_type( $file_path ) . "\r\n\r\n";
			$body .= file_get_contents( $file_path ) . "\r\n";
	
			// Add data part
			$body .= '--' . $boundary . "\r\n";
			$body .= 'Content-Disposition: form-data; name="data"' . "\r\n";
			$body .= 'Content-Type: application/json' . "\r\n\r\n";
			$body .= wp_json_encode( array(
				"operations" => $operations,
				"output"     => $output
			) ) . "\r\n";
	
			// Finalize body
			$body .= '--' . $boundary . '--';
	
			$args = array(
				'headers' => $headers,
				'body'    => $body,
				'method'  => 'POST',
				'timeout' => 30,
			);
		}
		
		if( $claid_upload === 'share' ) {
			// Use media URL for shared uploads
			$mediaUrl = wp_get_attachment_url( $mediaId );
			if ( empty( $mediaUrl ) ) {
				$response['message'] = 'Media URL is empty.';
				return $response;
			}
	
			$data = array(
				"input"      => $mediaUrl,
				"operations" => $operations,
				"output"     => $output
			);
	
			$payload = wp_json_encode( $data );
	
			$args = array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $claid_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => $payload,
				'method'  => 'POST',
				'timeout' => 30,
			);
		}
	
		// Make the API request
		$api_response = wp_remote_post( $url, $args );
	
		if ( is_wp_error( $api_response ) ) {
			$response['message'] = 'Request Error: ' . $api_response->get_error_message();
		} else {
			$httpCode = wp_remote_retrieve_response_code( $api_response );
			if ( $httpCode == 200 ) {
				$result_body = wp_remote_retrieve_body( $api_response );
				$resultData  = json_decode( $result_body, true );
				if ( isset( $resultData['data']['output']['tmp_url'] ) ) {
					$response['status']  = true;
					$response['message'] = 'Upscale successful.';
					$response['data']    = $resultData['data']['output']['tmp_url'];
					$response['meta']    = $resultData['data'];
				} else {
					$response['message'] = 'Unexpected API response.';
				}
			} else {
				$response['message'] = 'API Error: ' . wp_remote_retrieve_body( $api_response );
			}
		}
	
		return $response;
	}



	// #endregion



	function is_wpml_installed() {
		return function_exists( 'icl_object_id' ) && !class_exists( 'Polylang' );
	}

	// SQL Query if WPML with an AND to check if the p.ID (p is attachment) is indeed an original
	// That is to limit the SQL that queries all the attachments
	function create_sql_if_wpml_original() {
		$whereIsOriginal = "";
		if ( $this->is_wpml_installed() ) {
			global $wpdb;
			global $sitepress;
			$tbl_wpml = $wpdb->prefix . "icl_translations";
			$language = $sitepress->get_default_language();
			$whereIsOriginal = " AND p.ID IN (SELECT element_id FROM $tbl_wpml WHERE element_type = 'post_attachment' AND language_code = '$language') ";
		}
		return $whereIsOriginal;
	}

	function increase_media_version( $mediaId ) {
		$version = get_post_meta( $mediaId, '_media_version', true );
		$version = $version ? intval( $version ) + 1 : 2;
		update_post_meta( $mediaId, '_media_version', $version );
		return $version;
	}

	function is_debug() {
		static $debug = -1;
		if ( $debug == -1 ) {
			$debug = $this->get_option( "debug" );
		}
		return !!$debug;
	}

	function get_logs() {
		$log_file_path = $this->get_logs_path();

		if ( !file_exists( $log_file_path ) ) {
			return "Empty log file.";
		}

		$content = file_get_contents( $log_file_path );
		$lines = explode( "\n", $content );
		$lines = array_filter( $lines );
		$lines = array_reverse( $lines );
		$content = implode( "\n", $lines );
		return $content;
	}

	function clear_logs() {
		$logPath = $this->get_logs_path();
		if ( file_exists( $logPath ) ) {
			unlink( $logPath );
		}

		$options = $this->get_options();
		$options['logs_path'] = null;
		$this->update_options( $options );
	}

	function get_logs_path() {
		$uploads_dir = wp_upload_dir();
		$uploads_dir_path = trailingslashit( $uploads_dir['basedir'] );

		$path = $this->get_option( 'logs_path' );

		if ( $path && file_exists( $path ) ) {
			// make sure the path is legal (within the uploads directory with the WR2X_PREFIX and log extension)
			if ( strpos( $path, $uploads_dir_path ) !== 0 || strpos( $path, WR2X_PREFIX ) === false || substr( $path, -4 ) !== '.log' ) {
				$path = null;
			} else {
				return $path;
			}
		}

		if ( !$path ) {
			$path = $uploads_dir_path . WR2X_PREFIX . "_" . $this->random_ascii_chars() . ".log";
			if ( !file_exists( $path ) ) {
				touch( $path );
			}
			$options = $this->get_options();
			$options['logs_path'] = $path;
			$this->update_options( $options );
		}

		return $path;
	}

	// Careful: this reads the plugin options, so it must never be called from get_options(),
	// sanitize_options() or anything they call, otherwise the two keep calling each other.
	function log( $data = null ) {
		if ( !$this->get_option( 'logs' ) ) { return false; }
		$log_file_path = $this->get_logs_path();
		$fh = @fopen( $log_file_path, 'a' );
		if ( !$fh ) { return false; }
		$date = date( "Y-m-d H:i:s" );
		if ( is_null( $data ) ) {
			fwrite( $fh, "\n" );
		}
		else {
			fwrite( $fh, "$date: {$data}\n" );
			//error_log( "[PERFECT IMAGES] $data" );
		}
		fclose( $fh );
		return true;
	}

	// Based on http://wordpress.stackexchange.com/questions/6645/turn-a-url-into-an-attachment-post-id
	function get_attachment_id( $file ) {
		$query = array(
			'post_type' => 'attachment',
			'meta_query' => array(
				array(
					'key'		=> '_wp_attached_file',
					'value'		=> ltrim( $file, '/' )
				)
			)
		);
		$posts = get_posts( $query );
		foreach( $posts as $post )
			return $post->ID;
		return false;
	}

	// Return the retina extension followed by a dot
	function retina_extension() {
		return '@2x.';
	}

	function webp_avif_extension() {

		if ( $this->get_option( 'generate_avif', false ) ) {
			return '.avif';
		}
			
		return '.webp';
	}

	function is_image_meta( $meta ) {
		if ( !isset( $meta ) )
			return false;
		if ( !isset( $meta['sizes'] ) )
			return false;
		if ( !isset( $meta['width'], $meta['height'] ) )
			return false;
		return true;
	}

	// Adds the shortname to the metadata
	function postprocess_metadata( $metadata ) {
		if ( !empty( $metadata['sizes'] ) ) {
			foreach ( $metadata['sizes'] as $key => $value ) {
				$metadata['sizes'][$key]['shortname'] = self::size_shortname( $key );
			}
		}
		return $metadata;
	}

	function retina_info( $id, $output_type = OBJECT  ) {
		$result = array();
		$meta = wp_get_attachment_metadata( $id );
		if ( !$this->is_image_meta( $meta ) )
			return $result;
		$original_width = $meta['width'];
		$original_height = $meta['height'];
		// Check if the full-size-retina version of the generation source exists. If it exists, use its dimensions.
		$uploads = wp_upload_dir();
		if ( $retina = $this->get_retina( $uploads['basedir'] . '/' . $meta['file'] ) ) {
			$dim = getimagesize( $retina );
			$original_width  = $dim[0];
			$original_height = $dim[1];
		}
		$sizes = $this->get_image_sizes();
		$originalfile = get_attached_file( $id );
		$pathinfo_fullsize = pathinfo( $originalfile );
		$basepath = $pathinfo_fullsize['dirname'];

		// Image Sizes
		if ( $sizes ) {
			foreach ( $sizes as $name => $attr ) {
				$validSize = !empty( $attr['width'] ) || !empty( $attr['height'] );
				if ( !$validSize ) {
					$result[$name] = 'IGNORED';
					continue;
				}
				if ( isset( $attr['enabled'] ) && !$attr['enabled'] ) {
					$result[$name] = 'IGNORED';
					continue;
				}
				// Check if the file related to this size is present
				$pathinfo = null;
				$retina_file = null;

				if ( isset( $meta['sizes'][$name]['width'] ) && isset( $meta['sizes'][$name]['height']) && isset($meta['sizes'][$name]) && isset($meta['sizes'][$name]['file']) && file_exists( trailingslashit( $basepath ) . $meta['sizes'][$name]['file'] ) ) {
					$normal_file = trailingslashit( $basepath ) . $meta['sizes'][$name]['file'];
					$pathinfo = pathinfo( $normal_file ) ;
					$retina_file = trailingslashit( $pathinfo['dirname'] ) . $pathinfo['filename'] . $this->retina_extension() . $pathinfo['extension'];
				}
				// None of the file exist
				else {
					$result[$name] = 'MISSING';
					continue;
				}

				// The retina file exists
				if ( $retina_file && file_exists( $retina_file ) ) {
					$result[$name] = 'EXISTS';
					continue;
				}
				// The size file exists
				else if ( $retina_file )
					$result[$name] = 'PENDING';

				// The retina file exists
				$required_width = $meta['sizes'][$name]['width'] * 2;
				$required_height = $meta['sizes'][$name]['height'] * 2;
				if ( !$this->are_dimensions_ok( $original_width, $original_height, $required_width, $required_height ) ) {
					$result[$name] = array( 'width' => $required_width, 'height' => $required_height );
				}
			}
		}

		// Full-Size (if required in the settings)
		$fullsize_required = $this->get_option( "full_size" ) && class_exists( 'MeowPro_WR2X_Core' );
		$retina_file = trailingslashit( $pathinfo_fullsize['dirname'] ) . $pathinfo_fullsize['filename'] .
			$this->retina_extension() . $pathinfo_fullsize['extension'];
		if ( $retina_file && file_exists( $retina_file ) )
			$result['full-size'] = 'EXISTS';
		else if ( $fullsize_required && $retina_file )
			$result['full-size'] = array( 'width' => $original_width * 2, 'height' => $original_height * 2 );

		if ( $output_type === ARRAY_A ) {
			$new_results = array();
			foreach ( $result as $key => $value ) {
				array_push( $new_results, array(
					'name' => $key,
					'shortname' => self::size_shortname( $key ),
					'status' => is_array( $value ) ? 'CANNOT' : $value,
					'required' => is_array( $value ) ? $value : null
					)
				);
			}
			return $new_results;
		}

		return $result;
	}

	function webp_info( $id, $output_type = OBJECT  ) {
		$result = array();
		$meta = wp_get_attachment_metadata( $id );
		if ( !$this->is_image_meta( $meta ) )
			return $result;
		$original_width = $meta['width'];
		$original_height = $meta['height'];
		$sizes = $this->get_image_sizes();
		$originalfile = get_attached_file( $id );
		$pathinfo_fullsize = pathinfo( $originalfile );
		$basepath = $pathinfo_fullsize['dirname'];

		// Image Sizes
		if ( $sizes ) {
			foreach ( $sizes as $name => $attr ) {
				$validSize = !empty( $attr['width'] ) || !empty( $attr['height'] );
				if ( !$validSize ) {
					$result[$name] = 'IGNORED';
					continue;
				}
				if ( isset( $attr['enabled'] ) && !$attr['enabled'] ) {
					$result[$name] = 'IGNORED';
					continue;
				}

				// Check if the file related to this size is present
				$pathinfo = null;
				$webp_file = null;

				if ( isset( $meta['sizes'][$name]['width'] )
					&& isset( $meta['sizes'][$name]['height'])
					&& isset($meta['sizes'][$name])
					&& isset($meta['sizes'][$name]['file'])
					&& file_exists( trailingslashit( $basepath ) . $meta['sizes'][$name]['file'] )
				) {
					$normal_file = trailingslashit( $basepath ) . $meta['sizes'][$name]['file'];
					$pathinfo = pathinfo( $normal_file ) ;
					
					$new_webp_ext = $pathinfo['extension'] === 'webp' ? '' : $this->webp_avif_extension();
					$webp_file = trailingslashit( $pathinfo['dirname'] ) . $pathinfo['filename'] . '.' . $pathinfo['extension'] . $new_webp_ext;
				}
				// None of the file exist
				else {
					$result[$name] = 'MISSING';
					continue;
				}

				// The WebP file exists
				if ( $webp_file && file_exists( $webp_file ) ) {
					$result[$name] = 'EXISTS';
				}
				else if ( $webp_file ) {
					// The size file exists
					$result[$name] = 'PENDING';
				}
			}
		}

		// Full-Size (if required in the settings)
		$fullsize_required = $this->get_option( "webp_full_size" ) && class_exists( 'MeowPro_WR2X_Core' );
		$webp_file = trailingslashit( $pathinfo_fullsize['dirname'] ) . $pathinfo_fullsize['filename'] . '.' . $pathinfo_fullsize['extension'] . $this->webp_avif_extension();
		if ( $webp_file && file_exists( $webp_file ) )
			$result['full-size'] = 'EXISTS';
		else if ( $fullsize_required && $webp_file )
			$result['full-size'] = array( 'width' => $original_width, 'height' => $original_height );

		if ( $output_type === ARRAY_A ) {
			$new_results = array();
			foreach ( $result as $key => $value ) {
				array_push( $new_results, array(
					'name' => $key,
					'shortname' => self::size_shortname( $key ),
					'status' => is_array( $value ) ? 'CANNOT' : $value,
					'required' => is_array( $value ) ? $value : null
					)
				);
			}
			return $new_results;
		}

		return $result;
	}

	function webp_retina_info( $id, $output_type = OBJECT  ) {
		$result = array();
		$meta = wp_get_attachment_metadata( $id );
		if ( !$this->is_image_meta( $meta ) )
			return $result;
		$original_width = $meta['width'];
		$original_height = $meta['height'];
		// Check if the full-size-retina version of the generation source exists. If it exists, use its dimensions.
		$uploads = wp_upload_dir();
		if ( $retina = $this->get_retina( $uploads['basedir'] . '/' . $meta['file'] ) ) {
			$dim = getimagesize( $retina );
			$original_width  = $dim[0];
			$original_height = $dim[1];
		}
		$sizes = $this->get_image_sizes();
		$originalfile = get_attached_file( $id );
		$pathinfo_fullsize = pathinfo( $originalfile );
		$basepath = $pathinfo_fullsize['dirname'];

		// Image Sizes
		if ( $sizes ) {
			foreach ( $sizes as $name => $attr ) {
				$validSize = !empty( $attr['width'] ) || !empty( $attr['height'] );
				if ( !$validSize ) {
					$result[$name] = 'IGNORED';
					continue;
				}
				if ( isset( $attr['enabled'] ) && !$attr['enabled'] ) {
					$result[$name] = 'IGNORED';
					continue;
				}

				// Check if the file related to this size is present
				$pathinfo = null;
				$webp_retina_file = null;

				if ( isset( $meta['sizes'][$name]['width'] )
					&& isset( $meta['sizes'][$name]['height'])
					&& isset($meta['sizes'][$name])
					&& isset($meta['sizes'][$name]['file'])
					&& file_exists( trailingslashit( $basepath ) . $meta['sizes'][$name]['file'] )
				) {
					$normal_file = trailingslashit( $basepath ) . $meta['sizes'][$name]['file'];
					$pathinfo = pathinfo( $normal_file ) ;
					$new_webp_ext = $pathinfo['extension'] === 'webp' ? '' : $this->webp_avif_extension();
					$webp_retina_file = trailingslashit( $pathinfo['dirname'] ) . $pathinfo['filename'] . $this->retina_extension() . $pathinfo['extension'] . $new_webp_ext;
				}
				// None of the file exist
				else {
					$result[$name] = 'MISSING';
					continue;
				}

				// The WebP Retina file exists
				if ( $webp_retina_file && file_exists( $webp_retina_file ) ) {
					$result[$name] = 'EXISTS';
					continue;
				}
				else if ( $webp_retina_file ) {
					// The size file exists
					$result[$name] = 'PENDING';
				}

				// The retina file exists
				$required_width = $meta['sizes'][$name]['width'] * 2;
				$required_height = $meta['sizes'][$name]['height'] * 2;
				if ( !$this->are_dimensions_ok( $original_width, $original_height, $required_width, $required_height ) ) {
					$result[$name] = array( 'width' => $required_width, 'height' => $required_height );
				}
			}
		}

		// Full-Size (if required in the settings)
		$fullsize_required = $this->get_option( "full_size" ) && class_exists( 'MeowPro_WR2X_Core' );
		$retina_file = trailingslashit( $pathinfo_fullsize['dirname'] ) . $pathinfo_fullsize['filename'] . 
			$this->retina_extension() . $pathinfo_fullsize['extension'];
		if ( $retina_file && file_exists( $retina_file ) )
			$result['full-size'] = 'EXISTS';
		else if ( $fullsize_required && $retina_file )
			$result['full-size'] = array( 'width' => $original_width * 2, 'height' => $original_height * 2 );

		if ( $output_type === ARRAY_A ) {
			$new_results = array();
			foreach ( $result as $key => $value ) {
				array_push( $new_results, array(
					'name' => $key,
					'shortname' => self::size_shortname( $key ),
					'status' => is_array( $value ) ? 'CANNOT' : $value,
					'required' => is_array( $value ) ? $value : null
					)
				);
			}
			return $new_results;
		}

		return $result;
	}

	/**
	 *
	 * FILTERS
	 *
	 */

	function validate_src( $src ) {
		if ( preg_match( "/^data:/i", $src ) )
			return null;
		return $src;
	}

	/**
	 *
	 * LOAD SCRIPTS IF REQUIRED
	 *
	 */

	function wp_enqueue_scripts () {

		// Picturefill Debug
		if ( $this->method == "Picturefill"  && $this->is_debug() ) {
			$physical_file = trailingslashit( WR2X_PATH ) . 'app/debug.js';
			$cache_buster = file_exists( $physical_file ) ? filemtime( $physical_file ) : WR2X_VERSION;
			wp_enqueue_script( 'wr2x-debug-js', trailingslashit( WR2X_URL ) . 'app/debug.js', array(), $cache_buster, false );
		}

		// No Picturefill Script
		if ( $this->method == "Picturefill"  && !$this->get_option( "picturefill_noscript" ) ) {
			$physical_file = trailingslashit( WR2X_PATH ) . 'app/picturefill.min.js';
			$cache_buster = file_exists( $physical_file ) ? filemtime( $physical_file ) : WR2X_VERSION;
			wp_enqueue_script( 'wr2x-picturefill-js', trailingslashit( WR2X_URL ) . 'app/picturefill.min.js', array(), $cache_buster, false );
		}

		// Debug + HTML Rewrite = No JS!
		if ( $this->is_debug() && $this->method == "HTML Rewrite" ) {
			return;
		}

		// Debug mode, we force the devicePixelRatio to be Retina
		if ( $this->is_debug() ) {
			$physical_file = trailingslashit( WR2X_PATH ) . 'app/debug.js';
			$cache_buster = file_exists( $physical_file ) ? filemtime( $physical_file ) : WR2X_VERSION;
			wp_enqueue_script( 'wr2x-debug-js', trailingslashit( WR2X_URL ) . 'app/debug.js', array(), $cache_buster, false );
		}

		// Retina-Images and HTML Rewrite both need the devicePixelRatio cookie on the server-side
		if ( $this->method == "Retina-Images" || $this->method == "HTML Rewrite" ) {
			$physical_file = trailingslashit( WR2X_PATH ) . 'app/retina-cookie.js';
			$cache_buster = file_exists( $physical_file ) ? filemtime( $physical_file ) : WR2X_VERSION;
			wp_enqueue_script( 'wr2x-debug-js', trailingslashit( WR2X_URL ) . 'app/retina-cookie.js', array(), $cache_buster, false );
		}

		// Retina.js only needs itself
		if ( $this->method == "retina.js" ) {
			$physical_file = trailingslashit( WR2X_PATH ) . 'app/retina.min.js';
			$cache_buster = file_exists( $physical_file ) ? filemtime( $physical_file ) : WR2X_VERSION;
			wp_enqueue_script( 'wr2x-retinajs-js', trailingslashit( WR2X_URL ) . 'app/retina.min.js', array(), $cache_buster, false );
		}
	}

	/**
	 *
	 * Roles & Access Rights
	 *
	 */
	function can_access_settings() {
		return apply_filters( 'wr2x_allow_setup', current_user_can( 'manage_options' ) );
	}

	function can_access_features() {
		return apply_filters( 'wr2x_allow_usage', current_user_can( 'administrator' ) );
	}

	#region Options

	static function get_plugin_option_name() {
		return self::$plugin_option_name;
	}

	function get_option_name() {
		return $this->option_name;
	}

	function get_option( $option, $default = null ) {
		$options = $this->get_options();
		return $options[$option] ?? $default;
	}

	function update_option( $option, $value ) {
		$options = $this->get_options();
		$options[$option] = $value;
		return $this->update_options( $options );
	}

	function list_options() {
		return array(
			'method' => 'none',
			'retina_sizes' => [],
			'disabled_sizes' => [],
			'big_image_size_threshold' => false,
			'hide_retina_dashboard' => false,
			'module_dashboard_enabled' => true,
			'hide_retina_column' => true,
			'hide_optimize' => true,
			'auto_generate' => false,
			'ignore_sizes' => [],
			'picturefill_keep_src' => false,
			'picturefill_css_background' => false,
			'disable_responsive' => false,
			'full_size' => false,
			'quality' => 75,
			'over_http_check' => false,
			'easyio_domain' => '',
			'cdn_domain' => '',
			'cdn_all_urls' => false,
			'easyio_lossless' => '',
			'debug' => false,
			'logs' => false,
			'picturefill_noscript' => false,
			'image_replace' => false,
			'sizes' => [],
			'module_imagesizes_enabled' => true,
			'module_retina_enabled' => false,
			'module_optimize_enabled' => true,
			'module_ui_enabled' => true,
			'gif_thumbnails_disabled' => true,
			'avif_thumbnails_disabled' => false,
			'webp_thumbnails_disabled' => false,
			'hide_admin_messages' => false,
			'logs_path' => null,
			'custom_image_sizes' => [],
			'legacy_build_thumbnails' => false,
			'module_devtools' => false,



			//Modern Image Formats
			'module_webp_enabled' => true,
			'webp_force_with_easyio' => false,
			'webp_full_size' => false,
			'generate_avif' => false,
			'webp_auto_generate' => false,
			'webp_retina_sizes' => [],
			'webp_sizes' => [],
			'webp_method' => 'none',
			'webp_ignored_extensions' => ['gif', 'svg', 'bmp', 'tiff'],

			// AI
			'module_ai_enabled' => false,
			'ai_service' => 'none',
			'ai_history' => [],
			'ai_upscale' => false,
			'ai_retina_full_size' => false,

			'claid_api_key' => '',
			'claid_host' => 'api.claid.ai',
			'claid_remote_upload' => 'upload', // upload or share
			'claid_restoration_type' => 'photo',
			'claid_upscale_type' => 'bounds',

			'wp_lazy_loading' => [
				'img' => wp_lazy_loading_enabled( 'img', 'wr2x' ),
				'iframe' => wp_lazy_loading_enabled( 'iframe', 'wr2x' ),
				'video' => wp_lazy_loading_enabled( 'video', 'wr2x' ),
				'picture' => wp_lazy_loading_enabled( 'picture', 'wr2x' ),
			],
			'wp_auto_sizes' => function_exists( 'wp_img_tag_add_auto_sizes' ) && function_exists( 'wp_sizes_attribute_includes_valid_auto' ),
			
		);
	}

	function reset_options_cache() {
		$this->options = null;
	}

	// Reads the options: what is stored, completed with the defaults and the runtime values.
	// It never writes, so anything can call it (including update_options) without looping.
	function get_options() {
		if ( $this->options === null ) {
			$options = get_option( $this->option_name, null );
			if ( !is_array( $options ) ) {
				// Old installs stored one row per option (wr2x_method, wr2x_quality, etc). Import them once.
				$options = [];
				foreach ( array_keys( $this->list_options() ) as $option ) {
					$legacy = get_option( 'wr2x_' . $option, null );
					if ( $legacy !== null ) {
						$options[$option] = $legacy;
						delete_option( 'wr2x_' . $option );
					}
				}
				$this->options = $this->sanitize_options( $options );
				update_option( $this->option_name, $this->options, false );
			}
			else {
				$this->options = $this->sanitize_options( $options );
			}
		}
		// Recomputed on every read, never cached: see rule 2 on the $options property. Moving
		// this line into the cache leaves the settings screen listing only the core sizes.
		$options = $this->options;
		$options['sizes'] = $this->get_image_sizes( ARRAY_A, $options );
		return $options;
	}

	// Completes the options with the defaults, fixes the types and keeps them logical.
	// Pure: it only reads WP (image sizes, lazy loading), it never writes the options.
	function sanitize_options( $options ) {
		$options = is_array( $options ) ? $options : [];

		// hide_retina_dashboard is the old name of module_dashboard_enabled.
		if ( !array_key_exists( 'module_dashboard_enabled', $options ) && array_key_exists( 'hide_retina_dashboard', $options ) ) {
			$options['module_dashboard_enabled'] = !$options['hide_retina_dashboard'];
		}
		$options = array_merge( $this->list_options(), $options );

		// Those are arrays, but older versions could store them as a comma-separated string.
		foreach ( ['retina_sizes', 'disabled_sizes', 'webp_sizes', 'webp_retina_sizes'] as $option ) {
			$value = $options[$option];
			$options[$option] = is_array( $value ) ? array_values( $value )
				: ( $value === '' || $value === null ? [] : explode( ',', (string)$value ) );
		}

		// A disabled size has no retina, and a size without retina has no retina webp.
		$options['retina_sizes'] = array_values( array_diff( $options['retina_sizes'], $options['disabled_sizes'] ) );
		if ( $options['module_webp_enabled'] ) {
			$options['webp_sizes'] = array_values( array_diff( $options['webp_sizes'], $options['disabled_sizes'] ) );
			$options['webp_retina_sizes'] = array_values( array_intersect( $options['webp_retina_sizes'], $options['retina_sizes'] ) );
		}

		// Both Easy IO and Modern Formats should not be enabled at the same time
		if ( $options['module_optimize_enabled'] && $options['module_webp_enabled'] ) {
			if ( !empty( $options['easyio_domain'] ) ) {
				$options['module_webp_enabled'] = false;
			} else {
				$options['module_optimize_enabled'] = false;
			}
		}

		// Easy IO auto-disables Modern Formats unless user explicitly forces it
		if ( !empty( $options['easyio_domain'] ) && !$options['webp_force_with_easyio'] ) {
			$options['module_webp_enabled'] = false;
		}

		if ( !$options['module_webp_enabled'] ) {
			$options['webp_full_size'] = false;
			$options['generate_avif'] = false;
			$options['webp_auto_generate'] = false;
			$options['webp_method'] = 'none';
			// Let's keep the sizes so we can restore them if the module is enabled again
		}

		// If the AI features are disabled, reset the AI options
		if ( !$options['module_ai_enabled'] ) {
			$options['ai_upscale'] = false;
			$options['ai_retina_full_size'] = false;
		}

		$options['hide_retina_dashboard'] = !$options['module_dashboard_enabled'];

		// Those are not settings, they are the current state of WordPress, refreshed on every read.
		$options['wp_lazy_loading'] = [
			'img' => wp_lazy_loading_enabled( 'img', 'wr2x' ),
			'iframe' => wp_lazy_loading_enabled( 'iframe', 'wr2x' ),
			'video' => wp_lazy_loading_enabled( 'video', 'wr2x' ),
			'picture' => wp_lazy_loading_enabled( 'picture', 'wr2x' ),
		];
		$options['wp_auto_sizes'] = function_exists( 'wp_img_tag_add_auto_sizes' ) && function_exists( 'wp_sizes_attribute_includes_valid_auto' );

		// Member variables used all over the plugin.
		$this->method = $options['method'];
		$this->retina_sizes = $options['retina_sizes'];
		$this->disabled_sizes = $options['disabled_sizes'];
		$this->webp_sizes = $options['webp_sizes'];
		$this->webp_retina_sizes = $options['webp_retina_sizes'];

		$options['sizes'] = $this->get_image_sizes( ARRAY_A, $options );

		return $options;
	}

	function update_options( $options ) {
		$old_options = $this->get_options();
		$options = $this->sanitize_options( $options );
		update_option( $this->option_name, $options, false );
		$this->options = $options;
		$this->notify_cdn_settings_changed( $old_options, $options );
		return $options;
	}

	// Other plugins cache image URLs (Meow Lightbox keeps them in transients for a day),
	// so they need to know when the CDN goes on, off or changes domain.
	function notify_cdn_settings_changed( $old_options, $new_options ) {
		$keys = array( 'easyio_domain', 'cdn_domain', 'cdn_all_urls' );
		$changed = array();
		foreach ( $keys as $key ) {
			$old = $old_options[$key] ?? null;
			$new = $new_options[$key] ?? null;
			if ( (string)$old !== (string)$new && !( empty( $old ) && empty( $new ) ) ) {
				$changed[$key] = array( 'old' => $old, 'new' => $new );
			}
		}
		if ( !empty( $changed ) ) {
			do_action( 'wr2x_cdn_settings_changed', $changed, $new_options );
		}
	}

	// #endregion

	// Custom Image Sizes
	// Compare the sizes by name. Comparing the values alone used to miss changes (two sizes
	// swapping their widths looked identical), and only the first change was ever returned.
	function get_custom_image_size_changes ( $old_options, $new_options ) {
		$old_by_name = array_column( is_array( $old_options ) ? $old_options : [], null, 'name' );
		$new_by_name = array_column( is_array( $new_options ) ? $new_options : [], null, 'name' );
		$changes = [];

		foreach ( $new_by_name as $name => $size ) {
			if ( !array_key_exists( $name, $old_by_name ) ) {
				$changes[] = [ 'type' => 'add', 'value' => $size ];
				continue;
			}
			foreach ( [ 'width', 'height', 'crop' ] as $key ) {
				if ( ( $old_by_name[$name][$key] ?? null ) !== ( $size[$key] ?? null ) ) {
					$changes[] = [ 'type' => 'update', 'value' => $size ];
					break;
				}
			}
		}

		foreach ( $old_by_name as $name => $size ) {
			if ( !array_key_exists( $name, $new_by_name ) ) {
				$changes[] = [ 'type' => 'delete', 'value' => $size ];
			}
		}

		return $changes;
	}

	function register_custom_image_size ( $type, $name, $width, $height, $crop ) {
		if ( $type === 'delete' ) {
			// A failed unregistration is not worth rolling back the whole settings save: the
			// sizes are registered again from the options on the next request anyway.
			remove_image_size( $name );
			return;
		}

		if ( in_array( $crop, ['yes', 'no'] ) ) {
			$crop = $crop === 'yes';
		} else {
			$crop = explode( '-', $crop );
		}

		add_image_size( $name, $width, $height, $crop );
	}

	private function random_ascii_chars( $length = 8 ) {
		$characters = array_merge( range( 'A', 'Z' ), range( 'a', 'z' ), range( '0', '9' ) );
		$characters_length = count( $characters );
		$random_string = '';

		for ($i = 0; $i < $length; $i++) {
			$random_string .= $characters[rand(0, $characters_length - 1)];
		}

		return $random_string;
	}

}

// Used by WP Rocket (and maybe by other plugins)
function wr2x_is_registered() {
	global $wr2x_core;
	return $wr2x_core->admin->is_registered();
}

?>
