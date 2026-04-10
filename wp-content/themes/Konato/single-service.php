<?php
/*
 * Template Name: Service template
 * Template Post Type: service
 */
?>
<?php get_header();  ?>
<?php while ( have_posts() ) : the_post(); ?>
<section class="service-content smal-pad-bot single-service mobile-nopad-bot">
	<div class="container no-pad-right no-pad-left" style="background-color: #f5f5f5">
		<?php
		$serviceTitel = get_field('service-text-top');
		if( !empty($serviceTitel)):?>
			<h1><?php the_field('service-text-top'); ?></h1>
        <?php endif; ?>
			<!-- <div class="row service-row-flex"> -->
					<div class="col-md-8 service-blok">
						<h1><?php the_title(); ?></h1>
						<h2><?php the_field('service_ondertitel'); ?></h2>
						<hr>
						<?php the_content(); ?>
					</div>
					<div class="col-md-4 single-service-arrow-right single-service-blok-color arrow-right arrow-right-even smal-pad-bot"
						style="background-color: <?php the_field('background_color'); ?>; outline:solid 40px <?php the_field('background_color');?> ">
						<?php $image = get_field('service_detail_image'); ?>
						<img class="img-responsive" src="<?php echo $image; ?>" alt="Service detail Image"/> 
					</div>
			<!-- </div> -->
	</div>
</section>

<div class="row">
	<div class="container">
		<div class="back-service-knop col-md-3 col-sm-6 col-xs-12 float-left mobile-pad-top smal-pad-bot">
    		<a href="<?php the_field('return_to_service'); ?>">
    			<button class="info">Back to our Services</button>
    		</a>
		</div>
	</div>
</div>
<?php endwhile; ?>
<?php get_footer();?>
