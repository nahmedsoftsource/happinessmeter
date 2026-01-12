<?php
require_once __DIR__ . '/../config/config.php';

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$siteName = getSetting('site_name', 'Josh Warner');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle ?? $siteName . ' - Product Designer & Artist'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Source+Code+Pro:wght@300;400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <?php if (isset($extraCSS)): ?>
        <?php foreach ($extraCSS as $css): ?>
            <link rel="stylesheet" href="css/<?php echo e($css); ?>.css">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body<?php echo isset($bodyClass) ? ' class="' . e($bodyClass) . '"' : ''; ?>>
    <!-- Header Navigation -->
    <header>
        <div class="header-left">
            <div class="logo">
                <div class="logo-circle"><?php echo e(substr($siteName, 0, 1) . substr(strrchr($siteName, ' ') ?: $siteName, 1, 1)); ?></div>
                <span><?php echo e($siteName); ?></span>
            </div>
        </div>
        <div class="header-center">
            <nav>
                <a href="index.php"<?php echo $currentPage === 'index' ? ' class="active"' : ''; ?>>Projects</a>
                <a href="art.php"<?php echo $currentPage === 'art' ? ' class="active"' : ''; ?>>Art</a>
                <a href="info.html"<?php echo $currentPage === 'info' ? ' class="active"' : ''; ?>>Info</a>
            </nav>
        </div>
        <div class="header-right">
            <a href="contact.html" class="contact-btn<?php echo $currentPage === 'contact' ? ' active' : ''; ?>">Contact</a>
        </div>
    </header>
