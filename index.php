<?php
/**
 * Home Page - Projects Portfolio
 */
require_once 'config/config.php';

// Fetch featured projects
try {
    $stmt = db()->prepare("
        SELECT p.*,
               (SELECT GROUP_CONCAT(pi.image_path ORDER BY pi.sort_order)
                FROM project_images pi WHERE pi.project_id = p.id) as gallery_images
        FROM projects p
        WHERE p.is_featured = 1 AND p.status != 'draft'
        ORDER BY p.sort_order ASC
    ");
    $stmt->execute();
    $projects = $stmt->fetchAll();
} catch (PDOException $e) {
    $projects = [];
}

// Fetch skills
try {
    $stmt = db()->query("SELECT * FROM skills WHERE is_active = 1 ORDER BY sort_order ASC");
    $skills = $stmt->fetchAll();
} catch (PDOException $e) {
    $skills = [];
}

// Page settings
$pageTitle = getSetting('site_name') . ' - Product Designer & Artist';

include 'includes/header.php';
?>

    <!-- Hero Section -->
    <section class="hero">
        <!-- Full Width Video -->
        <div class="hero-video">
            <video id="heroVideo" autoplay loop muted playsinline>
                <source src="portfolio_video.mp4" type="video/mp4">
            </video>
            <div class="video-overlay"></div>
        </div>

        <div class="hero-content">
            <h1 class="hero-headline"><strong><?php echo e(getSetting('hero_headline', 'Product')); ?></strong> by day.</h1>
            <h2 class="hero-subline"><?php echo e(getSetting('hero_subline', 'Art by night.')); ?></h2>

            <?php if (getSetting('available_for_hire', '1') === '1'): ?>
            <div class="status-badge">
                <span class="status-dot"></span>
                Available for hire
            </div>
            <?php endif; ?>

            <p class="hero-description">
                <?php echo e(getSetting('hero_description', 'Product designer and artist working independently from Redding, CA.')); ?>
            </p>

            <a href="contact.html" class="hire-btn">Hire me</a>
        </div>
    </section>

    <!-- Portfolio Grid -->
    <div class="portfolio-grid" id="projects">
        <?php foreach ($projects as $index => $project):
            $tags = json_decode($project['tags'] ?? '[]', true) ?: [];
            $galleryImages = $project['gallery_images'] ? explode(',', $project['gallery_images']) : [];
            if ($project['featured_image']) {
                array_unshift($galleryImages, $project['featured_image']);
            }
        ?>
        <div class="portfolio-item"
             data-project="<?php echo $project['id']; ?>"
             data-title="<?php echo e($project['title']); ?>"
             data-description="<?php echo e($project['description']); ?>"
             data-tags='<?php echo json_encode($tags); ?>'
             data-images='<?php echo json_encode(array_unique($galleryImages)); ?>'>
            <div class="portfolio-image">
                <div class="portfolio-tags">
                    <span class="tag">Case Study</span>
                    <?php if ($project['status'] === 'coming_soon'): ?>
                    <span class="tag coming-soon">Coming Soon</span>
                    <?php endif; ?>
                </div>
                <?php if ($project['featured_image']): ?>
                <img src="<?php echo e($project['featured_image']); ?>"
                     alt="<?php echo e($project['title']); ?>"
                     loading="<?php echo $index < 2 ? 'eager' : 'lazy'; ?>">
                <?php else: ?>
                <div class="placeholder-image"></div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Skills Section -->
    <section class="skills-section" id="info">
        <p class="skills-intro">
            I'm a design generalist so my skills are varied, but they fall into four main categories:
        </p>

        <a href="contact.html" class="hire-btn-inline">Hire me</a>

        <div class="skills-grid">
            <?php foreach ($skills as $skill): ?>
            <div class="skill-card">
                <h3 class="skill-title"><?php echo e($skill['title']); ?></h3>
                <p class="skill-description">
                    <?php echo e($skill['description']); ?>
                </p>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Project Modal -->
    <div class="modal" id="projectModal">
        <div class="modal-overlay"></div>
        <div class="modal-container">
            <button class="modal-close" aria-label="Close modal">&times;</button>

            <!-- Carousel -->
            <div class="carousel">
                <button class="carousel-btn carousel-prev" aria-label="Previous image">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </button>

                <div class="carousel-track-container">
                    <div class="carousel-track">
                        <!-- Images will be injected here -->
                    </div>
                </div>

                <button class="carousel-btn carousel-next" aria-label="Next image">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>
            </div>

            <!-- Carousel Indicators -->
            <div class="carousel-indicators"></div>

            <!-- Project Info -->
            <div class="modal-content">
                <h2 class="modal-title"></h2>
                <p class="modal-description"></p>
                <div class="modal-tags"></div>
            </div>
        </div>
    </div>

    <script>
    // Project data from PHP for modal
    const projectsData = {};
    <?php foreach ($projects as $project):
        $tags = json_decode($project['tags'] ?? '[]', true) ?: [];
        $galleryImages = $project['gallery_images'] ? explode(',', $project['gallery_images']) : [];
        if ($project['featured_image']) {
            array_unshift($galleryImages, $project['featured_image']);
        }
        $images = array_map(function($img) use ($project) {
            return ['src' => $img, 'alt' => $project['title']];
        }, array_unique($galleryImages));
    ?>
    projectsData[<?php echo $project['id']; ?>] = {
        title: <?php echo json_encode($project['title']); ?>,
        description: <?php echo json_encode($project['description']); ?>,
        tags: <?php echo json_encode($tags); ?>,
        images: <?php echo json_encode(array_values($images)); ?>
    };
    <?php endforeach; ?>
    </script>

<?php include 'includes/footer.php'; ?>
