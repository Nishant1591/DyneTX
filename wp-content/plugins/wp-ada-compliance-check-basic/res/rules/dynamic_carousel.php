<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * Carousels that require manual review
 */
function wp_ada_compliance_basic_validate_dynamic_carousel( $content, $postinfo ) {

	$wp_ada_compliance_basic_def = wp_ada_compliance_basic_def();

	$dom = str_get_html( $content );

	// get options.
	$wp_ada_compliance_basic_scanoptions = get_option( 'wp_ada_compliance_basic_ignore_scan_rules', array() );

	// check if being scanned.
	if ( in_array( 'dynamic_carousel', $wp_ada_compliance_basic_scanoptions ) ) {
		return;
	}

	// code to check for various slideshows (compiled from most used WordPress plugins).

	// check for elementor image carousel widgets.
	wp_ada_compliance_basic_check_for_elementor_carousels( $dom, $postinfo );

	// check for metaslider carousels
	$foundslider = wp_ada_compliance_basic_check_for_metaslider_carousels( $dom, $postinfo );

	// check for flexslider if no other carousels where found.
	if ( 0 == $foundslider ) {
		wp_ada_compliance_basic_check_for_generic_flexslider_carousels( $dom, $postinfo );
	}

	// check for owl carousel.
	wp_ada_compliance_basic_check_for_owl_carousels( $dom, $postinfo );

	return;
}

/**
 * Check for generic flex slider
 **/
function wp_ada_compliance_basic_check_for_generic_flexslider_carousels( $dom, $postinfo ) {
	$wp_ada_compliance_basic_def = wp_ada_compliance_basic_def();

	$elements        = $dom->find( '[class*=flexslider]' );
	$carouselcounter = 1;
	foreach ( $elements as $element ) {
		$errorcode = '';

		// check if hidden before reporting.
		if ( 'true' !== $element->getAttribute( 'aria-hidden' ) ) {

			$carouselcode = $element->outertext;

			$carouselid = $element->getAttribute( 'id' );

			// check for auto play.
			$autoplayon = 0;
			$scripts    = $dom->find( 'script' );
			foreach ( $scripts as $script ) {
				if ( strstr( $script->innertext, '.flexslider' ) && ! preg_match( '!slideshow:\s?false!', $script->innertext ) ) {
					$autoplayon = 1;
				}
			}
			if ( 1 == $autoplayon ) {
				$errorcode .= '[newline]';
				$errorcode .= __( ' This carousel is set to autoplay. Any moving, blinking or scrolling information that starts automatically, lasts more than five seconds, and is presented in parallel with other content must have a mechanism for the user to pause, stop, or hide it. Edit the carousel settings to disable autoplay or enable the autocorrect feature under the Content Filters tab. ', 'wp-ada-compliance-basic' );
			}

			$images     = $element->find( 'img' );
			$imageerror = '';
			foreach ( $images as $image ) {
				if ( '' == $imageerror && '' == $image->getAttribute( 'alt' ) && wp_ada_compliance_check_basic_image_visibility( $image ) ) {
					$imageerror = __( ' One or more images are missing alternate text. Carousels that provide only decorative images should be hidden from screen reader users using aria-hidden="true" or marked with role="presentation". If images are enclosed in a link and not hidden from screen reader users they should have alt text that describes the purpose of the link.', 'wp-ada-compliance-basic' );
				}
			}
			if ( '' != $imageerror ) {
				$errorcode .= '[newline]';
				$errorcode .= '[newline]' . $imageerror;
			}

			$postinfo['examplecode'] = '<div style="margin: 20px; background-color: #eee; padding: 10px;">' . $carouselcode . htmlspecialchars( $carouselcode ) . '</div>';

			if ( '' == $carouselid ) {
				$carouselid = $carouselcounter;
			}
			$errorcode = __( 'Carousel Id:', 'wp-ada-compliance-basic' ) . $carouselid . __( ': A Flexslider Carousel was found. Flexslider Carousels include readable text on navigation buttons, can be navigated using a keyboard and hide inactive images from screen reader users but they do not include the correct structural markup and labeling and are set to autoplay by default and paging controls contain only numbers which can be confusing to screen reader users. The dynamic carousel auto correct setting should be enabled to automatically correct issues in the Flexslider Carousel plugin.', 'wp-ada-compliance-basic' ) . $errorcode;

			// save error.
			if ( ! $insertid = wp_ada_compliance_basic_error_check( $postinfo, 'dynamic_carousel', $errorcode ) ) {
				$insertid = wp_ada_compliance_basic_insert_error( $postinfo, 'dynamic_carousel', $wp_ada_compliance_basic_def['dynamic_carousel']['StoredError'], $errorcode );

			}
			++$carouselcounter;
		}
	}
}

/**
 * Check for elementor carousel
 **/
