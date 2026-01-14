<?php
/**
 * Template Name: Art Gallery
 */
get_header();

// Get art categories
$art_categories = get_terms(array(
    'taxonomy'   => 'art_category',
    'hide_empty' => true,
));

// Get all artworks
$artworks = get_posts(array(
    'post_type'      => 'art',
    'posts_per_page' => -1,
    'orderby'        => 'meta_value_num',
    'meta_key'       => '_art_sort_order',
    'order'          => 'ASC',
));
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
        <?php foreach ($art_categories as $category): ?>
        <button class="filter-btn" data-filter="<?php echo esc_attr($category->slug); ?>">
            <?php echo esc_html($category->name); ?>
        </button>
        <?php endforeach; ?>
    </div>
</section>

<!-- Art Gallery Grid -->
<section class="art-gallery">
    <div class="gallery-grid">
        <?php foreach ($artworks as $index => $art):
            $is_large = get_post_meta($art->ID, '_art_is_large', true);
            $categories = wp_get_post_terms($art->ID, 'art_category', array('fields' => 'slugs'));
            $category = !empty($categories) ? $categories[0] : '';
        ?>
        <div class="art-item<?php echo $is_large ? ' large' : ''; ?>"
             data-category="<?php echo esc_attr($category); ?>"
             data-index="<?php echo esc_attr($index); ?>">
            <div class="art-image">
                <?php if (has_post_thumbnail($art->ID)): ?>
                    <?php echo get_the_post_thumbnail($art->ID, 'art-thumb'); ?>
                <?php else: ?>
                    <div class="placeholder-image"></div>
                <?php endif; ?>
            </div>
            <div class="art-info">
                <h3><?php echo esc_html($art->post_title); ?></h3>
                <span class="art-category"><?php echo esc_html(ucfirst($category)); ?></span>
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
// Art data from WordPress for lightbox
const artData = [
    <?php foreach ($artworks as $art):
        $image_url = get_the_post_thumbnail_url($art->ID, 'art-large');
        $categories = wp_get_post_terms($art->ID, 'art_category', array('fields' => 'names'));
        $category = !empty($categories) ? $categories[0] : '';
    ?>
    {
        src: <?php echo json_encode($image_url ?: ''); ?>,
        title: <?php echo json_encode($art->post_title); ?>,
        category: <?php echo json_encode($category); ?>
    },
    <?php endforeach; ?>
];
</script>

<?php get_footer(); ?>
