<?php

if ( !defined('ABSPATH') ) {
    exit;
}

/**
 * Handles all on-page-load popup functionality including cookie management,
 * display triggers, targeting rules, and appearance customization.
 * Provides extensive settings for controlling video popups that appear automatically
 * on-page load with support for custom display locations, user targeting,
 * and time-based display rules.
 * Uses singleton pattern to ensure single instance throughout the application.
 * 
 * @author   Alobaidi
 * @since    2.0.0
 */

class Video_Popup_OnPageLoad {

    private static $instance = null;
    private $key_suffix = null;
    
    /**
     * Retrieves the singleton instance of this class
     * Creates a new instance if one doesn't exist yet
     * 
     * @return Video_Popup_OnPageLoad The single instance of this class
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
     * Gets the utils class instance for validation helpers
     * 
     * @return Video_Popup_Utils The utils class instance
     */
    private function get_utils() {
        $utils = Video_Popup_Utils::get_instance();
        return $utils;
    }

    /**
     * Returns the suffix used for settings keys
     * Used to differentiate onpage load settings from main settings
     * 
     * @return string Settings key suffix
     */
    public function key_suffix_() {
        return '_onpage_load';
    }
    
    /**
     * Run the on-page-load functionality by registering hooks
     * Sets up admin submenu, settings, and fields
     */
    public function run() {
        $this->key_suffix = $this->key_suffix_();
        add_action('admin_menu', array($this, 'add_admin_submenu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_script'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_init', array($this, 'settings_section'));
        add_action('admin_init', array($this, 'settings_fields'));
    }

    /**
     * Enqueues admin scripts and styles for on-page-load settings
     * Only loads on the plugin's settings page
     */
    public function enqueue_admin_script() {
        global $pagenow;
        if ( $pagenow != 'admin.php' ) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reason: only checking $_GET['page'] value to verify current admin page, no data processing.
        if ( !isset($_GET['page']) || isset($_GET['page']) && $_GET['page'] != $this->get_const('plugin_id') . $this->key_suffix ) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reason: This check only reads a harmless GET flag (vp_note_got_it) to update an internal option, no sensitive or user-submitted data is processed.
        if ( isset($_GET['vp_note_got_it']) ) {
            if ( !get_option($this->get_const('settings_key') . '_first_use') ) {
                update_option($this->get_const('settings_key') . '_first_use', 'used');
            }
        }

        if ( !get_option($this->get_const('settings_key') . '_admin_notify') ) {
            update_option($this->get_const('settings_key') . '_admin_notify', 'shown');
        }

        wp_enqueue_style(
            $this->get_const('plugin_id') . '_vp-tooltip' . $this->key_suffix,
            $this->get_const('plugin_url') . 'includes/css/vp-tooltip.css',
            array(),
            $this->get_const('plugin_version')
        );

        wp_enqueue_style(
            $this->get_const('plugin_id') . '_vp-admin-style' . $this->key_suffix,
            $this->get_const('plugin_url') . 'includes/css/vp-admin-style.css',
            array(),
            $this->get_const('plugin_version')
        );

        wp_enqueue_script(
            $this->get_const('plugin_id') . '-display-locations',
            $this->get_const('plugin_url') . 'includes/js/display-locations.js',
            array(),
            $this->get_const('plugin_version'),
            true
        );
    }

    /**
     * Adds submenu page for on-page-load settings
     * Creates dedicated admin page for on-page-load video popup configuration
     */
    public function add_admin_submenu() {
        add_submenu_page(
            $this->get_const('plugin_id'),
            esc_html__('Public On-Page Load Settings - Video Popup', 'video-popup'),
            esc_html__('Public On-Page Load', 'video-popup'),
            'manage_options',
            $this->get_const('plugin_id') . $this->key_suffix,
            array($this, 'render_page')
        );
    }

    /**
     * Returns default settings for on-page-load functionality
     * Used when initializing settings and as fallback values
     * 
     * @return array Default settings with their initial values
     */
    public function get_default_settings() {
        return array(
            'enable_onpage_load' => 0,
            'debug' => 0,

            'video_url' => '',
            'autoplay' => 0,
            'mute'  => 0,

            'no_cookie' => 0,
            'wrap_close' => 0,

            'cookie_expiry' => '',
            'cookie_id' => '',

            'user_target' => 'all',
            'display_locations' => array(),
            'allowed_post_id' => ''
        );
    }
    
    /**
     * Registers plugin settings with WordPress
     * Creates settings if they don't exist with default values
     */
    public function register_settings() {
        $option_key = $this->get_const('settings_key') . $this->key_suffix;
        $default_settings = $this->get_default_settings();

        $version_key = $option_key . '_version';
        $plugin_version = $this->get_const('plugin_version');
        
        if ( !get_option($option_key) ) {
            add_option($option_key, $default_settings);
            add_option($version_key, $plugin_version);
        } else {
            $current_settings = get_option($option_key);
            $saved_version = get_option($version_key, '0.0.0');
            if ( version_compare($saved_version, $plugin_version, '<') || count($current_settings) !== count($default_settings) ) {
                $merged_settings = wp_parse_args($current_settings, $default_settings);
                $cleaned_settings = array_intersect_key($merged_settings, $default_settings);
                update_option($option_key, $cleaned_settings);
                update_option($version_key, $plugin_version);
            }
        }

        register_setting(
            $this->get_const('settings_group') . $this->key_suffix,
            $option_key,
            array(
                'sanitize_callback' => array($this, 'sanitize_settings')
            )
        );
    }

    /**
     * Creates settings sections for organizing fields
     * Groups related settings with descriptive text
     */
    public function settings_section() {
        // General Section
        add_settings_section(
            'general_section',
            esc_html__('General Options', 'video-popup'),
            function() {
                echo '<p class="vp-settings-section-description">' . esc_html__('Enable or disable the On-Page Load and JavaScript debug mode.', 'video-popup') . '</p>';
            },
            $this->get_const('plugin_id') . $this->key_suffix . '_general_settings_section'
        );

        // Video Section
        add_settings_section(
            'video_section',
            esc_html__('Video Settings', 'video-popup'),
            function() {
                echo '<p class="vp-settings-section-description">' . esc_html__('Video link, autoplay, mute, hide/show player controls, and more.', 'video-popup') . '</p>';
            },
            $this->get_const('plugin_id') . $this->key_suffix . '_video_settings_section'
        );

        // Appearance Section
        add_settings_section(
            'appearance_section',
            esc_html__('Appearance Settings', 'video-popup'),
            function() {
                echo '<p class="vp-settings-section-description">' . esc_html__('Customize size, overlay color, overlay opacity, minimize, and popup closing method.', 'video-popup') . '</p>';
            },
            $this->get_const('plugin_id') . $this->key_suffix . '_appearance_settings_section'
        );

        // Display Section
        add_settings_section(
            'display_section',
            esc_html__('Display Settings', 'video-popup'),
            function() {
                echo '<p class="vp-settings-section-description">' . esc_html__('Set displaying rules to control where, when, and for whom the video popup shows.', 'video-popup') . '</p>';
            },
            $this->get_const('plugin_id') . $this->key_suffix . '_display_settings_section'
        );
    }

    /**
     * Registers settings fields for on-page-load functionality
     * Creates all form fields with their callbacks
     */
    public function settings_fields() {
        // General Section Fields
        add_settings_field(
            'enable_onpage_load',
            esc_html__('Enable Public On-Page Load', 'video-popup'),
            array($this, 'field_enable_onpage_load_callback'),
            $this->get_const('plugin_id') . $this->key_suffix . '_general_settings_section',
            'general_section',
            array('label_for' => 'vp_input_enable_onpage_load', 'class' => 'vp-table-tr-custom-class')
        );

        add_settings_field(
            'debug',
            esc_html__('Enable JS Debug Mode', 'video-popup'),
            array($this, 'field_debug_onpage_load_callback'),
            $this->get_const('plugin_id') . $this->key_suffix . '_general_settings_section',
            'general_section',
            array('label_for' => 'vp_input_debug', 'class' => 'vp-table-tr-custom-class')
        );

        // Video Section Fields
        add_settings_field(
            'video_url',
            esc_html__('Video URL', 'video-popup'),
            array($this, 'field_video_url_callback'),
            $this->get_const('plugin_id') . $this->key_suffix . '_video_settings_section',
            'video_section',
            array('label_for' => 'vp_input_video_url', 'class' => 'vp-table-tr-custom-class')
        );

        add_settings_field(
            'autoplay',
            esc_html__('Autoplay', 'video-popup'),
            array($this, 'field_autoplay_callback'),
            $this->get_const('plugin_id') . $this->key_suffix . '_video_settings_section',
            'video_section',
            array('label_for' => 'vp_input_autoplay', 'class' => 'vp-table-tr-custom-class')
        );

        add_settings_field(
            'mute',
            esc_html__('Mute', 'video-popup'),
            array($this, 'field_mute_callback'),
            $this->get_const('plugin_id') . $this->key_suffix . '_video_settings_section',
            'video_section',
            array('label_for' => 'vp_input_mute', 'class' => 'vp-table-tr-custom-class')
        );

        add_settings_field(
            'disable_controls',
            esc_html__('Hide Player Controls (Premium)', 'video-popup'),
            array($this, 'field_disable_controls_callback'),
            $this->get_const('plugin_id') . $this->key_suffix . '_video_settings_section',
            'video_section',
            array('label_for' => 'vp_input_disable_controls', 'class' => 'vp-table-tr-custom-class vp-table-tr-star')
        );

        add_settings_field(
            'dis_yt_rel_videos',
            esc_html__('Limit YouTube Related Videos (Premium)', 'video-popup'),
            array($this, 'field_dis_yt_rel_videos_callback'),
            $this->get_const('plugin_id') . $this->key_suffix . '_video_settings_section',
            'video_section',
            array('label_for' => 'vp_input_dis_yt_rel_videos', 'class' => 'vp-table-tr-custom-class vp-table-tr-star')
        );

        add_settings_field(
            'yt_start_time',
            esc_html__('Start Time (Premium)', 'video-popup'),
            array($this, 'field_yt_start_time_callback'),
            $this->get_const('plugin_id') . $this->key_suffix . '_video_settings_section',
            'video_section',
            array('label_for' => 'vp_input_yt_start_time', 'class' => 'vp-table-tr-custom-class vp-table-tr-star')
        );

        add_settings_field(
            'yt_end_time',
            esc_html__('End Time (Premium)', 'video-popup'),
            array($this, 'field_yt_end_time_callback'),
            $this->get_const('plugin_id') . $this->key_suffix . '_video_settings_section',
            'video_section',
            array('label_for' => 'vp_input_yt_end_time', 'class' => 'vp-table-tr-custom-class vp-table-tr-star')
        );

        add_settings_field(
            'no_cookie',
            esc_html__('Privacy Mode', 'video-popup'),
            array($this, 'field_no_cookie_callback'),
            $this->get_const('plugin_id') . $this->key_suffix . '_video_settings_section',
            'video_section',
            array('label_for' => 'vp_input_no_cookie', 'class' => 'vp-table-tr-custom-class')
        );

        // Appearance Section Fields
        add_settings_field(
            'width_size',
            esc_html__('Width Size (Premium)', 'video-popup'),
            array($this, 'field_width_size_callback'),
            $this->get_const('plugin_id') . $this->key_suffix . '_appearance_settings_section',
            'appearance_section',
            array('label_for' => 'vp_input_width_size', 'class' => 'vp-table-tr-custom-class vp-table-tr-star')
        );

        add_settings_field(
            'height_size',
            esc_html__('Height Size (Premium)', 'video-popup'),
            array($this, 'field_height_size_callback'),
            $this->get_const('plugin_id') . $this->key_suffix . '_appearance_settings_section',
            'appearance_section',
            array('label_for' => 'vp_input_height_size', 'class' => 'vp-table-tr-custom-class vp-table-tr-star')
        );

        add_settings_field(
            'overlay_color',
            esc_html__('Overlay Color (Premium)', 'video-popup'),
            array($this, 'field_overlay_color_callback'),
            $this->get_const('plugin_id') . $this->key_suffix . '_appearance_settings_section',
            'appearance_section',
            array('label_for' => 'vp_input_overlay_color', 'class' => 'vp-table-tr-custom-class vp-table-tr-star')
        );

        add_settings_field(
            'overlay_opacity',
            esc_html__('Overlay Opacity (Premium)', 'video-popup'),
            array($this, 'field_overlay_opacity_callback'),
            $this->get_const('plugin_id') . $this->key_suffix . '_appearance_settings_section',
            'appearance_section',
            array('label_for' => 'vp_input_overlay_opacity', 'class' => 'vp-table-tr-custom-class vp-table-tr-star')
        );

        add_settings_field(
            'minimize',
            esc_html__('Popup Design (Premium)', 'video-popup'),
            array($this, 'field_minimize_callback'),
            $this->get_const('plugin_id') . $this->key_suffix . '_appearance_settings_section',
            'appearance_section',
            array('label_for' => 'vp_input_minimize', 'class' => 'vp-table-tr-custom-class vp-table-tr-star')
        );

        add_settings_field(
            'wrap_close',
            esc_html__('Disable Outside Click Close', 'video-popup'),
            array($this, 'field_wrap_close_callback'),
            $this->get_const('plugin_id') . $this->key_suffix . '_appearance_settings_section',
            'appearance_section',
            array('label_for' => 'vp_input_wrap_close', 'class' => 'vp-table-tr-custom-class')
        );

        // Display Section Fields
        add_settings_field(
            'user_target',
            esc_html__('User Targeting', 'video-popup'),
            array($this, 'field_user_target_callback'),
            $this->get_const('plugin_id') . $this->key_suffix . '_display_settings_section',
            'display_section',
            array('label_for' => 'vp_input_user_target', 'class' => 'vp-table-tr-custom-class')
        );

        add_settings_field(
            'device_target',
            esc_html__('Device Targeting (Premium)', 'video-popup'),
            array($this, 'field_device_target_callback'),
            $this->get_const('plugin_id') . $this->key_suffix . '_display_settings_section',
            'display_section',
            array('label_for' => 'vp_input_device_target', 'class' => 'vp-table-tr-custom-class vp-table-tr-star')
        );

        add_settings_field(
            'delay_before_show',
            esc_html__('Delay Before Show (Premium)', 'video-popup'),
            array($this, 'field_delay_before_show_callback'),
            $this->get_const('plugin_id') . $this->key_suffix . '_display_settings_section',
            'display_section',
            array('label_for' => 'vp_input_delay_before_show', 'class' => 'vp-table-tr-custom-class vp-table-tr-star')
        );

        add_settings_field(
            'cookie_expiry',
            esc_html__('Cookie Expiry', 'video-popup'),
            array($this, 'field_cookie_expiry_callback'),
            $this->get_const('plugin_id') . $this->key_suffix . '_display_settings_section',
            'display_section',
            array('label_for' => 'vp_input_cookie_expiry', 'class' => 'vp-table-tr-custom-class')
        );

        add_settings_field(
            'display_locations',
            esc_html__('Display Locations', 'video-popup'),
            array($this, 'field_display_locations_callback'),
            $this->get_const('plugin_id') . $this->key_suffix . '_display_settings_section',
            'display_section',
            array('label_for' => 'vp_input_display_locations', 'class' => 'vp-table-tr-custom-class')
        );

        add_settings_field(
            'allowed_post_id',
            esc_html__('Always show on this ID', 'video-popup'),
            array($this, 'field_allowed_post_id_callback'),
            $this->get_const('plugin_id') . $this->key_suffix . '_display_settings_section',
            'display_section',
            array('label_for' => 'vp_input_allowed_post_id', 'class' => 'vp-table-tr-custom-class')
        );

        add_settings_field(
            'allowed_ids',
            esc_html__('Always show on these IDs (Premium)', 'video-popup'),
            array($this, 'field_allowed_ids_callback'),
            $this->get_const('plugin_id') . $this->key_suffix . '_display_settings_section',
            'display_section',
            array('label_for' => 'vp_input_allowed_ids', 'class' => 'vp-table-tr-custom-class vp-table-tr-star')
        );

        add_settings_field(
            'excluded_ids',
            esc_html__('Never show on these IDs (Premium)', 'video-popup'),
            array($this, 'field_excluded_ids_callback'),
            $this->get_const('plugin_id') . $this->key_suffix . '_display_settings_section',
            'display_section',
            array('label_for' => 'vp_input_excluded_ids', 'class' => 'vp-table-tr-custom-class vp-table-tr-star')
        );

        add_settings_field(
            'max_width_screen',
            esc_html__('Hide on Specific Screen Width (Premium)', 'video-popup'),
            array($this, 'field_max_width_screen_callback'),
            $this->get_const('plugin_id') . $this->key_suffix . '_display_settings_section',
            'display_section',
            array('label_for' => 'vp_input_max_width_screen', 'class' => 'vp-table-tr-custom-class vp-table-tr-star')
        );

        add_settings_field(
            'schedule',
            esc_html__('Display Scheduling (Premium)', 'video-popup'),
            array($this, 'field_schedule_callback'),
            $this->get_const('plugin_id') . $this->key_suffix . '_display_settings_section',
            'display_section',
            array('label_for' => 'vp_input_max_width_screen', 'class' => 'vp-table-tr-custom-class vp-table-tr-star')
        );

    }

    /* --- General Section Callbacks --- */
    public function field_enable_onpage_load_callback($args) {
        $defaults = $this->get_default_settings();

        $option_key = $this->get_const('settings_key') . $this->key_suffix;
        $options = get_option($option_key, $defaults);

        $input_id = isset($args['label_for']) ? $args['label_for'] : '';

        if ( !isset($options['enable_onpage_load']) ) {
            $options['enable_onpage_load'] = $defaults['enable_onpage_load'];
        }
        ?>
        <label class="vp-switch-container">
            <input class="vp-switch-input" type="checkbox" id="<?php echo esc_attr($input_id); ?>" name="<?php echo esc_attr($option_key); ?>[enable_onpage_load]" value="1" <?php checked(1, $options['enable_onpage_load']); ?>>
            <span class="vp-switch-slider"></span>
        </label>
        <p class="vp-option-tagline"><?php esc_html_e('You can enable or disable the Public On-Page Load at any time using this option.', 'video-popup'); ?></p>
        <?php
    }

    public function field_debug_onpage_load_callback($args) {
        $defaults = $this->get_default_settings();

        $option_key = $this->get_const('settings_key') . $this->key_suffix;
        $options = get_option($option_key, $defaults);

        $input_id = isset($args['label_for']) ? $args['label_for'] : '';

        if ( !isset($options['debug']) ) {
            $options['debug'] = $defaults['debug'];
        }
        ?>
        <label class="vp-switch-container">
            <input class="vp-switch-input" type="checkbox" id="<?php echo esc_attr($input_id); ?>" name="<?php echo esc_attr($option_key); ?>[debug]" value="1" <?php checked(1, $options['debug']); ?>>
            <span class="vp-switch-slider"></span>
        </label>
        <p class="vp-option-tagline"><?php esc_html_e('Enable this option to show debug logs in the browser console. Logs are visible only after opening the video popup, and you must be logged in. To view them, step by step: Open your site > Click on any video popup link in your content or the one with the issue > Press F12 > Check the Console tab.', 'video-popup'); ?><span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('We recommend using Google Chrome while debugging.', 'video-popup'); ?>">?</span></p>
        <?php
    }


    /* --- Video Section Callbacks --- */
    public function field_video_url_callback($args) {
        $shortcode_ref_url = admin_url('admin.php?page=' . $this->get_const('plugin_id') . '_shortcode');

        $defaults = $this->get_default_settings();

        $option_key = $this->get_const('settings_key') . $this->key_suffix;
        $options = get_option($option_key, $defaults);
        $input_id = isset($args['label_for']) ? $args['label_for'] : '';

        if ( !isset($options['video_url']) ) {
            $options['video_url'] = $defaults['video_url'];
        }
        ?>
        <input type="text" id="<?php echo esc_attr($input_id); ?>" name="<?php echo esc_attr($option_key); ?>[video_url]" value="<?php echo esc_attr($options['video_url']); ?>" class="large-text vp-input-large-text">
        <p class="vp-option-tagline">
            <?php
                printf(
                    // translators: %1$s is opening link tag, %2$s is closing link tag
                    esc_html__('Enter a YouTube, Vimeo, or direct video URL (MP4 or WebM only). YouTube Shorts links are also supported. For more information on URL formats, go to the %1$sShortcode Reference%2$s > Supported Video Sources.', 'video-popup'),
                        '<a href="' . esc_url($shortcode_ref_url) . '">',
                        '</a>'
                );
            ?>
        </p>
        <?php
    }

    public function field_autoplay_callback($args) {
        $defaults = $this->get_default_settings();

        $option_key = $this->get_const('settings_key') . $this->key_suffix;
        $options = get_option($option_key, $defaults);

        $input_id = isset($args['label_for']) ? $args['label_for'] : '';

        if ( !isset($options['autoplay']) ) {
            $options['autoplay'] = $defaults['autoplay'];
        }
        ?>
        <label class="vp-switch-container">
            <input class="vp-switch-input" type="checkbox" id="<?php echo esc_attr($input_id); ?>" name="<?php echo esc_attr($option_key); ?>[autoplay]" value="1" <?php checked(1, $options['autoplay']); ?>>
            <span class="vp-switch-slider"></span>
        </label>
        <p class="vp-option-tagline"><?php esc_html_e('Video starts playing automatically when the popup opens. Please note, autoplay may not work unless the mute option is enabled. However, on mobile phones and tablets, autoplay may not work even when the mute option is enabled, or it may behave inconsistently, working sometimes and failing at other times, due to browser autoplay policies.', 'video-popup'); ?></p>
        <?php
    }

    public function field_mute_callback($args) {
        $defaults = $this->get_default_settings();

        $option_key = $this->get_const('settings_key') . $this->key_suffix;
        $options = get_option($option_key, $defaults);

        $input_id = isset($args['label_for']) ? $args['label_for'] : '';

        if ( !isset($options['mute']) ) {
            $options['mute'] = $defaults['mute'];
        }
        ?>
        <label class="vp-switch-container">
            <input class="vp-switch-input" type="checkbox" id="<?php echo esc_attr($input_id); ?>" name="<?php echo esc_attr($option_key); ?>[mute]" value="1" <?php checked(1, $options['mute']); ?>>
            <span class="vp-switch-slider"></span>
        </label>
        <p class="vp-option-tagline"><?php esc_html_e('Mute video sound.', 'video-popup'); ?></p>
        <?php
    }

    public function field_disable_controls_callback($args) {
        ?>
        <p class="vp-option-tagline">
            <?php
                printf(
                    // translators: %1$s is opening link tag, %2$s is closing link tag
                    esc_html__('With the %1$sPremium Version%2$s, you can hide the player controls on YouTube, Vimeo, and supported direct video formats.', 'video-popup'),
                    '<a href="https://videopopup.net/?utm_source=plugin&utm_medium=link&utm_campaign=opl_settings_page#get-premium" target="_blank">',
                    '</a>'
                );
            ?>
        </p>
        <?php
    }

    public function field_dis_yt_rel_videos_callback($args) {
        ?>
        <p class="vp-option-tagline">
            <?php
                printf(
                    // translators: %1$s is opening link tag, %2$s is closing link tag
                    esc_html__('With the %1$sPremium Version%2$s, you can limit related videos to the same channel instead of showing all YouTube videos. This option is for YouTube only.', 'video-popup'),
                    '<a href="https://videopopup.net/?utm_source=plugin&utm_medium=link&utm_campaign=opl_settings_page#get-premium" target="_blank">',
                    '</a>'
                );
            ?><span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('Note: YouTube no longer allows completely hiding related videos since September 2018, but this option helps keep viewers within the same channel content.', 'video-popup'); ?>">?</span>
        </p>
        <?php
    }

    public function field_yt_start_time_callback($args) {
        ?>
        <p class="vp-option-tagline">
            <?php
                printf(
                    // translators: %1$s is opening link tag, %2$s is closing link tag
                    esc_html__('With the %1$sPremium Version%2$s, you can start a YouTube or Vimeo video at a specific time. Supported formats: Seconds (e.g., 90), Time format (MM:SS, e.g., 01:30 or HH:MM:SS, e.g., 01:15:45), Duration format (e.g., 1m, 1m30s, 1h, 1h30s, 1h40m, or 1h15m45s). This option is for YouTube and Vimeo only.', 'video-popup'),
                    '<a href="https://videopopup.net/?utm_source=plugin&utm_medium=link&utm_campaign=opl_settings_page#get-premium" target="_blank">',
                    '</a>'
                );
            ?>
        </p>
        <?php
    }

    public function field_yt_end_time_callback($args) {
        ?>
        <p class="vp-option-tagline">
            <?php
                printf(
                    // translators: %1$s is opening link tag, %2$s is closing link tag
                    esc_html__('With the %1$sPremium Version%2$s, you can stop a YouTube video at a specific time. Supported formats: Seconds (e.g., 300), Time format (MM:SS, e.g., 05:00 or HH:MM:SS, e.g., 02:30:00), Duration format (e.g., 5m, 5m20s, 2h, 2h15s, 2h30m, or 2h30m10s). This option is for YouTube only.', 'video-popup'),
                    '<a href="https://videopopup.net/?utm_source=plugin&utm_medium=link&utm_campaign=opl_settings_page#get-premium" target="_blank">',
                    '</a>'
                );
            ?>
        </p>
        <?php
    }

    public function field_no_cookie_callback($args) {
        $defaults = $this->get_default_settings();

        $option_key = $this->get_const('settings_key') . $this->key_suffix;
        $options = get_option($option_key, $defaults);

        $input_id = isset($args['label_for']) ? $args['label_for'] : '';

        if ( !isset($options['no_cookie']) ) {
            $options['no_cookie'] = $defaults['no_cookie'];
        }
        ?>
        <label class="vp-switch-container">
            <input class="vp-switch-input" type="checkbox" id="<?php echo esc_attr($input_id); ?>" name="<?php echo esc_attr($option_key); ?>[no_cookie]" value="1" <?php checked(1, $options['no_cookie']); ?>>
            <span class="vp-switch-slider"></span>
        </label>
        <p class="vp-option-tagline"><?php esc_html_e('Prevent YouTube and Vimeo from storing cookies in the user\'s browser. GDPR-compliant. This option is for YouTube and Vimeo only.', 'video-popup'); ?></p>
        <?php
    }


    /* --- Appearance Section Callbacks --- */
    public function field_width_size_callback($args) {
        ?>
        <p class="vp-option-tagline">
            <?php
                printf(
                    // translators: %1$s is opening link tag, %2$s is closing link tag
                    esc_html__('With the %1$sPremium Version%2$s, you can set a custom popup width with support for pixels (px), percentages (%%), and viewport width (vw).', 'video-popup'),
                    '<a href="https://videopopup.net/?utm_source=plugin&utm_medium=link&utm_campaign=opl_settings_page#get-premium" target="_blank">',
                    '</a>'
                );
            ?>
        </p>
        <?php
    }

    public function field_height_size_callback($args) {
        ?>
        <p class="vp-option-tagline">
            <?php
                printf(
                    // translators: %1$s is opening link tag, %2$s is closing link tag
                    esc_html__('With the %1$sPremium Version%2$s, you can set a custom popup height with support for pixels (px), percentages (%%), and viewport height (vh). You can also apply a 16:9 aspect ratio by entering "16:9" for height and a pixel value for width.', 'video-popup'),
                    '<a href="https://videopopup.net/?utm_source=plugin&utm_medium=link&utm_campaign=opl_settings_page#get-premium" target="_blank">',
                    '</a>'
                );
            ?>
        </p>
        <?php
    }

    public function field_overlay_color_callback($args) {
        ?>
        <p class="vp-option-tagline">
            <?php
                printf(
                    // translators: %1$s is opening link tag, %2$s is closing link tag
                    esc_html__('With the %1$sPremium Version%2$s, you can set a custom overlay color using HEX format (e.g., #FFFFFF for white).', 'video-popup'),
                    '<a href="https://videopopup.net/?utm_source=plugin&utm_medium=link&utm_campaign=opl_settings_page#get-premium" target="_blank">',
                    '</a>'
                );
            ?>
        </p>
        <?php
    }

    public function field_overlay_opacity_callback($args) {
        ?>
        <p class="vp-option-tagline">
            <?php
                printf(
                    // translators: %1$s is opening link tag, %2$s is closing link tag
                    esc_html__('With the %1$sPremium Version%2$s, you can adjust overlay transparency from 0 (fully transparent) to 100 (completely opaque). You can even create a blur backdrop effect by combining low opacity (e.g., 20) with black (#000000) or white (#FFFFFF) overlay color.', 'video-popup'),
                    '<a href="https://videopopup.net/?utm_source=plugin&utm_medium=link&utm_campaign=opl_settings_page#get-premium" target="_blank">',
                    '</a>'
                );
            ?>
        </p>
        <?php
    }

    public function field_minimize_callback($args) {
        ?>
        <div class="vp-radio-group">
            <label><input type="radio" value="none" checked><?php esc_html_e('Basic Design (default).', 'video-popup'); ?></label>
            <label class="vp-label-star"><?php esc_html_e('Minimized Design on the Left Side (Premium).', 'video-popup'); ?></label>
            <label class="vp-label-star"><?php esc_html_e('Minimized Design on the Right Side (Premium).', 'video-popup'); ?></label>
        </div>
        <p class="vp-option-tagline">
            <?php
                printf(
                    // translators: %1$s and %3$s is opening link tag, %2$s and %4$s is closing link tag, and %5$s is <br> tag.
                    esc_html__('With the %1$sPremium Version%2$s, you can display a video popup in a small, elegant design at the bottom of the page, on either the left or right side.%5$sYou can see a %3$slive demo%4$s of the minimized design.', 'video-popup'),
                    '<a href="https://videopopup.net/?utm_source=plugin&utm_medium=link&utm_campaign=opl_settings_page#get-premium" target="_blank">',
                    '</a>',
                    '<a href="https://wp-time.com/on-page-load-video-popup-delay-before-show-option-live-demo/" target="_blank">',
                    '</a>',
                    '<br>'
                );
            ?>
        </p>
        <?php
    }

    public function field_wrap_close_callback($args) {
        $defaults = $this->get_default_settings();

        $option_key = $this->get_const('settings_key') . $this->key_suffix;
        $options = get_option($option_key, $defaults);

        $input_id = isset($args['label_for']) ? $args['label_for'] : '';

        if ( !isset($options['wrap_close']) ) {
            $options['wrap_close'] = $defaults['wrap_close'];
        }
        ?>
        <label class="vp-switch-container">
            <input class="vp-switch-input" type="checkbox" id="<?php echo esc_attr($input_id); ?>" name="<?php echo esc_attr($option_key); ?>[wrap_close]" value="1" <?php checked(1, $options['wrap_close']); ?>>
            <span class="vp-switch-slider"></span>
        </label>
        <p class="vp-option-tagline"><?php esc_html_e('Prevents popup from closing when clicking outside the video area (overlay background).', 'video-popup'); ?><span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('When this option is enabled, users must use the close button or ESC key to close the popup.', 'video-popup'); ?>">?</span></p>
        <?php
    }


    /* --- Display Section Callbacks --- */
    public function field_delay_before_show_callback($args) {
        ?>
        <p class="vp-option-tagline">
            <?php
                printf(
                    // translators: %1$s and %3$s is opening link tag, %2$s and %4$s is closing link tag
                    esc_html__('With the %1$sPremium Version%2$s, you can set a delay in seconds before showing the video popup. You can see a live demo %3$shere%4$s to get the idea.', 'video-popup'),
                    '<a href="https://videopopup.net/?utm_source=plugin&utm_medium=link&utm_campaign=opl_settings_page#get-premium" target="_blank">',
                    '</a>',
                    '<a href="https://wp-time.com/on-page-load-video-popup-delay-before-show-option-live-demo/" target="_blank">',
                    '</a>'
                );
            ?>
        </p>
        <?php
    }

    public function field_max_width_screen_callback($args) {
        ?>
        <p class="vp-option-tagline">
            <?php
                printf(
                    // translators: %1$s is opening link tag, %2$s is closing link tag
                    esc_html__('With the %1$sPremium Version%2$s, you can hide the video popup on specific screen widths, such as "480" for mobile devices.', 'video-popup'),
                    '<a href="https://videopopup.net/?utm_source=plugin&utm_medium=link&utm_campaign=opl_settings_page#get-premium" target="_blank">',
                    '</a>'
                );
            ?>
        </p>
        <?php
    }

    public function field_schedule_callback($args) {
        ?>
        <p class="vp-option-tagline">
            <?php
                printf(
                    // translators: %1$s is opening link tag, %2$s is closing link tag
                    esc_html__('With the %1$sPremium Version%2$s, you can set a start date to begin displaying the video popup on a specific date, an end date to stop it on a specific date, or both to show it only between selected dates.', 'video-popup'),
                    '<a href="https://videopopup.net/?utm_source=plugin&utm_medium=link&utm_campaign=opl_settings_page#get-premium" target="_blank">',
                    '</a>'
                );
            ?>
        </p>
        <?php
    }

    public function field_user_target_callback($args) {
        $defaults = $this->get_default_settings();
        $option_key = $this->get_const('settings_key') . $this->key_suffix;
        $options = get_option($option_key, $defaults);
        $input_id = isset($args['label_for']) ? $args['label_for'] : '';
        
        $current = isset($options['user_target']) ? $options['user_target'] : $defaults['user_target'];
        ?>
        <select id="<?php echo esc_attr($input_id); ?>" 
                name="<?php echo esc_attr($option_key); ?>[user_target]" 
                class="regular-text" style="width: 100%;max-width: 200px;">
            <option value="all" <?php selected($current, 'all'); ?>><?php esc_html_e('Everyone', 'video-popup'); ?></option>
            <option value="users" <?php selected($current, 'users'); ?>><?php esc_html_e('Logged in users only', 'video-popup'); ?></option>
            <option value="visitors" <?php selected($current, 'visitors'); ?>><?php esc_html_e('Visitors only', 'video-popup'); ?></option>
        </select>
        <p class="vp-option-tagline"><?php esc_html_e('Choose who the video popup shows to. This lets you control whether the video popup shows for everyone, visitors only, or logged in users only.', 'video-popup'); ?></p>
        <?php
    }

    public function field_device_target_callback($args) {
        ?>
        <div class="vp-radio-group">
            <label><input type="radio" value="none" checked><?php esc_html_e('Any device (default).', 'video-popup'); ?></label>
            <label class="vp-label-star"><?php esc_html_e('Mobile devices only (will appear on iPhone, iPad, and Android devices only. Premium).', 'video-popup'); ?></label>
            <label class="vp-label-star"><?php esc_html_e('Any device except mobile (will not appear on iPhone, iPad, and Android devices. Premium).', 'video-popup'); ?></label>
            <label class="vp-label-star"><?php esc_html_e('iPhone and iPad only (Premium).', 'video-popup'); ?></label>
            <label class="vp-label-star"><?php esc_html_e('iPhone only (Premium).', 'video-popup'); ?></label>
            <label class="vp-label-star"><?php esc_html_e('iPad only (Premium).', 'video-popup'); ?></label>
            <label class="vp-label-star"><?php esc_html_e('Android devices only (Premium).', 'video-popup'); ?></label>
        </div>
        <p class="vp-option-tagline">
            <?php
                printf(
                    // translators: %1$s is opening link tag, %2$s is closing link tag
                    esc_html__('With the %1$sPremium Version%2$s, you can choose which devices the video popup should appear on. This allows you to target specific platforms or limit the video popup to mobile users only.', 'video-popup'),
                    '<a href="https://videopopup.net/?utm_source=plugin&utm_medium=link&utm_campaign=opl_settings_page#get-premium" target="_blank">',
                    '</a>'
                );
            ?><span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('For the On-Page Load Shortcode (premium only), use the dtr="" attribute to target a device or platform. You will find a detailed explanation in: "Shortcode Reference" page > "On-Page Load Shortcode" section > "Attributes" table > dtr attribute.', 'video-popup'); ?>">?</span>
        </p>
        <?php
    }

    public function field_cookie_expiry_callback($args) {
        $defaults = $this->get_default_settings();
        $option_key = $this->get_const('settings_key') . $this->key_suffix;
        $options = get_option($option_key, $defaults);
        $input_id = isset($args['label_for']) ? $args['label_for'] : '';

        $get_cookie_name = false;
        
        if (!isset($options['cookie_expiry'])) {
            $options['cookie_expiry'] = $defaults['cookie_expiry'];
        }

        if (!isset($options['cookie_id'])) {
            $options['cookie_id'] = $defaults['cookie_id'];
        }

        if ( !empty($options['cookie_id']) && isset($_COOKIE[$options['cookie_id']]) ) {
            $get_cookie_name = sanitize_text_field($options['cookie_id']);
        }

        $delete_cookie_alert = __("Public On-Page Load cookie deleted successfully on this browser!", 'video-popup');
        $no_cookie_alert =  __("No Public On-Page Load cookie found on this browser.", 'video-popup');
        ?>
        <input type="text" id="<?php echo esc_attr($input_id); ?>" 
               name="<?php echo esc_attr($option_key); ?>[cookie_expiry]" 
               value="<?php echo esc_attr($options['cookie_expiry']); ?>" 
               class="regular-text vp-input-number">
        <p class="vp-option-tagline"><?php esc_html_e('Set how many days to wait before showing the video popup again for each visitor/user. For example: "7" means after showing the video popup to a visitor/user, it will not show again for that visitor/user until 7 days have passed. Only whole numbers are allowed. Leave it empty to show every visit.', 'video-popup'); ?><span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('This feature is GDPR compliant as it only stores whether the video popup has been shown or not and doesn\'t collect any personal data.', 'video-popup'); ?>">?</span></p>

        <?php if ( $get_cookie_name ) : ?>
            <p style="margin: 7px 0 0 0; padding: 0;">
                <button type="button" id="reset_vp_opl_cookie" class="button button-secondary"><?php esc_html_e('Delete Cookie', 'video-popup'); ?><span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('If the "Delete Cookie" button appears, it means you have already seen the popup. Instead of waiting for the cookie to expire, click the "Delete Cookie" button to delete the "Public On-Page Load" cookie from your browser. This will show the video popup again for you only and does not affect other users/visitors. Useful for testing.', 'video-popup'); ?>">?</span></button>
            </p>

            <script type="text/javascript">
                document.addEventListener('DOMContentLoaded', function() {
                    function vpPublicOplDeleteCookie(name) {
                        if ( document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)')) ) {
                            document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/`;
                            alert("<?php echo esc_js($delete_cookie_alert); ?>");
                        }
                        else{
                            alert("<?php echo esc_js($no_cookie_alert); ?>");
                        }
                    }
                    if ( document.getElementById('reset_vp_opl_cookie') ) {
                        document.getElementById('reset_vp_opl_cookie').addEventListener('click', function(e) {
                            e.preventDefault();
                            vpPublicOplDeleteCookie("<?php echo esc_js($get_cookie_name); ?>");
                        });
                    }
                });
            </script>
        <?php endif; ?>

        <?php
    }

    public function field_allowed_post_id_callback($args) {
        $defaults = $this->get_default_settings();
        $option_key = $this->get_const('settings_key') . $this->key_suffix;

        $options = get_option($option_key, $defaults);
        $input_id = isset($args['label_for']) ? $args['label_for'] : '';
        
        if (!isset($options['allowed_post_id'])) {
            $options['allowed_post_id'] = $defaults['allowed_post_id'];
        }

        ?>
        <input type="text" id="<?php echo esc_attr($input_id); ?>" 
               name="<?php echo esc_attr($option_key); ?>[allowed_post_id]" 
               value="<?php echo esc_attr($options['allowed_post_id']); ?>" 
               class="regular-text vp-input-number">
        <p class="vp-option-tagline">
                <?php
                    printf(
                        // translators: %1$s and %3$s is opening link tag, %2$s and %4$s is closing link tag
                        esc_html__('Enter an ID of a single %1$sPost%2$s or %3$sPage%4$s (e.g., 4591). The video popup will always show on this ID, regardless of the display location settings above.', 'video-popup'),
                        '<a href="' . esc_url( admin_url('edit.php?post_type=post') ) . '" target="_blank">',
                        '</a>',
                        '<a href="' . esc_url( admin_url('edit.php?post_type=page') ) . '" target="_blank">',
                        '</a>'
                    );
                ?>
            </p>
        <?php
    }

    public function field_allowed_ids_callback($args) {
        ?>
        <p class="vp-option-tagline">
            <?php
                printf(
                    // translators: %1$s is opening link tag, %2$s is closing link tag
                    esc_html__('With the %1$sPremium Version%2$s, you can show the video popup on multiple specific content by their IDs (e.g., 329,80,6517), regardless of the display location settings above. Unlike the free option "Always show on this ID" which supports only a single post or page ID, the Premium version supports IDs of any content type such as posts, pages, WooCommerce products, and custom post types, with unlimited IDs.', 'video-popup'),
                    '<a href="https://videopopup.net/?utm_source=plugin&utm_medium=link&utm_campaign=opl_settings_page#get-premium" target="_blank">',
                    '</a>'
                );
            ?>
        </p>
        <?php
    }

    public function field_excluded_ids_callback($args) {
        ?>
        <p class="vp-option-tagline">
            <?php
                printf(
                    // translators: %1$s is opening link tag, %2$s is closing link tag
                    esc_html__('With the %1$sPremium Version%2$s, you can hide the video popup on multiple specific content by their IDs (e.g., 912,8301,54), regardless of the display location settings above. It supports IDs of any content type such as posts, pages, products, and custom post types, with unlimited IDs supported.', 'video-popup'),
                    '<a href="https://videopopup.net/?utm_source=plugin&utm_medium=link&utm_campaign=opl_settings_page#get-premium" target="_blank">',
                    '</a>'
                );
            ?>
        </p>
        <?php
    }

    public function field_display_locations_callback($args) {
        $defaults = $this->get_default_settings();
        $option_key = $this->get_const('settings_key') . $this->key_suffix;
        $options = get_option($option_key, $defaults);
        
        if (!isset($options['display_locations']) || !is_array($options['display_locations'])) {
            $options['display_locations'] = array();
        }
        
        $display_locations = $options['display_locations'];
        ?>
        <div class="vp-display-locations-grid">
            <p class="vp-option-tagline">
                <?php esc_html_e('Select where to show the video popup. You can leave all locations unchecked and use the "Always show on this ID" field.', 'video-popup'); ?>
            </p>
            
            <!-- General Locations -->
            <h4><?php esc_html_e('General Locations', 'video-popup'); ?></h4>
            <div class="vp-locations-group">
                <label>
                    <input type="checkbox" 
                        name="<?php echo esc_attr($option_key); ?>[display_locations][]" 
                        value="entire"
                        <?php checked(in_array('entire', $display_locations)); ?>>
                    <?php esc_html_e('Entire Site', 'video-popup'); ?>
                </label>

                <label>
                    <input type="checkbox" 
                        name="<?php echo esc_attr($option_key); ?>[display_locations][]" 
                        value="entire_ex_woo"
                        <?php checked(in_array('entire_ex_woo', $display_locations)); ?>>
                    <?php esc_html_e('Entire Site (excluding WooCommerce pages)', 'video-popup'); ?>
                    <span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('When this option is selected, the video popup will be displayed on the entire site, except on WooCommerce pages and products if the WooCommerce plugin is installed.', 'video-popup'); ?>">?</span>
                </label>

                <label>
                    <input type="checkbox" 
                        name="<?php echo esc_attr($option_key); ?>[display_locations][]" 
                        value="front"
                        <?php checked(in_array('front', $display_locations)); ?>>
                    <?php 
                        printf(
                            // translators: %1$s is opening link tag, %2$s is closing link tag
                            esc_html__('Homepage (will show the video popup on the static page you set in %1$sSettings > Reading%2$s > Homepage)', 'video-popup'),
                            '<a href="' . esc_url( admin_url('options-reading.php') ) . '" target="_blank">',
                            '</a>'
                        );
                    ?>
                </label>

                <label>
                    <input type="checkbox" 
                        name="<?php echo esc_attr($option_key); ?>[display_locations][]" 
                        value="home"
                        <?php checked(in_array('home', $display_locations)); ?>>
                    <?php 
                        printf(
                            // translators: %1$s is opening link tag, %2$s is closing link tag
                            esc_html__('Blog Page (will show the video popup on the page that displays your latest posts, or on the page set in %1$sSettings > Reading%2$s > Posts page)', 'video-popup'),
                            '<a href="' . esc_url( admin_url('options-reading.php') ) . '" target="_blank">',
                            '</a>'
                        );
                    ?>
                </label>
            </div>
            
            <!-- WordPress Content Types -->
            <h4><?php esc_html_e('WordPress Content Types', 'video-popup'); ?></h4>
            <div class="vp-locations-group">
                <label>
                    <input type="checkbox" 
                        name="<?php echo esc_attr($option_key); ?>[display_locations][]" 
                        value="posts"
                        <?php checked(in_array('posts', $display_locations)); ?>>
                    <?php 
                        printf(
                            // translators: %1$s is opening link tag, %2$s is closing link tag
                            esc_html__('%1$sPosts%2$s', 'video-popup'),
                            '<a href="' . esc_url( admin_url('edit.php?post_type=post') ) . '" target="_blank">',
                            '</a>'
                        );
                    ?>
                </label>

                <label>
                    <input type="checkbox" 
                        name="<?php echo esc_attr($option_key); ?>[display_locations][]" 
                        value="pages"
                        <?php checked(in_array('pages', $display_locations)); ?>>
                    <?php 
                        printf(
                            // translators: %1$s is opening link tag, %2$s is closing link tag
                            esc_html__('%1$sPages%2$s', 'video-popup'),
                            '<a href="' . esc_url( admin_url('edit.php?post_type=page') ) . '" target="_blank">',
                            '</a>'
                        );
                    ?>
                </label>

                <label>
                    <input type="checkbox" 
                        name="<?php echo esc_attr($option_key); ?>[display_locations][]" 
                        value="attach"
                        <?php checked(in_array('attach', $display_locations)); ?>>
                    <?php esc_html_e('Attachment Pages', 'video-popup'); ?>
                    <span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('Shows on media attachment pages.', 'video-popup'); ?>">?</span>
                </label>

                <label class="vp-label-star"><?php esc_html_e('Custom Post Types (Premium)', 'video-popup'); ?>
                    <span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('Shows on public custom post types when available. Premium version only.', 'video-popup'); ?>">?</span>
                </label>
            </div>

            <!-- Taxonomies -->
            <h4><?php esc_html_e('WordPress Default Taxonomies', 'video-popup'); ?></h4>
            <div class="vp-locations-group">
                <label>
                    <input type="checkbox" 
                        name="<?php echo esc_attr($option_key); ?>[display_locations][]" 
                        value="cats"
                        <?php checked(in_array('cats', $display_locations)); ?>>
                    <?php esc_html_e('Categories', 'video-popup'); ?>
                    <span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('Shows on category archive pages.', 'video-popup'); ?>">?</span>
                </label>

                <label>
                    <input type="checkbox" 
                        name="<?php echo esc_attr($option_key); ?>[display_locations][]" 
                        value="tags"
                        <?php checked(in_array('tags', $display_locations)); ?>>
                    <?php esc_html_e('Tags', 'video-popup'); ?>
                    <span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('Shows on tag archive pages.', 'video-popup'); ?>">?</span>
                </label>
            </div>

            <!-- Special Pages -->
            <h4><?php esc_html_e('Special Pages', 'video-popup'); ?></h4>
            <div class="vp-locations-group">
                <label>
                    <input type="checkbox" 
                        name="<?php echo esc_attr($option_key); ?>[display_locations][]" 
                        value="search"
                        <?php checked(in_array('search', $display_locations)); ?>>
                    <?php esc_html_e('Search Results', 'video-popup'); ?>
                    <span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('Shows on search results pages.', 'video-popup'); ?>">?</span>
                </label>

                <label>
                    <input type="checkbox" 
                        name="<?php echo esc_attr($option_key); ?>[display_locations][]" 
                        value="error"
                        <?php checked(in_array('error', $display_locations)); ?>>
                    <?php esc_html_e('404 Error Pages (useful for testing and preview)', 'video-popup'); ?>
                    <span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('Shows on 404 error pages.', 'video-popup'); ?>">?</span>
                </label>
            </div>

            <!-- WooCommerce Pages -->
            <h4><?php esc_html_e('WooCommerce Pages', 'video-popup'); ?></h4>
            <div class="vp-locations-group">
                <label class="vp-label-star"><?php esc_html_e('Shop Page (Premium)', 'video-popup'); ?>
                    <span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('Shows on the WooCommerce shop page. Premium version only.', 'video-popup'); ?>">?</span>
                </label>

                <label class="vp-label-star"><?php esc_html_e('Product Pages (Premium)', 'video-popup'); ?>
                    <span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('Shows on individual product pages. Premium version only.', 'video-popup'); ?>">?</span>
                </label>

                <label class="vp-label-star"><?php esc_html_e('Cart Page (Premium)', 'video-popup'); ?>
                    <span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('Shows on the cart page. Premium version only.', 'video-popup'); ?>">?</span>
                </label>

                <label class="vp-label-star"><?php esc_html_e('Checkout Page (Premium)', 'video-popup'); ?>
                    <span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('Shows on the checkout page. Premium version only.', 'video-popup'); ?>">?</span>
                </label>

                <label class="vp-label-star"><?php esc_html_e('My Account Page (Premium)', 'video-popup'); ?>
                    <span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('Shows on the customer account page. Premium version only.', 'video-popup'); ?>">?</span>
                </label>

                <label class="vp-label-star"><?php esc_html_e('Product Categories (Premium)', 'video-popup'); ?>
                    <span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('Shows on product category pages. Premium version only.', 'video-popup'); ?>">?</span>
                </label>

                <label class="vp-label-star"><?php esc_html_e('Product Tags (Premium)', 'video-popup'); ?>
                    <span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('Shows on product tag pages. Premium version only.', 'video-popup'); ?>">?</span>
                </label>

                <label class="vp-label-star"><?php esc_html_e('WooCommerce other pages (Premium)', 'video-popup'); ?>
                    <span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('Shows on WooCommerce pages not listed above. Premium version only.', 'video-popup'); ?>">?</span>
                </label>
            </div>
        </div>
        <?php
    }

    /**
     * Sanitizes and validates all settings
     * Ensures proper data format and generates cookie ID when needed
     * 
     * @param array $input Raw settings from form
     * @return array Sanitized settings
     */
    public function sanitize_settings($input) {
        $option_key = $this->get_const('settings_key') . $this->key_suffix;

        $sanitized = array();
        $defaults = $this->get_default_settings();

        $utils = $this->get_utils();

        // Verify nonce
        if ( !isset($_POST[$this->get_const('settings_nonce').$this->key_suffix]) || 
            !wp_verify_nonce(
                sanitize_key($_POST[$this->get_const('settings_nonce').$this->key_suffix]), 
                $this->get_const('settings_action').$this->key_suffix
            )) {
            add_settings_error(
                $option_key,
                'settings_error',
                esc_html__('Security check failed. Please try saving the settings again. If the problem persists, log out then log back in and try again.', 'video-popup'),
                'error'
            );
            return get_option($option_key, $defaults);
        }

        $sanitized['enable_onpage_load'] = isset($input['enable_onpage_load']) ? $utils->validate_checkbox($input['enable_onpage_load']) : 0;
        $sanitized['debug'] = isset($input['debug']) ? $utils->validate_checkbox($input['debug']) : 0;

        /* Sanitize Video Settings */
        $video_url = false;
        if ( isset($input['video_url']) ) {
            $sanitized['video_url'] = $utils->validate_url($input['video_url']) ?: '';
            $video_url = $sanitized['video_url'] ?: false;
        }

        $is_youtube = false;
        $is_vimeo = false;
        if ( $video_url ) {
            $is_youtube = $utils->is_youtube($video_url);
            $is_vimeo = $utils->is_vimeo($video_url);
        }

        if ($is_youtube) {
            $youtube_video_id = $utils->get_video_id($video_url, 'youtube');
            if ( $youtube_video_id ) {
                $video_url = 'https://www.youtube.com/watch?v=' . $youtube_video_id;
                $sanitized['video_url'] = $video_url;
            }
        }

        if ($is_vimeo) {
            $vimeo_video_id = $utils->get_video_id($video_url, 'vimeo');
            if ( $vimeo_video_id ) {
                $video_url = 'https://vimeo.com/' . $vimeo_video_id;
                $sanitized['video_url'] = $video_url;
            }
        }

        $sanitized['autoplay'] = isset($input['autoplay']) ? $utils->validate_checkbox($input['autoplay']) : 0;
        $sanitized['mute'] = isset($input['mute']) ? $utils->validate_checkbox($input['mute']) : 0;

        $sanitized['no_cookie'] = isset($input['no_cookie']) && ($is_youtube || $is_vimeo) ? $utils->validate_checkbox($input['no_cookie']) : 0;

        $sanitized['wrap_close'] = isset($input['wrap_close']) ? $utils->validate_checkbox($input['wrap_close']) : 0;

        if ( isset($input['user_target']) && in_array($input['user_target'], array('all', 'users', 'visitors')) ) {
            $sanitized['user_target'] = $input['user_target'];
        } else {
            $sanitized['user_target'] = $defaults['user_target'];
        }

        if ( isset($input['cookie_expiry']) ) {
            $cookie_expiry = trim($input['cookie_expiry']);
            $cookie_expiry = $cookie_expiry >= 1 ? true : false;
            $sanitized['cookie_expiry'] = $cookie_expiry ? $utils->validate_number($input['cookie_expiry'], $defaults['cookie_expiry']) : '';
        }

        if ( isset($input['allowed_post_id']) ) {
            $allowed_post_id = trim($input['allowed_post_id']);
            $allowed_post_id = $allowed_post_id >= 1 ? true : false;
            $sanitized['allowed_post_id'] = $allowed_post_id ? $utils->validate_number($input['allowed_post_id'], $defaults['allowed_post_id']) : '';
        }

        if ( isset($sanitized['video_url']) && !empty($sanitized['video_url']) && 
             isset($sanitized['cookie_expiry']) && !empty($sanitized['cookie_expiry']) ) {
            
            $current_options = get_option($option_key, $defaults);
            $current_video_url = isset($current_options['video_url']) ? $current_options['video_url'] : '';
            $current_cookie_id = isset($current_options['cookie_id']) ? $current_options['cookie_id'] : '';
            $current_cookie_expiry = isset($current_options['cookie_expiry']) ? $current_options['cookie_expiry'] : '';

            if ($current_video_url !== $sanitized['video_url'] || $current_cookie_expiry !== $sanitized['cookie_expiry']) {
                $sanitized['cookie_id'] = 'vp_opl_coo_general_'.uniqid().'_'.time();
            } else {
                $sanitized['cookie_id'] = $current_cookie_id;
            }
        } else {
            $sanitized['cookie_id'] = '';
        }

        // Define valid display locations
        $valid_locations = array('entire', 'entire_ex_woo', 'home', 'front', 'posts', 'pages', 'cats', 'tags', 'attach', 'error', 'search');

        // Initialize display_locations array
        $sanitized['display_locations'] = array();

        // Check if display_locations is set and is array
        if (isset($input['display_locations']) && is_array($input['display_locations'])) {
            foreach ($input['display_locations'] as $location) {
                // Only add valid locations
                if (in_array($location, $valid_locations)) {
                    $sanitized['display_locations'][] = $location;
                }
            }
        }

        // If no valid locations selected, set default
        if (empty($sanitized['display_locations'])) {
            $sanitized['display_locations'] = array();
        }

        add_settings_error(
            $option_key,
            'settings_updated',
            esc_html__('Settings saved! If changes are not showing on your site, read the answer to question #1 at the bottom of this page.', 'video-popup'),
            'updated'
        );

        return wp_parse_args($sanitized, $defaults);
    }

    /**
     * Adds client-side validation for critical form fields
     * Handles confirmation prompts when emptying or changing video URL and cookie expiry fields
     * Prevents accidental data loss by confirming changes before submission
     */
    private function page_script() {
        $videoUrlEmptyMsg = __("You are about to clear the Video URL field. This will delete any cookie that was set for the previous video. For more details, read the answer to question #5. Do you want to continue?", 'video-popup');

        $videoUrlChangeMsg = __("You are about to change the Video URL. This will delete any cookies set for the previous video. This means that anyone who watched the previous video but whose waiting period has not yet expired will see the new video, and a new waiting period will start for them. For more details, read the answer to question #5. Do you want to continue?", 'video-popup');

        $cookieExpiryEmptyMsg = __("You are about to clear the Cookie Expiry field. This will remove any wait periods between popup shows, which means the popup will appear on every visit until you set a new expiry period. For more details, read the answer to question #6. Do you want to continue?", 'video-popup');

        $cookieExpiryChangeMsg = __("You are about to change the Cookie Expiry value. This will delete any existing wait period, which means that anyone whose waiting period has not yet expired will see the popup again, and a new waiting period will start for them. For more details, read the answer to question #6. Do you want to continue?", 'video-popup');

        $bothFieldsEmpty = __("Clearing both the Video URL and Cookie Expiry fields will delete any cookie set for the previous video, which means it will permanently remove any existing waiting period. For more details, read the answers to questions #5 and #6. Do you want to continue?", 'video-popup');
        ?>
            <script type="text/javascript">
                document.addEventListener('DOMContentLoaded', function() {

                    function vpOplConfirmUrlAndCookie() {
                        const form = document.querySelector('.vp-settings-form form');
                        if ( !form ) {
                            return;
                        }

                        const cookieExpiryField = document.getElementById('vp_input_cookie_expiry');
                        if ( !cookieExpiryField ) {
                            return;
                        }
                        const cookieExpiryCurrentValue = cookieExpiryField.value ? cookieExpiryField.value.trim() : '';
                        const cookieExpiryEmptyMsg = '<?php echo esc_js($cookieExpiryEmptyMsg); ?>';
                        const cookieExpiryChangeMsg = '<?php echo esc_js($cookieExpiryChangeMsg); ?>';

                        const videoUrlField = document.getElementById('vp_input_video_url');
                        if ( !videoUrlField ) {
                            return;
                        }
                        const videoUrlCurrentValue = videoUrlField.value ? videoUrlField.value.trim() : '';
                        const videoUrlEmptyMsg = '<?php echo esc_js($videoUrlEmptyMsg); ?>';
                        const videoUrlChangeMsg = '<?php echo esc_js($videoUrlChangeMsg); ?>';

                        const bothFieldsEmpty = '<?php echo esc_js($bothFieldsEmpty); ?>';
                        
                        if (form) {
                            form.addEventListener('submit', function(e) {

                                let getVideoUrlValue = videoUrlField.value ? videoUrlField.value.trim() : '';
                                let getCookieExpiryFieldValue = cookieExpiryField.value ? cookieExpiryField.value.trim() : '';

                                let makeConfirm = false;
                                if ( cookieExpiryCurrentValue && videoUrlCurrentValue && (!getVideoUrlValue || getVideoUrlValue !== videoUrlCurrentValue)
                                || cookieExpiryCurrentValue && videoUrlCurrentValue && (!getCookieExpiryFieldValue || getCookieExpiryFieldValue !== cookieExpiryCurrentValue) ) {
                                    makeConfirm = true;
                                }

                                if ( makeConfirm ) {

                                    if ( !getVideoUrlValue && !getCookieExpiryFieldValue ) {
                                        if ( !confirm(bothFieldsEmpty) ) {
                                            e.preventDefault();
                                            if ( !getVideoUrlValue ) {
                                                videoUrlField.value = videoUrlCurrentValue;
                                            }
                                            if ( !getCookieExpiryFieldValue ) {
                                                cookieExpiryField.value = cookieExpiryCurrentValue
                                            }
                                            return;
                                        }
                                    }

                                    else if ( !getVideoUrlValue || getVideoUrlValue !== videoUrlCurrentValue ) {
                                        let videoUrlMsg = !getVideoUrlValue ? videoUrlEmptyMsg : videoUrlChangeMsg;
                                        if ( !confirm(videoUrlMsg) ) {
                                            e.preventDefault();
                                            videoUrlField.value = videoUrlCurrentValue;
                                            return;
                                        }
                                    }

                                    else if ( !getCookieExpiryFieldValue || getCookieExpiryFieldValue !== cookieExpiryCurrentValue ) {
                                        let cookieExpiryMsg = !getCookieExpiryFieldValue ? cookieExpiryEmptyMsg : cookieExpiryChangeMsg;
                                        if ( !confirm(cookieExpiryMsg) ) {
                                            e.preventDefault();
                                            cookieExpiryField.value = cookieExpiryCurrentValue ? cookieExpiryCurrentValue : '';
                                            return;
                                        }
                                    }
                                }

                            });
                        }
                    }

                    vpOplConfirmUrlAndCookie();
                });
            </script>
        <?php
    }

    /**
     * Renders the settings page
     * Displays complete form with all sections and fields
     */
    public function render_page() {
        if ( !get_option($this->get_const('settings_key') . '_first_use') ) {
            $got_it_link = admin_url('admin.php?page=' . $this->get_const('plugin_id') . $this->key_suffix . '&vp_note_got_it=true');
            $note_message = sprintf(
                    /* translators: %1$s is the URL, %2$s is the button text */
                    __('Note after updating to v2:<br><br>If you are using a caching plugin, please clear your cache once to ensure the new Video Popup modal and style load correctly.<br>All your popups created with any version in your content will continue to work seamlessly without the need for any edits! But, if you previously used the old On-Page Load feature, it has been completely re-developed and renamed to "Public On-Page Load", so you will need to enable it and set it up if you wish to use it.<div class="vp-got-it-note"><a href="%1$s" class="button secondary-button">%2$s</a></div>', 'video-popup'),
                    $got_it_link,
                    'Got it'
                );
            add_settings_error(
                $this->get_const('settings_key') . $this->key_suffix,
                'settings_error',
                $note_message,
                'info'
            );
        }
        ?>
            <div class="wrap vp-settings-form vp-opl-settings-form">

                <div class="vp-settings-header">
                    <h1><?php esc_html_e('Public On-Page Load Settings', 'video-popup'); ?></h1>

                    <?php settings_errors($this->get_const('settings_key') . $this->key_suffix); ?>

                    <p>
                        <?php
                            printf(
                                // translators: %1$s is opening link tag, %2$s is closing link tag
                                esc_html__('Set up a video to automatically show as a popup on specific locations. See a %1$slive demo%2$s.', 'video-popup'),
                                '<a href="https://wp-time.com/public-on-page-load-video-popup-live-demo/" target="_blank">',
                                '</a>'
                            );
                        ?>
                    </p>
                </div>

                <form method="post" action="options.php">
                    <?php settings_fields($this->get_const('settings_group') . $this->key_suffix); ?>

                    <?php wp_nonce_field($this->get_const('settings_action') . $this->key_suffix, $this->get_const('settings_nonce') . $this->key_suffix); ?>

                    <div class="vp-settings-section" id="vp-general-options">
                        <?php do_settings_sections($this->get_const('plugin_id') . $this->key_suffix . '_general_settings_section'); ?>
                    </div>

                    <div class="vp-settings-section">
                        <?php do_settings_sections($this->get_const('plugin_id') . $this->key_suffix . '_video_settings_section'); ?>
                    </div>

                    <div class="vp-settings-section">
                        <?php do_settings_sections($this->get_const('plugin_id') . $this->key_suffix . '_appearance_settings_section'); ?>
                    </div>

                    <div class="vp-settings-section">
                        <?php do_settings_sections($this->get_const('plugin_id') . $this->key_suffix . '_display_settings_section'); ?>
                    </div>

                    <div id="vp-submit" class="vp-form-btns">
                        <div><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo esc_attr__('Save Changes', 'video-popup'); ?>"></div>
                    </div>

                    <div class="vp-premium-cta">
                        <div class="vp-premium-content">
                            <div class="vp-premium-icon">
                                <svg viewBox="0 0 90 90" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <defs>
                                        <linearGradient id="vpLogoGrad" x1="0" y1="0.5" x2="1" y2="0.5">
                                            <stop offset="0%" stop-color="#1279f8"></stop>
                                            <stop offset="100%" stop-color="#10b5cb"></stop>
                                        </linearGradient>
                                        <linearGradient id="vpLogoDark" x1="0%" y1="0%" x2="100%" y2="100%">
                                            <stop offset="0%" stop-color="#0a1f5c"></stop>
                                            <stop offset="100%" stop-color="#061240"></stop>
                                        </linearGradient>
                                    </defs>
                                    <circle cx="45" cy="45" r="38" fill="#5aaeff" opacity="0.28"></circle>
                                    <circle cx="45" cy="45" r="32" fill="url(#vpLogoGrad)"></circle>
                                    <circle cx="45" cy="45" r="24" fill="url(#vpLogoDark)"></circle>
                                    <g transform="translate(45,45) scale(0.82) translate(-47,-45)">
                                        <path d="M 38,33 C 36,31.5 36,32.5 36,34.5 L 36,55.5 C 36,57.5 36,58.5 38,57 L 58.5,47.5 C 61,46 61,44 58.5,42.5 Z" fill="#FFFFFF"></path>
                                        <path d="M 38,33 C 36,31.5 36,32.5 36,34.5 L 36,55.5 C 36,57.5 36,58.5 38,57 L 58.5,47.5 C 61,46 61,44 58.5,42.5 Z" fill="url(#vpLogoDark)" opacity="0.05"></path>
                                    </g>
                                </svg>
                            </div>
                            <div class="vp-premium-text">
                                <h3><?php esc_html_e('Get More Control. Get Premium.', 'video-popup'); ?></h3>
                                <p><?php esc_html_e('Make your Video Popups stand out with many features in the Premium version.', 'video-popup'); ?><br>
                                <span class="vp-premium-highlight"><?php esc_html_e('After upgrading to Premium, all your popups created with the free version will continue to work seamlessly without the need for edits! Upgrade with confidence.', 'video-popup'); ?></span></p>
                                <div class="vp-premium-features">
                                    <span><?php esc_html_e('Download Instantly', 'video-popup'); ?></span>
                                    <span><?php esc_html_e('One-Time Payment', 'video-popup'); ?></span>
                                    <span><?php esc_html_e('PayPal Accepted', 'video-popup'); ?></span>
                                    <span><?php esc_html_e('14-Day Money Back Guarantee', 'video-popup'); ?></span>
                                </div>
                            </div>
                            <div class="vp-premium-actions">
                                <a href="https://videopopup.net/?utm_source=plugin&utm_medium=cta&utm_campaign=opl_settings_page#get-premium" target="_blank" class="vp-btn-primary">
                                    <?php esc_html_e('Upgrade Now', 'video-popup'); ?>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                                </a>
                                <a href="https://wp-time.com/video-popup-plugin-for-wordpress/#live-demo" target="_blank" class="vp-btn-secondary">
                                    <?php esc_html_e('30+ Live Demos', 'video-popup'); ?>
                                </a>
                                <a href="https://videopopup.net/?utm_source=plugin&utm_medium=cta&utm_campaign=opl_settings_page#faq" target="_blank" class="vp-btn-secondary">
                                    <?php esc_html_e('FAQ', 'video-popup'); ?>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="vp-settings-section vp-faq-section">
                        <h2><?php esc_html_e('FAQ', 'video-popup'); ?></h2>

                        <h3><?php esc_html_e('Question #1: I\'m using a caching plugin, do I need to clear the cache every time I save changes?!', 'video-popup'); ?></h3>

                        <p>
                            <?php
                                printf(
                                    // translators: %1$s is opening link tag, %2$s is closing link tag
                                    esc_html__('No! All options on this page are dynamic, except for the "%1$sEnable Public On-Page Load%2$s" option above. Clear cache after enabling or disabling the Public On-Page Load.', 'video-popup'),
                                    '<a href="#vp-general-options">',
                                    '</a>'
                                );
                            ?>
                        </p>

                        <h3><?php esc_html_e('Question #2: What\'s the difference between the Public On-Page Load and the On-Page Load Shortcode?', 'video-popup'); ?></h3>
                        <p>
                            <?php
                                printf(
                                    // translators: %1$s is opening link tag, %2$s is closing link tag
                                    esc_html__('With the Public On-Page Load, you can set up a video to automatically show as a popup on multiple locations at once, like all posts or all pages. With the %1$sOn-Page Load Shortcode%2$s (in the premium version), you can show a different popup with a different video on a specific page or post. It works independently from the Public On-Page Load, giving you more flexibility to show multiple popups with different videos!', 'video-popup'),
                                    '<a href="https://wp-time.com/video-popup-plugin-for-wordpress/#on-page-load-shortcode" target="_blank">',
                                    '</a>'
                                );
                            ?>
                        </p>

                        <h3><?php esc_html_e('Question #3: Why does the On-Page Load popup only show after page has finished loading, and not while loading?', 'video-popup'); ?></h3>
                        <p><?php esc_html_e('To ensure the Video Popup Modal is ready, as showing it while page is loading could sometimes cause errors.', 'video-popup'); ?></p>

                        <h3><?php esc_html_e('Question #4: I added a video URL and set Cookie Expiry to 5 days. Many people have already watched the video. Now I want to change the video URL. Will those who watched the previous video see the new one immediately or do they have to wait until the 5-day period expires?', 'video-popup'); ?></h3>

                        <p><?php esc_html_e('Once the video URL is changed, the cookie for the previous video will be deleted, and a new waiting period will start even if you keep the same wait value (5 days). This means that those who watched the previous video will immediately see the new video, and a new 5-day waiting period will begin for them. Also, even if you clear the Video URL, the cookie for that video will be deleted.', 'video-popup'); ?></p>

                        <h3><?php esc_html_e('Question #5: I added a video URL with Cookie Expiry set to 7 days, but later I decided to change the value to 2 days. Will this change take effect immediately?', 'video-popup'); ?></h3>

                        <p><?php esc_html_e('The change will take effect immediately (whether the new value is higher or lower than the current value). This means that even those who watched the video when the expiry was 7 days will see it again, and a new 2-day waiting period will start for them. Also, clearing the value will delete any waiting period, and the popup will appear on every visit regardless of any active waiting period.', 'video-popup'); ?></p>

                        <p>
                        <?php
                            printf(
                                // translators: %1$s is opening link tag, %2$s is closing link tag
                                esc_html__('Finally, we have provided these questions based on what you might encounter while using our plugin. If you have any questions, please feel free to visit the %1$splugin reference page%2$s and contact us.', 'video-popup'),
                                '<a href="https://wp-time.com/video-popup-plugin-for-wordpress/#contact" target="_blank">',
                                '</a>'
                            );
                        ?>
                        </p>

                    </div>

                </form>
            </div>
        <?php
        $this->page_script();
    }

}