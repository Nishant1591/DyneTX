<?php
/**
 * Plugin - WP ADA Compliance Check
 * functions to support dipslay or reports and reference pages
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * REFRESH REPORT PAGE
 */
function wp_ada_compliance_basic_refresh_report_page() {

	check_ajax_referer( 'wp_rest', '_wpnonce' );

	wp_ada_compliance_basic_purge_records();
	wp_ada_compliance_basic_report_page( 1 );
}
/**
 * Display stats/error report
 **/
function wp_ada_compliance_basic_report_page( $scaninprogress = 0 ) {
	global $wpdb;
	$wp_ada_compliance_basic_def = wp_ada_compliance_basic_def();

	
	// check cap allowed to edit settings.
	$settingsuser = get_option( 'wp_ada_compliance_basic_settingsusers', 'manage_options' );

	// check auto correct settings.
	$report_filtered_errors = get_option( 'wp_ada_compliance_basic_report_filtered_errors', 'scanonly' );

	// set default values.
	$per_page    = get_option( 'wp_ada_compliance_errors_per_page', '15' );
	$total       = 0;
	$iframe      = 0;
	$offset      = 0;
	$page        = 1;
	$view        = 1;
	$sort        = 1;
	$sortby      = 'date DESC, ' . $wpdb->prefix . 'wp_ada_compliance_basic.id DESC';
	$type        = '';
	$error       = '';
	$errorid     = '';
	$postid      = '';
	$searchtitle = '';
	$hidesummary = 0;
	$scansingle  = 0;
	$startscan   = 0;

	if ( 1 == $scaninprogress || ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), 'wp-ada-compliance-nonce' ) ) ) {

		if ( 0 == $scaninprogress ) {
			wp_ada_compliance_basic_form_values();
		}

		if ( isset( $_GET['iframe'] ) ) {
			$iframe = sanitize_text_field( wp_unslash( $_GET['iframe'] ) );
		}

		if ( isset( $_GET['displaysummary'] ) ) {
			$hidesummary = sanitize_text_field( wp_unslash( $_GET['displaysummary'] ) );
		}

		if ( isset( $_GET['scansingle'] ) ) {
			$scansingle = sanitize_text_field( wp_unslash( $_GET['scansingle'] ) );
		}

		if ( isset( $_GET['startscan'] ) ) {
			$startscan = sanitize_text_field( wp_unslash( $_GET['startscan'] ) );
		}

		if ( isset( $_GET['postid'] ) ) {
			$postid = sanitize_text_field( wp_unslash( $_GET['postid'] ) );
		}

		if ( isset( $_GET['status'] ) ) {
			$scaninprogress = (int) $_GET['status'];
		} else {
			$scaninprogress = 0;
		}

		$page = isset( $_GET['cpage'] ) ? abs( (int) $_GET['cpage'] ) : 1;
		if ( $page > 1 ) {
			$offset = $page * $per_page - $per_page;
		}

		// filter by view.
		if ( isset( $_GET['view'] ) && '' != $_GET['view'] ) {
			$view = (int) $_GET['view'];
		}

		if ( isset( $_GET['sort'] ) && '' != $_GET['sort'] ) {
			if ( 1 == $_GET['sort'] ) {
				$sort   = 1;
				$sortby = 'date DESC, ' . $wpdb->prefix . 'wp_ada_compliance_basic.id DESC';
			}
			if ( 2 == $_GET['sort'] ) {
				$sort   = 2;
				$sortby = $wpdb->prefix . 'wp_ada_compliance_basic.type asc';
			}
			if ( 3 == $_GET['sort'] ) {
				$sort   = 3;
				$sortby = 'errorcode asc';
			}
			if ( 4 == $_GET['sort'] ) {
				$sort   = 4;
				$sortby = $wpdb->prefix . 'wp_ada_compliance_basic.ignre desc';
			}
			if ( 5 == $_GET['sort'] ) {
				$sort   = 5;
				$sortby = $wpdb->prefix . 'wp_ada_compliance_basic.posttitle asc';
			}
			if ( 6 == $_GET['sort'] ) {
				$sort   = 6;
				$sortby = 'scantype desc';
			}
		}
		if ( isset( $_GET['type'] ) ) {
				$type = sanitize_text_field( wp_unslash( $_GET['type'] ) );
		}

		if ( isset( $_GET['errorw'] ) && ! isset( $_GET['error'] ) ) {
			$_GET['error'] = sanitize_text_field( wp_unslash( $_GET['errorw'] ) );
		}
		if ( isset( $_GET['error'] ) ) {
			$error = sanitize_text_field( wp_unslash( $_GET['error'] ) );
		}

		if ( isset( $_GET['errorid'] ) && '' != $_GET['errorid'] ) {
			$errorid = (int) $_GET['errorid'];
		}

		if ( isset( $_GET['searchtitle'] ) ) {
			if ( is_numeric( $_GET['searchtitle'] ) ) {
				$errorid     = (int) $_GET['searchtitle'];
				$searchtitle = '';
			} else {
				$searchtitle = sanitize_text_field( wp_unslash( $_GET['searchtitle'] ) );
			}
		}
	}
	// start scan of content.
	if ( 1 == $startscan && ! isset( $_GET['cpage'] ) && '' != $type && '' != $postid ) {
		wp_ada_compliance_basic_start_single_scan( $type, $postid );
	}

	echo '<div class="wp_ada_compliance_basic_report">';

	if ( 1 != $iframe ) {
		wp_ada_compliance_basic_check_server_requirements();
	}
			echo '<a id="adascrollbutton" aria-label="Top"></a>';
	echo '<h2>';
	esc_html_e( 'Web Accessibility Report: ', 'wp-ada-compliance-basic' );
	if ( '' != $errorid ) {
		echo esc_html( get_the_title( $errorid ) );
	} elseif ( '' != $searchtitle ) {
		echo esc_html( $searchtitle );
	} else {
		echo esc_html( get_bloginfo( 'name' ) );
	}
	echo '</h2>';

	if ( '' != $errorid && 1 != $iframe && '' == $postid ) { // specific post.

		$query = 'SELECT * FROM ' . $wpdb->prefix . 'wp_ada_compliance_basic where postid = %d and type = %s  ';

		// hide if auto filter is turned off.
		if ( 'false' == $report_filtered_errors ) {
			$query .= ' and ignre != 2 ';
		}

		$query .= 'order by ignre';

		$results = $wpdb->get_results( $wpdb->prepare( $query, $errorid, $type ), ARRAY_A );

		$title       = get_the_title( $errorid );
		$showresults = "View=PostID: $errorid" . '; ';
		if ( '' != $title ) {
			$showresults .= esc_html__( 'Title=', 'wp-ada-compliance-basic' ) . stripslashes( $title ) . ';';
		}
	} else {
		$showresults            = '';
		$query                  = 'SELECT * FROM ' . $wpdb->prefix . 'wp_ada_compliance_basic where %d ';
		$query_variables_main   = array();
		$query_variables_total  = array();
		$query_variables_main[] = 1;

		$totalquery              = 'SELECT count(id) FROM ' . $wpdb->prefix . 'wp_ada_compliance_basic where %d  ';
		$query_variables_total[] = 1;

		// hide if auto filter is turned off.
		if ( 'false' == $report_filtered_errors ) {
			$query .= ' and ignre != 2 ';
		}

		if ( 1 == $view ) { // current.
			$query      .= ' and ignre != %d ';
			$totalquery .= ' and ignre != %d';

			$query_variables_main[]  = 1;
			$query_variables_total[] = 1;

			$showresults .= esc_html__( ' View=Current; ', 'wp-ada-compliance-basic' );

		}
		if ( 2 == $view ) { // all.

			if ( 'false' == $report_filtered_errors ) {
				$query                  .= ' and ignre != %d ';
				$totalquery             .= ' and ignre != %d';
				$query_variables_main[]  = 2;
				$query_variables_total[] = 2;
			}
			$showresults .= esc_html__( ' View=All; ', 'wp-ada-compliance-basic' );

		}

		if ( 3 == $view ) { // ignored.
			$query      .= ' and ignre = %d ';
			$totalquery .= ' and ignre = %d';

			$query_variables_main[]  = 1;
			$query_variables_total[] = 1;

			$showresults .= esc_html__( ' View=Ignored; ', 'wp-ada-compliance-basic' );

		}
		if ( 4 == $view ) { // auto corrected issues.
			$query      .= ' and ignre = %d ';
			$totalquery .= ' and ignre = %d';

			$query_variables_main[]  = 2;
			$query_variables_total[] = 2;

			$showresults .= esc_html__( ' View=Auto Corrected; ', 'wp-ada-compliance-basic' );

		}
		if ( '' != $errorid ) { // filter by post type.
			$query      .= ' and postid = %d ';
			$totalquery .= ' and postid = %d ';

			$query_variables_main[]  = $errorid;
			$query_variables_total[] = $errorid;

			$showresults = esc_html__( ' View=PostID: ', 'wp-ada-compliance-basic' ) . $errorid . ';';
		}

		if ( '' != $type ) { // filter by post type.

			$query                  .= ' and ( type = %s)';
			$totalquery             .= '  and (type = %s)';
			$query_variables_main[]  = $type;
			$query_variables_total[] = $type;

			$showresults .= esc_html__( ' Post type=', 'wp-ada-compliance-basic' ) . $type . '; ';
		}
		if ( '' != $error ) { // filter by error code.
			$query      .= ' and errorcode = %s ';
			$totalquery .= ' and errorcode = %s';

			$query_variables_total[] = $error;
			$query_variables_main[]  = $error;

			$showresults .= esc_html__( ' Issue type=', 'wp-ada-compliance-basic' ) . $error . '; ';
		}

		if ( '' != $searchtitle ) { // filter by post title.
			$query      .= ' and posttitle LIKE %s ';
			$totalquery .= ' and posttitle LIKE %s';

			$query_variables_total[] = '%' . $searchtitle . '%';
			$query_variables_main[]  = '%' . $searchtitle . '%';

			$showresults .= esc_html__( ' Title=', 'wp-ada-compliance-basic' ) . stripslashes( $searchtitle ) . '; ';
		}

		$query .= " order by $sortby limit %d offset %d";

		$query_variables_main[] = $per_page;
		$query_variables_main[] = $offset;

		$total = $wpdb->get_var( $wpdb->prepare( $totalquery, $query_variables_total ) );

		$results = $wpdb->get_results( $wpdb->prepare( $query, $query_variables_main ), ARRAY_A );

		if ( 1 != $iframe ) {
			if ( isset( $_COOKIE['hide-wp-ada-summary'] ) ) {
				$hidesummary = 1;
			}

			if ( 1 == $hidesummary ) {
				echo '<button type="button" class="summary-dismiss"><i class="fas fa-toggle-off"></i> ';
				esc_html_e( 'Show Summary', 'wp-ada-compliance-basic' );
				echo '</button>';
			} else {
				echo '<button type="button" class="summary-dismiss"><i class="fas fa-toggle-on"></i> ';
				esc_html_e( 'Hide Summary', 'wp-ada-compliance-basic' );
				echo '</button>';
			}
			echo '<div class="wp_ada_summary"';
			if ( 1 == $hidesummary ) {
				echo ' style="display:none;" ';
			}
			echo '>';
			wp_ada_compliance_basic_error_summary( $view, $type, $error, $searchtitle, $errorid );
			echo '</div>';

		}
	}

	if ( 1 != $iframe && 1 != $scaninprogress ) { // specific post.
		echo '<div class="wp-ada-compliance-buttns">';

		$current_cron_count = get_option( 'wp_ada_compliance_basic_cron_count', '0' );
		if ( 0 != $current_cron_count && 1 == $startscan ) {

			echo '<a href="' . esc_url( get_site_url() ) . '/wp-admin/admin.php?page=ada_compliance/compliancereportbasic.php&startscan=1&scanmore=1&sort=' . esc_attr( $sort ) . '" class="startscan btnwpada btnwpada-primary"><i class="fas fa-forward" aria-hidden="true"></i> ';
			esc_html_e( 'Scan More', 'wp-ada-compliance-basic' );
			echo '</a> ';
		} else {
			echo '<a href="' . esc_url( get_site_url() ) . '/wp-admin/admin.php?page=ada_compliance/compliancereportbasic.php&startscan=1&sort=' . esc_attr( $sort ) . '" class="startscan btnwpada btnwpada-primary"><i class="fas fa-forward" aria-hidden="true"></i> ';
			esc_html_e( 'Start Scan', 'wp-ada-compliance-basic' );
			echo '</a> ';
		}

		echo '<a href="' . esc_url( get_site_url() ) . '/wp-admin/admin.php?page=ada_compliance/compliancereportbasic.php&view=' . esc_attr( $view ) . '&type=' . esc_attr( $type ) . '&error=' . esc_attr( $error ) . '&errorid=' . esc_attr( $errorid ) . '&searchtitle=' . esc_attr( stripslashes( $searchtitle ) ) . '&refresh=1&sort=' . esc_attr( $sort ) . '" class="btnwpada btnwpada-primary"><i class="fas fa-sync-alt" aria-hidden="true"></i> ';
		esc_html_e( 'Refresh View', 'wp-ada-compliance-basic' );
		echo '</a> ';
		echo '<a href="' . esc_url( get_site_url() ) . '/wp-admin/admin.php?page=ada_compliance/compliancereportbasic.php&view=1&type=&error=&errorid=&searchtitle=&refresh=1&sort=" class="btnwpada btnwpada-primary"><i class="fas fa-filter" aria-hidden="true"></i> ';
		esc_html_e( 'Clear Filters', 'wp-ada-compliance-basic' );
		echo '</a> ';

		if ( current_user_can( $settingsuser ) ) {
			echo '<a href="' . esc_url( get_site_url() ) . '/wp-admin/admin.php?page=wp-ada-compliance-basic-admin" class="btnwpada btnwpada-primary"><i class="fas fa-cog" aria-hidden="true"></i> ';
			esc_html_e( 'Settings', 'wp-ada-compliance-basic' );
			echo '</a> ';

		}
		if ( count( $results ) > 0 ) {
			echo ' <a href="#" class="wp-ada-compliance-modal-open btnwpada btnwpada-primary" data-id="wp-ada-report-dialog"><i class="fas fa-print" aria-hidden="true"></i> ';
			esc_html_e( 'Reports', 'wp-ada-compliance-basic' );
			echo '</a>';
			$nonce = wp_create_nonce( 'wp-ada-compliance-nonce' );
			echo '<div id="wp-ada-report-dialog" class="wp-ada-compliance-modal-wrapper" aria-label="' . esc_html__( 'Reports', 'wp-ada-compliance-basic' ) . '" role="dialog" aria-modal="true">
			<div class="wp-ada-compliance-modal-inner">
			<a class="wp-ada-compliance-modal-close" href="#" role="button"><i aria-hidden="true" class="fas fa-times"></i><span class="screen-reader-text">' . esc_html__( 'Close', 'wp-ada-compliance-basic' ) . '</span></a>';
			echo '<iframe src="' . esc_url( get_site_url() ) . '/wp-admin/admin.php?page=ada_compliance/print-report.php&_wpnonce=' . esc_attr( $nonce ) . '&error=' . esc_attr( $error ) . '&type=' . esc_attr( $type ) . '&errorid=' . esc_attr( $errorid ) . '&searchtitle=' . esc_attr( stripslashes( $searchtitle ) ) . '&view=' . esc_attr( $view ) . '" style="width: 600px; height: 400px;"></iframe>';
			echo '</div></div>';
		}
		echo '</div>';
		// display dropdown filters.
		wp_ada_compliance_basic_dropdown_builder( $view, $error, $type, $searchtitle, $showresults, $sort );

	} elseif ( count( $results ) > 0 && 1 != $scaninprogress ) {
		$nonce = wp_create_nonce( 'wp-ada-compliance-nonce' );
		echo ' <a href="' . esc_url( get_site_url() ) . '/wp-admin/admin.php?page=ada_compliance/print-report.php&_wpnonce=' . esc_attr( $nonce ) . '&error=' . esc_attr( $error ) . '&type=' . esc_attr( $type ) . '&errorid=' . esc_attr( $errorid ) . '&searchtitle=' . esc_attr( stripslashes( $searchtitle ) ) . '&view=2&iframe=1&TB_iframe=true&width=450&height=250" class="thickbox btnwpada btnwpada-primary"><i class="fas fa-print" aria-hidden="true"></i> ';
		esc_html_e( 'Print', 'wp-ada-compliance-basic' );
		echo '</a>';

	}

		// display messages.
	echo '<div class="wp_ada_compliance_notice_container">';
	echo '<button aria-label="' . esc_html__( 'hide notices', 'wp-ada-compliance-basic' ) . '" class="wpadahidenotices"><i class="fas fa-times" aria-hidden="true"></i></button>';
	echo '<div class="wp_ada_compliance_notices">';
	if ( isset( $_SESSION['my_ada_important_notices'] ) ) {
		echo wp_kses_post( $_SESSION['my_ada_important_notices'] );
		unset( $_SESSION['my_ada_important_notices'] );
	} elseif ( 1 == $scansingle ) {
			echo '<i class="far fa-check-circle" aria-hidden="true"></i> ';
		if ( isset( $total ) && 0 == $total ) {
			esc_html_e( 'SCAN IS COMPLETE: No issues were found! ', 'wp-ada-compliance-basic' ) . '<p class="adaRedText">' . esc_html__( 'WP ADA Compliance Basic is limited to 15 posts or pages, includes 22 fewer error checks and will not scan your entire website. Upgrade to the full version to enable more comprehensive error checks and to identify issues in theme files, custom fields, shortcodes, widgets and other parts of your website.', 'wp-ada-compliance-basic' ) . '</p>';
		} else {
			esc_html_e( 'SCAN IS COMPLETE: Results are displayed in the report below. ', 'wp-ada-compliance-basic' ) . '<p class="adaRedText">' . esc_html__( ' WP ADA Compliance Basic is limited to 15 posts or pages, includes 22 fewer error checks and will not scan your entire website. Upgrade to the full version to enable more comprehensive error checks and to identify issues in theme files, custom fields, shortcodes, widgets and other parts of your website. ', 'wp-ada-compliance-basic' ) . '</p>';
		}
	} elseif ( 1 == $startscan ) {
		if ( isset( $_SESSION['wp_ada_compliance_message'] ) && '' != $_SESSION['wp_ada_compliance_message'] ) {
			echo wp_kses_post( $_SESSION['wp_ada_compliance_message'] );
		} else {
			echo '<i class="far fa-check-circle" aria-hidden="true"></i> ';
			esc_html_e( 'SCAN IS COMPLETE: A maximum of 15 items were scanned.', 'wp-ada-compliance-basic' );

			esc_html_e( ' Upgrade to the full version to remove the scan limit and enable unlimited deep scans which will check your entire website. The full version includes 22 additional error checks, will identify issues in theme files, custom fields, shortcodes, widgets, archives and much more. The automatic scan feature will monitor your website for issues while you are offline and send detailed email reports.', 'wp-ada-compliance-basic' );
		}
	} elseif ( ! $results && 1 != $iframe ) {
			echo '<i class="fas fa-info-circle" aria-hidden="true"></i> ';
		if ( ! $results ) {
			esc_html_e( 'Click "START SCAN" to begin', 'wp-ada-compliance-basic' );
		}
	} else {
		echo '<i class="fas fa-info-circle" aria-hidden="true"></i> ';
		esc_html_e( 'Look for status notices here.', 'wp-ada-compliance-basic' );
	}
	echo '</div>';
	echo '</div>';

	if ( 0 == $startscan || $results ) {

		if ( $results && 1 != $scaninprogress ) {

			// display the pagination.
			$nonce      = wp_create_nonce( 'wp-ada-compliance-nonce' );
			$pagination = paginate_links(
				array(
					'base'      => add_query_arg(
						array(
							'cpage'    => '%#%',
							'_wpnonce' => $nonce,
						),
						esc_url( get_site_url() ) . '/wp-admin/admin.php?page=ada_compliance/compliancereportbasic.php&refresh=1&view=' . esc_attr( $view ) . '&type=' . esc_attr( $type ) . '&error=' . esc_attr( $error ) . '&errorid=' . esc_attr( $errorid ) . '&searchtitle=' . stripslashes( esc_attr( $searchtitle ) ) . '&sort=' . esc_attr( $sort )
					),
					'format'    => '',
					'mid_size'  => 2,
					'prev_text' => '&laquo;',
					'next_text' => '&raquo;',
					'total'     => ceil( $total / $per_page ),
					'current'   => $page,
				)
			);
			if ( 1 == $startscan ) {
					echo '<p class="wp_ada_light_red">';
					esc_html_e( 'You may have issues in your theme files. Upgrade to the full version to scan your entire website. Full scans will identify issues in theme files, custom fields,  shortcodes, widgets, archives, linked pages and PDF files. The full version also corrects many issues automatically including: missing skip links, missing landmarks, new window links, empty tags, redundant ALT text and more.', 'wp-ada-compliance-basic' );
					echo '</p>';
			}

			if ( '' != $pagination ) {
				echo '<p class="hideduringscan">' . wp_kses_post( $pagination ) . '</p>';
			}

			echo '<table class="ada_error hideduringscan"><tr>';
			echo '<th scope="column">';
			esc_html_e( 'Title', 'wp-ada-compliance-basic' );
			echo '</th>';
			echo '<th scope="column">';
			esc_html_e( 'Content Type', 'wp-ada-compliance-basic' );

			echo '</th>';
			echo '<th scope="column">';
			esc_html_e( 'Issue Type', 'wp-ada-compliance-basic' );

			echo '</th>';
			echo '<th scope="column" >';
			esc_html_e( 'Issue', 'wp-ada-compliance-basic' );
			echo '</th>';
			echo '<th scope="column" class="printhidden">';
			esc_html_e( 'Affected Code', 'wp-ada-compliance-basic' );
			echo '</th>';
			echo '<th scope="column" class="printhidden wp_ada_action_column">';
			esc_html_e( 'Actions', 'wp-ada-compliance-basic' );
			echo '</th></tr>';

			foreach ( $results as $row ) {

				echo '<tr class="errorid' . esc_attr( $row['errorcode'] ) . '-' . esc_attr( md5( $row['object'] ) ) . ' errorid' . esc_attr( $row['id'] ) . ' ruleid_' . esc_attr( $row['errorcode'] ) . '">';
				echo '<td>';
				if ( 1 != $iframe ) { // hide in iframe.
					$nonce = wp_create_nonce( 'wp-ada-compliance-nonce' );
					echo '<a href="' . esc_url( get_site_url() ) . '/wp-admin/admin.php?page=ada_compliance/compliancereportbasic.php&_wpnonce=' . esc_attr( $nonce ) . '&view=' . esc_attr( $view ) . '&type=' . esc_attr( $type ) . '&error=' . esc_attr( $error ) . '&errorid=' . esc_attr( $errorid ) . '&searchtitle=' . esc_attr( $row['posttitle'] ) . '&refresh=1&sort=' . esc_attr( $sort ) . '" title="' . esc_html__( 'filter results on this title', 'wp-ada-compliance-basic' ) . '">';
					echo esc_attr( $row['posttitle'] );
					echo '</a>';
				} else {
					echo esc_attr( $row['posttitle'] );
				}
				echo '<br /><span class="adaIgnored ignore' . esc_attr( $row['id'] ) . ' ignore-' . esc_attr( $row['errorcode'] ) . '-' . esc_attr( md5( $row['object'] ) );

				echo '"';
				if ( 1 != $row['ignre'] ) {
					echo ' style="display:none;" ';
				}
				echo '>';
				esc_html_e( '** being ignored', 'wp-ada-compliance-basic' );
				echo '</span>';
				if ( 2 == $row['ignre'] ) {
					echo '<br /><span class="adaIgnored" title="';
					esc_html_e( 'Upgrade to the full version to automatically correct this issue.', 'wp-ada-compliance-basic' );
					echo '">';
					esc_html_e( '** upgrade to auto correct this issue!', 'wp-ada-compliance-basic' );
					echo '</span>';
				}

				echo '</td>';
				echo '<td class="contenttype">';
				echo esc_html( $row['type'] );
				echo wp_kses_post( wp_ada_compliance_basic_format_error_location( $row['ignre'], $row['errorcode'], $row['type'], $row['id'], $row['themeerror'], 1 ) );
				echo '</td>';
				echo '<td>';
				echo esc_html( str_replace( '_', ' ', $row['errorcode'] ) );
				echo '</td>';
				echo '<td class="printfixedwidth">';
				if ( strstr( $wp_ada_compliance_basic_def[ $row['errorcode'] ]['DisplayError'], 'WPADAWARNING' ) ) {
					echo '<i class="fas fa-ban" aria-hidden="true"></i>';
				} elseif ( strstr( $wp_ada_compliance_basic_def[ $row['errorcode'] ]['DisplayError'], 'WPADAALERT' ) ) {
					echo '<i class="fas fa-exclamation-circle" aria-hidden="true"></i>';
				}

				echo wp_kses_post( $wp_ada_compliance_basic_def[ $row['errorcode'] ]['StoredError'] );
				echo '</div>';

				// display error references.
				if ( is_array( $wp_ada_compliance_basic_def ) ) {
					if ( '' != $wp_ada_compliance_basic_def[ $row['errorcode'] ]['Reference'] ) {
						echo ' <a href="' . esc_url( $wp_ada_compliance_basic_def[ $row['errorcode'] ]['ReferenceURL'] ) . '" target="_blank" class="adaNewWindowInfo">' . wp_kses_post( $wp_ada_compliance_basic_def[ $row['errorcode'] ]['Reference'] ) . ' <i class="fas fa-external-link-alt" aria-hidden="true"><span class="wp_ada_hidden">' . esc_html__( 'opens in a new window', 'wp-ada-compliance-basic' ) . '</span></i></a>';
					}
					echo '<a href="#" class="adaHelpLinkToggle  adaHelpLink viewHelp' . esc_attr( $row['id'] ) . '"><i class="fas fa-question-circle" aria-hidden="true"></i> ' . esc_html__( 'HELP', 'wp-ada-compliance-basic' ) . '</a>';
					echo '<div class="adaHelpText helptext' . esc_attr( $row['id'] ) . '">' . wp_kses_post( $wp_ada_compliance_basic_def[ $row['errorcode'] ]['HelpINSTR'] );
					if ( '' != $wp_ada_compliance_basic_def[ $row['errorcode'] ]['HelpURL'] ) {
						echo ' <a href="' . esc_url( $wp_ada_compliance_basic_def[ $row['errorcode'] ]['HelpURL'] ) . '" target="_blank" class="adaViewbar adaNewWindowInfo">' . esc_html__( 'More Help', 'wp-ada-compliance-basic' ) . '  <i class="fas fa-external-link-alt" aria-hidden="true"><span class="wp_ada_hidden">' . esc_html__( 'opens in a new window', 'wp-ada-compliance-basic' ) . '</span></i></a>';
					}
					echo '</div>';
				}

				echo '</td>';
				echo '<td class="printfixedwidth">';
				echo '<span class="viewCode' . esc_attr( $row['id'] ) . ' adaViewCode"><a href="#" data-id="code' . esc_attr( $row['id'] ) . '" class="wp-ada-compliance-modal-open" name="' . esc_html__( 'Code View', 'wp-ada-compliance' ) . '"><i class="fas fa-eye" aria-hidden="true"></i>';
				esc_html_e( 'View Code', 'wp-ada-compliance-basic' );
				echo '</a></span>';

				echo '<div id="code' . esc_attr( $row['id'] ) . '" class="adaEffectedCode code' . esc_attr( $row['id'] ) . ' wp-ada-compliance-modal-wrapper" aria-label="' . esc_html__( 'View This Code', 'wp-ada-compliance' ) . '" role="dialog" aria-modal="true">
				<div class="wp-ada-compliance-modal-inner">
				<a class="wp-ada-compliance-modal-close" href="#" role="button"><i aria-hidden="true" class="fas fa-times"></i><span class="screen-reader-text">' . esc_html__( 'Close', 'wp-ada-compliance' ) . '</span></a>';

				if ( 'html_validation' != $row['errorcode'] && 'missing_landmarks' != $row['errorcode'] && 'unlabeled_landmarks' != $row['errorcode'] && 'skip_nav_links' != $row['errorcode'] && 'dynamic_carousel' != $row['errorcode'] ) {
					echo ' <code style="background-color: #fff;">' . esc_html( $row['object'] ) . '</code>';
				}

				if ( stristr( $row['object'], '<table' ) || stristr( $row['object'], '</th>' ) || stristr( $row['object'], '</td>' ) ) {
					$row['object'] = wp_ada_compliance_basic_close_unclosed_tables( $row['object'] );
				}
				if ( strstr( $row['object'], '[newline]' ) ) {
					$row['object'] = str_replace( '[newline]', '<br><br>', $row['object'] );
				} else {
					$row['object'] = str_replace( '[newline]', '', $row['object'] );
				}
				$trustedtags = wp_ada_compliance_basic_get_trusted_tags_array();
				echo '<br /><br /><div style="background-color: #fff;">' . wp_kses( $row['object'], $trustedtags ) . '</div>';

				if ( '' != $row['examplecode'] ) {
					echo '<br /><br /><div style="background-color: #fff;">' . wp_kses( $row['examplecode'], $trustedtags ) . '</div>';
				}
				echo '</div></div>';

				echo '</td>';

				$nonce = wp_create_nonce( 'wp-ada-compliance-nonce' );

				echo '<td class="wp_ada_action_column printhidden"> ';
				echo '<div><a href="#" class="wp-ada-ignore-options-click wp-ada-ignore-options-click' . esc_attr( $row['id'] ) . '"><i class="fas fa-cog" aria-hidden="true"></i>' . esc_html__( 'Ignore', 'wp-ada-compliance-basic' ) . '</a>';

				echo '<span class="wp-ada-ignore-options wp-ada-ignore-options' . esc_attr( $row['id'] ) . '">';

				if ( 1 != $row['ignre'] ) {
					// ignore this instance.
					echo ' <a href="' . esc_url( get_site_url() ) . '/wp-admin/admin.php?page=ada_compliance/compliancereportbasic.php&_wpnonce=' . esc_attr( $nonce ) . '&wpada_ignore=' . esc_attr( $row['id'] ) . '&sort=' . esc_attr( $sort ) . '&view=' . esc_attr( $view ) . '&type=' . esc_attr( $type ) . '&error=' . esc_attr( $error ) . '&errorid=' . esc_attr( $errorid ) . '&searchtitle=' . esc_attr( stripslashes( $searchtitle ) );
					if ( 1 == $iframe ) {
						echo '&iframe=1';
					}
					echo '" title="' . esc_html__( 'Ignore this instance of the error.', 'wp-ada-compliance-basic' ) . '" id="wpadaignore_' . esc_attr( $row['id'] ) . '_' . esc_attr( $row['ignre'] ) . '" class="wp_ada_compliance_basic_ignoreerror addignore"><i class="fas fa-eye-slash" aria-hidden="true"></i>';
					esc_html_e( 'This Error', 'wp-ada-compliance-basic' );
					echo '</a><br />';

					echo ' <a class="basicdisabled" title="' . esc_html__( 'Ignore all errors of this type found in this page. (Not available in the basic version) ', 'wp-ada-compliance-basic' ) . '" aria-hidden="true" tabindex="-1"><i class="fas fa-list" aria-hidden="true"></i>';
					esc_html_e( 'In This Page', 'wp-ada-compliance-basic' );
					echo '</a><br />';

					echo ' <a class="basicdisabled" title="' . esc_html__( 'Ignore this issue and all errors that appear to be duplicates of it. (Not available in the basic version) ', 'wp-ada-compliance-basic' ) . '" aria-hidden="true" tabindex="-1"><i class="far fa-clone" aria-hidden="true"></i>';
					esc_html_e( 'Duplicates', 'wp-ada-compliance-basic' );
					echo '</a><br />';

				}

				if ( 1 == $row['ignre'] ) {
					echo ' <a href="' . esc_url( get_site_url() ) . '/wp-admin/admin.php?page=ada_compliance/compliancereportbasic.php&_wpnonce=' . esc_attr( $nonce ) . '&wpada_ignore=' . esc_attr( $row['id'] ) . '&sort=' . esc_attr( $sort ) . '&canxignore=1&view=' . esc_attr( $view ) . '&type=' . esc_attr( $type ) . '&error=' . esc_attr( $error ) . '&errorid=' . esc_attr( $errorid ) . '&searchtitle=' . esc_attr( stripslashes( $searchtitle ) );
					if ( 1 == $iframe ) {
						echo '&iframe=1';
					}
					echo '" title="' . esc_html__( 'Remove ignore from this error instance.', 'wp-ada-compliance-basic' ) . '" id="wpadaignore_' . esc_attr( $row['id'] ) . '_' . esc_attr( $row['ignre'] ) . '" class="wp_ada_compliance_basic_ignoreerror removeignore"><i class="fas fa-times-circle"></i>';
					esc_html_e( 'This Error', 'wp-ada-compliance-basic' );
					echo '</a><br />';

				}
				if ( current_user_can( $settingsuser ) ) {
					echo '<a href="' . esc_url( get_site_url() ) . '/wp-admin/admin.php?page=ada_compliance/compliancereportbasic.php&_wpnonce=' . esc_attr( $nonce ) . '&wpada_ignore_rule=' . esc_attr( $row['errorcode'] ) . '&type=' . esc_attr( $type ) . '&view=' . esc_attr( $view ) . '&sort=' . esc_attr( $sort ) . '&errorid=' . esc_attr( $errorid ) . '&searchtitle=' . esc_attr( stripslashes( $searchtitle ) ) . '" title="' . esc_html__( 'Remove this error from the results as well as future scans.', 'wp-ada-compliance-basic' ) . '" id="wpadaignorerule|' . esc_attr( $row['id'] ) . '|' . esc_attr( $row['errorcode'] ) . '" class="wp_ada_compliance_basic_ignorerule"><i class="fas fa-tasks" aria-hidden="true"></i>';
					esc_html_e( 'This Rule', 'wp-ada-compliance-basic' );
					echo '</a><br />';
				}

				echo ' <a title="' . esc_html__( 'Remove this item from the results and future scans. (Not available in the basic version) ', 'wp-ada-compliance-basic' ) . '" aria-hidden="true" tabindex="-1" class="basicdisabled"><i class="far fa-file" aria-hidden="true"></i>';
				esc_html_e( 'This File', 'wp-ada-compliance-basic' );
				echo '</a><br />';
				echo '</span>';
				echo '</div>';

				// display edit options.
				if ( 1 != $iframe ) { // hide in iframe.
					echo '<a href="' . esc_url( get_site_url() ) . '/wp-admin/admin.php?page=ada_compliance/compliancereportbasic.php&scansingle=1&errorid=' . esc_attr( $row['id'] ) . '&sort=' . esc_attr( $sort ) . '&type=' . esc_attr( $row['type'] ) . '&taxonomy=' . esc_attr( $row['taxonomy'] ) . '&postid=' . esc_attr( $row['postid'] ) . '&view=' . esc_attr( $view ) . '&searchtitle=' . esc_attr( $row['postid'] ) . '" class="wp_ada_compliance_basic_recheck" id="' . esc_attr( $row['type'] ) . '|' . esc_attr( $row['postid'] ) . '"><i class="fas fa-sync-alt" aria-hidden="true"></i>';
					esc_html_e( 'Recheck', 'wp-ada-compliance-basic' );
					echo '</a>';
					echo '<br />';

					echo '<a href="post.php?post=' . esc_attr( $row['postid'] ) . '&action=edit"  target="_blank" class="adaNewWindowInfo"><i class="far fa-edit" aria-hidden="true" ></i>';
					esc_html_e( 'Edit', 'wp-ada-compliance-basic' );
					echo ' <i class="fas fa-external-link-alt" aria-hidden="true"><span class="wp_ada_hidden">' . esc_html__( 'opens in a new window', 'wp-ada-compliance-basic' ) . '</span></i></a>';
					echo '<br />';

					echo '<a href="' . esc_url( get_permalink( $row['postid'] ) ) . '" target="_blank" class="adaNewWindowInfo"><i class="fas fa-eye" aria-hidden="true" ></i>';
					esc_html_e( 'View', 'wp-ada-compliance-basic' );
					echo ' <i class="fas fa-external-link-alt" aria-hidden="true"><span class="wp_ada_hidden">' . esc_html__( 'opens in a new window', 'wp-ada-compliance-basic' ) . '</span></i></a>';
					echo '<br />';

				}

				echo '<a href="#" onclick="return false;" tabindex="-1" style="margin-left:0px;" class="wp_ada_label wp_ada_label_disabled" title="' . esc_html__( 'Notes are not available in the basic version, upgrade to the full version to share notes with team members.', 'wp-ada-compliance-basic' ) . '"><i class="far fa-sticky-note" aria-hidden="true"></i>';
				esc_html_e( 'Notes', 'wp-ada-compliance-basic' );
				echo '</a>';

				echo '</td>';
				echo '</tr>';
			}
			echo '</table>';

			if ( '' != $pagination ) {
				echo '<p class="hideduringscan">' . wp_kses_post( $pagination ) . '</p>';
			}

			echo '<p class="wp_ada_error_key hideduringscan">';
			echo '<i class="fas fa-exclamation-circle" aria-hidden="true"></i> ';
			esc_html_e( 'SUGGESTED IMPROVEMENTS - if corrected, will enhance web accessibility, improve a user\'s experience or avoid the possibility of inaccessible content inadvertently being introduced into a website.', 'wp-ada-compliance-basic' );
				echo '<br /><br />';
			echo '<i class="fas fa-ban" aria-hidden="true"></i> ';
			esc_html_e( 'ERRORS - MUST BE corrected to ensure compliance with Section 508 or WCAG 2.2 LEVEL A/AA Web Accessibility Standards and ensure content is accessible to users with disabilities.', 'wp-ada-compliance-basic' );
			echo '</p>';
			echo '</div>';
		}
	}

	if ( ! $results ) {

		if ( 1 == $startscan || 1 == $scansingle ) {
			echo '<p class="wp_ada_compliance_basic_scanstatus">';
			esc_html_e( 'No issues were found!', 'wp-ada-compliance-basic' );
			echo '</p>';
		}
		echo '</div>';

	}
			// stop header sent warnings.
	if ( isset( $_GET['_wpnonce'] ) ) {
		exit;
	}
}

