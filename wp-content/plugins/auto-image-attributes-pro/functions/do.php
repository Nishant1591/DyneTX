<?php
/**
 * Operations of the plugin are included here. 
 *
 * @since 1.0
 * @function	iaffpro_auto_image_attributes_pro()			Pro module that updates image attributes
 * @function	iaffpro_update_attributes_in_post()			Update image attributes in every post the image is used
 * @function	iaffpro_update_attributes_in_post_helper()	Helper function for iaffpro_update_alt_text_in_post()
 * @function	iaffpro_get_attachment_id_by_url()			Get image ID from its url
 * @function	iaffpro_image_name_from_filename()			Extract, format and return image name from filename
 * @function	iaffpro_create_filter_list()				Create an array of characters to filter based on settings
 * @function	iaffpro_clean_filename()					Clean image filename
 * @function	iaffpro_capitalize()						Apply capitalization
 * @function	iaffpro_generate_image_attributes()			Generate Image Attributes based on settings
 * @function	iaffpro_update_image()						Update image attributes in database
 * @function	iaffpro_create_array_for_postids()			Create an empty array in database to store post ids of updated posts
 * @function	iaffpro_delete_array_for_postids()			Delete array in database that stores post ids of updated posts
 * @function	iaffpro_wp_plupload_include_attachment_id()	Help WordPress set post_parent for images uploaded to custom post types
 */

// Exit if accessed directly
if ( ! defined('ABSPATH') ) exit;

/**
 * Pro module that updates image attributes
 *
 * @since 1.0
 * @since 4.7 Return type changed from null to array to accommodate error messages.
 * 
 * @param (Object) $image Object of the image to work on
 * @param (bool) $bulk True when called from the Bulk Updater. False by default
 * 
 * @return (array) An array with keys following keys
 * 		'success' (bool) => True if image was updated. False otherwise.
 * 		'error' (string) => Error message if image was not updated.
 */
function iaffpro_auto_image_attributes_pro( $image, $bulk = false ) {
	
	// Return if no image object is passed
	if ( $image === NULL ) {
		return [
			'success' 	=> false, 
			'error' 	=> __( 'Image object was not provided.', 'auto-image-attributes-pro' )
		];
	}
	
	// Check if the image is excluded.
	if ( iaffpro_is_skip_image( $image->ID ) ) {
		return [
			'success' 	=> false, 
			'error' 	=> sprintf( 
				__( 'Image is excluded from bulk updates. To change this, use the <a href="%s" target="_blank">Image Attributes Pro meta box</a> in the Media Library.', 'auto-image-attributes-pro' ),
				'https://imageattributespro.com/media-library-meta-box/?utm_source=iap&utm_medium=error-log'
			)
		];
	}
	
	// Get Settings
	$settings = iaff_get_settings();

	// Find parent post of image.
	$parent_post_id = iaffpro_get_parent_post_of_image( $image->ID );
	
	// Generate image attributes
	$attributes = iaffpro_generate_image_attributes( $image->ID, $parent_post_id, $bulk );

	// Update image attributes in media library
	iaffpro_update_image( $image->ID, $attributes, $bulk );

	// Update Event log.
	iaffpro_event_log_append( 'Image attributes updated for: ' . '<a href="'. admin_url( 'post.php?post=' . $image->ID . '&action=edit' ) . '">' . wp_get_attachment_url( $image->ID ) . '</a>' );
	
	// Update attributes in post if running bulk updater.
	if ( $bulk == true ) {
		
		if ( 
			( isset( $settings['bu_title_location_post'] ) && boolval( $settings['bu_title_location_post'] ) ) || 
			( isset( $settings['bu_alt_text_location_post'] ) && boolval( $settings['bu_alt_text_location_post'] ) )
		) {
			iaffpro_update_attributes_in_post( $image );
		}
	}

	return [ 'success' 	=> true ];
}

/**
 * Update image attributes in every post the image is used
 *
 * @since	1.0
 * @param	Object		$image		Object of the image to work on
 * @return	Boolean		false on failure. true otherwise
 */
