<?php
/**
 * Default Page Template
 */
get_header();
?>

<main class="page-content" style="padding-top: 100px; min-height: 80vh;">
    <div style="max-width: 1400px; margin: 0 auto; padding: 2rem 2.5rem;">
        <?php while (have_posts()): the_post(); ?>
            <article id="page-<?php the_ID(); ?>" <?php post_class(); ?>>
                <h1 class="page-title" style="font-size: 3rem; font-weight: 600; margin-bottom: 2rem; letter-spacing: -0.03em;">
                    <?php the_title(); ?>
                </h1>
                <div class="entry-content" style="font-size: 1.1rem; line-height: 1.8; color: var(--text-secondary);">
                    <?php the_content(); ?>
                </div>
            </article>
        <?php endwhile; ?>
    </div>
</main>

<?php get_footer(); ?>
