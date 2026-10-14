<?php

get_header();

if (have_posts()): while (have_posts()) : the_post(); ?>
<div class="container-fluid section">
    <div class="container jobs_container">
			<h2><?php the_title(); ?></h2>
			<?php if (!isset($_GET['candidate_saved'])) { ?>
				<div class="jobs_button_container">
					<?php /*<a href="<?php echo get_post_meta($post->ID, '_apply_url',true); ?>#app" class="button" target="_blank">Apply for this job</a> */ ?>
					<a href="https://boards.greenhouse.io/dynetherapeutics/jobs/<?php echo get_post_meta($post->ID, '_orig_job_id', true); ?>#app" target=”_blank” class="btn btn-primary btn-lg waves-effect waves-light">Apply for this job</a><br><br>
				</div>
			<?php } ?>
			<b>Department: <?php echo get_post_meta($post->ID, '_department', true); ?></b><br><br>
			<b>Location: <?php echo get_post_meta($post->ID, '_location', true); ?></b><br><br>
			<?php the_content(); ?>
			<br>
            <div class="jobs_form">
				<?php //do_action('show_harvest_form'); ?>
				<a href="https://boards.greenhouse.io/dynetherapeutics/jobs/<?php echo get_post_meta($post->ID, '_orig_job_id', true); ?>#app" target=”_blank” class="btn btn-primary btn-lg waves-effect waves-light">Apply for this job</a><br><br>
            </div>
			<?php
			$back_link = apply_filters('get_careers_back_link','');
			$next_link = apply_filters('get_careers_next_link','');

			$aTmp = array();
			if ($back_link) $aTmp[] = '<a href="'.$back_link.'">BACK</a>';
			if ($next_link) $aTmp[] = '<a href="'.$next_link.'">NEXT</a>';

			echo implode(" | ", $aTmp);
			?>
    </div>
</div>
<?php endwhile; ?>

<?php endif; ?>


<?php get_footer(); ?>