function iaffpro_update_attributes_in_post( $image ) {
	
	// Return if no object is passed
	if ( $image === NULL ) {
		return;
	}
	
	// Get Settings
	$settings = iaff_get_settings();
	
	// Find all posts that use the given image
	$all_posts_with_image = iaffpro_get_all_posts_by_attachment_id_combined( $image->ID );
	
	if ( $all_posts_with_image === false ) {
		return false;
	}
	
	// Get id's of posts that were already updated
	$updated_post_ids = get_option( 'iaffpro_updated_posts', array() );

	$settings = iaff_get_settings();
	$included_post_types = isset( $settings['bu_post_types'] ) ? $settings['bu_post_types'] : array();
	
	/** 
	 * Filter the list of post types to update.
	 * 
	 * @since 1.3 Empty array is passed to the filter.
	 * @since 4.9 UI introduced to select post types and that is passed to the filter.
	 */
	$included_post_types = apply_filters( 'iaffpro_included_post_types', $included_post_types );
	
	foreach ( $all_posts_with_image as $post_with_image ) {
		
		// Skip the current post if its already updated. 
		if ( in_array( $post_with_image, $updated_post_ids ) ) {
			continue;
		}
		
		// Get post type of the post with the image. 
		$post_with_image_post_type = get_post_type( $post_with_image );
		
		// Skip current post if a post type is mentioned and current post is not included in the included post types. 
		if ( ( ! empty( $included_post_types ) ) && ( ! in_array ( $post_with_image_post_type, $included_post_types ) ) ) {
			continue;
		}
		
		// Update the post id into the list of updated posts
		$updated_post_ids[] = $post_with_image;
		$updated_post_ids['current_post'] = $post_with_image;
		update_option( 'iaffpro_updated_posts', $updated_post_ids );

		// Retrieve the post.
		$post = get_post( $post_with_image );
		
		// Update image attributes of all images in a given post.
		iaffpro_update_attributes_in_post_by_post_id( $post_with_image );
		
		// Update Event log.
		iaffpro_event_log_append( 'All images updated within: ' . '<a href="'. admin_url( 'post.php?post=' . $post->ID . '&action=edit' ) . '">' . $post->post_title . '</a>' );
	}
	
	return true;
}

/**
 * Update image attributes of all images in a given post.
 * 
 * @since 3.0
 * 
 * @param $post_id (int|WP_POST) Post ID or post object.
 */
function iaffpro_update_attributes_in_post_by_post_id( $post_id ) {

	// Retrieve the post
	$post = get_post( $post_id );

	if ( $post === NULL ) {
		return;
	}

	/**
	 * Store $post_id in a transient to send to iaffpro_update_attributes_in_post_helper().
	 * $post_id is required to generate image attributes using iaffpro_generate_image_attributes() in iaffpro_update_attributes_in_post_helper().
	 */
	set_transient( 'iaffpro_current_post_id', $post_id );
		
	// Update the post content
	$updated_content = preg_replace_callback( '/<img[^>]+/', 'iaffpro_update_attributes_in_post_helper' , $post->post_content );

	delete_transient( 'iaffpro_current_post_id' );

	// Update post back into the database
	$updated_post = array(
		'ID'           	=> $post->ID,
		'post_content'	=> $updated_content,
	);

	// Update the post into the database
	wp_update_post( wp_slash( $updated_post ) );

	// Update attributes of featured image.
	$featured_image = get_post_thumbnail_id( $post_id );

	if ( $featured_image !== 0 && $featured_image !== false ) {
		
		$parent_post_id = iaffpro_get_parent_post_of_image( $featured_image );

		if ( $parent_post_id === 0 ) {
			$parent_post_id = $post_id;
		}

		$attributes = iaffpro_generate_image_attributes( $featured_image, $parent_post_id, true );
		
		iaffpro_update_image( $featured_image, $attributes, true );
	}

	/**
	 * This action can be used to extend this function for custom post types. 
	 * 
	 * Used in /3rd-party/woocommerce.php to update attributes of images in Product gallery.
	 * 
	 * @since 3.1
	 */
	do_action( 'iaffpro_after_update_attributes_in_post', $post_id );
}

/**
 * Helper function for iaffpro_update_attributes_in_post()
 *
 * Update Alt and title in the <img title="" alt=""> tag based on settings
 * 
 * @since	1.0
 * @param 	Array	$match passed from preg_replace_callback() function
 * @return	String	Image markup with alt text replaced with the image name
 */
