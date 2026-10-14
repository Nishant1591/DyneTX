<?php
/**
 * Functions to find where an image is used.
 * 
 * A good portion of the following code is from the "Find Posts Using Attachment" WordPress plugin
 * by Sergey Biryukov (http://profiles.wordpress.org/sergeybiryukov/)
 *
 * @since 3.0
 * 
 * @link https://wordpress.org/plugins/find-posts-using-attachment/
 */

// Exit if accessed directly
if ( ! defined('ABSPATH') ) exit;

/**
 * Find posts and WooCommerce products where an image is used. 
 * 
 * @since 1.0 (revised and moved to separate file in 3.0).
 * 
 * @param $attachment_id (integer) ID of the image.
 * 
 * @return $posts (array|boolean) An array of all the post ID's that use the image. False when attachment is not an image.
 */
function iaffpro_get_posts_by_attachment_id( $attachment_id ) {

	// Retrieve saved data from transient if available.
	$posts = get_transient( 'iaffpro_get_posts_by_attachment_id_' . $attachment_id );

	if ( $posts !== false ) {
		return $posts;
	}
	
	// Determines whether an attachment is an image.
	if ( ! wp_attachment_is_image( $attachment_id ) ) {
		return false;
	}

	/**
	 * Password protected posts and private posts are only available when logged in.
	 * We set the current user to 1 (admin) and then change it back after we are done.
	 * 
	 * @link https://developer.wordpress.org/reference/classes/wp_query/#status-parameters
	 */
	$current_user_ID = get_current_user_id();
	wp_set_current_user(1);

	/**
	 * Check if the image is used as Featured image on a post or Product image on a WooCommerce product.
	 * This will also check work for any custom post type that uses the Featured Image. 
	 */

	$used_as_thumbnail = array();

	$thumbnail_query = new WP_Query( array(
		'meta_key'       => '_thumbnail_id',
		'meta_value'     => $attachment_id,
		'post_type'      => 'any',
		'post_status'    => 'any',
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'posts_per_page' => -1,
	) );

	$used_as_thumbnail = $thumbnail_query->posts;
	$used_as_thumbnail = array_unique( $used_as_thumbnail );

	/**
	 * Check if the image is used in any post within the post HTML.
	 * To do so, all the image sizes are gathered and searched recursively on all post types. 
	 */

	// An array to hold all the image sizes for the given image.
	$attachment_urls = array( wp_get_attachment_url( $attachment_id ) );

	// Extracting url's of all the image sizes for the given image.
	foreach ( get_intermediate_image_sizes() as $size ) {
		$intermediate = image_get_intermediate_size( $attachment_id, $size );
		if ( $intermediate ) {
			$attachment_urls[] = $intermediate['url'];
		}
	}

	$used_in_content = array();

	// Search query to find post id's that use each image url.
	foreach ( $attachment_urls as $attachment_url ) {
		$content_query = new WP_Query( array(
			's'              => $attachment_url,
			'post_type'      => 'any',
			'post_status'    => 'any',
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'posts_per_page' => -1,
		) );

		$used_in_content = array_merge( $used_in_content, $content_query->posts );
	}

	$used_in_content = array_unique( $used_in_content );

	/**
	 * Check if the image is uploaded to a post.
	 * 
	 * wp_get_post_parent_id() will return ID of the post to which the image is 
	 * uploaded to (saved as post_parent in the database). 
	 * Returns 0 if there is no parent or false if the $attachment_id does not exist.
	*/
	$uploaded_to_post = array();
	$parent_post = wp_get_post_parent_id( $attachment_id );

	if ( ( $parent_post !== 0 ) && ( $parent_post !== false ) ) {
		$uploaded_to_post[0] = $parent_post;
	}

	wp_set_current_user( $current_user_ID );

	// Combine into a single array.
	$posts = array(
		'10_thumbnail'		=> $used_as_thumbnail,
		'40_content'		=> $used_in_content,
		'50_uploaded_to'	=> $uploaded_to_post,
	);

	/**
	 * Filter $posts array.
	 * Used in /3rd-party/woocommerce.php to add search in WooCommerce products.
	 * 
	 * @since 4.8
	 * 
	 * @param $posts (array) An associative array of posts where the image is used.
	 * @param $attachment_id (int) ID of the image.
	 */
	$posts = apply_filters( 'iaffpro_get_posts_by_attachment_id', $posts, $attachment_id );

	/**
	 * Sort an array by key in ascending order.
	 * 
	 * When an image is used on multiple posts / products, Image Attributes Pro uses the first post 
	 * from this array to generate image attributes.
	 * 
	 * For example, if Bulk Updater is configured to use Post Title as image attribute, 
	 * the post title of the first post from this array will be used in the media library. 
	 * 
	 * So the order here is important. Array keys are numbered to make sorting possible. 
	 * Priority in descending order is given below. Highest priority first.
	 * 
	 * - 10_thumbnail: If the image is used as a featured image, then that has the highest preference.
	 * - 20_wc_product_variation_image: WooCommerce product variation image.
	 * - 30_wc_product_image_gallery: WooCommerce product where the image is part of the product gallery.
	 * - 40_content: Posts where image is used within the content.
	 * - 50_uploaded_to: The post ID to with the image is uploaded to.
	 * 
	 * Array keys are prefixed with a number to make sorting predictable.
	 * 
	 * @link https://imageattributespro.com/image-attributes-pro-behavior-same-image-on-multiple-posts/
	 */
	ksort( $posts );

	// Save results to a transient.
	set_transient( 'iaffpro_get_posts_by_attachment_id_' . $attachment_id , $posts, 60 );

	return $posts;
}

