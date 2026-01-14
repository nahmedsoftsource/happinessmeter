<?php
/**
 * Front Page Template
 */
get_header();

// Get projects data for modal
$projects_data = developer_portfolio_get_projects_data();
?>

<main class="hero">
    <!-- Hero Video/Image -->
    <div class="hero-video">
        <?php
        $hero_media_id = get_theme_mod('hero_media');
        if ($hero_media_id) {
            $media_url = wp_get_attachment_url($hero_media_id);
            $media_type = get_post_mime_type($hero_media_id);

            if (strpos($media_type, 'video') !== false) {
                ?>
                <video id="heroVideo" autoplay muted loop playsinline>
                    <source src="<?php echo esc_url($media_url); ?>" type="<?php echo esc_attr($media_type); ?>">
                </video>
                <?php
            } else {
                ?>
                <img src="<?php echo esc_url($media_url); ?>" alt="Hero">
                <?php
            }
        } else {
            // Placeholder
            ?>
            <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);"></div>
            <?php
        }
        ?>
        <div class="video-overlay"></div>
    </div>

    <!-- Hero Content -->
    <div class="hero-content">
        <h1 class="hero-headline">
            <strong><?php echo esc_html(get_theme_mod('hero_headline', 'Product by day.')); ?></strong>
        </h1>
        <p class="hero-subline"><?php echo esc_html(get_theme_mod('hero_subline', 'Art by night.')); ?></p>

        <?php if (get_theme_mod('available_for_hire', true)): ?>
        <div class="status-badge">
            <span class="status-dot"></span>
            Available for hire
        </div>
        <?php endif; ?>

        <p class="hero-description">
            <?php echo esc_html(get_theme_mod('hero_description', 'I design products and craft 3D art. Currently building experiences at Company.')); ?>
        </p>

        <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="hire-btn">Get in touch</a>
    </div>
</main>

<!-- Portfolio Grid -->
<section class="portfolio-grid">
    <?php
    $projects = get_posts(array(
        'post_type'      => 'project',
        'posts_per_page' => -1,
        'orderby'        => 'meta_value_num',
        'meta_key'       => '_project_sort_order',
        'order'          => 'ASC',
    ));

    foreach ($projects as $project):
        $tags = wp_get_post_terms($project->ID, 'project_tag', array('fields' => 'names'));
    ?>
    <article class="portfolio-item" data-project="<?php echo esc_attr($project->ID); ?>">
        <div class="portfolio-image">
            <?php if (has_post_thumbnail($project->ID)): ?>
                <?php echo get_the_post_thumbnail($project->ID, 'portfolio-thumb'); ?>
            <?php else: ?>
                <div style="width: 100%; height: 100%; background: var(--bg-secondary);"></div>
            <?php endif; ?>
        </div>
        <?php if (!empty($tags)): ?>
        <div class="portfolio-tags">
            <?php foreach (array_slice($tags, 0, 2) as $tag): ?>
                <span class="tag"><?php echo esc_html($tag); ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </article>
    <?php endforeach; ?>
</section>

<!-- Skills Section -->
<section class="skills-section">
    <p class="skills-intro">
        I specialize in product design, branding, and 3D art. Looking for a designer to elevate your digital presence?
    </p>
    <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="hire-btn-inline">Let's work together</a>

    <div class="skills-grid">
        <div class="skill-card">
            <h3 class="skill-title">Product Design</h3>
            <p class="skill-description">End-to-end product design from research and wireframing to high-fidelity prototypes and design systems.</p>
        </div>
        <div class="skill-card">
            <h3 class="skill-title">3D & Motion</h3>
            <p class="skill-description">Creating immersive 3D visuals and motion graphics using Cinema 4D, Redshift, and After Effects.</p>
        </div>
        <div class="skill-card">
            <h3 class="skill-title">Branding</h3>
            <p class="skill-description">Developing cohesive brand identities including logos, color systems, typography, and brand guidelines.</p>
        </div>
        <div class="skill-card">
            <h3 class="skill-title">Development</h3>
            <p class="skill-description">Bringing designs to life with clean, responsive code using modern frameworks and best practices.</p>
        </div>
    </div>
</section>

<!-- Behance-style Project Modal -->
<div class="modal project-modal" id="projectModal">
    <div class="modal-overlay"></div>
    <div class="modal-container project-container">
        <div class="project-header">
            <div class="project-header-left">
                <h2 class="modal-title"></h2>
            </div>
            <button class="modal-close" aria-label="Close modal">&times;</button>
        </div>
        <div class="project-scroll-container">
            <div class="project-info">
                <p class="modal-description"></p>
                <div class="modal-tags"></div>
            </div>
            <div class="project-images" id="projectImages"></div>
        </div>
    </div>
</div>

<script>
// Projects data from WordPress
const projectsData = <?php echo json_encode($projects_data); ?>;
</script>

<?php get_footer(); ?>
