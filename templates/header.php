<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(
        $config["site_title"],
    ); ?> - <?php echo isset($pageTitle)
     ? htmlspecialchars($pageTitle)
     : "Home"; ?></title>
    <?php
    // Dynamically set CSS path based on theme
    $themeCss = [
        "default" => "/css/style.css",
        "dark" => "/css/dark.css",
        "minimal" => "/css/minimal.css",
        "vibrant" => "/css/vibrant.css",
        "nature" => "/css/nature.css",
        "corporate" => "/css/corporate.css",
        "retro" => "/css/retro.css",
        "minimalist" => "/css/minimalist.css",
    ];
    $currentCss = isset($themeCss[$config["theme"]])
        ? $themeCss[$config["theme"]]
        : "/css/style.css";
    ?>
    <link rel="stylesheet" href="<?php echo $currentCss; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/mermaid@10.6.1/dist/mermaid.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-core.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism.min.css">
    <script>
        // Theme switcher functionality
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.getElementById('themeToggle');
            const themeMenu = document.getElementById('themeMenu');

            if (themeToggle && themeMenu) {
                themeToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    themeMenu.classList.toggle('show');
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!themeToggle.contains(e.target)) {
                        themeMenu.classList.remove('show');
                    }
                });
            }
        });
    </script>
</head>
<body>
    <header>
        <nav>
            <div class="nav-container">
                <div class="logo-title">
                    <?php if (
                        isset($config["logo_path"]) &&
                        !empty($config["logo_path"])
                    ): ?>
                        <a href="/" class="logo-link">
                            <img src="<?php echo htmlspecialchars(
                                $config["logo_path"],
                            ); ?>"
                                 alt="<?php echo htmlspecialchars(
                                     $config["site_title"],
                                 ); ?> Logo"
                                 class="site-logo">
                            <span class="site-title"><?php echo htmlspecialchars(
                                $config["site_title"],
                            ); ?></span>
                        </a>
                    <?php else: ?>
                        <h1><a href="/"><?php echo htmlspecialchars(
                            $config["site_title"],
                        ); ?></a></h1>
                    <?php endif; ?>
                </div>
                <ul>
                    <li><a href="/?action=view&page=Home">Home</a></li>
                    <li><a href="/?action=list">All Pages</a></li>
                    <li><a href="/?action=search">Search</a></li>
                    <?php if ($auth->isLoggedIn()): ?>
                        <li><a href="/?action=create">Create Page</a></li>
                        <li>Hello, <?php echo htmlspecialchars(
                            $auth->getCurrentUser()["username"],
                        ); ?>!</li>
                        <li><a href="/?action=logout">Logout</a></li>
                    <?php else: ?>
                        <li><a href="/?action=login">Login</a></li>
                        <li><a href="/?action=register">Register</a></li>
                    <?php endif; ?>
                    <li class="theme-switcher">
                        <div class="theme-dropdown">
                            <button class="theme-toggle" id="themeToggle">
                                <i class="fas fa-palette"></i>
                                <span>Theme</span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="theme-menu" id="themeMenu">
                                <a href="?theme=default" class="theme-option <?php echo $config[
                                    "theme"
                                ] === "default"
                                    ? "active"
                                    : ""; ?>">
                                    <span class="theme-preview default"></span>
                                    Default
                                </a>
                                <a href="?theme=dark" class="theme-option <?php echo $config[
                                    "theme"
                                ] === "dark"
                                    ? "active"
                                    : ""; ?>">
                                    <span class="theme-preview dark"></span>
                                    Dark
                                </a>
                                <a href="?theme=minimal" class="theme-option <?php echo $config[
                                    "theme"
                                ] === "minimal"
                                    ? "active"
                                    : ""; ?>">
                                    <span class="theme-preview minimal"></span>
                                    Minimal
                                </a>
                                <a href="?theme=vibrant" class="theme-option <?php echo $config[
                                    "theme"
                                ] === "vibrant"
                                    ? "active"
                                    : ""; ?>">
                                    <span class="theme-preview vibrant"></span>
                                    Vibrant
                                </a>
                                <a href="?theme=nature" class="theme-option <?php echo $config[
                                    "theme"
                                ] === "nature"
                                    ? "active"
                                    : ""; ?>">
                                    <span class="theme-preview nature"></span>
                                    Nature
                                </a>
                                <a href="?theme=corporate" class="theme-option <?php echo $config[
                                    "theme"
                                ] === "corporate"
                                    ? "active"
                                    : ""; ?>">
                                    <span class="theme-preview corporate"></span>
                                    Corporate
                                </a>
                                <a href="?theme=retro" class="theme-option <?php echo $config[
                                    "theme"
                                ] === "retro"
                                    ? "active"
                                    : ""; ?>">
                                    <span class="theme-preview retro"></span>
                                    Retro
                                </a>
                                <a href="?theme=minimalist" class="theme-option <?php echo $config[
                                    "theme"
                                ] === "minimalist"
                                    ? "active"
                                    : ""; ?>">
                                    <span class="theme-preview minimalist"></span>
                                    Minimalist
                                </a>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </nav>
    </header>
    <main>
