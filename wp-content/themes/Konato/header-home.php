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
    <header style="background-image: url(<?php the_field('background_image');?>);
			background-color: <?php the_field('background_color');?>;
			background-position: center center;
			background-size: cover;">
        <img style="position: absolute; width: 100%; height: 100%;" src="<?php the_post_thumbnail() ?>">
        <div id="hide-open" class="mobile-nav-bar desktop-hide">
            <div class="container">
                <a href="<?php echo get_home_url(); ?>">
                    <img class="konatologo-mobile"
                        src="<?php echo get_template_directory_uri(); ?>/img/logo-Konato.png" />
                </a>
                <span id="hamburger" class="hamburger">&#9776;</span>
            </div>
        </div>
        <div class="arrow-bottom-large mobile-hide" style="position: absolute; top: 0;">
            <a href="<?php echo get_home_url(); ?>" class="logo-link">
                <img class="konatologo" src="<?php echo get_template_directory_uri(); ?>/img/logo-Konato.png" />
            </a>
        </div>
        <div class="row">
            <div class="col-md-12 text-center">
                <span id="hamburger" class="hamburger mobile-hide">&#9776;</span>

                <h1 class="header-title">Empowering<br> your projects</h1>
                <p class="header-text">We guide your projects successfully <br>through the complete lifecycle.<br><br>
                <a href="/freelance-jobs">
						<button class="info white">Freelance Jobs</button>
					</a>&nbsp;&nbsp;
                    <a href="/jobs">
						<button class="info white">Permanent Jobs</button>
					</a>
            </p>
            </div>
        </div>

        <div class="mouse">
            <span class="scroll-btn">
                <a href="#content">
                    <span class="mouse">
                        <span></span>
                    </span>
                </a>
            </span>
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