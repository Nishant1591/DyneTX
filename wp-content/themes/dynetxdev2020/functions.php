<?php
$show_admin_bar = false;
remove_filter( 'the_content', 'wpautop' );

add_action('add_meta_boxes', 'invivy_addPageMetabox');
add_action('save_post', 'invivy_savePageMetabox');

/**
 * Adds new metabox for PAGE post
 */
function invivy_addPageMetabox() {
    add_meta_box( 'page_options', 'Additional Options', 'invivy_showMetabox', 'page', 'normal', 'low' );
}


/**
 * Shows metabox content for PAGE post
 * @param $post
 */
function invivy_showMetabox($post) {
    wp_nonce_field('theme_page_optios', '_theme_nonce', false);
    ?>
    <label><b>Additional Body Class</b></label><br>
    <input type="text" name="additional_body_class" value="<?php echo esc_attr(get_post_meta($post->ID, 'additional_body_class', true)); ?>" style="width: 100%">
    <br><br>
    <?php
}

/**
 * Saves metabox content for PAGE post
 * @param $post_id
 */
function invivy_savePageMetabox( $post_id ) {
    // Save logic goes here. Don't forget to include nonce checks!
    $nonce = isset($_POST['_theme_nonce'])?$_POST['_theme_nonce']:'';

    if (wp_verify_nonce($nonce, 'theme_page_optios')) {
        update_post_meta($post_id, 'additional_body_class', isset($_POST['additional_body_class'])?stripslashes($_POST['additional_body_class']):'');
    }
}

if (function_exists('add_theme_support')) {
	add_theme_support('menus');
	}
	
register_nav_menus( array(
	'top_menu' => 'Main Menu',
	'footer_menu' => 'Bottom menu'
) );

add_theme_support( 'post-thumbnails' );
add_theme_support( 'title-tag' );


add_action( 'wp_enqueue_scripts', 'invivy_add_theme_scripts' );
function invivy_add_theme_scripts() {
	wp_enqueue_script('jquery');
//	wp_enqueue_script('popper-min-js', get_template_directory_uri ().'/js/popper.min.js', ['jquery'], '', true);
//	wp_enqueue_script('bootstrap-min-js', get_template_directory_uri ().'/js/bootstrap.min.js', ['jquery'], '', true);
	wp_enqueue_script('mdb-min-js', get_template_directory_uri ().'/js/mdb.min.js', ['jquery'], '', true);
	wp_enqueue_script('multi-carousel-min-js', get_template_directory_uri ().'/js/multi-carousel.min.js', ['jquery'], '', true);
	wp_enqueue_script('custom-js', get_template_directory_uri ().'/js/custom.js', ['jquery'], time(), true);


//	wp_enqueue_style( 'bootstrap-min-css', get_template_directory_uri() . '/css/bootstrap.min.css', false, '', 'all' );
	wp_enqueue_style( 'mdb-min-css', get_template_directory_uri() . '/css/mdb.min.css', false, '', 'all' );
	wp_enqueue_style( 'multi-carousel-min-css', get_template_directory_uri() . '/css/multi-carousel.min.css', false, '', 'all' );
	wp_enqueue_style( 'styles-css', get_template_directory_uri() . '/css/styles.css', false, time(), 'all' );
	wp_enqueue_style( 'community-popup-css', get_template_directory_uri() . '/css/community-popup.css', false, '', 'all' );
	wp_enqueue_style( 'styles-css', get_template_directory_uri() . '/css/custom.css', false, time(), 'all' );
}


add_action('wp_head', 'locanabio_jqueryNoConflict', 99);
//add_action ( 'after_setup_theme', 'locanabio_themeSupport' );


function locanabio_jqueryNoConflict() {
	?>
	<script>$ = jQuery.noConflict();</script>
	<?php
	}
  
if (class_exists('MultiPostThumbnails')) {
  $types = array('post', 'page', 'my_post_type');
  foreach($types as $type) {
    new MultiPostThumbnails(array(
      'label' => 'Image for Mobile',
      'id' => 'secondary-image',
      'post_type' => $type
      )
    );
    new MultiPostThumbnails(array(
      'label' => 'Hero Overlay Image',
      'id' => 'hero-overlay-image',
      'post_type' => $type
      )
    );
  }
}

