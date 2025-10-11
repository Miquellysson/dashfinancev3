<?php get_header(); ?>
<main>
  <section class="archive">
    <h1><?php the_archive_title(); ?></h1>
    <?php if (have_posts()): ?>
      <div class="grid">
        <?php while (have_posts()): the_post(); ?>
          <article>
            <a href="<?php the_permalink(); ?>">
              <h2><?php the_title(); ?></h2>
              <p><?php the_excerpt(); ?></p>
            </a>
          </article>
        <?php endwhile; ?>
      </div>
      <?php the_posts_pagination(); ?>
    <?php else: ?>
      <p>Sem conteúdo.</p>
    <?php endif; ?>
  </section>
</main>
<?php get_footer(); ?>