/**
 * Create guidelines reference page
 **/
function wp_ada_compliance_basic_referencereport_page() {
	$wp_ada_compliance_basic_def = wp_ada_compliance_basic_def();
	// check cap allowed to edit settings.
	$settingsuser = get_option( 'wp_ada_compliance_basic_settingsusers', 'manage_options' );

	echo '<div class="adaReferenceReport">';
	echo '<h2>' . esc_html__( 'ADA Compliance Guidelines Reference', 'wp-ada-compliance-basic' ) . '</h2>';

			echo '<a href="' . esc_url( get_site_url() ) . '/wp-admin/admin.php?page=ada_compliance/compliancereportbasic.php" class="btnwpada btnwpada-primary"><i class="fas fa-file-alt" aria-hidden="true"></i> ';
	esc_html_e( 'View Report', 'wp-ada-compliance-basic' );
	echo '</a> ';

	if ( current_user_can( $settingsuser ) ) {
		echo '<a href="' . esc_url( get_site_url() ) . '/wp-admin/admin.php?page=wp-ada-compliance-basic-admin" class="btnwpada btnwpada-primary"><i class="fas fa-cog" aria-hidden="true"></i> ';
		esc_html_e( 'Settings', 'wp-ada-compliance-basic' );
		echo '</a> ';
	}

	echo '<a href="#" class="btnwpada btnwpada-primary" onclick="javascript:window.print(); return false;"><i class="fas fa-print" aria-hidden="true"></i> ' . esc_html__( 'Print', 'wp-ada-compliance-basic' ) . '</a>';

	echo '<p class="adamarketingtext">' . esc_html__( 'The WP ADA Compliance Basic Plugin evaluates content for the most common issues typically found in the WordPress page editor. The full version will scan your entire website and includes many accessibility checks not available in the basic plugin. The full version also corrects many issues automatically. The following is a list of issues that the basic plugin will identify.', 'wp-ada-compliance-basic' ) . '</p>';

	echo '<p class="wp_ada_error_key">';
		echo '<i class="fas fa-exclamation-circle" aria-hidden="true"></i> ';
		esc_html_e( 'SUGGESTED IMPROVEMENTS - if corrected, will enhance web accessibility, improve a user\'s experience or avoid the possibility of inaccessible content inadvertently being introduced into a website.', 'wp-ada-compliance-basic' );
		echo '<br /><br />';
		echo '<i class="fas fa-ban" aria-hidden="true"></i> ';
		esc_html_e( 'ERRORS - MUST BE corrected to ensure compliance with Section 508 or WCAG 2.2 LEVEL A/AA Web Accessibility Standards and ensure content is accessible to users with disabilities.', 'wp-ada-compliance-basic' );
		echo '</p>';

	echo '<a id="adascrollbutton" aria-label="Top"></a>';

	// display error references.
	if ( is_array( $wp_ada_compliance_basic_def ) ) {
		foreach ( $wp_ada_compliance_basic_def as $rows => $row ) {
			echo '<div class="adaReference">';
			if ( strstr( $row['DisplayError'], 'WPADAWARNING' ) ) {
				echo '<i class="fas fa-ban" aria-hidden="true"></i>';
			} elseif ( strstr( $row['DisplayError'], 'WPADAALERT' ) ) {
				echo '<i class="fas fa-exclamation-circle" aria-hidden="true"></i>';
			}
			echo wp_kses_post( $row['StoredError'] );
			if ( '' != $row['Reference'] ) {
				echo ' <a href="' . esc_url( $row['ReferenceURL'] ) . '" target="_blank" class="adaNewWindowInfo">' . esc_html( $row['Reference'] ) . ' <i class="fas fa-external-link-alt" aria-hidden="true"><span class="wp_ada_hidden">' . esc_html__( 'opens in a new window', 'wp-ada-compliance-basic' ) . '</span></i></a>';
			}
			echo '<a href="#" class="adaHelpLinkToggle adaHelpLink viewHelp' . esc_attr( $rows ) . '"><i class="fas fa-question-circle" aria-hidden="true"></i> ' . esc_html__( 'HELP', 'wp-ada-compliance-basic' ) . ' </i></a>';
			echo '<div class="adaHelpText helptext' . esc_attr( $rows ) . '">' . wp_kses_post( $row['HelpINSTR'] );
			if ( '' != $row['HelpURL'] ) {
				echo ' <a href="' . esc_url( $row['HelpURL'] ) . '" target="_blank" class="adaViewbar adaNewWindowInfo">' . esc_html__( 'More Help', 'wp-ada-compliance-basic' ) . ' <i class="fas fa-external-link-alt" aria-hidden="true"><span class="wp_ada_hidden">' . esc_html__( 'opens in a new window', 'wp-ada-compliance-basic' ) . '</span></i></a>';
			}
			echo '</div>';
			echo '</div>';

		}
	}
	echo '</div>';
}

