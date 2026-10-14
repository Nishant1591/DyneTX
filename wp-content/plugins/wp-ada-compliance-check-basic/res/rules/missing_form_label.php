<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * Validate form fields for labels
 */
function wp_ada_compliance_basic_validate_missing_form_label( $content, $postinfo ) {

	$wp_ada_compliance_basic_def = wp_ada_compliance_basic_def();

	$dom = str_get_html( $content );

	// get options.
	$wp_ada_compliance_basic_scanoptions = get_option( 'wp_ada_compliance_basic_ignore_scan_rules', array() );

	// check if being scanned.
	if ( in_array( 'missing_form_label', $wp_ada_compliance_basic_scanoptions ) ) {
		return 1;
	}

	$fields    = $dom->find( 'input' );
	$selects   = $dom->find( 'select' );
	$textareas = $dom->find( 'textarea' );

	// store label values in array.
	if ( isset( $labelfors ) ) {
		unset( $labelfors );
	}
	$labelfors = array();
	$labels    = $dom->find( 'label' );
	foreach ( $labels as $label ) {
		if ( isset( $label ) ) {
			$labelfors[] = $label->getAttribute( 'for' );
		}
	}
	if ( ! isset( $labelfors ) ) {
		$labelfors[] = '%^&*';
	}

	$form_atttributes_to_ignore   = array();
	$form_atttributes_to_ignore[] = 'submit';
	$form_atttributes_to_ignore[] = 'hidden';
	$form_atttributes_to_ignore[] = 'button';

	// check input fields.
	foreach ( $fields as $field ) {

		// ignore.
		if ( isset( $field ) && ! in_array( strtolower( $field->getAttribute( 'type' ) ), $form_atttributes_to_ignore ) && ! preg_match( '/display:\s?none;/i', $field->parent()->getAttribute( 'style' ) ) && ! preg_match( '/display:\s?none;/i', $field->getAttribute( 'style' ) ) ) {

			if ( ( '' == $field->getAttribute( 'id' ) || ! in_array( $field->getAttribute( 'id' ), $labelfors ) )
			&& 'label' != $field->parent()->tag
			&& 'label' != $field->parent()->parent()->tag
			&& ( ( '' == $field->getAttribute( 'aria-labelledby' )
				&& '' == $field->getAttribute( 'aria-describedby' )
			&& '' == $field->getAttribute( 'aria-label' )
				&& '-1' != $field->getAttribute( 'tabindex' )
			&& '' == $field->getAttribute( 'title' )
			&& 'image' != $field->getAttribute( 'type' ) )
			|| ( 'image' == $field->getAttribute( 'type' )
			&& '' == $field->getAttribute( 'alt' ) ) ) ) {

				$formfieldcode = $field->outertext;

				// save error.
				if ( ! $insertid = wp_ada_compliance_basic_error_check( $postinfo, 'missing_form_label', $formfieldcode ) ) {
					$insertid = wp_ada_compliance_basic_insert_error( $postinfo, 'missing_form_label', $wp_ada_compliance_basic_def['missing_form_label']['StoredError'], $formfieldcode );
				}
			}
		}
	}

	// check select fields.
	foreach ( $selects as $field ) {

		if ( isset( $field ) && ! preg_match( '/display:\s?none;/i', $field->parent()->getAttribute( 'style' ) ) && ! preg_match( '/display:\s?none;/i', $field->getAttribute( 'style' ) ) ) {

			if ( isset( $field ) ) {
				$nodetext = trim( strip_tags( preg_replace( '/<select[^>]*>([\s\S]*?)<\/select[^>]*>/', '', $field->parent()->outertext ) ) );
			}

			$nodetextparent = trim( strip_tags( preg_replace( '/<select[^>]*>([\s\S]*?)<\/select[^>]*>/', '', $field->parent()->parent()->outertext ) ) );

			if ( isset( $field )
			&& ( '' == $field->getAttribute( 'id' ) || ! in_array( $field->getAttribute( 'id' ), $labelfors ) )
			&& ( 'label' != $field->parent()->tag
				|| ( 'label' == $field->parent()->tag && '' == $nodetext ) )
			&& ( 'label' != $field->parent()->parent()->tag
				|| ( 'label' == $field->parent()->parent()->tag && '' == $nodetextparent ) )
			&& '' == $field->getAttribute( 'aria-labelledby' )
			&& '' == $field->getAttribute( 'aria-label' )
			&& '-1' != $field->getAttribute( 'tabindex' )
			&& '' == $field->getAttribute( 'title' ) ) {

				$formfieldcode = $field->outertext;

				// save error.
				if ( ! $insertid = wp_ada_compliance_basic_error_check( $postinfo, 'missing_form_label', $formfieldcode ) ) {
					$insertid = wp_ada_compliance_basic_insert_error( $postinfo, 'missing_form_label', $wp_ada_compliance_basic_def['missing_form_label']['StoredError'], $formfieldcode );
				}
			}
		}
	}

	// check textareas fields.

	foreach ( $textareas as $field ) {

		// ignore.
		if ( isset( $field ) && ! preg_match( '/display:\s?none;/i', $field->parent()->getAttribute( 'style' ) ) && ! preg_match( '/display:\s?none;/i', $field->getAttribute( 'style' ) ) ) {

			if ( isset( $field )
			&& ( '' == $field->getAttribute( 'id' ) || ! in_array( $field->getAttribute( 'id' ), $labelfors ) )
			&& 'label' != $field->parent()->tag
			&& 'label' != $field->parent()->parent()->tag
			&& '' == $field->getAttribute( 'aria-labelledby' )
			&& '' == $field->getAttribute( 'aria-label' )
			&& '-1' != $field->getAttribute( 'tabindex' )
			&& '' == $field->getAttribute( 'title' ) ) {
				$formfieldcode = $field->outertext;

				// save error.
				if ( ! $insertid = wp_ada_compliance_basic_error_check( $postinfo, 'missing_form_label', $formfieldcode ) ) {
					$insertid = wp_ada_compliance_basic_insert_error( $postinfo, 'missing_form_label', $wp_ada_compliance_basic_def['missing_form_label']['StoredError'], $formfieldcode );
				}
			}
		}
	}

	// IDENTIFY EMPTY LABELS.
	$labels = $dom->find( 'label' );
	foreach ( $labels as $label ) {
		if ( isset( $label ) && '' == trim( strip_tags( preg_replace( '/<select[^>]*>([\s\S]*?)<\/select[^>]*>/', '', $label->outertext ) ) )
		) {

			$missing_form_label_errorcode = $label->outertext;

			// if not hidden from screen readers.
			if ( ! stristr( $missing_form_label_errorcode, 'tabindex="-1"' ) && ! stristr( $missing_form_label_errorcode, "tabindex='-1'" ) ) {
				// save error.
				if ( ! $insertid = wp_ada_compliance_basic_error_check( $postinfo, 'missing_form_label', $missing_form_label_errorcode ) ) {

					$insertid = wp_ada_compliance_basic_insert_error( $postinfo, 'missing_form_label', $wp_ada_compliance_basic_def['missing_form_label']['StoredError'], $missing_form_label_errorcode );
				}
			}
		}
	}

	return 1;
}
