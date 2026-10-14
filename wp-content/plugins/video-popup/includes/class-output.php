<?php

if ( !defined('ABSPATH') ) {
    exit;
}

/**
 * Handles all frontend output functionality including CSS/JS enqueuing,
 * AJAX processing for on-page-load popups, and conditional display logic.
 * Implements sophisticated targeting rules for displaying popups based on
 * page location, user types, and specific page/post IDs.
 * Uses singleton pattern to ensure single instance throughout the application.
 * 
 * @author   Alobaidi
 * @since    2.0.0
 */

class Video_Popup_Output {

    private static $instance = null;
    
    /**
     * Retrieves the singleton instance of this class
     * Creates a new instance if one doesn't exist yet
     * 
     * @return Video_Popup_Output The single instance of this class
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * constructor - intentionally empty
     * Initialization happens in the run() method instead
     */
    private function __construct() {
        // No initialization here.
    }

    /**
     * Helper to get plugin constants from core class
     * Normalizes the key by trimming and converting to lowercase
     * 
     * @param string $key The constant key to retrieve
     * @return mixed|null The constant value or null if not found
     */
    private function get_const($key){
        $key = strtolower(trim($key));
        return Video_Popup_Core::plugin_consts($key) ?? null;
    }
    
    /**
     * Run the assets functionality by registering hooks
     * Sets up frontend assets and AJAX handlers
     */
    public function run() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('wp_ajax_video_popup_onpage_load', array($this, 'handle_onpage_load_ajax'));
        add_action('wp_ajax_nopriv_video_popup_onpage_load', array($this, 'handle_onpage_load_ajax'));
    }

    /**
     * Get video popup options from database
     * 
     * @param bool $onpage_load_options Whether to get onpage load options
     * @return array|false Returns options array if exists, false otherwise
     */
    public function get_video_popup_options($onpage_load_options = false){
        $key_suffix = $onpage_load_options === true ? '_onpage_load' : '';
        $options = $this->get_const('settings_key').$key_suffix ? get_option($this->get_const('settings_key').$key_suffix, array()) : false;
        $has_options = is_array($options) && !empty($options) ? true : false;

        if ( $has_options === true ) {
            return $options;
        }

        return false;
    }

    /**
     * Check if on-page-load functionality is enabled
     * 
     * @return bool True if enabled, false otherwise
     */
    public function is_opl_enabled(){
        $options = $this->get_video_popup_options(true);

        $is_enabled = $options && isset($options['enable_onpage_load']) && $options['enable_onpage_load'] == 1 ? true : false;

        if ( $is_enabled ) {
            return true;
        }

        return false;
    }
    
    /**
     * Enqueue frontend CSS and JavaScript assets
     * Conditionally loads on-page-load scripts based on settings
     */
    public function enqueue_frontend_assets() {

        if ( $this->get_const('plugin_url') === null ) return;

        wp_enqueue_style(
            $this->get_const('plugin_id') . '_main_style',
            $this->get_const('plugin_url') . "assets/css/videoPopup.css",
            array(),
            $this->get_const('plugin_version')
        );
        
        wp_enqueue_script(
            $this->get_const('plugin_id') . '_main_modal',
            $this->get_const('plugin_url') . 'assets/js/videoPopup.js',
            array(),
            $this->get_const('plugin_version'),
            false
        );

        $options = $this->get_video_popup_options(false);

        $wrap_close = $options && isset($options['wrap_close']) && $options['wrap_close'] == 1 ? 'true' : 'false';
        $no_cookie = $options && isset($options['no_cookie']) && $options['no_cookie'] == 1 ? 'true' : 'false';
        $debug = is_user_logged_in() && $options && isset($options['enable_js_debug']) && $options['enable_js_debug'] == 1 ? '1' : '0';

        wp_localize_script($this->get_const('plugin_id') . '_main_modal', 'theVideoPopupGeneralOptions', array(
            "wrap_close" => $wrap_close,
            "no_cookie" => $no_cookie,
            "debug" => $debug
        ));


        if ( $this->is_opl_enabled() ) {
            $current_id = is_singular( array('post', 'page') ) ? get_the_ID() : 'none';

            $onpage_options = $this->get_video_popup_options(true);
            $onpage_debug = is_user_logged_in() && $onpage_options && isset($onpage_options['debug']) && $onpage_options['debug'] == 1 ? '1' : '0';

            wp_enqueue_script(
                $this->get_const('plugin_id') . '_onpage_load_ajax',
                $this->get_const('plugin_url') . 'assets/js/onpage-load-ajax.js',
                array(),
                $this->get_const('plugin_version'),
                true
            );

            wp_localize_script($this->get_const('plugin_id') . '_onpage_load_ajax', 'theVideoPopupOplVars', array(
                "ajax_url" => admin_url('admin-ajax.php'),
                "ajax_action" => 'video_popup_onpage_load',
                "current_id" => $current_id,
                "current_location" => $this->get_current_location(),
                "user_type" => is_user_logged_in() ? 'user' : 'visitor',
                "debug" => $onpage_debug
            ));
        }
    }

    /**
     * Get current page location type regardless of settings
     * Detects page type including WooCommerce and custom post types
     * 
     * @return string Location type identifier
     */
    public function get_current_location() {

        $is_woocommerce_page = function_exists('is_woocommerce') && ( is_product() || is_shop() || is_cart() || is_checkout() || is_account_page() || is_view_order_page() || is_product_category() || is_product_tag() || is_woocommerce() ) ? true : false;

        if ( $is_woocommerce_page ) {
            return 'woocommerce_page'; // Entire Site (excluding WooCommerce pages) option.
        }
        
        // Check standard WordPress pages
        if (is_home()) {
            return 'home';
        }
        
        if (is_front_page()) {
            return 'front';
        }
        
        if (is_singular('post')) {
            return 'posts';
        }
        
        if (is_singular('page') && !is_front_page() && !$is_woocommerce_page) {
            return 'pages';
        }
        
        if (is_category()) {
            return 'cats';
        }
        
        if (is_tag()) {
            return 'tags';
        }
        
        if (is_attachment()) {
            return 'attach';
        }
        
        if (is_404()) {
            return 'error';
        }
        
        if (is_search()) {
            return 'search';
        }
        
        return 'other';
    }

    /**
     * AJAX handler for on-page-load popup
     * Processes request and generates JavaScript for popup display
     */
    public function handle_onpage_load_ajax() {
        $options = $this->get_video_popup_options(true);

        // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
        // Reason: Safe static output used only as an internal AJAX response string, no user data or HTML is rendered.
        if ( !$options ) {
            ob_start();
            echo 'no_options';
            echo ob_get_clean();
            exit;
        }
        
        if ( !$this->is_opl_enabled() ) {
            ob_start();
            echo 'opl_disabled';
            echo ob_get_clean();
            exit;
        }
        

        if ( !isset($options['video_url']) || empty($options['video_url']) ) {
            ob_start();
            echo 'no_url';
            echo ob_get_clean();
            exit;
        }

        $debug = isset($options['debug']) && $options['debug'] == 1 && is_user_logged_in() ? true : false;
        $video_url = $options['video_url'];

        $utils = Video_Popup_Utils::get_instance();
        $video_url = $utils->validate_url($video_url);

        if ( !$video_url ) {
            ob_start();
            echo 'invalid_url';
            echo ob_get_clean();
            exit;
        }

        // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped

        // phpcs:disable WordPress.Security.NonceVerification.Missing
        // Reason: No nonce needed; this code only reads a the POST values does not modify or save any data.
        $is_youtube = $utils->is_youtube($video_url);
        $is_vimeo = $utils->is_vimeo($video_url);

        /* Display Locations */
        $display = false;

        // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        // Reason: Data comes from wp_localize_script() via AJAX and does not modify or save any data, so sanitization is not required.

        $id = isset($_POST['current_id']) ? intval($_POST['current_id']) : 'none';

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Reason: Data comes from wp_localize_script() via AJAX and does not pass through addslashes, so wp_unslash() is unnecessary.
        $location = isset($_POST['current_location']) && !empty(trim($_POST['current_location'])) ? sanitize_text_field($_POST['current_location']) : 'none';
        $location = strtolower($location);

        $allowed_locations = isset($options['display_locations']) && !empty($options['display_locations']) ? $options['display_locations'] : array();
        $is_entire = in_array('entire', $allowed_locations) ? true : false;
        $is_entire_ex_woo = in_array('entire_ex_woo', $allowed_locations) ? true : false;

        $display = $this->should_display_video($id, $location);

        if ( $is_entire || $is_entire_ex_woo ) {
            if ( $id == 'none' || $location == 'none' || $location == 'other' ) {
                $display = true;
            }
        }

        $allowed_locations_string = is_array($allowed_locations) && !empty($allowed_locations) ? implode(', ', $allowed_locations) : 'You have not selected any display location';

        $allowed_id = isset($options['allowed_post_id']) && !empty($options['allowed_post_id']) ? $options['allowed_post_id'] : 'You have not set any ID';

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Reason: Data comes from wp_localize_script() via AJAX and does not pass through addslashes, so wp_unslash() is unnecessary.
        $user_type = isset($_POST['user_type']) && !empty(trim($_POST['user_type'])) ? sanitize_text_field($_POST['user_type']) : 'none';
        $user_type = strtolower($user_type);
        $user_type = sanitize_text_field($user_type);
        $user_target = isset($options['user_target']) && !empty($options['user_target']) ? $options['user_target'] : 'all';
        $allowed_user = false;

        if ( $user_target == 'all' || $user_type == 'none' ) {
            $allowed_user = true;
        }
        if ($user_target == 'visitors' && $user_type == 'visitor') {
            $allowed_user = true;
        }
        if ($user_target == 'users' && $user_type == 'user') {
            $allowed_user = true;
        }
        
        // phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

        /* Appearance Settings */
        $wrap_close = isset($options['wrap_close']) && $options['wrap_close'] == 1 ? true : false;

        /* Video Settings */
        $autoplay = isset($options['autoplay']) && $options['autoplay'] == 1 ? true : false;
        $link_class = $autoplay ? 'vp-a' : 'vp-s';
        $mute = isset($options['mute']) && $options['mute'] == 1 ? true : false;
        $no_cookie = isset($options['no_cookie']) && $options['no_cookie'] == 1 && ($is_youtube || $is_vimeo) ? true : false;

        /* Display Settings */
        $cookie_expiry = isset($options['cookie_expiry']) && $options['cookie_expiry'] >= 1 ? $options['cookie_expiry'] : '';
        $cookie_id = isset($options['cookie_id']) && !empty($options['cookie_id']) ? $options['cookie_id'] : 'video_popup_onpage_load';
        $cookie_name = $cookie_id;
        $should_set_cookie = $cookie_expiry > 0 && $display && $allowed_user ? true : false;
        $unset_cookie = false;
        $opl_cookie = 'false';
        if ( $should_set_cookie ) {
            $days = $cookie_expiry;
            $opl_cookie = '{name:"'.$cookie_name.'", days:"'.$days.'"}';
        }

        if ( isset($_COOKIE[$cookie_name]) && $should_set_cookie ) {
            ob_start();
            if ( $debug ) {
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Reason: wp_unslash() is unnecessary in this case.
                $time_left = !empty($_COOKIE[$cookie_name]) ? sanitize_text_field($_COOKIE[$cookie_name]) - time() : false;
                $time_left = $time_left ? intval($time_left) : false;
                if ( $time_left > 0 ) {
                    $days_left  = floor($time_left / 86400);
                    $hours_left = floor(($time_left % 86400) / 3600);
                    $minutes_left = floor(($time_left % 3600) / 60);
                    $seconds_left = $time_left % 60;
                    $days_text = $days_left > 1 ? 'days' : 'day'; 
                    $time_left = "Cookie will expire in {$days_left} {$days_text}, {$hours_left} hours, {$minutes_left} minutes, {$seconds_left} seconds.";
                }else{
                    $time_left = false;
                }
                ?>
                    (function() {
                        console.log('onpage-load.js (on page load debug): The video popup was prevented on page load by cookie.');
                        console.log('onpage-load.js (on page load debug): You need to wait until the cookie expires, change the value of the "Cookie Expiry" field, or clear its value.');
                        <?php if ($time_left && $time_left > 0) : ?>
                        console.log("onpage-load.js (on page load debug): <?php echo esc_js($time_left); ?>");
                        <?php endif; ?>
                        console.log('onpage-load.js (on page load debug): Cookie Expiry: <?php echo $cookie_expiry ? esc_js($cookie_expiry) : 'not set'; ?>');
                        console.log('onpage-load.js (on page load debug): Cookie ID: <?php echo $cookie_id ? esc_js($cookie_id) : 'not set'; ?>');
                    })();
                <?php
                // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
                // Reason: Safe static output used only as an internal AJAX response string, no user data or HTML is rendered.
            }else{
                echo 'hide_by_cookie';
            }
            echo ob_get_clean();
            exit;
        }
        // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped

        if ( isset($_COOKIE[$cookie_name]) && !$cookie_expiry ) {
            $unset_cookie = true;
            setcookie($cookie_name, "", time() - 3600, "/");
            unset($_COOKIE[$cookie_name]);
        }

        // phpcs:enable WordPress.Security.NonceVerification.Missing

        // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
        // Reason: All output here is already sanitized and safe through the sanitize_settings() method in class-onpageload.php.
        ob_start();
        ?>
        (function() {
            const vpOnPageLoadVirtualLink = document.createElement('a');
            vpOnPageLoadVirtualLink.href = "<?php echo $video_url; ?>";
            vpOnPageLoadVirtualLink.classList.add("<?php echo $link_class; ?>", 'vp-modal-onpage_load_general');

            <?php if ( !$display ) : ?>
                <?php if ( $debug ) : ?>
                console.log('onpage-load.js (on page load debug): The video popup was prevented on page load because the display location did not match your selected locations (if any), or the post/page ID did not match (if specified).');
                console.log('onpage-load.js (on page load debug): Current location is "<?php echo $location; ?>".');
                console.log('onpage-load.js (on page load debug): Current ID is "<?php echo $id; ?>".');
                console.log('onpage-load.js (on page load debug): Your selected locations: <?php echo $allowed_locations_string; ?>');
                console.log('onpage-load.js (on page load debug): Allowed ID: <?php echo $allowed_id; ?>');
                <?php endif; ?>
                return;
            <?php endif; ?>

            <?php if ( !$allowed_user ) : ?>
                <?php if ( $debug ) : ?>
                console.log('onpage-load.js (on page load debug): The video popup was prevented on page load because the user type does not match the user targeting options.');
                console.log('onpage-load.js (on page load debug): User type: <?php echo $user_type; ?>');
                console.log('onpage-load.js (on page load debug): User targeting: <?php echo $user_target; ?>');
                <?php endif; ?>
                return;
            <?php endif; ?>

            <?php if ( $debug ) : ?>
            console.log('onpage-load.js (on page load debug): Video URL: <?php echo $video_url; ?>');
            console.log('onpage-load.js (on page load debug): User type: <?php echo $user_type; ?>');
            console.log('onpage-load.js (on page load debug): User targeting: <?php echo $user_target; ?>');
            console.log('onpage-load.js (on page load debug): Allowed user var: <?php echo $allowed_user ? 'true' : 'false'; ?>');
            console.log('onpage-load.js (on page load debug): Cookie Expiry: <?php echo $cookie_expiry ? $cookie_expiry : 'not set'; ?>');
            console.log('onpage-load.js (on page load debug): Cookie ID: <?php echo $cookie_id ? $cookie_id : 'not set'; ?>');
            console.log('onpage-load.js (on page load debug): Should set cookie var: <?php echo $should_set_cookie ? 'true' : 'false'; ?>');
            console.log('onpage-load.js (on page load debug): Your selected locations: <?php echo $allowed_locations_string; ?>');
            console.log('onpage-load.js (on page load debug): Allowed ID: <?php echo $allowed_id; ?>');
            console.log('onpage-load.js (on page load debug): Should display var: <?php echo $display ? 'true' : 'false'; ?>');
            console.log('onpage-load.js (on page load debug): Current location is "<?php echo $location; ?>".');
            console.log('onpage-load.js (on page load debug): Current ID is "<?php echo $id; ?>".');
            <?php endif; ?>

            <?php if( $mute ) : ?>
            vpOnPageLoadVirtualLink.setAttribute('data-mute', "1");
            <?php endif; ?>

            const vpOnPageLoadPopup = new VideoPopupOnPageLoad({
                autoplay: <?php echo $autoplay ? 'true' : 'false'; ?>,
                wrapClose: <?php echo $wrap_close ? 'true' : 'false'; ?>,
                noCookie: <?php echo $no_cookie ? 'true' : 'false'; ?>, 
                onPageLoad: true,
                oplCookie: <?php echo $should_set_cookie ? $opl_cookie : 'false'; ?>,
                debug: <?php echo $debug ? 'true' : 'false'; ?>,
                handleVars: true
            });
            vpOnPageLoadPopup.openPopup(vpOnPageLoadVirtualLink, <?php echo $autoplay ? 'true' : 'false'; ?>);
        })();
        <?php
        $html = ob_get_clean();
        echo $html;
        exit; // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * Check if popup should be displayed based on current context
     * Applies all filtering rules in proper sequence
     * 
     * @param string $id Current page/post ID
     * @param string $location Current page location type
     * @return bool Whether popup should be displayed
     */
    public function should_display_video($id = '', $location = '') {
        if ( $this->is_allowed_id($id) ) {
            return true;
        }
        
        $options = $this->get_video_popup_options(true);
        $locations = $options && isset($options['display_locations']) && !empty($options['display_locations']) ? $options['display_locations'] : array();
        
        $is_entire = in_array('entire', $locations) ? true : false;
        if ($is_entire) {
            return true;
        }
        
        $is_entire_ex_woo = in_array('entire_ex_woo', $locations) ? true : false;
        if ( $is_entire_ex_woo && $location && $location != 'woocommerce_page' ) {
            return true;
        }
        
        if ( $this->is_allowed_location($location) ) {
            return true;
        }
        
        return false;
    }

    /**
     * Check if current location is allowed in settings
     * 
     * @param string $location_name Location to check
     * @return bool True if location is allowed
     */
    public function is_allowed_location($location_name = '') {
        $options = $this->get_video_popup_options(true);

        $locations = $options && isset($options['display_locations']) && is_array($options['display_locations']) && !empty($options['display_locations']) 
            ? $options['display_locations'] 
            : array();

        if ( empty($locations) || empty(trim($location_name)) ) {
            return false;
        }

        $location_name = sanitize_text_field($location_name);
        $location_name = strtolower($location_name);

        return in_array($location_name, $locations);
    }

    /**
     * Check if current ID is in the allowed ID
     * 
     * @param string|int $current_id ID to check
     * @return bool True if ID is allowed
     */
    public function is_allowed_id($current_id = '') {
        $options = $this->get_video_popup_options(true);

        $allowed_id = $options && isset($options['allowed_post_id']) && !empty($options['allowed_post_id']) ? intval($options['allowed_post_id']) : 0;

        if ( empty($allowed_id) || empty(trim($current_id)) ) {
            return false;
        }

        $current_id = intval($current_id);

        return $current_id == $allowed_id;
    }

}