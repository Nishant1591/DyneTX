<?php

if ( !defined('ABSPATH') ) {
    exit;
}

/**
 * Integrates the plugin with WordPress TinyMCE editor
 * Adds custom button and dialog for inserting video popups in classic editor
 * 
 * @author   Alobaidi
 * @since    2.0.0
 */

class Video_Popup_TinyMCE {

    private static $instance = null;
    
    /**
     * Retrieves the singleton instance of this class
     * Creates a new instance if one doesn't exist yet
     * 
     * @return Video_Popup_TinyMCE The single instance of this class
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
     * Run the TinyMCE functionality by registering hooks
     * Sets up editor integration points with WordPress
     */
    public function run() {
        add_action('admin_init', array($this, 'add_editor_style'));
        add_filter('mce_external_plugins', array($this, 'register_tinymce_plugin'));
        add_filter('mce_buttons', array($this, 'add_tinymce_button'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_tinymce_script'));
    }

    /**
     * Add custom CSS style to the classic editor
     * Makes video popup links visually distinct in editor
     */
    public function add_editor_style() {
        $options = $this->get_const('settings_key') ? get_option($this->get_const('settings_key'), array()) : false;
        $has_options = is_array($options) && !empty($options) ? true : false;

        $is_enable_editor_style = $has_options && isset($options['editor_style']) && $options['editor_style'] == 1 ? true : false;

        if ( $is_enable_editor_style ) {
            $editor_style_version = $this->get_const('plugin_version');
            add_editor_style($this->get_const('plugin_url') . 'includes/css/editor-style.css?ver=' . $editor_style_version);
        }
    }
    
    /**
     * Register TinyMCE plugin JavaScript file
     * Adds custom JS file that defines button behavior
     * 
     * @param array $plugin_array Existing TinyMCE plugins
     * @return array Modified plugins array with our custom plugin
     */
    public function register_tinymce_plugin($plugin_array) {
        if ( $this->get_const('plugin_id') === null ) return $plugin_array;

        $button_script_version = $this->get_const('plugin_version');
        
        $plugin_array[$this->get_const('plugin_id') . '_tinymce_plugin'] = $this->get_const('plugin_url') . 'includes/js/vp-tinymce-button-script.js?ver=' . $button_script_version;
        
        return $plugin_array;
    }

    /**
     * Add custom button to TinyMCE toolbar
     * 
     * @param array $buttons Existing toolbar buttons
     * @return array Modified buttons array with our custom button
     */
    public function add_tinymce_button($buttons) {
        if ( $this->get_const('plugin_id') === null ) return $buttons;

        array_push($buttons, $this->get_const('plugin_id') . '_tinymce_plugin');

        return $buttons;
    }
    
    /**
     * Enqueue TinyMCE scripts and styles
     * Only loads on post edit screens for better performance
     */
    public function enqueue_tinymce_script() {
        // Only load on post edit screens
        global $pagenow;
        if ( !in_array($pagenow, array('post.php', 'post-new.php')) ) {
            return;
        }

        wp_enqueue_style(
            $this->get_const('plugin_id') . '_tinymce-button-style',
            $this->get_const('plugin_url') . 'includes/css/vp-tinymce-button-style.css',
            array(),
            $this->get_const('plugin_version')
        );

        wp_register_script(
            'vp-tinymce-translations',
            false,
            array(),
            $this->get_const('plugin_version'),
            true
        );
        wp_enqueue_script('vp-tinymce-translations');
        wp_add_inline_script('vp-tinymce-translations', 'var vpTinyMCELang = ' . json_encode($this->get_tinymce_texts()) . ';');
    }
    
    /**
     * Get translated text strings for TinyMCE dialog
     * Also provides configuration options from plugin settings
     * 
     * @return array Translated text strings and configuration values
     */
    public function get_tinymce_texts() {
        $general_settings_url = admin_url('admin.php?page=' . $this->get_const('plugin_id'));
        $builder_guide_page_url = admin_url('admin.php?page=' . $this->get_const('plugin_id') . '_builder');
        $onpage_load_settings_url = admin_url('admin.php?page=' . $this->get_const('plugin_id') . '_onpage_load');
        $shortcode_usage_page_url = admin_url('admin.php?page=' . $this->get_const('plugin_id') . '_shortcode');

        $options = $this->get_const('settings_key') ? get_option($this->get_const('settings_key'), array()) : false;
        $has_options = is_array($options) && !empty($options) ? true : false;

        $global_no_cookie = $has_options && isset($options['no_cookie']) && $options['no_cookie'] == 1 ? 'true' : 'false';
        $global_wrap_close = $has_options && isset($options['wrap_close']) && $options['wrap_close'] == 1 ? 'true' : 'false';

        return array(
            'global_no_cookie' => $global_no_cookie,
            'global_wrap_close' => $global_wrap_close,

            'error_no_selection' => __('Please select text or link first!', 'video-popup'),

            'dialog_title' => __('Video Popup Builder', 'video-popup'),
            'button_title' => __('Create a YouTube, Vimeo, or direct video popup.', 'video-popup'),

            'text_label' => __('Text', 'video-popup'),
            'text_tooltip' => __('The clickable text.', 'video-popup'),
            'default_text' => 'Video',

            'title_label' => __('Title', 'video-popup'),
            'title_tooltip' => __('Tooltip text that appears when hover over the link.', 'video-popup'),

            'url_label' => __('Video URL', 'video-popup'),
            'url_tooltip' => __('Enter a YouTube, Vimeo, or direct video URL (MP4 or WebM only). YouTube Shorts links are also supported. For more information on URL formats, go to the Shortcode Reference > Supported Video Sources.', 'video-popup'),

            'img_url_label' => __('Custom Video Preview Image URL', 'video-popup'),
            'img_url_tooltip' => __('Enter an image URL to display it as a clickable video preview image instead of text.', 'video-popup'),
            
            'shortcode_label' => __('Basic Shortcode', 'video-popup'),
            'shortcode_tooltip' => __('The basic shortcode allows you to create video popup links, just like the ones created by this builder. It serves as an alternative solution for websites that do not support the Classic Editor, and it is a perfect solution for Gutenberg and page builders. For more details, go to the "Shortcode Reference".', 'video-popup'),

            'opl_shortcode_label' => __('On-Page Load Shortcode (Premium)', 'video-popup'),
            'opl_shortcode_tooltip' => __('With the Premium Version, there is a [video_popup_opl] shortcode that shows a video popup automatically after page has finished loading, without requiring a click. It works independently from the Public On-Page Load. For more details, go to the "Shortcode Reference" > "On-Page Load Shortcode" section.', 'video-popup'),

            'autoplay_label' => __('Autoplay', 'video-popup'),
            'autoplay_tooltip' => __('Video starts playing automatically when the popup opens. Please note, autoplay may not work unless the mute option is enabled. However, on mobile phones and tablets, autoplay may not work even when the mute option is enabled, or it may behave inconsistently, working sometimes and failing at other times, due to browser autoplay policies.', 'video-popup'),

            'mute_label' => __('Mute', 'video-popup'),
            'mute_tooltip' => __('Mute video sound.', 'video-popup'),

            'disable_related_label' => __('Limit YouTube Related Videos (Premium)', 'video-popup'),
            'disable_related_tooltip' => __('With the Premium Version, you can limit related videos to the same channel instead of showing all YouTube videos. Note: YouTube no longer allows completely hiding related videos since September 2018, but this option helps keep viewers within the same channel content. This option is for YouTube only.', 'video-popup'),

            'disable_controls_label' => __('Hide Player Controls (Premium)', 'video-popup'),
            'disable_controls_tooltip' => __('With the Premium Version, you can hide the player controls on YouTube, Vimeo, and supported direct video formats.', 'video-popup'),
            
            'start_time_label' => __('Start Time (Premium)', 'video-popup'),
            'start_time_tooltip' => __('With the Premium Version, you can start a YouTube or Vimeo video at a specific time. Supported formats: Seconds (e.g., 90), Time format (MM:SS, e.g., 01:30 or HH:MM:SS, e.g., 01:15:45), Duration format (e.g., 1m, 1m30s, 1h, 1h30s, 1h40m, or 1h15m45s). This option is for YouTube and Vimeo only.', 'video-popup'),

            'end_time_label' => __('End Time (Premium)', 'video-popup'),
            'end_time_tooltip' => __('With the Premium Version, you can stop a YouTube video at a specific time. Supported formats: Seconds (e.g., 300), Time format (MM:SS, e.g., 05:00 or HH:MM:SS, e.g., 02:30:00), Duration format (e.g., 5m, 5m20s, 2h, 2h15s, 2h30m, or 2h30m10s). This option is for YouTube only.', 'video-popup'),

            'minimize_design_label' => __('Popup Design (Premium)', 'video-popup'),
            'minimize_design_tooltip' => __('With the Premium Version, you can display a video popup in a small, elegant design at the bottom of the page, on either the left or right side.', 'video-popup'),
            'minimize_none' => __('Basic Design', 'video-popup'),
            'minimize_left' => __('Minimized Design on the Left Side (Premium)', 'video-popup'),
            'minimize_right' => __('Minimized Design on the Right Side (Premium)', 'video-popup'),

            'width_label' => __('Width Size (Premium)', 'video-popup'),
            'width_tooltip' => __('With the Premium Version, you can set a custom popup width with support for pixels (px), percentages (%), and viewport width (vw).', 'video-popup'),

            'height_label' => __('Height Size (Premium)', 'video-popup'),
            'height_tooltip' => __('With the Premium Version, you can set a custom popup height with support for pixels (px), percentages (%), and viewport height (vh).', 'video-popup'),

            'aspect_ratio_label' => __('Apply Aspect-Ratio 16:9 (Premium)', 'video-popup'),
            'aspect_ratio_tooltip' => __('With the Premium Version, you can apply a 16:9 aspect ratio by entering a width in pixels and leaving the height field empty. The height will be calculated automatically based on the width. Simple!', 'video-popup'),
            
            'overlay_color_label' => __('Overlay Color (Premium)', 'video-popup'),
            'overlay_color_tooltip' => __('With the Premium Version, you can set a custom overlay color using HEX format (e.g., #FFFFFF for white).', 'video-popup'),
            
            'overlay_opacity_label' => __('Overlay Opacity (Premium)', 'video-popup'),
            'overlay_opacity_tooltip' => __('With the Premium Version, you can adjust overlay transparency from 0 (fully transparent) to 100 (completely opaque). You can even create a blur backdrop effect by combining low opacity (e.g., 20) with black (#000000) or white (#FFFFFF) overlay color.', 'video-popup'),

            'show_thumbnail_label' => __('Display YouTube Video Preview Image', 'video-popup'),
            'show_thumbnail_tooltip' => __('Displays youtube video preview image as the clickable element instead of text. This option is for YouTube only. Note: If the image does not appear or a default placeholder is shown, it means the video was deleted or the image is no longer available. Additionally, if the video owner changes the image in the future, the new image may appear.', 'video-popup'),
            
            'no_cookie_label' => __('Privacy Mode', 'video-popup'),
            'no_cookie_tooltip' => __('Prevent YouTube and Vimeo from storing cookies in the user\'s browser. GDPR-compliant. This option is for YouTube and Vimeo only.', 'video-popup'),
            'no_cookie_note_label' => __('Note: The "Privacy Mode" option is enabled here by default because it was enabled in the general settings. You can disable it here to override it for this video popup.', 'video-popup'),

            'disable_wrap_close_label' => __('Disable Outside Click Close', 'video-popup'),
            'disable_wrap_close_tooltip' => __('Prevents popup from closing when clicking outside the video area (overlay background). When this option is enabled, users must use the close button or ESC key to close the popup.', 'video-popup'),
            'close_wrap_note_label' => __('Note: The "Disable Outside Click Close" option is enabled here by default because it was enabled in the general settings. You can disable it here to override it for this video popup.', 'video-popup'),

            'rel_nofollow_label' => __('Rel Nofollow', 'video-popup'),
            'rel_nofollow_tooltip' => __('Adds rel="nofollow" attribute to prevent search engines from following this link.', 'video-popup'),

            'error_youtube_only_thumbnail' => __('Display YouTube Video Preview Image option are available for YouTube videos only.', 'video-popup'),

            'error_no_url' => __('Please enter a YouTube, Vimeo, or direct video URL (supports only MP4 and WebM formats).', 'video-popup'),
            'error_unsupported_url' => __('Please enter a valid video URL! YouTube, Vimeo, or direct video URL (supports only MP4 and WebM formats).', 'video-popup'),

            'error_youtube_vimeo_only_nocookie' => __('Privacy mode is available for YouTube and Vimeo videos only.', 'video-popup'),

            'general_settings_button' => __('General Settings', 'video-popup'),
            'general_settings_button_tooltip' => __('Set up the default appearance and behavior for all video popups, except for the On-Page Load.', 'video-popup'),
            
            'onpage_load_settings_button' => __('Public On-Page Load', 'video-popup'),
            'onpage_load_settings_button_tooltip' => __('Set up a video to automatically show as a popup on specific locations.', 'video-popup'),

            'shortcode_usage_button' => __('Shortcode Reference', 'video-popup'),
            'shortcode_usage_button_tooltip' => __('Shortcode attributes and usage guide.', 'video-popup'),

            'get_demo_button' => __('30+ Live Demos', 'video-popup'),
            'get_demo_button_tooltip' => __('Explore 30+ live demos, including the minimized design and On-Page Load demos.', 'video-popup'),

            'get_bldr_guide_button' => __('Builder Guide', 'video-popup'),
            'get_bldr_guide_button_tooltip' => __('Learn how to create video popups on your site. Follow simple steps to get started.', 'video-popup'),

            'get_premium_button' => __('Get Premium Version', 'video-popup'),
            'get_premium_button_tooltip' => __('After upgrading to Premium, all your popups created with the free version will continue to work seamlessly without the need for edits! Upgrade with confidence.', 'video-popup'),

            'gs_settings_url' => $general_settings_url,
            'opl_settings_url' => $onpage_load_settings_url,
            'bldr_guide_url' => $builder_guide_page_url,
            'scu_page_url' => $shortcode_usage_page_url
        );
    }

}