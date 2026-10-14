<?php
/**
 * Functions related WooCommerce plugin.
 * 
 * @link https://wordpress.org/plugins/woocommerce/
 *
 * @since 3.0
 */

// Exit if accessed directly
if ( ! defined('ABSPATH') ) exit;

// Exit if WooCommerce is not active.
if ( ! in_array( 'woocommerce/woocommerce.php', get_option( 'active_plugins' ) ) ) {
	return;
}

/**
 * Add Bulk Actions to WooCommerce Products > All Products list view in WordPress admin.
 * Adds "Update image attributes" Bulk action. 
 * 
 * edit-product is the screen ID for WooCommerce product pages in WordPress admin.
 * 
 * @since 3.0
 * 
 * Refer admin/bulk-actions-posts-pages.php for function definitions.
 */ 
add_filter( 'bulk_actions-edit-product', 'iaffpro_add_bulk_action_update_image_attributes_to_posts' );
add_filter( 'handle_bulk_actions-edit-product', 'iaffpro_handle_posts_bulk_action_update_image_attributes', 10, 3 );

// Add WooCommerce Product category taxonomy to custom attribute tag %category%.
add_filter( 'iaffpro_custom_attribute_tag_category_taxonomy', 'iaffpro_add_wc_product_category_to_custom_attribute_tag_category', 10, 2 );

// Add WooCommerce Product tag taxonomy to custom attribute tag %tag%.
add_filter( 'iaffpro_custom_attribute_tag_tag_taxonomy', 'iaffpro_add_wc_product_tag_to_custom_attribute_tag_tag', 10, 2 );

// Update attributes of images in Product Gallery while updating attributes of a product.
add_action( 'iaffpro_after_update_attributes_in_post', 'iaffpro_wc_update_product_gallery_image_attributes' );

// Update image attributes after product import from CSV file.
add_action( 'woocommerce_product_import_inserted_product_object', 'iaffpro_wc_update_image_attributes_after_product_import_from_csv', 10, 2 );

// Add Meta Box to WooCommerce products.
add_action( 'add_meta_boxes_product', 'iaffpro_add_meta_box_posts_pages' );

// Add custom attribute tags for WooCommerce.
add_filter( 'iaff_custom_attribute_tags', 'iaffpro_add_custom_attribute_tags_for_wc' );

// Find WooCommerce products where an image is used.
add_filter( 'iaffpro_get_posts_by_attachment_id', 'iaffpro_wc_get_products_by_attachment_id', 10, 2 );

// Add WooCommerce related image used in text.
add_filter( 'iaffpro_image_used_in_text', 'iaffpro_wc_add_image_used_in_text' );

/**
 * Add WooCommerce Product category taxonomy to custom attribute tag %category%.
 * 
 * @since 3.0
 * 
 * @param $category_taxonomy_name (string) Name of the taxonomy.
 * @param $post_type (string) will have the post type of the parent post where the image is used.
 * 
 * @return $category_taxonomy_name (string) WooCommerce product category taxonomy name ('product_cat') if $post_type is 'product'.
 */
function iaffpro_add_wc_product_category_to_custom_attribute_tag_category ( $category_taxonomy_name, $post_type ) {

	// Check if post type is WooCommerce product. 
	if ( strcmp( $post_type, 'product' ) === 0 ) {
		return 'product_cat';
	}

	return $category_taxonomy_name;
}

/**
 * Add WooCommerce Product tag taxonomy to custom attribute tag %tag%.
 * 
 * @since 3.0
 * 
 * @param $tag_taxonomy_name (string) Name of the taxonomy.
 * @param $post_type (string) will have the post type of the parent post where the image is used.
 * 
 * @return $tag_taxonomy_name (string) WooCommerce product tag taxonomy name ('product_tag') if $post_type is 'product'.
 */
function iaffpro_add_wc_product_tag_to_custom_attribute_tag_tag ( $tag_taxonomy_name, $post_type ) {

	// Check if post type is WooCommerce product. 
	if ( strcmp( $post_type, 'product' ) === 0 ) {
		return 'product_tag';
	}

	return $tag_taxonomy_name;
}

