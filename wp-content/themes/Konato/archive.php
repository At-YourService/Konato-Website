
<?php get_header(); ?>

<?php the_content(); ?>

	<section class="pad-bottom smal-pad-top">
		<div class="container">
			<div class="row">
			<?php
			$args = array( 'post_type' => 'post', 'posts_per_page' => 9 );
			$loop = new WP_Query( $args );
			while ( $loop->have_posts() ) : $loop->the_post(); ?>
			<div class="col-md-4 smal-pad-top">
				<div class="card mb-4 box-shadow">
						<img class="card-img-top" alt="" style="height: 225px; width: 100%; display: block;" src="<?php the_post_thumbnail() ?>">
						<div class="card-body grey">
						<h3 class="blog-title"><?php the_title();?></h3>
						<p class="card-text overal-pad"><?php the_content(); ?></p>
						<div class="d-flex justify-content-between align-items-center pad-b-10" >
							<small class="text-muted overal-pad"><i class="far fa-clock" style="margin-right: 5px"></i><?php the_time('j F Y'); ?></small>
						</div>
					</div>
				</div>
			</div>
			<?php endwhile; ?>
			<?php wp_reset_query(); ?>
			</div>
		</div>
	</section>
	
<?php get_footer(); ?>