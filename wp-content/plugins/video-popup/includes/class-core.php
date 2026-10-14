<?php

if ( !defined('ABSPATH') ) {
    exit;
}

/**
 * Core class handles plugin initialization and manages main components.
 * Uses singleton pattern to ensure single instance throughout the application.
 * 
 * @author   Alobaidi
 * @since    2.0.0
 */

class Video_Popup_Core {

    private static $instance = null;
    private static $plugin_consts = null;
    private $utils = null;
    
    /**
     * Retrieves the singleton instance of this class
     * Creates a new instance if one doesn't exist yet
     * 
     * @return Video_Popup_Core The single instance of this class
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * constructor - intentionally empty
     * Initialization happens in the run() method instead for better control
     */
    public function __construct() {
        // No initialization here.
    }

    /**
     * Centralized constants repository for plugin-wide configuration
     * Initializes constants on first call and caches for future reference
     * 
     * @param string|null $key Optional key to retrieve specific constant
     * @return array|mixed|null All constants, specific constant value, or null if not found
     */
    public static function plugin_consts($key = null) {
        if (self::$plugin_consts === null) {
            self::$plugin_consts = array(
                'plugin_id'         => VIDEO_POPUP_PLUGIN_ID,
                'plugin_version'    => VIDEO_POPUP_PLUGIN_VERSION,
                'plugin_path'       => VIDEO_POPUP_PLUGIN_PATH,
                'plugin_url'        => VIDEO_POPUP_PLUGIN_URL,
                'plugin_basename'   => VIDEO_POPUP_PLUGIN_BASENAME,

                'settings_key'      => VIDEO_POPUP_PLUGIN_ID . '_settings',
                'settings_group'    => VIDEO_POPUP_PLUGIN_ID . '_option_group',
                'settings_nonce'    => VIDEO_POPUP_PLUGIN_ID . '_settings_nonce',
                'settings_action'   => VIDEO_POPUP_PLUGIN_ID . '_settings_action',

                'class_utils'           => 'Video_Popup_Utils',
                'class_admin'           => 'Video_Popup_Admin',
                'class_get_started'     => 'Video_Popup_Get_Started',
                'class_shortcode'       => 'Video_Popup_Shortcode',
                'class_shortcode_page'  => 'Video_Popup_Shortcode_Reference_Page',
                'class_output'          => 'Video_Popup_Output',
                'class_tinymce'         => 'Video_Popup_TinyMCE',
                'class_onpageload'      => 'Video_Popup_OnPageLoad'
            );
        }

        return $key ? (self::$plugin_consts[$key] ?? null) : self::$plugin_consts;
    }

    /**
     * Adds custom links to plugin's meta information row
     * Displays reference and promotional links
     * 
     * @param array $links Existing plugin meta links
     * @param string $file Current plugin file being processed
     * @return array Modified links array
     */
    public function plugin_row_meta_custom($links, $file) {
        $plugin_file = $this->plugin_consts('plugin_basename');

        if ( $file == $plugin_file ) {
            $plugin_reference_url = $this->plugin_consts('plugin_ref_url');

            $custom_links = array(
                '<a style="font-weight:bold;" href="https://wp-time.com/video-popup-plugin-for-wordpress/" target="_blank">' . esc_html__('Plugin Reference', 'video-popup') . '</a>',
                '<a style="font-weight:bold;color:#4f9915;" href="https://videopopup.net/?utm_source=plugin&utm_medium=rm_link&utm_campaign=plugins_page#get-premium" target="_blank">' . esc_html__('Get Premium Version', 'video-popup') . '</a>',
                '<a style="font-weight:bold;color:#ce7825;" href="https://wordpress.org/plugins/the-preloader/" target="_blank">' . esc_html__('Preloader Plugin', 'video-popup') . '</a>'
            );
            
            $links = array_merge($links, $custom_links);
        }
        return $links;
    }

    /**
     * Adds settings link to plugin action links
     * Places settings as first item before activate/deactivate links
     * 
     * @param array $actions Existing plugin action links
     * @param string $plugin_file Current plugin file being processed
     * @return array Modified action links array
     */
    public function plugin_action_links_custom($actions, $plugin_file){
        $plugin = $this->plugin_consts('plugin_basename');
            
        if ($plugin == $plugin_file) {
            $plugin_id = $this->plugin_consts('plugin_id');
            $builder_guide = '<a href="' . admin_url('admin.php?page=' . $plugin_id . '_builder') . '">' . esc_html__('Create a Popup', 'video-popup') . '</a>';
            $settings_link = '<a href="' . admin_url('admin.php?page=' . $plugin_id) . '">'.esc_html__('Settings', 'video-popup').'</a>';
            $actions = array_merge(array($builder_guide, $settings_link), $actions);
        }

        return $actions;
    }
        
