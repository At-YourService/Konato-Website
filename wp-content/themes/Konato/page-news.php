<?php
/* Template Name: News Template */
?>

<?php get_header(); ?>


<section class="pad-bottom smal-pad-top">
    <div class="container">
        <div class="row">
            <?php
			$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
			$loop = new WP_Query( array(
					'posts_per_page' => 9,
					'paged'          => $paged )
			);
			if ( $loop->have_posts() ) : while ( $loop->have_posts() ) : $loop->the_post(); ?>
            <!-- rest of the loop -->
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
            <div class="col-md-12">
                <nav class="pagination">
                    <?php pagination_bar( $loop ); ?>
                </nav>
            </div>
            <?php wp_reset_postdata();
			endif; ?>
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