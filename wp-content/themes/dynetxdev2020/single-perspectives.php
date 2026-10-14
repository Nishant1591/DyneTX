<?php
/**
 * The Template for displaying Second-level pages
 *
 * Template name: Perspectives
 */

get_header();

while ( have_posts() ) {
    the_post();
    get_template_part ( 'content', 'perspectives' );
}

if (is_404()) {
    get_template_part ( 'content', 'error404' );
}


get_footer();