    /**
     * Main initialization method for plugin functionality
     * Checks for conflicting plugins and initializes components
     */
    public function initialize() {
        if ( is_admin() ) {
            add_filter('plugin_row_meta', array($this, 'plugin_row_meta_custom'), 10, 2);
        }

        $this->init_components();
    }
    
    /**
     * Initializes all plugin components in proper dependency order
     * Controls component loading based on context (admin/frontend)
     */
    private function init_components() {
        $this->load_utils();
        
        if ( $this->utils === true ){
            if ( is_admin() ) {
                $this->load_admin();
                $this->load_onpageload();
                $this->load_get_started();
                $this->load_shortcode_page();
                $this->load_tinymce();
            }
            $this->load_shortcode();
            $this->load_output();
        }
    }

    /**
     * Loads the utility class essential for all components
     * Must be loaded first as other components depend on it
     */
    private function load_utils() {
        $class_name = $this->plugin_consts('class_utils');

        if ( !class_exists($class_name) ) {
            $utils_class_file = $this->plugin_consts('plugin_path') . 'includes/class-utils.php';
            require_once $utils_class_file;
            $this->utils = true;
        }
    }
    
    /**
     * Loads the admin settings and interface components
     * Only loaded in admin context
     */
    private function load_admin() {
        $class_name = $this->plugin_consts('class_admin');

        if ( !class_exists($class_name) ) {
            $class_file = $this->plugin_consts('plugin_path') . 'includes/class-admin.php';
            require_once $class_file;
            $class = Video_Popup_Admin::get_instance();
            $class->run();
            add_filter('plugin_action_links', array($this, 'plugin_action_links_custom'), 10, 5);
        }
    }

    /**
     * Loads the get started and interface components
     * Only loaded in admin context
     */
    private function load_get_started() {
        $class_name = $this->plugin_consts('class_get_started');

        if ( !class_exists($class_name) ) {
            $class_file = $this->plugin_consts('plugin_path') . 'includes/class-get-started.php';
            require_once $class_file;
            $class = Video_Popup_Get_Started::get_instance();
            $class->run();
        }
    }
    
    /**
     * Loads the shortcode handling component
     * Available in both admin and frontend contexts
     */
    private function load_shortcode() {
        $class_name = $this->plugin_consts('class_shortcode');

        if ( !class_exists($class_name) ) {
            $class_file = $this->plugin_consts('plugin_path') . 'includes/class-shortcode.php';
            require_once $class_file;
            $class = Video_Popup_Shortcode::get_instance();
            $class->run();
        }
    }

    /**
     * Loads the shortcode reference documentation page
     * Only loaded in admin context
     */
    private function load_shortcode_page() {
        $class_name = $this->plugin_consts('class_shortcode_page');

        if ( !class_exists($class_name) ) {
            $class_file = $this->plugin_consts('plugin_path') . 'includes/class-shortcode-reference-page.php';
            require_once $class_file;
            $class = Video_Popup_Shortcode_Reference_Page::get_instance();
            $class->run();
        }
    }
    
    /**
     * Loads the frontend output component
     * Handles CSS/JS assets and display logic
     */
    private function load_output() {
        $class_name = $this->plugin_consts('class_output');

        if ( !class_exists($class_name) ) {
            $class_file = $this->plugin_consts('plugin_path') . 'includes/class-output.php';
            require_once $class_file;
            $class = Video_Popup_Output::get_instance();
            $class->run();
        }
    }
    
    /**
     * Loads the TinyMCE integration component
     * Adds custom buttons to the classic editor
     * Only loaded in admin context
     */
    private function load_tinymce() {
        $class_name = $this->plugin_consts('class_tinymce');

        if ( !class_exists($class_name) ) {
            $class_file = $this->plugin_consts('plugin_path') . 'includes/class-tinymce.php';
            require_once $class_file;
            $class = Video_Popup_TinyMCE::get_instance();
            $class->run();
        }
    }
    
    /**
     * Loads the on-page-load popup functionality
     * Only loaded in admin context
     */
    private function load_onpageload() {
       $class_name = $this->plugin_consts('class_onpageload');

        if ( !class_exists($class_name) ) {
            $class_file = $this->plugin_consts('plugin_path') . 'includes/class-onpageload.php';
            require_once $class_file;
            $class = Video_Popup_OnPageLoad::get_instance();
            $class->run();
        }
    }

}