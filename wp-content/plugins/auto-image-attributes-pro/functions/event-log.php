<?php
/**
 * Event Logging Functions.
 *
 * @since 4.0
 */

// Exit if accessed directly.
if ( ! defined('ABSPATH') ) exit;

// Create log folder
add_action( 'iaff_before_bulk_updater', 'iaffpro_event_log_init_folder' );

// Handle AJAX request to delete Event Log when delete button is clicked.
add_action( 'wp_ajax_iaffpro_bulk_updater_delete_log', 'iaffpro_event_log_delete_handle_ajax' );

// Handles ajax request to check if event log has updated.
add_action( 'wp_ajax_iaffpro_check_event_log_revision', 'iaffpro_check_event_log_revision' );

// Handles ajax request to fetch new event log entries since last offset.
add_action( 'wp_ajax_iaffpro_fetch_new_event_log', 'iaffpro_fetch_new_event_log' );

/**
 * Create /iap-logs folder and initialize it with .htaccess and index.html.
 * 
 * @since 4.0
 */
function iaffpro_event_log_init_folder() {

	// Initialize the WP filesystem
	iaffpro_wp_filesystem_init();
	global $wp_filesystem;

	$upload_dir 	= wp_get_upload_dir();
	$logs_dir 		= $upload_dir['basedir'] . '/iap-logs';

	// Return if files already exist.
	if ( $wp_filesystem->exists( trailingslashit( $logs_dir ) . '.htaccess' ) ) {
		return;
	}

	// Create folder /iap-logs/ in WordPress uploads folder.
	if ( wp_mkdir_p( $logs_dir ) ) {

		// Add .htaccess to prevent hotlinking.
		iaffpro_file_put_contents( trailingslashit( $logs_dir ) . '.htaccess', 'deny from all' );

		// Add empty index.html file into the folder.
		iaffpro_file_put_contents( trailingslashit( $logs_dir ) . 'index.html', '' );
	}
}

/**
 * Append log to the log file.
 * 
 * @since 4.0
 * 
 * @param $content (string) Content to be logged.
 * @param $timestamp (boolean) Set to false to prevent adding of timestamp to log. True by default.
 * 
 * @return (bool) True on success, false on failure.
 */
function iaffpro_event_log_append( $content, $timestamp = true ) {

	/**
	 * Check if event logging is disabled.
	 * 
	 * @since 4.7
	 * 
	 * @param (bool) Set as true to disable event log file generation. Default to false.
	 */
	if ( apply_filters( 'iaffpro_disable_event_logging', false ) ) {
		return false;
	}

	$upload_dir 	= wp_get_upload_dir();
	$log_file 		= $upload_dir['basedir'] . '/iap-logs/iap-bu-log.log';

	// Read the current log file.
	$file_content = iaffpro_file_get_contents( $log_file );

	// Add timestamp
	$content = ( $timestamp === true ) ? date( 'd-M-Y H:i:s e', time() ) . ' : ' . $content : $content;

	// Append new content to file if the file is not empty.
	$file_content = ( $file_content === false ) ? $content : $file_content . PHP_EOL . $content;

	// Write the log to file.
	$written = iaffpro_file_put_contents( $log_file, $file_content );

	if ( $written !== false ) {
		// Set a flag to be used later to update the Event Log with ajax.
		set_transient( 'iaffpro_event_log_updated_time', time(), HOUR_IN_SECONDS );
	}

	return (bool) $written;
}

/** 
 * Read event log from the log file.
 * 
 * @param $limit (int) Number of lines to read. If a limit n is specified, the last n lines are read. 0 reads all lines. Defaults to 10,000.
 * 
 * @return (array) Log entires from the log file as an array. Empty array on failure.
 * 
 * @since 4.0
 * @since 4.4 Modified the default value of $limit from 0 to 10,000. This prevents stalling of UI from a huge log file.
 */
function iaffpro_event_log_read( $limit = 10000 ) {

	$upload_dir 	= wp_get_upload_dir();
	$log_file 		= $upload_dir['basedir'] . '/iap-logs/iap-bu-log.log';

	// Read the current log file.
	$file_content = iaffpro_file_get_contents( $log_file, true );

	if ( $file_content === false ) {
		return array();
	}

	if ( ( $limit > 0 ) && ( count( $file_content ) > $limit ) ) {
		
		// Get the last n items of the array.
		$file_content = array_slice( $file_content, - $limit );
		
		// Insert a message to the top of the array.
		array_unshift( 
			$file_content, 
			'<p><span class="dashicons dashicons-info"></span> ' . sprintf( __( 'Latest 10,000 log entries are displayed below. Full log is available on the server here: %s', 'auto-image-attributes-pro' ), $log_file ) . '</p>' 
		);
	}

	return $file_content;
}

/**
 * Delete Event Log file.
 * 
 * @since 4.1
 * 
 * @return (bool) True on success, false otherwise.
 */
function iaffpro_event_log_delete() {

	$upload_dir = wp_get_upload_dir();
	$log_file 	= $upload_dir['basedir'] . '/iap-logs/iap-bu-log.log';
	
	return iaffpro_file_delete( $log_file );
}

/**
 * Handle AJAX request to delete Event Log when delete button is clicked.
 * 
 * @since 4.1
 */
function iaffpro_event_log_delete_handle_ajax() {
	
	// Security Check
	check_ajax_referer( 'iaffpro_bulk_updater_delete_log_nonce', 'security' );

	// Delete the event log
	$file_deleted = iaffpro_event_log_delete();

	$response_message = $file_deleted ? 
		__( 'Event Log file deleted.', 'auto-image-attributes-pro' ) :
		__( 'Event Log file could not be deleted. Please delete it manually in /wp-content/uploads/iap-logs/iap-bu-log.log', 'auto-image-attributes-pro' );
	
	$response = array(
		'message' => $response_message,
	);
	wp_send_json( $response );
}

/**
 * Handles ajax request to check if event log has updated.
 * 
 * When event log is updated, the last updated time() is saved in a transient iaffpro_event_log_updated_time. 
 * This is checked with the last checked time in event-log.js to determine if the event log has 
 * been modified or not. 
 * 
 * @since 4.9
 */
function iaffpro_check_event_log_revision() {

	// Security
	check_ajax_referer( 'iaffpro_event_log_update_nonce', 'security' );

	$revision = get_transient( 'iaffpro_event_log_updated_time' );
	$revision = $revision ? $revision : 0;

	$completed = iaff_count_remaining_images() == 0 ? true : false;

	wp_send_json_success( array(
		'revision'	=> (int) $revision,
		'completed'	=> $completed,
	));
}

/**
 * Handles ajax request to fetch new event log entries since last offset.
 *
 * @since 4.9
 */
function iaffpro_fetch_new_event_log() {

	// Security
	check_ajax_referer( 'iaffpro_event_log_update_nonce', 'security' );

	$offset = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;

	$lines = iaffpro_event_log_read();
	$total_lines = count( $lines );

	// Get only new lines
	$new_lines = array_slice( $lines, $offset );

	wp_send_json_success( array(
		'lines'  => $new_lines,
		'offset' => $total_lines,
	));
}