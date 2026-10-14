<?php
/**
 * Plugin - WP ADA Compliance Check
 * functions to display dashboard items
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * Add dashboard widget with stats
 **/
function wp_ada_compliance_basic_dashboard_widgets() {
	global $wp_meta_boxes;
	if ( current_user_can( 'edit_pages' ) ) {
		wp_add_dashboard_widget( 'wp_ada_compliance_basic_stats_widget', 'Web Accessibility Summary', 'wp_ada_compliance_basic_dashboard_stats' );

	}
}
/**
 * Dashboard statistics
 */
function wp_ada_compliance_basic_dashboard_stats() {
	echo '<div class="wp_ada_dashboard_widget">';
	wp_ada_compliance_basic_dashboard_summary();
	echo '<p style="text-align:center; clear:both;"><a href="' . esc_url( get_site_url() ) . '/wp-admin/admin.php?page=ada_compliance/compliancereportbasic.php" class="btnwpada btnwpada-primary">';
	esc_html_e( 'View Report', 'wp-ada-compliance-basic' );
	echo '</a></p>';
	echo '</div>';
}

/**
 * Create dashboard summary
 */
function wp_ada_compliance_basic_dashboard_summary() {
	global $wpdb;
	// count number of records  and display message.
	$totalitems = wp_ada_compliance_basic_count_total_scan_records();

	// count total number of pages with issues.
	$totalpages = $wpdb->get_var( $wpdb->prepare( 'SELECT count(DISTINCT postid) FROM ' . $wpdb->prefix . 'wp_ada_compliance_basic where ignre != %d', 1 ) );

	// count total number of issues.
	$totalissues = $wpdb->get_var( $wpdb->prepare( 'SELECT count(*) FROM ' . $wpdb->prefix . 'wp_ada_compliance_basic where ignre !=%d', 1 ) );

	// count protected issues.
	$totalprotected = $wpdb->get_var( $wpdb->prepare( 'SELECT count(*) FROM ' . $wpdb->prefix . 'wp_ada_compliance_basic where ignre =%d', 2 ) );

	// count ignored issues.
	$totalignored = $wpdb->get_var( $wpdb->prepare( 'SELECT count(*) FROM ' . $wpdb->prefix . 'wp_ada_compliance_basic where ignre =%d', 1 ) );

	$mediafileswithoutalt = $wpdb->get_var( $wpdb->prepare( 'SELECT count(*) FROM ' . $wpdb->prefix . 'posts e WHERE (NOT EXISTS (SELECT null FROM ' . $wpdb->prefix . 'postmeta d WHERE d.post_id = e.ID and meta_key = %s) or EXISTS (SELECT post_id FROM ' . $wpdb->prefix . 'postmeta d WHERE d.post_id = e.ID and meta_key = %s and meta_value = %s )) and post_type = %s AND post_mime_type LIKE %s ', '_wp_attachment_image_alt', '_wp_attachment_image_alt', '', 'attachment', '%image/%' ) );

	// determine grade < 20% green light  21-50% yellow 50-100% red.
	if ( 0 == $totalitems ) {
		$percent        = 0;
		$percentdisplay = 0;
		$score = '';
	} else {
		$percent        = round( ( $totalpages ) / ( $totalitems ) * 100, PHP_ROUND_HALF_UP );
		$percentdisplay = round( ( $totalpages ) / ( $totalitems ) * 100 );
	}
	// account for last 1% bug.
	if ( $percent < 1 && $totalissues > 0 ) {
		$percent        = 1;
		$percentdisplay = 1;
	}

	if ( $percent < 20 ) {
		$class = 'wp_ada_light_green';
	} elseif ( $percent >= 20 && $percent < 50 ) {
		$class = 'wp_ada_light_yellow';
	} elseif ( $percent >= 50 ) {
		$class = 'wp_ada_light_red';
	} else {
		$class = '';
	}

	//$score = "NR";

	// display protected issues.
	echo '<ul class="wp_ada_summary_left ">';
	if ( $totalprotected > 0 ) {
		echo '<li class="adaViewbar adaRedText">';
		esc_html_e( 'Automatic protection could be zapping ', 'wp-ada-compliance-basic' );
		echo esc_html( $totalprotected );
		esc_html_e( ' issues! ', 'wp-ada-compliance-basic' );
		echo '<br />';
		echo '<a href="https://www.wpadacompliance.com/" class="adaRedText">';
		esc_html_e( 'Upgrade to the full version to enable this option', 'wp-ada-compliance-basic' );
		echo '</a>';
		echo '</li>';
	}
	if ( $totalitems > 15 ) {
		echo '<li class="adaViewbar adaRedText">';
		echo esc_html( $totalitems - 15 );
		esc_html_e( ' posts or pages are unprotected ', 'wp-ada-compliance-basic' );
		echo '<br /><a href="https://www.wpadacompliance.com/" class="adaRedText">';
		esc_html_e( 'Upgrade to the full version to protect all your content', 'wp-ada-compliance-basic' );
		echo '</a>';
		echo '</li>';
	}

	echo '<li class="adaViewbar adaRedText">';
	echo esc_html( $mediafileswithoutalt );
	esc_html_e( ' media library images are missing alt text', 'wp-ada-compliance-basic' );
	echo '<br /><a href="https://www.wpadacompliance.com/" class="adaRedText">';
	esc_html_e( 'Upgrade to the full version to scan media library images for missing alt text', 'wp-ada-compliance-basic' );
	echo '</a>';
	echo '</li>';

	echo '<li class="adaViewbar adaRedText">';
	esc_html_e( 'No theme files, widgets or category pages have been checked ', 'wp-ada-compliance-basic' );
	echo '<br /><a href="https://www.wpadacompliance.com/" class="adaRedText">';
	esc_html_e( 'Upgrade to the full version to protect all your content', 'wp-ada-compliance-basic' );
	echo '</a>';
	echo '</li>';

	echo '<li class="adaViewbar">';
	esc_html_e( 'Your website has ', 'wp-ada-compliance-basic' );
	echo esc_html( $totalpages );
	esc_html_e( ' posts or pages with issues ', 'wp-ada-compliance-basic' );
	echo '</li>';

	// display total issues.
	echo '<li class="adaViewbar">';
	esc_html_e( 'Issues: ', 'wp-ada-compliance-basic' );
	echo esc_html( $totalissues );
	esc_html_e( ' issues found in ', 'wp-ada-compliance-basic' );
	echo esc_html( $totalitems );
	esc_html_e( ' items ', 'wp-ada-compliance-basic' );
	echo '</li>';

	// display ignored issues.
	$total = $wpdb->get_var( $wpdb->prepare( 'SELECT count(*) FROM ' . $wpdb->prefix . 'wp_ada_compliance_basic where ignre =%d', 1 ) );
	echo '<li class="adaViewbar">';
	esc_html_e( 'Ignored: You are ignoring ', 'wp-ada-compliance-basic' );
	echo esc_html( $totalignored );
	esc_html_e( ' issues ', 'wp-ada-compliance-basic' );
	echo '</li>';

	echo '<li class="adaViewbar ' . esc_attr( $class ) . ' wp_ada_light" style="max-width: 500px; white-space: normal;"><span class="wpda-compliance-score">' . esc_attr( $score ) . '<span class="wpda-compliance-score-description wp-ada-screen-reader-text">';
	echo '<span class="adaRedText">' . esc_html__( ' Your WP ADA Compliance Check accessibility score is: NOT RATED (Upgrade to the full version to unlock your rating) - ', 'wp-ada-compliance-basic' ) . '</span>';
	echo '' . esc_html__( 'The Accessibility Score is a rating between 0-100 that indicates the page or websites level of compliance with WGAG standards. In deriving this score the number of possible errors, number of actual errors and the severity of each error were considered. A higher score means fewer/less severe issues. A score in the 90 to 100 range may indicate that the website meets most WCAG criteria and is highly accessible. Screen reader testing and manual audits should be conducted to ensure compliance.', 'wp-ada-compliance-basic' );
	echo '</span></span><i class="fas fa-circle"></i><span>';
	echo esc_html( $percentdisplay );
	esc_html_e( '% of your site has issues ', 'wp-ada-compliance-basic' );
	echo '</span><br />';
	if ( $percent < 5 ) {
		esc_html_e( ' The basic version is limited to 15 posts or pages, identifies 22 fewer error types and will not scan your entire website. ', 'wp-ada-compliance-basic' );
		if ( $totalitems > 15 ) {
			echo esc_html( $totalitems - 15 );
			esc_html_e( ' posts or pages were not checked. ', 'wp-ada-compliance-basic' );
			esc_html_e( ' Consider upgrading to the full version to check all your content. ', 'wp-ada-compliance-basic' );
		}
	} elseif ( $percent < 10 ) {
		esc_html_e( ' You\'re doing great, strive for less than 5%! ', 'wp-ada-compliance-basic' );
	} elseif ( $percent < 20 ) {
		esc_html_e( ' You\'re doing good, but you can do better! ', 'wp-ada-compliance-basic' );
	} elseif ( $percent < 40 ) {
		esc_html_e( ' You have some work to do! ', 'wp-ada-compliance-basic' );
	} elseif ( $percent > 40 ) {
		esc_html_e( ' Don\'t get discouraged, Get R Done! ', 'wp-ada-compliance-basic' );
	}
	echo '</li>';
	echo '</ul>';
}
/**
 * Count total records
 **/
function wp_ada_compliance_basic_count_total_scan_records() {
	global $wpdb;
	// get post types to be scanned.
	$posttypes = array( 'page', 'post' );

	if ( ! is_array( $posttypes ) ) {
		return 0;
	}

	$how_many     = count( $posttypes );
	$placeholders = array_fill( 0, $how_many, '%s' );
	$format       = implode( ', ', $placeholders );

	array_unshift( $posttypes, 'trash' );
	array_unshift( $posttypes, 'auto-draft' );
	array_unshift( $posttypes, 'private' );
	array_unshift( $posttypes, 'tao_sc_publish' );
	array_unshift( $posttypes, 'cus_sc_publish' );
	array_unshift( $posttypes, 'revision' );

	$wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'posts where post_type != %s and post_status NOT IN(%s,%s, %s, %s, %s) and post_type IN(' . $format . ')', $posttypes ), ARRAY_A );

	$rowcount = $wpdb->num_rows;

	return $rowcount;
}
