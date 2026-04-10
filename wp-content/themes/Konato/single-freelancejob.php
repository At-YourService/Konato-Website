<?php
/*
 * Template Name: Freelance Job template
 * Template Post Type: freelance-job
 */
?>
<?php get_header();  ?>

<section class="content-jobs">
    <div class="container pad-bottom pad-top">
	    <?php
	    if( have_posts() ):
		    while( have_posts() ): the_post() ; ?>

        <div class="row">
            <div class="col-md-6">
			    <?php
			    if ( has_post_thumbnail() ) { // nagaan of post een image heeft ander kan je best een placeholder zetten bv het logo
				    the_post_thumbnail( 'full', array( 'class'  => 'img-responsive' ) ); // show featured image met responsive class
			    }
			    ?>
            </div>
            <div class="col-md-6 single-job">
	            <?php
	            $aboutTitel = get_field('about-us-titel');

	            if( !empty($aboutTitel)):?>
                    <h1><?php the_field('about-us-titel'); ?></h1>
	            <?php endif; ?>

	            <?php
	            $aboutOnderTitel = get_field('about_us_ondertitel');

	            if( !empty($aboutOnderTitel)):?>
                    <h2><?php the_field('about_us_ondertitel'); ?></h2>
	            <?php endif; ?>

	            <?php
	            $aboutTekst = get_field('about_tekstblok');

	            if( !empty($aboutTekst)):?>
                    <p><?php the_field('about_tekstblok'); ?></p>
	            <?php endif; ?>

                <h2><?php the_field('job_description_titel'); ?></h2>
				<?php the_content()?>
				<hr>
				<h5><?php the_field('contact_info'); ?></h5>
				<div class="back-service-knop col-md-6 col-sm-6 col-xs-12 float-left mobile-pad-top smal-pad-top" style="padding-left: 0">
					<a href="<?php the_field('return-to-page'); ?>">
						<button class="info">Back to our Jobs</button>
					</a>
				</div>
				<div class="back-service-knop col-md-6 col-sm-6 col-xs-12 float-right mobile-pad-top smal-pad-top" style="padding-left: 0">
					<a href="<?php the_field('go_to_page'); ?>">
						<button class="info">Apply now!</button>
					</a>
				</div>
			</div>
			

		    <?php  endwhile; endif; ?>
    </div>
</section>

<?php get_footer();?>
