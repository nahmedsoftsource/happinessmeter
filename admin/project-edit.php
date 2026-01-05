<?php
/**
 * Project Add/Edit
 */
$isEdit = isset($_GET['id']) && is_numeric($_GET['id']);
$pageTitle = $isEdit ? 'Edit Project' : 'Add Project';
$project = null;

// Fetch project if editing
if ($isEdit) {
    try {
        $stmt = db()->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $project = $stmt->fetch();

        if (!$project) {
            setFlash('error', 'Project not found.');
            header('Location: projects.php');
            exit;
        }
    } catch (PDOException $e) {
        setFlash('error', 'Failed to load project.');
        header('Location: projects.php');
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid request.');
    } else {
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '') ?: generateSlug($title);
        $description = trim($_POST['description'] ?? '');
        $category = trim($_POST['category'] ?? 'Product Design');
        $tags = $_POST['tags'] ?? '';
        $status = $_POST['status'] ?? 'draft';
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
        $sortOrder = intval($_POST['sort_order'] ?? 0);

        // Validate
        $errors = [];
        if (empty($title)) {
            $errors[] = 'Title is required.';
        }

        // Handle image upload
        $imagePath = $project['featured_image'] ?? null;
        if (!empty($_FILES['featured_image']['tmp_name'])) {
            $upload = uploadImage($_FILES['featured_image'], 'projects');
            if ($upload['success']) {
                // Delete old image
                if ($imagePath) {
                    deleteImage($imagePath);
                }
                $imagePath = $upload['path'];
            } else {
                $errors[] = $upload['error'];
            }
        }

        if (empty($errors)) {
            try {
                // Process tags
                $tagsArray = array_map('trim', explode(',', $tags));
                $tagsJson = json_encode(array_filter($tagsArray));

                if ($isEdit) {
                    $stmt = db()->prepare("
                        UPDATE projects SET
                            title = ?, slug = ?, description = ?, category = ?,
                            tags = ?, featured_image = ?, status = ?,
                            is_featured = ?, sort_order = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $title, $slug, $description, $category,
                        $tagsJson, $imagePath, $status,
                        $isFeatured, $sortOrder, $project['id']
                    ]);
                    setFlash('success', 'Project updated successfully.');
                } else {
                    $stmt = db()->prepare("
                        INSERT INTO projects
                            (title, slug, description, category, tags, featured_image, status, is_featured, sort_order)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $title, $slug, $description, $category,
                        $tagsJson, $imagePath, $status,
                        $isFeatured, $sortOrder
                    ]);
                    setFlash('success', 'Project created successfully.');
                }

                header('Location: projects.php');
                exit;
            } catch (PDOException $e) {
                setFlash('error', 'Failed to save project: ' . $e->getMessage());
            }
        } else {
            setFlash('error', implode(' ', $errors));
        }
    }
}

// Get tags as comma-separated string
$tagsString = '';
if ($project && $project['tags']) {
    $tagsArray = json_decode($project['tags'], true);
    if (is_array($tagsArray)) {
        $tagsString = implode(', ', $tagsArray);
    }
}

include 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><?php echo $isEdit ? 'Edit Project' : 'Add New Project'; ?></h3>
        <a href="projects.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i>
            Back to Projects
        </a>
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="title">Title *</label>
                    <input type="text" id="title" name="title" class="form-control"
                           value="<?php echo e($project['title'] ?? $_POST['title'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="slug">Slug</label>
                    <input type="text" id="slug" name="slug" class="form-control"
                           value="<?php echo e($project['slug'] ?? $_POST['slug'] ?? ''); ?>"
                           placeholder="auto-generated-from-title">
                    <p class="form-help">Leave empty to auto-generate from title</p>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="4"><?php echo e($project['description'] ?? $_POST['description'] ?? ''); ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="category">Category</label>
                    <select id="category" name="category" class="form-control">
                        <option value="Product Design" <?php echo ($project['category'] ?? '') === 'Product Design' ? 'selected' : ''; ?>>Product Design</option>
                        <option value="Branding" <?php echo ($project['category'] ?? '') === 'Branding' ? 'selected' : ''; ?>>Branding</option>
                        <option value="Graphic Design" <?php echo ($project['category'] ?? '') === 'Graphic Design' ? 'selected' : ''; ?>>Graphic Design</option>
                        <option value="Typography" <?php echo ($project['category'] ?? '') === 'Typography' ? 'selected' : ''; ?>>Typography</option>
                        <option value="3D Art" <?php echo ($project['category'] ?? '') === '3D Art' ? 'selected' : ''; ?>>3D Art</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="tags">Tags</label>
                    <input type="text" id="tags" name="tags" class="form-control"
                           value="<?php echo e($tagsString ?: ($_POST['tags'] ?? '')); ?>"
                           placeholder="Product Design, Mobile App, UX/UI">
                    <p class="form-help">Comma-separated list of tags</p>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="draft" <?php echo ($project['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="published" <?php echo ($project['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
                        <option value="coming_soon" <?php echo ($project['status'] ?? '') === 'coming_soon' ? 'selected' : ''; ?>>Coming Soon</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="sort_order">Sort Order</label>
                    <input type="number" id="sort_order" name="sort_order" class="form-control"
                           value="<?php echo e($project['sort_order'] ?? $_POST['sort_order'] ?? '0'); ?>">
                    <p class="form-help">Lower numbers appear first</p>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Featured Image</label>
                <label class="file-upload" for="featured_image">
                    <input type="file" id="featured_image" name="featured_image" accept="image/*">
                    <div class="file-upload-icon">
                        <i class="bi bi-cloud-upload"></i>
                    </div>
                    <p class="file-upload-text">
                        <span>Click to upload</span> or drag and drop<br>
                        PNG, JPG, GIF, WebP (max 10MB)
                    </p>
                    <?php if ($project && $project['featured_image']): ?>
                    <div class="file-preview">
                        <img src="../<?php echo e($project['featured_image']); ?>" alt="Current image">
                    </div>
                    <?php endif; ?>
                </label>
            </div>

            <div class="form-group">
                <label class="form-label" style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="is_featured" value="1"
                           <?php echo ($project['is_featured'] ?? false) ? 'checked' : ''; ?>>
                    Show on homepage (Featured)
                </label>
            </div>

            <div class="action-buttons">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check"></i>
                    <?php echo $isEdit ? 'Update Project' : 'Create Project'; ?>
                </button>
                <a href="projects.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
// Auto-generate slug from title
document.getElementById('title').addEventListener('input', function() {
    const slugField = document.getElementById('slug');
    if (!slugField.value) {
        slugField.placeholder = this.value.toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '');
    }
});

// Preview uploaded image
document.getElementById('featured_image').addEventListener('change', function(e) {
    const preview = this.parentElement.querySelector('.file-preview');
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            if (!preview) {
                const div = document.createElement('div');
                div.className = 'file-preview';
                div.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
                document.querySelector('.file-upload').appendChild(div);
            } else {
                preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
            }
        };
        reader.readAsDataURL(this.files[0]);
    }
});
</script>

<?php include 'includes/footer.php'; ?>
