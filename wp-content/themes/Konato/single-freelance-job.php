<?php
/*
 * Template Name: Freelance Job template
 * Template Post Type: freelance-job
 */
?>
<?php get_header('job');  ?>

<section class="content-jobs">
	<div class="container pad-bottom pad-top">
		<?php
	    if( have_posts() ):
		    while( have_posts() ): the_post() ; ?>

		<div class="row">
			<!-- <div class="col-md-6">
			    <?php
			    if ( has_post_thumbnail() ) { // nagaan of post een image heeft ander kan je best een placeholder zetten bv het logo
				    the_post_thumbnail( 'full', array( 'class'  => 'img-responsive' ) ); // show featured image met responsive class
			    }
			    ?>
            </div>-->
			<div class="col-md-12 ">
				<?php
	            $aboutTitel = get_field('about-us-titel');

	            if( !empty($aboutTitel)):?>
				<h1><?php the_field('about-us-titel'); ?></h1>
				<?php endif; ?>

				<?php
	            $aboutOnderTitel = get_field('about_us_ondertitel');

	            if( !empty($aboutOnderTitel)):?>
				<h2><?php the_field('about_us_ondertitel'); ?></h2>
				<?php endif; ?>

				<?php
	            $aboutTekst = get_field('about_tekstblok');

	            if( !empty($aboutTekst)):?>
				<p><?php the_field('about_tekstblok'); ?></p>
				<?php endif; ?>
				<div class="row">
					<div class="col-md-8">
						<div class="row single-job">
							<div class="col-sm-12">
								<h1 class="content-title"><?php the_title(); ?></h1>
								<h2><?php the_field('job_description_titel'); ?></h2>
								<?php the_content()?>
								<?php
									$posts = get_field('recruiter');
									if( $posts ): ?>
									<?php foreach( $posts as $post): // variable must be called $post (IMPORTANT) ?>	
								<h4>Do you have a question</h4>
								<p><?php the_field('voornaam'); ?> <?php the_field('achternaam'); ?> is happy to help you on <a href="tel:<?php the_field('telefoonnummer'); ?>"><?php the_field('telefoonnummer'); ?></a> or <a href="mailto:<?php the_field('e-mail'); ?>"><?php the_field('e-mail'); ?></a>.</p>
								<br>
								<?php endforeach; ?>
								<?php endif; ?>
								<?php wp_reset_query(); ?>

							</div>
						</div>
						
						<div class="row">
							<div class="col-sm-12">
								<h4>Follow us:</h4>
								<ul class="wp-container-social-1 wp-block-social-links is-style-logos-only">
									<li class="wp-social-link wp-social-link-facebook wp-block-social-link">
										<a href="https://www.facebook.com/konato.be"
											aria-label="Facebook: https://www.facebook.com/konato.be" rel="noopener nofollow"
											target="_blank" class="wp-block-social-link-anchor">
											<svg width="24" height="24" viewBox="0 0 24 24" version="1.1"
												xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true"
												focusable="false">
												<path
													d="M12 2C6.5 2 2 6.5 2 12c0 5 3.7 9.1 8.4 9.9v-7H7.9V12h2.5V9.8c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.5h-1.3c-1.2 0-1.6.8-1.6 1.6V12h2.8l-.4 2.9h-2.3v7C18.3 21.1 22 17 22 12c0-5.5-4.5-10-10-10z">
												</path>
											</svg>
										</a>
									</li>
									<li class="wp-social-link wp-social-link-linkedin wp-block-social-link">
										<a href="https://www.linkedin.com/company/konato-nv/"
											aria-label="LinkedIn: https://www.linkedin.com/company/konato-nv/"
											rel="noopener nofollow" target="_blank" class="wp-block-social-link-anchor">
											<svg width="24" height="24" viewBox="0 0 24 24" version="1.1"
												xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true"
												focusable="false">
												<path
													d="M19.7,3H4.3C3.582,3,3,3.582,3,4.3v15.4C3,20.418,3.582,21,4.3,21h15.4c0.718,0,1.3-0.582,1.3-1.3V4.3 C21,3.582,20.418,3,19.7,3z M8.339,18.338H5.667v-8.59h2.672V18.338z M7.004,8.574c-0.857,0-1.549-0.694-1.549-1.548 c0-0.855,0.691-1.548,1.549-1.548c0.854,0,1.547,0.694,1.547,1.548C8.551,7.881,7.858,8.574,7.004,8.574z M18.339,18.338h-2.669 v-4.177c0-0.996-0.017-2.278-1.387-2.278c-1.389,0-1.601,1.086-1.601,2.206v4.249h-2.667v-8.59h2.559v1.174h0.037 c0.356-0.675,1.227-1.387,2.526-1.387c2.703,0,3.203,1.779,3.203,4.092V18.338z">
												</path>
											</svg>
										</a>
									</li>
								</ul>
								<hr>
							</div>
							<div class="back-service-knop col-md-6 col-sm-6 col-xs-12 float-left mobile-pad-top smal-pad-top">
								<a href="/freelance-jobs">
									<button class="info outline">Back to our Freelance Jobs</button>
								</a>
							</div>
							<div class="back-service-knop col-md-6 col-sm-6 col-xs-12 float-left mobile-pad-top smal-pad-top">
								<a href="apply?jobid=<?php the_field('job_id'); ?>&jobtitle=<?php the_title(); ?>">
									<button class="info">Apply here</button>
								</a>
							</div>
						</div>
					</div>
					<div class="col-md-4">
						<div class="row">
							<div class="col-sm-12 ">
								<h3>Other vacancies</h3>
							</div>
						</div>

						<?php
						$args = array( 'post_type' => 'freelance-job', 'posts_per_page' => 25 );
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
			<?php  endwhile; endif; ?>
		</div>
</section>

<?php get_footer();?>