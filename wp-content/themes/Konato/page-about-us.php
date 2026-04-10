<?php
/* Template Name: About-us Template */
?>

<?php get_header(); ?>

<section class="about-content pad-bottom">
	<div class="container">
		<div class="row smal-pad-top">
			<h3><?php the_field('konato_co-pilot_title'); ?></h3>
			<?php the_field('konato_co-pilot_tekst_blok_boven'); ?>
			<?php $quote = get_field('konato_co-pilot_quote');
				if( !empty($quote) ):?>
              	<p class="fat-text text-center smal-pad-top "><?php the_field('konato_co-pilot_quote'); ?></p>
            <?php endif; ?>
            <?php $txt = get_field('konato_co-pilot_tekst_blok_onder');
				if( !empty($txt) ):?>
			<p class="smal-pad-top"><?php the_field('konato_co-pilot_tekst_blok_onder'); ?>
			</p>
			<?php endif; ?>
        </div>
        
        <div class="row service-row-flex" style="flex-direction: column;">

            <div class="row smal-pad-top">
                <div class="col-md-8 smal-pad-bot">
                    <h3><?php the_field('our_mission_title'); ?></h3>
                    <?php the_field('our_mission_tekst'); ?>
                </div>
                <div class="col-md-4">
                    <?php $image = get_field('our_mission_image'); ?>
                    <img class="img-responsive" src="<?php echo $image; ?>" alt="Mission Image"/>
                </div>
            </div>
            
            <div class="row smal-pad-top mobile-flex">
            <div class="col-md-4">
                    <?php $image = get_field('our_focus_image'); ?>
                    <img class="img-responsive" src="<?php echo $image; ?>" alt="Focus Image"/> 
                </div>    
                <div class="col-md-8 smal-pad-bot">
                    <h3><?php the_field('our_focus_title'); ?></h3>
                    <?php the_field('our_focus_tekst'); ?>  
                </div>           
            </div>

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
