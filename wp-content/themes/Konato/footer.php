<footer>
    <div class="container">
        <div class="row top-footer">
            <div class="col-md-4 col-sm-12">
                <a href="<?php echo get_home_url(); ?>">
                    <img src="<?php echo get_template_directory_uri(); ?>/img/logo-Konato-white.png" style="height: 42px; width: 130px"/>
                </a>
                <div class="bottom-footer">
                    <p>Konato is a co-pilot you can rely on to manage your projects. We are supportive and focused on
                        getting the job done.</p>
                    <p class="small">&copy; <?php echo date("Y") ?> KONATO NV</p>
                </div>
            </div>
            <div class="col-md-4 col-sm-12 foot-pad-left">
                <h3>Navigation</h3>
                <div class="bottom-footer">
                    <ul>
                        <?php
                        wp_nav_menu(array(
                                'theme_location' => 'secondary',
                                'container' => false,
                                'menu_class' => ''
                            )
                        );
                        ?>
                    </ul>
                </div>
            </div>
            <div class="col-md-4 col-sm-12 foot-pad-left">
                <h3>Office hours</h3>
                <?php if ( is_active_sidebar( 'office_hours' ) ) : ?>
	                <div id="primary-sidebar" class="bottom-footer widget-area" role="complementary">
		            <?php dynamic_sidebar( 'office_hours' ); ?>
                <?php endif; ?>
                    <!-- <a href="#">
                        <button class="info">Contact us</button>
                    </a> -->
                </div>
            </div>
        </div>
        <div class="row bottom-footer">
            <div class="col-md-4"></div>
            <div class="col-md-4 foot-pad-left"></div>
            <div class="col-md-4 foot-pad-left"></div>
        </div>
    </div>
</footer>

<div class="link-footer">
    <div class="container">
        <div class="copyright-rights float-left">
            <p class="small">&copy; <?php echo date("Y") ?> all rights reserved | KONATO NV</p>
        </div>
        <div class="links float-right">
        <?php
			wp_nav_menu(array(
					'theme_location' => 'tertiary',
					'container' => false,
					'menu_class' => ''
				)
			);
			?>
        </div>
    </div>
</div>

<!-- Hier komen de javascript tags-->
<?php wp_footer(); ?>

</body>
</html>