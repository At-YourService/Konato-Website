<?php
/* Template Name: Contact Template */
?>

<?php get_header(); ?>

	<section class="contact-content">
		<div class="container pad-top pad-bottom">
			<div class="row">
				<div class="col-md-6 pad-bottom">
					<h1>GET IN <br> TOUCH <br> WITH US</h1>
					<div class="smal-pad-top">
						<i class="contact-icon fa fa-home blue float-left"></i><p>Veldkant 33A, B2550 Kontich</p>
						<i class="contact-icon fa fa-envelope blue float-left"></i><p><a href="mailto:info@konato.be">info@konato.be</a></p>
					</div>
				</div>
				<div class="col-md-6">
					<?php if (have_posts()) : ?>
            			<?php while (have_posts()) : the_post(); ?>
                		<?php the_content(); ?>
            		<?php endwhile; ?>
        			<?php else : ?>
            			<h2>Sorry</h2>
            			<p>Deze pagina kon niet gevonden worden.</p>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</section>
	
	<?php get_footer(); ?>