/*********************************************
REPORT PAGE SUPPORT FUNCTIONS
 ********************************************/
/**
 * Create filter drop downs
 **/
function wp_ada_compliance_basic_dropdown_builder( $view, $error, $type, $searchtitle, $showresults, $sort ) {
	global $wpdb;

	echo '<form name="filtererrors" class="wp_ada_form hideduringscan" action="' . esc_url( get_site_url() ) . '/wp-admin/admin.php" method="get"><input type="hidden" name="page" value="ada_compliance/compliancereportbasic.php" />
<input type="hidden" name="sort" value="' . esc_attr( $sort ) . '" />';
	wp_ada_compliance_basic_promotions();
	wp_nonce_field( 'wp-ada-compliance-nonce' );
	// display view by.
	echo '<label for="view" class="wp_ada_label">' . esc_html__( 'View: ', 'wp-ada-compliance-basic' ) . '<select name="view" id="view">';
	echo '<option value="2"';
	if ( '2' == $view ) {
		echo ' selected';
	}
	echo '>' . esc_html__( 'All', 'wp-ada-compliance-basic' ) . '</option>';
	echo '<option value="3"';
	if ( '3' == $view ) {
		echo ' selected';
	}
	echo '>' . esc_html__( 'Ignored', 'wp-ada-compliance-basic' ) . '</option>';
	echo '<option value="1"';
	if ( '1' == $view ) {
		echo ' selected';
	}
	echo '>' . esc_html__( 'Current', 'wp-ada-compliance-basic' ) . '</option>';
	echo '</select></label>';

	// filter by error code.
	$results = $wpdb->get_results( 'SELECT distinct(errorcode) FROM ' . $wpdb->prefix . 'wp_ada_compliance_basic order by errorcode', ARRAY_A );
	echo '<label for="errortype" class="wp_ada_label">' . esc_html__( 'Issue Type: ', 'wp-ada-compliance-basic' ) . '<select name="errorw" id="errortype">';
	echo '<option value="">' . esc_html__( 'Any', 'wp-ada-compliance-basic' ) . '</option>';
	foreach ( $results as $row ) {
		echo '<option value="' . esc_attr( $row['errorcode'] ) . '"';
		if ( $error == $row['errorcode'] ) {
			echo ' selected';
		}
		echo '>' . esc_attr( str_replace( '_', ' ', $row['errorcode'] ) ) . '</option>';
	}
	echo '</select></label>';

	// filter by post type.
	$results = $wpdb->get_results( 'SELECT distinct(type) FROM ' . $wpdb->prefix . 'wp_ada_compliance_basic', ARRAY_A );
	echo '<label for="posttype" class="wp_ada_label">' . esc_html__( 'Post Type: ', 'wp-ada-compliance-basic' ) . '<select name="type" id="posttype">';
	echo '<option value="">' . esc_html__( 'Any', 'wp-ada-compliance-basic' ) . '</option>';
	foreach ( $results as $row ) {
		echo '<option value="' . esc_attr( $row['type'] ) . '"';
		if ( $type == $row['type'] ) {
			echo ' selected';
		}
		echo '>' . esc_attr( $row['type'] ) . '</option>';
	}
	echo '</select></label> ';
	echo '<label for="searchtitle" class="wp_ada_label">';
	echo esc_html__( 'Search:', 'wp-ada-compliance' );
	echo '<input type="text" name="searchtitle" id="searchtitle" value="';

	echo esc_attr( stripslashes( $searchtitle ) );

	echo '" aria-label="' . esc_html__( 'Post Title or Post ID', 'wp-ada-compliance-basic' ) . '" placeholder="' . esc_html__( 'Post Title or Post ID', 'wp-ada-compliance-basic' ) . '" onfocus="this.value=\'\'"></label>';

	// sort list.
	echo '<label for="sort" class="wp_ada_label sortby">' . esc_html__( 'Sort by: ', 'wp-ada-compliance-basic' ) . ' <select id="sort" name="sort">
';
	echo '<option value="1"';
	if ( 1 == $sort ) {
		echo ' selected';
	}
	echo '>';
	echo esc_html__( 'Date', 'wp-ada-compliance-basic' );
	echo '</option>';
	echo '<option value="2"';
	if ( 2 == $sort ) {
		echo ' selected';
	}
	echo '>';
	echo esc_html__( 'Content Type', 'wp-ada-compliance-basic' );
	echo '</option>';
	echo '<option value="3"';
	if ( 3 == $sort ) {
		echo ' selected';
	}
	echo '>';
	echo esc_html__( 'Issue Type', 'wp-ada-compliance-basic' );
	echo '</option>';
	echo '<option value="4"';
	if ( 4 == $sort ) {
		echo ' selected';
	}
	echo '>';
	echo esc_html__( 'Error State (ignore, current, etc)', 'wp-ada-compliance-basic' );
	echo '</option>';
	echo ' <option value="5"';
	if ( 5 == $sort ) {
		echo ' selected';
	}
	echo '>';
	echo esc_html__( 'Title', 'wp-ada-compliance-basic' );
	echo '</option>';
	echo '</select></label>';
	echo '<br><br><label for="modifieddate" class="wp_ada_label">';
	echo esc_html__( 'Modified Date: ', 'wp-ada-compliance-basic' );
	echo '<input size="10" type="date" name="modifieddate" readonly id="modifieddate" value="" title="' . esc_html__( 'Not available in the basic version', 'wp-ada-compliance-basic' ) . '"></label>';
	echo '<p class="wp-ada-compliance-filters">
<label for="excludethemes" class="wp_ada_label wp_ada_label_disabled" title="' . esc_html__( 'Not available in the basic version', 'wp-ada-compliance-basic' ) . '"><input type="checkbox" name="excludethemes" id="excludethemes" value="1" disabled>' . esc_html__( 'hide theme issues', 'wp-ada-compliance' ) . '</label><label for="excludedups" class="wp_ada_label wp_ada_label_disabled" title="' . esc_html__( 'Not available in the basic version', 'wp-ada-compliance-basic' ) . '"><input disabled type="checkbox" name="excludedups" id="excludedups" value="1">' . esc_html__( 'hide duplicates', 'wp-ada-compliance' ) . '</label>';

	// submit.
	echo '<p><input type="submit" value="' . esc_html__( 'Filter', 'wp-ada-compliance-basic' ) . '" class=" wp_ada_label btnwpada btnwpada-primary filterbtn" /></p>';

	// display filter message.
	if ( isset( $showresults ) ) {
		echo '<p class="adashowingmessage">';
		echo esc_html__( 'Filters: ', 'wp-ada-compliance-basic' );
		echo esc_attr( $showresults );
		echo '</p>';
	}
	echo '</form>';
}
/**
 * Create error summary
 */
