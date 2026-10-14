<?php

/**
 * Widget logic class, includes form excludes front end display
 */
namespace Get_Directions\Includes;

use  Get_Directions\FrontEnd\FrontEnd ;
use  WP_Widget ;
class Get_Directions_Widget extends WP_Widget
{
    public function __construct()
    {
        $widget_ops = array(
            'classname'                   => 'widget_get_directions',
            'description'                 => __( 'Get Directions displays a MapQuest map with option to get directions to your business.', 'get-directions' ),
            'customize_selective_refresh' => true,
        );
        $control_ops = array();
        /*
        		'width'  => 400,
        		'height' => 350
        	);
        */
        parent::__construct(
            'getdirections',
            __( 'MapQuest Map - Get Directions', 'get-directions' ),
            $widget_ops,
            $control_ops
        );
        $this->alt_option_name = 'widget_get_directions';
    }
    
    /**
     * Outputs the content for the current widget instance.
     *
     */
    public function widget( $args, $instance )
    {
        /** @var \Freemius $get_directions_fs Freemius global object. */
        global  $get_directions_fs ;
        // Merge the input arguments and the defaults.
        $instance = wp_parse_args( (array) $instance, $this->default_args() );
        $mapscript = FrontEnd::build_mapquest_script( $args['widget_id'] . '-map', $instance );
        add_action( 'wp_footer', function () use( $mapscript ) {
            echo  $mapscript ;
        }, 100001 );
        $template_loader = new Template_Loader();
        $template_loader->set_template_data( array(
            'id'   => $args['widget_id'] . '-map',
            'args' => $instance,
        ) );
        ob_start();
        $template_loader->get_template_part( 'widget' );
        $html = ob_get_clean();
        // Output the theme's $before_widget wrapper.
        echo  $args['before_widget'] ;
        if ( !empty($instance['title']) ) {
            echo  $args['before_title'] . apply_filters(
                'widget_title',
                $instance['title'],
                $instance,
                $this->id_base
            ) . $args['after_title'] ;
        }
        echo  $html ;
        // Close the theme's widget wrapper.
        echo  $args['after_widget'] ;
    }
    
    public static function default_args()
    {
        /** @var \Freemius $get_directions_fs Freemius global object. */
        global  $get_directions_fs ;
        $defaults = array(
            'title'             => '',
            'destname'          => '',
            'radius'            => 0,
            'latlng'            => '51.23,1.12',
            'showroute'         => true,
            'height'            => 350,
            'controls'          => false,
            'zoom'              => 12,
            'usepost'           => false,
            'tile'              => 'map',
            'enabledestination' => false,
            'hidepin'           => false,
            'locale'            => 'en_US',
            'unit'              => 'm',
        );
        // Allow plugins/themes developer to filter the default arguments.
        return apply_filters( 'get-directions_widget_default_args', $defaults );
    }
    
    /**
     * Handles updating the settings for the current  widget instance.
     *
     */
    public function update( $new_instance, $old_instance )
    {
        /** @var \Freemius $get_directions_fs Freemius global object. */
        global  $get_directions_fs ;
        $instance = $old_instance;
        $instance['title'] = sanitize_text_field( $new_instance['title'] );
        $instance['latlng'] = sanitize_text_field( $new_instance['latlng'] );
        $instance['showroute'] = ( isset( $new_instance['showroute'] ) ? (bool) $new_instance['showroute'] : false );
        $instance['height'] = intval( $new_instance['height'] );
        $instance['controls'] = ( isset( $new_instance['controls'] ) ? (bool) $new_instance['showroute'] : false );
        $instance['zoom'] = intval( $new_instance['zoom'] );
        $instance['locale'] = sanitize_text_field( $new_instance['locale'] );
        $instance['unit'] = sanitize_text_field( $new_instance['unit'] );
        return $instance;
    }
    
