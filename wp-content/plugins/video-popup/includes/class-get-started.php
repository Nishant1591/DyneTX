<?php

if ( !defined('ABSPATH') ) {
    exit;
}

/**
 * Handles the Builder page in WordPress admin to help users create and configure video popups.
 * Provides a visual guide and instructions for setting up video popups.
 * This is an informational/tutorial page rather than an actual popup builder interface.
 * Uses singleton pattern to ensure single instance throughout the application.
 * 
 * @author   Alobaidi
 * @since    2.0.4
 */

class Video_Popup_Get_Started {

    private static $instance = null;
    
    /**
     * Retrieves the singleton instance of this class
     * Creates a new instance if one doesn't exist yet
     * 
     * @return Video_Popup_Get_Started The single instance of this class
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
     * Registers necessary hooks for admin menu and styles
     */
    public function run() {
        add_action('admin_menu', array($this, 'add_admin_submenu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_script'));
    }
    
    /**
     * Adds submenu page for builder page
     * Creates documentation page
     */
    public function add_admin_submenu() {
        add_submenu_page(
            $this->get_const('plugin_id'),
            esc_html__('Popup Builder Guide - Video Popup', 'video-popup'),
            esc_html__('Popup Builder Guide', 'video-popup'),
            'manage_options',
            $this->get_const('plugin_id') . '_builder',
            array($this, 'render_page')
        );
    }

    /**
     * Enqueues CSS styles for builder page
     * Loads tooltip and custom styling only on the plugin's reference page
     */
    public function enqueue_admin_script() {
        global $pagenow;
        if ( $pagenow != 'admin.php' ) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reason: only checking $_GET['page'] value to verify current admin page, no data processing.
        if ( !isset($_GET['page']) || isset($_GET['page']) && $_GET['page'] != $this->get_const('plugin_id') . '_builder' ) {
            return;
        }

        if ( !get_option($this->get_const('settings_key') . '_admin_notify') ) {
            update_option($this->get_const('settings_key') . '_admin_notify', 'shown');
        }

        wp_enqueue_style(
            $this->get_const('plugin_id') . '_vp-tooltip-builder-page',
            $this->get_const('plugin_url') . 'includes/css/vp-tooltip.css',
            array(),
            $this->get_const('plugin_version')
        );

        wp_enqueue_style(
            $this->get_const('plugin_id') . '_vp-builder-page-style',
            $this->get_const('plugin_url') . 'includes/css/builder-page-style.css',
            array(),
            $this->get_const('plugin_version')
        );
    }
    
