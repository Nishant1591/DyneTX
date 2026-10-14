<?php

if ( !defined('ABSPATH') ) {
    exit;
}

/**
 * Creates a dedicated admin page displaying comprehensive shortcode documentation
 * Provides examples, attribute descriptions, and usage instructions for plugin shortcodes
 * 
 * @author   Alobaidi
 * @since    2.0.0
 */

class Video_Popup_Shortcode_Reference_Page {

    private static $instance = null;
    
    /**
     * Retrieves the singleton instance of this class
     * Creates a new instance if one doesn't exist yet
     * 
     * @return Video_Popup_Shortcode_Reference_Page The single instance of this class
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
     * Run the shortcode reference page functionality
     * Registers necessary hooks for admin menu and styles
     */
    public function run() {
        add_action('admin_menu', array($this, 'add_admin_submenu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_script'));
    }

    /**
     * Adds shortcode reference submenu page to plugin menu
     * Creates documentation page accessible to users with manage_options capability
     */
    public function add_admin_submenu() {
        add_submenu_page(
            $this->get_const('plugin_id'),
            esc_html__('Shortcode Reference - Video Popup', 'video-popup'),
            esc_html__('Shortcode Reference', 'video-popup'),
            'manage_options',
            $this->get_const('plugin_id') . '_shortcode',
            array($this, 'render_page')
        );
    }

    /**
     * Enqueues CSS styles for shortcode reference page
     * Loads tooltip and custom styling only on the plugin's reference page
     */
    public function enqueue_admin_script() {
        global $pagenow;
        if ( $pagenow != 'admin.php' ) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reason: only checking $_GET['page'] value to verify current admin page, no data processing.
        if ( !isset($_GET['page']) || isset($_GET['page']) && $_GET['page'] != $this->get_const('plugin_id') . '_shortcode' ) {
            return;
        }

        if ( !get_option($this->get_const('settings_key') . '_admin_notify') ) {
            update_option($this->get_const('settings_key') . '_admin_notify', 'shown');
        }

        wp_enqueue_style(
            $this->get_const('plugin_id') . '_vp-tooltip-shortcode-page',
            $this->get_const('plugin_url') . 'includes/css/vp-tooltip.css',
            array(),
            $this->get_const('plugin_version')
        );

        wp_enqueue_style(
            $this->get_const('plugin_id') . '_vp-shortcode-page-style',
            $this->get_const('plugin_url') . 'includes/css/shortcode-page-style.css',
            array(),
            $this->get_const('plugin_version')
        );
    }

