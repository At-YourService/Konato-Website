<!DOCTYPE html>
<html>

<head>
    <meta id="viewport" name="viewport"
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">

    <meta charset="UTF-8">
    <title>Konato</title>

    <!--  FONTS -->
    <link href="https://fonts.googleapis.com/css?family=Lato:300,400,700" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600" rel="stylesheet">

    <!-- Hier komen de Stylesheet tags-->
    <?php wp_head(); ?>

</head>

<body style="background-color: white !important;">

    <header class="small-header">

        <div id="hide-open" class="mobile-nav-bar desktop-hide">
            <div class="container">
                <a href="<?php echo get_home_url(); ?>">
                    <img class="konatologo-mobile"
                        src="<?php echo get_template_directory_uri(); ?>/img/logo-Konato.png" />
                </a>
                <span id="hamburger" class="hamburger">&#9776;</span>
            </div>
        </div>
        <div class="arrow-bottom-large mobile-hide">
            <a href="<?php echo get_home_url(); ?>" class="logo-link">
                <img class="konatologo" src="<?php echo get_template_directory_uri(); ?>/img/logo-Konato.png" />
            </a>
        </div>
        <div class="row">
            <div class="text-center">
                <span id="hamburger" class="hamburger mobile-blue">&#9776;</span>
                <h1 class="small-header-title">News</h1>
            </div>
        </div>

        <div id="myNav" class="overlay">
            <a href="javascript:void(0)" class="closebtn">&times;</a>
            <div class="overlay-content">
                <?php
			wp_nav_menu(array(
					'theme_location' => 'primary',
					'container' => false,
					'menu_class' => ''
				)
			);
			?>
            </div>
        </div>

    </header>

    <section class="pad-bottom smal-pad-top">
        <div class="container">
            <div class="row">
                <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
                <div class="row">
                    <div class="col-md-12 smal-pad-top">
                        <div class="banner-placeholder">
                            <?php
                        $url = wp_get_attachment_url( get_post_thumbnail_id($post->ID) );
                        echo '<div class="banner-placeholder"
                        style="
                        background: url('. $url.');
                        background-position: center center;
    					background-size: contain;
    					background-repeat: no-repeat;
    					background-color: #00ADDA;">';
                        echo '</div>';
                    ?>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <h1 class="smal-pad-top"><?php the_title();?></h1>
                        <hr>
                        <div class="card-body">
                            <?php the_content(); ?>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pad-b-10"
                            style="margin-top:25px;">
                            <div class="pull-left">
                                <small class="text-muted"><i class="far fa-clock"
                                        style="margin-right: 5px"></i><?php the_time('j F Y'); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; endif;?>
                <?php wp_reset_query(); ?>
            </div>
            <hr>
            <section class="testimonial smal-pad-top">
                <div class="row">
                    <h2>Check out <b>our lastest</b> news posts </h2>
                    <?php
                    $args = array( 'post_type' => 'post', 'posts_per_page' => 3 );
                    $loop = new WP_Query( $args );
                    while ( $loop->have_posts() ) : $loop->the_post(); ?>
                    <div class="col-md-4 col-sm-6 col-sm-12 smal-pad-top">
                        <div class="news-img">
                            <?php
                            if ( has_post_thumbnail() ) { // nagaan of post een image heeft ander kan je best een placeholder zetten bv het logo
                                 the_post_thumbnail( 'medium', array( 'class'  => 'card-img-top ' )  ); // show featured image met responsive class
                            }
                        ?>
                        </div>
                        <div class="card-body grey">
                            <h3 class="blog-title"><?php the_title();?></h3>
                            <div class="ellipsis">
                                <?php  echo '<p >' . get_the_excerpt() . '</p>' ?>
                            </div>

                            <hr>
                            <div class="d-flex justify-content-between align-items-center pad-b-10">
                                <small class="text-muted overal-pad"><i class="far fa-clock"
                                        style="margin-right: 5px"></i><?php the_time('j F Y'); ?>
                                    <a href="<?php the_permalink(); ?>">
                                        <p class="pull-right" style="font-size:12px; margin: 2px 10px 0 0">Read more</p>
                                    </a>
                                </small>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    <?php wp_reset_query(); ?>
                </div>
            </section>

            <div class="back-service-knop col-md-6 col-sm-6 col-xs-12 float-left mobile-pad-top smal-pad-top"
                style="padding-left: 0">
                <a href="<?php the_field('back_to_news'); ?>">
                    <button class="info">Back to our News</button>
                </a>
            </div>
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
                    <div
                        style="border: solid 2px black; border-radius: 50%; width: 60px; height: 60px; margin-top: 20px">
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
                    <div
                        style="border: solid 2px white; border-radius: 50%; width: 60px; height: 60px; margin-top:20px">
                        <i class="fa fa-newspaper" style="padding: 19px 0 0 20px; color: white;"></i>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <?php get_footer(); ?>