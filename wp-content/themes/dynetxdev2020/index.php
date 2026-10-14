<?php get_header(); ?>

    <!-- PAGE -->	
		<?php while ( have_posts() ) : the_post(); ?>		  			
			<?php the_content(); ?>		  
		<?php endwhile; // End of the loop. ?>
	
    <!-- /PAGE -->
<?php get_footer(); ?>