function iaffpro_update_attributes_in_post_helper( $match ) {
	
	// Return if no match is passed
	if ( $match === NULL ) {
		return;
	}
	
	// Get Settings
	$settings = iaff_get_settings();
	
	// Extract url in src="" in the passed markup
	preg_match( '/<img[^>]+src="([^">]+)"/', $match[0], $src );
	
	// Get the image ID from the image url 
	$image_id = iaffpro_get_attachment_id_by_url( $src[1] );

	/**
	 * iaffpro_get_attachment_id_by_url() can return 0 as $image_id when image ID is not found from image URL.
	 * This can happen for external images and when images urls are rewritten with CDN urls.
	 */
	if ( $image_id !== 0 ) {

		// Check if the image is excluded.
		if ( iaffpro_is_skip_image( $image_id ) ) {
			return $match[0];
		}

		/**
		 * Find parent post of image.
		 * 
		 * For consistency with rest of the code, attributes of images in media library will be based on it's parent post.
		 */
		$parent_post_id = iaffpro_get_parent_post_of_image( $image_id );

		if ( $parent_post_id !== 0 ) {
			// Generate image attributes
			$attributes = iaffpro_generate_image_attributes( $image_id, $parent_post_id, true );
		}

		// Update image attributes in Media Library.
		iaffpro_update_image( $image_id, $attributes, true );
	}

	// Get the post id of the current post from the transient.
	$post_id = get_transient( 'iaffpro_current_post_id' );

	// Generate image attributes
	$attributes = iaffpro_generate_image_attributes( $image_id, $post_id, true, $src[1] );
	
	// Update Title
	if ( 
		isset( $settings['bu_image_title'] ) && boolval( $settings['bu_image_title'] ) && // Title is checked in Bulk Updater General Settings.
		isset( $settings['bu_title_location_post'] ) && boolval( $settings['bu_title_location_post'] ) // Post HTML is selected in Image Title Settings.
	) {
		
		switch ($settings['bu_title_behaviour']) {
			
			// Update all attributes overwriting any existing attributes.
			case '1':	
				
				// Replace existing image title. It could be empty image title with title="".
				if ( strpos($match[0], "title=") !== false ) {
					$match[0] = preg_replace( '/title=""|title="[^"]+"/', 'title="'. $attributes['title'] .'"', $match[0] );
				}
				// Add image title.
				else {
					$match[0] = preg_replace( '/<img/', '<img title="'. $attributes['title'] .'"', $match[0] );
				}
				
				break;
			
			// Preserve existing attributes and add missing attributes only.
			case '2':
			default:
				
			// Add image title if no title attribute exist.
				if ( strpos( $match[0], "title=" ) === false ) {
					$match[0] = preg_replace( '/<img/', '<img title="' . $attributes['title'] . '"', $match[0] );
				}
				/** 
				 * In Gutenberg, if you delete the Image Title via "Advanced" setting of image block editor, it leaves an empty title in the markup (as title="").
				 * Here when we detect empty title, we replace it with the generated title.
				 */
				else if ( strpos( $match[0], 'title=""' ) !== false ) {
					$match[0] = str_replace( 'title=""', 'title="' . $attributes['title'] . '"', $match[0] );
				}
				
				break;
		}
	}
	
	// Update Alt Text
	if ( 
		isset( $settings['bu_image_alttext'] ) && boolval( $settings['bu_image_alttext'] ) && // Alt Text is checked in Bulk Updater General Settings.
		isset( $settings['bu_alt_text_location_post'] ) && boolval( $settings['bu_alt_text_location_post'] ) // Post HTML is selected in Image Alt Text Settings.
	) {
		
		switch ($settings['bu_alt_text_behaviour']) {
			
			// Update all attributes overwriting any existing attributes.
			case '1':

				if ( strpos($match[0], "alt=") !== false ) {
					$match[0] = preg_replace( '/alt=""|alt="[^"]+"/', 'alt="'. $attributes['alt_text'] .'"', $match[0] );
				}
				// Image markup does not have alt="" in it.
				else {
					$match[0] = preg_replace( '/<img/', '<img alt="'. $attributes['alt_text'] .'"', $match[0] );
				}

				break;
			
			// Preserve existing attributes and add missing attributes only.
			default:
			case '2':

				if ( strpos($match[0], "alt=") !== false ) {
					$match[0] = preg_replace( '/alt=""/', 'alt="'. $attributes['alt_text'] .'"', $match[0] );
				}
				else {
					$match[0] = preg_replace( '/<img/', '<img alt="'. $attributes['alt_text'] .'"', $match[0] );
				}
				
				break;
		}
	}
	
	/**
	 * Filter the image HTML markup. 
	 * 
	 * Useful to add or remove custom image attributes (example Lazyload attributes or Pinterest attributes) on all images. 
	 * 
	 * @param $match[0] (string) The image HTML markup (<img alt="" title="" ...) without the closing '>' after it is updated by Image Attributes Pro.
	 * 
	 * @since 1.4
	 */
	return apply_filters( 'iaffpro_html_image_markup_post_update', $match[0] );
}

/**
 * Get image ID from its url
 * 
 * This function is a wrapper around WordPress function attachment_url_to_postid() to accommodate for cropped images
 * @since	1.0
 * @refer	http://bordoni.me/get-attachment-id-by-image-url/
 * @param	String		$url	Url of the image whose ID is to be retrieved
 * @return	Integer		Id of the image
 */
