<?php
/**
 * Functions to handle custom attributes.
 * 
 * All functions are named in the format iaffpro_get_custom_attribute_tag_{%tagname%}
 * 
 * Attributes related to third party are located in /3rd-party/ folder
 *
 * @since 2.0 
 */

// Exit if accessed directly
if ( ! defined('ABSPATH') ) exit;

/**
 * Extract the custom attribute structure and decode it. 
 * 
 * @since 2.0
 * @since 3.2 Added a fifth argument $image_url (string) to optionally pass an image url.
 * @since 4.3 Removed fifth argument and changed fourth argument to an array. For backwards compatibility.
 * 
 * @param $attribute (String) The attribute that the bulk updater is trying to update prefixed with $bu_prefix.
 * @param $image_id (Integer) The ID of the image that is being updated. 
 * @param $parent_post_id (Integer) The post to which the image is attached (uploaded) to. 0 if the image is not attached to any post. 
 * @param $args (array) An array containing additional arguments. See notes below. 
 * 
 * $args array:
 * - bulk (boolean) True when called from Bulk Updater. False by default.
 * - image_url (string) Optionally pass image url. Used when generating attributes for external images.
 * - attribute (string) Attribute setting requested.
 * 
 * @return String The decoded custom attribute.
 */
function iaffpro_decode_custom_attribute( $attribute, $image_id, $parent_post_id = 0, $args = array() ) {
	
	// Get Settings
	$settings = iaff_get_settings();

	// Add attribute setting name to the $args array. Prefixed with $bu_prefix, so will have bu_ when called from bulk updater.
	$args['attribute'] = $attribute;
	
	// Read custom attribute
	$custom_attribute = $settings['custom_attribute_' . $attribute ];
	
	preg_match_all( '/%(.+?)%/', $custom_attribute, $tags );
	
	foreach( $tags[1] as $tagname ) {
		
		/**
		 * PHP supports variable functions!
		 * 
		 * Using variable functions will allow users to define their own tagnames and write custom functions.
		 * If a tag %my_custom_tag% is added, all they have to do is create a function name `iaffpro_get_custom_attribute_tag_my_custom_tag`.
		 */
		$decoder_function = 'iaffpro_get_custom_attribute_tag_' . $tagname;
		
		if ( function_exists( $decoder_function ) ) {

			$decoded_tag 		= $decoder_function( $image_id, $parent_post_id, $args );
			$custom_attribute 	= str_ireplace( '%' . $tagname . '%', $decoded_tag, $custom_attribute );
		}
	}

	/**
	 * Filter characters to trim.
	 * 
	 * @since 4.3
	 * 
	 * @param (string) Default list of characters to trim include space, pipe symbol (|) and hyphen (-).
	 */
	$trim_list = apply_filters( 'iaffpro_custom_attribute_tag_trim_list', ' |-' );
	
	return trim( $custom_attribute, $trim_list );
}

/**
 * Return Image Filename.
 * For %filename%
 * 
 * This is a wrapper to iaffpro_image_name_from_filename()
 * 
 * @since 2.0
 * @since 3.2 Added $image_url as param.
 * @since 4.3 Removed $image_url as param and replaced third param with $args array.
 * 
 * @param $image_id (Integer) The ID of the image that is being updated. 
 * @param $parent_post_id (Integer) The post to which the image is attached (uploaded) to. 0 if the image is not attached to any post. 
 * @param $args (array) An array containing additional arguments.
 * 
 * @return String Name of the image extracted from filename
 */
function iaffpro_get_custom_attribute_tag_filename( $image_id, $parent_post_id, $args = array() ) {

	$bulk 		= isset( $args['bulk'] ) ? $args['bulk'] : false;
	$image_url 	= isset( $args['image_url'] ) ? $args['image_url'] : '';

	return iaffpro_image_name_from_filename( $image_id, $bulk, $image_url );
}

/**
 * Return title of the post where the image is uploaded to. 
 * For %posttitle%
 * 
 * This is a wrapper to iaffpro_image_name_from_filename()
 * 
 * @since 2.0
 * @since 4.3 Get post title using post object instead get_the_title to avoid sanitization.
 *
 * @param $image_id The ID of the image that is being updated. 
 * @param $parent_post_id The post to which the image is attached (uploaded) to. 0 if the image is not attached to any post. 
 * @param $args (array) An array containing additional arguments.
 * 
 * @return String Title of the post where the image is uploaded to. 
 */