/**
 * Combines the output of iaffpro_get_posts_by_attachment_id() into one single array.
 * 
 * Image Attributes Pro only needs the combined array. 
 * Separate results are stored for display in Media library and was part of the "Find Posts Using Attachment" plugin.
 * 
 * @since 3.0
 * 
 * @param $attachment_id (integer) ID of the image.
 * 
 * @return (array|boolean) Array containing all the post ID's where the image is used. False if iaffpro_get_posts_by_attachment_id() returns false.
 */
function iaffpro_get_all_posts_by_attachment_id_combined( $attachment_id ) {
	
	$post_ids = iaffpro_get_posts_by_attachment_id( $attachment_id );

	if ( $post_ids === false ) {
		return false;
	}

	$all_posts_with_image = call_user_func_array( 'array_merge', array_values( $post_ids ) );
	$all_posts_with_image = array_values( array_unique( $all_posts_with_image ) );

	return $all_posts_with_image;
}

/**
 * Returns HTML output listing posts where the image is used. 
 * 
 * Two context is available to use depending on where the HTML is used. 
 * Context: column. When output is in media library. (Not used by Image Attributes Pro as of 3.1)
 * Context: details. When output is used elsewhere. Image Attributes Pro 3.1 uses this. 
 * 
 * @since 3.0 (and used by Image Attributes Pro since 3.1)
 * 
 * @param $attachment_id (integer) ID of the image. 
 * @param $context (string) 'column' or 'details'.
 * 
 * @return (string) HTML output that lists posts where the image is used. 
 */