function wp_ada_compliance_basic_check_for_elementor_carousels( $dom, $postinfo ) {
	$wp_ada_compliance_basic_def = wp_ada_compliance_basic_def();

	$elements        = $dom->find( '[class*=elementor-widget-image-carousel]' );
	$carouselcounter = 1;
	foreach ( $elements as $element ) {
		$errorcode = '';

		// check if hidden before reporting.
		if ( 'true' !== $element->getAttribute( 'aria-hidden' ) ) {

			$carouselcode = $element->outertext;

			// set counter so all menus are reported.
			$carouselid = $element->getAttribute( 'id' );

			// check for auto play.
			if ( strstr( $element->getAttribute( 'data-settings' ), '&quot;autoplay&quot;:&quot;yes&quot;' )
			|| strstr( $element->getAttribute( 'data-settings' ), '"autoplay":"yes"' ) ) {
				$errorcode .= '[newline]';
				$errorcode .= __( ' This carousel is set to autoplay. Any moving, blinking or scrolling information that starts automatically, lasts more than five seconds, and is presented in parallel with other content must have a mechanism for the user to pause, stop, or hide it. Edit the carousel settings to disable autoplay or enable the autocorrect feature under the Content Filters tab. ', 'wp-ada-compliance-basic' );
			}

			$images     = $element->find( 'img' );
			$imageerror = '';
			foreach ( $images as $image ) {
				if ( '' == $imageerror && '' == $image->getAttribute( 'alt' ) && wp_ada_compliance_check_basic_image_visibility( $image ) ) {
					$imageerror = __( ' One or more images are missing alternate text. Carousels that provide only decorative images should be hidden from screen reader users using aria-hidden="true" or marked with role="presentation". If images are enclosed in a link and not hidden from screen reader users they should have alt text that describes the purpose of the link.', 'wp-ada-compliance-basic' );
				}
			}
			if ( '' != $imageerror ) {
				$errorcode .= '[newline]';
				$errorcode .= '[newline]' . $imageerror;
			}

			$postinfo['examplecode'] = '<div style="margin: 20px; background-color: #eee; padding: 10px;">' . $carouselcode . htmlspecialchars( $carouselcode ) . '</div>';

			if ( '' == $carouselid ) {
				$carouselid = $carouselcounter;
			}
			$errorcode = __( 'Carousel Id:', 'wp-ada-compliance-basic' ) . $carouselid . __( ': An Elementor Carousel Widget was found. Elementor Carousels include readable text on navigation buttons, can be navigated using a keyboard and use the images alternate text but do not include the correct structural markup and labeling. Elementor Carousels display images before navigation options, do not hide inactive slides from screen reader users and do not provide an option to bypass the content, forcing keyboard users to navigate through each image before reaching the navigation and other page content. They also default to auto play and do not include a pause button.', 'wp-ada-compliance-basic' ) . $errorcode;

			// save error.
			if ( ! $insertid = wp_ada_compliance_basic_error_check( $postinfo, 'dynamic_carousel', $errorcode ) ) {
				$insertid = wp_ada_compliance_basic_insert_error( $postinfo, 'dynamic_carousel', $wp_ada_compliance_basic_def['dynamic_carousel']['StoredError'], $errorcode );

			}
			++$carouselcounter;
		}
	}
}


/**
 * Check for metaslider
 **/
function wp_ada_compliance_basic_check_for_metaslider_carousels( $dom, $postinfo ) {
	$wp_ada_compliance_basic_def = wp_ada_compliance_basic_def();

	$foundslider = 0;

	$elements        = $dom->find( '[class*=metaslider-flex]' );
	$carouselcounter = 1;
	foreach ( $elements as $element ) {
		$errorcode = '';

		// check if hidden before reporting.
		if ( 'true' !== $element->getAttribute( 'aria-hidden' ) ) {

			$carouselcode = $element->outertext;

			$carouselid = $element->getAttribute( 'id' );

			// check for auto play.
			$flexsliderid       = '';
			$flexsliderelements = $element->find( 'div div' );
			foreach ( $flexsliderelements as $flexsliderelement ) {
				$flexsliderid = $flexsliderelement->getAttribute( 'id' );
			}
			$autoplayon = 0;
			if ( '' != $flexsliderid ) {
				$metasliderscripts = $dom->find( 'script[id*=metaslider]' );
				foreach ( $metasliderscripts as $metasliderscript ) {
					if ( strstr( $metasliderscript->innertext, 'slideshow:true' ) ) {
						$autoplayon = 1;
					}
				}
				if ( 1 == $autoplayon ) {
					$errorcode .= '[newline]';
					$errorcode .= __( ' This carousel is set to autoplay. Any moving, blinking or scrolling information that starts automatically, lasts more than five seconds, and is presented in parallel with other content must have a mechanism for the user to pause, stop, or hide it. Edit the carousel settings to disable autoplay or enable the autocorrect feature under the Content Filters tab. ', 'wp-ada-compliance-basic' );
				}
			}

			$images     = $element->find( 'img' );
			$imageerror = '';
			foreach ( $images as $image ) {
				if ( '' == $imageerror && '' == $image->getAttribute( 'alt' ) && wp_ada_compliance_check_basic_image_visibility( $image ) ) {
					$imageerror = __( ' One or more images are missing alternate text. Carousels that provide only decorative images should be hidden from screen reader users using aria-hidden="true" or marked with role="presentation". If images are enclosed in a link and not hidden from screen reader users they should have alt text that describes the purpose of the link.', 'wp-ada-compliance-basic' );
				}
			}
			if ( '' != $imageerror ) {
				$errorcode .= '[newline]';
				$errorcode .= '[newline]' . $imageerror;
			}

			$postinfo['examplecode'] = '<div style="margin: 20px; background-color: #eee; padding: 10px;">' . $carouselcode . htmlspecialchars( $carouselcode ) . '</div>';

			if ( '' == $carouselid ) {
				$carouselid = $carouselcounter;
			}
			$errorcode = __( 'Carousel Id:', 'wp-ada-compliance-basic' ) . $carouselid . __( ': A MetaSlider Carousel was found. MetaSlider Carousels include readable text on navigation buttons, can be navigated using a keyboard, hide inactive images from screen reader users, use image alternate text and use an aria-live region so that slide content is read to screen reader users. However they do not include the correct structural markup and labeling. MetaSlider Carousels display images before navigation options which is a minor problem since only the active image is visible. MetaSlider Carousels default to auto play and read slide content with out providing a pause button. Paging controls contain only numbers which can be confusing to screen reader users.', 'wp-ada-compliance-basic' ) . $errorcode;

			// save error.
			if ( ! $insertid = wp_ada_compliance_basic_error_check( $postinfo, 'dynamic_carousel', $errorcode ) ) {
				$insertid = wp_ada_compliance_basic_insert_error( $postinfo, 'dynamic_carousel', $wp_ada_compliance_basic_def['dynamic_carousel']['StoredError'], $errorcode );

				$foundslider = 1;
			}
			++$carouselcounter;
		}
	}

	return $foundslider;
}