function iaffpro_get_attachment_id_by_url( $url ) {
    
	// Attempt to resolve with native function. Will return 0 if it fails.
	$post_id = attachment_url_to_postid( $url );

    if ( ! $post_id ) {
        
		$dir = wp_upload_dir();
        $path = $url;
		
        if ( 0 === strpos( $path, $dir['baseurl'] . '/' ) ) {
			
            $path = substr( $path, strlen( $dir['baseurl'] . '/' ) );
        }

        if ( preg_match( '/^(.*)(\-\d*x\d*)(\.\w{1,})/i', $path, $matches ) ){
			
            $url = $dir['baseurl'] . '/' . $matches[1] . $matches[3];
            $post_id = attachment_url_to_postid( $url );
        }
    }

    return (int) $post_id;
}

/**
 * Extract, format and return image name from filename.
 *
 * @since 1.0.
 * @since 3.2 Added $image_url as param.
 * @since 4.3 Uses the image title generated by WordPress at the time of image upload if it's available. This is saved as iaff_wp_attachment_original_post_title.
 * @since 4.7 Preserve hyphens separated by space.
 * 
 * @param $image_id (int) ID of the image to work on. 0 for external images.
 * @param $bulk (boolean) True when called from the Bulk Updater. False by default.
 * @param $image_url (string) Optionally pass image url. Used when generating attributes for external images.
 * 
 * @return (string) Name of the image extracted from filename.
 */
function iaffpro_image_name_from_filename( $image_id, $bulk = false, $image_url = '' ) {

	// Image title generated by WordPress.
	$image_name = get_post_meta( $image_id, 'iaff_wp_attachment_original_post_title', true );
	
	// Extract the image name from the image url if WordPress generated image name is not available or if the image file was renamed.
	if ( ( $image_name === false ) || ( $image_name === '' ) || iaffpro_is_attachment_renamed( $image_id ) ) {
		$image_url			= $image_id !== 0 ? wp_get_attachment_url( $image_id ) : $image_url;
		$image_extension 	= pathinfo( $image_url );
		$image_name 		= basename( $image_url, '.' . $image_extension['extension'] );
	}

	/**
	 * Filter the name of the image before Image Attributes Pro starts processing.
	 * 
	 * @since 4.8
	 * 
	 * @param $image_name (string) Name of the image.
	 * @param $image_id (int) ID of the image to work on. 0 for external images.
	 * @param $bulk (boolean) True when called from the Bulk Updater. False otherwise.
	 * @param $image_url (string) Optionally pass image url. Used only when generating attributes for external images.
	 */
	$image_name = apply_filters( 'iaffpro_image_name_from_filename_pre', $image_name, $image_id, $bulk, $image_url );

	/**
	 * Define Safe Words that should remain intact during filename processing.
	 *
	 * Safe Words are preserved before Image Attributes Pro applies any filters, 
	 * and are restored afterward without modification.
	 *
	 * @since 4.9
	 *
	 * @param (array) $safe_words List of words to preserve.
	 */
	$safe_words = apply_filters( 'iaffpro_safe_words_in_filename', array() );
	
	$preserve_map = array();

	if ( ! empty( $safe_words ) && is_array( $safe_words ) ) {
		foreach ( $safe_words as $word ) {
			
			$word = trim( $word );
			if ( $word === '' ) {
				continue;
			}

			// Generate a placeholder with no numbers in it. Numbers are removed by filters.
			$placeholder = 'IAPKEEP' . strtr( md5( $word ), '0123456789abcdef', 'ghijklmnopqrstuv' );

			$image_name = str_replace( $word, $placeholder, $image_name, $count );

			if ( $count > 0 ) {
				$preserve_map[ $placeholder ] = $word;
			}
		}
	}

	// Preserve hyphens separated by space.
	$image_name = str_replace( ' - ', 'IAPHYPHEN', $image_name ); // Adds a placeholder.
	
	// Get the filter list
	$filter_list = iaffpro_create_filter_list();
	
	// Extract and remove regex
	if ( ! empty( $filter_list['regex'] ) ) {
		$regex = $filter_list['regex'];
		unset( $filter_list['regex'] );
	}
	
	// Remove characters
	if ( ! empty( $filter_list ) ) {
		$image_name = str_replace( $filter_list, ' ', $image_name );
	}
	if ( ! empty( $regex ) ) {
		$image_name = preg_replace( $regex, ' ', $image_name );
    }

	$image_name = str_replace( 'IAPHYPHEN', ' - ', $image_name ); // Replaces placeholder with a hyphen.

	// Capitalization
	$image_name	= iaffpro_capitalize( $image_name );

	// Restore preserved words
	if ( ! empty( $preserve_map ) ) {
		foreach ( $preserve_map as $placeholder => $word ) {
			$image_name = str_ireplace( $placeholder, $word, $image_name );
		}
	}
	
	// Final cleanup
	$image_name = preg_replace( '/\s\s+/', ' ', $image_name ); // Replace multiple spaces with a single spaces
	$image_name = trim( $image_name );		// Remove white spaces from both ends
	
	return $image_name;
}

