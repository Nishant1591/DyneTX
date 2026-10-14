<?php

if ( !defined('ABSPATH') ) {
    exit;
}

/**
 * Handles all shortcode functionality for embedding video popups
 * Provides [video_popup] shortcode for click-triggered popups
 * Features extensive customization options including appearance, and behavior
 * Supports YouTube, Vimeo and direct video files with thumbnail generation
 * Uses singleton pattern to ensure single instance throughout the application
 * 
 * @author   Alobaidi
 * @since    2.0.0
 */

class Video_Popup_Shortcode {

    private static $instance = null;
    
    /**
     * Retrieves the singleton instance of this class
     * Creates a new instance if one doesn't exist yet
     * 
     * @return Video_Popup_Shortcode The single instance of this class
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
     * Gets the utils class instance for validation helpers
     * 
     * @return Video_Popup_Utils The utils class instance
     */
    private function get_utils() {
        $utils = Video_Popup_Utils::get_instance();
        return $utils;
    }
    
    /**
     * Run the shortcode functionality
     * Registers the [video_popup] shortcode with WordPress
     */
    public function run() {
        add_shortcode('video_popup', array($this, 'shortcode_callback'));
    }

    /**
     * Shortcode callback function that processes attributes and renders HTML output
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output of shortcode
     */
    public function shortcode_callback($atts) {
        // Supported attributes based on JS logic
        $defaults = array(
            'url' => '',
            'text' => '',
            'title' => '',
            'auto' => '2',
            'autoplay' => '2',
            'mute' => '',
            'video_image' => '',
            'nc' => '',
            'wc' => '',
            'n' => '',
            'img' => '',
            'p' => ''
        );

        $atts = shortcode_atts($defaults, $atts, 'video_popup');
        $utils = $this->get_utils();

        if ( !isset($atts['url']) || isset($atts['url']) && empty(trim($atts['url'])) ) {
            $error_msg = esc_html__('Video Popup Shortcode: Please enter a valid URL in the url="" attribute, and make sure the URL is written as text, not as an HTML tag.', 'video-popup');
            return is_user_logged_in() && (current_user_can('administrator') || current_user_can('editor') || current_user_can('author')) ? '(('.$error_msg.'))' : '';
        }

        // Sanitize and validate attributes
        $url = $utils->validate_url($atts['url']);
        $text = $this->validate_text($atts['text']);
        $title = $this->validate_title($atts['title']);

        $autoplay_attr_values = array(1, 'no');
        $auto_attr = in_array(trim(strtolower($atts['auto'])), $autoplay_attr_values);
        $autoplay_attr = in_array(trim(strtolower($atts['autoplay'])), $autoplay_attr_values);
        $autoplay = $auto_attr || $autoplay_attr ? false : true;

        $mute = $utils->validate_boolean($atts['mute'], false);
        $no_cookie = $utils->validate_boolean($atts['nc'], false);
        $disable_wrap_close = $utils->validate_boolean($atts['wc'], false);
        $rel_nofollow = $utils->validate_boolean($atts['n'], false);

        $is_youtube = $url ? $utils->is_youtube($url) : false;
        $is_vimeo = $url ? $utils->is_vimeo($url) : false;
        $is_direct_video = $url ? $utils->is_direct_video($url) : false;

        $img = $this->validate_image($atts['img']);
        $video_image = $utils->validate_boolean($atts['video_image'], false); // Video Preview Image

        $paragraph_tag = trim($atts['p']) == 1 ? true : false;
        $paragraph_tag_open = $paragraph_tag ? '<p class="vp-paragraph-tag">' : '';
        $paragraph_tag_close = $paragraph_tag ? '</p>' : '';

        // Build class attribute
        $classNames = [];
        $classNames[] = $autoplay ? 'vp-a' : 'vp-s';
        $classNames[] = $this->get_video_type_class($url);
        $classNames[] = 'vp-modal-click vp-modal-click_shortcode';

        // Build attributes
        $attributes = [];

        if ($title) $attributes['title'] = $title;
        if ($mute) $attributes['data-mute'] = '1';
        
        $attributes['rel'] = $rel_nofollow ? 'nofollow noreferrer' : 'noreferrer';

        if ( $no_cookie && ($is_youtube || $is_vimeo) ) $attributes['data-cookie'] = 'true';
        if( trim($atts['nc']) == '0' ) $attributes['data-cookie'] = 'false';

        if ( $disable_wrap_close ) $attributes['data-wrap-c'] = 'true';
        if( trim($atts['wc']) == '0' ) $attributes['data-wrap-c'] = 'false';

        // Build link content
        $link_content = '';

        if ($video_image) {
            $video_image_data = $this->get_video_image_data($url);
            $video_image_url = $video_image_data['url'];
            $video_image_classes = $video_image_data['class'];

            if ($video_image_url && $video_image_classes) {
                $classNames[] = 'vp-has-img'; // class for link.
                $link_content = '<img class="' . esc_attr($video_image_classes) . '" src="' . esc_url($video_image_url) . '">';
            } elseif ($img) {
                $classNames[] = 'vp-has-img'; // class for link.
                $link_content = '<img class="vp-img" src="' . esc_url($img) . '">';
            } else {
                $link_content = esc_html($text ?: 'Video');
            }
        }
        elseif ($img) {
            $classNames[] = 'vp-has-img'; // class for link.
            $link_content = '<img class="vp-img" src="' . esc_url($img) . '">';
        } 
        else {
            $link_content = esc_html($text ?: __('Video', 'video-popup'));
        }

        $attributes['href'] = $url ? trim($url) : '#';
        $attributes['class'] = implode(' ', $classNames);

        // Build HTML attributes string
        $attrString = '';

        foreach ($attributes as $attr => $val) {
            $attrString .= ' ' . $attr . '="' . esc_attr($val) . '"';
        }

        return $paragraph_tag_open . '<a' . $attrString . '>' . $link_content . '</a>' . $paragraph_tag_close;
    }    

