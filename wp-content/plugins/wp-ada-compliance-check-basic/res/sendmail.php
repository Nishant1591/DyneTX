<?php
/**
 * EMAIL FUNCTIONS
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * Cron function to send notifications of new issues
 */
function wp_ada_compliance_basic_send_email_notifications() {
	$notification_email = get_option( 'wp_ada_compliance_basic_notification_email', '' );

	$emailaddresses = explode( ',', $notification_email );
	foreach ( $emailaddresses as $key => $email ) {
		if ( '' != $email ) {
			$headers  = array( 'Content-Type: text/html; charset=UTF-8' );
			$blogname = get_bloginfo( 'name' );
			$subject  = esc_html( $blogname );
			$subject .= __( ' Web Accessibility Report', 'wp-ada-compliance-basic' );

			$body = wp_ada_compliance_basic_create_email_report( $email );
			if ( '' != $body ) {
				wp_mail( $email, $subject, $body, $headers );
			}
		}
	}
}
/**
 * Send email report
 **/
function wp_ada_compliance_basic_send_report() {

	if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), 'wp-ada-compliance-nonce' ) ) {
		die();
	}

	$error = wp_ada_compliance_basic_form_values();

	if ( isset( $_GET['view'] ) ) {
		$postinfo['view'] = (int) $_GET['view'];
	} else {
		$postinfo['view'] = 1;
	}
	if ( isset( $_GET['sort'] ) ) {
		$postinfo['sort'] = (int) $_GET['sort'];
	} else {
		$postinfo['sort'] = 1;
	}
	if ( isset( $_GET['errorid'] ) ) {
		$postinfo['errorid'] = (int) $_GET['errorid'];
	} else {
		$postinfo['errorid'] = '';
	}
	if ( isset( $_GET['error'] ) ) {
		$postinfo['error'] = sanitize_text_field( wp_unslash( $_GET['error'] ) );
	} else {
		$postinfo['error'] = '';
	}
	if ( isset( $_GET['type'] ) ) {
		$postinfo['type'] = sanitize_text_field( wp_unslash( $_GET['type'] ) );
	} else {
		$postinfo['type'] = '';
	}
	if ( isset( $_GET['searchtitle'] ) ) {
		$postinfo['searchtitle'] = sanitize_text_field( wp_unslash( $_GET['searchtitle'] ) );
	} else {
		$postinfo['searchtitle'] = '';
	}

	if ( isset( $_POST['email'] )
	&& '' === $error
	&& isset( $_POST['error'] )
	&& isset( $_POST['type'] )
	&& isset( $_POST['comments'] )
	&& isset( $_POST['searchtitle'] )
	&& isset( $_POST['view'] )
	&& isset( $_POST['errorid'] )
	&& isset( $_POST['sort'] )
	) {
		$postinfo['email']       = sanitize_text_field( wp_unslash( $_POST['email'] ) );
		$postinfo['view']        = (int) $_POST['view'];
		$postinfo['sort']        = (int) $_POST['sort'];
		$postinfo['errorid']     = (int) $_POST['errorid'];
		$postinfo['error']       = sanitize_text_field( wp_unslash( $_POST['error'] ) );
		$postinfo['type']        = sanitize_text_field( wp_unslash( $_POST['type'] ) );
		$postinfo['comments']    = sanitize_text_field( wp_unslash( $_POST['comments'] ) );
		$postinfo['searchtitle'] = sanitize_text_field( wp_unslash( $_POST['searchtitle'] ) );

		$headers  = array( 'Content-Type: text/html; charset=UTF-8' );
		$blogname = get_bloginfo( 'name' );
		$subject  = esc_html( $blogname );
		$subject .= __( ' Web Accessibility Report', 'wp-ada-compliance-basic' );

		$body = wp_ada_compliance_basic_create_email_report( $postinfo['email'], $postinfo );
		if ( '' != $body ) {
			$body = '<p>' . $postinfo['comments'] . '</p>' . $body;
			wp_mail( $postinfo['email'], $subject, $body, $headers );
			echo '<p class="adaAllGood">';
			esc_html_e( 'Report sent!', 'wp-ada-compliance-basic' );
			echo '</p>';
		} else {
			echo '<p class="adaRedText">';
			esc_html_e( 'No records to send!', 'wp-ada-compliance-basic' );
			echo '</p>';
		}
	} elseif ( '' != $error ) {
		echo '<p class="notice notice-error">' . esc_attr( $error ) . '</p>';
	}

	echo '<style>#wpadminbar, #adminmenuwrap, #adminmenuback{display:none;} body{margin-left:15px; margin-top: -40px;}</style>';
	echo '<form action="" method="post" class="wp-ada-send-report">';
	wp_nonce_field( 'wp-ada-compliance-nonce' );
	echo '<p>';
	echo '<label for="email">';
	esc_html_e( 'Email:', 'wp-ada-compliance-basic' );
	echo ' <input type="text" id="email" name="email"></label>
	<input type="submit"></p>