    /**
     * Renders the builder page content with usage instructions
     */
    public function render_page() {
        $shortcode_ref_url = admin_url('admin.php?page=' . $this->get_const('plugin_id') . '_shortcode');
        ?>
        <div class="wrap vp-builder-page">
            <div class="vp-builder-container">
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
                            <a href="https://videopopup.net/?utm_source=plugin&utm_medium=cta&utm_campaign=guide_builder_page#get-premium" target="_blank" class="vp-btn-primary">
                                <?php esc_html_e('Upgrade Now', 'video-popup'); ?>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                            </a>
                            <a href="https://wp-time.com/video-popup-plugin-for-wordpress/#live-demo" target="_blank" class="vp-btn-secondary">
                                <?php esc_html_e('30+ Live Demos', 'video-popup'); ?>
                            </a>
                            <a href="https://videopopup.net/?utm_source=plugin&utm_medium=cta&utm_campaign=guide_builder_page#faq" target="_blank" class="vp-btn-secondary">
                                <?php esc_html_e('FAQ', 'video-popup'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="wrap vp-builder-page">
            <div class="vp-builder-container">
                
                <div class="vp-builder-header">
                    <h1><?php esc_html_e('Popup Builder Guide', 'video-popup'); ?></h1>
                    <p class="vp-builder-intro"><?php esc_html_e('Learn how to create video popups on your site. Follow the simple steps below to get started.', 'video-popup'); ?></p>
                </div>

                <div class="vp-builder-section">
                    <h2 id="create-popup"><?php esc_html_e('How to Create a Video Popup', 'video-popup'); ?></h2>
                    <p><?php esc_html_e('There are two easy ways to create a video popup. You can use the Video Popup Builder in the Classic Editor, which provides a user-friendly interface for creating your popup and inserting it directly into your content. Alternatively, you can use the basic shortcode method, which allows you to add video popups anywhere on your site, including Gutenberg and page builders.', 'video-popup'); ?></p>
                </div>

                <div class="vp-builder-section">
                    <h3 id="video-popup-builder"><?php esc_html_e('1. Using the Video Popup Builder:', 'video-popup'); ?></h3>
                    <p><?php esc_html_e('To create a video popup using the Builder in the Classic Editor, simply select the text that you want the popup to appear when clicked, then click the Video Popup Builder icon in the editor\'s toolbar. The builder interface will open, allowing you to enter the video URL and adjust other settings as you like.', 'video-popup'); ?></p>
                    
                    <div class="vp-builder-screenshots">
                        <h4><?php esc_html_e('Screenshots:', 'video-popup'); ?></h4>
                        <div class="vp-screenshots-grid">
                            <a href="<?php echo esc_url($this->get_const('plugin_url') . 'includes/images/screenshots/video-popup-builder-icon.png'); ?>" target="_blank">
                                <img src="<?php echo esc_url($this->get_const('plugin_url') . 'includes/images/screenshots/video-popup-builder-icon.png'); ?>">
                                <span class="vp-image-caption"><?php esc_html_e('Video Popup Builder Icon', 'video-popup'); ?></span>
                            </a>
                            <a href="<?php echo esc_url($this->get_const('plugin_url') . 'includes/images/screenshots/video-popup-builder.png'); ?>" target="_blank">
                                <img src="<?php echo esc_url($this->get_const('plugin_url') . 'includes/images/screenshots/video-popup-builder.png'); ?>">
                                <span class="vp-image-caption"><?php esc_html_e('Video Popup Builder', 'video-popup'); ?></span>
                            </a>
                            <a href="<?php echo esc_url($this->get_const('plugin_url') . 'includes/images/screenshots/popup-links.png'); ?>" target="_blank">
                                <img src="<?php echo esc_url($this->get_const('plugin_url') . 'includes/images/screenshots/popup-links.png'); ?>">
                                <span class="vp-image-caption"><?php esc_html_e('After creating popups', 'video-popup'); ?></span>
                            </a>
                            <a href="<?php echo esc_url($this->get_const('plugin_url') . 'includes/images/screenshots/classic-block.png'); ?>" target="_blank">
                                <img src="<?php echo esc_url($this->get_const('plugin_url') . 'includes/images/screenshots/classic-block.png'); ?>">
                                <span class="vp-image-caption"><?php esc_html_e('In Gutenberg (using Classic block only)', 'video-popup'); ?></span>
                            </a>
                        </div>
                        <p class="vp-note"><?php esc_html_e('Click on any image to view it in full size.', 'video-popup'); ?></p>
                    </div>

                    <p><?php esc_html_e('When you hover over any field, helpful notes and tips will appear to explain the purpose of the option.', 'video-popup'); ?></p>

                    <div class="vp-info-box">
                        <h4><?php esc_html_e('For editing a video popup link:', 'video-popup'); ?></h4>
                        <p><?php esc_html_e('Simply select the link text, click the Video Popup Builder icon in the editor toolbar, and the builder interface will open, allowing you to edit the popup settings.', 'video-popup'); ?></p>
                    </div>

                    <div class="vp-warning-box">
                        <h4 id="b-tag"><?php esc_html_e('Note:', 'video-popup'); ?></h4>
                        <p><?php esc_html_e('If you select a link and its settings do not appear in the builder, make sure the link or text is not bold (B). If necessary, temporarily remove the bold formatting while editing, then reapply it after finishing the link edit.', 'video-popup'); ?></p>
                    </div>

                    <div class="vp-tip-box">
                        <h4><?php esc_html_e('Useful Plugin - From our plugins:', 'video-popup'); ?></h4>
                        <p><?php esc_html_e('If you want to add more classes or assign an ID to a Video Popup link, use the', 'video-popup'); ?> <a href="https://wordpress.org/plugins/extend-link/" target="_blank"><strong><?php esc_html_e('Extend Link', 'video-popup'); ?></strong></a> <?php esc_html_e('plugin. It\'s free, useful, and easy to use.', 'video-popup'); ?></p>
                        <div class="vp-plugin-image">
                            <a href="<?php echo esc_url($this->get_const('plugin_url') . 'includes/images/screenshots/extend-link-plugin.png'); ?>" target="_blank">
                                <img src="<?php echo esc_url($this->get_const('plugin_url') . 'includes/images/screenshots/extend-link-plugin.png'); ?>">
                            </a>
                            <p class="vp-image-caption"><?php esc_html_e('Extend Link Plugin: Link Options for the Classic Editor - Free!', 'video-popup'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="vp-builder-section">
                    <h3 id="basic-shortcode"><?php esc_html_e('2. Using the Basic Shortcode:', 'video-popup'); ?></h3>
                    <div class="vp-code-block">
                        <code>[video_popup url="https://www.youtube.com/watch?v=_-AS5DtDeqs" text="Watch My Video"]</code>
                    </div>
                    <p><?php printf(
                        // translators: %1$s is opening link tag, %2$s is closing link tag
                        esc_html__('There are many attributes for the basic shortcode. We have explained them in detail on the %1$sShortcode Reference%2$s page.', 'video-popup'),
                        '<a href="' . esc_url($shortcode_ref_url) . '"><strong>',
                        '</strong></a>'
                    ); ?></p>
                </div>

                <div class="vp-builder-section">
                    <h3 id="trigger-vp"><?php esc_html_e('Trigger Video Popup on a Link Click:', 'video-popup'); ?></h3>
                    <p><?php esc_html_e('You can add the', 'video-popup'); ?> <code class="vp-inline-code">vp-a</code> <?php esc_html_e('or', 'video-popup'); ?> <code class="vp-inline-code">vp-s</code> <?php esc_html_e('class to any link anywhere on your site to trigger a video popup on click.', 'video-popup'); ?> <code class="vp-inline-code">vp-a</code> <?php esc_html_e('enables autoplay, and', 'video-popup'); ?> <code class="vp-inline-code">vp-s</code> <?php esc_html_e('disables autoplay. For example:', 'video-popup'); ?></p>
                    
                    <div class="vp-code-block">
                        <p><strong><?php esc_html_e('Autoplay:', 'video-popup'); ?></strong></p>
                        <code>&lt;a href="https://www.youtube.com/watch?v=_-AS5DtDeqs" class="<span class="vp-highlight">vp-a</span>"&gt;Play Video&lt;/a&gt;</code>
                    </div>

                    <div class="vp-code-block">
                        <p><strong><?php esc_html_e('No autoplay:', 'video-popup'); ?></strong></p>
                        <code>&lt;a href="https://www.youtube.com/watch?v=_-AS5DtDeqs" class="<span class="vp-highlight">vp-s</span>"&gt;Play Video&lt;/a&gt;</code>
                    </div>
                </div>

            </div>
        </div>
        <?php
    }

}