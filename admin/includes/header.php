<?php
require_once __DIR__ . '/../../config/config.php';

// Check login for all pages except login.php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
if ($currentPage !== 'login') {
    requireLogin();
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle ?? 'Admin Dashboard'); ?> - Portfolio Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body class="admin-body">
    <?php if ($currentPage !== 'login'): ?>
    <!-- Admin Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <a href="index.php" class="sidebar-logo">
                <i class="bi bi-grid-3x3-gap-fill"></i>
                <span>Portfolio Admin</span>
            </a>
        </div>

        <nav class="sidebar-nav">
            <ul>
                <li>
                    <a href="index.php"<?php echo $currentPage === 'index' ? ' class="active"' : ''; ?>>
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="projects.php"<?php echo in_array($currentPage, ['projects', 'project-edit']) ? ' class="active"' : ''; ?>>
                        <i class="bi bi-folder"></i>
                        <span>Projects</span>
                    </a>
                </li>
                <li>
                    <a href="art.php"<?php echo in_array($currentPage, ['art', 'art-edit']) ? ' class="active"' : ''; ?>>
                        <i class="bi bi-palette"></i>
                        <span>Art Gallery</span>
                    </a>
                </li>
                <li>
                    <a href="skills.php"<?php echo $currentPage === 'skills' ? ' class="active"' : ''; ?>>
                        <i class="bi bi-tools"></i>
                        <span>Skills</span>
                    </a>
                </li>
                <li>
                    <a href="settings.php"<?php echo $currentPage === 'settings' ? ' class="active"' : ''; ?>>
                        <i class="bi bi-gear"></i>
                        <span>Settings</span>
                    </a>
                </li>
            </ul>
        </nav>

        <div class="sidebar-footer">
            <a href="../index.php" target="_blank" class="view-site">
                <i class="bi bi-box-arrow-up-right"></i>
                <span>View Site</span>
            </a>
            <a href="logout.php" class="logout-link">
                <i class="bi bi-box-arrow-left"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <header class="admin-header">
            <div class="header-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
                <h1 class="page-title"><?php echo e($pageTitle ?? 'Dashboard'); ?></h1>
            </div>
            <div class="header-right">
                <span class="admin-user">
                    <i class="bi bi-person-circle"></i>
                    <?php echo e($_SESSION['admin_name'] ?? 'Admin'); ?>
                </span>
            </div>
        </header>

        <div class="admin-content">
            <?php if ($flash): ?>
            <div class="alert alert-<?php echo e($flash['type']); ?>">
                <?php echo e($flash['message']); ?>
                <button class="alert-close">&times;</button>
            </div>
            <?php endif; ?>
    <?php endif; ?>
