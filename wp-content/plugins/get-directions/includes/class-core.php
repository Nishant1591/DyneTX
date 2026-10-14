<?php

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 */
namespace Get_Directions\Includes;

use  Get_Directions\Admin\Admin ;
use  Get_Directions\Admin\Settings ;
use  Get_Directions\FrontEnd\FrontEnd ;
class Core
{
    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     */
    protected  $loader ;
    /**
     * The unique identifier of this plugin.
     */
    protected  $plugin_name ;
    /**
     * The current version of the plugin.
     */
    protected  $version ;
    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     * @access   public
     * @param \Freemius $freemius Object for freemius.
     */
    public function __construct( $freemius )
    {
        $this->plugin_name = 'get-directions';
        $this->version = GET_DIRECTIONS_PLUGIN_VERSION;
        $this->freemius = $freemius;
        $this->loader = new Loader();
        $this->set_locale();
        $this->settings_pages();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }
    
    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale()
    {
        $plugin_i18n = new i18n();
        $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
    }
    
    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function settings_pages()
    {
        $settings = new Settings( $this->get_plugin_name(), $this->get_version(), $this->freemius );
        $this->loader->add_action( 'cmb2_admin_init', $settings, 'register_settings' );
    }
    
    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @access    public
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }
    
    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @access    public
     * @return    string    The version number of the plugin.
     */
    public function get_version()
    {
        return $this->version;
    }
    
    /**
     * Responsible for defining all actions that occur in the admin area.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks()
    {
        $plugin_admin = new Admin( $this->get_plugin_name(), $this->get_version() );
        $this->loader->add_action( 'widgets_init', $plugin_admin, 'register_custom_widgets' );
        $this->freemius->add_filter(
            'connect_message_on_update',
            array( $plugin_admin, 'connect_message' ),
            10,
            6
        );
        $this->freemius->add_filter(
            'connect_message',
            array( $plugin_admin, 'connect_message' ),
            10,
            6
        );
    }
    
    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks()
    {
        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        $plugin_public = new FrontEnd( $this->get_plugin_name(), $this->get_version() );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
        $this->loader->add_action( 'wp', $plugin_public, 'add_shortcode' );
    }
    
    /**
     * Run the loader to execute all of the hooks with WordPress
     *
     * @since    1.0.0
     * @access   public
     */
    public function run()
    {
        $this->loader->run();
    }
    
    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since    1.0.0
     * @access   public
     */
    public function get_loader()
    {
        return $this->loader;
    }

}