/** 
 * Create an array of characters to filter based on settings
 *
 * @since 1.0
 * @since 4.3 Removed @param $bulk (boolean). There is only one set of filters from here on for both new images and bulk updater.
 * 
 * @return	Array	An array of characters that can be used to filter the filename. $filter_list['regex'] contains regex.
 */
function iaffpro_create_filter_list() {
	
	// Get Settings
	$settings = iaff_get_settings();
	
	$filter_list = array();
	
	// Create the filter list
	if ( isset( $settings['hyphens'] ) && boolval( $settings['hyphens'] ) ) {
		$filter_list[] = '-';	// Hypen
	}
	if ( isset( $settings['under_score'] ) && boolval( $settings['under_score'] ) ) {
		$filter_list[] = '_';	// Underscore
	}
	if ( isset( $settings['full_stop'] ) && boolval( $settings['full_stop'] ) ) {
		$filter_list[] = '.';	// Full stops
	}
	if ( isset( $settings['commas'] ) && boolval( $settings['commas'] ) ) {
		$filter_list[] = ',';	// Commas
	}
	if ( isset( $settings['all_numbers'] ) && boolval( $settings['all_numbers'] ) ) {
		$filter_list[] = '0';	// All numbers
		$filter_list[] = '1';
		$filter_list[] = '2';
		$filter_list[] = '3';
		$filter_list[] = '4';
		$filter_list[] = '5';
		$filter_list[] = '6';
		$filter_list[] = '7';
		$filter_list[] = '8';
		$filter_list[] = '9';
	}
	if ( isset( $settings['apostrophe'] ) && boolval( $settings['apostrophe'] ) ) {
		$filter_list[] = "'";	// Apostrophe
	}
	if ( isset( $settings['tilde'] ) && boolval( $settings['tilde'] ) ) {
		$filter_list[] = '~';	// Tilde
	}
	if ( isset( $settings['plus'] ) && boolval( $settings['plus'] ) ) {
		$filter_list[] = '+';	// Plus
	}
	if ( isset( $settings['pound'] ) && boolval( $settings['pound'] ) ) {
		$filter_list[] = '#';	// Pound
	}
	if ( isset( $settings['ampersand'] ) && boolval( $settings['ampersand'] ) ) {
		$filter_list[] = '&';	// Ampersand
	}
	if ( isset( $settings['round_brackets'] ) && boolval( $settings['round_brackets'] ) ) {
		$filter_list[] = '(';	// Round Brackets
		$filter_list[] = ')';
	}
	if ( isset( $settings['square_brackets'] ) && boolval( $settings['square_brackets'] ) ) {
		$filter_list[] = '[';	// Square Brackets
		$filter_list[] = ']';
	}
	if ( isset( $settings['curly_brackets'] ) && boolval( $settings['curly_brackets'] ) ) {
		$filter_list[] = '{';	// Curly Brackets
		$filter_list[] = '}';
	}
	if ( isset( $settings['custom_filter'] ) && ( ! empty( $settings['custom_filter'] ) ) ) {
		
		$all_custom_filters = explode( ',', $settings['custom_filter'] );	// Custom Filter
		foreach ( $all_custom_filters as $custom_filter ) {
			
			$filter_list[] = trim( $custom_filter );
		}
	}
	if ( isset( $settings['regex_filter'] ) && ( ! empty( $settings['regex_filter'] ) ) ) {
		$filter_list['regex'] = $settings['regex_filter'];	// Custom Regex
	}
	
	return $filter_list;
}

/**
 * Clean image filename
 *
 * @param (array) $file $_FILES array passed from WordPress
 * 
 * @return (array) Array with cleaned filename.
 * 
 * @since 1.0
 * @since 4.4 Trim any trailing hyphens in filename to avoid filenames that look like file-name-.jpg.
 */
function iaffpro_clean_filename( $file ) {
	
	// Get Settings
	$settings = iaff_get_settings();
	
	if ( ! isset( $settings['clean_filename'] ) ) {
		return $file;
	}
	
	$image_extension = pathinfo( $file['name'] );
	$image_name 	 = $image_extension['filename'];
	
	// Generate filter list
	$filter_list = iaffpro_create_filter_list();
	
	// Extract and remove regex from filter list
	if ( ! empty( $filter_list['regex'] ) ) {
		
		$regex = $filter_list['regex'];
		unset( $filter_list['regex'] );
	}
	
	// Remove characters
	if ( ! empty( $filter_list ) ) {
		$image_name = str_replace( $filter_list, '-', $image_name );
	}
	if ( ! empty( $regex ) ) {
		$image_name = preg_replace( $regex, '-', $image_name );
    }
	
	// If filename is empty, return the original filename instead.
	if ( empty( $image_name ) ) {
		return $file;
	}

	// Trim any trailing hyphens.
	$image_name = trim( $image_name, '-' );
	
	$file['name'] = $image_name . '.' . $image_extension['extension']; // Add extension to name of image
	
	return $file;
}

