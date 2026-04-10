<?php
/* Template Name: Legal Template */
?>
<?php get_header(); ?>


<div class="container smal-pad-top legal smal-pad-bot legal">
    <div class="row col-md-12">
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

<?php get_footer(); ?>