function wp_ada_compliance_basic_error_summary( $view, $type, $error, $searchtitle, $errorid ) {
	global $wpdb;

	$query             = 'SELECT * FROM ' . $wpdb->prefix . 'wp_ada_compliance_basic where %d';
	$query_variables   = array();
	$query_variables[] = 1;
	$totalquery        = '';

	// hide if auto filter is turned off.
	$report_filtered_errors = get_option( 'wp_ada_compliance_basic_report_filtered_errors', 'scanonly' );
	if ( 'false' == $report_filtered_errors ) {
		$totalquery .= ' and ignre != 2 ';
	}

	if ( 1 == $view ) { // current.
		$totalquery       .= ' and ignre != %d';
		$query_variables[] = 1;
	}
	if ( 3 == $view ) { // ignored.
		$totalquery       .= ' and ignre = %d';
		$query_variables[] = 1;
	}
	if ( 4 == $view ) { // auto corrected issues.
		$totalquery       .= ' and ignre = %d';
		$query_variables[] = 2;
	}
	if ( '' != $type ) { // filter by post type.
		$totalquery       .= '  and (type = %s)';
		$query_variables[] = $type;
	}
	if ( '' != $error ) { // filter by error code.
		$totalquery       .= ' and errorcode = %s';
		$query_variables[] = $error;
	}
	if ( '' != $searchtitle ) { // filter by error code.
		$totalquery       .= ' and posttitle LIKE %s';
		$query_variables[] = '%' . $searchtitle . '%';
	}
	if ( '' != $errorid && 0 != $errorid ) { // filter by error code.
		$totalquery       .= ' and postid = %d ';
		$query_variables[] = $errorid;
	}

	$query .= $totalquery;

	$records = $wpdb->get_results( $wpdb->prepare( $query, $query_variables ), ARRAY_A );

	$total = count( $records );

	wp_ada_compliance_basic_dashboard_summary();
	echo '<div class="wp_ada_summary_right">';
	echo '<h2 class="wp_ada_summary_header">';
	esc_html_e( 'Issue Summary', 'wp-ada-compliance-basic' );
	echo '</h2>';
	echo '<p class="wp_ada_issue_sum">';
	echo '<span class="adaViewbar">';
	esc_html_e( 'total issues: ', 'wp-ada-compliance-basic' );
	echo '</span>';
	echo esc_html( $total );
	echo '</p>';
	if ( '' == $error ) {
		$results = $wpdb->get_results( 'SELECT distinct(errorcode) FROM ' . $wpdb->prefix . 'wp_ada_compliance_basic ', ARRAY_A );

		foreach ( $results as $row ) {
			echo '<p class="wp_ada_issue_sum">';
			wp_ada_compliance_basic_error_count( $row['errorcode'], $totalquery, $query_variables );

			echo '</p>';
		}
	}

	echo '</div>';
}