/**
 * Apply Capitalization.
 *
 * @since 1.0
 * @since 4.3 Removed @param $bulk (boolean). The bulk updater will use the same capitalization settings from Advanced tab from here on.
 * 
 * @param $text (string) The string to capitalize.
 * 
 * @return (string) The capitalized string.
 */
function iaffpro_capitalize( $text ) {
	
	// Get Settings
	$settings = iaff_get_settings();
	
	switch ( $settings['capitalization'] ) {
		
		default:
		case '0':			// No capitalization
			return $text;
			break;
			
		case '1':			// Convert to lowercase 
			return strtolower($text);
			break;
			
		case '2':			// Convert to uppercase
			return strtoupper($text);
			break;
			
		case '3':			// Title casing
			return ucwords(strtolower($text), " \t\r\n\f\v(-");
			break;
			
		case '4':			// Sentence casing
			return ucfirst(strtolower($text));
			break;
	}
}

/**
 * Generate Image Attributes based on settings
 *
 * @since 1.0.
 * @since 3.2 Added $image_url as param.
 * 
 * @param 	Integer		$image_id			ID of the image to work on
 * @param	Integer		$parent_post_id		A post ID. Expecting the id of the post the image is inserted into
 * @param	Boolean		$bulk				True when called from Bulk Updater. False by default
 * @param 	String		$image_url 			Optionally pass image url. Used when generating attributes for external images.
 * 
 * @return	Array		Array containing image attributes
 */
function iaffpro_generate_image_attributes( $image_id, $parent_post_id = 0, $bulk = false, $image_url = '' ) {
	
	// Get Settings
	$settings = iaff_get_settings();
	
	// Generate image name from filename if at least one attribute is set as image filename or parent_post_id is zero.
	if 	( 	(isset( $settings['title_source'] ) && ($settings['title_source'] == '0' )) ||
			(isset( $settings['alt_text_source'] ) && ($settings['alt_text_source'] == '0' )) ||
			(isset( $settings['caption_source'] ) && ($settings['caption_source'] == '0' )) ||
			(isset( $settings['description_source'] ) && ($settings['description_source'] == '0' )) ||
			($parent_post_id == 0) ) {
			
		$image_name = iaffpro_image_name_from_filename( $image_id, $bulk, $image_url );
	}
	
	// Get post title is at least one attribute is set as post title and if parent_post_id is not equal to 0
	if ($parent_post_id == 0) {
		
		$post_title = $image_name;
	} else {
		
		if 	( 	(isset( $settings['title_source'] ) && ($settings['title_source'] == '1' )) ||
				(isset( $settings['alt_text_source'] ) && ($settings['alt_text_source'] == '1' )) ||
				(isset( $settings['caption_source'] ) && ($settings['caption_source'] == '1' )) ||
				(isset( $settings['description_source'] ) && ($settings['description_source'] == '1' )) 
			) {
			
			$post = get_post( $parent_post_id );
			$post_title = $post->post_title; // Get the post title
			$post_title	= iaffpro_capitalize( $post_title ); // Apply Capitalization
		}
	}
	
	$attributes = array();

	// Prepare the arguments array for iaffpro_decode_custom_attribute()
	$args = array(
		'bulk'		=> $bulk,
		'image_url'	=> $image_url,
	);
	
	// Image Title
	if ( isset( $settings['title_source'] ) ) {
		switch ( $settings['title_source'] ) {
			case '0':
				$attributes['title'] = $image_name;
				break;
			case '1':
				$attributes['title'] = $post_title;
				break;
			case '2':
				$attributes['title'] = iaffpro_decode_custom_attribute( 'title', $image_id, $parent_post_id, $args );
				break;
		}
	}
	
	// Image Alt Text
	if ( isset( $settings['alt_text_source'] ) ) {
		switch ( $settings['alt_text_source'] ) {
			case '0':
				$attributes['alt_text'] = $image_name;
				break;
			case '1':
				$attributes['alt_text'] = $post_title;
				break;
			case '2':
				$attributes['alt_text'] = iaffpro_decode_custom_attribute( 'alt_text', $image_id, $parent_post_id, $args );
				break;
		}
	}
	
	// Image Caption
	if ( isset( $settings['caption_source'] ) ) {
		switch ( $settings['caption_source'] ) {
			case '0':
				$attributes['caption'] = $image_name;
				break;
			case '1':
				$attributes['caption'] = $post_title;
				break;
			case '2':
				$attributes['caption'] = iaffpro_decode_custom_attribute( 'caption', $image_id, $parent_post_id, $args );
				break;
		}
	}
	
	// Image Description
	if ( isset( $settings['description_source'] ) ) {
		switch ( $settings['description_source'] ) {
			case '0':
				$attributes['description'] = $image_name;
				break;
			case '1':
				$attributes['description'] = $post_title;
				break;
			case '2':
				$attributes['description'] = iaffpro_decode_custom_attribute( 'description', $image_id, $parent_post_id, $args );
				break;
		}
	}
	
	/**
	 * Filter generated image attributes
	 * 
	 * @param $attributes 		(array) Associative array of image attributes.
	 * @param $image_id 		(int) ID of the image to work on.
	 * @param $parent_post_id	(int) ID of the post the image is inserted into. 0 for images not attached to a post. 
	 * 
	 * @since 1.3
	 */
	$attributes = apply_filters( 'iaffpro_image_attributes', $attributes, $image_id, $parent_post_id );
	
	return $attributes;
}