<p><label for="comments">';
	esc_html_e( 'Comments:', 'wp-ada-compliance-basic' );
	echo '<br /><textarea id="comments" name="comments" cols="45" rows="5"></textarea></label></p>
<input type="hidden" name="view" value="' . esc_attr( $postinfo['view'] ) . '" />
<input type="hidden" name="error" value="' . esc_attr( $postinfo['error'] ) . '" />
<input type="hidden" name="errorid" value="' . esc_attr( $postinfo['errorid'] ) . '" />
	<input type="hidden" name="type" value="' . esc_attr( $postinfo['type'] ) . '" />
	<input type="hidden" name="sort" value="' . esc_attr( $postinfo['sort'] ) . '" />
	<input type="hidden" name="searchtitle" value="' . esc_attr( $postinfo['searchtitle'] ) . '" />
	</form>';
}

/********************************************
// print report
 **********************************************/
function wp_ada_compliance_basic_print_report() {

	if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), 'wp-ada-compliance-nonce' ) ) {
		die();
	}

	wp_ada_compliance_basic_form_values();

	if ( isset( $_GET['view'] ) ) {
		$postinfo['view'] = (int) $_GET['view'];
	} else {
		$postinfo['view'] = 1;
	}
	if ( isset( $_GET['sort'] ) ) {
		$postinfo['sort'] = (int) $_GET['sort'];
	} else {
		$postinfo['sort'] = 1;
	}
	if ( isset( $_GET['errorid'] ) ) {
		$postinfo['errorid'] = (int) $_GET['errorid'];
	} else {
		$postinfo['errorid'] = '';
	}
	if ( isset( $_GET['error'] ) ) {
		$postinfo['error'] = sanitize_text_field( wp_unslash( $_GET['error'] ) );
	} else {
		$postinfo['error'] = '';
	}
	if ( isset( $_GET['type'] ) ) {
		$postinfo['type'] = sanitize_text_field( wp_unslash( $_GET['type'] ) );
	} else {
		$postinfo['type'] = '';
	}
	if ( isset( $_GET['search'] ) ) {
		$postinfo['searchtitle'] = sanitize_text_field( wp_unslash( $_GET['search'] ) );
	} elseif ( isset( $_GET['searchtitle'] ) ) {
		$postinfo['searchtitle'] = sanitize_text_field( wp_unslash( $_GET['searchtitle'] ) );
	} else {
		$postinfo['searchtitle'] = '';
	}

	$body = '';

	if ( isset( $_GET['wp-ada-report'] ) ) {
		// print full report.
		if ( 1 == $_GET['wp-ada-report'] ) {
			echo '<div class="wp-ada-preparing-report2">';
			esc_html_e( 'Stand by while we create the report, this could take a few minutes.', 'wp-ada-compliance-basic' );
			echo '</div>';

			$blogname = get_bloginfo( 'name' );
			$subject  = esc_html( $blogname );
			$subject .= __( ' Web Accessibility Report', 'wp-ada-compliance-basic' );

			$body = wp_ada_compliance_basic_create_email_report( 'print', $postinfo );
		}
	}

	echo '<div class="wp-ada-print-btns">';
	$nonce = wp_create_nonce( 'wp-ada-compliance-nonce' );
	echo '<p><a href="' . esc_url( get_site_url() ) . '/wp-admin/admin.php?page=ada_compliance/print-report.php&_wpnonce=' . esc_attr( $nonce ) . '&wp-ada-report=1&error=' . esc_attr( $postinfo['error'] ) . '&type=' . esc_attr( $postinfo['type'] ) . '&errorid=' . esc_attr( $postinfo['errorid'] ) . '&searchtitle=' . esc_attr( $postinfo['searchtitle'] ) . '&view=' . esc_attr( $postinfo['view'] ) . '&iframe=1" class="btnwpada btnwpada-primary wp-ada-report-btn">';
	esc_html_e( ' Print', 'wp-ada-compliance-basic' );
	echo '</a></p>';
	$nonce = wp_create_nonce( 'wp-ada-compliance-nonce' );
	echo '<p>  <a href="' . esc_url( get_site_url() ) . '/wp-admin/admin.php?page=ada_compliance/send-report.php&_wpnonce=' . esc_attr( $nonce ) . '&error=' . esc_attr( $postinfo['error'] ) . '&type=' . esc_attr( $postinfo['type'] ) . '&errorid=' . esc_attr( $postinfo['errorid'] ) . '&searchtitle=' . esc_attr( $postinfo['searchtitle'] ) . '&view=' . esc_attr( $postinfo['view'] ) . '&iframe=1" class="btnwpada btnwpada-primary" name="' . esc_html__( 'Email this report.', 'wp-ada-compliance-basic' ) . '">' . esc_html__( 'Email', 'wp-ada-compliance-basic' ) . '</a></p>';
	echo '</div>';

	if ( '' != $body ) {
		$trustedtags = wp_ada_compliance_basic_get_trusted_tags_array();
		echo '<div class="wp-ada-print_results"><h2>' . esc_html( $subject ) . '</h2>' . wp_kses( $body, $trustedtags ) . '</div>';
		echo '<script>window.print();</script>';
	}
}
