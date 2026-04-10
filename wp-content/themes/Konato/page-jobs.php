<?php
/* Template Name: Jobs Template */
?>

<?php get_header(); ?>

	<section class="service-content pad-bottom">
		<div class="container no-pad-right no-pad-left" style="background-color: #f5f5f5">
			<?php
			$aboutTitel = get_field('service-text-top');

			if( !empty($aboutTitel)):?>
                <h1><?php the_field('service-text-top'); ?></h1>
			<?php endif; ?>

			<?php
			$args = array( 'post_type' => 'Job', 'posts_per_page' => 9 );
			$loop = new WP_Query( $args );
			while ( $loop->have_posts() ) : $loop->the_post(); ?>
                <div class="row service-row-flex">
                    <div class="col-md-8  col-sm-6 service-blok">
                        <h1><?php the_title(); ?></h1>
                        <!-- <h2><?php the_field(''); ?></h2> -->
                        <p><?php the_field('job_preview'); ?></p>
                        <div class="service-readmore">
                            <a href="<?php the_permalink(); ?>">
                                <button class="info home-info">Read More</button>
                            </a>
                        </div>
                    </div>
                    <div style="background-color: <?php the_field('background-color-job	'); ?>; outline:solid 80px <?php the_field('background-color-job');?> " class="col-md-4 col-sm-6 service-blok-color arrow-right arrow-right-even square image">
                    	<div class="crop">
						<a href="<?php the_permalink(); ?>">
		                    <?php
		                    if ( has_post_thumbnail() ) { // nagaan of post een image heeft ander kan je best een placeholder zetten bv het logo
			                    the_post_thumbnail( 'thumbnail', array( 'class'  => 'img-responsive' ) ); // show featured image met responsive class
		                    }
							?>
							</a>
		                </div>
                    </div>
                </div>
			<?php endwhile; ?>
			<?php wp_reset_query(); ?>
		</div>
	</section>

<?php get_footer(); ?>
