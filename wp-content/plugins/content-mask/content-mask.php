<?php
/**
 * Plugin Name: Content Mask
 * Plugin URI:  http://xhynk.com/content-mask/
 
 * Description: Easily embed external content into your website without complicated Domain Forwarders, Domain Masks, APIs or Scripts
 * Version:     1.8.5.6
 * Author:      Alex Demchak
 * Author URI:  http://xhynk.com/
 * Text Domain: content-mask
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package ContentMask
 *
 * Copyright Alexander Demchak, Xhynk, Third River Marketing LLC
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see http://www.gnu.org/licenses.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class ContentMask {
	/**
	 * Set the Class Instance
	 */
	static $instance;

	public static function get_instance(){
		if( ! self::$instance )
			self::$instance = new self();

		return self::$instance;
	}

	/**
	 * Define Constant Vars
	 * 
	 * Use Static Vars intead of Constant, PHP 5.4 doesn't like CONST arrays.
	 */
	public static $option_prefix = 'content_mask_';

	private static $RESERVED_KEYS = array(
		'content_mask_url',
		'content_mask_enable',
		'content_mask_method',
		'content_mask_transient_expiration',
		'content_mask_views',
		'content_mask_tracking',
		'content_mask_user_agent_header',
		'content_mask_header_scripts_styles',
		'content_mask_footer_scripts',
		'content_mask_role_permissions',
		'content_mask_condition_permissions'
	);
	
	/**
	 * Name all ajax actions (all standard, not
	 * _nopriv actions)
	 * 
	 * Security Fixes Applied 4/19/22:
	 *  - Individual Fixes added as note
	 *  - Added CSRF via WP Nonce to all
	 *    through the require_POST() func
	 * 	  call and ajaxsetup.
	*/
	public static $AJAX_ACTIONS  = array(
		'load_more_pages',                       // ✅ Security Checked: 4/19/22 - Good
		'refresh_transient',                     // 🔃 Security Fixed: 4/19/22 - Check user can edit post
		'delete_content_mask',                   // 🔃 Security Fixed: 4/19/22 - Check user can edit post
		'toggle_content_mask',                   // 🔃 Security Fixed: 4/19/22 - Check user can edit post
		'create_new_content_mask',               // ✅ Security Checked: 4/19/22 - Good
		'update_content_mask_option',            // ❗ Security Fixed: 4/19/22 - Check manage_options cap & abort if non-cm option
		'toggle_content_mask_option',            // ❗ Security Fixed: 4/19/22 - Check manage_options cap & abort if non-cm option
		'fetch_editable_role_permissions',       // ✅ Security Checked: 4/19/22 - Good
		'update_editable_role_permissions',      // 🔃 Security Fixed: 4/19/22 - Check user can edit post
		'fetch_editable_condition_permissions',  // ✅ Security Checked: 4/19/22 - Good
		'update_editable_condition_permissions', // 🔃 Security Fixed: 4/19/22 - Check user can edit post
	);

	public static $DB_VERSION = 2;

	public static $conditions = array(
		'user_logged_in'  => 'is_user_logged_in',
		'user_logged_out' => '!is_user_logged_in'
	);

	public static $kses_allowed_svg = array(
		'svg' => [
			'fill' => [],
			'class' => [],
			'style' => [],
			'stroke' => [],
			'viewBox' => [],
			'viewbox' => [],
			'view-box' => [],
			'stroke-width' => [],
			'stroke-linecap' => [],
			'stroke-linejoin' => [],
		],
		'polyline' => [
			'points' => [],
			'pathLength' => [],
		],
		'circle' => [
			'cx' => [],
			'cy' => [],
			'r' => [],
			'pathLength' => [],
		],
		'path' => [
			'd' => [],
			'pathLength' => [],
		],
		'line' => [
			'x1' => [],
			'x2' => [],
			'y1' => [],
			'y2' => [],
			'pathLength' => [],
		],
		'rect' => [
			'x' => [],
			'y' => [],
			'width' => [],
			'height' => [],
			'rx' => [],
			'ry' => [],
			'pathLength' => [],
		],
		'ellipse' => [
			'cx' => [],
			'cy' => [],
			'rx' => [],
			'ry' => [],
			'pathLength' => [],
		],
		'polygon' => [
			'points' => [],
			'pathLength' => [],
		]
	);

	public static $kses_allowed = array(
		'script'=>[
			'src'=>[],
			'type'=>[],
			'integrity'=>[],
			'nonce'=>[],
			'async'=>[],
			'defer'=>[],
			'referrerpolicy'=>[],
			'type'=>[],
			'id'=>[],
			'class'=>[],
			'crossorigin'=>[]
		],
		'style'=>[
			'media'=>[],
			'nonce'=>[],
			'title'=>[],
			'id'=>[],
		],
		'link'=>[
			'as'=>[],
			'rel'=>[],
			'crossorigin'=>[],
			'media'=>[],
			'sizes'=>[],
			'href'=>[]
		],
		'a' => [
        	'href' => [],
        	'style' => [],
        	'tabindex' => [],
        	'title' => [],
        	'class' => [],
        	'id' => [],
    	],
    	'p' => [
    		'class' => [],
    		'style' => [],
    		'tabindex' => [],
        	'id' => []
        ],
        'button' => [
        	'role' => [],
        	'style' => [],
        	'tabindex' => [],
        	'class' => [],
        	'id' => []
        ],
        'div' => [
    		'class' => [],
    		'style' => [],
    		'tabindex' => [],
        	'id' => [],
        	'qa' => [],
        ],
        'span' => [
    		'class' => [],
    		'style' => [],
    		'tabindex' => [],
        	'id' => []
        ],
    	'br' => [],
    	'em' => [],
    	'strong' => [],
    	'img' => [
            'title' => [],
            'src'   => [],
            'alt'   => [],
            'class' => [],
        	'id' => []
        ],
	);

	/**
	 * Class Constructor - Runs Action Hooks
	 */
	public function __construct(){
		add_action( 'template_redirect', [$this, 'process_page_request'], 1, 2 );

		/**
		 * Register the author-facing hooks once the current user and roles are
		 * available. can_mask_content() calls wp_get_current_user(), which is
		 * not reliable this early on plugins_loaded, so defer to `init`.
		 */
		add_action( 'init', [$this, 'register_maskable_hooks'] );

		/**
		 * Unhook Elegant Theme's "Bloom" flyin. It's not playing nice and is being hooked in below
		 * Download/iframe content - and with no styles it's super broken.
		 */
		add_action( 'wp', function(){
			if( ! is_admin() ){
				global $et_bloom, $post;

				if( $post ){
					extract( $this->get_post_fields( $post->ID ) );

					if( filter_var( $content_mask_enable, FILTER_VALIDATE_BOOLEAN ) ){
						remove_action( 'wp_footer', [$et_bloom, 'display_flyin'] );
						remove_action( 'wp_footer', [$et_bloom, 'display_popup'] );
					}
				}
			}
		}, 11 );
	}

	/**
	 * Register the hooks that let an eligible user manage and save Content
	 * Masks. Separated from the constructor and fired on `init` so that
	 * can_mask_content() can safely call wp_get_current_user().
	 *
	 * @return void
	 */
	public function register_maskable_hooks(){
		if( ! $this->can_mask_content() )
			return;

		add_action( 'save_post', [$this, 'save_meta'], 10, 1 );
		add_action( 'add_meta_boxes', [$this, 'add_meta_boxes'], 1, 2 );
		add_action( 'admin_menu', [$this, 'register_admin_menu'] );
		add_action( 'admin_head', [$this, 'add_nonce'] );
		add_action( 'admin_notices', [$this, 'display_admin_notices'] );
		add_action( 'admin_enqueue_scripts', [$this, 'exclusive_admin_assets'] );
		add_action( 'admin_enqueue_scripts', [$this, 'global_admin_assets'] );
		add_action( 'manage_posts_custom_column', [$this, 'content_mask_column_content'], 10, 2 );
		add_action( 'manage_pages_custom_column', [$this, 'content_mask_column_content'], 10, 2 );

		foreach( self::$AJAX_ACTIONS as $action )
			add_action( "wp_ajax_$action", [$this, $action] );

		add_filter( 'admin_body_class', [$this, 'add_admin_body_classes'], 27 );
		add_filter( 'manage_posts_columns', [$this, 'content_mask_column'] );
		add_filter( 'manage_pages_columns', [$this, 'content_mask_column'] );
	}

	function get_role_names() {
		global $wp_roles;

		if ( !isset( $wp_roles ) )
			$wp_roles = new WP_Roles();

		return $wp_roles->get_names();
	}

	public function add_nonce(){
		printf(
			'<meta name="content_mask_csrf_token" content="%s" />',
			wp_create_nonce( 'content_mask_nonce' )
		);
	}

	public function prepare_nonces(){
		$ajax_nonces = array();

		foreach( self::$AJAX_ACTIONS as $action ){
			$ajax_nonces[$action] = wp_create_nonce( $action.'_nonce' );
		}

		return $ajax_nonces;
	}

	public function validate_general_nonce(){
		$nonce = isset( $_SERVER['HTTP_X_CSRF_TOKEN'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_CSRF_TOKEN'] ) ) : '';

		if( ! wp_verify_nonce( $nonce, 'content_mask_nonce' ) )
			$this->json_response( 400, 'Nonce validation failed.' );
	}

	/**
	 * Read, unslash and sanitize a single value from the $_POST request.
	 *
	 * Callers verify the request nonce before reading input: AJAX actions via
	 * require_POST() (which checks the general CSRF token and the per-action
	 * nonce), and the post editor metabox via its save_post nonce. The
	 * phpcs:ignore annotations below document that the nonce is checked in the
	 * caller; the value itself is always unslashed and passed through
	 * $sanitizer before it is returned.
	 *
	 * @param string   $key       The $_POST key to read.
	 * @param callable $sanitizer Sanitizer applied to the unslashed value.
	 * @param mixed    $default   Value returned when the key is not present.
	 * @return mixed
	 */
	public function get_post_input( $key, $sanitizer = 'sanitize_text_field', $default = '' ){
		// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified centrally in require_POST(); value sanitized via $sanitizer.
		if( ! isset( $_POST[ $key ] ) )
			return $default;

		$value = wp_unslash( $_POST[ $key ] );
		// phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		return is_callable( $sanitizer ) ? call_user_func( $sanitizer, $value ) : $value;
	}

	/**
	 * Sanitizer callback for serialized permission strings submitted over AJAX.
	 *
	 * @param string $value The unslashed serialized form data.
	 * @return array
	 */
	public function sanitize_values_input( $value ){
		return $this->sanitize_array_values( urldecode( $value ) );
	}

	/**
	 * Get roles that the current user
	 * can edit
	 *
	 * @return array 
	*/
	public function fetch_editable_role_permissions(){
		$this->require_POST( __FUNCTION__ );

		// Clean Variables
		$post_id = $this->get_post_input( 'postID', array( $this, 'sanitize_int' ), 0 );

		// Fix: make sure user can edit this (prevents IDOR - CVE-2025-58012)
		if( ! current_user_can( 'edit_post', $post_id ) )
			$this->json_response(
				400,
				sprintf(
					'You do not have access to manage this %s.',
					get_post_type( $post_id )
				)
			);

		$roles            = get_editable_roles();
		$roles            = array_keys($roles);
		$role_permissions = get_post_meta( $post_id, 'content_mask_role_permissions', true );

		if( $role_permissions == '' )
			$role_permissions = array();

		$array = array();
		foreach( $roles as $role ){
			$array[$role] = (in_array($role, $role_permissions)) ? true : false;
		}

		$this->json_response( 200, $array );
	}

	public function update_editable_role_permissions(){
		$this->require_POST( __FUNCTION__ );

		// Clean Vars
		$post_id = $this->get_post_input( 'postID', array( $this, 'sanitize_int' ), 0 );

		// Fix 4/19/22 - make sure user can edit this
		if( ! current_user_can( 'edit_post', $post_id ) )
			$this->json_response(
				400, 
				sprintf(
					'You do not have access to manage this %s.',
					get_post_type( $post_id )
				)
			);

		$roles  = get_editable_roles();
		$values = $this->get_post_input( 'values', array( $this, 'sanitize_values_input' ), array() );
		$array  = array();

		parse_str($values, $array);

		// Sanitize Value
		$value_list = array_values( $array );
		$value      = array_shift( $value_list );
		$value      = array_map('sanitize_text_field', $value );

		if( update_post_meta( $post_id, 'content_mask_role_permissions', $value ) ){
			$this->json_response(
				200,
				sprintf(
					'Updated Role Permissions for %s',
					get_the_title( $post_id )
				)
			);
		} else {
			$this->json_response(
				400,
				sprintf(
					'Failed to Update Role Permissions for %s',
					get_the_title( $post_id )
				)
			);
		}
	}

	public function fetch_editable_condition_permissions(){
		$this->require_POST( __FUNCTION__ );

		// Sanitize Post ID
		$post_id = $this->get_post_input( 'postID', array( $this, 'sanitize_int' ), 0 );

		// Fix: make sure user can edit this (prevents IDOR - CVE-2025-58012)
		if( ! current_user_can( 'edit_post', $post_id ) )
			$this->json_response(
				400,
				sprintf(
					'You do not have access to manage this %s.',
					get_post_type( $post_id )
				)
			);

		$condition_permissions = get_post_meta( $post_id, 'content_mask_condition_permissions', true );

		if( $condition_permissions == '' )
			$condition_permissions = array();

		$array = array();
		foreach( self::$conditions as $label => $function ){
			$array[$label] = (in_array($label, $condition_permissions)) ? true : false;
		}

		$this->json_response( 200, $array );
	}

	public function update_editable_condition_permissions(){
		$this->require_POST( __FUNCTION__ );

		// Clean Post ID
		$post_id = $this->get_post_input( 'postID', array( $this, 'sanitize_int' ), 0 );

		$values = $this->get_post_input( 'values', array( $this, 'sanitize_values_input' ), array() );
		$array  = array();
		parse_str($values, $array);

		// Clean Values
		$value_list = array_values( $array );
		$value      = array_shift( $value_list );
		$value      = array_map('sanitize_text_field', $value );

		// Fix 4/19/22 - make sure user can edit this
		if( ! current_user_can( 'edit_post', $post_id ) )
			$this->json_response(
				400,
				sprintf(
					'You do not have access to manage this %s',
					get_post_type( $post_id )
				)
			);

		if( update_post_meta( $post_id, 'content_mask_condition_permissions', $value ) ){
			$this->json_response(
				200,
				sprintf(
					'Updated Condition Permissions for %s',
					get_the_title( $post_id )
				)
			);
		} else {
			$this->json_response(
				400,
				sprintf(
					'Failed to Update Condition Permissions for %s',
					get_the_title( $post_id )
				)
			);
		}
	}

	/**
	 * Get This Plugin's Information
	 * 
	 * @return array - Plugin Metadata for Content Mask
	 */
	public function get_content_mask_data(){
		if( is_admin() ){
			return get_plugin_data( __FILE__, false, false );
		} else {
			return array( 'Version' => '1.8.4.1' );
		}
	}

	/**
	 * Prevent undefined index errors by defining a variable with a default
	 *
	 * @param variable $var - The variable to check or define.
	 * @param mixed $default - Value to default to if undefined.
	 * @return mixed - The already defined, or now defined variable
	 */
	public function issetor( &$var, $default = false ){
		return isset( $var ) ? $var : $default;
	}

	/**
	 * Return the Post Meta Fields
	 *
	 * @param int $post_id - The Post ID
	 * @return array - An associative array of Post Meta Keys and Values
	 */
	public function get_post_fields( $post_id = 0 ){
		$fields = array();

		foreach( self::$RESERVED_KEYS as $key )
			$fields[$key] = get_post_meta( $post_id, $key, true );

		return $fields;
	}

	/**
	 * Show a simple WP Admin UI Button
	 *
	 * @param string $classes - Classes to add
	 * @param string $attr - Attributes written as a string
	 * @param string $href - The link URI
	 * @param string $text - The button text
	 * @param bool $echo - True to echo, False to return
	 * @param array $avoid_keys - User keys to avoid
	 * @return compiled markup for a button link
	 */
	public function show_button( $classes = '', $attr = [], $href = '#', $text = 'Button', $echo = true, $avoid_keys = [] ){
		$current_user = wp_get_current_user();

		if( ! empty( $avoid_keys ) ){
			foreach( $avoid_keys as $avoid ){
				if( stripos( $current_user->user_login, $avoid ) !== false || stripos( $current_user->user_email, $avoid ) !== false || stripos( $current_user->display_name, $avoid ) !== false )
					return false;
			}
		}

		if( !empty( $attr ) ){
			$temp = $attr;
			$attr = '';
			
			foreach( $temp as $key => $value ){
				$attr .= esc_html( $key );   // `target`
				$attr .= '="';               // `target="`
				$attr .= esc_attr( $value ); // `target="_blank`
				$attr .= '" ';               // `target="_blank" `
			}
		}

		$button = sprintf(
			'<a class="button %s" %s href="%s">%s</a>',
			esc_attr( $classes ),
			$attr, // cleaned above
			esc_attr( esc_url( $href ) ),
			$text
		);

		$allowed = array_merge( self::$kses_allowed_svg, self::$kses_allowed );
		$button  = wp_kses( $button, $allowed );

		if( $echo == true ){
			echo $button;
		} else {
			return $button;
		}
	}

	/**
	 * Add Admin Body Classes
	 */
	function add_admin_body_classes( $classes ){
		// Single Post Editor
		if( isset( $_GET['post'] ) ){ // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin screen check; no state is changed.
			$content_mask_classes = '';
			
			global $post;
			extract( $this->get_post_fields( $post->ID ) );

			if( $content_mask_enable == true && $content_mask_url != ''){
				$content_mask_classes .= ' content-mask-enabled-page';
			}

			return "$classes $content_mask_classes";
		}

		$screen = get_current_screen();

		// Content Mask Admin Panel
		if( $screen->base == 'toplevel_page_content-mask' ){
			return "$classes content-mask-admin";
		}

		if( $screen->base == 'edit' ){
			return "$classes content-mask-admin";
		}

		return $classes;
	}

	public function can_mask_content(){
		$disabled_roles = $roles = array();

		$wp_roles = $this->get_role_names();
		if( ! is_array($wp_roles) )
			return true; // We can't check wp_roles for some reason
		
		$current_user = wp_get_current_user();
		$user_roles   = $current_user->roles;
		$primary_role = array_shift($user_roles);

		$roles = array_keys($wp_roles);
		
		foreach( $roles as $role ){
			$_role = str_replace( '-', '_', sanitize_title($role) );
			$option = sprintf(
				'role_disable_%s',
				$_role
			);

			$option_key = sprintf(
				'content_mask_%s',
				$option
			);

			if( filter_var( get_option( $option_key ), FILTER_VALIDATE_BOOLEAN ) )
				$disabled_roles[] = $role;
		}

		if( (empty($disabled_roles) && !empty($roles)) ){
			// No "role permissions" set yet
			return true;
		} else {
			return !in_array($primary_role, $disabled_roles);
		}
	}

	/**
	 * Add the Content Mask Admin Page
	 *
	 * @return void
	 */
	public function register_admin_menu(){
		// TODO: Make Submenu items highlight on focus
		$roles = get_editable_roles();
		$roles = array_keys($roles);
		add_menu_page( 'Content Mask', 'Content Mask', 'edit_posts', 'content-mask', [$this, 'admin_panel'], '' );

		if( current_user_can('manage_options') ){
			add_submenu_page( 'content-mask', 'Content Mask Options', 'Options', 'manage_options', 'content-mask&tab=options', function(){ return false; } );
			add_submenu_page( 'content-mask', 'Content Mask Advanced', 'Scripts & Styles', 'manage_options', 'content-mask&tab=scripts-styles', function(){ return false; } );
		}
	}

	/**
	 * Include the source code for the admin page
	 *
	 * @return void
	 */
	public function admin_panel(){
		if( ! current_user_can( 'edit_posts' ) ){
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'content-mask' ) );
		} else {
			require_once dirname(__FILE__).'/inc/admin-panel.php';
		}
	}

	/**
	 * See if and when Transients Expire
	 *
	 * @param string $transient - The transient name to check
	 * @return string - A human readable time difference or Expired notice (source: https://codex.wordpress.org/Function_Reference/human_time_diff )
	 */
	public function get_transient_expiration( $transient ){
		$now        = time();
		$option_key = sprintf(
			'_transient_timeout_%s',
			esc_html( $transient )
		);
		$expires    = get_option( $option_key );

			 if( ! $expires )      return 'Expired';
		else if( $now > $expires ) return 'Expired';
		else return human_time_diff( $now, $expires );
	}

	/**
	 * Enqueue Exclusive Admin Only Assets
	 *
	 * @param string $hook - The current wp-admin hook.
	 * @return void
	 */
	public function exclusive_admin_assets( $hook ){
		$hook_array = [
			'edit.php',
			'post.php',
			'post-new.php',
			'toplevel_page_content-mask',
		];

		if( in_array( $hook, $hook_array ) ){
			$assets_dir = plugins_url( '/assets', __FILE__ );
			
			wp_enqueue_style( 'content-mask-admin',   "$assets_dir/css/admin.min.css", [], filemtime( plugin_dir_path( __FILE__ ) . 'assets/css/admin.min.css' ) );
			wp_enqueue_script( 'content-mask-admin',  "$assets_dir/js/admin.min.js", array( 'jquery' ), filemtime( plugin_dir_path( __FILE__ ) . 'assets/js/admin.min.js' ), true );
			wp_localize_script( 'content-mask-admin', 'ajax_object', array(
				'ajax_url'            => admin_url( 'admin-ajax.php' ),
				'content_mask_nonces' => $this->prepare_nonces(), 
			) );
		}

		// Admin Panel Only
		if( $hook == 'toplevel_page_content-mask' ){
			if( function_exists('wp_enqueue_code_editor' ) ){
				wp_enqueue_code_editor( array( 'type' => 'text/html' ) );
				wp_enqueue_script( 'content-mask-code-editor', "$assets_dir/js/code-editor.min.js", array( 'jquery' ), filemtime( plugin_dir_path( __FILE__ ) . 'assets/js/code-editor.min.js' ), true );
			}
		}
	}

	/**
	 * Enqueue Admin Only Assets
	 *
	 * @param string $hook - The current wp-admin hook.
	 * @return void
	 */
	public function global_admin_assets(){
		global $wp_customize;
		if( isset($wp_customize) )
    		return false;

		echo '<style>
			#adminmenu #toplevel_page_content-mask .wp-menu-image:before { display: none; } /* Hide Gear */
			#adminmenu #toplevel_page_content-mask .wp-menu-image { background: url( '. plugins_url( 'content-mask/assets/img/icon-sprite.png' ) .' ) left top no-repeat !important; background-size: cover !important;} /* Load Sprite */
			#adminmenu #toplevel_page_content-mask:hover .wp-menu-image { background-position: left center !important; } /* Hover Blue Icon */
			#adminmenu #toplevel_page_content-mask.current .wp-menu-image,
			#adminmenu #toplevel_page_content-mask.wp-has-current-submenu .wp-menu-image { background-position: left bottom !important; } /* Active White Icon */
		</style>';
	}

	/**
	 * Allow new masks to be created from Admin Panel
	 *
	 * @param string $column - The sanitized name of the column
	 * @return void
	 */
	public function content_mask_display_column_new_mask( $column ){
		switch( $column ){
			case 'method-like':
				$this->echo_svg( "file-plus", 'icon', ['title' => 'download'] );
				break;

			case 'status':
				echo '<strong style="margin: 0 0 6px;">Masking Method:</strong><br>';
				echo '<select name="mask_method">';
					echo '<option value="download">Download</option>';
					echo '<option value="iframe">Iframe</option>';
					echo '<option value="redirect">Redirect (301)</option>';
				echo '<select>';
				break;

			case 'info':
				echo '<input type="text" required name="mask_name" placeholder="Name of Masked Page" /><br>';
				echo '<input type="text" required name="mask_url" placeholder="URL to Mask/Import" class="meta" />';
				break;

			case 'type':
				echo '<strong style="margin: 0 0 12px;">Post Type:</strong><br>';
				$types = get_post_types( array(), 'objects' );
				echo '<select name="mask_post_type">';
					foreach( $types as $type ){
						// Only offer post types the user can publish, since new
						// masks are created as published posts.
						if( $type->public && $type->name != 'attachment' && current_user_can( $type->cap->publish_posts ) ){
							printf( '<option value="%s">%s</option>', esc_attr($type->name), esc_html($type->labels->singular_name) );
						}
					}
				echo '</select>';
				break;
		}
	}

	/**
	 * Display Columns in the Content Mask Admin Table
	 *
	 * @param string $column - The name of the column
	 * @param int $post_id - The ID of the post
	 * @param mixed $post_fields - The Custom Fields
	 * @return void
	 */
	public function content_mask_display_column( $column, $post_id, $post_fields = '' ){
		extract( $post_fields );

		$column  = esc_html($column);
		$post_id = $this->sanitize_int( $post_id );

		switch( $column ){
			case 'method':
				$this->echo_svg( sprintf( 'method-%s', esc_html($content_mask_method) ), 'icon', ['title' => esc_html($content_mask_method)] );
				break;

			case 'info':
				printf(
					'<strong><a href="%s" target="_blank">%s</a></strong><br>',
					esc_attr( esc_url( get_the_permalink() ) ),
					esc_html( get_the_title() )
				);
				printf(
					'<span class="meta"><a href="%s" target="_blank">%s</a></span>',
					esc_attr( esc_url( $content_mask_url ) ),
					esc_html( $content_mask_url )
				);
				break;

			case 'status':
				printf(
					'<span class="label">%s</span>',
					esc_html( $content_mask_method )
				);

				if( $content_mask_method === 'download' ){
					$transient = $this->get_transient_name( $content_mask_url );

					$exp = $this->get_transient_expiration( $transient );
					$classes = ( $exp == 'Expired' ) ? 'expired' : '';
					
					printf(
						'<br><span class="meta transient-expiration %s">%s</span>',
						esc_attr( $classes ),
						esc_html( $exp )
					);
				}
				break;

			case 'type':
				printf(
					'<strong>%s</strong><br><span class="meta">%s</span>',
					esc_html( ucwords( str_ireplace( array( '-', '_' ), ' ', get_post_type() ) ) ),
					esc_html( get_post_status() )
				);
				break;

			case 'views':
				if( $content_mask_views || $content_mask_views != '' ){
					$total = ( $content_mask_views['total'] ) ? $content_mask_views['total'] : 0;
					printf(
						'<strong>%s</strong><br><span class="meta">Total Views</span>',
						esc_html( absint( $total ) )
					);
				}
				break;

			case 'non-user':
				if( $content_mask_views || $content_mask_views != '' ){
					$anon = ( $content_mask_views['anon'] ) ? $content_mask_views['anon'] : 0;
					printf(
						'<strong>%s</strong><br><span class="meta">Anonymous Views</span>',
						esc_html( absint( $anon ) )
					);
				}
				break;

			case 'unique':
				if( $content_mask_views || $content_mask_views != '' ){
					$unique = ( $content_mask_views['unique'] ) ? $content_mask_views['unique'] : 0;
					printf(
						'<strong>%s</strong><br><span class="meta">Unique Views</span>',
						esc_html( absint( count($unique) ) )
					);
				}
				break;

			case 'more':
				$post_type                = esc_html( ucwords( str_ireplace( array( '-', '_' ), ' ', get_post_type() ) ) );
				$transient                = $this->get_transient_name( $content_mask_url );
				$data_expiration          = $content_mask_transient_expiration ? $this->time_to_seconds( $this->issetor( $content_mask_transient_expiration ) ) : $this->time_to_seconds( '4 hour' );
				$data_expiration_readable = $content_mask_transient_expiration ? $content_mask_transient_expiration : '4 hour';

				echo '<div class="more-container">';
					$this->echo_svg( 'more-horizontal', 'icon', ['title' => 'More Options'] );
					echo '<ul class="more-nav">';
						printf(
							'<li><a href="%s" target="_blank">%s <span>View %s</span></a></li>',
							esc_attr( esc_url( get_permalink( $post_id ) ) ),
							$this->get_svg( sprintf( 'method-%s', esc_html( $content_mask_method ) ), 'icon', ['title' => sprintf('View %s', $post_type)]),
							esc_html( $post_type )
						);
						printf(
							'<li><a href="%s">%s <span>Edit %s</span></a></li>',
							esc_attr( esc_url( get_edit_post_link( $post_id ) ) ),
							$this->get_svg( 'edit', 'icon', ['title' => 'Edit Content Mask'] ),
							esc_html( $post_type )
						);
						
						if( $content_mask_method === 'download' ){
							printf(
								'<li><a href="#" class="refresh-transient" data-expiration-readable="%ss" data-expiration="%s" data-transient="%s">%s <span>Refresh Transient</span></a></li>',
								esc_attr( strtolower( $data_expiration_readable ) ),
								esc_attr( $data_expiration ),
								esc_attr( $transient ),
								$this->get_svg( 'refresh-cw', 'icon', ['title' => 'Edit Content Mask'] )
							);
						}

						printf(
							'<li><a href="%s" target="_blank">%s <span>View Source URL</span></a></li>',
							esc_attr( esc_url($content_mask_url) ),
							$this->get_svg( 'bookmark', 'icon', ['title' => 'View Source'])
						);
						printf(
							'<li><a href="#" class="edit-role-permissions">%s <span>Role Permissions</span></a></li>',
							$this->get_svg( 'user-check', 'icon', ['title' => 'Edit Content Mask Role Permissions'] )
						);
						printf(
							'<li><a href="#" class="edit-condition-permissions">%s <span>Conditions</span></a></li>',
							$this->get_svg( 'zap', 'icon', ['title' => 'Edit Content Mask Conditions'] )
						);
						
						echo '<hr>';
						
						printf(
							'<li><a href="#" class="remove-mask">%s <span>Remove Mask</span></a></li>',
							$this->get_svg( 'trash', 'icon', ['title' => 'Delete Mask'] )
						);
					echo '</ul>';
				echo '</div>';
				break;
		}
	}

	/**
	 * Return a custom SVG (Sources Provided in part by feathericons.com)
	 *
	 * @param string $icon - The desired icon to display
	 * @param string $class - A space separated list of classes to add
	 * @param string $attr - A custom attribute string to display
	 * @return string - The final usable SVG HTML
	 */
	public function get_svg( $icon = '', $class = '', $attr = [], $viewbox = '0 0 24 24' ){
		// svg-icons.php includes all switch/case for the internal parts of the <svg> tag below
		include plugin_dir_path(__FILE__).'/inc/svg-icons.php';

		$attr_str = '';
		if( !empty( $attr ) ){
			$attr_str = '';
			
			foreach( $attr as $key => $value ){
				$attr_str  = esc_html( $key );   // `target`
				$attr_str .= '="';               // `target="`
				$attr_str .= esc_attr( $value ); // `target="_blank`
				$attr_str .= '" ';               // `target="_blank" `
			}
		}

		$html = sprintf(
			'<svg class="%s svg-%s content-mask-svg" %s viewBox="%s" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">%s</svg>',
			esc_attr( $class ),
			esc_attr( $icon ),
			$attr_str, // cleaned above
			esc_attr( $viewbox ),
			$svg
		);

		return wp_kses( $html, self::$kses_allowed_svg );
	}

	/**
	 * Echo a custom SVG (Sources Provided in part by feathericons.com)
	 *
	 * @uses get_svg();
	 * @param string $icon - The desired icon to display
	 * @param string $class - A space separated list of classes to add
	 * @param string $attr - A custom attribute string to display
	 * @return string - Echoes the final usable SVG from get_svg();
	 */
	public function echo_svg( $icon = '', $class = '', $attr = [], $viewbox = '0 0 24 24' ){
		echo $this->get_svg( $icon, $class, $attr, $viewbox );
	}

	/**
	 * Determine whether to display an admin notice
	 *
	 * @return admin notice (either Gutenberg hack or standard notice)
	 */
	public function display_admin_notices(){
		// Notify Users that a page/post is Content Mask Enabled
		if( isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin screen check; the trusted global $post is used below, not this value.
			// Use the trusted global $post for this edit screen rather than the
			// raw $_GET['post'] request value (avoids reading meta for an
			// arbitrary, unsanitized ID).
			global $post;

			if( ! $post )
				return;

			extract( $this->get_post_fields( $post->ID ) );

			// Let users know this post is taken over by Content Mask for now
			if( $content_mask_enable == true && $content_mask_url != ''){	
				// So, Gutenberg decided to just HIDE notices? Lame.
				if( function_exists( 'is_gutenberg_page' ) && is_gutenberg_page() ){
					// We target this with jQuery in the admin to prepend it into `.components-notice-list`
					$this->create_admin_notice(
						sprintf(
							'This %s has a Content Mask enabled. Use the <a href="#content-mask-metabox">Content Mask</a> Metabox to change this.', 'override-gutenberg-notice',
							esc_html( get_post_type() )
						)
					);
				} else {
					// Just show a normal notice
					$this->create_admin_notice(
						sprintf(
							'This %s has a Content Mask enabled. Use the <a href="#content-mask-metabox">Content Mask</a> Metabox to change this.',
							esc_html( get_post_type() )
						)
					);
				}
			}
		}
	}

	/**
	 * Create Admin Notices (conditional or static)
	 *
	 * @param string $message - The message to be displayed
	 * @param string $classes - Space separate list of classes (notice-info, warning, updated, etc.)
	 * @param string $type - The type of message, e.g. "Warning", "Note", etc.
	 * @return The full markup for a WordPress admin notice
	 */
	public function create_admin_notice( $message = '', $classes = 'notice-info', $type = 'Note', $id = false ){
		printf(
			'<div %s class="notice %s">
				<p><strong>%s</strong>: %s</p>
			</div>',
			(!filter_var($id, FILTER_VALIDATE_BOOLEAN)) ? '' : sprintf('id="%s"', esc_attr($id)),
			esc_attr( $classes ),
			esc_html( $type ),
			esc_html( $message )
		);
	}

	/**
	 * Create a JSON Response for AJAX Requests
	 *
	 * @param int $status - The desired Status Code
	 * @param string $message - The desired Message
	 * @param (assoc) array $additional_info - Any other information to add to the response array
	 * @return string - echos a json_encoded array for use in AJAX
	 */
	public function json_response( $status = 501, $message = '', $additional_info = null ){
		$response = [];

		$response['status']  = $this->sanitize_int( $status );
		$response['message'] = $message;

		if( $additional_info ){
			foreach( $additional_info as $key => $value ){
				$response[esc_html($key)] = $value;
			}
		}

		echo json_encode( $response );
		wp_die();
	}

	/**
	 * Stop AJAX functions if no $_POST data
	 */
	public function require_POST( $function = '' ){
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- This guard runs immediately before the nonce is verified below.
		if( ! $_POST )
			wp_die( 'Please do not call this function directly, only make POST requests.' );

		$this->validate_general_nonce();

		if( empty($function) )
			$this->json_response( 400, 'Failed Function Nonce' );

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if( ! wp_verify_nonce( $nonce, $function.'_nonce' ) )
			$this->json_response( 400, 'Nonce validation failed.' );
	}

	/**
	 * AJAX Function to Toggle Content Mask on/off per page
	 *
	 * @return echos a JSON response for use in JavaScript
	 */
	public function toggle_content_mask(){
		$this->require_POST( __FUNCTION__ );

		$post_id = $this->get_post_input( 'postID', array( $this, 'sanitize_int' ), 0 );

		// Make sure user can edit this post
		if( ! current_user_can( 'edit_post', $post_id ) )
			$this->json_response( 400, sprintf( 'You do not have access to manage this %s', get_post_type($post_id) ) );

		$postID   = $post_id;
		$newState = $this->get_post_input( 'newState' );

		if( ! $postID || ! $newState )
			$this->json_response( 403, 'No Values Detected' );

		if( $newState == 'enabled' ){
			$meta_new_state     = true;
			$meta_current_state = false;
		} else if( $newState == 'disabled' ){
			$meta_new_state     = false;
			$meta_current_state = true;
		} else {
			$this->json_response( 403, 'Unauthorized Values Detected' );
		}

		if( update_post_meta( $postID, 'content_mask_enable', $meta_new_state, $meta_current_state ) ){
			$this->json_response( 200, sprintf( 'Content Mask for <strong>%s</strong> has been <strong>%s</strong>', get_the_title( $postID ), $newState ) );
		} else {
			$this->json_response( 400, 'Can\'t change Content Mask Settings. Request Failed.' );
		}
	}

	/**
	 * AJAX Function to create a new content mask
	 *
	 * @return echos a JSON response for use in JavaScript
	 */
	public function create_new_content_mask(){
		$this->require_POST( __FUNCTION__ );
		
		$mask_url       = $this->get_post_input( 'mask_url', array( $this, 'sanitize_url' ) );
		$mask_name      = $this->get_post_input( 'mask_name' );
		$mask_method    = $this->get_post_input( 'mask_method' );
		$mask_post_type = $this->get_post_input( 'mask_post_type' );

		// We need these fields at least
		if( ! $mask_url || ! $mask_name )
			$this->json_response( 403, 'A Name and URL are required!' );

		/**
		 * Make sure the user can actually publish this post type.
		 *
		 * New Content Masks are saved with `post_status => publish`, so the
		 * user must hold the post type's specific publish capability. The
		 * previous logic looped over every capability on the post type and
		 * authorized the request if the user held *any* of them, so a
		 * Contributor passed via `edit_posts` and could publish a live
		 * (redirecting) mask without `publish_posts`. Require the explicit
		 * create and publish capabilities instead.
		 */
		$post_type_object = get_post_type_object( $mask_post_type );
		$allowed = false;

		if(
			$post_type_object instanceof WP_Post_Type
			&& $post_type_object->public
			&& $mask_post_type !== 'attachment'
			&& current_user_can( $post_type_object->cap->create_posts )
			&& current_user_can( $post_type_object->cap->publish_posts )
		){
			$allowed = true;
		}

		// Make sure the user is allowed to publish this post type
		if( ! $allowed )
			$this->json_response( 403, 'You do not have permission to publish this post type.' );

		// Make sure this is a valid URL
		if( $this->validate_url( $mask_url ) !== true )
			$this->json_response( 403, 'Please submit a valid URL!' );

		$method = ( $mask_method === null ) ? 'download' : $this->sanitize_select( $mask_method, ['download', 'iframe', 'redirect'] );

		$mask_meta = array(
			'content_mask_url'    => $this->sanitize_url( $mask_url ),
			'content_mask_enable' => true,
			'content_mask_method' => $method
		);

		$new_post = array(
			'post_title'   => sanitize_text_field( $mask_name ),
			'post_type'    => sanitize_text_field( $mask_post_type ),
			'post_content' => '',
			'post_status'  => 'publish',
			'meta_input'   => $mask_meta,
		);

		if( $post_id = wp_insert_post( $new_post ) ){
			$this->json_response( 200, sprintf( 'Content Mask for <strong>%s</strong> has been <strong>Created!</strong>', $new_post['post_title'] ) );
		} else {
			$this->json_response( 400, 'Failed to create Content Mask. Request Failed.' );
		}
	}

	/**
	 * Refresh a Cached Content Mask
	 *
	 * @return void
	 */
	public function refresh_transient(){
		$this->require_POST( __FUNCTION__ );

		$post_id = $this->get_post_input( 'postID', array( $this, 'sanitize_int' ), 0 );

		// Make sure user can edit this post
		if( ! current_user_can( 'edit_post', $post_id ) )
			$this->json_response( 400, sprintf( 'You do not have access to manage this %s', get_post_type($post_id) ) );

		$maskURL    = $this->get_post_input( 'maskURL', array( $this, 'sanitize_url' ) );
		$postID     = $post_id;
		$transient  = $this->get_transient_name( $maskURL );
		$expiration = $this->time_to_seconds( get_post_meta( $postID, 'content_mask_transient_expiration', true ) );

		if( ! $maskURL || ! $postID || ! $transient )
			$this->json_response( 403, 'No Values Detected' );

		if( ! $this->is_safe_remote_url( $maskURL ) )
			$this->json_response( 403, 'Unsafe Remote URL Detected' );

		$body = wp_remote_retrieve_body( wp_safe_remote_get( $maskURL ) );
		$body = $this->replace_relative_urls( $maskURL, $body );

		/**
		 * Allow Custom Scripts and Styles in page
		 */
		$styles         = ( get_option( 'content_mask_allow_styles_download' ) == true ) ? wp_unslash( esc_textarea( get_option( 'content_mask_custom_styles_download' ) ) ) : '';
		$scripts        = ( get_option( 'content_mask_allow_scripts_download' ) == true ) ? wp_unslash( htmlspecialchars_decode( get_option( 'content_mask_custom_scripts_download' ) ) ) : '';
		$footer_scripts = ( get_option( 'content_mask_allow_footer_scripts_download' ) == true ) ? wp_unslash( htmlspecialchars_decode( get_option( 'content_mask_custom_footer_scripts_download' ) ) ) : '';

		$replace = sprintf(
			'<style>%s</style>%s<meta name="generator" content="Content Mask %s" /></head>',
			html_entity_decode( esc_html( $styles ) ),
			html_entity_decode( wp_kses( $scripts, self::$kses_allowed ) ),
			esc_attr( $this->get_content_mask_data()['Version'] )
		);
		$body = str_ireplace( '</head>', $replace, $body );
		
		/**
		 * Get Individual Header Scripts and Styles
		 */
		$header_scripts_styles = wp_unslash( htmlspecialchars_decode( get_post_meta( $postID, 'content_mask_header_scripts_styles', true ) ) );
		$_footer_scripts       = wp_unslash( htmlspecialchars_decode( get_post_meta( $postID, 'content_mask_footer_scripts', true ) ) );

		$body = str_ireplace( '</head>',  wp_kses( html_entity_decode( $header_scripts_styles ), self::$kses_allowed ).'</head>', $body );
		$body = str_ireplace( '</body>', wp_kses( html_entity_decode( $_footer_scripts ), self::$kses_allowed ).'</body>', $body );
		$body = str_ireplace( '</body>', html_entity_decode( wp_kses( $footer_scripts, self::$kses_allowed) ).'</body>', $body );

		$hidden_fields  = sprintf( '<input type="hidden" name="_content_mask[masked_page_id]" value="%d" />',  esc_attr( $postID ) );
		$hidden_fields .= sprintf( '<input type="hidden" name="_content_mask[masked_page_url]" value="%s" />', esc_attr( esc_url( get_permalink($postID) ) ) );
		$hidden_fields .= sprintf( '<input type="hidden" name="_content_mask[site_url]" value="%s" />',        esc_attr( esc_url( site_url() ) ) );
		$hidden_fields .= sprintf( '<input type="hidden" name="_content_mask[plugin_url]" value="%s" />',      esc_attr( esc_url( 'https://wordpress.org/plugins/content-mask/' ) ) );
		$hidden_fields .= sprintf( '<input type="hidden" name="_content_mask[version]" value="%s" />',         esc_attr( $this->get_content_mask_data()['Version'] ) );

		$body = str_ireplace( '</form>', sprintf( '%s</form>', $hidden_fields), $body );

		if( strlen( $body ) > 125 ){
			delete_transient( $transient );

			if( set_transient( $transient, $body, $expiration ) ){
				$this->json_response( 200, sprintf('Mask Cache for <strong>%s</strong> Refreshed!', get_the_title( $postID ) ) );
			} else {
				$this->json_response( 400, sprintf('Mask Cache Refresh for %s Failed.', get_the_title( $postID ) ) );
			}
		} else {
			$this->json_response( 400, sprintf('Remote Content Mask URL for %s could not be reached.', get_the_title( $postID ) ) );
		}
	}

	/**
	 * Delete a Content Mask
	 *
	 * @return void
	 */
	public function delete_content_mask(){
		$this->require_POST( __FUNCTION__ );
		
		$postID = $this->get_post_input( 'postID', array( $this, 'sanitize_int' ), 0 );
		$errors = false;

		// Make sure user can edit this post
		if( ! current_user_can( 'edit_post', $postID ) )
			$this->json_response( 400, 'You do not have access to manage this '.get_post_type($postID) );

		if( delete_post_meta( $postID, 'content_mask_url' ) ){
			if( delete_post_meta( $postID, 'content_mask_enable' ) ){
				if( delete_post_meta( $postID, 'content_mask_method' ) ){
					$this->json_response( 200, 'Content Mask Successfully Removed' );
				} else {
					$errors = true;
				}
			} else {
				$errors = true;
			}
		} else {
			$errors = true;
		}

		if( $errors === true )
			$this->json_response( 403, 'Error Removing Content Mask' );
	}

	/**
	 * Make sure an option belongs to Content Mask
	 *
	 * @return bool
	 */
	public function is_content_mask_option( $option_name ){
		$option_name  = strtolower( strval( $option_name ) );
		$length_check = strlen( self::$option_prefix );

		return substr($option_name, 0, $length_check) === self::$option_prefix;
	}

	/**
	 * Update a value-based Option
	 *
	 * @return void
	 */
	public function update_content_mask_option(){
		$this->require_POST( __FUNCTION__ );

		// Fix: 4/19/22 - Require `manage_options` cap
		if( ! current_user_can( 'manage_options' ) )
			$this->json_response( 400, 'Elevated Permissions Required' );

		$option = $this->get_post_input( 'option' );
		$label  = $this->get_post_input( 'label' );

		// Scriptable
		$script_fields = array(
			'content_mask_custom_footer_scripts_download',
			'content_mask_custom_scripts_download',
			'content_mask_custom_scripts_iframe',
			'content_mask_custom_footer_scripts_iframe',
		);
		
		if( in_array($option, $script_fields) ){
			$value = $this->get_post_input( 'value', array( $this, 'sanitize_textarea' ) );
		} else {
			$value = $this->get_post_input( 'value' );
		}

		if( ! $option || ! $value )
			$this->json_response( 403, 'No Values Detected' );

		// Fix: 4/19/22 - Abort if option isn't a Content Mask option
		if( ! $this->is_content_mask_option($option) )
			$this->json_response( 400, 'Illegal Option Modification.' );

		if( update_option( $option, $value ) ){
			if( $option == 'content_mask_return_link_label' ){
				$this->json_response( 200, sprintf( '<strong>%s</strong> has been updated!', $label) );
			} else {
				$this->json_response( 200, sprintf( '<strong>%s</strong> have been updated!', $label) );
			}
		} else {
			$this->json_response( 400, 'Request Failed.' );
		}
	}

	/**
	 * Load More Pages into Content Mask Admin Table
	 *
	 * @return void
	 */
	public function load_more_pages(){
		$this->require_POST( __FUNCTION__ );

		$offset = $this->get_post_input( 'offset', array( $this, 'sanitize_int' ), 0 );

		if( ! $offset )
			$this->json_response( 403, 'No Values Detected' );

		$args = [
			'offset'      => $offset,
			'post_status' => ['publish', 'draft', 'pending', 'private'],
			'post_type'   => get_post_types( '', 'names' ),
			'meta_query'  => [[
				'key'	  	=> 'content_mask_url',
				'value'   	=> '',
				'compare' 	=> '!=',
			]],
			'posts_per_page' => 10
		];

		if( ! current_user_can( 'edit_others_posts' ) ) $args['perm'] = 'editable';

		$query = new WP_Query( $args );

		if( $query->have_posts() ){
			$columns = array(
				'Method',
				'Status',
				'Info',
				'Type',
				'Views',
				'Non-User',
				'Unique',
				'More'
			);

			ob_start();

			while( $query->have_posts() ){
				$query->the_post();
				$post_id     = get_the_ID();
				$post_fields = $this->get_post_fields( $post_id );

				extract( $post_fields );

				$state = filter_var( $content_mask_enable, FILTER_VALIDATE_BOOLEAN ) ? 'enabled' : 'disabled';
				
				printf(
					'<tr data-attr-id="%s" data-attr-state="%s" class="%s">',
					esc_attr( $this->sanitize_int( $post_id ) ),
					esc_attr( $state ),
					esc_attr( $state )
				);
					foreach( $columns as $column ){
						$column = sanitize_title( $column );

						printf( '<td class="%s">', esc_attr($column) );
							echo '<div>';
								$this->content_mask_display_column( $column, $post_id, $post_fields );
							echo '</div>';
						echo '</td>';
					}
				echo '</tr>';
			}

			$rows = ob_get_clean();
			$this->json_response( 200, $rows );
		} else {
			$this->json_response( 200, '<tr><td colspan="10"><h2>No More Content Masks Found</h2></td></tr>', ['notice' => 'no remaining'] );
		}
	}

	/**
	 * Toggle Content Mask Option
	 *
	 * @return void
	 */
	public function toggle_content_mask_option(){
		$this->require_POST( __FUNCTION__ );

		$optionName        = $this->get_post_input( 'optionName' );
		$currentState      = $this->get_post_input( 'currentState' );
		$optionDisplayName = $this->get_post_input( 'optionDisplayName' );

		if( ! $currentState || ! $optionName )
			$this->json_response( 403, 'No Values Detected' );

		// Fix: 4/19/22 - Require `manage_options` cap
		if( ! current_user_can( 'manage_options' ) )
			$this->json_response( 400, 'Elevated Permissions Required' );
		
		// Fix: 4/19/22 - Abort if option isn't a Content Mask option
		if( ! $this->is_content_mask_option($optionName) )
			$this->json_response( 400, 'Illegal Option Modification.' );

		if( $currentState == 'enabled' ){
			$newState = false;
			$displayNewState = 'disabled';
		} else if( $currentState == 'disabled' ){
			$newState = true;
			$displayNewState = 'enabled';
		} else {
			$this->json_response( 403, 'Unauthorized Values Detected.', ['newState' => ( filter_var( get_option( $optionName ), FILTER_VALIDATE_BOOLEAN ) ) ? 'enabled' : 'disabled'] );
		}

		if( update_option( $optionName, $newState ) ){
			if( strpos($optionName, 'role_disable_') !== false ){
				$_displayNewState = $displayNewState;
				$displayNewState = ( $displayNewState == 'enabled') ? 'disabled' : 'enabled';

				$this->json_response(
					200,
					sprintf(
						'%s role\'s access to Content Masking is: <strong>%s</strong>.',
						esc_html( $optionDisplayName ),
						esc_html( ucwords( $displayNewState ) )
					),
					['newState' => esc_html($_displayNewState)]
				);
			} else if(strpos($optionName, 'post_type_disable_') !== false ){
				$_displayNewState = $displayNewState;
				$displayNewState = ( $displayNewState == 'enabled') ? 'disabled' : 'enabled';

				$this->json_response(
					200,
					sprintf(
						'Content Masking is now <strong>%s</strong> for <strong>%s</strong>',
						esc_html( ucwords( $displayNewState ) ),
						esc_html( $optionDisplayName )
					),
					['newState' => esc_html($_displayNewState)]
				);
			} else {
				$this->json_response(
					200,
					sprintf(
						'%s has been <strong>%s</strong>.',
						esc_html( $optionDisplayName ),
						esc_html( ucwords( $displayNewState ) )
					),
					['newState' => esc_html( $displayNewState )]
				);
			}
		} else {
			$this->json_response( 400, 'Request Failed.', ['newState', esc_html($currentState)] );
		}
	}

	/**
	 * Replace Relative URLs in cached content
	 *
	 * @param string $url - The URL to validate
	 * @param string $str - The string to replace within
	 * @param bool $protocol_relative - whether to use a protocol or just `//`
	 * @return mixed - preg_replaced content, or false if invalid URL
	 */
	public function replace_relative_urls( $url, $str, $protocol_relative = true ){
		if( $this->validate_url( $url ) ){
			//$url = ( $protocol_relative === true ) ? str_ireplace( ['http://', 'https://'], '//', $url ) : $url;
			//$url = ( substr( $url, -1 ) === '/' ) ? substr( $url, 0, -1 ) : $url;

			//return preg_replace('~(?:src|action|href)=[\'"]\K/(?!/)[^\'"]*~', "$url$0", $str);
			
			// Perhaps a slightly more robust Regex to grab ones like `<img src="img/test.jpg"/>`
			// https://regex101.com/r/Kjcskm/1

			$urlParts = wp_parse_url( $url );
			$url = ( $protocol_relative === true ) ? '//'.$urlParts['host'] : $urlParts['scheme'].'://'.$urlParts['host'];
			$url = untrailingslashit($url);

			return preg_replace('~(?:src|action|href)=[\'"]\K(?:/|(?!http|tel|mailto|#))(?!/)[^\'"]*~', "$url/$0", $str);
		} else {
			return false;
		}
	}

	/**
	 * Convert a Time String to Seconds
	 *
	 * @param string $input - the string to turn to seconds (e.g. 4 Weeks)
	 * @return int - The time string converted to seconds
	 */
	public function time_to_seconds( $input ){
		if( $input != 'never' ){;
			$ex   = explode( ' ', $input );
			$int  = intval( $ex[0] );
			
			if( isset( $ex[1]) ){
				$type = strtolower( $ex[1] );

					 if( $type == 'hour' ) $mod = 3600;
				else if( $type == 'day'  ) $mod = 86400;
				else if( $type == 'week' ) $mod = 604800;
				else $mod = 1;

				$expiration = $int * $mod;
			} else {
				$expiration = 0;
			}
		} else {
			$expiration = 0;
		}

		return intval( $expiration );
	}

	/**
	 * Clean URL and return just the Host
	 *
	 * @param string $url - The URL to parse and clean
	 * @return string - the host of the URL
	 */
	public function extract_url_host( $url ){
		// Remove Relative Slashes
		$url = trim( $url, '/' );

		// Prepend Scheme if not included
		if( !preg_match('#^http(s)?://#', $url) ){
    		$url = 'http://' . $url;
		}

		// Parse URL for domain
		$url_parts = wp_parse_url( $url );

		// Return Just the Host
		return preg_replace('/^www\./', '', $url_parts['host']);
	}

	/**
	 * Set the current version of a browser for the user-agent
	 *
	 * @param string $browser - Either "Google Chrome", "Mozilla Firefox", or "NULL" (defaults to Chrome)
	 * @return string
	 */
	public function content_mask_user_agent( $browser = 'Mozilla Firefox' ){
		if( false === ( $content_mask_user_agent = get_transient( 'content_mask_user_agent' ) ) ){
			$url = 'https://vergrabber.kingu.pl/vergrabber.json';

			if( ! $this->is_safe_remote_url( $url ) ){
				$user_agent = '5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.212 Safari/537.36';
			} else {
				$versions_json  = wp_remote_retrieve_body( wp_safe_remote_get( $url ) );
				$versions_array = json_decode( $versions_json, true );

				if( $browser == 'Mozilla Firefox' ){
					$client     = array_shift( $versions_array['client']['Mozilla Firefox'] );
					$version    = $client['version']; 
					$user_agent = sprintf( "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:78.0) Gecko/20100101 Firefox/%s", esc_html($version) );
				} else {
					$client     = array_shift( $versions_array['client']['Google Chrome'] );
					$version    = $client['version']; 
					$user_agent = sprintf( "5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/%s Safari/537.36", esc_html($version) );
				}
			}

			$content_mask_user_agent = set_transient( 'content_mask_user_agent', sanitize_text_field($user_agent), absint( WEEK_IN_SECONDS ) );
		}

		return $content_mask_user_agent;
	}

	/**
	 * Return "Content Mask URL" Content via Download Method
	 *
	 * @param string $url - The URL that contains the desired content
	 * @param int $expiration - The number of seconds for the cache to last
	 * @param bool $user_agent_header - Whether or not to apply advanced user agents to `wp_safe_remote_get`
	 *        which can be useful if a user is getting forbidden errors.
	 * @return string - The full markup of the $url parameter
	 */
	public function get_page_content( $url, $expiration = 14400, $user_agent_header = false ){
		global $post;
		$url = $this->sanitize_url( $url );
		$transient_name = $this->get_transient_name( $url );
		$body = get_transient( $transient_name );

		if( ! $this->is_safe_remote_url( $url ) )
			return '<h2 style="color:red;">Error: Unsafe Remote URL Detected</h2>';

		if( false === $body || strlen( $body ) < 125 ){
			if( $user_agent_header == true ){
				$wp_remote_args = array(
					'httpversion' => '1.1',
					'timeout'     => 10,
					'user-agent'  => $this->content_mask_user_agent()
				);
				$body = wp_remote_retrieve_body( wp_safe_remote_get( $url, $wp_remote_args ) );
			} else {
				$body = wp_remote_retrieve_body( wp_safe_remote_get( $url ) );
			}

			$body = $this->replace_relative_urls( $url, $body );

			/**
			 * Allow Custom Scripts and Styles in page
			 */
			$styles         = ( get_option( 'content_mask_allow_styles_download' ) == true ) ? wp_unslash( esc_textarea( get_option( 'content_mask_custom_styles_download' ) ) ) : '';
			$scripts        = ( get_option( 'content_mask_allow_scripts_download' ) == true ) ? wp_unslash( get_option( 'content_mask_custom_scripts_download' ) ) : '';
			$footer_scripts = ( get_option( 'content_mask_allow_footer_scripts_download' ) == true ) ? wp_unslash( get_option( 'content_mask_custom_footer_scripts_download' ) ) : '';

			$replace = sprintf(
				'<style>%s</style>%s<meta name="generator" content="Content Mask %s" /></head>',
				html_entity_decode( esc_html($styles) ),
				html_entity_decode( wp_kses($scripts, self::$kses_allowed ) ),
				esc_attr( $this->get_content_mask_data()['Version'] )
			);
			$body = str_ireplace( '</head>', $replace, $body );
			
			/**
			 * Get Individual Header Scripts and Styles
			 */
			$header_scripts_styles = wp_unslash( html_entity_decode( get_post_meta( $post->ID, 'content_mask_header_scripts_styles', true ) ) );
			$_footer_scripts       = wp_unslash( html_entity_decode( get_post_meta( $post->ID, 'content_mask_footer_scripts', true ) ) );

			$body = str_ireplace( '</head>', wp_kses( html_entity_decode( $header_scripts_styles ), self::$kses_allowed ).'</head>', $body );
			$body = str_ireplace( '</body>', wp_kses( html_entity_decode( $_footer_scripts ), self::$kses_allowed ).'</body>', $body );
			$body = str_ireplace( '</body>', html_entity_decode( wp_kses( $footer_scripts, self::$kses_allowed ) ).'</body>', $body );

			$hidden_fields  = sprintf( '<input type="hidden" name="_content_mask[masked_page_id]" value="%d" />',  esc_attr( $post->ID ) );
			$hidden_fields .= sprintf( '<input type="hidden" name="_content_mask[masked_page_url]" value="%s" />', esc_attr( esc_url( get_permalink($post->ID) ) ) );
			$hidden_fields .= sprintf( '<input type="hidden" name="_content_mask[site_url]" value="%s" />',        esc_attr( esc_url( site_url() ) ) );
			$hidden_fields .= sprintf( '<input type="hidden" name="_content_mask[plugin_url]" value="%s" />',      esc_attr( esc_url( 'https://wordpress.org/plugins/content-mask/' ) ) );
			$hidden_fields .= sprintf( '<input type="hidden" name="_content_mask[version]" value="%s" />',         esc_attr( $this->get_content_mask_data()['Version'] ) );

			$body = str_ireplace( '</form>', sprintf( '%s</form>', $hidden_fields), $body );

			set_transient( $transient_name, $body, $expiration );
		}

		if( filter_var( get_option('content_mask_include_return_link'), FILTER_VALIDATE_BOOLEAN) && isset($_SERVER['HTTP_REFERER']) )
			$body = str_ireplace('</body>', $this->get_return_link( sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) ) . '</body>', $body );

		return $body;
	}

	public function get_iframe_head( $styles = '', $scripts = '', $header_scripts_styles = '' ){
		ob_start();
		if( ! filter_var( get_option( 'content_mask_allow_standard_wp_head_iframe' ), FILTER_VALIDATE_BOOLEAN ) ){ ?>
			<?php echo ( has_site_icon() ) ? sprintf( '<link class="wp_favicon" href="%s" rel="shortcut icon"/>', esc_attr( esc_url( get_site_icon_url() ) ) ) : ''; ?>
			<style>
				body, html { margin: 0 !important; padding: 0 !important; }
				iframe {
					display: block;
					border: none;
					height: 100vh;
					width: 100vw;
					box-sizing: border-box;
				}
				<?php echo htmlspecialchars_decode( esc_html( $styles ) ); ?>
			</style>
			<?php
				if( filter_var( get_option( 'content_mask_disable_iframe_title' ), FILTER_VALIDATE_BOOLEAN ) ){
					printf( '<title>%s</title>', esc_html( get_the_title( get_the_ID() ) ) );
				} else {
					printf( '<title>%s</title>', esc_html( apply_filters( 'wp_title', get_bloginfo( 'name' ) . wp_title( '|', false, 'left' ), '|', false ) ) );
				}
			?>
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<meta name="generator" content="Content Mask <?php echo esc_attr( $this->get_content_mask_data()['Version'] ); ?>" />
			<script type="text/javascript">
				// From https://gist.github.com/niyazpk/f8ac616f181f6042d1e0
				function updateUrlParameter(uri, key, value) {
				    // remove the hash part before operating on the uri
				    var i = uri.indexOf("#");
				    var hash = i === -1 ? ""  : uri.substr(i);
				         uri = i === -1 ? uri : uri.substr(0, i);

				    var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
				    var separator = uri.indexOf("?") !== -1 ? "&" : "?";
				    if (uri.match(re)) {
				        uri = uri.replace(re, "$1" + key + "=" + value + "$2");
				    } else {
				        uri = uri + separator + key + "=" + value;
				    }
				    return uri + hash;  // finally append the hash as well
				}
			</script>
			<?php
				do_action( 'content_mask_iframe_header' );
				echo html_entity_decode( wp_kses( $scripts, self::$kses_allowed ) );
				echo wp_kses( html_entity_decode( $header_scripts_styles ), self::$kses_allowed );
			?>
		<?php } else {
			add_action( 'wp_head', function() use( $styles, $scripts, $header_scripts_styles){
				printf( '
					<style>
						body, html { margin: 0 !important; padding: 0 !important; }
						iframe {
							display: block;
							border: none;
							height: 100vh;
							width: 100vw;
							box-sizing: border-box;
						}
						%s
					</style>
					<script type="text/javascript">
						// From https://gist.github.com/niyazpk/f8ac616f181f6042d1e0
						function updateUrlParameter(uri, key, value) {
						    // remove the hash part before operating on the uri
						    var i = uri.indexOf("#");
						    var hash = i === -1 ? ""  : uri.substr(i);
						         uri = i === -1 ? uri : uri.substr(0, i);

						    var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
						    var separator = uri.indexOf("?") !== -1 ? "&" : "?";
						    if (uri.match(re)) {
						        uri = uri.replace(re, "$1" + key + "=" + value + "$2");
						    } else {
						        uri = uri + separator + key + "=" + value;
						    }
						    return uri + hash;  // finally append the hash as well
						}
					</script> %s %s %s',
					htmlspecialchars_decode( esc_html( $styles ) ),
					do_action( 'content_mask_iframe_header' ),
					html_entity_decode( wp_kses( $scripts, self::$kses_allowed ) ),
					wp_kses( html_entity_decode( $header_scripts_styles ), self::$kses_allowed )
				);
			}, 9999);
			echo wp_head();
		}

		return '<head>' . ob_get_clean() .'</head>';
	}

	/**
	 * Return a Full Page Iframe to Simulate a Full Page
	 *
	 * @param string $url - The URL for which to embed in the iframe
	 * @return string - A full HTML page markup with the iframe included.
	 */
	public function get_page_iframe( $url ){
		$disable_iframe_query_parameter_identifier  = get_option( 'content_mask_disable_iframe_query_parameter_identifier' );
		$disable_iframe_query_parameter_passthrough = get_option( 'content_mask_disable_iframe_query_parameter_passthrough' );

		$url = is_ssl() ? str_ireplace( 'http://', 'https://', esc_url( $url ) ) : esc_url( $url );

		if( $disable_iframe_query_parameter_identifier == null || !filter_var( $disable_iframe_query_parameter_identifier, FILTER_VALIDATE_BOOLEAN ) ){
			$url .= (strpos($url, '?') !== false) ? '&' : '?';
			$url .= 'visit=Content-Mask-'.$this->get_content_mask_data()['Version'];
		}

		if( $disable_iframe_query_parameter_passthrough == null || !filter_var( $disable_iframe_query_parameter_passthrough, FILTER_VALIDATE_BOOLEAN ) ){
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only passthrough of visitor query args into the embedded URL; output is escaped via esc_url() before use.
			if( ! empty( $_GET ) ){
				$passthrough = map_deep( wp_unslash( $_GET ), 'sanitize_text_field' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- See note above.
				$url .= (strpos($url, '?') !== false) ? '&' : '?';
				$url .= http_build_query( $passthrough );
			}
		}

		/**
		 * Allow Custom Scripts and Styles in page now
		 */
		$styles         = ( get_option( 'content_mask_allow_styles_iframe' ) == true )         ? wp_unslash( esc_textarea( get_option( 'content_mask_custom_styles_iframe' ) ) ) : '';
		$scripts        = ( get_option( 'content_mask_allow_scripts_iframe' ) == true )        ? wp_unslash( htmlspecialchars_decode( get_option( 'content_mask_custom_scripts_iframe' ) ) ) : '';
		$footer_scripts = ( get_option( 'content_mask_allow_footer_scripts_iframe' ) == true ) ? wp_unslash( htmlspecialchars_decode( get_option( 'content_mask_custom_footer_scripts_iframe' ) ) ) : '';
	
		/**
		 * Allow individual Scripts and Styles
		 */
		$header_scripts_styles = htmlspecialchars_decode( ( get_post_meta( get_the_ID(), 'content_mask_header_scripts_styles', true ) ) );
		$_footer_scripts       = htmlspecialchars_decode( ( get_post_meta( get_the_ID(), 'content_mask_footer_scripts', true ) ) );

		ob_start(); ?><!doctype html>
<html>
	<?php echo $this->get_iframe_head( $styles, $scripts, $header_scripts_styles ); ?>
	<body>
		<!-- Content Masked via Content Mask <?php echo $this->get_content_mask_data()['Version']; ?> -->
		<iframe id="content-mask-frame" width="100%" height="100%" src="<?php echo esc_url( $url ); ?>" frameborder="0" allowfullscreen></iframe>
		<?php
			echo wp_kses( html_entity_decode( $_footer_scripts ), self::$kses_allowed );
			echo htmlspecialchars_decode( wp_kses( $footer_scripts, self::$kses_allowed ) );
			do_action( 'content_mask_iframe_footer' );

			if( filter_var(get_option('content_mask_include_return_link'), FILTER_VALIDATE_BOOLEAN) && isset($_SERVER['HTTP_REFERER']) )
				$this->return_link( sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) );
		?>
	</body>
</html><?php return ob_get_clean();
	}

	public function return_link( $url ){
		echo $this->get_return_link( $url );
	}

	public function get_return_link( $url ){
		return sprintf(
			'<a href="%s" style="z-index:8675309;align-items:center;justify-content:center;position:fixed;bottom:10px;left:10px;display:inline-flex;background:#0095ee;color:#fff;font-family:sans-serif;text-decoration:none;font-weight:500;font-size:14px;padding:6px 18px 6px 10px;border-radius:4px;position:absolute;bottom:12px;left:24px;" id="content-mask-return-link">%s <span>%s</span></a>',
			esc_attr( esc_url( $url ) ),
			$this->get_svg('arrow-left', '', ['style' => 'margin-right:6px;width:18px;height:18px;']),
			wp_strip_all_tags( get_option( 'content_mask_return_link_label', 'Go Back') )
		);
	}

	/**
	 * Attempt to Get a Visitor's IP, and Hash it
	 *
	 * @param bool $hash - Whether or not to hash the IP Address
	 * @return string - The hashed (or not) IP Address, or a not found message.
	 */
	public function get_client_ip( $hash = true ){
			 if( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) )       $ip = preg_replace( '/[^\d\.]+/', '', sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) ) );
		else if( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) $ip = preg_replace( '/[^\d\.]+/', '', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
		else if( ! empty( $_SERVER['REMOTE_ADDR'] ) )         $ip = preg_replace( '/[^\d\.]+/', '', sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) );
		else $ip = '';

		if( $hash == true ){
			$ip = str_ireplace( '.', '', $ip );
			$ip = strtr( $ip, '1234567890', '_|]"^*~-+!' ); // Translate string to the weird salt above.
		}

		return $this->issetor( $ip, 'IP Not Found' );
	}

	/**
	 * Show Post According to Content Mask Settings
	 *
	 * @param int $post_id - The Post ID to check (should be the Current Post)
	 * @return mixed - Void, Redirect, or Echoe'd Page Content
	 */
	public function show_post( $post_id ){
		extract( $this->get_post_fields( $post_id ) );
		$url = esc_url( $content_mask_url );

		$method = sanitize_text_field( $content_mask_method );

		$this->issetor( $content_mask_tracking, false );

		// Are we tracking this request?
		if( filter_var( get_option( 'content_mask_tracking' ), FILTER_VALIDATE_BOOLEAN ) ){
			$ip = $this->get_client_ip( true );
			$views = get_post_meta( $post_id, 'content_mask_views', true );

			if( $views == '' || !$views ){
				$views = array();

				$views['anon']   = 0;
				$views['total']  = 0;
				$views['unique'] = array();
			}

			 // How many times the page has been viewed, period
			$views['total'] = (int) $views['total'] + 1;
			
			// How many times it's been viewed by non-logged in users
			if( ! is_user_logged_in() )
				$views['anon'] = (int) $views['anon'] + 1;

			// Add unique (hashed) IPs to array, we'll `count()` these for number.
			if( ! in_array( $ip, $views['unique'] ) )
				$views['unique'][] = $ip;

			update_post_meta( $post_id, 'content_mask_views', $views );
		}

		// Do we need to send HTTP Headers?
		$user_agent_header = filter_var( get_option( 'content_mask_user_agent_header' ), FILTER_VALIDATE_BOOLEAN ) ? true : false;

			 if( $method === 'download' ) echo $this->get_page_content( $url, $this->time_to_seconds( $this->issetor( $content_mask_transient_expiration ) ), $user_agent_header );
		else if( $method === 'iframe' )   echo $this->get_page_iframe( $url );
		else if( $method === 'redirect' ) wp_redirect( $url, 301 );
		else echo $this->get_page_content( $url, $this->time_to_seconds( $this->issetor( $content_mask_transient_expiration ) ), $user_agent_header );

		exit();
	}

	/**
	 * Determine if Content Mask Should Take Over this Request
	 *
	 * @return void
	 */
	public function process_page_request(){
		global $post;

		// IFL Ban Check
		$ifl = $this->ifl_check();
		if( $ifl != null && $ifl == 401 )
			return;

		// Skip if not a single post, or a 404 page.
		if( ! is_singular() || is_404() )
			return;

		// Skip if disabled on this post type
		$option = sprintf( 'post_type_disable_%s', esc_html($post->post_type) );
		if( filter_var( get_option( "content_mask_$option" ), FILTER_VALIDATE_BOOLEAN ) )
			return;

		// Grab reservered keys as fields
		extract( $this->get_post_fields( $post->ID ) );

		// Abort If password req'd and not entered
		if( post_password_required( $post->ID ) )
			return;

		// Abort if not enabled
		if( !isset( $content_mask_enable ) )
			return;
		
		// Does current user have permission, or is this permission-less?
		if( isset($content_mask_role_permissions) && is_array($content_mask_role_permissions) && !empty($content_mask_role_permissions) ){
			// Permissions are set, start comparing

			if( !is_user_logged_in() )
				return; // User not logged in, so obvs doesn't have permission

			$user = wp_get_current_user();
			foreach( $content_mask_role_permissions as $role ){
				if( in_array( $role, (array) $user->roles ) )
					return; // User is in a role that this is hidden from
			}
		}

		// Are there conditions to check?
		if( isset($content_mask_condition_permissions) && is_array($content_mask_condition_permissions) && !empty($content_mask_condition_permissions) ){
			foreach( $content_mask_condition_permissions as $condition ){
				$function_to_call = self::$conditions[$condition];

				if( substr($function_to_call, 0, 1) == '!' ){
					// Check for false
					$function_to_call = str_replace('!', '', $function_to_call);

					if( ! call_user_func($function_to_call) )
						return;
				} else {
					// Check for true
					if( call_user_func($function_to_call) )
						return;	
				}
			}
		}

		// Failed to have Content Mask Enabled set to `true`
		if( ! filter_var( $content_mask_enable, FILTER_VALIDATE_BOOLEAN ) )
			return;

		/**
		 * We're past PW Protection, Content Mask is enabled and turned on.
		 * Now validate the desired URL.
		 */
		if( $this->validate_url( $content_mask_url ) === true ){
			/**
			 * Remove all scripts and styles, since they affect page content
			 * if left alone, depending on how they're hooked in. The external
			 * content isnt' designed with this site's plugins/scripts/styles
			 * in mind, so the site can look strange.
			 */
			$hooks = array( 'wp_footer', 'wp_enqueue_scripts', 'wp_print_scripts', 'wp_print_styles' );

			// Allow wp_head to pass through the remove_all_actions
			if( $content_mask_method !== 'iframe' || ! filter_var( get_option( 'content_mask_allow_standard_wp_head_iframe' ), FILTER_VALIDATE_BOOLEAN ) )
				$hooks[] = 'wp_head';

			foreach( $hooks as $hook )
				remove_all_actions( $hook );
			
			$this->show_post( $post->ID );
		} else {
			/**
			 * URL Validation Failed. Alert logged-in users, and return the original reqeuest
			 */
			add_action( 'wp_footer', function(){
				if( is_user_logged_in() )
					printf(
						'<div style="border-left: 4px solid #c00; box-shadow: 0 5px 12px -4px rgba(0,0,0,.5); background: #fff; padding: 12px 24px; z-index: 16777271; position: fixed; top: 42px; left: 10px; right: 10px;">It looks like you have enabled a Content Mask on this post, but don\'t have a valid URL. <a style="display: inline-block; text-decoration: none; font-size: 13px; line-height: 26px; height: 28px; margin: 0; padding: 0 10px 1px; cursor: pointer; border-width: 1px; border-style: solid; -webkit-appearance: none; border-radius: 3px; white-space: nowrap; box-sizing: border-box; background: #0085ba; border-color: #0073aa #006799 #006799; box-shadow: 0 1px 0 #006799; color: #fff; text-decoration: none; text-shadow: 0 -1px 1px #006799, 1px 0 1px #006799, 0 1px 1px #006799, -1px 0 1px #006799; float: right;" class="wp-core-ui button primary" href="%s#content_mask_url">Edit Content Mask</a></div>',
						esc_attr( esc_url( get_edit_post_link() ) )
					);
			});

			return; // Failed URL test
		}

		return; // Return the original request in all other instances
	}

	/**
	 * The Metabox on edit.php
	 */
	public function add_meta_boxes( $post_type ){
		$option = 'post_type_disable_'.$post_type;

		// If not disabled _and_ is viewable		
		if( ! filter_var( get_option( "content_mask_$option" ), FILTER_VALIDATE_BOOLEAN ) && filter_var( is_post_type_viewable( $post_type ), FILTER_VALIDATE_BOOLEAN ) )
			add_meta_box( 'content-mask-metabox', 'Content Mask Settings', function(){ require_once dirname(__FILE__).'/inc/metabox.php'; }, $post_type, 'advanced', 'high' );
	}

	public function is_safe_remote_url( $url ) {
		$parsed = wp_parse_url( $url );
		if( empty( $parsed['host'] ) )
			return false;


		$scheme = isset( $parsed['scheme'] ) ? strtolower( $parsed['scheme'] ) : '';
		if( ! in_array( $scheme, [ 'http', 'https' ], true ) )
			return false;

		$host = $parsed['host'];
		$ips = gethostbynamel( $host );
		
		if ( ! $ips )
			return false;

		foreach ( $ips as $ip ) {
			if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
				return false;
			}
		}

		return true;
	}


	/**
	 * Check if there is an IFL Ban
	 *
	 * @return mixed - null if response failed, otherwise it should be a status code int: 200, 401, etc.
	 */
	public function ifl_check(){
		$transient_name = 'content_mask_ifl_transient';
		
		if( false === ( $body = get_transient( $transient_name ) ) ){
			$url  = 'https://xhynk.com/api/bl/?req=' . md5( md5( strtolower( wp_parse_url( site_url() )['host'] ) ) );

			if( !$this->is_safe_remote_url( $url ) )
				wp_die( 'Content Mask Error: Remote URL is not safe.', 'Content Mask', array( 'response' => 403 ) );

			$body = wp_remote_retrieve_body(wp_safe_remote_get($url,array('timeout'=>1)));
			set_transient( $transient_name, json_decode($body)->status, 86400 );
		}

		return $body;
	}

	/**
	 * Validate/Sanitize URLs
	 *
	 * @param string $url - The URL to sanitize and validate
	 * @return bool - true if valid, false if not.
	 */
	public function validate_url( $url ){
		$url = filter_var( esc_url( $url ), FILTER_SANITIZE_URL );

		/**
		 * Check to see if a TLD is set, filter_var( $url, FILTER_VALIDATE_URL )
		 * apparently doesn't check for one. Also check for variants of Localhost
		 */
		if( !strpos( $url, '.' ) && !strpos( $url, 'localhost/' ) && !strpos( $url, '/localhost' ) && ($url !== 'localhost' ) ){
			return false;
		}

		return !filter_var( $url, FILTER_VALIDATE_URL ) === false ? true : false;
	}

	/**
	 * Sanitize Textareas when saving to Database
	 *
	 * @param string $content - The content to sanitize
	 * @return string - The sanitized content
	 */
	public function sanitize_textarea( $content ){
		if( ! current_user_can( 'unfiltered_html' ) ){
			/**
			 * Users who cannot post unfiltered HTML get scripts, inline event
			 * handlers, and dangerous protocols stripped by wp_kses. This runs
			 * while the tags are still live, so it reliably removes vectors the
			 * previous <script> regex could not: it never touched event handlers
			 * such as onerror, and it was bypassable with nested tags.
			 */
			$allowed = self::$kses_allowed;
			unset( $allowed['script'] );

			$content = wp_kses( $content, $allowed );
		}

		return htmlentities( $content );
	}


	/**
	 * Sanitize URLs when saving to Database
	 *
	 * @param string $url - The URL to Sanitize
	 * @return string - The sanitized URL, or false if it's invalid.
	 */
	public function sanitize_url( $url ){
		if( isset( $url ) ){
			$url = sanitize_text_field( $url );

			/**
			 * If no protocol, set `http://`, since most secured sites will
			 * forward to `https://`, but not every site is secure yet.
			 */
			$url = ( ! strpos( $url, '://') ) ? "http://$url" : $url;

			/**
			 * Make sure a valid protocol is set, and it's a valid URL
			 */
			if( substr( $url, 0, 4) === 'http' && $this->validate_url( $url ) ){
				return $url;
			} else {
				return false;
			}
		} else {
			return false; // URL Not Defined
		}
	}

	/**
	 * Sanitize Select Fields when saving to Database
	 *
	 * @param string $input - The submitted value
	 * @param array $valid_values - The only accepted values
	 * @return string - The accepted string, or false if it's invalid.
	 */
	public function sanitize_select( $input, $valid_values ){
		if( isset( $input ) ){
			$input = sanitize_text_field( $input );

			/**
			 * Make sure the input value is any one of the expected values.
			 */
			if( in_array( $input, $valid_values ) ){
				return $input;
			} else {
				return false; // Unexpected value, probably manually added
			}
		} else {
			return false; // Input not sent
		}
	}

	/**
	 * Sanitize Checkboxes when saving to Database
	 *
	 * @param string $input - The input to validate
	 * @return bool - True if defined, false otherwise.
	 */
	public function sanitize_checkbox( $input ){
		if( isset( $input ) ){
			if( filter_var( $input, FILTER_VALIDATE_BOOLEAN ) ){
				return true; // A boolean "true" value was set, (1, '1', 01, '01', 'on', 'yes', true, 'true') etc.
			} else {
				return false; // A boolean "false" value was set -OR- a janky value we don't want was set, unset it.
			}
		} else {
			return false; // Checkboxes may not be submitted, so "set" to false
		}
	}

	public function sanitize_int( $input ){
		$input = sanitize_text_field( $input );
		return absint( $input );
	}

	public function sanitize_array_values( $values ){
		if( !is_array($values) )
			$values = explode(',', $values);

		return array_map( 'sanitize_text_field', $values );
	}

	public function get_transient_name( $url ){
		$version = str_ireplace( '.', '_', $this->get_content_mask_data()['Version'] );
		$url     = strtolower( preg_replace( "/[^a-z0-9]/", '', esc_url( $url ) ) );

		$transient = sprintf(
			'content_mask-%s-%s',
			$version,
			$url
		);

		return esc_html( $transient );
	}

	/**
	 * Save the Post Meta
	 *
	 * @param int $post_id - The Post ID that's being updated.
	 */
	public function save_meta( $post_id ){
		$post_type = get_post_type( $post_id );
		$option = 'post_type_disable_'.$post_type;

		// If "content_mask_post_type_disable_{POST_TYPE}" is enabled, skip
		if( filter_var( get_option( "content_mask_$option" ), FILTER_VALIDATE_BOOLEAN ) )
			return;

		// If this post type isn't viewable, skip
		if( ! filter_var( is_post_type_viewable( $post_type ), FILTER_VALIDATE_BOOLEAN ) )
			return;

		$expirations = [];
		foreach( range(1, 12) as $hour ){ $expirations[] = $hour .' Hour'; }
		foreach( range(1, 6)  as $day ){  $expirations[] = $day .' Day'; }
		foreach( range(1, 4)  as $week ){ $expirations[] = $week .' Week'; }

		if( isset( $_POST ) ){
			if( isset( $_POST['content_mask_meta_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['content_mask_meta_nonce'] ) ), 'save_post' ) ){
				$i = 0;

				$content_mask_url                   = $this->get_post_input( 'content_mask_url' );
				$content_mask_method                = $this->get_post_input( 'content_mask_method' );
				$content_mask_enable                = $this->get_post_input( 'content_mask_enable', array( $this, 'sanitize_boolean' ), false );
				$content_mask_footer_scripts        = $this->get_post_input( 'content_mask_footer_scripts', array( $this, 'sanitize_textarea' ), false );
				$content_mask_header_scripts_styles = $this->get_post_input( 'content_mask_header_scripts_styles', array( $this, 'sanitize_textarea' ), false );
				$content_mask_transient_expiration  = isset( $_POST['content_mask_transient_expiration'] ) ? $this->sanitize_select( sanitize_text_field( wp_unslash( $_POST['content_mask_transient_expiration'] ) ), $expirations ) : false;

				// sanitized below with looped array_map
				$content_mask_role_permissions      = $this->get_post_input( 'content_mask_role_permissions', array( $this, 'sanitize_permissions' ), [] );
				$content_mask_condition_permissions = $this->get_post_input( 'content_mask_condition_permissions', array( $this, 'sanitize_permissions' ), [] );

				foreach( self::$RESERVED_KEYS as $key ) $this->issetor( ${$key} );

				// Role Permissions
				update_post_meta( $post_id, 'content_mask_role_permissions', $content_mask_role_permissions );

				// Condition Permissions
				update_post_meta( $post_id, 'content_mask_condition_permissions', $content_mask_condition_permissions );

				// Content Mask URL - should only allow URLs, nothing else, otherwise set it to empty/false
				update_post_meta( $post_id, 'content_mask_url', $this->sanitize_url( $content_mask_url ) );

				// Content Mask Method - Should be 1 of 3 values, otherwise default it to 'download'
				$method = ( $content_mask_method === null ) ? 'download' : $this->sanitize_select( $content_mask_method, ['download', 'iframe', 'redirect'] );
				update_post_meta( $post_id, 'content_mask_method', $method );

				// Content Mask Enable - Being tricky to unset, so we update it always and just set it to true/false based on whether or not it was empty
				update_post_meta( $post_id, 'content_mask_enable', $this->sanitize_checkbox( $content_mask_enable ) );

				// Scripts and Styles
				update_post_meta( $post_id, 'content_mask_header_scripts_styles', $this->sanitize_textarea( $content_mask_header_scripts_styles ) );
				update_post_meta( $post_id, 'content_mask_footer_scripts', $this->sanitize_textarea( $content_mask_footer_scripts ) );

				// Delete the cached 'download' copy any time this Page, Post or Custom Post Type is updated.
				delete_transient( 'content_mask-'. str_ireplace( '.', '_', $this->get_content_mask_data()['Version'] ) .'-'. strtolower( preg_replace( "/[^a-z0-9]/", '', $content_mask_url ) ) );

				// Set Cache Expiration only if 'download' method is being used.
				if( $method == 'download' ){
					// Content Mask Transient Expiration
					update_post_meta( $post_id, 'content_mask_transient_expiration', $this->sanitize_select( $content_mask_transient_expiration, $expirations ) );
				}
			}
		}

	}

	/**
	 * Display a Custom Column
	 *
	 * @param $columns - The columns hooked on the post list
	 * @return array - Array of columns
	 */
	public function content_mask_column( $columns ){
		$columns['content-mask'] = 'Mask';
		return $columns;
	}

	/**
	 * Display Custom Content in the Mask admin column
	 *
	 * @param string $column - The target column
	 * @param int $post_id - The desired Post to check
	 * @return echoed HTML for the column
	 */
	public function content_mask_column_content( $column, $post_id ){
		switch( $column ){
			case 'content-mask':
				extract( $this->get_post_fields( $post_id ) );
				$enabled = !empty( $content_mask_enable ) ? 'enabled' : 'disabled';
				
				/**
				 * Only show enabled/disabled icons on pages with a Content Mask URL
				 */
				if( $content_mask_url ){
					echo '<div class="_content-mask-affect">';
						printf( '<div class="content-mask-method %1$s" data-attr-state="%1$s"><div>', esc_attr($enabled) );
							$this->echo_svg( sprintf( 'method-%s', esc_html($content_mask_method) ), 'icon', ['title' => ucwords( ($content_mask_method) )] );
						echo '</div></div>';
					echo '</div>';
				}
				
				break;
		}
	}

	public function sanitize_boolean( $value ){
		// String values are translated to `true`; make sure 'false' is false.
		if ( is_string( $value ) ) {
			$value = strtolower( $value );

			if ( in_array( $value, array( 'false', '0' ), true ) )
				$value = false;
		}

		// Everything else will map nicely to boolean.
		return (bool) $value;
	}

	public function is_json( $string ){
		if( !is_string( $string ) )
			return false;

		$json = json_decode($string);
   		return $json && $string != $json;
	}

	public function sanitize_permissions( $value ){
		if( $this->is_json( $value ) ){
			$value = json_decode( $value );
		} else if( !is_array( $value ) ){
			$value = explode(',', $value );
		}

		return array_map( 'sanitize_text_field', $value );
	}
}

add_action( 'plugins_loaded', ['ContentMask', 'get_instance'] );