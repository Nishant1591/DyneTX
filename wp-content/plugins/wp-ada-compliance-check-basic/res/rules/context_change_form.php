<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * Validate forms that submit automatically or trigger an unexpected context change
 */
function wp_ada_compliance_basic_validate_context_change_form( $content, $postinfo ) {

	$wp_ada_compliance_basic_def = wp_ada_compliance_basic_def();

	$dom = str_get_html( $content );

	// get options.
	$wp_ada_compliance_basic_scanoptions = get_option( 'wp_ada_compliance_basic_ignore_scan_rules', array() );

	// check if being scanned.
	if ( in_array( 'context_change_form', $wp_ada_compliance_basic_scanoptions ) ) {
		return 1;
	}

	$forms = $dom->find( 'form' );

	$formAtttributestoIgnore   = array();
	$formAtttributestoIgnore[] = 'submit';
	$formAtttributestoIgnore[] = 'hidden';

	foreach ( $forms as $form ) {

		$fields    = $form->find( 'input' );
		$selects   = $form->find( 'select' );
		$textareas = $form->find( 'textarea' );

		// check input fields.
		foreach ( $fields as $field ) {
			$i = 0;
			// ignore
			if ( isset( $field ) && ! in_array( strtolower( $field->getAttribute( 'type' ) ), $formAtttributestoIgnore ) ) {

				if (
				( $field->getAttribute( 'onclick' )
				&& ( stristr( $field->getAttribute( 'onclick' ), 'window.open' )
				|| stristr( $field->getAttribute( 'onclick' ), '.submit' ) ) )
				|| ( $field->getAttribute( 'onchange' )
				&& ( stristr( $field->getAttribute( 'onchange' ), 'window.open' )
				|| stristr( $field->getAttribute( 'onchange' ), '.submit' ) ) )
				|| ( $field->getAttribute( 'onfocus' )
				&& ( stristr( $field->getAttribute( 'onfocus' ), 'window.open' )
				|| stristr( $field->getAttribute( 'onfocus' ), '.submit' ) ) )
				|| ( $field->getAttribute( 'onkeydown' )
				&& ( stristr( $field->getAttribute( 'onkeydown' ), 'window.open' )
				|| stristr( $field->getAttribute( 'onkeydown' ), '.submit' ) ) )
				|| ( $field->getAttribute( 'onkeypress' )
				&& ( stristr( $field->getAttribute( 'onkeypress' ), 'window.open' )
				|| stristr( $field->getAttribute( 'onkeypress' ), '.submit' ) ) ) ) {

					$formfieldcode = $field->outertext;

					// save error.
					if ( ! $insertid = wp_ada_compliance_basic_error_check( $postinfo, 'context_change_form', $formfieldcode ) ) {
						$insertid = wp_ada_compliance_basic_insert_error( $postinfo, 'context_change_form', $wp_ada_compliance_basic_def['context_change_form']['StoredError'], $formfieldcode );
					}
				}
				++$i;
			}
		}

		// check select fields.
		foreach ( $selects as $field ) {
			$i = 0;

			if (
			( $field->getAttribute( 'onclick' )
			&& ( stristr( $field->getAttribute( 'onclick' ), 'window.open' )
			|| stristr( $field->getAttribute( 'onclick' ), '.submit' ) )
			|| stristr( $field->getAttribute( 'onclick' ), 'jumpMenu' ) )
			|| ( $field->getAttribute( 'onchange' )
			&& ( stristr( $field->getAttribute( 'onchange' ), 'window.open' )
			|| stristr( $field->getAttribute( 'onchange' ), '.submit' ) )
			|| stristr( $field->getAttribute( 'onchange' ), 'jumpMenu' ) )
			|| ( $field->getAttribute( 'onfocus' )
			&& ( stristr( $field->getAttribute( 'onfocus' ), 'window.open' )
			|| stristr( $field->getAttribute( 'onfocus' ), '.submit' ) )
				|| stristr( $field->getAttribute( 'onfocus' ), 'jumpMenu' ) )
			|| ( $field->getAttribute( 'onkeydown' )
			&& ( stristr( $field->getAttribute( 'onkeydown' ), 'window.open' )
			|| stristr( $field->getAttribute( 'onkeydown' ), '.submit' ) )
				|| stristr( $field->getAttribute( 'onkeydown' ), 'jumpMenu' ) )
			|| ( $field->getAttribute( 'onkeypress' )
			&& ( stristr( $field->getAttribute( 'onkeypress' ), 'window.open' )
			|| stristr( $field->getAttribute( 'onkeypress' ), '.submit' )
			|| stristr( $field->getAttribute( 'onkeypress' ), 'jumpMenu' )
			) ) ) {

				$formfieldcode = $field->outertext;

				// save error.
				if ( ! $insertid = wp_ada_compliance_basic_error_check( $postinfo, 'context_change_form', $formfieldcode ) ) {
					$insertid = wp_ada_compliance_basic_insert_error( $postinfo, 'context_change_form', $wp_ada_compliance_basic_def['context_change_form']['StoredError'], $formfieldcode );
				}
			}
			++$i;
		}

		// check textareas fields.

		foreach ( $textareas as $field ) {
			$i = 0;

			if (
			( $field->getAttribute( 'onclick' )
			&& ( stristr( $field->getAttribute( 'onclick' ), 'window.open' )
			|| stristr( $field->getAttribute( 'onclick' ), '.submit' ) ) )
			|| ( $field->getAttribute( 'onchange' )
			&& ( stristr( $field->getAttribute( 'onchange' ), 'window.open' )
			|| stristr( $field->getAttribute( 'onchange' ), '.submit' ) ) )
			|| ( $field->getAttribute( 'onfocus' )
			&& ( stristr( $field->getAttribute( 'onfocus' ), 'window.open' )
			|| stristr( $field->getAttribute( 'onfocus' ), '.submit' ) ) )
			|| ( $field->getAttribute( 'onkeydown' )
			&& ( stristr( $field->getAttribute( 'onkeydown' ), 'window.open' )
			|| stristr( $field->getAttribute( 'onkeydown' ), '.submit' ) ) )
			|| ( $field->getAttribute( 'onkeypress' )
			&& ( stristr( $field->getAttribute( 'onkeypress' ), 'window.open' )
			|| stristr( $field->getAttribute( 'onkeypress' ), '.submit' ) ) ) ) {

				$formfieldcode = $field->outertext;

				// save error.
				if ( ! $insertid = wp_ada_compliance_basic_error_check( $postinfo, 'context_change_form', $formfieldcode ) ) {
					$insertid = wp_ada_compliance_basic_insert_error( $postinfo, 'context_change_form', $wp_ada_compliance_basic_def['context_change_form']['StoredError'], $formfieldcode );
				}
			}

			++$i;
		}
	}
	return 1;
}
