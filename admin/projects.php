<?php
/**
 * Projects Management
 */
$pageTitle = 'Projects';

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if (!verifyCSRF($_GET['token'] ?? '')) {
        setFlash('error', 'Invalid request.');
    } else {
        try {
            // Get project to delete image
            $stmt = db()->prepare("SELECT featured_image FROM projects WHERE id = ?");
            $stmt->execute([$_GET['delete']]);
            $project = $stmt->fetch();

            if ($project) {
                // Delete project
                $stmt = db()->prepare("DELETE FROM projects WHERE id = ?");
                $stmt->execute([$_GET['delete']]);

                // Delete image file
                if ($project['featured_image']) {
                    deleteImage($project['featured_image']);
                }

                setFlash('success', 'Project deleted successfully.');
            }
        } catch (PDOException $e) {
            setFlash('error', 'Failed to delete project.');
        }
    }
    header('Location: projects.php');
    exit;
}

// Fetch all projects
try {
    $stmt = db()->query("SELECT * FROM projects ORDER BY sort_order ASC, created_at DESC");
    $projects = $stmt->fetchAll();
} catch (PDOException $e) {
    $projects = [];
}

include 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">All Projects</h3>
        <a href="project-edit.php" class="btn btn-primary">
            <i class="bi bi-plus"></i>
            Add Project
        </a>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">Image</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Featured</th>
                        <th>Order</th>
                        <th style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($projects)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--admin-text-muted); padding: 2rem;">
                            No projects found. <a href="project-edit.php" style="color: var(--admin-primary);">Add your first project</a>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($projects as $project): ?>
                    <tr>
                        <td>
                            <?php if ($project['featured_image']): ?>
                            <img src="../<?php echo e($project['featured_image']); ?>" alt="" class="table-image">
                            <?php else: ?>
                            <div class="table-image" style="background: var(--admin-border);"></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo e($project['title']); ?></strong>
                            <br>
                            <small style="color: var(--admin-text-muted);"><?php echo e($project['slug']); ?></small>
                        </td>
                        <td><?php echo e($project['category']); ?></td>
                        <td>
                            <?php if ($project['status'] === 'published'): ?>
                            <span class="badge badge-success">Published</span>
                            <?php elseif ($project['status'] === 'coming_soon'): ?>
                            <span class="badge badge-warning">Coming Soon</span>
                            <?php else: ?>
                            <span class="badge badge-secondary">Draft</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($project['is_featured']): ?>
                            <i class="bi bi-star-fill" style="color: var(--admin-warning);"></i>
                            <?php else: ?>
                            <i class="bi bi-star" style="color: var(--admin-text-muted);"></i>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $project['sort_order']; ?></td>
                        <td>
                            <div class="actions">
                                <a href="project-edit.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-secondary btn-icon" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="projects.php?delete=<?php echo $project['id']; ?>&token=<?php echo generateCSRF(); ?>"
                                   class="btn btn-sm btn-danger btn-icon"
                                   title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this project?');">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
