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
	
	<!-- Google analytics -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=G-2DW8ZNZX2V"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'G-2DW8ZNZX2V');
    </script>

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
                <h1 class="small-header-title">&nbsp;</h1>
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