    /**
     * Outputs the settings form for the widget.
     *
     */
    public function form( $instance )
    {
        /** @var \Freemius $get_directions_fs Freemius global object. */
        global  $get_directions_fs ;
        // Merge the user-selected arguments with the defaults.
        $instance = wp_parse_args( (array) $instance, self::default_args() );
        // Extract the array to allow easy use of variables.
        extract( $instance );
        
        if ( $get_directions_fs->is_trial() ) {
            ?>
            <div class="notice inline notice-info notice-alt"><p>
					<?php 
            printf( __( 'You are in the Free trial - <a href="%1$s">Upgrade Now!</a> to keep benefits', 'get-directions' ), $get_directions_fs->get_upgrade_url() );
            ?>
                </p>
            </div>
		<?php 
        } elseif ( $get_directions_fs->is_free_plan() ) {
            ?>
            <div class="notice inline notice-info notice-alt"><p>
					<?php 
            printf( __( 'Try Pro. <a href="%1$s">FREE trial 7 days.</a>', 'get-directions' ), $get_directions_fs->get_trial_url() );
            ?>
                </p>
            </div>
		<?php 
        }
        
        ?>

        <p>
            <label for="<?php 
        echo  $this->get_field_id( 'title' ) ;
        ?>">
				<?php 
        _e( 'Title', 'get-directions' );
        ?>
            </label>
            <input class="widefat" id="<?php 
        echo  $this->get_field_id( 'title' ) ;
        ?>"
                   name="<?php 
        echo  $this->get_field_name( 'title' ) ;
        ?>" type="text"
                   value="<?php 
        echo  esc_attr( $instance['title'] ) ;
        ?>"/>
        </p>
		<?php 
        ?>

        <p>
            <label for="<?php 
        echo  $this->get_field_id( 'latlng' ) ;
        ?>">
				<?php 
        _e( 'Lat/Long e.g. 51.23,1.12', 'get-directions' );
        ?>
            </label>
            <input class="widefat" id="<?php 
        echo  $this->get_field_id( 'latlng' ) ;
        ?>"
                   name="<?php 
        echo  $this->get_field_name( 'latlng' ) ;
        ?>" type="text"
                   value="<?php 
        echo  esc_attr( $instance['latlng'] ) ;
        ?>"/>
        </p>

        <p>
            <input class="checkbox" id="<?php 
        echo  $this->get_field_id( 'showroute' ) ;
        ?>"
                   name="<?php 
        echo  $this->get_field_name( 'showroute' ) ;
        ?>" type="checkbox"
                   value="1" <?php 
        checked( $instance['showroute'] );
        ?>
                   value="<?php 
        echo  esc_url( $instance['showroute'] ) ;
        ?>"/>
            <label for="<?php 
        echo  $this->get_field_id( 'showroute' ) ;
        ?>">
				<?php 
        _e( 'Show directions on map', 'get-directions' );
        ?>
            </label>
        </p>

        <p>
            <label for="<?php 
        echo  $this->get_field_id( 'height' ) ;
        ?>">
				<?php 
        _e( 'Map height in px:', 'get-directions' );
        ?>
            </label>
            <input class="small-text" id="<?php 
        echo  $this->get_field_id( 'height' ) ;
        ?>"
                   name="<?php 
        echo  $this->get_field_name( 'height' ) ;
        ?>" type="number" step="1" min="150"
                   max="900"
                   value="<?php 
        echo  esc_attr( $instance['height'] ) ;
        ?>"/>
        </p>

        <p>
            <input class="checkbox" id="<?php 
        echo  $this->get_field_id( 'controls' ) ;
        ?>"
                   name="<?php 
        echo  $this->get_field_name( 'controls' ) ;
        ?>" type="checkbox"
                   value="1" <?php 
        checked( $instance['controls'] );
        ?>
                   value="<?php 
        echo  esc_url( $instance['controls'] ) ;
        ?>"/>
            <label for="<?php 
        echo  $this->get_field_id( 'controls' ) ;
        ?>">
				<?php 
        _e( 'Show large map controls', 'get-directions' );
        ?>
            </label>
        </p>

        <p>
            <label for="<?php 
        echo  $this->get_field_id( 'zoom' ) ;
        ?>">
				<?php 
        _e( 'Initial map zoom 1-16:', 'get-directions' );
        ?>
            </label>
            <input class="tiny-text" id="<?php 
        echo  $this->get_field_id( 'zoom' ) ;
        ?>"
                   name="<?php 
        echo  $this->get_field_name( 'zoom' ) ;
        ?>" type="number" step="1" min="1" max="16"
                   value="<?php 
        echo  esc_attr( $instance['zoom'] ) ;
        ?>"/>
        </p>
        <p>
            <label for="<?php 
        echo  $this->get_field_id( 'locale' ) ;
        ?>">
				<?php 
        _e( 'Language for instructions e.g. en_US:', 'get-directions' );
        ?>
            </label>
            <input class="small" id="<?php 
        echo  $this->get_field_id( 'locale' ) ;
        ?>"
                   name="<?php 
        echo  $this->get_field_name( 'locale' ) ;
        ?>" type="text"
                   value="<?php 
        echo  esc_attr( $instance['locale'] ) ;
        ?>"/>
        </p>
        <p>
            <input class="checkbox" id="<?php 
        echo  $this->get_field_id( 'unit' ) ;
        ?>"
                   name="<?php 
        echo  $this->get_field_name( 'unit' ) ;
        ?>" type="radio"
                   value="m" <?php 
        checked( $instance['unit'], 'm' );
        ?>
                   value="<?php 
        echo  esc_url( $instance['unit'] ) ;
        ?>"/>
            <label for="<?php 
        echo  $this->get_field_id( 'unit' ) ;
        ?>">
				<?php 
        _e( 'Miles', 'get-directions' );
        ?>
            </label>
            <input class="checkbox" id="<?php 
        echo  $this->get_field_id( 'unit' ) ;
        ?>"
                   name="<?php 
        echo  $this->get_field_name( 'unit' ) ;
        ?>" type="radio"
                   value="k" <?php 
        checked( $instance['unit'], 'k' );
        ?>
                   value="<?php 
        echo  esc_url( $instance['unit'] ) ;
        ?>"/>
            <label for="<?php 
        echo  $this->get_field_id( 'unit' ) ;
        ?>">
		        <?php 
        _e( 'Kilometres', 'get-directions' );
        ?>
            </label>
        </p>


		<?php 
    }

}