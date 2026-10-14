<?php

namespace Get_Directions\Admin;

use  CMB2_Tabs ;
/**
 * Class Settings
 * @package Get_Directions\Admin
 */
class Settings
{
    private  $options_key = "get-directions-settings" ;
    /**
     * Settings constructor.
     *
     * @param string $plugin_name
     * @param string $version plugin version.
     * @param \Freemius $freemius Freemius SDK.
     */
    public function __construct( $plugin_name, $version, $freemius )
    {
        // Init CMB2 ( consider if needed elsewhere )
        require_once GET_DIRECTIONS_PLUGIN_DIR . 'includes/vendor/cmb2/init.php';
        //		require_once GET_DIRECTIONS_PLUGIN_DIR . 'includes/vendor/cmb2-extensions/init.php';
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->freemius = $freemius;
    }
    
    public function register_settings()
    {
        /**
         * Registers options page menu item and form.
         */
        $options = array(
            'id'           => 'get-directions_option_metabox',
            'title'        => esc_html__( 'MapQuest - Get Directions', 'get-directions' ),
            'tabs'         => array(
            'info' => array(
            'label' => __( 'info', 'get-directions' ),
            'icon'  => 'dashicons-info',
        ),
            'key'  => array(
            'label' => __( 'API Keys', 'get-directions' ),
            'icon'  => 'dashicons-location-alt',
        ),
        ),
            'object_types' => array( 'options-page' ),
            'option_key'   => $this->options_key,
            'parent_slug'  => 'options-general.php',
        );
        $cmb_options = new_cmb2_box( $options );
        /*
         * Options fields ids only need
         * to be unique within this box.
         * Prefix is not needed.
         */
        $infomsg = '<p>' . sprintf( __( 'Welcome. To use this plugin add the widget or shortcode to your website <br>
            You will also need to obtain and store MapQuest API Key, see the tab for that. <br><br>
            For more detailed setup and usage instructions visit <a class="button-secondary" target="_blank" href="https://fullworks.net/technical-documentation/get-directions-usage-instructions" >this page.</a><br>
            Support for the <strong>free</strong> version is provided <a class="button-secondary" href="https://wordpress.org/support/plugin/get-directions" target="_blank">here on WordPress.org.</a><br>
			<br>
			Get a FREE trial of the Pro version - <a href="%1$s">click here</a> 
			<ul style="list-style-type:disc;">
				<li>7 day free trial</li>
				<li>Multi map pins, by post or custom post,easily build a geo directory</li>
				<li>Different map skins</li>
				<li>Direct support</li>
			</ul>
			', 'get-directions' ), $this->freemius->get_trial_url() );
        $cmb_options->add_field( array(
            'before'      => '<h2>' . __( 'Information', 'get-directions' ) . '</h2>',
            'after_field' => $infomsg,
            'id'          => 'info_text',
            'type'        => 'title',
            'tab'         => 'info',
        ) );
        $infomsg = '<p>' . sprintf( esc_html__( 'Sign up to a MapQuest Plan using this %1$slink%2$s', 'get-directions' ), '<a href="https://developer.mapquest.com/plan_purchase/steps/business_edition/business_edition_free/register" target="_blank">', '</a>' ) . '</p>' . sprintf( esc_html__( 'Then login to MapQuest Developers and create an API key using this %1$slink%2$s', 'get-directions' ), '<a href="https://developer.mapquest.com/user/me/apps" target="_blank">', '</a>' ) . '</p>';
        $cmb_options->add_field( array(
            'before'      => '<h2>' . __( 'MapQuest API Keys', 'get-directions' ) . '</h2>',
            'after_field' => $infomsg,
            'id'          => 'key_text',
            'type'        => 'title',
            'tab'         => 'key',
        ) );
        $cmb_options->add_field( array(
            'name'       => esc_html__( 'Consumer Key', 'get-directions' ),
            'desc'       => esc_html__( 'Copy and paste your consumer key here', 'get-directions' ),
            'id'         => 'consumer_key',
            'type'       => 'text',
            'attributes' => array(
            'type' => 'password',
        ),
            'tab'        => 'key',
        ) );
    }

}