function iaffpro_get_custom_attribute_tag_posttitle( $image_id, $parent_post_id, $args = array() ) {
	
	if ( (int) $parent_post_id === 0 ) {
		return '';
	}

	$post = get_post( $parent_post_id );
	return $post->post_title;
}

/**
 * Return Site Title defined in WordPress General Settings.
 * For %sitetitle%
 * 
 * @since 2.0
 * 
 * @param $image_id The ID of the image that is being updated. 
 * @param $parent_post_id The post to which the image is attached (uploaded) to. 0 if the image is not attached to any post. 
 * @param $args (array) An array containing additional arguments.
 * 
 * @return String Site Title from WordPress General Settings. 
 */
function iaffpro_get_custom_attribute_tag_sitetitle( $image_id, $parent_post_id, $args = array() ) {
	return get_bloginfo( 'name' );
}

/**
 * Return Category name for Posts.
 * For %category%
 * 
 * Can be extended to other post types using 'iaffpro_custom_attribute_tag_category_taxonomy' filter. 
 * Returns first category name by default. Can be altered using 'iaffpro_custom_attribute_tag_category_names' filter. 
 * 
 * @since 3.0
 * 
 * @param $image_id The ID of the image that is being updated. 
 * @param $parent_post_id The post to which the image is attached (uploaded) to. 0 if the image is not attached to any post. 
 * @param $args (array) An array containing additional arguments.
 * 
 * @return (string) The first category of the post. Can be modified using iaffpro_custom_attribute_tag_category_names filter. Empty string otherwise.
 */
function iaffpro_get_custom_attribute_tag_category( $image_id, $parent_post_id, $args = array() ) {

	// Check post type of parent post where the image is used. 
	$post_type = get_post_type( $parent_post_id );

	if ( $post_type === false ) {
		return '';
	}

	switch ( $post_type ) {
		
		// WordPress posts.
		case 'post':
			$category_taxonomy_name = 'category';
			break;
			
		default:
			$category_taxonomy_name = false;
			break;
	}

	/**
	 * Filter $category_taxonomy_name to extend %category% custom attribute tag to other post types. 
	 * 
	 * For example, if you have a custom post type named 'library' where the category taxnomy name is 'genre',
	 * You can return the category taxonomy name so that the name of the genre can be retrieved. 
	 * 
	 * Refer 3rd-party/woocommerce.php for example code.
	 * 
	 * @since 3.0
	 * 
	 * @param $category_taxonomy_name (string) Name of the taxonomy.
	 * @param $post_type (string) will have the post type of the parent post where the image is used.
	 */
	$category_taxonomy_name = apply_filters( 'iaffpro_custom_attribute_tag_category_taxonomy', $category_taxonomy_name, $post_type );

	if ( $category_taxonomy_name === false ) {
		return '';
	}
	
	// Extract the names of categories associated with the post or product.
	$terms = get_the_terms( $parent_post_id, $category_taxonomy_name );

	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return '';
	}
	
	$categories = wp_list_pluck( $terms, 'name' );

	/**
	 * Filter the list of categories returned by %category% custom attribute tag. 
	 * Default value is first category name.
	 * 
	 * @since 3.0
	 * 
	 * @param $categories[0] (string) The first category available. This is the default value.
	 * @param $categories (array) Contains the names of all categories associated with $parent_post_id.
	 */
	return apply_filters( 'iaffpro_custom_attribute_tag_category_names', $categories[0], $categories );
}

/**
 * Return Tag name for Posts.
 * For %tag%
 * 
 * Can be extended to other post types using 'iaffpro_custom_attribute_tag_tag_taxonomy' filter. 
 * Returns first tag name by default. Can be altered using 'iaffpro_custom_attribute_tag_tag_names' filter. 
 * 
 * @since 3.0
 * 
 * @param $image_id The ID of the image that is being updated. 
 * @param $parent_post_id The post to which the image is attached (uploaded) to. 0 if the image is not attached to any post. 
 * @param $args (array) An array containing additional arguments.
 * 
 * @return (string) The first tag of the post. Can be modified using iaffpro_custom_attribute_tag_tag_names filter. Empty string otherwise.
 */
