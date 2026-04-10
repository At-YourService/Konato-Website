<?php
if(is_front_page())
{
	get_header('home');
}
else
{
	get_header();
}
?>
    <section id="content">
        <div class="row home-flex" style="background-color: whitesmoke;">
            <div class="text-center content-blok square">
                <div class="content">
                    <h1><?php the_field('titel_blok_1'); ?></h1>
                    <h2><?php the_field('ondertitel_blok_1'); ?></h2>
                    <p><?php the_field('tekst_blok_1'); ?></p>
                    <a href="<?php the_field('url_blok_1'); ?>"><button class="info home-info"><?php the_field('button_tekst_blok_1'); ?></button></a>
                </div>
            </div>
                <div class="content-blok blok-red arrow-left square image">
                    <?php $image2 = get_field('image_blok_2');

                    if( !empty($image2) ): 
                        // vars
                        $url2 = $image2['url'];
                        $title2 = $image2['title'];
                        $alt2 = $image2['alt'];
                        $caption2 = $image2['caption'];
                        // medium
                        $size2 = 'medium-small-square';
                        $thumb2 = $image2['sizes'][ $size2 ];
                        $width2 = $image2['sizes'][ $size2 . '-width' ];
                        $height2 = $image2['sizes'][ $size2 . '-height' ];
                    ?>
                        <div class="crop">
                        <a href="<?php the_field('url_blok_2'); ?>">
                            <img src="<?php echo $thumb2; ?>" alt="<?php echo $alt2; ?>" width="<?php echo $width2; ?>" height="<?php echo $height2; ?>" />
                        </a>
                        </div>
                    <?php endif; ?>
                </div>
            </a>
            <div class="text-center content-blok square">
                <div class="content">
                    <h1><?php the_field('titel_blok_2'); ?></h1>
                    <h2><?php the_field('ondertitel_blok_2'); ?></h2>
                    <p><?php the_field('tekst_blok_2'); ?></p>
                    <a href="<?php the_field('url_blok_2'); ?>"><button class="info home-info"><?php the_field('button_tekst_blok_2'); ?></button></a>
                </div>
            </div>
            <div class="content-blok blok-green arrow-bottom square image">
                <?php $image1 = get_field('image_blok_1');

                if( !empty($image1) ): 
                    // vars
                    $url1 = $image1['url'];
                    $title1 = $image1['title'];
                    $alt1 = $image1['alt'];
                    $caption1 = $image1['caption'];
                    // medium
                    $size1 = 'medium-small-square';
                    $thumb1 = $image1['sizes'][ $size1 ];
                    $width1 = $image1['sizes'][ $size1 . '-width' ];
                    $height1 = $image1['sizes'][ $size1 . '-height' ];
                ?>
                    <div class="crop">
                    <a href="<?php the_field('url_blok_1'); ?>">
                        <img src="<?php echo $thumb1; ?>" alt="<?php echo $alt1; ?>" width="<?php echo $width1; ?>" height="<?php echo $height1; ?>" />
                    </a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="text-center content-blok arrow-right square">
                <div class="content">
                    <h1><?php the_field('titel_blok_3'); ?></h1>
                    <h2><?php the_field('ondertitel_blok_3'); ?></h2>
                    <p><?php the_field('tekst_blok_3'); ?></p>
                    <a href="<?php the_field('url_blok_3'); ?>"><button class="info home-info"><?php the_field('button_tekst_blok_3'); ?></button></a>
                </div>
            </div>
            <div class="content-blok blok-darkblue arrow-right square image">
                <?php $image3 = get_field('image_blok_3');

                if( !empty($image3) ): 
                    // vars
                    $url3 = $image3['url'];
                    $title3 = $image3['title'];
                    $alt3 = $image3['alt'];
                    $caption3 = $image3['caption'];
                    // medium
                    $size3 = 'medium-small-square';
                    $thumb3 = $image3['sizes'][ $size3 ];
                    $width3 = $image3['sizes'][ $size3 . '-width' ];
                    $height3 = $image3['sizes'][ $size3 . '-height' ];
                ?>
                    <div class="crop">
                    <a href="<?php the_field('url_blok_3'); ?>">
                        <img src="<?php echo $thumb3; ?>" alt="<?php echo $alt3; ?>" width="<?php echo $width3; ?>" height="<?php echo $height3; ?>" />
                    </a>
                    </div>
                <?php endif; ?>
            </div>
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