/**
 * Check for owl carousel
 **/
function wp_ada_compliance_basic_check_for_owl_carousels( $dom, $postinfo ) {
	$wp_ada_compliance_basic_def = wp_ada_compliance_basic_def();

	$elements        = $dom->find( '[class*=owl-carousel]' );
	$carouselcounter = 1;
	foreach ( $elements as $element ) {
		$errorcode = '';

		// check if hidden before reporting.
		if ( 'true' !== $element->getAttribute( 'aria-hidden' ) ) {

			$carouselcode = $element->outertext;

			$carouselid = $element->getAttribute( 'id' );

			// check for auto play.
			$autoplayon = 0;
			$scripts    = $dom->find( 'script' );
			foreach ( $scripts as $script ) {
				if ( strstr( $script->innertext, '.owl-carousel' ) and ! preg_match( '!autoPlay\s?:\s?false!i', $script->innertext ) ) {
					$autoplayon = 1;
				}
			}
			if ( 1 == $autoplayon ) {
				$errorcode .= '[newline]';
				$errorcode .= __( ' This carousel is set to autoplay. Any moving, blinking or scrolling information that starts automatically, lasts more than five seconds, and is presented in parallel with other content must have a mechanism for the user to pause, stop, or hide it. Edit the carousel settings to disable autoplay or enable the autocorrect feature under the Content Filters tab. ', 'wp-ada-compliance-basic' );
			}

			$images     = $element->find( 'img' );
			$imageerror = '';
			foreach ( $images as $image ) {
				if ( '' == $imageerror && '' == $image->getAttribute( 'alt' ) && wp_ada_compliance_check_basic_image_visibility( $image ) ) {
					$imageerror = __( ' One or more images are missing alternate text. Carousels that provide only decorative images should be hidden from screen reader users using aria-hidden="true" or marked with role="presentation". If images are enclosed in a link and not hidden from screen reader users they should have alt text that describes the purpose of the link.', 'wp-ada-compliance-basic' );
				}
			}
			if ( '' != $imageerror ) {
				$errorcode .= '[newline]';
				$errorcode .= '[newline]' . $imageerror;
			}

			$postinfo['examplecode'] = '<div style="margin: 20px; background-color: #eee; padding: 10px;">' . $carouselcode . htmlspecialchars( $carouselcode ) . '</div>';

			if ( '' == $carouselid ) {
				$carouselid = $carouselcounter;
			}
			$errorcode = __( 'Carousel Id:', 'wp-ada-compliance-basic' ) . $carouselid . __( ': An Owl Carousel was found. Owl Carousels do not include the correct structural markup and labeling and are set to autoplay by default, they do not hide inactive images from screen reader users forcing them to browse all carousel content, paging controls have no alternate text and are located after the carousel content.', 'wp-ada-compliance-basic' ) . $errorcode;

			// save error.
			if ( ! $insertid = wp_ada_compliance_basic_error_check( $postinfo, 'dynamic_carousel', $errorcode ) ) {
				$insertid = wp_ada_compliance_basic_insert_error( $postinfo, 'dynamic_carousel', $wp_ada_compliance_basic_def['dynamic_carousel']['StoredError'], $errorcode );

			}
			++$carouselcounter;
		}
	}
}
