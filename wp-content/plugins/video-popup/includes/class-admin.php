<?php

if ( !defined('ABSPATH') ) {
    exit;
}

/**
 * Handles all WordPress admin functionality including menu pages, settings registration,
 * field display, and settings validation. Creates a centralized control panel for all
 * plugin configuration options with proper sanitization and error handling.
 * Uses singleton pattern to ensure single instance throughout the application.
 * 
 * @author   Alobaidi
 * @since    2.0.0
 */

class Video_Popup_Admin {

    private static $instance = null;
    
    /**
     * Retrieves the singleton instance of this class
     * Creates a new instance if one doesn't exist yet
     * 
     * @return Video_Popup_Admin The single instance of this class
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
     * Run the admin functionality by registering hooks
     * Sets up admin menu, registers settings and fields, and enqueues scripts
     */
    public function run() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_init', array($this, 'register_settings_fields'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_script'));
        add_action('admin_head', array($this, 'admin_notify'));
    }

    /**
     * Enqueues admin styles only on the plugin's settings pages
     * Checks current page and GET parameters before loading assets
     */
    public function enqueue_admin_script() {
        global $pagenow;
        if ( $pagenow != 'admin.php' ) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reason: only checking $_GET['page'] value to verify current admin page, no data processing.
        if ( !isset($_GET['page']) || isset($_GET['page']) && $_GET['page'] != $this->get_const('plugin_id') ) {
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
            $this->get_const('plugin_id') . '_vp-tooltip',
            $this->get_const('plugin_url') . 'includes/css/vp-tooltip.css',
            array(),
            $this->get_const('plugin_version')
        );

        wp_enqueue_style(
            $this->get_const('plugin_id') . '_vp-admin-style',
            $this->get_const('plugin_url') . 'includes/css/vp-admin-style.css',
            array(),
            $this->get_const('plugin_version')
        );
    }
    
    /**
     * Registers admin menu and submenu pages
     */
    public function add_admin_menu() {
        $svg_icon = '<svg width="256" height="256" xmlns="http://www.w3.org/2000/svg" fill="none"><path d="M 35.2 0 L 220.8 0 Q 240 0 240 19.2 L 240 140.8 Q 240 160 220.8 160 L 35.2 160 Q 16 160 16 140.8 L 16 19.2 Q 16 0 35.2 0 Z M 102.4 38.4 L 166.4 76.8 L 102.4 115.2 Z" fill="#A7AAAD" fill-rule="evenodd"/><line x1="32" y1="240" x2="224" y2="240" stroke="#f1f1f1" stroke-width="12.8" stroke-linecap="round"/><line x1="32" y1="240" x2="115.2" y2="240" stroke="#A7AAAD" stroke-width="12.8" stroke-linecap="round"/><circle cx="115.2" cy="240" r="16" fill="#A7AAAD" stroke="none"/></svg>';

        $menu_icon = 'data:image/svg+xml;base64,' . base64_encode($svg_icon);

        add_menu_page(
            esc_html__('General Settings - Video Popup', 'video-popup'),
            esc_html__('Video Popup', 'video-popup'),
            'manage_options',
            $this->get_const('plugin_id'),
            '',
            $menu_icon
        );

        add_submenu_page(
            $this->get_const('plugin_id'),
            esc_html__('General Settings - Video Popup', 'video-popup'),
            esc_html__('General Settings', 'video-popup'),
            'manage_options',
            $this->get_const('plugin_id'),
            array($this, 'render_page')
        );
    }

    /**
     * Adds visual admin notifications to the plugin menu
     * when the admin_notify option is not enabled.
     */
    public function admin_notify() {
        if ( !get_option($this->get_const('settings_key') . '_admin_notify') ) {
            ?>
                <style>
                    li.toplevel_page_video_popup > a.toplevel_page_video_popup{
                        background-color: #409b1a !important;
                        font-weight: 500 !important;
                    }
                </style>
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        const vpMainMenuLabel = document.querySelector(
                            'li.toplevel_page_video_popup > a.toplevel_page_video_popup .wp-menu-name'
                        );

                        const vpSubmenuFirstItem = document.querySelector(
                            'li.toplevel_page_video_popup .wp-submenu li.wp-first-item > a'
                        );

                        if (vpMainMenuLabel) {
                            const vpBadgeMain = document.createElement('span');
                            vpBadgeMain.className = 'update-plugins count-1';
                            vpBadgeMain.innerHTML = '<span class="plugin-count">1</span>';
                            vpMainMenuLabel.append(' ', vpBadgeMain);
                        }

                        if (vpSubmenuFirstItem) {
                            const vpBadgeSub = document.createElement('span');
                            vpBadgeSub.className = 'update-plugins count-1';
                            vpBadgeSub.innerHTML = '<span class="plugin-count">1</span>';
                            vpSubmenuFirstItem.append(' ', vpBadgeSub);
                        }
                    });
                </script>
            <?php
        }
    }

    /**
     * Returns default settings for the plugin
     * Used when initializing settings and as fallback values
     * 
     * @return array Default settings with their initial values
     */
    public function get_default_settings() {
        return array(
            'wrap_close' => 0,
            'no_cookie' => 0,
            'enable_js_debug' => 0,
            'editor_style' => 1
        );
    }
    
    /**
     * Registers plugin settings with WordPress
     * Creates settings if they don't exist with default values
     */
    public function register_settings() {
        $default_settings = $this->get_default_settings();
        $version_key = $this->get_const('settings_key') . '_version';
        $plugin_version = $this->get_const('plugin_version');
        
        if ( !get_option($this->get_const('settings_key')) ) {
            add_option($this->get_const('settings_key'), $default_settings);
            add_option($version_key, $plugin_version);
        } else {
            $current_settings = get_option($this->get_const('settings_key'));
            $saved_version = get_option($version_key, '0.0.0');
            if ( version_compare($saved_version, $plugin_version, '<') || count($current_settings) !== count($default_settings) ) {
                $merged_settings = wp_parse_args($current_settings, $default_settings);
                $cleaned_settings = array_intersect_key($merged_settings, $default_settings);
                update_option($this->get_const('settings_key'), $cleaned_settings);
                update_option($version_key, $plugin_version);
            }
        }

        register_setting(
            $this->get_const('settings_group'),
            $this->get_const('settings_key'),
            array(
                'sanitize_callback' => array($this, 'sanitize_settings')
            )
        );
    }

    /**
     * Registers settings fields and sections
     * Groups related settings in sections with descriptive text
     */
    public function register_settings_fields() {
        // General appearance section
        add_settings_section(
            'appearance_section',
            esc_html__('Appearance and Behavior Settings', 'video-popup'),
            function() {
                echo '<p class="vp-settings-section-description">'.esc_html__('These settings control the default options for all video popups on your site, except for the On-Page Load. You can override these settings for each video popup using the Video Popup Builder in the Classic Editor or via Basic Shortcode attributes.', 'video-popup').'</p>';
                echo '<p class="vp-settings-section-description">'.esc_html__('When do these settings apply? These settings apply to any video popup that doesn\'t have appearance and behavior options set, whether it\'s created using the Video Popup Builder or Basic Shortcode. They also apply if incorrect or invalid values are used in the builder or basic shortcode, serving as a fallback to ensure the popup displays correctly. These settings also make it easier to keep a consistent design for all your video popups. Just set the appearance here once, and skip the appearance options when creating new video popups.', 'video-popup').'</p>';
            },
            $this->get_const('plugin_id') . '_general_appearance_settings_section'
        );

        add_settings_field(
            'width_size',
            esc_html__('Width Size (Premium)', 'video-popup'),
            array($this, 'field_width_size_callback'),
            $this->get_const('plugin_id') . '_general_appearance_settings_section',
            'appearance_section',
            array('label_for' => 'vp_input_width_size', 'class' => 'vp-table-tr-custom-class vp-table-tr-star')
        );

        add_settings_field(
            'height_size',
            esc_html__('Height Size (Premium)', 'video-popup'),
            array($this, 'field_height_size_callback'),
            $this->get_const('plugin_id') . '_general_appearance_settings_section',
            'appearance_section',
            array('label_for' => 'vp_input_height_size', 'class' => 'vp-table-tr-custom-class vp-table-tr-star')
        );

        add_settings_field(
            'overlay_color',
            esc_html__('Overlay Color (Premium)', 'video-popup'),
            array($this, 'field_overlay_color_callback'),
            $this->get_const('plugin_id') . '_general_appearance_settings_section',
            'appearance_section',
            array('label_for' => 'vp_input_overlay_color', 'class' => 'vp-table-tr-custom-class vp-table-tr-star')
        );

        add_settings_field(
            'overlay_opacity',
            esc_html__('Overlay Opacity (Premium)', 'video-popup'),
            array($this, 'field_overlay_opacity_callback'),
            $this->get_const('plugin_id') . '_general_appearance_settings_section',
            'appearance_section',
            array('label_for' => 'vp_input_overlay_opacity', 'class' => 'vp-table-tr-custom-class vp-table-tr-star')
        );

        add_settings_field(
            'apply_aam',
            esc_html__('Apply Autoplay & Mute Globally (Premium)', 'video-popup'),
            array($this, 'field_apply_aam_callback'),
            $this->get_const('plugin_id') . '_general_appearance_settings_section',
            'appearance_section',
            array('label_for' => 'vp_input_apply_aam', 'class' => 'vp-table-tr-custom-class vp-table-tr-star')
        );

        add_settings_field(
            'wrap_close',
            esc_html__('Disable Outside Click Close', 'video-popup'),
            array($this, 'field_wrap_close_callback'),
            $this->get_const('plugin_id') . '_general_appearance_settings_section',
            'appearance_section',
            array('label_for' => 'vp_input_wrap_close', 'class' => 'vp-table-tr-custom-class')
        );

        add_settings_field(
            'no_cookie',
            esc_html__('Privacy Mode', 'video-popup'),
            array($this, 'field_no_cookie_callback'),
            $this->get_const('plugin_id') . '_general_appearance_settings_section',
            'appearance_section',
            array('label_for' => 'vp_input_no_cookie', 'class' => 'vp-table-tr-custom-class')
        );

        // General appearance section
        add_settings_section(
            'utility_section',
            esc_html__('Utility Settings', 'video-popup'),
            function() {
                echo '<p class="vp-settings-section-description">' . esc_html__('Enable or disable JavaScript debug mode and the editor style.', 'video-popup') . '</p>';
            },
            $this->get_const('plugin_id') . '_general_utility_settings_section'
        );

        // JS debug checkbox
        add_settings_field(
            'enable_js_debug',
            esc_html__('Enable JS Debug Mode', 'video-popup'),
            array($this, 'field_enable_js_debug_callback'),
            $this->get_const('plugin_id') . '_general_utility_settings_section',
            'utility_section',
            array('label_for' => 'vp_input_enable_js_debug', 'class' => 'vp-table-tr-custom-class')
        );

        // Enable editor style checkbox
        add_settings_field(
            'editor_style',
            esc_html__('Enable Editor Style', 'video-popup'),
            array($this, 'field_editor_style_callback'),
            $this->get_const('plugin_id') . '_general_utility_settings_section',
            'utility_section',
            array('label_for' => 'vp_input_editor_style', 'class' => 'vp-table-tr-custom-class')
        );
    }

    /**
     * Sanitizes and validates submitted settings
     * Ensures all values meet expected formats and constraints
     * 
     * @param array $input Raw settings input from form submission
     * @return array Sanitized settings with defaults applied for missing values
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        $defaults = $this->get_default_settings();

        $utils = $this->get_utils();

        // Verify nonce
        if ( !isset($_POST[$this->get_const('settings_nonce')]) || 
            !wp_verify_nonce(
                sanitize_key($_POST[$this->get_const('settings_nonce')]), 
                $this->get_const('settings_action')
            )) {
            add_settings_error(
                $this->get_const('settings_key'),
                'settings_error',
                esc_html__('Security check failed. Please try saving the settings again. If the problem persists, log out then log back in and try again.', 'video-popup'),
                'error'
            );
            return get_option($this->get_const('settings_key'), $defaults);
        }
        
        $sanitized['wrap_close'] = isset($input['wrap_close']) ? $utils->validate_checkbox($input['wrap_close']) : 0;
        $sanitized['no_cookie'] = isset($input['no_cookie']) ? $utils->validate_checkbox($input['no_cookie']) : 0;

        $sanitized['enable_js_debug'] = isset($input['enable_js_debug']) ? $utils->validate_checkbox($input['enable_js_debug']) : 0;
        $sanitized['editor_style'] = isset($input['editor_style']) ? $utils->validate_checkbox($input['editor_style']) : 0;

        add_settings_error(
            $this->get_const('settings_key'),
            'settings_updated',
            esc_html__('Settings saved! If changes are not showing on your site, please clear your cache.', 'video-popup'),
            'updated'
        );

        return wp_parse_args($sanitized, $defaults);
    }

    public function field_width_size_callback($args) {
        ?>
            <p class="vp-option-tagline">
                <?php
                    printf(
                        // translators: %1$s is opening link tag, %2$s is closing link tag
                        esc_html__('With the %1$sPremium Version%2$s, you can set a custom popup width with support for pixels (px), percentages (%%), and viewport width (vw).', 'video-popup'),
                        '<a href="https://videopopup.net/?utm_source=plugin&utm_medium=link&utm_campaign=general_settings_page#get-premium" target="_blank">',
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
                        '<a href="https://videopopup.net/?utm_source=plugin&utm_medium=link&utm_campaign=general_settings_page#get-premium" target="_blank">',
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
                        '<a href="https://videopopup.net/?utm_source=plugin&utm_medium=link&utm_campaign=general_settings_page#get-premium" target="_blank">',
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
                        '<a href="https://videopopup.net/?utm_source=plugin&utm_medium=link&utm_campaign=general_settings_page#get-premium" target="_blank">',
                        '</a>'
                    );
                ?>
            </p>
        <?php
    }

    public function field_apply_aam_callback($args){
        ?>
            <p class="vp-option-tagline">
                <?php
                    printf(
                        // translators: %1$s is opening link tag, %2$s is closing link tag
                        esc_html__('With the %1$sPremium Version%2$s, you can automatically apply autoplay and mute to all existing and future video popups at once from a single flexible option, ensuring consistent behavior without editing each popup individually, and if you disable it later, all video popups will return to how they were before. Flexibility, simplicity, and time-saving!', 'video-popup'),
                        '<a href="https://videopopup.net/?utm_source=plugin&utm_medium=link&utm_campaign=general_settings_page#get-premium" target="_blank">',
                        '</a>'
                    );
                ?>
            </p>
        <?php
    }

    /**
     * Renders wrap close toggle switch
     * Controls outside click behavior
     * @param array $args Field arguments
     */
    public function field_wrap_close_callback($args) {
        $defaults = $this->get_default_settings();
        $options = get_option($this->get_const('settings_key'), $defaults);

        $input_id = $args && isset($args['label_for']) ? $args['label_for'] : '';

        if ( !isset($options['wrap_close']) ) {
            $options['wrap_close'] = $defaults['wrap_close'];
        }

        ?>
            <label class="vp-switch-container">
                <input class="vp-switch-input" type="checkbox" id="<?php echo esc_attr($input_id); ?>" name="<?php echo esc_attr($this->get_const('settings_key')); ?>[wrap_close]" value="1" <?php checked(1, $options['wrap_close']); ?>>
                <span class="vp-switch-slider"></span>
            </label>
            <p class="vp-option-tagline"><?php esc_html_e('Prevents popup from closing when clicking outside the video area (overlay background).', 'video-popup'); ?><span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('When this option is enabled, users must use the close button or ESC key to close the popup.', 'video-popup'); ?>">?</span>

                <br>

                <?php esc_html_e('If this option is enabled, you can override it in the basic shortcode using wc="0" (0 = disable, 1 = enable).', 'video-popup'); ?>
            </p>
        <?php
    }

    /**
     * Renders no-cookie mode toggle for GDPR compliance
     * @param array $args Field arguments
     */
    public function field_no_cookie_callback($args) {
        $defaults = $this->get_default_settings();
        $options = get_option($this->get_const('settings_key'), $defaults);

        $input_id = $args && isset($args['label_for']) ? $args['label_for'] : '';

        if ( !isset($options['no_cookie']) ) {
            $options['no_cookie'] = $defaults['no_cookie'];
        }

        ?>
            <label class="vp-switch-container">
                <input class="vp-switch-input" type="checkbox" id="<?php echo esc_attr($input_id); ?>" name="<?php echo esc_attr($this->get_const('settings_key')); ?>[no_cookie]" value="1" <?php checked(1, $options['no_cookie']); ?>>
                <span class="vp-switch-slider"></span>
            </label>
            <p class="vp-option-tagline"><?php esc_html_e('Prevent YouTube and Vimeo from storing cookies in the user\'s browser. GDPR-compliant. This option is for YouTube and Vimeo only.', 'video-popup'); ?><span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('Direct video formats such as MP4 and WebM will not be affected by enabling or disabling this option.', 'video-popup'); ?>">?</span>

                <br>

                <?php esc_html_e('If this option is enabled, you can override it in the basic shortcode using nc="0" (0 = disable, 1 = enable).', 'video-popup'); ?>
            </p>
        <?php
    }

    /**
     * Renders JavaScript debug mode toggle
     * @param array $args Field arguments
     */
    public function field_enable_js_debug_callback($args) {
        $defaults = $this->get_default_settings();
        $options = get_option($this->get_const('settings_key'), $defaults);

        $input_id = $args && isset($args['label_for']) ? $args['label_for'] : '';

        if ( !isset($options['enable_js_debug']) ) {
            $options['enable_js_debug'] = $defaults['enable_js_debug'];
        }

        ?>
            <label class="vp-switch-container">
                <input class="vp-switch-input" type="checkbox" id="<?php echo esc_attr($input_id); ?>" name="<?php echo esc_attr($this->get_const('settings_key')); ?>[enable_js_debug]" value="1" <?php checked(1, $options['enable_js_debug']); ?>>
                <span class="vp-switch-slider"></span>
            </label>
            <p class="vp-option-tagline"><?php esc_html_e('Enable this option to show debug logs in the browser console. Logs are visible only after opening a video popup link, and you must be logged in. To view them, step by step: Open your site > Click on any video popup link in your content or the one with the issue > Press F12 > Check the Console tab.', 'video-popup'); ?><span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('We recommend using Google Chrome while debugging. Note: This option does not apply to the On-Page Load, which has its own debug option.', 'video-popup'); ?>">?</span></p>
        <?php
    }

    /**
     * Renders editor style toggle for classic editor
     * @param array $args Field arguments
     */
    public function field_editor_style_callback($args) {
        $defaults = $this->get_default_settings();
        $options = get_option($this->get_const('settings_key'), $defaults);

        $input_id = $args && isset($args['label_for']) ? $args['label_for'] : '';

        if ( !isset($options['editor_style']) ) {
            $options['editor_style'] = $defaults['editor_style'];
        }

        ?>
            <label class="vp-switch-container">
                <input class="vp-switch-input" type="checkbox" id="<?php echo esc_attr($input_id); ?>" name="<?php echo esc_attr($this->get_const('settings_key')); ?>[editor_style]" value="1" <?php checked(1, $options['editor_style']); ?>>
                <span class="vp-switch-slider"></span>
            </label>
            <p class="vp-option-tagline"><?php esc_html_e('Enable or disable the custom editor style for video popup links in the Classic Editor.', 'video-popup'); ?><span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('When this option is enabled, video popup links will appear styled in the Classic Editor to make them easier to recognize inside your content. For example: red background for YouTube popup links, blue for Vimeo, gray for MP4, and purple for WebM.', 'video-popup'); ?>">?</span></p>
        <?php
    }
    
    /**
     * Renders the admin settings page
     * Creates form with all registered settings sections and fields
     */
    public function render_page() {
        $opl_settings_url = admin_url('admin.php?page=' . $this->get_const('plugin_id') . '_onpage_load');

        if ( !get_option($this->get_const('settings_key') . '_first_use') ) {
            $got_it_link = admin_url('admin.php?page=' . $this->get_const('plugin_id') . '&vp_note_got_it=true');
            $note_message = sprintf(
                    /* translators: %1$s is the URL, %2$s is the button text */
                    __('Note after updating to v2:<br><br>If you are using a caching plugin, please clear your cache once to ensure the new Video Popup modal and style load correctly.<br>All your popups created with any version in your content will continue to work seamlessly without the need for any edits! But, if you previously used the old On-Page Load feature, it has been completely re-developed and renamed to "Public On-Page Load", so you will need to enable it and set it up if you wish to use it.<div class="vp-got-it-note"><a href="%1$s" class="button secondary-button">%2$s</a></div>', 'video-popup'),
                    $got_it_link,
                    'Got it'
                );
            add_settings_error(
                $this->get_const('settings_key'),
                'settings_error',
                $note_message,
                'info'
            );
        }
        ?>
            <div class="wrap vp-settings-form vp-general-settings-form">
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
                                <a href="https://videopopup.net/?utm_source=plugin&utm_medium=cta&utm_campaign=general_settings_page#get-premium" target="_blank" class="vp-btn-primary">
                                    <?php esc_html_e('Upgrade Now', 'video-popup'); ?>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                                </a>
                                <a href="https://wp-time.com/video-popup-plugin-for-wordpress/#live-demo" target="_blank" class="vp-btn-secondary">
                                    <?php esc_html_e('30+ Live Demos', 'video-popup'); ?>
                                </a>
                                <a href="https://videopopup.net/?utm_source=plugin&utm_medium=cta&utm_campaign=general_settings_page#faq" target="_blank" class="vp-btn-secondary">
                                    <?php esc_html_e('FAQ', 'video-popup'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
            </div>

            <div class="wrap vp-settings-form vp-general-settings-form">

                <div class="vp-settings-header">
                    <h1><?php esc_html_e('General Settings', 'video-popup'); ?></h1>

                    <?php settings_errors($this->get_const('settings_key')); ?>
                    
                    <p><?php esc_html_e('The general settings are for the Video Popup Builder and Basic Shortcode &#91;video_popup].', 'video-popup'); ?></p>

                    <p style="font-size: 14px;">
                        <?php
                            printf(
                                // translators: %1$s is opening link tag, %2$s is closing link tag
                                esc_html__('Note: These settings do not apply to the %1$sPublic On-Page Load%2$s.', 'video-popup'),
                                '<a href="' . esc_url($opl_settings_url) . '">',
                                '</a>'
                            );
                        ?>
                    </p>
                </div>

                <form method="post" action="options.php">
                    <?php settings_fields($this->get_const('settings_group')); ?>

                    <?php wp_nonce_field($this->get_const('settings_action'), $this->get_const('settings_nonce')); ?>

                    <div class="vp-settings-section">
                        <?php do_settings_sections($this->get_const('plugin_id') . '_general_appearance_settings_section'); ?>
                    </div>

                    <div class="vp-settings-section">
                        <?php do_settings_sections($this->get_const('plugin_id') . '_general_utility_settings_section'); ?>
                    </div>

                    <div id="vp-submit" class="vp-form-btns">
                        <div><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo esc_attr__('Save Changes', 'video-popup'); ?>"></div>
                        <div class="vp-premium-btn"><a href="https://videopopup.net/?utm_source=plugin&utm_medium=button&utm_campaign=general_settings_page#get-premium" target="_blank" class="button secondary-button vp-get-premium"><?php esc_html_e('Get Premium Version', 'video-popup'); ?></a></div>
                    </div>

                </form>
            </div>
        <?php
    }

}