/**
 * Count errors
 **/
function wp_ada_compliance_basic_error_count( $errorcode, $totalquery, $query_variables ) {
	global $wpdb;

	$query  = 'SELECT * FROM ' . $wpdb->prefix . 'wp_ada_compliance_basic where %d ';
	$query .= $totalquery;

	$query            .= ' and errorcode = %s ';
	$query_variables[] = $errorcode;

	$results = $wpdb->get_results( $wpdb->prepare( $query, $query_variables ), ARRAY_A );

	$total = count( $results );

	echo '<span class="adaViewbar">' . esc_attr( str_replace( '_', ' ', $errorcode ) ) . ':</span> ' . esc_attr( $total ) . '<br />';
}

/**
 * Create email report
 **/
function wp_ada_compliance_basic_create_email_report( $email, $postinfo = 0 ) {
	global $wpdb;
	$wp_ada_compliance_basic_def = wp_ada_compliance_basic_def();
	$showresults          = '';
	$query_variables_main = array();
	if ( is_array( $postinfo ) ) {
		$query = 'SELECT * FROM ' . $wpdb->prefix . 'wp_ada_compliance_basic where %d ';

		$report_filtered_errors = get_option( 'wp_ada_compliance_basic_report_filtered_errors', 'scanonly' );
		if ( 'false' == $report_filtered_errors ) {
			$query .= ' and ignre != 2 ';
		}

		$query_variables_main[] = 1;

		if ( array_key_exists( 'view', $postinfo ) ) {
			if ( 1 == $postinfo['view'] ) {
				$query                 .= ' and ignre != %d ';
				$query_variables_main[] = 1;
				$showresults           .= esc_html__( ' View=Current; ', 'wp-ada-compliance-basic' );
			}
			if ( 2 == $postinfo['view'] ) {
				$showresults .= esc_html__( ' View=All; ', 'wp-ada-compliance-basic' );
			}
			if ( 3 == $postinfo['view'] ) { // ignored.
				$query                 .= ' and ignre = %d ';
				$query_variables_main[] = 1;
				$showresults           .= esc_html__( ' View=Ignored; ', 'wp-ada-compliance-basic' );

			}
			if ( 4 == $postinfo['view'] ) { // auto corrected issues.
				$query                 .= ' and ignre = %d ';
				$query_variables_main[] = 2;
				$showresults           .= esc_html__( ' View=Auto Corrected; ', 'wp-ada-compliance-basic' );
			}
		}
		if ( array_key_exists( 'errorid', $postinfo ) && '' != $postinfo['errorid'] && '0' != $postinfo['errorid'] ) { // filter by post type.
			$query                 .= ' and postid = %d ';
			$query_variables_main[] = $postinfo['errorid'];
			$showresults            = esc_html__( ' View=PostID: ', 'wp-ada-compliance-basic' ) . $postinfo['errorid'] . ';';

		}
		if ( array_key_exists( 'type', $postinfo ) && '' != $postinfo['type'] ) { // filter by post type.

			$query                 .= ' and (type = %s)';
			$query_variables_main[] = $postinfo['type'];

			$showresults .= esc_html__( ' Post type=', 'wp-ada-compliance-basic' ) . $postinfo['type'] . '; ';
		}
		if ( array_key_exists( 'error', $postinfo ) && '' != $postinfo['error'] ) { // filter by error code.
			$query                 .= ' and errorcode = %s ';
			$query_variables_main[] = $postinfo['error'];
			$showresults           .= esc_html__( 'Issue type=', 'wp-ada-compliance-basic' ) . $postinfo['error'] . '; ';
		}

		if ( array_key_exists( 'searchtitle', $postinfo ) && '' != $postinfo['searchtitle'] ) { // filter by post title.
			$query                 .= ' and posttitle LIKE %s ';
			$query_variables_main[] = '%' . $postinfo['searchtitle'] . '%';
			$showresults           .= esc_html__( 'Title=', 'wp-ada-compliance-basic' ) . stripslashes( $postinfo['searchtitle'] ) . '; ';
		}

		if ( array_key_exists( 'sort', $postinfo ) ) {
			if ( 1 == $postinfo['sort'] ) {
				$sortby = 'date DESC, id DESC ';
			}
			if ( 2 == $postinfo['sort'] ) {
				$sortby = 'type asc';
			}
			if ( 3 == $postinfo['sort'] ) {
				$sortby = 'errorcode asc';
			}
			if ( 4 == $postinfo['sort'] ) {
				$sortby = 'ignre desc';
			}
			if ( 5 == $postinfo['sort'] ) {
				$sortby = 'posttitle asc';
			}
		} else {
			$sortby = 'date DESC, id DESC ';
		}

		$query .= " order by $sortby ";

	} else {
		$notification_frequency = get_option( 'wp_ada_compliance_basic_notification_frequency', 'daily' );

		// create interval based on frequency.
		if ( 'monthly' == $notification_frequency ) {
			$interval = '30 DAY';
		}
		if ( 'weekly' == $notification_frequency ) {
			$interval = '7 DAY';
		}
		if ( 'daily' == $notification_frequency ) {
			$interval = '1 DAY';
		}
		if ( 'twicedaily' == $notification_frequency ) {
			$interval = '12 HOUR';
		}
		if ( 'hourly' == $notification_frequency ) {
			$interval = '1 HOUR';
		}

		$query = 'SELECT * FROM ' . $wpdb->prefix . 'wp_ada_compliance_basic where ignre = %d ';

		// hide if auto filter is turned off.
		$report_filtered_errors = get_option( 'wp_ada_compliance_basic_report_filtered_errors', 'scanonly' );
		if ( 'false' == $report_filtered_errors ) {
			$query .= ' and ignre != 2 ';
		}

		$query                 .= 'and date >= DATE_SUB(NOW(),INTERVAL ' . $interval . ') order by date DESC, id DESC ';
		$query_variables_main[] = 0;
	}

	$results = $wpdb->get_results( $wpdb->prepare( $query, $query_variables_main ), ARRAY_A );

		echo '<div class="wp_ada_compliance_report">';

	if ( count( $results ) > 0 ) {
		$report = '';
		// $report = '<h2>' . esc_html__( 'Web Accessibility Report', 'wp-ada-compliance-basic' ) . '</h2>';
		if ( isset( $interval ) ) {
			$report .= '<p>';
			$report .= esc_html__( 'This report includes issues identified in the past ', 'wp-ada-compliance-basic' );
			$report .= esc_attr( $interval );
			$report .= '</p>';
		} elseif ( '' != $showresults ) {
			$report .= '<p>' . esc_html( $showresults ) . '</p>';
		}
		$report .= '<table class="ada_error" border="1"><tr>';
		$report .= '<th scope="column" >';
		$report .= esc_html__( 'Title', 'wp-ada-compliance-basic' );
		$report .= '</th>';
		$report .= '<th scope="column">';
		$report .= esc_html__( 'Content Type', 'wp-ada-compliance-basic' );
		$report .= '</th>';
		$report .= '<th scope="column" >';
		$report .= esc_html__( 'Issue Type', 'wp-ada-compliance-basic' );
		$report .= '</th>';
		$report .= '<th scope="column" >';
		$report .= esc_html__( 'Issue', 'wp-ada-compliance-basic' );
		$report .= '</th></tr>';

		foreach ( $results as $row ) {

			$userid = get_userdata( $row['activeuser'] );

			if ( ( is_array( $postinfo ) && ( '' != $email || 'print' == $email ) ) || ( '' != $email && $email != $userid->user_email ) ) {
				$issuefound = 1;

				$report .= '<tr>';
				$report .= '<td>';
				$report .= esc_html( $row['posttitle'] );
				if ( 1 == $row['ignre'] ) {
					$report .= '<br /><span class="adaIgnored">';
					$report .= esc_html__( '** being ignored', 'wp-ada-compliance-basic' );
					$report .= '</span>';
				} elseif ( 2 == $row['ignre'] ) {
					$report .= '<br /><span class="adaIgnored">';
					$report .= esc_html__( '** upgrade to auto correct this issue!', 'wp-ada-compliance-basic' );
					$report .= '</span>';
				}
				$report .= '</td>';
				$report .= '<td>';
				$report .= esc_html( $row['type'] );

				$report .= '</td>';
				$report .= '<td>';
				$report .= esc_html( str_replace( '_', ' ', $row['errorcode'] ) );
				$report .= '</td>';
				$report .= '<td>';
				$report .= wp_kses_post( $wp_ada_compliance_basic_def[ $row['errorcode'] ]['StoredError'] );

				// if printing display error.
				if ( 'print' == $email ) {
					$report .= '<div class="adaEffectedCode code' . esc_attr( $row['id'] ) . '" id="code' . esc_attr( $row['id'] ) . '">';

					if ( 'html_validation' != $row['errorcode'] && 'missing_landmarks' != $row['errorcode'] && 'unlabeled_landmarks' != $row['errorcode'] && 'skip_nav_links' != $row['errorcode'] && 'dynamic_carousel' != $row['errorcode'] ) {
						$report .= '<code>' . esc_html( $row['object'] ) . '</code>';
					}

					// $trustedtags = '<svg><metadata><g><path><button><embed><iframe><p><a><img><h1><h2><h3><h4><h5><h6><input><map><area><audio><video><pre><textarea><label><select><span><blink><i><fieldset><caption><form><legend><br><div><nav><main><aside><header><footer>';
					if ( stristr( $row['object'], '<table' ) || stristr( $row['object'], '</th>' ) || stristr( $row['object'], '</td>' ) ) {
						$row['object'] = wp_ada_compliance_basic_close_unclosed_tables( $row['object'] );
						// $trustedtags  .= '<table><tr><td><th><tbody><thead><span>';
					}

					if ( strstr( $row['object'], '[newline]' ) ) {
						$row['object'] = str_replace( '[newline]', '<br><br>', $row['object'] );
					} else {
						$row['object'] = str_replace( '[newline]', '', $row['object'] );
					}

					$trustedtags = wp_ada_compliance_basic_get_trusted_tags_array();
					$report     .= '<br /><br /><div style="background-color: #fff;">' . wp_kses( $row['object'], $trustedtags ) . '</div>';

					if ( '' != $row['examplecode'] ) {
						$report .= '<br /><br /><div style="background-color: #fff;">' . wp_kses( $row['examplecode'], $trustedtags ) . '</div>';
					}
					$report .= '</div>';
				}

				$report .= '</td>';
				$report .= '</tr>';
			}
		}
		$report .= '</table>';

		$report .= '
		<style>
		table{
		border-collapse: collapse;
		}
		table.ada_error td, table.ada_error th
		{
		padding: 5px; 
		color: #000; 
		min-width: 75px; 
		background-color:#ccc;
		}
		table.ada_error td
		{
		background-color:#fff;
		}
		</style>';
	}

	if ( isset( $issuefound ) ) {
		return $report;
	} else {
		return '';
	}
}

