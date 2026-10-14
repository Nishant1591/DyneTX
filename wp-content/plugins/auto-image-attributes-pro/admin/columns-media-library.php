<?php
/**
 * Add image attributes as columns in Media Library.
 * 
 * @since 4.4
 */

// Exit if accessed directly
if ( ! defined('ABSPATH') ) exit;

// Hide columns added by basic function first.
remove_filter( 'manage_media_columns', 'iaff_manage_media_columns_add_columns' );
remove_action( 'manage_media_custom_column', 'iaff_manage_media_custom_column_add_data', 10, 2 );

add_filter( 'manage_media_columns', 'iaffpro_manage_media_columns_add_columns', 11 );
add_action( 'manage_media_custom_column', 'iaffpro_manage_media_columns_add_column_data', 10, 2 );
add_filter( 'manage_upload_sortable_columns', 'iaffpro_manage_upload_sortable_columns_add_columns' );
add_action( 'pre_get_posts', 'iaffpro_pre_get_posts_media_columns_do_sort' );

add_action( 'wp_ajax_iaffpro_media_library_columns_edit_data', 'iaffpro_media_library_columns_edit_data_handler' );

/**
 * Add columns in Media Library for each of the image attributes.
 * 
 * @since 4.4
 * 
 * @param $columns (array) An array of columns displayed in the Media list table.
 */
function iaffpro_manage_media_columns_add_columns( $columns ) {

    $columns['iaffpro_image_title'] = __( 'Title', 'auto-image-attributes-pro' ) . '&nbsp; <span style="float: right;" title="Edit image attributes" class="dashicons dashicons-edit iaffpro-dashicons-edit" data-image-attribute="iaffpro_image_title"></span>';
    $columns['iaffpro_image_alt'] = __( 'Alternative Text', 'auto-image-attributes-pro' ) . '&nbsp; <span style="float: right;" title="Edit image attributes" class="dashicons dashicons-edit iaffpro-dashicons-edit" data-image-attribute="iaffpro_image_alt"></span>';
    $columns['iaffpro_image_caption'] = __( 'Caption', 'auto-image-attributes-pro' ) . '&nbsp; <span style="float: right;" title="Edit image attributes" class="dashicons dashicons-edit iaffpro-dashicons-edit" data-image-attribute="iaffpro_image_caption"></span>';
    $columns['iaffpro_image_description'] = __( 'Description', 'auto-image-attributes-pro' ) . '&nbsp; <span style="float: right;" title="Edit image attributes" class="dashicons dashicons-edit iaffpro-dashicons-edit" data-image-attribute="iaffpro_image_description"></span>';

    return $columns;
}

/**
 * Add data to the custom columns created in Media Library.
 * 
 * @since 4.4
 * 
 * @param (string) $column_name Name of the custom column.
 * @param (int) $attachment_id Attachment ID.
 */
function iaffpro_manage_media_columns_add_column_data( $column_name, $attachment_id ) {

    $image = get_post( $attachment_id );
    $column_data = false;
    
    switch ( $column_name ) {

		case 'iaffpro_image_title':
			$column_data = $image->post_title;
			break;
        
        case 'iaffpro_image_alt':
            $column_data = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
            break;
        
        case 'iaffpro_image_caption':
            $column_data = $image->post_excerpt;
            break;

        case 'iaffpro_image_description':
            $column_data = $image->post_content;
            break;
	}

    if ( $column_data !== false ) {

        $column_data = esc_html( $column_data );
        
        $column_html = '
            <span 
                id="' . $column_name . '_data_' . $attachment_id . '" 
                class="' . $column_name . '_data" 
                data-image-attribute="' . $column_name . '"
            >' . $column_data . ' 
            </span>
            <input 
                style="display: none; width: 100%;" 
                id="' . $column_name . '_input_' . $attachment_id . '" 
                class="' . $column_name . '_input" 
                data-' . $column_name . '="' . $column_data . '" 
                data-' . $column_name . '_attachment_id="' . $attachment_id . '" 
                tabindex="47" 
                value="' . $column_data . '"
            >
            <span  style="display: none;" class="iaffpro-' . $column_name . '-save-all-spinner spinner is-active"></span>
            <span  style="display: none; color: #46B450" class="iaffpro-' . $column_name . '-save-all-success attachment_id_' . $attachment_id . '">' . __( 'Saved!', 'auto-image-attributes-pro' ) . '</span>';

        echo $column_html;
    }
}

/**
 * Make image title and alt text columns sortable.
 * 
 * @since 4.4
 * 
 * @param $columns (array) An array of sortable columns.
 */
function iaffpro_manage_upload_sortable_columns_add_columns( $columns ) {

    $columns['iaffpro_image_title'] = 'iaffpro_image_title';
    $columns['iaffpro_image_alt'] = 'iaffpro_image_alt';
    $columns['iaffpro_image_caption'] = 'iaffpro_image_caption';
    $columns['iaffpro_image_description'] = 'iaffpro_image_description';

    return $columns;
}

/**
 * Add sorting logic for image title and alt text.
 * 
 * @since 4.4
 * 
 * @param $query The WP_Query instance.
 */