function iaffpro_get_posts_using_attachment( $attachment_id, $context ) {

	// 'column' is for display in Media Library and details for the meta box.
	switch ( $context ) {
		case 'column':
			$item_format   = '<strong>%1$s</strong> %2$s<br />';
			$output_format = '%s';
			break;
		case 'details':
		default:
			$item_format   = '%1$s %2$s<br />';
			$output_format = '<div>%s</div>';
			break;
	}
	
	$all_posts	= iaffpro_get_posts_by_attachment_id( $attachment_id );
	$output 	= '';

	// Return early if attachment isn't an image.
	if ( $all_posts === false ) {
		$output = __( '(Attachment is not an image.)', 'auto-image-attributes-pro' );
		return sprintf( $output_format, $output );
	}
	
	// Description for the context where the image is used in.
	$used_in_text = array(
		'10_thumbnail'		=> __( '(as featured image)', 'auto-image-attributes-pro' ),
		'40_content'		=> __( '(in content)', 'auto-image-attributes-pro' ),
		'50_uploaded_to'	=> __( '(uploaded to post)', 'auto-image-attributes-pro' ),
	);

	/**
	 * Filter image used in context.
	 * Used in /3rd-party/woocommerce.php for WooCommerce related images.
	 * 
	 * @since 4.8
	 * 
	 * @param $used_in_text (array) Descriptions for the context where the image is used in.
	 */
	$used_in_text = apply_filters( 'iaffpro_image_used_in_text', $used_in_text );

	foreach ( $all_posts as $context => $posts ) {

		foreach ( $posts as $post_id ) {
			
			$post = get_post( $post_id );
			if ( ! $post ) {
				continue;
			}

			$post_title = _draft_or_post_title( $post );
			$post_type  = get_post_type_object( $post->post_type );

			if ( $post_type && $post_type->show_ui && current_user_can( 'edit_post', $post_id ) ) {
				$link = sprintf( '<a href="%s">%s</a>', get_edit_post_link( $post_id ), $post_title );
			} else {
				$link = $post_title;
			}

			$output .= sprintf( $item_format, $link, $used_in_text[$context] );
		}
	}

	if ( ! $output ) {
		$output = __( '(Image is not used in any post.)', 'auto-image-attributes-pro' );
	}

	return sprintf( $output_format, $output );
}

/**
 * The following code from "Find Posts Using Attachment" WordPress plugin is left here for debug purposes only. 
 * The plugin adds a column in the WordPress Media Library titled "Used In" which lists the posts and products where the image is used. 
 */

// add_filter( 'attachment_fields_to_edit', 'iaffpro_attachment_fields_to_edit', 10, 2 );
// add_filter( 'manage_media_columns', 'iaffpro_manage_media_columns' );
// add_action( 'manage_media_custom_column', 'iaffpro_manage_media_custom_column', 10, 2 );

function iaffpro_attachment_fields_to_edit( $form_fields, $attachment ) {
	$form_fields['used_in'] = array(
		'label' => __( 'Used In', 'auto-image-attributes-pro' ),
		'input' => 'html',
		'html'  => iaffpro_get_posts_using_attachment( $attachment->ID, 'details' ),
	);

	return $form_fields;
}

function iaffpro_manage_media_columns( $columns ) {
	$filtered_columns = array();

	foreach ( $columns as $key => $column ) {
		$filtered_columns[ $key ] = $column;

		if ( 'parent' === $key ) {
			$filtered_columns['used_in'] = __( 'Used In', 'auto-image-attributes-pro' );
		}
	}

	return $filtered_columns;
}

function iaffpro_manage_media_custom_column( $column_name, $attachment_id ) {
	switch ( $column_name ) {
		case 'used_in':
			echo iaffpro_get_posts_using_attachment( $attachment_id, 'column' );
			break;
	}
}

/**
 * Delete transients.
 * 
 * As of version 4.8, this is called after bulk updater is completed.
 * In iaffpro_bu_bulk_updater_process().
 * 
 * @since 4.8 Delete transient iaffpro_get_posts_by_attachment_id_$attachment_id and it's timeout.
 */
function iaffpro_delete_transients() {

	global $wpdb;
	
	$prefix = '_transient_iaffpro_get_posts_by_attachment_id_';
	$timeout_prefix = '_transient_timeout_iaffpro_get_posts_by_attachment_id_';

	// Delete transients and their timeouts.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} 
			WHERE option_name LIKE %s
			OR option_name LIKE %s",
			$wpdb->esc_like( $prefix ) . '%',
			$wpdb->esc_like( $timeout_prefix ) . '%'
	));
}