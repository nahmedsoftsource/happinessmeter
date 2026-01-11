<?php
/**
 * Project Add/Edit with Multiple Gallery Images
 */
$isEdit = isset($_GET['id']) && is_numeric($_GET['id']);
$pageTitle = $isEdit ? 'Edit Project' : 'Add Project';
$project = null;
$galleryImages = [];

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

        // Fetch gallery images
        $stmt = db()->prepare("SELECT * FROM project_images WHERE project_id = ? ORDER BY sort_order ASC");
        $stmt->execute([$project['id']]);
        $galleryImages = $stmt->fetchAll();
    } catch (PDOException $e) {
        setFlash('error', 'Failed to load project.');
        header('Location: projects.php');
        exit;
    }
}

// Handle delete gallery image
if (isset($_GET['delete_image']) && is_numeric($_GET['delete_image']) && $isEdit) {
    if (verifyCSRF($_GET['token'] ?? '')) {
        try {
            $stmt = db()->prepare("SELECT image_path FROM project_images WHERE id = ? AND project_id = ?");
            $stmt->execute([$_GET['delete_image'], $project['id']]);
            $img = $stmt->fetch();
            if ($img) {
                deleteImage($img['image_path']);
                $stmt = db()->prepare("DELETE FROM project_images WHERE id = ?");
                $stmt->execute([$_GET['delete_image']]);
                setFlash('success', 'Image deleted.');
            }
        } catch (PDOException $e) {
            setFlash('error', 'Failed to delete image.');
        }
        header('Location: project-edit.php?id=' . $project['id']);
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

        // Handle featured image upload
        $imagePath = $project['featured_image'] ?? null;
        if (!empty($_FILES['featured_image']['tmp_name'])) {
            $upload = uploadImage($_FILES['featured_image'], 'projects');
            if ($upload['success']) {
                if ($imagePath) {
                    deleteImage($imagePath);
                }
                $imagePath = $upload['path'];
            } else {
                $errors[] = $upload['error'];
            }
        }

        // Handle PDF upload
        $pdfPath = $project['pdf_path'] ?? null;
        if (!empty($_FILES['pdf_file']['tmp_name'])) {
            $pdfUpload = uploadPDF($_FILES['pdf_file'], 'pdfs');
            if ($pdfUpload['success']) {
                if ($pdfPath && file_exists($pdfPath)) {
                    @unlink($pdfPath);
                }
                $pdfPath = $pdfUpload['path'];
            } else {
                $errors[] = $pdfUpload['error'];
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
                            tags = ?, featured_image = ?, pdf_path = ?, status = ?,
                            is_featured = ?, sort_order = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $title, $slug, $description, $category,
                        $tagsJson, $imagePath, $pdfPath, $status,
                        $isFeatured, $sortOrder, $project['id']
                    ]);
                    $projectId = $project['id'];
                    setFlash('success', 'Project updated successfully.');
                } else {
                    $stmt = db()->prepare("
                        INSERT INTO projects
                            (title, slug, description, category, tags, featured_image, pdf_path, status, is_featured, sort_order)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $title, $slug, $description, $category,
                        $tagsJson, $imagePath, $pdfPath, $status,
                        $isFeatured, $sortOrder
                    ]);
                    $projectId = db()->lastInsertId();
                    setFlash('success', 'Project created successfully.');
                }

                // Handle multiple gallery images upload
                if (!empty($_FILES['gallery_images']['tmp_name'][0])) {
                    $files = $_FILES['gallery_images'];
                    $fileCount = count($files['tmp_name']);

                    // Get current max sort order
                    $stmt = db()->prepare("SELECT MAX(sort_order) as max_order FROM project_images WHERE project_id = ?");
                    $stmt->execute([$projectId]);
                    $maxOrder = $stmt->fetch()['max_order'] ?? 0;

                    for ($i = 0; $i < $fileCount; $i++) {
                        if (!empty($files['tmp_name'][$i])) {
                            $file = [
                                'name' => $files['name'][$i],
                                'type' => $files['type'][$i],
                                'tmp_name' => $files['tmp_name'][$i],
                                'error' => $files['error'][$i],
                                'size' => $files['size'][$i]
                            ];

                            $upload = uploadImage($file, 'projects/gallery');
                            if ($upload['success']) {
                                $maxOrder++;
                                $stmt = db()->prepare("
                                    INSERT INTO project_images (project_id, image_path, alt_text, sort_order)
                                    VALUES (?, ?, ?, ?)
                                ");
                                $stmt->execute([$projectId, $upload['path'], $title . ' - Image ' . $maxOrder, $maxOrder]);
                            }
                        }
                    }
                }

                // Update sort orders if provided
                if (!empty($_POST['image_order'])) {
                    $orders = $_POST['image_order'];
                    foreach ($orders as $imageId => $order) {
                        $stmt = db()->prepare("UPDATE project_images SET sort_order = ? WHERE id = ? AND project_id = ?");
                        $stmt->execute([$order, $imageId, $projectId]);
                    }
                }

                header('Location: project-edit.php?id=' . $projectId);
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
                <label class="form-label">Featured Image (Thumbnail for homepage)</label>
                <label class="file-upload" for="featured_image">
                    <input type="file" id="featured_image" name="featured_image" accept="image/*">
                    <div class="file-upload-icon">
                        <i class="bi bi-image"></i>
                    </div>
                    <p class="file-upload-text">
                        <span>Click to upload</span> or drag and drop<br>
                        This image appears on the homepage grid
                    </p>
                    <?php if ($project && $project['featured_image']): ?>
                    <div class="file-preview">
                        <img src="../<?php echo e($project['featured_image']); ?>" alt="Current image">
                    </div>
                    <?php endif; ?>
                </label>
            </div>

            <!-- Gallery Images Section -->
            <div class="form-group">
                <label class="form-label">
                    <i class="bi bi-images"></i>
                    Gallery Images (for scrollable modal view)
                </label>
                <p class="form-help" style="margin-bottom: 1rem;">
                    Upload multiple images that will be displayed when user clicks on the project. Images will appear stacked vertically like Behance.
                </p>

                <?php if (!empty($galleryImages)): ?>
                <p class="form-help" style="margin-bottom: 0.5rem; color: var(--admin-primary);">
                    <i class="bi bi-info-circle"></i> Set order numbers (1 = top, 2 = second, etc.) - Images will appear seamlessly stacked
                </p>
                <div class="gallery-grid" id="galleryGrid">
                    <?php foreach ($galleryImages as $index => $img): ?>
                    <div class="gallery-item" data-id="<?php echo $img['id']; ?>">
                        <img src="../<?php echo e($img['image_path']); ?>" alt="<?php echo e($img['alt_text']); ?>">
                        <div class="gallery-item-actions">
                            <div class="order-input-wrapper">
                                <label>Order:</label>
                                <input type="number"
                                       name="image_order[<?php echo $img['id']; ?>]"
                                       value="<?php echo $img['sort_order']; ?>"
                                       min="1"
                                       class="order-input">
                            </div>
                            <a href="project-edit.php?id=<?php echo $project['id']; ?>&delete_image=<?php echo $img['id']; ?>&token=<?php echo generateCSRF(); ?>"
                               class="btn-delete-img" onclick="return confirm('Delete this image?');">
                                <i class="bi bi-trash"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <label class="file-upload gallery-upload" for="gallery_images">
                    <input type="file" id="gallery_images" name="gallery_images[]" accept="image/*" multiple>
                    <div class="file-upload-icon">
                        <i class="bi bi-plus-circle"></i>
                    </div>
                    <p class="file-upload-text">
                        <span>Add Gallery Images</span><br>
                        Select multiple images at once (PNG, JPG, WebP)
                    </p>
                </label>
                <div id="galleryPreview" class="gallery-preview"></div>
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

<style>
/* Gallery Grid */
.gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.gallery-item {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    background: var(--admin-bg);
    border: 1px solid var(--admin-border);
}

.gallery-item img {
    width: 100%;
    height: 120px;
    object-fit: cover;
    display: block;
}

.gallery-item-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem;
    background: rgba(0, 0, 0, 0.7);
}

