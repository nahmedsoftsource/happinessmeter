<?php
/**
 * Main Index Template (fallback)
 */
get_header();
?>

<main class="main-content" style="padding-top: 100px; min-height: 80vh;">
    <div style="max-width: 1400px; margin: 0 auto; padding: 2rem 2.5rem;">
        <?php if (have_posts()): ?>
            <?php while (have_posts()): the_post(); ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                    <h1><?php the_title(); ?></h1>
                    <div class="entry-content">
                        <?php the_content(); ?>
                    </div>
                </article>
            <?php endwhile; ?>
        <?php else: ?>
            <p><?php _e('No content found.', 'developer-portfolio'); ?></p>
        <?php endif; ?>
    </div>
</main>

<?php get_footer(); ?>
