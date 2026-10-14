<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * Validate missing landmarks
 */
function wp_ada_compliance_basic_validate_missing_landmarks( $content, $postinfo ) {

	$wp_ada_compliance_basic_def = wp_ada_compliance_basic_def();

	if ( 'onsave' == $postinfo['scantype'] ) {
		return;
	}

	// get options.
	$wp_ada_compliance_basic_scanoptions = get_option( 'wp_ada_compliance_basic_ignore_scan_rules', array() );

	// check if being scanned.
	if ( in_array( 'missing_landmarks', $wp_ada_compliance_basic_scanoptions ) ) {
		return;
	}

	$dom                     = str_get_html( $content );
	$dom                     = wp_ada_compliance_basic_remove_hidden_elements( $dom );
	$errorcode               = '';
	$postinfo['examplecode'] = '';

	// check main.
	$mains = $dom->find( 'main,[role=main]' );
	if ( 0 == count( $mains ) ) {
		$errorcode .= __( 'No main landmark was found (i.e... &lt;main&gt; ||  role="main"). The main landmark should include the main content in a document. Other landmarks that may be required include complementary, contentinfo, form and search. ', 'wp-ada-compliance-basic' );
		wp_ada_compliance_basic_insert_landmark_error( $errorcode, $postinfo, $wp_ada_compliance_basic_def, 'missing_landmarks' );
		$errorcode = '';
	}

	// check navs.
	$navs = $dom->find( 'nav, [role=navigation]' );
	if ( 0 == count( $navs ) ) {
		$errorcode .= __( 'No navigation landmark was found (i.e... &lt;nav&gt; or role="navigation"). The nav landmark should contain a collection of links suitable for use when navigating the document or related documents. ', 'wp-ada-compliance-basic' );

		wp_ada_compliance_basic_insert_landmark_error( $errorcode, $postinfo, $wp_ada_compliance_basic_def, 'missing_landmarks' );
		$errorcode = '';
	}

	// check for search forms without a role attribute set to search.
	$forms = $dom->find( 'form' );
	foreach ( $forms as $form ) {
		if ( 'search' != strtolower( $form->getAttribute( 'role' ) )
		&& ( stristr( $form->outertext, 'title="' . __( 'Search', 'wp-ada-compliance-basic' ) . '"' )
		|| stristr( $form->outertext, 'title=\'' . __( 'Search', 'wp-ada-compliance-basic' ) . '\'' )
		|| stristr( $form->outertext, 'aria-label="' . __( 'Search', 'wp-ada-compliance-basic' ) . '"' )
		|| stristr( $form->outertext, 'aria-label=\'' . __( 'Search', 'wp-ada-compliance-basic' ) . '\'' )
			|| stristr( $form->outertext, 'value="' . __( 'Search', 'wp-ada-compliance-basic' ) . '"' )
		|| stristr( $form->outertext, 'value=\'' . __( 'Search', 'wp-ada-compliance-basic' ) . '\'' )
		)
		) {

			$errorcode              .= __( 'A search form is used without the role attribute set to search (i.e... role="search")', 'wp-ada-compliance-basic' );
			$postinfo['examplecode'] = esc_attr( $form->outertext ) . $form->outertext;
			wp_ada_compliance_basic_insert_landmark_error( $errorcode, $postinfo, $wp_ada_compliance_basic_def, 'missing_landmarks' );
			$errorcode               = '';
			$postinfo['examplecode'] = '';
		}
	}

/**
 * Run these checks  last or elements will be stripped
 */
	// check header/banner.
	$headers = wp_ada_compliance_basic_remove_tags_wrapped_in_excluded_sections( $dom, 'header' );
	if ( 0 == count( $headers ) ) {
		$errorcode .= __( 'No banner/header landmark was found (i.e... &lt;header&gt; or role="banner"). The header landmark should contain the primary heading or internal title of a page.  Other landmarks that may be required include complementary or aside, contentinfo of footer, form and search.', 'wp-ada-compliance-basic' );
		wp_ada_compliance_basic_insert_landmark_error( $errorcode, $postinfo, $wp_ada_compliance_basic_def, 'missing_landmarks' );
		$errorcode               = '';
		$postinfo['examplecode'] = '';
	}

	// check contentinfo.
	$contentinfo = wp_ada_compliance_basic_remove_tags_wrapped_in_excluded_sections( $dom, 'footer' );
	if ( 0 == count( $contentinfo ) ) {
		$errorcode .= __( 'No footer/contentinfo landmark was found (i.e... &lt;footer&gt; or role="contentinfo"). A footer landmark is a way to identify common information at the bottom of each page within a website, typically called the "footer" of the page, including information such as copyrights and links to privacy and accessibility statements. ', 'wp-ada-compliance-basic' );
		wp_ada_compliance_basic_insert_landmark_error( $errorcode, $postinfo, $wp_ada_compliance_basic_def, 'missing_landmarks' );
		$errorcode               = '';
		$postinfo['examplecode'] = '';
	}
}

/**
 * Insert error
 ***/
function wp_ada_compliance_basic_insert_landmark_error( $errorcode, $postinfo, $wp_ada_compliance_basic_def, $errortype ) {

	// save error.
	if ( ! $insertid = wp_ada_compliance_basic_error_check( $postinfo, $errortype, $errorcode ) ) {
		$insertid = wp_ada_compliance_basic_insert_error( $postinfo, $errortype, $wp_ada_compliance_basic_def[ $errortype ]['StoredError'], $errorcode );
	}
}

/**
 * Remove elements wrapped in excluded tags
 **/
function wp_ada_compliance_basic_remove_tags_wrapped_in_excluded_sections( $dom, $tag ) {
	if ( null == $dom ) {
		return;
	}
	$ignored[] = 'main';
	$ignored[] = 'article';
	$ignored[] = 'aside';
	$ignored[] = 'nav';
	$ignored[] = 'section';

	foreach ( $ignored as $key => $value ) {
		$elements = $dom->find( $value );
		foreach ( $elements as $element ) {
			if ( is_object( $element->parent() ) ) {
				$element->parent()->removeChild( $element );
			}
		}
	}
	if ( 'footer' == $tag ) {
		$newelements = $dom->find( 'footer,[role=contentinfo]' );
	}
	if ( 'header' == $tag ) {
		$newelements = $dom->find( 'header,[role=banner]' );
	}

	return $newelements;
}

/**
 * Remove hidden
 **/
function wp_ada_compliance_basic_remove_hidden_elements( $dom ) {

	$elements = $dom->find( '[aria-hidden=true]' );
	foreach ( $elements as $element ) {
		if ( is_object( $element->parent() ) ) {
			$element->parent()->removeChild( $element );
		}
	}
	return $dom;
}