.order-input-wrapper {
    display: flex;
    align-items: center;
    gap: 0.375rem;
}

.order-input-wrapper label {
    font-size: 0.7rem;
    color: var(--admin-text-muted);
}

.order-input {
    width: 50px;
    padding: 0.25rem 0.375rem;
    font-size: 0.8rem;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 4px;
    color: var(--admin-text);
    text-align: center;
}

.order-input:focus {
    outline: none;
    border-color: var(--admin-primary);
    background: rgba(255,255,255,0.15);
}

.btn-delete-img {
    color: #ef4444;
    padding: 0.25rem;
    border-radius: 4px;
    transition: all 0.2s;
}

.btn-delete-img:hover {
    background: rgba(239, 68, 68, 0.2);
}

.gallery-upload {
    border-style: dashed;
    background: transparent;
}

.gallery-preview {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 0.5rem;
    margin-top: 1rem;
}

.gallery-preview-item {
    position: relative;
    border-radius: 6px;
    overflow: hidden;
}

.gallery-preview-item img {
    width: 100%;
    height: 80px;
    object-fit: cover;
    display: block;
}

.gallery-preview-item .remove-preview {
    position: absolute;
    top: 4px;
    right: 4px;
    width: 20px;
    height: 20px;
    background: rgba(0,0,0,0.7);
    border: none;
    border-radius: 50%;
    color: white;
    cursor: pointer;
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>

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

// Preview featured image
document.getElementById('featured_image').addEventListener('change', function(e) {
    const preview = this.parentElement.querySelector('.file-preview');
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            if (!preview) {
                const div = document.createElement('div');
                div.className = 'file-preview';
                div.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
                document.getElementById('featured_image').parentElement.appendChild(div);
            } else {
                preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
            }
        };
        reader.readAsDataURL(this.files[0]);
    }
});

// Preview multiple gallery images
document.getElementById('gallery_images').addEventListener('change', function(e) {
    const previewContainer = document.getElementById('galleryPreview');
    previewContainer.innerHTML = '';

    if (this.files) {
        Array.from(this.files).forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'gallery-preview-item';
                div.innerHTML = `
                    <img src="${e.target.result}" alt="Preview ${index + 1}">
                `;
                previewContainer.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
