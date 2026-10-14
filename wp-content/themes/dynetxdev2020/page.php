<?php get_header(); ?>

    <!-- PAGE -->	
	<?php while ( have_posts() ) : the_post(); ?>		  			
		<?php the_content(); ?>		  
	<?php endwhile; // End of the loop. ?>
	<?php if ($_SERVER['REQUEST_URI'] == '/cookie-declaration/'): ?>
		<div class="container-fluid section">
          	<div class="container">
				<script id="CookieDeclaration" src="https://consent.cookiebot.com/23b37b73-f275-4a71-8aa5-ac47aa8bfbfe/cd.js" type="text/javascript" async></script>
			</div>
				
		</div>

	<?php endif ?>
	
    <!-- /PAGE -->
<?php get_footer(); ?>