function iaffpro_pre_get_posts_media_columns_do_sort( $query ) {

    switch( $query->get( 'orderby' ) ) {
        case 'iaffpro_image_title':
            $query->set( 'orderby', 'title' );
            break;
        
        case 'iaffpro_image_alt':
            $query->set( 'orderby', 'meta_value' );
            $query->set(
                'meta_query',
                [
                    'relation' => 'OR',
                    [
                        'key'     => '_wp_attachment_image_alt',
                        'compare' => 'NOT EXISTS',
                    ],
                    [
                        'key'     => '_wp_attachment_image_alt',
                        'compare' => 'EXISTS',
                    ],
                ]
            );
            break;
        
        case 'iaffpro_image_caption':
            // Refer iaffpro_modify_posts_request_sql_order_by_for_caption() for details.
            $query->set( 'orderby', 'title' );
            add_filter( 'posts_request', 'iaffpro_modify_posts_request_sql_order_by_for_caption', 10, 2 );
            break;
        
        case 'iaffpro_image_description':
            // Refer iaffpro_modify_posts_request_sql_order_by_for_description() for details.
            $query->set( 'orderby', 'title' );
            add_filter( 'posts_request', 'iaffpro_modify_posts_request_sql_order_by_for_description', 10, 2 );
            break;
    }
}

/**
 * Replace post_title in ORDER BY with post_excerpt to sort captions.
 * 
 * There is no post_excerpt available in the ORDER BY Parameters.
 * @link https://developer.wordpress.org/reference/classes/wp_query/#order-orderby-parameters
 * 
 * So here we filter posts_request SQL query and replace post_title (Title) with post_excerpt (Caption).
 * The SQL string looks like before replacement:
 * 
 * SELECT SQL_CALC_FOUND_ROWS  wp_posts.* FROM wp_posts 
 *      WHERE 1=1  
 *      AND wp_posts.post_type = 'attachment' 
 *      AND ((wp_posts.post_status = 'inherit' OR wp_posts.post_status = 'private'))
 *      ORDER BY wp_posts.post_title 
 *      ASC LIMIT 0, 20
 */
function iaffpro_modify_posts_request_sql_order_by_for_caption( $request, $query  ) {
    $request = str_replace( '.post_title', '.post_excerpt', $request );
    return $request;
}

/**
 * Replace post_title in ORDER BY with post_content to sort Descriptions.
 * 
 * There is no post_content available in the ORDER BY Parameters.
 * @link https://developer.wordpress.org/reference/classes/wp_query/#order-orderby-parameters
 * 
 * So here we filter posts_request SQL query and replace post_title (Title) with post_content (Description).
 * The SQL string looks like before replacement:
 * 
 * SELECT SQL_CALC_FOUND_ROWS  wp_posts.* FROM wp_posts 
 *      WHERE 1=1  
 *      AND wp_posts.post_type = 'attachment' 
 *      AND ((wp_posts.post_status = 'inherit' OR wp_posts.post_status = 'private'))
 *      ORDER BY wp_posts.post_title 
 *      ASC LIMIT 0, 20
 */
function iaffpro_modify_posts_request_sql_order_by_for_description( $request, $query  ) {
    $request = str_replace( '.post_title', '.post_content', $request );
    return $request;
}

/**
 * Handles the ajax request to update image attributes in Media Library columns.
 * Used from media-library.js
 * 
 * @since 4.4
 * @since 4.9 Check using wp_attachment_is_image() is removed to enable updates for non images.
 */
function iaffpro_media_library_columns_edit_data_handler() {
	
    check_ajax_referer( 'iaffpro_media_library_js_nonce', 'security' );

    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( 
            __( 'It looks like you do not have the capability to edit that. Kindly check with your Administrator to upgrade your capability.', 'auto-image-attributes-pro' ), 
            403 // Forbidden HTTP status code.
        );
    }
	
    $image_data = isset( $_POST['data'] ) ? $_POST['data'] : '';

    // Check if sent data is array. Also handles cases where empty value is passed. 
    if ( ! is_array( $image_data ) ) {
        wp_send_json_success();
    }

    foreach( $image_data as $image ) {

        // Sanitize.
        $image_id = (int) $image['id'];
        $image_attribute = sanitize_text_field( $image['value'] );

        $updated_image = array();
        $updated_image['ID'] = $image_id;
        
        switch( $image['attribute'] ) {
        
            case 'iaffpro_image_title':
                $updated_image['post_title'] = $image_attribute;
                wp_update_post( $updated_image );
                break;
    
            case 'iaffpro_image_alt':
                update_post_meta( $updated_image['ID'], '_wp_attachment_image_alt', $image_attribute );
                break;
    
            case 'iaffpro_image_caption':
                $updated_image['post_excerpt'] = $image_attribute;
                wp_update_post( $updated_image );
                break;
    
            case 'iaffpro_image_description':
                $updated_image['post_content'] = $image_attribute;
                wp_update_post( $updated_image );
                break;
        }

        // Update image attributes in post HTML.
        $post = get_post( $image_id, ARRAY_A );
        iaffpro_attachment_fields_to_save_update_image_attributes_in_post( $post );
    }

    wp_send_json_success();
    wp_die();
}