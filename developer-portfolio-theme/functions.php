<?php
/**
 * Developer Portfolio Theme Functions
 */

if (!defined('ABSPATH')) {
    exit;
}

define('DEVELOPER_PORTFOLIO_VERSION', '1.0.0');

/**
 * Theme Setup
 */
function developer_portfolio_setup() {
    // Add theme support
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', array(
        'height'      => 76,
        'width'       => 76,
        'flex-height' => true,
        'flex-width'  => true,
    ));
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ));

    // Register navigation menus
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'developer-portfolio'),
    ));

    // Add image sizes
    add_image_size('portfolio-thumb', 800, 500, true);
    add_image_size('portfolio-large', 1200, 0, false);
    add_image_size('art-thumb', 600, 450, true);
    add_image_size('art-large', 1200, 0, false);
}
add_action('after_setup_theme', 'developer_portfolio_setup');

/**
 * Enqueue Scripts and Styles
 */
function developer_portfolio_scripts() {
    // Google Fonts
    wp_enqueue_style(
        'developer-portfolio-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap',
        array(),
        null
    );

    // Main stylesheet
    wp_enqueue_style(
        'developer-portfolio-style',
        get_stylesheet_uri(),
        array(),
        DEVELOPER_PORTFOLIO_VERSION
    );

    // Art page styles (load on art page only)
    if (is_page_template('page-art.php') || is_post_type_archive('art')) {
        wp_enqueue_style(
            'developer-portfolio-art',
            get_template_directory_uri() . '/css/art.css',
            array('developer-portfolio-style'),
            DEVELOPER_PORTFOLIO_VERSION
        );
    }

    // Main JS
    wp_enqueue_script(
        'developer-portfolio-main',
        get_template_directory_uri() . '/js/main.js',
        array(),
        DEVELOPER_PORTFOLIO_VERSION,
        true
    );

    // Pass data to JS
    wp_localize_script('developer-portfolio-main', 'portfolioData', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('portfolio_nonce'),
    ));

    // Art page JS
    if (is_page_template('page-art.php') || is_post_type_archive('art')) {
        wp_enqueue_script(
            'developer-portfolio-art',
            get_template_directory_uri() . '/js/art.js',
            array(),
            DEVELOPER_PORTFOLIO_VERSION,
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'developer_portfolio_scripts');

/**
 * Register Custom Post Type: Projects
 */
function developer_portfolio_register_projects() {
    $labels = array(
        'name'               => __('Projects', 'developer-portfolio'),
        'singular_name'      => __('Project', 'developer-portfolio'),
        'menu_name'          => __('Projects', 'developer-portfolio'),
        'add_new'            => __('Add New', 'developer-portfolio'),
        'add_new_item'       => __('Add New Project', 'developer-portfolio'),
        'edit_item'          => __('Edit Project', 'developer-portfolio'),
        'new_item'           => __('New Project', 'developer-portfolio'),
        'view_item'          => __('View Project', 'developer-portfolio'),
        'search_items'       => __('Search Projects', 'developer-portfolio'),
        'not_found'          => __('No projects found', 'developer-portfolio'),
        'not_found_in_trash' => __('No projects found in trash', 'developer-portfolio'),
    );

    $args = array(
        'labels'              => $labels,
        'public'              => true,
        'has_archive'         => true,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_rest'        => true,
        'menu_position'       => 5,
        'menu_icon'           => 'dashicons-portfolio',
        'supports'            => array('title', 'editor', 'thumbnail', 'excerpt'),
        'rewrite'             => array('slug' => 'projects'),
    );

    register_post_type('project', $args);

    // Project Tags taxonomy
    register_taxonomy('project_tag', 'project', array(
        'label'        => __('Project Tags', 'developer-portfolio'),
        'hierarchical' => false,
        'show_in_rest' => true,
        'rewrite'      => array('slug' => 'project-tag'),
    ));
}
add_action('init', 'developer_portfolio_register_projects');

/**
 * Register Custom Post Type: Art
 */
function developer_portfolio_register_art() {
    $labels = array(
        'name'               => __('Art', 'developer-portfolio'),
        'singular_name'      => __('Artwork', 'developer-portfolio'),
        'menu_name'          => __('Art Gallery', 'developer-portfolio'),
        'add_new'            => __('Add New', 'developer-portfolio'),
        'add_new_item'       => __('Add New Artwork', 'developer-portfolio'),
        'edit_item'          => __('Edit Artwork', 'developer-portfolio'),
        'new_item'           => __('New Artwork', 'developer-portfolio'),
        'view_item'          => __('View Artwork', 'developer-portfolio'),
        'search_items'       => __('Search Art', 'developer-portfolio'),
        'not_found'          => __('No artwork found', 'developer-portfolio'),
        'not_found_in_trash' => __('No artwork found in trash', 'developer-portfolio'),
    );

    $args = array(
        'labels'              => $labels,
        'public'              => true,
        'has_archive'         => true,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_rest'        => true,
        'menu_position'       => 6,
        'menu_icon'           => 'dashicons-art',
        'supports'            => array('title', 'thumbnail'),
        'rewrite'             => array('slug' => 'art'),
    );

    register_post_type('art', $args);

    // Art Category taxonomy
    register_taxonomy('art_category', 'art', array(
        'label'        => __('Art Categories', 'developer-portfolio'),
        'hierarchical' => true,
        'show_in_rest' => true,
        'rewrite'      => array('slug' => 'art-category'),
    ));
}
add_action('init', 'developer_portfolio_register_art');

/**
 * Add Meta Boxes for Projects
 */
function developer_portfolio_project_meta_boxes() {
    add_meta_box(
        'project_gallery',
        __('Project Gallery Images', 'developer-portfolio'),
        'developer_portfolio_gallery_callback',
        'project',
        'normal',
        'high'
    );

    add_meta_box(
        'project_settings',
        __('Project Settings', 'developer-portfolio'),
        'developer_portfolio_settings_callback',
        'project',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'developer_portfolio_project_meta_boxes');

/**
 * Project Gallery Meta Box Callback
 */
function developer_portfolio_gallery_callback($post) {
    wp_nonce_field('project_gallery_nonce', 'project_gallery_nonce_field');
    $gallery_images = get_post_meta($post->ID, '_project_gallery', true);
    ?>
    <div id="project-gallery-container">
        <div id="gallery-images" class="gallery-images-list">
            <?php
            if (!empty($gallery_images)) {
                foreach ($gallery_images as $image_id) {
                    $image_url = wp_get_attachment_image_url($image_id, 'thumbnail');
                    if ($image_url) {
                        echo '<div class="gallery-image-item" data-id="' . esc_attr($image_id) . '">';
                        echo '<img src="' . esc_url($image_url) . '" />';
                        echo '<button type="button" class="remove-image">&times;</button>';
                        echo '<input type="hidden" name="project_gallery[]" value="' . esc_attr($image_id) . '" />';
                        echo '</div>';
                    }
                }
            }
            ?>
        </div>
        <button type="button" id="add-gallery-images" class="button button-primary">
            <?php _e('Add Gallery Images', 'developer-portfolio'); ?>
        </button>
    </div>
    <style>
        .gallery-images-list { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 15px; }
        .gallery-image-item { position: relative; width: 100px; height: 100px; }
        .gallery-image-item img { width: 100%; height: 100%; object-fit: cover; border-radius: 4px; }
        .gallery-image-item .remove-image { position: absolute; top: -8px; right: -8px; width: 20px; height: 20px; border-radius: 50%; background: #dc3545; color: #fff; border: none; cursor: pointer; font-size: 14px; line-height: 1; }
    </style>
    <script>
    jQuery(document).ready(function($) {
        var frame;
        $('#add-gallery-images').on('click', function(e) {
            e.preventDefault();
            if (frame) { frame.open(); return; }
            frame = wp.media({
                title: '<?php _e('Select Gallery Images', 'developer-portfolio'); ?>',
                multiple: true,
                library: { type: 'image' },
                button: { text: '<?php _e('Add to Gallery', 'developer-portfolio'); ?>' }
            });
            frame.on('select', function() {
                var attachments = frame.state().get('selection').toJSON();
                attachments.forEach(function(attachment) {
                    var html = '<div class="gallery-image-item" data-id="' + attachment.id + '">';
                    html += '<img src="' + (attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url) + '" />';
                    html += '<button type="button" class="remove-image">&times;</button>';
                    html += '<input type="hidden" name="project_gallery[]" value="' + attachment.id + '" />';
                    html += '</div>';
                    $('#gallery-images').append(html);
                });
            });
            frame.open();
        });
        $(document).on('click', '.remove-image', function() {
            $(this).closest('.gallery-image-item').remove();
        });
    });
    </script>
    <?php
}

/**
 * Project Settings Meta Box Callback
 */
function developer_portfolio_settings_callback($post) {
    $sort_order = get_post_meta($post->ID, '_project_sort_order', true);
    $is_featured = get_post_meta($post->ID, '_project_featured', true);
    ?>
    <p>
        <label for="project_sort_order"><strong><?php _e('Sort Order', 'developer-portfolio'); ?></strong></label><br>
        <input type="number" id="project_sort_order" name="project_sort_order" value="<?php echo esc_attr($sort_order); ?>" min="0" style="width: 100%;">
    </p>
    <p>
        <label>
            <input type="checkbox" name="project_featured" value="1" <?php checked($is_featured, '1'); ?>>
            <?php _e('Featured Project', 'developer-portfolio'); ?>
        </label>
    </p>
    <?php
}

/**
 * Save Project Meta
 */
function developer_portfolio_save_project_meta($post_id) {
    if (!isset($_POST['project_gallery_nonce_field']) ||
        !wp_verify_nonce($_POST['project_gallery_nonce_field'], 'project_gallery_nonce')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Save gallery
    if (isset($_POST['project_gallery'])) {
        $gallery = array_map('absint', $_POST['project_gallery']);
        update_post_meta($post_id, '_project_gallery', $gallery);
    } else {
        delete_post_meta($post_id, '_project_gallery');
    }

    // Save sort order
    if (isset($_POST['project_sort_order'])) {
        update_post_meta($post_id, '_project_sort_order', absint($_POST['project_sort_order']));
    }

    // Save featured
    if (isset($_POST['project_featured'])) {
        update_post_meta($post_id, '_project_featured', '1');
    } else {
        delete_post_meta($post_id, '_project_featured');
    }
}
add_action('save_post_project', 'developer_portfolio_save_project_meta');

/**
 * Add Art Meta Box for Large Image setting
 */
function developer_portfolio_art_meta_boxes() {
    add_meta_box(
        'art_settings',
        __('Art Settings', 'developer-portfolio'),
        'developer_portfolio_art_settings_callback',
        'art',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'developer_portfolio_art_meta_boxes');

function developer_portfolio_art_settings_callback($post) {
    wp_nonce_field('art_settings_nonce', 'art_settings_nonce_field');
    $is_large = get_post_meta($post->ID, '_art_is_large', true);
    $sort_order = get_post_meta($post->ID, '_art_sort_order', true);
    ?>
    <p>
        <label>
            <input type="checkbox" name="art_is_large" value="1" <?php checked($is_large, '1'); ?>>
            <?php _e('Display as Large (spans 2 columns)', 'developer-portfolio'); ?>
        </label>
    </p>
    <p>
        <label for="art_sort_order"><strong><?php _e('Sort Order', 'developer-portfolio'); ?></strong></label><br>
        <input type="number" id="art_sort_order" name="art_sort_order" value="<?php echo esc_attr($sort_order); ?>" min="0" style="width: 100%;">
    </p>
    <?php
}

/**
 * Save Art Meta
 */
function developer_portfolio_save_art_meta($post_id) {
    if (!isset($_POST['art_settings_nonce_field']) ||
        !wp_verify_nonce($_POST['art_settings_nonce_field'], 'art_settings_nonce')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (isset($_POST['art_is_large'])) {
        update_post_meta($post_id, '_art_is_large', '1');
    } else {
        delete_post_meta($post_id, '_art_is_large');
    }

    if (isset($_POST['art_sort_order'])) {
        update_post_meta($post_id, '_art_sort_order', absint($_POST['art_sort_order']));
    }
}
add_action('save_post_art', 'developer_portfolio_save_art_meta');

/**
 * Theme Customizer Settings
 */
function developer_portfolio_customize_register($wp_customize) {
    // Hero Section
    $wp_customize->add_section('hero_section', array(
        'title'    => __('Hero Section', 'developer-portfolio'),
        'priority' => 30,
    ));

    // Hero Video/Image
    $wp_customize->add_setting('hero_media', array(
        'default'           => '',
        'sanitize_callback' => 'absint',
    ));
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'hero_media', array(
        'label'     => __('Hero Video or Image', 'developer-portfolio'),
        'section'   => 'hero_section',
        'mime_type' => 'video,image',
    )));

    // Hero Headline
    $wp_customize->add_setting('hero_headline', array(
        'default'           => 'Product by day.',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('hero_headline', array(
        'label'   => __('Hero Headline', 'developer-portfolio'),
        'section' => 'hero_section',
        'type'    => 'text',
    ));

    // Hero Subline
    $wp_customize->add_setting('hero_subline', array(
        'default'           => 'Art by night.',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('hero_subline', array(
        'label'   => __('Hero Subline', 'developer-portfolio'),
        'section' => 'hero_section',
        'type'    => 'text',
    ));

    // Hero Description
    $wp_customize->add_setting('hero_description', array(
        'default'           => 'I design products and craft 3D art. Currently building experiences at Company.',
        'sanitize_callback' => 'sanitize_textarea_field',
    ));
    $wp_customize->add_control('hero_description', array(
        'label'   => __('Hero Description', 'developer-portfolio'),
        'section' => 'hero_section',
        'type'    => 'textarea',
    ));

    // Available for Hire
    $wp_customize->add_setting('available_for_hire', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
    ));
    $wp_customize->add_control('available_for_hire', array(
        'label'   => __('Show "Available for Hire" badge', 'developer-portfolio'),
        'section' => 'hero_section',
        'type'    => 'checkbox',
    ));

    // Footer Section
    $wp_customize->add_section('footer_section', array(
        'title'    => __('Footer', 'developer-portfolio'),
        'priority' => 90,
    ));

    // Email
    $wp_customize->add_setting('footer_email', array(
        'default'           => 'hello@example.com',
        'sanitize_callback' => 'sanitize_email',
    ));
    $wp_customize->add_control('footer_email', array(
        'label'   => __('Contact Email', 'developer-portfolio'),
        'section' => 'footer_section',
        'type'    => 'email',
    ));

    // Location
    $wp_customize->add_setting('footer_location', array(
        'default'           => 'San Francisco, CA',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('footer_location', array(
        'label'   => __('Location', 'developer-portfolio'),
        'section' => 'footer_section',
        'type'    => 'text',
    ));

    // Social Links
    $wp_customize->add_setting('social_twitter', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ));
    $wp_customize->add_control('social_twitter', array(
        'label'   => __('Twitter URL', 'developer-portfolio'),
        'section' => 'footer_section',
        'type'    => 'url',
    ));

    $wp_customize->add_setting('social_linkedin', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ));
    $wp_customize->add_control('social_linkedin', array(
        'label'   => __('LinkedIn URL', 'developer-portfolio'),
        'section' => 'footer_section',
        'type'    => 'url',
    ));

    $wp_customize->add_setting('social_dribbble', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
    ));
    $wp_customize->add_control('social_dribbble', array(
        'label'   => __('Dribbble URL', 'developer-portfolio'),
        'section' => 'footer_section',
        'type'    => 'url',
    ));
}
add_action('customize_register', 'developer_portfolio_customize_register');

/**
 * Get Projects with Gallery Data
 */
function developer_portfolio_get_projects_data() {
    $projects = get_posts(array(
        'post_type'      => 'project',
        'posts_per_page' => -1,
        'orderby'        => 'meta_value_num',
        'meta_key'       => '_project_sort_order',
        'order'          => 'ASC',
    ));

    $data = array();
    foreach ($projects as $project) {
        $gallery = get_post_meta($project->ID, '_project_gallery', true);
        $images = array();

        if (!empty($gallery)) {
            foreach ($gallery as $image_id) {
                $url = wp_get_attachment_image_url($image_id, 'portfolio-large');
                if ($url) {
                    $images[] = $url;
                }
            }
        }

        // If no gallery, use featured image
        if (empty($images) && has_post_thumbnail($project->ID)) {
            $images[] = get_the_post_thumbnail_url($project->ID, 'portfolio-large');
        }

        $tags = wp_get_post_terms($project->ID, 'project_tag', array('fields' => 'names'));

        $data[$project->ID] = array(
            'title'       => $project->post_title,
            'description' => $project->post_excerpt ?: wp_trim_words($project->post_content, 30),
            'tags'        => $tags,
            'images'      => $images,
        );
    }

    return $data;
}

/**
 * Custom Walker for Navigation Menu
 */
class Developer_Portfolio_Nav_Walker extends Walker_Nav_Menu {
    public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0) {
        $classes = empty($item->classes) ? array() : (array) $item->classes;
        $classes[] = 'menu-item-' . $item->ID;

        $class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args, $depth));

        $active_class = '';
        if (in_array('current-menu-item', $classes) || in_array('current_page_item', $classes)) {
            $active_class = ' active';
        }

        $output .= '<a href="' . esc_url($item->url) . '" class="' . esc_attr($active_class) . '">';
        $output .= esc_html($item->title);
        $output .= '</a>';
    }

    public function end_el(&$output, $item, $depth = 0, $args = null) {
        // No closing tag needed
    }

    public function start_lvl(&$output, $depth = 0, $args = null) {
        // No sub-menu wrapper
    }

    public function end_lvl(&$output, $depth = 0, $args = null) {
        // No sub-menu wrapper
    }
}

/**
 * Flush rewrite rules on theme activation
 */
function developer_portfolio_activate() {
    developer_portfolio_register_projects();
    developer_portfolio_register_art();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'developer_portfolio_activate');