// Detection Mobile Devices by User Agent, maybe we need to use it later... :(

function check_mobile_device() {
    $mobile_agent_array = array('ipad', 'iphone', 'android', 'pocket', 'palm', 'windows ce', 'windowsce', 'cellphone', 'opera mobi', 'ipod', 'small', 'sharp', 'sonyericsson', 'symbian', 'opera mini', 'nokia', 'htc_', 'samsung', 'motorola', 'smartphone', 'blackberry', 'playstation portable', 'tablet browser');
    $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
    foreach ($mobile_agent_array as $value) {    
        if (strpos($agent, $value) !== false) return true;   
    }       
    return false; 
}

function DyneIsMenuItemActive($menu_object) {
  global $post;

  if (isset($post->ID)) {
    if ($menu_object->object != 'custom') {
      return $post->ID == $menu_object->object_id;
    } else {
      $post_id = url_to_postid($menu_object->url);
      return $post->ID == $post_id;
    }
  } else {
    return false;
  }
}

add_action( 'wp_head', function() {

    $host = $_SERVER['HTTP_HOST'];

    if (strpos($host, 'wpenginepowered.com') !== false) {
        $environment = 'development';
    } else {
        $environment = 'production';
    }

    // OLD
    // Production
    /*if ( $environment === 'production' ) {
        echo "\n<!-- Production script -->\n";
        echo '<script src="https://privacyportal.onetrust.com/consent-embedded-forms/consent-receipt-scripts/OtFormStub.js" data-id="5c7d29d6-5208-4f7a-b1b5-b9aae9d54670" worker="https://consent-api.onetrust.com"></script>
          <script>
            OneTrust.webform.InstallCP("", function() {
            // Callback function code goes here
            // This function will be called after the collection point is installed
          });</script>' . "\n";

    // Staging & Development
    } else {
        echo "\n<!-- Staging/Development script -->\n";
        echo '<script src="https://privacyportal.onetrust.com/consent-embedded-forms/consent-receipt-scripts/OtFormStub.js" data-id="5c7d29d6-5208-4f7a-b1b5-b9aae9d54670-test" worker="https://consent-api.onetrust.com"></script>
          <script>
            OneTrust.webform.InstallCP("ot-d37d89f4-02ef-4834-8033-678cd54cd22a", function() {
            // Callback function code goes here
            // This function will be called after the collection point is installed
          });</script>' . "\n";
    }*/

    // NEW
    // Production
    if ( $environment === 'production' ) {
        echo "\n<!-- Production script -->\n";
        echo '<!--script src="https://privacyportal.onetrust.com/consent-embedded-forms/consent-receipt-scripts/OtFormStub.js" data-id="5c7d29d6-5208-4f7a-b1b5-b9aae9d54670" worker="https://consent-api.onetrust.com"></script-->
        <script>
            window.addEventListener("load", function () {
              setTimeout(function () {
                OneTrust.webform.InstallCP(
                  "ot-f1366d23-e54d-40cb-b359-36f96ad4bc9d",
                  function () {
                    console.log("OneTrust form loaded");
                  }
                );
              }, 1000);
            });
        </script>
          
          ' . "\n";

        // Staging & Development
    } else {
        echo "\n<!-- Staging/Development script -->\n";
        echo '<!--script src="https://privacyportal.onetrust.com/consent-embedded-forms/consent-receipt-scripts/OtFormStub.js" data-id="5c7d29d6-5208-4f7a-b1b5-b9aae9d54670-test" worker="https://consent-api.onetrust.com"></script-->
         <script>
            window.addEventListener("load", function () {
              setTimeout(function () {
                OneTrust.webform.InstallCP(
                  "ot-f1366d23-e54d-40cb-b359-36f96ad4bc9d",
                  function () {
                    console.log("OneTrust form loaded");
                  }
                );
              }, 1000);
            });
        </script>' . "\n";
    }

}, 20);

?>