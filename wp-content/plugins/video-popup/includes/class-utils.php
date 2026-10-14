<?php

if ( !defined('ABSPATH') ) {
    exit;
}

/**
 * Utility class with helper methods for validation and processing
 * Used throughout the plugin for consistent data handling
 * 
 * @author   Alobaidi
 * @since    2.0.0
 */

class Video_Popup_Utils {
    
    private static $instance = null;
    
    /**
     * Retrieves the singleton instance of this class
     * Creates a new instance if one doesn't exist yet
     * 
     * @return Video_Popup_Utils The single instance of this class
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * constructor - intentionally empty
     */
    private function __construct() {
        // No initialization here.
    }
    
    /**
     * Validates and standardizes checkbox value
     * 
     * @param mixed $input The input to validate
     * @return int 1 if checked, 0 if unchecked
     */
    public function validate_checkbox($input) {
        return (trim($input) == 1) ? 1 : 0;
    }

    /**
     * Validates numeric input for general numbers
     * 
     * @param mixed $input Value to validate
     * @param string|int $default_value Default value if invalid
     * @return int Valid integer or default
     */
    public function validate_number($input, $default_value = '') {
        $input = trim($input);
        
        if ( strpos($input, '-') !== false || !is_numeric($input) || !preg_match('/^(0|[1-9]\d*)$/', $input) ) {
            return $default_value;
        }
        
        $input = intval($input);
        
        return $input;
    }

    /**
     * Validates boolean values with various formats
     * 
     * @param mixed $value Value to validate
     * @param bool $default_value Default value if invalid
     * @return bool Normalized boolean value
     */
    public function validate_boolean($value, $default_value = false) {
        $v = strtolower(trim((string)$value));

        if ($v === '1' || $v === 'true' || $v === 'yes') return true;
        if ($v === '0' || $v === 'false' || $v === 'no') return false;

        return $default_value;
    }

    /**
     * Checks if URL is from YouTube
     * 
     * @param string $url URL to check
     * @return bool True if YouTube URL
     */
    public function is_youtube($url) {
        return preg_match('/youtube\.com|youtu\.be|youtube\-nocookie\.com/i', trim($url));
    }

    /**
     * Checks if URL is from Vimeo
     * 
     * @param string $url URL to check
     * @return bool True if Vimeo URL
     */
    public function is_vimeo($url) {
        return preg_match('/vimeo\.com/i', trim($url));
    }

    /**
     * Checks if URL is direct video file (MP4 or WebM)
     * 
     * @param string $url URL to check
     * @return bool True if direct video file
     */
    public function is_direct_video($url){
        return preg_match('/\.(mp4|webm)(\?.*)?$/i', trim($url));
    }

    /**
     * Validates video URL for supported formats
     * 
     * @param string $url URL to validate
     * @return string|false Valid escaped URL or false if invalid
     */
    public function validate_url($url) {
        $url = trim($url);

        if ( empty($url) || !$url ) return false;

        if ( filter_var($url, FILTER_VALIDATE_URL) ) {

            if ( !$this->is_youtube($url) && !$this->is_vimeo($url) && !$this->is_direct_video($url) ) {
                return false;
            }

            $url = rtrim($url, '/');
            return esc_url_raw($url);
        }

        return false;
    }

    /**
     * Extracts video ID from YouTube or Vimeo URL
     * 
     * @param string $url Video URL
     * @param string $video_type Type of video (youtube or vimeo)
     * @return string|false Video ID or false if not found
     */
    public function get_video_id($url, $video_type = 'youtube') {
        if ( $this->validate_url($url) ) {
            
            if ( strtolower($video_type) == 'youtube' && $this->is_youtube($url) ) {
                if ( preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|v\/|shorts\/)|youtu\.be\/|youtube-nocookie\.com\/(?:watch\?v=|embed\/|v\/)|m\.youtube\.com\/watch\?v=)([^&\n?#]+)/', $url, $matches) ) {
                    return $matches[1] ? trim($matches[1]) : false;
                }
            }

            if ( strtolower($video_type) == 'vimeo' && $this->is_vimeo($url) ) {
                if ( preg_match('/vimeo\.com\/(?:channels\/[^\/]+\/|video\/)?(\d+)(?:\/|\?|$)/', $url, $matches) ) {
                    return $matches[1] ? trim($matches[1]) : false;
                }
            }

        }

        return false;
    }
    
}