/**
 * Update attributes of images in Product Gallery while updating attributes of a product.
 * 
 * This function is hooked on to iaffpro_after_update_attributes_in_post action in iaffpro_update_attributes_in_post_by_post_id().
 * Attributes of images in Product Gallery are updated in the Media Library.
 * 
 * @since 3.1
 * 
 * @param $post_id (integer) ID of the post that is being updated in iaffpro_update_attributes_in_post_by_post_id()
 */
function iaffpro_wc_update_product_gallery_image_attributes( $post_id ) {
	
	// Retrieve post type of the post.
	$post_type = get_post_type( $post_id );

	// Check if post type is WooCommerce product.
	if ( strcmp( $post_type, 'product' ) !== 0 ) {
		return;
	}

	// Retrieve Product Gallery image ID's.
	$product = new WC_product( $post_id );
    $product_gallery_image_ids = $product->get_gallery_image_ids();

    // Update every image in Product Gallery.
	foreach( $product_gallery_image_ids as $gallery_image ) {

		$parent_post_id = iaffpro_get_parent_post_of_image( $gallery_image );

		if ( $parent_post_id === 0 ) {
			$parent_post_id = $post_id;
		}

		$attributes = iaffpro_generate_image_attributes( $gallery_image, $parent_post_id, true );
		
		iaffpro_update_image( $gallery_image, $attributes, true );
	}
}

/**
 * Update image attributes after product import from CSV file.
 * 
 * WooCommerce has an inbuilt feature to import products in bulk from a CSV file. Here we are updating the image attributes 
 * after product import using iaffpro_update_attributes_in_post_by_post_id().
 * 
 * @since 4.1
 * 
 * @param $object (object) Object containing product data of the newly created product from CSV import.
 * @param $data (array) Product data from the CSV file.
 */
function iaffpro_wc_update_image_attributes_after_product_import_from_csv( $object, $data ) {
	
	// Update image attributes of all images in a given post.
	iaffpro_update_attributes_in_post_by_post_id( $object->get_id() );
}

/**
 * Add custom attribute tags for WooCommerce
 * - %wc_variation%
 * - %wc_variation_terms%
 * 
 * @since 4.8
 * 
 * @param $available_tags (array) Array containing all custom attribute tags.
 * 
 * @return $available_tags (array) Array with WooCommerce related tags added.
 */
function iaffpro_add_custom_attribute_tags_for_wc( $available_tags ) {

	$available_tags[ 'wc_variation' ] = __( 'WooCommerce product variation name', 'auto-image-attributes-pro' );
	$available_tags[ 'wc_variation_terms' ] = __( 'WooCommerce product variation terms', 'auto-image-attributes-pro' );

	return $available_tags;
}

/**
 * Custom attribute tag: Return WooCommerce product variation name.
 * For %wc_variation%
 * 
 * Returned name will include the attribute name and the term. 
 * Example: Color: Red, Size: Large.
 *
 * @since 4.8
 * 
 * @param $image_id The ID of the image that is being updated. 
 * @param $parent_post_id The post to which the image is attached (uploaded) to. 0 if the image is not attached to any post. 
 * @param $args (array) An array containing additional arguments.
 * 
 * @return (string) WooCommerce product variation name if available. Empty string otherwise. 
 */
function iaffpro_get_custom_attribute_tag_wc_variation( $image_id, $parent_post_id, $args = array() ) {

	// Ensure we have a valid variation post.
	if ( ! $parent_post_id || 'product_variation' !== get_post_type( $parent_post_id ) ) {
		return '';
	}

	// Get the variation object.
	$variation = wc_get_product( $parent_post_id );
	if ( ! $variation || ! $variation->is_type( 'variation' ) ) {
		return '';
	}

	//Get formatted variation name, e.g., "Color: Red, Size: Large".
	$variation_name = wc_get_formatted_variation( $variation, true );

	// Clean and return.
	return trim( wp_strip_all_tags( $variation_name, true ) );
}

