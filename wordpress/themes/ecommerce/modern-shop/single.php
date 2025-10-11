<?php get_header(); ?>
<main>
  <article class="post">
    <h1><?php the_title(); ?></h1>
    <div class="meta">Publicado em <?php the_time('d/m/Y'); ?></div>
    <div class="content"><?php the_content(); ?></div>
  </article>
</main>
<?php get_footer(); ?>
