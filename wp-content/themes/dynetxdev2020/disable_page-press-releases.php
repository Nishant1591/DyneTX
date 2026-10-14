<?php
/**
* Template Name: Press Realeses
*/
?>
<?php get_header(); ?>
<h1>TREST</h1>
    <!-- PAGE -->	
		<?php while ( have_posts() ) : the_post(); ?>		  			
			<?php the_content(); ?>		  
		<?php endwhile; // End of the loop. ?>
	
    <!-- /PAGE -->
<?php get_footer(); ?>