function iaffpro_get_custom_attribute_tag_tag( $image_id, $parent_post_id, $args = array() ) {

	// Check post type of parent post where the image is used. 
	$post_type = get_post_type( $parent_post_id );

	if ( $post_type === false ) {
		return '';
	}

	switch ( $post_type ) {
		
		// WordPress posts.
		case 'post':
			$tag_taxonomy_name = 'post_tag';
			break;
			
		default:
			$tag_taxonomy_name = false;
			break;
	}

	/**
	 * Filter $tag_taxonomy_name to extend %tag% custom attribute tag to other post types. 
	 * 
	 * Refer 3rd-party/woocommerce.php for example code.
	 * 
	 * @since 3.0
	 * 
	 * @param $tag_taxonomy_name (string) Name of the taxonomy.
	 * @param $post_type (string) will have the post type of the parent post where the image is used. 
	 */
	$tag_taxonomy_name = apply_filters( 'iaffpro_custom_attribute_tag_tag_taxonomy', $tag_taxonomy_name, $post_type );

	if ( $tag_taxonomy_name === false ) {
		return '';
	}
	
	// Extract the names of categories associated with the post or product.
	$terms = get_the_terms( $parent_post_id, $tag_taxonomy_name );

	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return '';
	}
	
	$tags = wp_list_pluck( $terms, 'name' );

	/**
	 * Filter the list of tags returned by %tag% custom attribute tag. 
	 * Default value is first tag name.
	 * 
	 * @since 3.0
	 * 
	 * @param $tags[0] (string) The first tag available. This is the default value.
	 * @param $tags (array) Contains the names of all tags associated with $parent_post_id.
	 */
	return apply_filters( 'iaffpro_custom_attribute_tag_tag_names', $tags[0], $tags );
}

/**
 * Return post excerpt.
 * For %excerpt%
 * 
 * WooCommerce product short description is saved as excerpt.
 * 
 * @since 3.2
 * 
 * @param $image_id The ID of the image that is being updated. 
 * @param $parent_post_id The post to which the image is attached (uploaded) to. 0 if the image is not attached to any post. 
 * @param $args (array) An array containing additional arguments.
 * 
 * @return String Post excerpt.
 */
function iaffpro_get_custom_attribute_tag_excerpt( $image_id, $parent_post_id, $args = array() ) {
	
	if ( $parent_post_id === 0 ) {
		return '';
	}

	/**
	 * Fetching post_excerpt directly instead of get_the_excerpt().
	 * get_the_excerpt() checks for post_password_required() and also filters the retrieved post excerpt.
	 */
	$post = get_post( $parent_post_id );

	if ( $post === NULL ) {
		return '';
	}
	
	return $post->post_excerpt;
}

/**
 * Return the image attribute that is present in the media library.
 * For %copymedialibrary%
 * 
 * @since 4.3
 * 
 * @param $image_id The ID of the image that is being updated. 
 * @param $parent_post_id The post to which the image is attached (uploaded) to. 0 if the image is not attached to any post. 
 * @param $args (array) An array containing additional arguments.
 * 
 * @return (string) The image attribute present in the media library.
 */