/**
 * Modify media library to add filter for images missing alternate text
 */
function wp_ada_compliance_basic_media_library_dropdown() {
	$scr = get_current_screen();
	if ( 'upload' !== $scr->base ) {
		return;
	}

	$nonce = wp_create_nonce( 'wp-ada-compliance-nonce' );
	echo '<input type="hidden" id="wp-ada-compliance-nonce" name="wp-ada-compliance-nonce" value="' . esc_attr( $nonce ) . '">';

	if ( isset( $_REQUEST['accessibility-filter'] ) && isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), 'wp-ada-compliance-nonce' ) ) {
		$value = sanitize_text_field( wp_unslash( $_REQUEST['accessibility-filter'] ) );
	} elseif ( isset( $_REQUEST['accessibility-filter'] ) && isset( $_REQUEST['wp-ada-compliance-nonce'] ) && wp_verify_nonce( sanitize_key( $_REQUEST['wp-ada-compliance-nonce'] ), 'wp-ada-compliance-nonce' ) ) {
		$value = sanitize_text_field( wp_unslash( $_REQUEST['accessibility-filter'] ) );
	} else {
		$value = '';
	}

	echo '
	<label for="accessibility-filter" class="screen-reader-text">Filter by accessibility</label>
	<select class="accessibility-filter" name="accessibility-filter" id="accessibility-filter">
	<option value=""';
	if ( '' == $value ) {
		echo ' selected';
	}
	echo '>';
	esc_html_e( 'Accessibility Issues', 'wp-ada-compliance-basic' );
	echo '</option>';
			echo '<option value="viewall"';
	if ( 'viewall' == $value ) {
		echo ' selected';
	}
	echo '>';
	esc_html_e( 'View All Images With Accessibility Issues', 'wp-ada-compliance' );
	echo '</option>';
	echo '<option value="missingalt"';
	if ( 'missingalt' == $value ) {
		echo ' selected';
	}
	echo '>';
	esc_html_e( 'Missing Alternate Text', 'wp-ada-compliancebasic' );
	echo '</option>';
	echo '<option value="invalidalt"';
	if ( 'invalidalt' == $value ) {
		echo ' selected';
	}
	echo '>';
	esc_html_e( 'Invalid Alternate Text', 'wp-ada-compliance-basic' );
	echo '</option>';
	echo '</select>';
}
add_action( 'restrict_manage_posts', 'wp_ada_compliance_basic_media_library_dropdown' );


