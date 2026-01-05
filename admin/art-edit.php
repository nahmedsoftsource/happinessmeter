<?php
/**
 * Art Add/Edit
 */
$isEdit = isset($_GET['id']) && is_numeric($_GET['id']);
$pageTitle = $isEdit ? 'Edit Artwork' : 'Add Artwork';
$art = null;

// Fetch artwork if editing
if ($isEdit) {
    try {
        $stmt = db()->prepare("SELECT * FROM art_gallery WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $art = $stmt->fetch();

        if (!$art) {
            setFlash('error', 'Artwork not found.');
            header('Location: art.php');
            exit;
        }
    } catch (PDOException $e) {
        setFlash('error', 'Failed to load artwork.');
        header('Location: art.php');
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
        $category = $_POST['category'] ?? '3d';
        $status = $_POST['status'] ?? 'draft';
        $isLarge = isset($_POST['is_large']) ? 1 : 0;
        $sortOrder = intval($_POST['sort_order'] ?? 0);

        // Validate
        $errors = [];
        if (empty($title)) {
            $errors[] = 'Title is required.';
        }

        // Handle image upload
        $imagePath = $art['image_path'] ?? null;
        if (!empty($_FILES['image']['tmp_name'])) {
            $upload = uploadImage($_FILES['image'], 'art');
            if ($upload['success']) {
                // Delete old image
                if ($imagePath) {
                    deleteImage($imagePath);
                }
                $imagePath = $upload['path'];
            } else {
                $errors[] = $upload['error'];
            }
        } elseif (!$isEdit) {
            $errors[] = 'Image is required.';
        }

        if (empty($errors)) {
            try {
                if ($isEdit) {
                    $stmt = db()->prepare("
                        UPDATE art_gallery SET
                            title = ?, slug = ?, description = ?, category = ?,
                            image_path = ?, is_large = ?, status = ?,
                            sort_order = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $title, $slug, $description, $category,
                        $imagePath, $isLarge, $status,
                        $sortOrder, $art['id']
                    ]);
                    setFlash('success', 'Artwork updated successfully.');
                } else {
                    $stmt = db()->prepare("
                        INSERT INTO art_gallery
                            (title, slug, description, category, image_path, is_large, status, sort_order)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $title, $slug, $description, $category,
                        $imagePath, $isLarge, $status, $sortOrder
                    ]);
                    setFlash('success', 'Artwork created successfully.');
                }

                header('Location: art.php');
                exit;
            } catch (PDOException $e) {
                setFlash('error', 'Failed to save artwork: ' . $e->getMessage());
            }
        } else {
            setFlash('error', implode(' ', $errors));
        }
    }
}

include 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><?php echo $isEdit ? 'Edit Artwork' : 'Add New Artwork'; ?></h3>
        <a href="art.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i>
            Back to Gallery
        </a>
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="title">Title *</label>
                    <input type="text" id="title" name="title" class="form-control"
                           value="<?php echo e($art['title'] ?? $_POST['title'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="slug">Slug</label>
                    <input type="text" id="slug" name="slug" class="form-control"
                           value="<?php echo e($art['slug'] ?? $_POST['slug'] ?? ''); ?>"
                           placeholder="auto-generated-from-title">
                    <p class="form-help">Leave empty to auto-generate from title</p>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="3"><?php echo e($art['description'] ?? $_POST['description'] ?? ''); ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="category">Category</label>
                    <select id="category" name="category" class="form-control">
                        <option value="3d" <?php echo ($art['category'] ?? '') === '3d' ? 'selected' : ''; ?>>3D Art</option>
                        <option value="typography" <?php echo ($art['category'] ?? '') === 'typography' ? 'selected' : ''; ?>>Typography</option>
                        <option value="abstract" <?php echo ($art['category'] ?? '') === 'abstract' ? 'selected' : ''; ?>>Abstract</option>
                        <option value="experimental" <?php echo ($art['category'] ?? '') === 'experimental' ? 'selected' : ''; ?>>Experimental</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="draft" <?php echo ($art['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="published" <?php echo ($art['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="sort_order">Sort Order</label>
                    <input type="number" id="sort_order" name="sort_order" class="form-control"
                           value="<?php echo e($art['sort_order'] ?? $_POST['sort_order'] ?? '0'); ?>">
                    <p class="form-help">Lower numbers appear first</p>
                </div>

                <div class="form-group">
                    <label class="form-label">&nbsp;</label>
                    <label class="form-label" style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding-top: 0.5rem;">
                        <input type="checkbox" name="is_large" value="1"
                               <?php echo ($art['is_large'] ?? false) ? 'checked' : ''; ?>>
                        Display as large (spans 2 columns)
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Image <?php echo $isEdit ? '' : '*'; ?></label>
                <label class="file-upload" for="image">
                    <input type="file" id="image" name="image" accept="image/*" <?php echo $isEdit ? '' : 'required'; ?>>
                    <div class="file-upload-icon">
                        <i class="bi bi-cloud-upload"></i>
                    </div>
                    <p class="file-upload-text">
                        <span>Click to upload</span> or drag and drop<br>
                        PNG, JPG, GIF, WebP (max 10MB)
                    </p>
                    <?php if ($art && $art['image_path']): ?>
                    <div class="file-preview">
                        <img src="../<?php echo e($art['image_path']); ?>" alt="Current image">
                    </div>
                    <?php endif; ?>
                </label>
            </div>

            <div class="action-buttons">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check"></i>
                    <?php echo $isEdit ? 'Update Artwork' : 'Create Artwork'; ?>
                </button>
                <a href="art.php" class="btn btn-secondary">Cancel</a>
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
document.getElementById('image').addEventListener('change', function(e) {
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