function iaffpro_get_custom_attribute_tag_copymedialibrary( $image_id, $parent_post_id, $args = array() ) {

	// Retrieve the image object.
	$attachment = get_post( $image_id );
	
	if ( ! is_object( $attachment ) ) {
		return '';
	}

	// Get Settings
	$settings = iaff_get_settings();

	/**
	 * Disable updating in media library to prevent chaining / looping of updates.
	 * 
	 * For example: custom attribute is "%copymedialibrary% - My Company Name" and image attributes are set to be 
	 * updated in media library and posts with the option to overwrite existing attributes.
	 * 
	 * Let's say an image with title "Hello World" is used in a product description and also in the prodct gallery.
	 * Here is what will happen:
	 * - First the image title will be updated to "Hello World - My Company Name".
	 * - Then when the image is discovered in the product, the media library will be updated again. Now the title becomes "Hello World - My Company Name - My Company Name".
	 * - Same happens when the image is discovered in the product gallery. Title becomes "Hello World - My Company Name - My Company Name - My Company Name".
	 * 
	 * Disabling updates in the media library when the bulk updater is set to update post HTML prevents this issue.
	 * It's not an ideal solution, but the best approximation we can have right now.
	 * 
	 * With this limitation, to add a value to the media library and then use it in the post HTML will need two passes of the bulk updater:
	 * - First pass with updates in post HTML disabled. The media library title will become "Hello World - My Company Name".
	 * - In the second pass, enable updates in post HTML. The media library will not be updated, but attributes will be copied to the post HTML.
	 * 
	 * @link https://app.asana.com/0/563315714509730/1204352908990817
	 */
	if ( isset( $settings['bu_title_location_post'] ) || isset( $settings['bu_alt_text_location_post'] ) ) {
		add_filter( 'iaffpro_update_media_library', '__return_false' );
	}

	switch ( $args['attribute'] ) {
		
		case 'title':

			return $attachment->post_title;
			break;
		
		case 'alt_text':
			
			$alt_text = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
			return $alt_text !== false ? $alt_text : '';
			break;
			
		case 'caption':

			return $attachment->post_excerpt;
			break;
		
		case 'description':

			return $attachment->post_content;
			break;

		default:
			return '';
	}
}

/**
 * Return the image title that is present in the media library.
 * For %imagetitle%
 * 
 * @since 4.3
 * 
 * @param $image_id The ID of the image that is being updated. 
 * @param $parent_post_id The post to which the image is attached (uploaded) to. 0 if the image is not attached to any post. 
 * @param $args (array) An array containing additional arguments.
 * 
 * @return (string) The image title from the media library.
 */
function iaffpro_get_custom_attribute_tag_imagetitle( $image_id, $parent_post_id, $args = array() ) {

	// Retrieve the image object.
	$attachment = get_post( $image_id );
	
	if ( ! is_object( $attachment ) ) {
		return '';
	}

	return $attachment->post_title;
}

/**
 * Return the image alt text that is present in the media library.
 * For %imagealttext%
 * 
 * @since 4.3
 * 
 * @param $image_id The ID of the image that is being updated. 
 * @param $parent_post_id The post to which the image is attached (uploaded) to. 0 if the image is not attached to any post. 
 * @param $args (array) An array containing additional arguments.
 * 
 * @return (string) The image alt text from the media library.
 */
function iaffpro_get_custom_attribute_tag_imagealttext( $image_id, $parent_post_id, $args = array() ) {
	$alt_text = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
	return $alt_text !== false ? $alt_text : '';
}

/**
 * Return the image caption that is present in the media library.
 * For %imagecaption%
 * 
 * @since 4.3
 * 
 * @param $image_id The ID of the image that is being updated. 
 * @param $parent_post_id The post to which the image is attached (uploaded) to. 0 if the image is not attached to any post. 
 * @param $args (array) An array containing additional arguments.
 * 
 * @return (string) The image caption from the media library.
 */
function iaffpro_get_custom_attribute_tag_imagecaption( $image_id, $parent_post_id, $args = array() ) {

	// Retrieve the image object.
	$attachment = get_post( $image_id );
	
	if ( ! is_object( $attachment ) ) {
		return '';
	}
	
	return $attachment->post_excerpt;
}

/**
 * Return the image description that is present in the media library.
 * For %imagedescription%
 * 
 * @since 4.3
 * 
 * @param $image_id The ID of the image that is being updated. 
 * @param $parent_post_id The post to which the image is attached (uploaded) to. 0 if the image is not attached to any post. 
 * @param $args (array) An array containing additional arguments.
 * 
 * @return (string) The image description from the media library.
 */
function iaffpro_get_custom_attribute_tag_imagedescription( $image_id, $parent_post_id, $args = array() ) {

	// Retrieve the image object.
	$attachment = get_post( $image_id );
	
	if ( ! is_object( $attachment ) ) {
		return '';
	}
	
	return $attachment->post_content;
}