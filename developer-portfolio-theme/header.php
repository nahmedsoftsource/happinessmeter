<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<!-- Header Navigation -->
<header>
    <div class="header-left">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="logo">
            <div class="logo-circle">
                <?php if (has_custom_logo()): ?>
                    <?php
                    $custom_logo_id = get_theme_mod('custom_logo');
                    $logo_url = wp_get_attachment_image_url($custom_logo_id, 'thumbnail');
                    ?>
                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php bloginfo('name'); ?>">
                <?php else: ?>
                    <?php
                    $site_name = get_bloginfo('name');
                    $words = explode(' ', $site_name);
                    $initials = '';
                    if (count($words) >= 2) {
                        $initials = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
                    } else {
                        $initials = strtoupper(substr($site_name, 0, 2));
                    }
                    echo esc_html($initials);
                    ?>
                <?php endif; ?>
            </div>
            <span><?php bloginfo('name'); ?></span>
        </a>
    </div>
    <div class="header-center">
        <nav>
            <?php
            if (has_nav_menu('primary')) {
                wp_nav_menu(array(
                    'theme_location' => 'primary',
                    'container'      => false,
                    'items_wrap'     => '%3$s',
                    'walker'         => new Developer_Portfolio_Nav_Walker(),
                ));
            } else {
                // Fallback menu
                ?>
                <a href="<?php echo esc_url(home_url('/')); ?>"<?php echo is_front_page() ? ' class="active"' : ''; ?>>Projects</a>
                <a href="<?php echo esc_url(home_url('/art/')); ?>"<?php echo is_page('art') || is_post_type_archive('art') ? ' class="active"' : ''; ?>>Art</a>
                <a href="<?php echo esc_url(home_url('/info/')); ?>"<?php echo is_page('info') ? ' class="active"' : ''; ?>>Info</a>
                <?php
            }
            ?>
        </nav>
    </div>
    <div class="header-right">
        <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="contact-btn<?php echo is_page('contact') ? ' active' : ''; ?>">Contact</a>
    </div>
</header>