    /**
     * Renders the shortcode reference page content
     * Displays formatted documentation with examples and tooltips
     */
    public function render_page() {
        $general_settings_url = admin_url('admin.php?page=' . $this->get_const('plugin_id'));

        $general_settings = sprintf(
            // translators: %1$s is opening link tag, %2$s is closing link tag
            esc_html__('(or value you set in %1$sgeneral settings%2$s for basic shortcode and builder)', 'video-popup'),
            '<a href="' . esc_url($general_settings_url) . '" target="_blank">',
            '</a>'
        );

        $opl_settings_url = admin_url('admin.php?page=' . $this->get_const('plugin_id') . '_onpage_load');
        ?>
        <div class="wrap vp-shortcode-page">

            <h1><?php esc_html_e('Shortcode Reference', 'video-popup'); ?></h1>

            <p style="padding: 0; font-size: 18px; margin: 0 0 30px 0;">
                <?php
                    printf(
                        // translators: %1$s is opening link tag, %2$s is closing link tag
                        esc_html__('On this page, you will find an explanation of the shortcode features and attributes, along with examples and their outputs. You can also find more %1$slive demos%2$s.', 'video-popup'),
                            '<a href="https://wp-time.com/video-popup-plugin-for-wordpress/#live-demo" target="_blank">',
                            '</a>'
                    );
                ?>
            </p>

            <div class="vp-section">
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
                                <a href="https://videopopup.net/?utm_source=plugin&utm_medium=cta&utm_campaign=shortcode_rf_page#get-premium" target="_blank" class="vp-btn-primary">
                                    <?php esc_html_e('Upgrade Now', 'video-popup'); ?>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                                </a>
                                <a href="https://wp-time.com/video-popup-plugin-for-wordpress/#live-demo" target="_blank" class="vp-btn-secondary">
                                    <?php esc_html_e('30+ Live Demos', 'video-popup'); ?>
                                </a>
                                <a href="https://videopopup.net/?utm_source=plugin&utm_medium=cta&utm_campaign=shortcode_rf_page#faq" target="_blank" class="vp-btn-secondary">
                                    <?php esc_html_e('FAQ', 'video-popup'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
            </div>

            <!-- Supported Video Sources -->
            <div class="vp-section">
                <h2><?php esc_html_e('Supported Video Sources', 'video-popup'); ?></h2>
                <div class="vp-table-wrapper">
                    <table class="vp-list-table">
                        <tr>
                            <th><?php esc_html_e('Platform', 'video-popup'); ?></th>
                            <th><?php esc_html_e('Supported URL Formats', 'video-popup'); ?></th>
                        </tr>
                        <tr>
                            <td>YouTube</td>
                            <td><code style="margin-bottom: 8px;">https://www.youtube.com/watch?v=VIDEO_ID</code><br><code style="margin-bottom: 8px;">https://youtu.be/VIDEO_ID</code><br><code>https://www.youtube.com/shorts/VIDEO_ID</code></td>
                        </tr>
                        <tr>
                            <td>Vimeo</td>
                            <td><code style="margin-bottom: 8px;">https://vimeo.com/VIDEO_ID</code><br><code>https://vimeo.com/channels/channelName/VIDEO_ID</code></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e('Direct Video (MP4 or WebM only)', 'video-popup'); ?></td>
                            <td><code style="margin-bottom: 8px;">https://example.com/my-video.mp4</code><br><code>https://example.com/my-video.webm</code></td>
                        </tr>
                    </table>

                    <p><em><?php esc_html_e('For YouTube and Vimeo, it doesn\'t matter if your URL contains extra content after the video ID; anything after the ID will be ignored automatically. This rule and the formats explained above apply to the entire Video Popup plugin, not just to shortcodes.', 'video-popup'); ?></em></p>
                </div>
            </div>

            <!-- Basic Usage -->
            <div class="vp-section">
                <h2><?php esc_html_e('Basic Shortcode', 'video-popup'); ?></h2>

                <p><?php esc_html_e('The basic shortcode allows you to create video popup links, just like the ones created by the Video Popup Builder in the Classic Editor. It serves as an alternative solution for websites that do not support the Classic Editor, and it is a perfect solution for Gutenberg and page builders.', 'video-popup'); ?></p>

                <p><strong><?php esc_html_e('Basic Usage:', 'video-popup'); ?></strong></p>
                <textarea class="vp-code-sample" readonly onclick="this.select()" style="height:45px;">&#91;video_popup url="https://www.youtube.com/watch?v=VIDEO_ID" text="Watch My Video"]</textarea>

                <p><strong><?php esc_html_e('Example:', 'video-popup'); ?></strong></p>
                <textarea class="vp-code-sample" readonly onclick="this.select()" style="height:45px; margin-bottom: 15px;">&#91;video_popup url="https://www.youtube.com/watch?v=EMmKs8vMKhU" text="Watch My Video" wc="1" n="1" title="This is my first video!"]</textarea>
                <p><strong><?php esc_html_e('Output:', 'video-popup'); ?></strong> <?php esc_html_e('a clickable link', 'video-popup'); ?></p>
                <textarea class="vp-code-sample" readonly onclick="this.select()" style="height:45px; margin-bottom: 0;"><?php echo esc_attr('<a href="https://www.youtube.com/watch?v=EMmKs8vMKhU" class="vp-a" title="This is my first video!" rel="nofollow" data-wrap-c="true">Watch My Video</a>'); ?></textarea>
            </div>

            <!-- Basic Attributes -->
            <div class="vp-section">
                <h2><?php esc_html_e('Basic Attributes', 'video-popup'); ?></h2>
                <div class="vp-table-wrapper">
                    <table class="vp-list-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Attribute', 'video-popup'); ?></th>
                                <th><?php esc_html_e('Description', 'video-popup'); ?></th>
                                <th><?php esc_html_e('Value Type', 'video-popup'); ?></th>
                                <th><?php esc_html_e('Usage Method', 'video-popup'); ?></th>
                                <th><?php esc_html_e('Default Value', 'video-popup'); ?></th>
                                <th><?php esc_html_e('Example', 'video-popup'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>url</code></td>
                                <td><?php esc_html_e('Your video URL', 'video-popup'); ?><span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('YouTube, Vimeo, or direct video URL (MP4 or WebM only). YouTube Shorts links are also supported.', 'video-popup'); ?>">?</span></td>
                                <td><code>URL</code></td>
                                <td><?php esc_html_e('Enter an URL', 'video-popup'); ?></td>
                                <td><?php esc_html_e('Empty', 'video-popup') ?></td>
                                <td><code>url="https://www.youtube.com/watch?v=VIDEO_ID"</code></td>
                            </tr>
                            <tr>
                                <td><code>text</code></td>
                                <td><?php esc_html_e('Link text to display', 'video-popup'); ?><span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('The clickable text', 'video-popup'); ?>">?</span></td>
                                <td><code>text</code></td>
                                <td><?php esc_html_e('Enter any text', 'video-popup'); ?></td>
                                <td><code>Video</code></td>
                                <td><code>text="Click to watch"</code></td>
                            </tr>
                            <tr>
                                <td><code>auto</code></td>
                                <td><?php esc_html_e('Autoplay control', 'video-popup'); ?><span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('Video starts playing automatically when the popup opens. Please note, autoplay may not work unless the mute option is enabled. However, on mobile phones and tablets, autoplay may not work even when the mute option is enabled, or it may behave inconsistently, working sometimes and failing at other times, due to browser autoplay policies.', 'video-popup'); ?>">?</span></td>
                                <td><code>number</code></td>
                                <td><?php esc_html_e('1 = disable, and 2 = enable', 'video-popup'); ?></td>
                                <td><code>2</code> <?php esc_html_e('(autoplay)', 'video-popup'); ?></td>
                                <td><code style="margin-bottom: 5px;">auto="1"</code> <?php esc_html_e('(disabling autoplay)', 'video-popup'); ?><br><code>auto="2"</code> <?php esc_html_e('(enabling autoplay)', 'video-popup'); ?></td>
                            </tr>
                            <tr>
                                <td><code>mute</code></td>
                                <td><?php esc_html_e('Mute video sound', 'video-popup'); ?></td>
                                <td><code>number</code></td>
                                <td><?php esc_html_e('0 = disable, and 1 = enable', 'video-popup'); ?></td>
                                <td><code>0</code></td>
                                <td><code>mute="1"</code></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Additional Attributes -->
            <div class="vp-section">
                <h2><?php esc_html_e('Additional Attributes', 'video-popup'); ?></h2>
                <div class="vp-table-wrapper">
                    <table class="vp-list-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Attribute', 'video-popup'); ?></th>
                                <th><?php esc_html_e('Description', 'video-popup'); ?></th>
                                <th><?php esc_html_e('Value Type', 'video-popup'); ?></th>
                                <th><?php esc_html_e('Usage Method', 'video-popup'); ?></th>
                                <th><?php esc_html_e('Default Value', 'video-popup'); ?></th>
                                <th><?php esc_html_e('Example', 'video-popup'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>nc</code></td>
                                <td><?php esc_html_e('Enable privacy mode', 'video-popup'); ?><span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('Prevent YouTube and Vimeo from storing cookies in the user\'s browser. GDPR-compliant. This option is for YouTube and Vimeo only.', 'video-popup'); ?>">?</span></td>
                                <td><code>number</code></td>
                                <td><?php esc_html_e('0 = disable, and 1 = enable', 'video-popup'); ?></td>
                                <?php
                                // Variable is already properly escaped, no need to escape again.
                                // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
                                ?>
                                <td><code>0</code> <?php echo $general_settings; ?></td>
                                <?php 
                                // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
                                ?>
                                <td><code>nc="1"</code></td>
                            </tr>
                            <tr>
                                <td><code>wc</code></td>
                                <td><?php esc_html_e('Disable Outside Click Close', 'video-popup'); ?><span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('Prevents popup from closing when clicking outside the video area (overlay background). When this option is enabled, users must use the close button or ESC key to close the popup.', 'video-popup'); ?>">?</span></td>
                                <td><code>number</code></td>
                                <td><?php esc_html_e('0 = disable, and 1 = enable', 'video-popup'); ?></td>
                                <?php
                                // Variable is already properly escaped, no need to escape again.
                                // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
                                ?>
                                <td><code>0</code> <?php echo $general_settings; ?></td>
                                <?php 
                                // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
                                ?>
                                <td><code>wc="1"</code></td>
                            </tr>
                            <tr>
                                <td><code>video_image</code></td>
                                <td><?php esc_html_e('Display YouTube Video Preview Image', 'video-popup'); ?><span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('Displays youtube video preview image as the clickable element instead of text. This option is for YouTube only. Note: If the image does not appear or a default placeholder is shown, it means the video was deleted or the image is no longer available. Additionally, if the video owner changes the image in the future, the new image may appear.', 'video-popup'); ?>">?</span></td>
                                <td><code>number</code></td>
                                <td><?php esc_html_e('0 = disable, and 1 = enable', 'video-popup'); ?></td>
                                <td><code>0</code></td>
                                <td><code>video_image="1"</code></td>
                            </tr>
                            <tr>
                                <td><code>img</code></td>
                                <td><?php esc_html_e('Custom video preview image', 'video-popup'); ?><span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('Displays custom video preview image as the clickable element instead of text.', 'video-popup'); ?>">?</span></td>
                                <td><code>URL</code></td>
                                <td><?php esc_html_e('Enter an image URL', 'video-popup'); ?></td>
                                <td><?php esc_html_e('Empty', 'video-popup') ?></td>
                                <td><code>img="https://example.com/thumb.jpg"</code></td>
                            </tr>
                            <tr>
                                <td><code>title</code></td>
                                <td><?php esc_html_e('Link title attribute', 'video-popup'); ?><span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('Tooltip text that appears when hover over the link.', 'video-popup'); ?>">?</span></td>
                                <td><code>text</code></td>
                                <td><?php esc_html_e('Enter any text', 'video-popup'); ?></td>
                                <td><?php esc_html_e('Empty', 'video-popup') ?></td>
                                <td><code>title="Watch full video"</code></td>
                            </tr>
                            <tr>
                                <td><code>n</code></td>
                                <td><?php esc_html_e('Add nofollow attribute', 'video-popup'); ?><span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('Adds rel="nofollow" attribute to prevent search engines from following this link.', 'video-popup'); ?>">?</span></td>
                                <td><code>number</code></td>
                                <td><?php esc_html_e('0 = disable, and 1 = enable', 'video-popup'); ?></td>
                                <td><code>0</code></td>
                                <td><code>n="1"</code></td>
                            </tr>
                            <tr>
                                <td><code>p</code></td>
                                <td><?php esc_html_e('Wrap in paragraph tag', 'video-popup'); ?><span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('Wraps the link in <p>.', 'video-popup'); ?>">?</span></td>
                                <td><code>number</code></td>
                                <td><?php esc_html_e('0 = disable, and 1 = enable', 'video-popup'); ?></td>
                                <td><code>0</code></td>
                                <td><code>p="1"</code></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Premium Features -->
            <div class="vp-section vp-premium_features">
                <h2><?php esc_html_e('Advanced Attributes (Premium)', 'video-popup'); ?></h2>

                <p>
                    <?php
                        printf(
                            // translators: %1$s is opening link tag, %2$s is closing link tag
                            esc_html__('With the %1$sPremium Version%2$s, there are 9 advanced attributes in addition to all the attributes listed above. In this section, we will explain these advanced attributes with examples so you can get an idea.', 'video-popup'),
                            '<a href="https://videopopup.net/?utm_source=plugin&utm_medium=link&utm_campaign=shortcode_rf_page#get-premium" target="_blank">',
                            '</a>'
                        );
                    ?>
                </p>

                <div class="vp-table-wrapper">

                    <p><strong><?php esc_html_e('Example:', 'video-popup'); ?></strong></p>

                    <textarea class="vp-code-sample" readonly onclick="this.select()" style="height:45px;">&#91;video_popup url="https://www.youtube.com/watch?v=Kpc31pvHjMM" co="#eeeeee" op="0" minimize="left" start="02:15" video_image="1" p="1"]</textarea>

                    <p><strong><?php esc_html_e('Output:', 'video-popup'); ?></strong></p>
                    <p style="margin-bottom:20px;"><?php esc_html_e('A clickable link with a YouTube preview image instead of text, because we used the attribute video_image="1", and the content will be wrapped in a paragraph (<p></p>) tag because we used the attribute p="1".', 'video-popup'); ?></p>
                    <?php
                    // This output is a static code example for users only. No external requests here.
                    // phpcs:disable PluginCheck.CodeAnalysis.Offloading.OffloadedContent
                    ?>
                    <textarea class="vp-code-sample" readonly onclick="this.select()" style="height:120px; margin-bottom: 20px;">
<?php echo esc_attr('<p class="vp-paragraph-tag">
    <a href="https://www.youtube.com/watch?v=Kpc31pvHjMM" class="vp-a" data-time="02:15" data-overlay="#EEEEEE" data-overlay-op="0" data-minimize="left">
        <img class="vp-img vp-yt-img" src="https://img.youtube.com/vi/Kpc31pvHjMM/sddefault.jpg">
    </a>
</p>'); ?></textarea>
                    <?php 
                    // phpcs:enable PluginCheck.CodeAnalysis.Offloading.OffloadedContent
                    ?>
                    <p><strong><?php esc_html_e('Attributes:', 'video-popup'); ?></strong></p>
                    <table class="vp-list-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Attribute', 'video-popup'); ?></th>
                                <th><?php esc_html_e('Description', 'video-popup'); ?></th>
                                <th><?php esc_html_e('Value Type', 'video-popup'); ?></th>
                                <th><?php esc_html_e('Usage Method', 'video-popup'); ?></th>
                                <th><?php esc_html_e('Default Value', 'video-popup'); ?></th>
                                <th><?php esc_html_e('Example', 'video-popup'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>w</code></td>
                                <td><?php esc_html_e('Popup width size', 'video-popup'); ?><span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('Custom popup width. Supports: pixels (e.g., 1200 or 1200px), viewport width (e.g., 80vw), and percentage (e.g., 90%). Numbers without unit default to pixels. Default width is "800px". Note: If you set a width larger than your screen width, it will be automatically resized to fit your screen width, and the larger width will appear on screens that support it. Also, if you set the width to "100%" or "100vw", the popup will not take the full screen width, it will leave appropriate spacing on the left and right sides. Min width is "150px".', 'video-popup'); ?>">?</span></td>
                                <td><code>size</code></td>
                                <td><?php esc_html_e('px, vw, or %', 'video-popup'); ?></td>
                                <?php
                                // Variable is already properly escaped, no need to escape again.
                                // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
                                ?>
                                <td><code>800px</code><br><?php echo $general_settings; ?></td>
                                <?php 
                                // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
                                ?>
                                <td><code>w="1200px"</code></td>
                            </tr>
                            <tr>
                                <td><code>h</code></td>
                                <td><?php esc_html_e('Popup height size', 'video-popup'); ?><span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('Custom popup height. Supports: pixels (e.g., 675 or 675px), viewport height (e.g., 80vh), and percentage (e.g., 90%). Numbers without unit default to pixels. Default height is "450px". Note: If you set a height larger than your screen height, it will be automatically resized to fit your screen height, and the larger height will appear on screens that support it. Also, If you set the height to "100%" or "100vh", the popup will not take the full screen height, it will leave appropriate spacing at the top and bottom. Min height is "84.38px".', 'video-popup'); ?>">?</span></td>
                                <td><code>size</code></td>
                                <td><?php esc_html_e('px, vh, % or 16:9', 'video-popup'); ?><br><?php esc_html_e('Read the note below*', 'video-popup'); ?></td>
                                <?php
                                // Variable is already properly escaped, no need to escape again.
                                // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
                                ?>
                                <td><code>450px</code><br><?php echo $general_settings; ?></td>
                                <?php 
                                // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
                                ?>
                                <td><code style="margin-bottom: 5px;">h="675px"</code> <?php esc_html_e('or:', 'video-popup'); ?><br><code>h="16:9"</code></td>
                            </tr>
                            <tr>
                                <td><code>co</code></td>
                                <td><?php esc_html_e('Overlay color', 'video-popup'); ?><span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('Overlay color in HEX format. Examples: #000000 for black, #FFFFFF for white. Default is #000000. Note: RGB and RGBA formats cannot be used in this attribute, but the HEX color will be automatically converted to RGBA format with transparency controlled via the Overlay Opacity attribute. The close button color will automatically adjust to complement the overlay color.', 'video-popup'); ?>">?</span></td>
                                <td><code>HEX</code></td>
                                <td><?php esc_html_e('HEX color code', 'video-popup'); ?></td>
                                <?php
                                // Variable is already properly escaped, no need to escape again.
                                // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
                                ?>
                                <td><code>#000000</code><br><?php echo $general_settings; ?></td>
                                <?php 
                                // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
                                ?>
                                <td><code>co="#ffffff"</code></td>
                            </tr>
                            <tr>
                                <td><code>op</code></td>
                                <td><?php esc_html_e('Overlay opacity', 'video-popup'); ?><span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('Overlay transparency level: 0 = fully transparent, 100 = completely opaque. Default is 80 for semi-transparent. Only whole numbers from 0 to 100 are allowed. Decimal values like 49.50 are not allowed. Trick: For a blur backdrop effect, set overlay color to #FFFFFF (for light blur effect) or #000000 (for dark blur effect) and opacity to "20".', 'video-popup'); ?>">?</span></td>
                                <td><code>number</code></td>
                                <td><?php esc_html_e('Number between 0-100', 'video-popup'); ?></td>
                                <?php
                                // Variable is already properly escaped, no need to escape again.
                                // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
                                ?>
                                <td><code>80</code><br><?php echo $general_settings; ?></td>
                                <?php 
                                // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
                                ?>
                                <td><code>op="50"</code></td>
                            </tr>
                            <tr>
                                <td><code>minimize</code></td>
                                <td><?php esc_html_e('Minimized design on the left or the right side', 'video-popup'); ?><span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('Set the alignment of the minimized design. Choosing "left" or "right" applies a fixed-size layout, and any custom width (w="") or height (h="") set via attributes will be ignored. The Overlay Color (co="") will be used as the border color, and Overlay Opacity (op="") controls its transparency. You can set op="0" to hide the border completely. If "none" is chosen, the popup will use the Basic Design. Note: On screens 768px or smaller, the minimized design will automatically switch to the Basic Design. Other attributes, such as overlay color and opacity, will remain applied; only the layout changes.', 'video-popup'); ?>">?</span></td>
                                <td><code>string</code></td>
                                <td><?php esc_html_e('"left", "right", or "none"', 'video-popup'); ?></td>
                                <td><code>none</code> <?php esc_html_e('(basic design)', 'video-popup'); ?></td>
                                <td><code>minimize="left"</code></td>
                            </tr>
                            <tr>
                                <td><code>dc</code></td>
                                <td><?php esc_html_e('Hide Player Controls', 'video-popup'); ?><span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('Hides the player controls on YouTube, Vimeo, and supported direct video formats.', 'video-popup'); ?>">?</span></td>
                                <td><code>number</code></td>
                                <td><?php esc_html_e('0 = disable, and 1 = enable', 'video-popup'); ?></td>
                                <td><code>0</code></td>
                                <td><code>dc="1"</code></td>
                            </tr>
                            <tr>
                                <td><code>rv</code></td>
                                <td><?php esc_html_e('Limit YouTube Related Videos', 'video-popup'); ?><span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('Limits related videos to show only videos from the same channel instead of all YouTube videos. Note: YouTube no longer allows completely hiding related videos since September 2018, but this option helps keep viewers within the same channel content. This option is for YouTube only.', 'video-popup'); ?>">?</span></td>
                                <td><code>number</code></td>
                                <td><?php esc_html_e('0 = disable, and 1 = enable', 'video-popup'); ?></td>
                                <td><code>0</code></td>
                                <td><code>rv="1"</code></td>
                            </tr>
                            <tr>
                                <td><code>start</code></td>
                                <td><?php esc_html_e('Start Time', 'video-popup'); ?><span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('Start a YouTube or Vimeo video at a specific time. Supported formats: Seconds (e.g. 90), Time format (MM:SS, e.g. 01:30 or HH:MM:SS, e.g. 01:15:45), Duration format (e.g. 1m, 1m30s, 1h, 1h30s, 1h40m, or 1h15m45s). This option is for YouTube and Vimeo only.', 'video-popup'); ?>">?</span></td>
                                <td><code>time</code></td>
                                <td><?php esc_html_e('Seconds (e.g. 90), Time (e.g. 01:30), or Duration (e.g. 1m30s)', 'video-popup'); ?></td>
                                <td><?php esc_html_e('Empty', 'video-popup') ?></td>
                                <td><code>start="05:30"</code></td>
                            </tr>
                            <tr>
                                <td><code>end</code></td>
                                <td><?php esc_html_e('End Time', 'video-popup'); ?><span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('Stop a YouTube video at a specific time. Supported formats: Seconds (e.g. 300), Time format (MM:SS, e.g. 05:00 or HH:MM:SS, e.g. 02:30:00), Duration format (e.g. 5m, 5m20s, 2h, 2h15s, 2h30m, or 2h30m10s). This option is for YouTube only. Note: End Time is not supported for Vimeo videos, since Vimeo does not provide this feature.', 'video-popup'); ?>">?</span></td>
                                <td><code>time</code></td>
                                <td><?php esc_html_e('Seconds (e.g. 100), Time (e.g. 01:40), or Duration (e.g. 1m40s)', 'video-popup'); ?></td>
                                <td><?php esc_html_e('Empty', 'video-popup') ?></td>
                                <td><code>end="06:00"</code></td>
                            </tr>
                        </tbody>
                    </table>

                    <p><?php esc_html_e('*Note: To apply a 16:9 aspect ratio, enter "16:9" for the height attribute and a pixel value for the width attribute (e. g. 1200px). The height will be calculated automatically based on the width value.', 'video-popup'); ?></p>

                    <div class="vp-premium-btn" style="margin-top: 15px;"><a href="https://videopopup.net/?utm_source=plugin&utm_medium=button&utm_campaign=shortcode_rf_page#get-premium" target="_blank" class="button secondary-button vp-get-premium" title="<?php esc_attr_e('After upgrading to Premium, all your popups created with the free version will continue to work seamlessly without the need for edits! Upgrade with confidence.', 'video-popup'); ?>"><?php esc_html_e('Get Premium Version', 'video-popup'); ?></a></div>
                </div>
            </div>

            <!-- On-Page Load Shortcode -->
            <div id="vp-opl-shortcode" class="vp-section vp-premium_features">
                <h2><?php esc_html_e('On-Page Load Shortcode (Premium)', 'video-popup'); ?></h2>
                <div class="vp-table-wrapper">
                    <p>
                        <?php
                                printf(
                                    // translators: %1$s and %3$s is opening link tag, %2$s and %4$s is closing link tag
                                    esc_html__('With the %1$sPublic On-Page Load%2$s, you can set up a video to automatically show as a popup on multiple locations at once, like all posts or all pages. With the %3$sOn-Page Load Shortcode%4$s (in the premium version), you can show a different popup with a different video on a specific page or post. It works independently from the Public On-Page Load, giving you more flexibility to show multiple popups with different videos!', 'video-popup'),
                                    '<a href="' . esc_url($opl_settings_url) . '">',
                                    '</a>',
                                    '<a href="https://wp-time.com/video-popup-plugin-for-wordpress/#on-page-load-shortcode" target="_blank">',
                                    '</a>'
                                );
                            ?>
                    </p>

                    <p><strong><?php esc_html_e('Live Demos:', 'video-popup'); ?></strong></p>
                    <p>
                        <?php
                                printf(
                                    // translators: %1$s is opening link tag, %2$s is closing link tag
                                    esc_html__('%1$sDemo for Single, Double, and Triple On-Page Load Video Popups%2$s', 'video-popup'),
                                    '<a href="https://wp-time.com/video-popup-plugin-for-wordpress/#s-opl-live-demos" target="_blank">',
                                    '</a>'
                                );
                            ?>
                    </p>

                    <p><strong><?php esc_html_e('Basic Usage:', 'video-popup'); ?></strong></p>
                    <textarea class="vp-code-sample" readonly onclick="this.select()" style="height:45px;">&#91;video_popup_opl url="https://www.youtube.com/watch?v=VIDEO_ID" mute="1"]</textarea>
                    
                    <p><strong><?php esc_html_e('Example #1:', 'video-popup'); ?></strong></p>
                    <textarea class="vp-code-sample" readonly onclick="this.select()" style="height:45px;">&#91;video_popup_opl url="https://www.youtube.com/watch?v=_-AS5DtDeqs" mute="1" co="#D6763A" op="75" w="1200px" h="16:9" start="26"]</textarea>
                    <p><strong><?php esc_html_e('Output #1:', 'video-popup'); ?></strong></p>
                    <p style="margin-bottom:15px;"><?php esc_html_e('JavaScript code that shows video popup automatically.', 'video-popup'); ?></p>

                    <p><strong><?php esc_html_e('Example #2:', 'video-popup'); ?></strong></p>
                    <textarea class="vp-code-sample" readonly onclick="this.select()" style="height:65px;">&#91;video_popup_opl url="https://www.youtube.com/watch?v=_-AS5DtDeqs" mute="1" co="#D6763A" op="75" minimize="right" start="26" dbs="4" text="My video will show 4 seconds after page has finished loading." p="1"]</textarea>
                    <p><strong><?php esc_html_e('Output #2:', 'video-popup'); ?></strong></p>
                    <p><?php esc_html_e('In Example #2, using the text="My video..... etc" and p="1" attributes outputs a paragraph with the text and JavaScript code that shows video popup automatically', 'video-popup'); ?></p>
                    <textarea class="vp-code-sample" readonly style="height:45px;"><?php echo esc_attr('<p>My video will show 4 seconds after page has finished loading.<script>//JavaScript code will be here...</script></p>'); ?></textarea>

                    <p><strong><?php esc_html_e('Example #3:', 'video-popup'); ?></strong></p>
                    <textarea class="vp-code-sample" readonly onclick="this.select()" style="height:45px;">&#91;video_popup_opl url="https://www.youtube.com/watch?v=_-AS5DtDeqs" mute="1" co="#D6763A" op="75" start="26" minimize="left" once="1"]</textarea>
                    <p><strong><?php esc_html_e('Output #3:', 'video-popup'); ?></strong></p>
                    <p><?php esc_html_e('In Example #3, using the once="1" attribute makes the popup show only once, then show it again after 1 day. You can also use the unc="1" attribute to show the popup again for you only while logged in, allowing you to test without waiting.', 'video-popup'); ?></p>
                    
                    <p style="margin-top:20px;"><strong><?php esc_html_e('Attributes:', 'video-popup'); ?></strong></p>

                    <p><?php esc_html_e('The &#91;video_popup_opl] shortcode also supports all attributes of the basic &#91;video_popup] shortcode, such as auto="", mute="", w="", h="", minimize="", etc. However, it does not support title="", n="", img="", or video_image="" since the output is not a clickable link. In addition, it has 8 unique attributes of its own, which are listed in this table:', 'video-popup'); ?></p>
                    <table class="vp-list-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Attribute', 'video-popup'); ?></th>
                                <th><?php esc_html_e('Description', 'video-popup'); ?></th>
                                <th><?php esc_html_e('Value Type', 'video-popup'); ?></th>
                                <th><?php esc_html_e('Usage Method', 'video-popup'); ?></th>
                                <th><?php esc_html_e('Default Value', 'video-popup'); ?></th>
                                <th><?php esc_html_e('Example', 'video-popup'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>dtr</code></td>
                                <td><?php esc_html_e('Device Targeting', 'video-popup'); ?><span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('Select which devices the video popup should appear on. Value examples: Use "any_mobile" to target iPhone, iPad, and Android devices only. Use "iphone" to target iPhone only, "ipad" to target iPad only, or "android" to target Android devices only. Use "apple_mob" to target iPhone and iPad only. Use "except_mobile" to target any device except mobile (will not appear on iPhone, iPad, and Android devices). Default value is "any_device".', 'video-popup'); ?>">?</span></td>
                                <td><code>string</code></td>
                                <td><?php esc_html_e('any_device | any_mobile | except_mobile | iphone | ipad | android | apple_mob', 'video-popup'); ?></td>
                                <td><code>any_device</code></td>
                                <td><code>dtr="any_mobile"</code></td>
                            </tr>
                            <tr>
                                <td><code>dbs</code></td>
                                <td><?php esc_html_e('Delay Before Show', 'video-popup'); ?><span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('Set a delay in seconds before showing the video popup. Only whole numbers are allowed. If you don\'t want any delay, enter "0". Default is 1 second. Note: The delay countdown starts right after your page finishes loading. For example, if you set a 4-second delay and your page requires 5 seconds to load, the popup will show after a total of 9 seconds.', 'video-popup'); ?>">?</span></td>
                                <td><code>number</code></td>
                                <td><?php esc_html_e('Seconds (e.g. 5)', 'video-popup'); ?></td>
                                <td><code>1</code></td>
                                <td><code>dbs="5"</code></td>
                            </tr>
                            <tr>
                                <td><code>fcs</code></td>
                                <td><?php esc_html_e('Apply Custom Size on Mobile', 'video-popup'); ?><span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('By default, mobile devices use an optimized size for the popup, while the custom width and height are applied on other devices. Enable this option to apply the width (w="") and height (h="") exactly as defined on all devices, including mobile. If black bars appear on the sides or top/bottom, this is caused by the size you set and the screen’s aspect ratio. This cannot be fixed automatically.', 'video-popup'); ?>">?</span></td>
                                <td><code>number</code></td>
                                <td><?php esc_html_e('0 = disable, and 1 = enable', 'video-popup'); ?></td>
                                <td><code>0</code></td>
                                <td><code>fcs="1"</code></td>
                            </tr>
                            <tr>
                                <td><code>once</code></td>
                                <td><?php esc_html_e('Show once every specified number of days', 'video-popup'); ?><span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('Shows the video popup once per visitor or user based on the number of days set in the once attribute, using a cookie. For example, setting once="2" will show the popup again after 2 days for the same visitor/user. This feature is GDPR compliant as it only stores whether the video popup has been shown or not and doesn\'t collect any personal data.', 'video-popup'); ?>">?</span></td>
                                <td><code>number</code></td>
                                <td><?php esc_html_e('Days (e.g. 2)', 'video-popup'); ?></td>
                                <td><code>0</code></td>
                                <td><code>once="2"</code></td>
                            </tr>
                            <tr>
                                <td><code>unc</code></td>
                                <td><?php esc_html_e('Delete Cookie (useful for testing)', 'video-popup'); ?><span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('The unc attribute works together with once. It allows you to delete the saved cookie and show the popup again immediately for testing purposes. This helps confirm your settings without waiting for the set number of days. This attribute works only for administrators, editors, and authors, and does not affect other users/visitors during testing.', 'video-popup'); ?>">?</span></td>
                                <td><code>number</code></td>
                                <td><?php esc_html_e('0 = disable, and 1 = enable', 'video-popup'); ?></td>
                                <td><code>0</code></td>
                                <td><code>unc="1"</code></td>
                            </tr>
                            <tr>
                                <td><code>out</code></td>
                                <td><?php esc_html_e('Shows Outside Singular', 'video-popup'); ?><span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('By default, on-page load popups only show on singular pages (posts, pages, products, etc). Enable this to allow showing on non-singular pages if needed.', 'video-popup'); ?>">?</span></td>
                                <td><code>number</code></td>
                                <td><?php esc_html_e('0 = disable, and 1 = enable', 'video-popup'); ?></td>
                                <td><code>0</code></td>
                                <td><code>out="1"</code></td>
                            </tr>
                            <tr>
                                <td><code>msw</code></td>
                                <td><?php esc_html_e('Hide on Specific Screen Width', 'video-popup'); ?><span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('Enter a screen width (in pixels) to hide the video popup on. For example: setting value to "960" will hide the video popup on screens that width or smaller. Enter "0" to show the video popup on all screen sizes. Only whole or decimal numbers allowed, without "px". Common screen widths: Small laptops & large tablets (1024px), Most tablets (768px), Mobile phones (480px). Recommended value: "960" to hide on most tablets and all phones.', 'video-popup'); ?>">?</span></td>
                                <td><code>number</code></td>
                                <td><?php esc_html_e('Pixels (e.g. 768)', 'video-popup'); ?></td>
                                <td><code>0</code></td>
                                <td><code>msw="768"</code></td>
                            </tr>
                            <tr>
                                <td><code>debug</code></td>
                                <td><?php esc_html_e('Enable JS Debug Mode', 'video-popup'); ?><span class="vp-tooltip" data-vp-tooltip="<?php esc_attr_e('Enable this option to show debug logs in the browser console. Logs are visible only after opening the video popup, and you must be logged in. To view them, step by step: Open your site > Click on any video popup link in your content or the one with the issue > Press F12 > Check the Console tab. We recommend using Google Chrome while debugging.', 'video-popup'); ?>">?</span></td>
                                <td><code>number</code></td>
                                <td><?php esc_html_e('0 = disable, and 1 = enable', 'video-popup'); ?></td>
                                <td><code>0</code></td>
                                <td><code>debug="1"</code></td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="vp-settings-section vp-opl-faq-section">
                        <h2><?php esc_html_e('FAQ:', 'video-popup'); ?></h2>

                        <h3><?php esc_html_e('Question #1: Why does the On-Page Load popup only show after page has finished loading, and not while loading?', 'video-popup'); ?></h3>

                        <p><?php esc_html_e('To ensure the Video Popup Modal is ready, as showing it while page is loading could sometimes cause errors.', 'video-popup'); ?></p>

                        <h3><?php esc_html_e('Question #2: Can I use more than one On-Page Load Shortcode on the same post or page?', 'video-popup'); ?></strong></h3>

                        <p><?php esc_html_e('If you want to use two, there won\'t be any conflict or errors, but the second one will be shown first. If you want the first one to show first, you can set it with no delay and the second with a delay, for example 60 seconds, using the attribute dbs="60". An example:', 'video-popup'); ?></p>

                        <textarea class="vp-code-sample" readonly style="height:120px;">&#91;video_popup_opl url="https://www.youtube.com/watch?v=_-AS5DtDeqs" mute="1" minimize="left" dbs="0"] <?php esc_attr_e('// Without delay.', 'video-popup'); ?>

&#91;video_popup_opl url="https://www.youtube.com/watch?v=Kpc31pvHjMM" mute="1" minimize="left" dbs="60"] <?php esc_attr_e('// a 60-second delay', 'video-popup'); ?>


<?php esc_attr_e('Note: The delay countdown starts right after your page finishes loading, not after the first popup opens. For example, if you set a 60-second delay and your page requires 5 seconds to load, the second popup will show after a total of 65 seconds.', 'video-popup'); ?></textarea>

                        <h3><?php esc_html_e('Question #3: I set up the Public On-Page Load video popup to target all posts, but at the same time I used &#91;video_popup_opl] on a specific post to display a different video popup. Will this cause any errors or conflicts?', 'video-popup'); ?></h3>

                        <p><?php esc_html_e('There will be no errors or conflicts. Our video popup plugin is smart and designed to be flexible. The Public On-Page Load popup will show normally across all posts, but if a post also contains an On-Page Load shortcode, the faster one will be shown. For example, let’s say you configured a Public On-Page Load video for "iPhone 17 Air unboxing" and targeted all posts through Display Locations, but you also have a post with ID 5668 that contains a &#91;video_popup_opl] shortcode with a video for "iPhone 17 Pro unboxing". When a user visits that post, only one popup video will show—the faster one, which could be either the iPhone 17 Air or iPhone 17 Pro video. If you want to ensure that the shortcode video always shows on that post (e.g. 5668), you can either set a delay for the shortcode or exclude the post ID (e.g. 5668) from the Public On-Page Load using the Never show on these IDs field, located under: Public On-Page Load > Display Settings.', 'video-popup'); ?></p>


                        <h3><?php esc_html_e('Question #4: Can I switch from &#91;video_popup_opl] to &#91;video_popup] or vice versa?', 'video-popup'); ?></h3>

                        <p><?php esc_html_e('Yes, you can. All you need to do is remove "_opl" from the shortcode, regardless of the attributes. Any attributes specific to &#91;video_popup_opl], such as once="" and dbs="", will have no effect if used in &#91;video_popup], and there will be no errors. For example:', 'video-popup'); ?></p>

                        <pre class="vp-code-sample" style="height:105px; margin-bottom: 20px;">
&#91;video_popup<span style="color:#d6336c;">_opl</span> text="My video" url="https://www.youtube.com/watch?v=M0au92yebLQ" mute="1" once="1" dbs="5"] <?php esc_attr_e('// On-Page Load shortcode.', 'video-popup'); ?>


After removing "<span style="color:#d6336c;">_opl</span>":
&#91;video_popup text="My video" url="https://www.youtube.com/watch?v=M0au92yebLQ" mute="1" once="1" dbs="5"] <?php esc_attr_e('// Basic shortcode.', 'video-popup'); ?>
</pre>
                    
                        <h3><?php esc_html_e('Question #5: Does the &#91;video_popup_opl] shortcode work inside WooCommerce product content?', 'video-popup'); ?></h3>
                        <p><?php esc_html_e('Yes, it works. Both &#91;video_popup_opl] and &#91;video_popup] shortcodes work with any post type.', 'video-popup'); ?></p>

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

                        <div class="vp-premium-btn" style="margin-top: 15px;"><a href="https://videopopup.net/?utm_source=plugin&utm_medium=button&utm_campaign=shortcode_rf_page#get-premium" target="_blank" class="button secondary-button vp-get-premium" title="<?php esc_attr_e('After upgrading to Premium, all your popups created with the free version will continue to work seamlessly without the need for edits! Upgrade with confidence.', 'video-popup'); ?>"><?php esc_html_e('Get Premium Version', 'video-popup'); ?></a></div>

                    </div>

                </div>
            </div>

            <div class="vp-section">
                <h2><?php esc_html_e('Common Mistakes in Shortcode Usage', 'video-popup'); ?></h2>

                <p><strong><?php esc_html_e('Example wrong:', 'video-popup'); ?></strong></p>

                <textarea class="vp-code-sample" readonly style="height:45px; margin-bottom: 5px;">&#91;video_popup url="https://www.youtube.com/watch?v=VIDEO_ID"text="Watch My Video"]</textarea>

                <p><em><?php esc_html_e('Attributes must be separated by a space. In this example, url and text are stuck together, so it will not work.', 'video-popup'); ?></em></p>

                <p><strong><?php esc_html_e('Correct usage:', 'video-popup'); ?></strong></p>

                <textarea class="vp-code-sample" readonly onclick="this.select()" style="height:45px; margin-bottom: 5px;">&#91;video_popup url="https://www.youtube.com/watch?v=VIDEO_ID" text="Watch My Video"]</textarea>

                <p><em><?php esc_html_e('Note: The same rule applies when using &#91;video_popup_opl]. Always separate attributes with a space.', 'video-popup'); ?></em></p>

                <p style="margin-bottom:5px;"><strong><?php esc_html_e('Question: Do shortcode attributes need to be in a specific order?', 'video-popup'); ?></strong></p>

                <p style="margin-bottom:10px;"><?php esc_html_e('No! You can use attributes in any order for both &#91;video_popup] and &#91;video_popup_opl] shortcodes. For example:', 'video-popup'); ?></p>

                <textarea class="vp-code-sample" readonly style="height:120px; margin-bottom: 0;">&#91;video_popup url="https://www.youtube.com/watch?v=VIDEO_ID" text="Watch My Video"]

&#91;video_popup text="Watch My Video" url="https://www.youtube.com/watch?v=VIDEO_ID"]

<?php esc_attr_e('// Both produce the same result.', 'video-popup'); ?></textarea>
            </div>

        </div>
        <?php
    }
    
}