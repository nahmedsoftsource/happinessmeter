<?php
/**
 * Site Settings
 */
$pageTitle = 'Settings';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid request.');
    } else {
        $settings = [
            'site_name' => trim($_POST['site_name'] ?? ''),
            'hero_headline' => trim($_POST['hero_headline'] ?? ''),
            'hero_subline' => trim($_POST['hero_subline'] ?? ''),
            'hero_description' => trim($_POST['hero_description'] ?? ''),
            'available_for_hire' => isset($_POST['available_for_hire']) ? '1' : '0',
            'contact_email' => trim($_POST['contact_email'] ?? ''),
            'location' => trim($_POST['location'] ?? ''),
            'instagram_url' => trim($_POST['instagram_url'] ?? ''),
            'twitter_url' => trim($_POST['twitter_url'] ?? ''),
            'dribbble_url' => trim($_POST['dribbble_url'] ?? ''),
            'linkedin_url' => trim($_POST['linkedin_url'] ?? ''),
        ];

        try {
            $stmt = db()->prepare("
                INSERT INTO site_settings (setting_key, setting_value)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");

            foreach ($settings as $key => $value) {
                $stmt->execute([$key, $value]);
            }

            setFlash('success', 'Settings saved successfully.');
        } catch (PDOException $e) {
            setFlash('error', 'Failed to save settings.');
        }

        header('Location: settings.php');
        exit;
    }
}

include 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Site Settings</h3>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">

            <h4 style="margin-bottom: 1.5rem; font-size: 1rem; color: var(--admin-text-muted);">General</h4>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="site_name">Site Name</label>
                    <input type="text" id="site_name" name="site_name" class="form-control"
                           value="<?php echo e(getSetting('site_name', 'Josh Warner')); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="location">Location</label>
                    <input type="text" id="location" name="location" class="form-control"
                           value="<?php echo e(getSetting('location', 'Redding, California')); ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="available_for_hire" value="1"
                           <?php echo getSetting('available_for_hire', '1') === '1' ? 'checked' : ''; ?>>
                    Available for hire (shows green badge)
                </label>
            </div>

            <hr style="border: none; border-top: 1px solid var(--admin-border); margin: 2rem 0;">

            <h4 style="margin-bottom: 1.5rem; font-size: 1rem; color: var(--admin-text-muted);">Hero Section</h4>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="hero_headline">Headline (Bold Part)</label>
                    <input type="text" id="hero_headline" name="hero_headline" class="form-control"
                           value="<?php echo e(getSetting('hero_headline', 'Product')); ?>"
                           placeholder="Product">
                    <p class="form-help">Displays as: <strong>[This]</strong> by day.</p>
                </div>

                <div class="form-group">
                    <label class="form-label" for="hero_subline">Subline</label>
                    <input type="text" id="hero_subline" name="hero_subline" class="form-control"
                           value="<?php echo e(getSetting('hero_subline', 'Art by night.')); ?>"
                           placeholder="Art by night.">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="hero_description">Description</label>
                <textarea id="hero_description" name="hero_description" class="form-control" rows="2"><?php echo e(getSetting('hero_description')); ?></textarea>
            </div>

            <hr style="border: none; border-top: 1px solid var(--admin-border); margin: 2rem 0;">

            <h4 style="margin-bottom: 1.5rem; font-size: 1rem; color: var(--admin-text-muted);">Contact & Social</h4>

            <div class="form-group">
                <label class="form-label" for="contact_email">Contact Email</label>
                <input type="email" id="contact_email" name="contact_email" class="form-control"
                       value="<?php echo e(getSetting('contact_email', 'hello@narrators.co')); ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="instagram_url">Instagram URL</label>
                    <input type="url" id="instagram_url" name="instagram_url" class="form-control"
                           value="<?php echo e(getSetting('instagram_url')); ?>"
                           placeholder="https://instagram.com/username">
                </div>

                <div class="form-group">
                    <label class="form-label" for="twitter_url">Twitter URL</label>
                    <input type="url" id="twitter_url" name="twitter_url" class="form-control"
                           value="<?php echo e(getSetting('twitter_url')); ?>"
                           placeholder="https://twitter.com/username">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="dribbble_url">Dribbble URL</label>
                    <input type="url" id="dribbble_url" name="dribbble_url" class="form-control"
                           value="<?php echo e(getSetting('dribbble_url')); ?>"
                           placeholder="https://dribbble.com/username">
                </div>

                <div class="form-group">
                    <label class="form-label" for="linkedin_url">LinkedIn URL</label>
                    <input type="url" id="linkedin_url" name="linkedin_url" class="form-control"
                           value="<?php echo e(getSetting('linkedin_url')); ?>"
                           placeholder="https://linkedin.com/in/username">
                </div>
            </div>

            <div class="action-buttons">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check"></i>
                    Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