/**
 * Custom attribute tag: Return WooCommerce product variation terms.
 * For %wc_variation_terms%
 * 
 * Returned name will include only the variation terms separated by commas.
 * Example: Red, Large
 *
 * @since 4.8
 * 
 * @param $image_id The ID of the image that is being updated. 
 * @param $parent_post_id The post to which the image is attached (uploaded) to. 0 if the image is not attached to any post. 
 * @param $args (array) An array containing additional arguments.
 * 
 * @return (string) WooCommerce product variation terms if available. Empty string otherwise. 
 */
function iaffpro_get_custom_attribute_tag_wc_variation_terms( $image_id, $parent_post_id, $args = array() ) {

	// Ensure we have a valid variation post.
	if ( ! $parent_post_id || 'product_variation' !== get_post_type( $parent_post_id ) ) {
		return '';
	}

	// Get the variation object.
	$variation = wc_get_product( $parent_post_id );
	if ( ! $variation || ! $variation->is_type( 'variation' ) ) {
		return '';
	}

	/**
	 * Get variation terms, e.g., "Red, Large".
	 * 
	 * To get variation terms only (e.g., Red, Large), 
	 * set the third param,`$include_names` to false.
	 * 
	 * @link https://bit.ly/wc_get_formatted_variation
	 */
	$variation_name = wc_get_formatted_variation( $variation, true, false );

	// Clean and return.
	return trim( wp_strip_all_tags( $variation_name, true ) );
}

/**
 * Find WooCommerce products where an image is used.
 * The results are merged to the findings of iaffpro_get_posts_by_attachment_id().
 * 
 * @since 4.8
 * 
 * @param $posts (array) An associative array of posts where the image is used.
 * @param $attachment_id (int) ID of the image.
 * 
 * @return $posts (array) Array with WooCommerce products added to the original $posts array.
 */
function iaffpro_wc_get_products_by_attachment_id( $posts, $attachment_id ) {

	/**
	 * Check if the image is used as a WooCommerce variation featured image.
	 */
	$used_as_variation_image = array();

	// Get all products where this image is a variation image
	$args = array(
		'post_type'   => 'product_variation',
		'meta_query'  => array(
			array(
				'key'     => '_thumbnail_id',
				'value'   => $attachment_id,
				'compare' => '='
			),
		),
		'posts_per_page' => 1,
		'fields' => 'ids',
	);

	$used_as_variation_image = get_posts( $args );
	$posts['20_wc_product_variation_image'] = $used_as_variation_image;

	/**
	 * Check if the image is added to a WooCommerce Product Gallery.
	 */
	$used_in_product_gallery = array();

	$product_gallery_query = new WP_Query( array(
		'meta_key'       => '_product_image_gallery',
		'meta_value'     => '\b' . $attachment_id . '\b',
		'meta_compare'   => 'REGEXP',
		'post_type'      => 'product',
		'post_status'    => 'any',
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'posts_per_page' => -1,
	) );

	$used_in_product_gallery = $product_gallery_query->posts;
	$used_in_product_gallery = array_unique( $used_in_product_gallery );

	$posts['30_wc_product_image_gallery'] = $used_in_product_gallery;

	return $posts;
}

/**
 * Add WooCommerce related image used in text.
 * 
 * @since 4.8
 * 
 * @param $used_in_text (array) Descriptions for the context where the image is used in.
 * 
 * @return $used_in_text (array) Descriptions with WooCommerce related context added to it.
 */
function iaffpro_wc_add_image_used_in_text( $used_in_text ) {

	$used_in_text[ '20_wc_product_variation_image' ] = __( '(as WooCommerce product variation image)', 'auto-image-attributes-pro' );
	$used_in_text[ '30_wc_product_image_gallery' ] = __( '(in WooCommerce product gallery)', 'auto-image-attributes-pro' );

	return $used_in_text;
}