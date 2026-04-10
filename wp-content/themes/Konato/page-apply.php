<?php
/* Template Name: Jobs Template */
?>

<?php get_header('job'); ?>

<section class="apply-content">
		<div class="container pad-top pad-bottom">
			<?php
				$aboutTitel = get_field('service-text-top');

				if( !empty($aboutTitel)):?>
			<h1><?php the_field('service-text-top'); ?></h1>
			<?php endif; ?>
			<div class="row">
			<div class="col-md-8">
				    <h2>Apply here for <?php if(isset($_GET['jobtitle'])) { echo($_GET['jobtitle']); } ?></h2>
					<p class="title-font">Yes, I want this job and I fill in the form below!</p>
					<?php if (have_posts()) : ?>
            			<?php while (have_posts()) : the_post(); ?>
                		<?php the_content(); ?>
            		<?php endwhile; ?>
        			<?php else : ?>
            			<h2>Sorry</h2>
            			<p>Deze pagina kon niet gevonden worden.</p>
					<?php endif; ?>
				</div>
				<div class="col-md-4">
						<div class="row">
							<div class="col-sm-12 ">
								<h3>Other vacancies</h3>
							</div>
						</div>

						<?php
						$args = array( 'post_type' => 'freelance-job', 'posts_per_page' => 5 );
						$loop = new WP_Query( $args );
						while ( $loop->have_posts() ) : $loop->the_post(); ?>
						<div class="row">
							<div class="col-sm-12 ">
								<a class="small-job-block" href="<?php the_permalink(); ?>">
									<h4><?php the_title(); ?></h4>
									<p><?php the_field('city'); ?></p>
								</a>
							</div>
						</div>

						<?php endwhile; ?>
						<?php wp_reset_query(); ?>
					</div>
			</div>
		</div>
	</section>


<?php get_footer(); ?>