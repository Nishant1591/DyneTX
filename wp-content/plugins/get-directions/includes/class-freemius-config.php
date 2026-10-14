<?php

/**
 * Class to load freemius configuration
 */
namespace Get_Directions\Includes;

class Freemius_Config
{
    public function init()
    {
        global  $get_directions_fs ;
        
        if ( !isset( $get_directions_fs ) ) {
            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/vendor/freemius/wordpress-sdk/start.php';
            $get_directions_fs = fs_dynamic_init( array(
                'id'             => '1430',
                'slug'           => 'get-directions',
                'type'           => 'plugin',
                'public_key'     => 'pk_ee6a9b7a50f0a82f86f1b77417af7',
                'is_premium'     => false,
                'has_addons'     => false,
                'has_paid_plans' => true,
                'trial'          => array(
                'days'               => 7,
                'is_require_payment' => false,
            ),
                'navigation'     => 'tabs',
                'menu'           => array(
                'slug'    => 'get-directions-settings',
                'support' => false,
                'contact' => false,
                'parent'  => array(
                'slug' => 'options-general.php',
            ),
            ),
                'is_live'        => true,
            ) );
        }
        
        $get_directions_fs->add_filter(
            'is_submenu_visible',
            array( $this, '_fs_show_contact_menu' ),
            10,
            2
        );
        return $get_directions_fs;
    }
    
    public function _fs_show_contact_menu( $is_visible, $menu_id )
    {
        /** @var \Freemius $wfea_fs Freemius global object. */
        global  $get_directions_fs ;
        if ( 'contact' === $menu_id ) {
            return $get_directions_fs->has_any_license();
        }
        if ( 'support' === $menu_id ) {
            return $get_directions_fs->is_free_plan();
        }
        return $is_visible;
    }

}