/**
 * Filter media
 */
function wp_ada_compliance_basic_media_filter( $query ) {
	if ( is_admin() && $query->is_main_query() ) {

		if ( isset( $_REQUEST['accessibility-filter'] ) && isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), 'wp-ada-compliance-nonce' ) ) {
			$filter = sanitize_text_field( wp_unslash( $_REQUEST['accessibility-filter'] ) );
		} elseif ( isset( $_REQUEST['accessibility-filter'] ) && isset( $_REQUEST['wp-ada-compliance-nonce'] ) && wp_verify_nonce( sanitize_key( $_REQUEST['wp-ada-compliance-nonce'] ), 'wp-ada-compliance-nonce' ) ) {
			$filter = sanitize_text_field( wp_unslash( $_REQUEST['accessibility-filter'] ) );
		} else {
			$filter = '';
		}

		if ( 'missingalt' == $filter ) {
			$query->set(
				'meta_query',
				array(
					'relation' => 'OR',
					array(
						'key'     => '_wp_attachment_image_alt',
						'value'   => '',
						'compare' => '=',
					),
					array(
						'key'     => '_wp_attachment_image_alt',
						'compare' => 'NOT EXISTS',
					),
				)
			);
		}
		if ( 'invalidalt' == $filter ) {
			$query->set(
				'meta_query',
				array(
					'relation' => 'OR',
					array(
						'key'     => '_wp_attachment_image_alt',
						'value'   => '.jpg',
						'compare' => 'LIKE',
					),
					array(
						'key'     => '_wp_attachment_image_alt',
						'value'   => '.jpeg',
						'compare' => 'LIKE',
					),
					array(
						'key'     => '_wp_attachment_image_alt',
						'value'   => '.png',
						'compare' => 'LIKE',
					),
					array(
						'key'     => '_wp_attachment_image_alt',
						'value'   => '.gif',
						'compare' => 'LIKE',
					),
					array(
						'key'     => '_wp_attachment_image_alt',
						'value'   => '_',
						'compare' => 'LIKE',
					),
					array(
						'key'     => '_wp_attachment_image_alt',
						'value'   => 'photo of',
						'compare' => 'LIKE',
					),
					array(
						'key'     => '_wp_attachment_image_alt',
						'value'   => 'image of',
						'compare' => 'LIKE',
					),
					array(
						'key'     => '_wp_attachment_image_alt',
						'value'   => 'graphic of',
						'compare' => 'LIKE',
					),
					array(
						'key'     => '_wp_attachment_image_alt',
						'value'   => 'untitled',
						'compare' => 'LIKE',
					),
				)
			);
		}

		if ( 'viewall' == $filter ) {
				$query->set(
					'meta_query',
					array(
						'relation' => 'OR',
						array(
							'key'     => '_wp_attachment_image_alt',
							'value'   => '',
							'compare' => '=',
						),
						array(
							'key'     => '_wp_attachment_image_alt',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => '_wp_attachment_image_alt',
							'value'   => '.jpg',
							'compare' => 'LIKE',
						),
						array(
							'key'     => '_wp_attachment_image_alt',
							'value'   => '.jpeg',
							'compare' => 'LIKE',
						),
						array(
							'key'     => '_wp_attachment_image_alt',
							'value'   => '.png',
							'compare' => 'LIKE',
						),
						array(
							'key'     => '_wp_attachment_image_alt',
							'value'   => '.gif',
							'compare' => 'LIKE',
						),
						array(
							'key'     => '_wp_attachment_image_alt',
							'value'   => '_',
							'compare' => 'LIKE',
						),
						array(
							'key'     => '_wp_attachment_image_alt',
							'value'   => 'photo of',
							'compare' => 'LIKE',
						),
						array(
							'key'     => '_wp_attachment_image_alt',
							'value'   => 'image of',
							'compare' => 'LIKE',
						),
						array(
							'key'     => '_wp_attachment_image_alt',
							'value'   => 'graphic of',
							'compare' => 'LIKE',
						),
					)
				);
		}
	}
}
add_action( 'pre_get_posts', 'wp_ada_compliance_basic_media_filter' );