/**
 * Update Image Attributes in database
 *
 * @since 	1.0
 * @param	Integer		$image_id		ID of the image to work on
 * @param 	Array		$attributes		An array of image attributes
 * @param	Boolean		$bulk			True when called from Bulk Updater. False by default
 * @return	True on success. False otherwise
 */
function iaffpro_update_image( $image_id, $attributes, $bulk = false ) {

	/** 
	 * Set filter iaffpro_update_media_library to false 
	 * to disable updating of attributes in media library. 
	 * 
	 * @since 1.3
	 */
	if ( ! apply_filters( 'iaffpro_update_media_library', true ) ) {
		return false;
	}
	
	// Return if no image ID is passed
	if( $image_id === NULL ) {
		return false;
	}

	// Check if the image is excluded.
	if ( iaffpro_is_skip_image( $image_id ) ) {
		return;
	}
	
	// Get Settings
	$settings = iaff_get_settings();
	
	$bu_prefix = '';
	
	if( $bulk === true ) {
		$bu_prefix = 'bu_';	// All bulk updater settings start with 'bu_'
	}
	
	$image		= array();
	$image['ID'] 	= $image_id;

	// Check if image title is set to be updated in General Settings
	if ( isset( $settings[$bu_prefix.'image_title'] ) && boolval( $settings[$bu_prefix.'image_title'] ) ) {
		
		// During bulk update.
		if ( $bulk == true ) {

			// Check if image title is to the updated in Media library (in Image Title Settings)
			if ( isset( $settings['bu_title_location_ml'] ) && boolval( $settings['bu_title_location_ml'] ) ) {

				switch( $settings['bu_title_behaviour'] ) {

					// Update all attributes overwriting any existing attributes.
					case '1':
						$image['post_title'] = $attributes['title']; // Image Title
						break;
					
					// Preserve existing attributes and add missing attributes only.
					case '2':
					default:
						/**
						 * Get current image title.
						 * 
						 * Image titles are rarely empty since WordPress adds them automatically. 
						 * If you manually make it empty, get_the_title will return 'Untitled'. Hence using get_post().
						 */
						$current_title = get_post( $image_id )->post_title; 
						if ( empty ( $current_title ) ) {
							$image['post_title'] = $attributes['title']; // Image Title
						}
						break;
				}
			}
		}
		
		// New image upload.
		else {
			$image['post_title'] = $attributes['title']; // Image Title
		}
	}
	
	// Check if alt text is set to be updated in General Settings.
	if ( isset( $settings[$bu_prefix.'image_alttext'] ) && boolval( $settings[$bu_prefix.'image_alttext'] ) ) {
		
		// During bulk update.
		if ( $bulk == true ) {

			// Check if alt text is to the updated in Media library (in Image Alt Text Settings)
			if ( isset( $settings['bu_alt_text_location_ml'] ) && boolval( $settings['bu_alt_text_location_ml'] ) ) {
			
				switch( $settings['bu_alt_text_behaviour'] ) {

					// Update all attributes overwriting any existing attributes.
					case '1':
						update_post_meta( $image_id, '_wp_attachment_image_alt', $attributes['alt_text'] ); 
						break;

					// Preserve existing attributes and add missing attributes only.
					case '2':
					default:
						// Get current image alt text
						$current_alt_text = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
						if ( empty ( $current_alt_text ) ) {
							update_post_meta( $image_id, '_wp_attachment_image_alt', $attributes['alt_text'] );
						}
						break;
				}
			}
		}
		
		// New image upload.
		else {
			update_post_meta( $image_id, '_wp_attachment_image_alt', $attributes['alt_text'] );
		}
	}
	
	// Check if image caption is set to be updated in General Settings.
	if ( isset( $settings[$bu_prefix.'image_caption'] ) && boolval( $settings[$bu_prefix.'image_caption'] ) ) {
		
		// During bulk update
		if ( $bulk == true ) {
			
			switch( $settings['bu_caption_behaviour'] ) {
				
				// Update all attributes overwriting any existing attributes.
				case '0':
					$image['post_excerpt'] = $attributes['caption'];
					break;
				
				// Preserve existing attributes and add missing attributes only.
				case '1':
				default:
					$current_caption = get_post( $image_id )->post_excerpt;
					if ( empty( $current_caption ) ) {
						$image['post_excerpt'] = $attributes['caption'];
					}
					break;
			}
		}
		
		// New image upload.
		else {
			$image['post_excerpt'] = $attributes['caption'];
		}
	}
	
	// Check if image description is set to be updated.
	if ( isset( $settings[$bu_prefix.'image_description'] ) && boolval( $settings[$bu_prefix.'image_description'] ) ) {
		
		// During bulk update
		if ( $bulk == true ) {
			
			switch( $settings['bu_description_behaviour'] ) {
				
				// Update all attributes overwriting any existing attributes.
				case '0':
					$image['post_content'] = $attributes['description'];
					break;
				
				// Preserve existing attributes and add missing attributes only.
				case '1':
				default:
					$current_description = get_post( $image_id )->post_content;
					if ( empty( $current_description ) ) {
						$image['post_content'] = $attributes['description'];
					}
					break;
			}
		}
		
		// New image upload.
		else {
			$image['post_content'] = $attributes['description'];
		}
	}

	$return_id = wp_update_post( $image ); // Retruns the ID of the post if the post is successfully updated in the database. Otherwise returns 0.
	
	if ( $return_id === 0 ) { 
		return false;
	}
	
	return true;
}

