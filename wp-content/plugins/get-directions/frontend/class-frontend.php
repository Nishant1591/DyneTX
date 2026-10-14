<?php

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, hooks & filters
 *
 */
namespace Get_Directions\FrontEnd;

use  Get_Directions\Includes\Template_Loader ;
use  DeviceDetector\Parser\Bot as BotParser ;
use  WP_Query ;
class FrontEnd
{
    /**
     * The ID of this plugin.
     *
     */
    private  $plugin_name ;
    /**
     * The version of this plugin.
     *
     */
    private  $version ;
    /**
     * Initialize the class and set its properties.
     *
     */
    public function __construct( $plugin_name, $version )
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }
    
    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     */
    public function enqueue_styles()
    {
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url( __FILE__ ) . 'css/frontend.css',
            array(),
            $this->version,
            'all'
        );
        // https://api.mqcdn.com/sdk/mapquest-js/v1.2.0/mapquest.css
        wp_enqueue_style(
            'mapquest-css',
            plugin_dir_url( __FILE__ ) . 'css/mapquest.css',
            array(),
            '1.3.2',
            'all'
        );
    }
    
    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     */
    public function enqueue_scripts()
    {
        //	https://api.mqcdn.com/sdk/mapquest-js/v1.2.0/mapquest.js
        wp_enqueue_script(
            'mapquest-js',
            plugin_dir_url( __FILE__ ) . 'js/mapquest.js',
            array(),
            '1.3.2',
            false
        );
        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url( __FILE__ ) . 'js/frontend.js',
            array( 'jquery', 'mapquest-js' ),
            $this->version,
            false
        );
        // build top and bottom of generated script
        add_action( 'wp_footer', function () {
            $options = get_option( 'get-directions-settings' );
            ?>
        <script type="text/javascript">
            window.onload = function () {
                L.mapquest.key = '<?php 
            echo  esc_html( $options['consumer_key'] ) ;
            ?>';
				<?php 
        }, 100000 );
        add_action( 'wp_footer', function () {
            $options = get_option( 'get-directions-settings' );
            ?>
            }
        </script>
		<?php 
        }, 100002 );
    }
    
    public function add_shortcode()
    {
        add_shortcode( 'get-directions', array( $this, 'build_shortcode' ) );
    }
    
    /**
     * @param $atts
     *
     * @return string
     */
    public function build_shortcode( $atts )
    {
        /** @var \Freemius $get_directions_fs Freemius global object. */
        global  $get_directions_fs ;
        $atts = shortcode_atts( self::default_args(), $atts, 'get-directions' );
        
        if ( isset( $atts['latlong'] ) && !empty($atts['latlong']) ) {
            $atts['latlng'] = sanitize_text_field( $atts['latlong'] );
        } else {
            $atts['latlng'] = sanitize_text_field( $atts['latlng'] );
        }
        
        $atts['showroute'] = $this->shortcode_bool( $atts['showroute'] );
        $atts['hidepin'] = $this->shortcode_bool( $atts['hidepin'] );
        $atts['height'] = preg_replace( '/\\s+/', '', $atts['height'] );
        $atts['width'] = preg_replace( '/\\s+/', '', $atts['width'] );
        $atts['unit'] = sanitize_text_field( $atts['unit'] );
        
        if ( in_array( $atts['unit'], array(
            'k',
            'km',
            'K',
            'KM'
        ) ) ) {
            $atts['unit'] = 'k';
        } else {
            $atts['unit'] = 'm';
        }
        
        $atts['locale'] = sanitize_text_field( $atts['locale'] );
        $atts['zoom'] = (int) $atts['zoom'];
        $atts['controls'] = $this->shortcode_bool( $atts['controls'] );
        // push script into footer
        $mapscript = $this->build_mapquest_script( $atts['id'], $atts );
        add_action( 'wp_footer', function () use( $mapscript ) {
            echo  $mapscript ;
        }, 100001 );
        $template_loader = new Template_Loader();
        $template_loader->set_template_data( array(
            'id'   => $atts['id'],
            'args' => $atts,
        ) );
        ob_start();
        $template_loader->get_template_part( 'shortcode_layout_' . $atts['layout'] );
        $html = ob_get_clean();
        return $html;
    }
    
    public static function default_args()
    {
        /** @var \Freemius $get_directions_fs Freemius global object. */
        global  $get_directions_fs ;
        $defaults = array(
            'destname'          => '',
            'latlng'            => '51.507879, -0.087732',
            'showroute'         => 'true',
            'height'            => '500px',
            'width'             => '100%',
            'zoom'              => '12',
            'controls'          => 'true',
            'layout'            => '1',
            'id'                => 'map-shortcode' . get_the_ID(),
            'radius'            => '',
            'usepost'           => 'false',
            'posttype'          => '',
            'taxonomy'          => '',
            'terms'             => '',
            'colors'            => '',
            'tile'              => 'map',
            'enabledestination' => 'false',
            'hidepin'           => 'false',
            'unit'              => 'm',
            'locale'            => 'en_US',
        );
        // Allow plugins/themes developer to filter the default arguments.
        return apply_filters( 'get-directions_shortcode_default_args', $defaults );
    }
    
    private function shortcode_bool( $att )
    {
        
        if ( 'true' === $att ) {
            $att = true;
        } else {
            $att = false;
        }
        
        return (bool) $att;
    }
    
    public static function build_mapquest_script( $id, $atts )
    {
        /** @var \Freemius $get_directions_fs Freemius global object. */
        global  $get_directions_fs ;
        $pointers = array();
        $pointers[0]['latlng'] = esc_html( $atts['latlng'] );
        $centre = esc_html( $atts['latlng'] );
        $pointers[0]['destname'] = $atts['destname'];
        $jsid = str_replace( "-", "_", $id );
        ob_start();
        ?>
        try {
        var <?php 
        echo  $jsid ;
        ?> =L.mapquest.map('<?php 
        echo  $id ;
        ?>', {
        center: [<?php 
        echo  $centre ;
        ?>],
        layers: L.mapquest.tileLayer('<?php 
        echo  $atts['tile'] ;
        ?>'),
        scrollWheelZoom: false,
        zoom: <?php 
        echo  esc_html( $atts['zoom'] ) ;
        ?>
        });
		<?php 
        if ( !$atts['hidepin'] ) {
            foreach ( $pointers as $pointer ) {
                ?>
                L.marker([<?php 
                echo  $pointer['latlng'] ;
                ?>], {
                icon:L.mapquest.icons.marker(<?php 
                if ( !empty($pointer['pointeratts']) ) {
                    echo  $pointer['pointeratts'] ;
                }
                ?>),
                draggable: false
                })<?php 
                echo  ( empty($pointer['destname']) ? '.' : ".bindPopup('" . $pointer['destname'] . "')." ) ;
                ?>addTo(<?php 
                echo  $jsid ;
                ?>);

				<?php 
            }
        }
        ?>

		<?php 
        if ( !(isset( $atts['posttype'] ) && !empty($atts['posttype'])) ) {
            // does not show route or radius if using posts as multi pointer logic @TDO think about multi route logic
            
            if ( true === $atts['showroute'] ) {
                ?>
                L.mapquest.directionsControl({
                routeSummary: {
                enabled: false
                },
                endInput: {
                disabled: <?php 
                echo  ( true === $atts['enabledestination'] ? 'false' : 'true' ) ;
                ?>,
                geolocation: false,
                <?php 
                
                if ( !$atts['hidepin'] || !$atts['enabledestination'] ) {
                    ?>
                location: [<?php 
                    echo  $pointers[0]['latlng'] ;
                    ?>],
                placeholderText: '<?php 
                    echo  esc_html__( 'To: ', 'get-directions' ) . wp_strip_all_tags( ( empty($pointers[0]['destname']) ? $pointers[0]['latlng'] : $pointers[0]['destname'] ), true ) ;
                    ?>',
                <?php 
                }
                
                ?>
                    },
                directions: {
                  options: {
                    locale: '<?php 
                echo  $atts['locale'] ;
                ?>',
                    unit:   '<?php 
                echo  $atts['unit'] ;
                ?>'
                   }
                },
                narrativeControl: {
                enabled: true,
                compactResults: false
                }
                }).addTo(<?php 
                echo  $jsid ;
                ?>);
				<?php 
            }
        
        }
        
        if ( true === $atts['controls'] ) {
            ?>
			<?php 
            echo  $jsid ;
            ?>.addControl(L.mapquest.control());

			<?php 
        }
        
        ?>
        } catch(error) {
        alert( '<?php 
        echo  esc_html__( 'Error generated by MapQuest ( via get-directions plugin ) report this to the site owner to resolve:\\n\\n ', 'get-directions' ) ;
        ?>' + error.toString() );
        }
		<?php 
        $script = ob_get_clean();
        return $script;
    }
    
    static function get_pointers( $pointers, $args, $pointeratts )
    {
        $pointer_query = new WP_Query( $args );
        
        if ( $pointer_query->have_posts() ) {
            while ( $pointer_query->have_posts() ) {
                $pointer_query->the_post();
                $popup = preg_replace( '/(\\v|\\s)+/', ' ', get_post_meta( get_the_ID(), '_get_directions_txt', true ) );
                $pointers[] = array(
                    'destname'    => '<a href="' . get_permalink() . '" title="' . the_title_attribute( array(
                    'echo' => false,
                ) ) . '">' . get_the_title() . '</a>' . '<br>' . $popup,
                    'pointeratts' => json_encode( $pointeratts ),
                    'latlng'      => get_post_meta( get_the_ID(), '_get_directions_lat', true ) . ',' . get_post_meta( get_the_ID(), '_get_directions_lng', true ),
                );
            }
            wp_reset_postdata();
        }
        
        return $pointers;
    }

}