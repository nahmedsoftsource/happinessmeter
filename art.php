<?php
/**
 * Art Gallery Page
 */
require_once 'config/config.php';

// Fetch all published art
try {
    $stmt = db()->query("
        SELECT * FROM art_gallery
        WHERE status = 'published'
        ORDER BY sort_order ASC
    ");
    $artworks = $stmt->fetchAll();
} catch (PDOException $e) {
    $artworks = [];
}

// Get unique categories
$categories = array_unique(array_column($artworks, 'category'));

// Page settings
$pageTitle = 'Art - ' . getSetting('site_name');
$bodyClass = 'art-page';
$extraCSS = ['art'];
$extraJS = ['art'];

include 'includes/header.php';
?>

    <!-- Art Hero -->
    <section class="art-hero">
        <h1 class="art-title">Art & Personal Work</h1>
        <p class="art-description">
            A collection of personal artwork and experimental pieces created using Cinema 4D, Redshift, and various other tools. These works explore themes of light, space, and digital abstraction.
        </p>

        <!-- Filter Tags -->
        <div class="art-filters">
            <button class="filter-btn active" data-filter="all">All</button>
            <button class="filter-btn" data-filter="3d">3D Art</button>
            <button class="filter-btn" data-filter="typography">Typography</button>
            <button class="filter-btn" data-filter="abstract">Abstract</button>
            <button class="filter-btn" data-filter="experimental">Experimental</button>
        </div>
    </section>

    <!-- Art Gallery Grid -->
    <section class="art-gallery">
        <div class="gallery-grid">
            <?php foreach ($artworks as $index => $art): ?>
            <div class="art-item<?php echo $art['is_large'] ? ' large' : ''; ?>"
                 data-category="<?php echo e($art['category']); ?>"
                 data-index="<?php echo $index; ?>">
                <div class="art-image">
                    <?php if ($art['image_path']): ?>
                    <img src="<?php echo e($art['image_path']); ?>"
                         alt="<?php echo e($art['title']); ?>"
                         loading="<?php echo $index < 3 ? 'eager' : 'lazy'; ?>">
                    <?php else: ?>
                    <div class="placeholder-image"></div>
                    <?php endif; ?>
                </div>
                <div class="art-info">
                    <h3><?php echo e($art['title']); ?></h3>
                    <span class="art-category"><?php echo e(ucfirst($art['category'])); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Art Lightbox Modal (Single Image Only) -->
    <div class="lightbox" id="artLightbox">
        <div class="lightbox-overlay"></div>
        <div class="lightbox-container">
            <button class="lightbox-close" aria-label="Close">&times;</button>
            <div class="lightbox-content">
                <img src="" alt="" class="lightbox-image">
            </div>
        </div>
    </div>

    <script>
    // Art data from PHP for lightbox
    const artData = [
        <?php foreach ($artworks as $art): ?>
        {
            src: <?php echo json_encode($art['image_path']); ?>,
            title: <?php echo json_encode($art['title']); ?>,
            category: <?php echo json_encode(ucfirst($art['category'])); ?>
        },
        <?php endforeach; ?>
    ];
    </script>

<?php include 'includes/footer.php'; ?>
