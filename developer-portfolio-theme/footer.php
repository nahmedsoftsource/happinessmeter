<!-- Footer -->
<footer>
    <div class="footer-content">
        <div class="footer-section">
            <h4>Contact</h4>
            <?php if ($email = get_theme_mod('footer_email', 'hello@example.com')): ?>
                <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a>
            <?php endif; ?>
        </div>
        <div class="footer-section">
            <h4>Location</h4>
            <p><?php echo esc_html(get_theme_mod('footer_location', 'San Francisco, CA')); ?></p>
        </div>
        <div class="footer-section">
            <h4>Social</h4>
            <?php if ($twitter = get_theme_mod('social_twitter')): ?>
                <a href="<?php echo esc_url($twitter); ?>" target="_blank" rel="noopener">Twitter</a>
            <?php endif; ?>
            <?php if ($linkedin = get_theme_mod('social_linkedin')): ?>
                <a href="<?php echo esc_url($linkedin); ?>" target="_blank" rel="noopener">LinkedIn</a>
            <?php endif; ?>
            <?php if ($dribbble = get_theme_mod('social_dribbble')): ?>
                <a href="<?php echo esc_url($dribbble); ?>" target="_blank" rel="noopener">Dribbble</a>
            <?php endif; ?>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
