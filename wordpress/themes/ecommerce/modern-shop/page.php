<?php get_header(); ?>
<main>
  <section class="hero">
    <h1><?php the_title(); ?></h1>
    <div class="content">
      <?php while (have_posts()): the_post(); ?>
        <?php the_content(); ?>
      <?php endwhile; ?>
    </div>
  </section>
</main>
<?php get_footer(); ?>