    /**
     * Validates text content for display in shortcode
     * 
     * @param string $text Text to validate
     * @return string|false Validated text or false if empty
     */
    private function validate_text($text) {
        $text = trim($text);
        return $text ? esc_html($text) : false;
    }

    /**
     * Validates title attribute for the link
     * 
     * @param string $title Title to validate
     * @return string|false Escaped title or false if empty
     */
    private function validate_title($title) {
        $title = trim($title);
        return $title ? esc_attr($title) : false;
    }

    /**
     * Validates image URL for custom thumbnail
     * 
     * @param string $img Image URL to validate
     * @return string|false Valid URL or false if invalid
     */
    private function validate_image($img) {
        $img = trim($img);

        if (empty($img)) return false;

        if (filter_var($img, FILTER_VALIDATE_URL)) return esc_url_raw($img);

        return false;
    }

    /**
     * Determines CSS class based on video type
     * Helps with styling and JavaScript targeting
     * 
     * @param string $url Video URL to analyze
     * @return string CSS class name for video type
     */
    private function get_video_type_class($url) {
        $url = trim($url);

        $utils = $this->get_utils();

        if ( $utils->is_youtube($url) ) {
            return 'vp-yt-type';
        } elseif ( $utils->is_vimeo($url) ) {
            return 'vp-vim-type';
        } elseif ( preg_match('/\.(mp4|webm)$/i', $url) ) {
            $ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));
            return 'vp-' . $ext . '-type';
        }

        return 'vp-unknown-type_shortcode';
    }

    /**
     * Gets video thumbnail data for automatic preview images
     * Currently supports YouTube thumbnails
     * 
     * @param string $url Video URL
     * @return array Array with thumbnail URL and CSS class
     */
    private function get_video_image_data($url) {
        $url = trim($url);

        $utils = $this->get_utils();
        $youtube_video_id = $utils->get_video_id($url, 'youtube');

        if ( $youtube_video_id ) {
            return [
                'url' => 'https://img.youtube.com/vi/' . $youtube_video_id . '/sddefault.jpg',
                'class' => 'vp-img vp-yt-img'
            ];
        }

        return [
            'url' => false,
            'class' => false
        ];
    }
    
}