/**
 * Create an empty array in database to store post ids of updated posts
 *
 * Runs before starting the bulk updater
 * @since	1.0
 */
function iaffpro_create_array_for_postids() {
	update_option( 'iaffpro_updated_posts', array() );
}
add_action('iaff_before_bulk_updater', 'iaffpro_create_array_for_postids');

/**
 * Delete array in database that stores post ids of updated posts
 *
 * Runs after finishing the bulk updater
 * @since	1.0
 */
function iaffpro_delete_array_for_postids() {
	delete_option( 'iaffpro_updated_posts' );
}
add_action('iaff_after_bulk_updater', 'iaffpro_delete_array_for_postids');

/**
 * Help WordPress set post_parent for images uploaded to custom post types
 *
 * post_parent column in wp_posts is where WordPress stores the post id of the post to which the image is attached to.
 * In custom post types, on some occasions this isn't set and the parent_post is set as the default zero. 
 * Image Attributes Pro needs the parent_post to retrieve the post title for new uploads.
 *
 * @since	1.01
 * @refer	https://wordpress.stackexchange.com/a/176767/90061
 */
function iaffpro_wp_plupload_include_attachment_id( $params ) {
	
    global $post_ID; 
    if ( isset( $post_ID ) ) 
        $params['post_id'] = (int) $post_ID; 
	
    return $params; 
}
add_filter( 'plupload_default_params', 'iaffpro_wp_plupload_include_attachment_id' ); 

/**
 * Get Parent Post of an image from it's ID.
 * 
 * Post parent is determined based on the priority set in iaffpro_get_posts_by_attachment_id().
 * The post to which the image is uploaded to ($image_object->post_parent) is used as fallback.
 * 
 * @since 3.1 Post to which an image is originally uploaded is given highest priority.
 * @since 4.8 Parent post is now determined based on the priority set in iaffpro_get_posts_by_attachment_id()
 * 
 * @param $image_id (integer) ID of the image. 
 * 
 * @return (integer) ID of the parent post if one is found. 0 otherwise. 
 */
function iaffpro_get_parent_post_of_image( $image_id ) {
	
	$parent_post_id = 0;

	// Find all posts that use the given image.
	$all_posts_with_image = iaffpro_get_all_posts_by_attachment_id_combined( $image_id );

	if ( ( $all_posts_with_image !== false ) && ( ! empty( $all_posts_with_image ) ) ) {
		
		// Assign first post ID as parent post ID.
		$parent_post_id = $all_posts_with_image[0];
	}
	
	if ( $parent_post_id === 0 ) {

		// Get the object of the image form image ID.
		$image_object = get_post( $image_id );

		if ( $image_object === NULL ) {
			return 0;
		}

		/**
		 * $image_object->post_parent is the ID of the post to which the image is attached to. 
		 * This is displayed in the Media Library (list view) in the "Uploaded to" column.
		 */
		$parent_post_id = $image_object->post_parent;
	}

	return $parent_post_id;
}