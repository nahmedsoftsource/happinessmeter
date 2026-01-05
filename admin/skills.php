<?php
/**
 * Skills Management
 */
$pageTitle = 'Skills';

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if (!verifyCSRF($_GET['token'] ?? '')) {
        setFlash('error', 'Invalid request.');
    } else {
        try {
            $stmt = db()->prepare("DELETE FROM skills WHERE id = ?");
            $stmt->execute([$_GET['delete']]);
            setFlash('success', 'Skill deleted successfully.');
        } catch (PDOException $e) {
            setFlash('error', 'Failed to delete skill.');
        }
    }
    header('Location: skills.php');
    exit;
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid request.');
    } else {
        $id = intval($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $sortOrder = intval($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (empty($title)) {
            setFlash('error', 'Title is required.');
        } else {
            try {
                if ($id > 0) {
                    // Update
                    $stmt = db()->prepare("
                        UPDATE skills SET title = ?, description = ?, sort_order = ?, is_active = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$title, $description, $sortOrder, $isActive, $id]);
                    setFlash('success', 'Skill updated successfully.');
                } else {
                    // Insert
                    $stmt = db()->prepare("
                        INSERT INTO skills (title, description, sort_order, is_active)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$title, $description, $sortOrder, $isActive]);
                    setFlash('success', 'Skill added successfully.');
                }
            } catch (PDOException $e) {
                setFlash('error', 'Failed to save skill.');
            }
        }
    }
    header('Location: skills.php');
    exit;
}

// Fetch all skills
try {
    $stmt = db()->query("SELECT * FROM skills ORDER BY sort_order ASC");
    $skills = $stmt->fetchAll();
} catch (PDOException $e) {
    $skills = [];
}

include 'includes/header.php';
?>

<div class="form-row">
    <!-- Skills List -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">All Skills</h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Order</th>
                            <th>Status</th>
                            <th style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($skills)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--admin-text-muted);">
                                No skills found.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($skills as $skill): ?>
                        <tr>
                            <td>
                                <strong><?php echo e($skill['title']); ?></strong>
                                <br>
                                <small style="color: var(--admin-text-muted);">
                                    <?php echo e(substr($skill['description'], 0, 60)); ?>...
                                </small>
                            </td>
                            <td><?php echo $skill['sort_order']; ?></td>
                            <td>
                                <?php if ($skill['is_active']): ?>
                                <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                <span class="badge badge-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <button type="button" class="btn btn-sm btn-secondary btn-icon edit-skill"
                                            data-id="<?php echo $skill['id']; ?>"
                                            data-title="<?php echo e($skill['title']); ?>"
                                            data-description="<?php echo e($skill['description']); ?>"
                                            data-sort="<?php echo $skill['sort_order']; ?>"
                                            data-active="<?php echo $skill['is_active']; ?>"
                                            title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="skills.php?delete=<?php echo $skill['id']; ?>&token=<?php echo generateCSRF(); ?>"
                                       class="btn btn-sm btn-danger btn-icon"
                                       title="Delete"
                                       onclick="return confirm('Are you sure?');">
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

    <!-- Add/Edit Form -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title" id="formTitle">Add Skill</h3>
        </div>
        <div class="card-body">
            <form method="POST" id="skillForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                <input type="hidden" name="id" id="skillId" value="0">

                <div class="form-group">
                    <label class="form-label" for="title">Title *</label>
                    <input type="text" id="title" name="title" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="4"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="sort_order">Sort Order</label>
                    <input type="number" id="sort_order" name="sort_order" class="form-control" value="0">
                </div>

                <div class="form-group">
                    <label class="form-label" style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="is_active" id="is_active" value="1" checked>
                        Active
                    </label>
                </div>

                <div class="action-buttons" style="border: none; padding-top: 0; margin-top: 1rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check"></i>
                        <span id="submitText">Add Skill</span>
                    </button>
                    <button type="button" class="btn btn-secondary" id="cancelEdit" style="display: none;">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Edit skill
document.querySelectorAll('.edit-skill').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('skillId').value = this.dataset.id;
        document.getElementById('title').value = this.dataset.title;
        document.getElementById('description').value = this.dataset.description;
        document.getElementById('sort_order').value = this.dataset.sort;
        document.getElementById('is_active').checked = this.dataset.active === '1';

        document.getElementById('formTitle').textContent = 'Edit Skill';
        document.getElementById('submitText').textContent = 'Update Skill';
        document.getElementById('cancelEdit').style.display = 'inline-flex';

        document.getElementById('title').focus();
    });
});

// Cancel edit
document.getElementById('cancelEdit').addEventListener('click', function() {
    document.getElementById('skillForm').reset();
    document.getElementById('skillId').value = '0';
    document.getElementById('formTitle').textContent = 'Add Skill';
    document.getElementById('submitText').textContent = 'Add Skill';
    this.style.display = 'none';
});
</script>

<?php include 'includes/footer.php'; ?>
