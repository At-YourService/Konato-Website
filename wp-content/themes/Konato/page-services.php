<?php
/* Template Name: Services Template */
?>
<?php get_header(); ?>

	<section class="service-content pad-bottom">
		<div class="container no-pad-right no-pad-left" style="background-color: #f5f5f5">
			<?php
			$aboutTitel = get_field('service-text-top');

			if( !empty($abouxtTitel)):?>
                <h1><?php the_field('service-text-top'); ?></h1>
			<?php endif; ?>

			<?php
			$args = array( 'post_type' => 'Service', 'posts_per_page' => 9 );
			$loop = new WP_Query( $args );
			while ( $loop->have_posts() ) : $loop->the_post(); ?>
                <div class="row service-row-flex">
                    <div class="col-md-8 col-sm-6 col-xs-12 service-blok ">
                        <h1><?php the_title(); ?></h1>
                        <h2><?php the_field('service_ondertitel'); ?></h2>
                        <p><?php the_field('service_intro'); ?></p>
                        <div class="service-readmore">
                            <a href="<?php the_permalink(); ?>">
                                <button class="info home-info">Read More</button>
                            </a>
                        </div>
                    </div>
                    <div style="background-color: <?php the_field('background_color'); ?>; outline:solid 80px <?php the_field('background_color');?> "
                     class="col-md-4 col-sm-6 col-xs-12 service-blok-color arrow-right arrow-right-even square image">
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
    
    <section class="actions">
        <div class="col-md-6 col-sm-12 halfsize-blok-yellow">
            <div class="wrap">
                <div class="imgcrop">
                    <img src='<?php the_field('gele_blok_triangle_image'); ?>'>
                </div>
            </div>
            <div class="action-text pull-right" style="padding-right: 50px">
                <h1 class="black"><?php the_field('gele_blok_titel'); ?></h1>
                <a href="<?php the_field('gele_blok_link'); ?>">
                    <p><?php the_field('gele_blok_tekst'); ?></p>
                </a>

                <a href="<?php the_field('gele_blok_link'); ?>">
                    <div style="border: solid 2px black; border-radius: 50%; width: 60px; height: 60px; margin-top: 20px">
                        <i class="fa fa-caret-right fa-2x" style="padding: 13px 0 0 24px;"></i>
                    </div>
                </a>
            </div>
        </div>

        <div class="col-md-6 col-sm-12 halfsize-blok-blue">
        <div class="wrap-two">
                <div class="imgcrop-two">
                    <img src='<?php the_field('blauwe_blok_triangle_image'); ?>'>
                </div>
            </div>           
             <div class="action-text" style="padding-left: 50px">
                <h1 class="white"><?php the_field('blauwe_blok_titel'); ?></h1>
                <a href="<?php the_field('blauwe_blok_link'); ?>" style="color:white;">
                    <p class="white"><?php the_field('blauwe_blok_tekst'); ?></p>
                 </a>
                <a href="<?php the_field('blauwe_blok_link'); ?>">
                    <div style="border: solid 2px white; border-radius: 50%; width: 60px; height: 60px; margin-top:20px">
                        <i class="fa fa-newspaper" style="padding: 19px 0 0 20px; color: white;"></i>
                    </div>
                </a>
            </div>
        </div>
</section>
<?php get_footer(); ?>