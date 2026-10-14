<?php
global $post;

$post_image = JazzPharmaTheme2::getHeaderImageURL();
$shortcode = 'wb_our_stories';
 ?>

    <section class="page-title type2 clearfix bg-greyLight">
        <div class="container">
            <?php  JazzPharmaTheme2::showPageBreadCrumbs(); ?>
            <h4>Company Announcements</h4>
        </div>
    </section>

    <section class="content type2">
        <div class="container">
            <div class="md-flex-container">
                <div class="col-side">
                    <?php JazzPharmaTheme2::showSubmenu(JazzPharmaTheme2::getShortcodePageID($shortcode)); ?>
                    <?php //echo apply_filters('the_content',$right_content); ?>
                </div>

                <div class="col-main">
                    <div class="st-article">
                        <h1><?php the_title(); ?></h1>
                        <div class="st-art-pic"><div class="pic-container" style="background-image: url('<?php echo $post_image; ?>');"></div></div>
                        <div class="st-art-text"><?php the_content(); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php /*?>

<section class="content">
	<div class="container">
		<div class="page-title type1 clearfix">
			<?php JazzPharmaTheme2::showPageBreadCrumbs(); ?>
			<h1><?php echo $subtitle;
			JazzPharmaTheme2::showTitle(); ?></h1>
		</div>
	</div>
	<div class="container">
		<div class="md-flex-container">
			<div class="col-side">
				<?php //JazzPharmaTheme2::showSubmenu(); ?>
				<?php echo apply_filters('the_content',$right_content); ?>
			</div>

			<div class="col-main">
				<?php the_content(); ?>
			</div>
		</div>
	</div>
</section>
<?php */ ?>