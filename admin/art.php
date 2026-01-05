<?php
/**
 * Art Gallery Management
 */
$pageTitle = 'Art Gallery';

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if (!verifyCSRF($_GET['token'] ?? '')) {
        setFlash('error', 'Invalid request.');
    } else {
        try {
            // Get art to delete image
            $stmt = db()->prepare("SELECT image_path FROM art_gallery WHERE id = ?");
            $stmt->execute([$_GET['delete']]);
            $art = $stmt->fetch();

            if ($art) {
                // Delete art
                $stmt = db()->prepare("DELETE FROM art_gallery WHERE id = ?");
                $stmt->execute([$_GET['delete']]);

                // Delete image file
                if ($art['image_path']) {
                    deleteImage($art['image_path']);
                }

                setFlash('success', 'Artwork deleted successfully.');
            }
        } catch (PDOException $e) {
            setFlash('error', 'Failed to delete artwork.');
        }
    }
    header('Location: art.php');
    exit;
}

// Fetch all art
try {
    $stmt = db()->query("SELECT * FROM art_gallery ORDER BY sort_order ASC, created_at DESC");
    $artworks = $stmt->fetchAll();
} catch (PDOException $e) {
    $artworks = [];
}

include 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">All Artworks</h3>
        <a href="art-edit.php" class="btn btn-primary">
            <i class="bi bi-plus"></i>
            Add Artwork
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
                        <th>Size</th>
                        <th>Status</th>
                        <th>Order</th>
                        <th style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($artworks)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--admin-text-muted); padding: 2rem;">
                            No artworks found. <a href="art-edit.php" style="color: var(--admin-primary);">Add your first artwork</a>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($artworks as $art): ?>
                    <tr>
                        <td>
                            <?php if ($art['image_path']): ?>
                            <img src="../<?php echo e($art['image_path']); ?>" alt="" class="table-image">
                            <?php else: ?>
                            <div class="table-image" style="background: var(--admin-border);"></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo e($art['title']); ?></strong>
                            <br>
                            <small style="color: var(--admin-text-muted);"><?php echo e($art['slug']); ?></small>
                        </td>
                        <td style="text-transform: capitalize;"><?php echo e($art['category']); ?></td>
                        <td>
                            <?php if ($art['is_large']): ?>
                            <span class="badge badge-warning">Large</span>
                            <?php else: ?>
                            <span class="badge badge-secondary">Normal</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($art['status'] === 'published'): ?>
                            <span class="badge badge-success">Published</span>
                            <?php else: ?>
                            <span class="badge badge-secondary">Draft</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $art['sort_order']; ?></td>
                        <td>
                            <div class="actions">
                                <a href="art-edit.php?id=<?php echo $art['id']; ?>" class="btn btn-sm btn-secondary btn-icon" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="art.php?delete=<?php echo $art['id']; ?>&token=<?php echo generateCSRF(); ?>"
                                   class="btn btn-sm btn-danger btn-icon"
                                   title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this artwork?');">
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
