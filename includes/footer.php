    <!-- Footer -->
    <footer id="contact">
        <div class="footer-content">
            <div class="footer-section">
                <div class="status-badge">
                    <span class="status-dot"></span>
                    <?php echo getSetting('available_for_hire', '1') === '1' ? 'Available for hire' : 'Currently unavailable'; ?>
                </div>
            </div>

            <div class="footer-section">
                <h4>Location</h4>
                <p><?php echo e(getSetting('location', 'Redding, California')); ?></p>
            </div>

            <div class="footer-section">
                <h4>Contact</h4>
                <a href="mailto:<?php echo e(getSetting('contact_email', 'hello@narrators.co')); ?>">
                    <?php echo e(getSetting('contact_email', 'hello@narrators.co')); ?>
                </a>
            </div>

            <div class="footer-section">
                <h4>Social</h4>
                <?php if ($instagram = getSetting('instagram_url')): ?>
                    <a href="<?php echo e($instagram); ?>" target="_blank" rel="noopener">Instagram</a>
                <?php endif; ?>
                <?php if ($twitter = getSetting('twitter_url')): ?>
                    <a href="<?php echo e($twitter); ?>" target="_blank" rel="noopener">Twitter</a>
                <?php endif; ?>
                <?php if ($dribbble = getSetting('dribbble_url')): ?>
                    <a href="<?php echo e($dribbble); ?>" target="_blank" rel="noopener">Dribbble</a>
                <?php endif; ?>
            </div>
        </div>
    </footer>

    <script src="js/main.js"></script>
    <?php if (isset($extraJS)): ?>
        <?php foreach ($extraJS as $js): ?>
            <script src="js/<?php echo e($js); ?>.js"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
