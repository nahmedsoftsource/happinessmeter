<?php
/**
 * Admin Dashboard
 */
$pageTitle = 'Dashboard';

// Get stats
try {
    $projectCount = db()->query("SELECT COUNT(*) FROM projects")->fetchColumn();
    $publishedProjects = db()->query("SELECT COUNT(*) FROM projects WHERE status = 'published'")->fetchColumn();
    $artCount = db()->query("SELECT COUNT(*) FROM art_gallery")->fetchColumn();
    $publishedArt = db()->query("SELECT COUNT(*) FROM art_gallery WHERE status = 'published'")->fetchColumn();
} catch (PDOException $e) {
    $projectCount = $publishedProjects = $artCount = $publishedArt = 0;
}

// Get recent projects
try {
    $recentProjects = db()->query("SELECT * FROM projects ORDER BY updated_at DESC LIMIT 5")->fetchAll();
} catch (PDOException $e) {
    $recentProjects = [];
}

// Get recent art
try {
    $recentArt = db()->query("SELECT * FROM art_gallery ORDER BY updated_at DESC LIMIT 5")->fetchAll();
} catch (PDOException $e) {
    $recentArt = [];
}

include 'includes/header.php';
?>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon purple">
            <i class="bi bi-folder"></i>
        </div>
        <div class="stat-value"><?php echo $projectCount; ?></div>
        <div class="stat-label">Total Projects</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon green">
            <i class="bi bi-check-circle"></i>
        </div>
        <div class="stat-value"><?php echo $publishedProjects; ?></div>
        <div class="stat-label">Published Projects</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon orange">
            <i class="bi bi-palette"></i>
        </div>
        <div class="stat-value"><?php echo $artCount; ?></div>
        <div class="stat-label">Total Artworks</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon blue">
            <i class="bi bi-image"></i>
        </div>
        <div class="stat-value"><?php echo $publishedArt; ?></div>
        <div class="stat-label">Published Artworks</div>
    </div>
</div>

<div class="form-row">
    <!-- Recent Projects -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Projects</h3>
            <a href="projects.php" class="btn btn-sm btn-secondary">View All</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentProjects)): ?>
                        <tr>
                            <td colspan="3" style="text-align: center; color: var(--admin-text-muted);">No projects yet</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($recentProjects as $project): ?>
                        <tr>
                            <td><?php echo e($project['title']); ?></td>
                            <td>
                                <?php if ($project['status'] === 'published'): ?>
                                <span class="badge badge-success">Published</span>
                                <?php elseif ($project['status'] === 'coming_soon'): ?>
                                <span class="badge badge-warning">Coming Soon</span>
                                <?php else: ?>
                                <span class="badge badge-secondary">Draft</span>
                                <?php endif; ?>
                            </td>
                            <td style="color: var(--admin-text-muted);">
                                <?php echo date('M j, Y', strtotime($project['updated_at'])); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Art -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Artworks</h3>
            <a href="art.php" class="btn btn-sm btn-secondary">View All</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentArt)): ?>
                        <tr>
                            <td colspan="3" style="text-align: center; color: var(--admin-text-muted);">No artworks yet</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($recentArt as $art): ?>
                        <tr>
                            <td><?php echo e($art['title']); ?></td>
                            <td style="text-transform: capitalize;"><?php echo e($art['category']); ?></td>
                            <td>
                                <?php if ($art['status'] === 'published'): ?>
                                <span class="badge badge-success">Published</span>
                                <?php else: ?>
                                <span class="badge badge-secondary">Draft</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