/**
 * Remove autoplay attributes from audio and video embeds
 **/
function wp_ada_compliance_basic_filter_autoplay_av_tags( $content, $run = 0 ) {
	$strip_autoplay = get_option( 'wp_ada_compliance_strip_autoplay', 'true' );

	if ( 'true' == $strip_autoplay || 1 == $run ) {

		$content = str_ireplace( 'autostart="true"', '', $content );
		$content = str_ireplace( "autostart='true'", '', $content );
		$content = str_ireplace( '&autoPlay=true', '', $content );
		$content = str_ireplace( '&amp;autoPlay=true', '', $content );
		$content = preg_replace( '/<param(\s)*name=(\'|")+(autoplay)(\'|")+(\/)*(\s)*value=(\'|")+(true)(\'|")+(\s*\/*>)(<\/param>)*/i', '', $content );
		$content = str_ireplace( 'autoPlay="autoplay"', '', $content );
		$content = str_ireplace( 'autoplay=""', '', $content );
		$content = str_ireplace( "autoplay=''", '', $content );
		$content = str_ireplace( "autoPlay='autoplay'", '', $content );
		$content = str_ireplace( 'autoPlay="1"', '', $content );
		$content = str_ireplace( "autoPlay='1'", '', $content );
		$content = preg_replace( '/(<audio+.+)((\s)((\S)+=("|\')(\w|\s|-|_)*("|\'))*(\s))*(autoplay)+(((\s)*((\S)+=("|\')(\w|\s|-|_)*("|\'))*(\s)*)*>)/i', '$1$11', $content );
		$content = preg_replace( '/(<video+.+)((\s)((\S)+=("|\')(\w|\s|-|_)*("|\'))*(\s))*(autoplay)+(((\s)*((\S)+=("|\')(\w|\s|-|_)*("|\'))*(\s)*)*>)/i', '$1$11', $content );
	}

	return $content;
}
/**
 * Closed unclosed tags to keep from breaking report
 **/
function wp_ada_compliance_basic_close_unclosed_tables( $object ) {
	$tabletags      = substr_count( $object, '<table' );
	$tableclosetags = substr_count( $object, '</table>' );
	if ( $tabletags > $tableclosetags ) {
		$numbertoadd = ( $tabletags - $tabletags );
		for ( $i = 1; $i <= $numbertoadd; $i++ ) {
			$object .= '</table>';
		}
	}
	return $object;
}

/**
 * Get trusted tags for sanitation
 */
function wp_ada_compliance_basic_get_trusted_tags_array() {

	$trustedtags = array(
		'svg'      => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'style'    => array(
			'class' => array(),
			'id'    => array(),
		),
		'metadata' => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'g'        => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'path'     => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'button'   => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'embed'    => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'p'        => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'a'        => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'img'      => array(
			'src'    => array(),
			'alt'    => array(),
			'width'  => array(),
			'height' => array(),
			'style'  => array(),
			'class'  => array(),
			'id'     => array(),
			'usemap' => array(),
		),
		'h1'       => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'h2'       => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'h3'       => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'h4'       => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'h5'       => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'h6'       => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'input'    => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'map'      => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'area'     => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'audio'    => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'video'    => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'pre'      => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'textarea' => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'label'    => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'select'   => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'span'     => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'blink'    => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'i'        => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'fieldset' => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'caption'  => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'form'     => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'legend'   => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'br'       => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'div'      => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'nav'      => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'main'     => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'aside'    => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'header'   => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'footer'   => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'table'    => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'th'       => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'td'       => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'tr'       => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'tbody'    => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'thead'    => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'iframe'   => array(
			'src'    => array(),
			'style'  => array(),
			'id'     => array(),
			'class'  => array(),
			'height' => array(),
			'width'  => array(),
		),
	);

	return $trustedtags;
}
