<?php
/* Template Name: LifeAtKonato Template */
?>

<?php get_header(); ?>

<section>
    <div class="container">
        <div class="col-md-12 col-sm-12 pad-top">
            <div class="banner-placeholder">
                <?php
                    $url = wp_get_attachment_url( get_post_thumbnail_id($post->ID) );
                    echo '<div class="banner-placeholder"
                    style="
                    background: url('. $url.');
                    background-position: 100% 30%;
                    background-size: cover;">';
                    echo '</div>';
                ?>
            </div>
        </div>
    </div>
</section>

<section class="meet-the-team smal-pad-top smal-pad-bot testimonials ">
    <div class="container text-center">
	    <?php
	    $args = array( 'post_type' => 'Teamleden', 'posts_per_page' => 4 );
	    $loop = new WP_Query( $args );
        while ( $loop->have_posts() ) : $loop->the_post(); ?>
        <a target="_blank" href="<?php the_field('go_to_url'); ?> ">
            <div class="carousel-info col-md-3 col-sm-6 col-xs-12">
            <div class="pull-left">
                <?php
                    if ( has_post_thumbnail() ) { // nagaan of post een image heeft ander kan je best een placeholder zetten bv het logo
                        the_post_thumbnail( 'full', array( 'class'  => 'img-responsive' ) );
                    } // show featured image met responsive class 
                ?>
            </div>
            <div class="pull-left">
                <span class="testimonials-name text-align-left"><?php the_title(); ?></span>
                <span class="testimonials-post text-align-left"><?php the_content(); ?></span>
            </div>
        </div>
        </a>
	    <?php endwhile; ?>
	    <?php wp_reset_query(); ?>
    </div>
</section>

<section class="work-life-konato">
    <div class="container">
        <div class="row work-konato">
            <div class="col-md-5 col-sm-6 col-xs-12 lifeatkonato-img">
	            <?php

	            $image = get_field('work_konato_picture');

	            if( !empty($image) ): ?>

                    <img class=" img-thumbnail" src="<?php echo $image['url']; ?>" alt="<?php echo $image['alt']; ?>" />

	            <?php endif; ?>
            </div>
            <div class="col-md-7 col-sm-6 col-xs-12 konato-work-text">
                <h3 class="work-life-title"><?php the_field('work_at_konato_title'); ?></h3>
	            <p><?php the_field('work_at_konato_description'); ?></p>
                <a href="<?php the_field('work_at_konato_link'); ?>">
                    <button class="info home-info" style="margin-top: 10px">Our jobs</button>
                </a>
            </div>
        </div>
        <div class="row life-konato" class="margin-top: 10px">
            <div class="col-md-5 col-sm-6 col-xs-12 lifeatkonato-img">
	            <?php

	            $image = get_field('konato_life_image');

	            if( !empty($image) ): ?>

                    <img class=" img-thumbnail" src="<?php echo $image['url']; ?>" alt="<?php echo $image['alt']; ?>" />

                <?php endif; ?>                 
            </div>
            <div class="col-md-7 col-sm-6 col-xs-12 konato-life-text">
                <h3 class="work-life-title"><?php the_field('konato_life_title'); ?></h3>
                <p><?php the_field('konato_life_description_text'); ?></p>
                
                <div class="pull-left col-md-5 col-sm-6 col-xs-12" style="padding: 10px 0 0 0">
                <a href="https://www.facebook.com/konato.be/" target="_blank">
                    <button class="info home-info social-media-button">Follow us on Facebook</button>
                </a>
                </div>
                <div class="pull-left col-md-5 col-sm-6 col-xs-12" style="padding: 10px 0 0 0">
                <a href="https://www.linkedin.com/company/konato-nv" target="_blank">
                    <button class="info home-info social-media-button"> Follow us on Linkedin</button>
                </a>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container">
    <div class="row">
        <?php if (have_posts()) : ?>
                <?php while (have_posts()) : the_post(); ?>
                    <?php the_content(); ?>
                <?php endwhile; ?>
            <?php else : ?>
                <h2>Sorry</h2>
                <p>Deze pagina kon niet gevonden worden.</p>
        <?php endif; ?>
    </div>
</div>

<section class="testimonial smal-pad-top">
        <div class="container">
            <h2>See What <b>Our Employees</b> Say About Us</h2>
            <div class="row" style="padding-top: 30px">
            <?php
	        $args = array( 'post_type' => 'Testimonial', 'posts_per_page' => 2 );
	        $loop = new WP_Query( $args );
	        while ( $loop->have_posts() ) : $loop->the_post(); ?>
                <div class="col-md-6 col-sm-6 col-xs-12">
                    <div class="testimonials">
                        <div class="active item">
                            <blockquote><?php the_content(); ?></blockquote>
                            <div class="carousel-info">
                                <div class="pull-left">
	                                <?php the_post_thumbnail(); ?>
                                </div>
                                <div class="pull-left">
                                    <span class="testimonials-name"><?php the_title(); ?></span>
                                    <span class="testimonials-post"><?php the_field('post_user_role'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
	        <?php endwhile; ?>
	        <?php wp_reset_query(); ?>
            </div>
        </div>
</section>

<section class="jobs-konato pad-bottom">
    <div class="container">
        <div class="row pad-top">
            <div class="testimonial"><h2> Check our Latest Jobs</h2></div>
            <?php
            $args = array( 'post_type' => 'Job', 'posts_per_page' => 3 );
            $loop = new WP_Query( $args );
            while ( $loop->have_posts() ) : $loop->the_post(); ?>
                    <div class="jobs">
                        <div class="col-md-4">
                            <a href="<?php the_permalink(); ?>">
                                <h3 class="job-title smal-pad-top"><?php the_title(); ?></h2>
                                <?php
                                if ( has_post_thumbnail() ) { // nagaan of post een image heeft ander kan je best een placeholder zetten bv het logo
                                    the_post_thumbnail( 'full', array( 'class'  => 'img-responsive' ) ); // show featured image met responsive class
                                }
                                ?>
                                <div class="job-text">
                                </a>
                                <p><?php the_field('job_preview'); ?></p>
                        </div>
                    </div>
            <?php endwhile; ?>
            <?php wp_reset_query(); ?>
        </div>
    </div>
    <div class="col-md-4 smal-pad-top">   
        <a href="<?php the_field('work_at_konato_link'); ?>">   
            <button class="info home-info">All Vacancies</button>
        </a>  
    </div>
</section>

<section class="banner">
    <div class="container text-center">
        <div class="row">
            <div class="col-md-2"></div>
            <div class="col-md-8">
                <h1 class="blue"><?php the_field('hq_titel'); ?></h1>
                <p class="banner-text">
                <?php the_field('hq_tekst'); ?>
                </p>
                <div class="col-md-8 float-left" style="display:none">
                    <input placeholder="Your email..." type="text" name="" value=""><br>
                </div>
                <div class="col-md-4 col-md-offset-4 float-left mobile-pad-top">
                    <a href="<?php the_field('hq_button_link'); ?>">
                        <button class="info"><?php the_field('hq_button_tekst'); ?></button>
                    </a>
                </div>

            </div>
            <div class="col-md-2"></div>
        </div>
    </div>
</section>

<?php get_footer(); ?>
