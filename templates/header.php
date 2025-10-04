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
    // Get user's theme preference from session, cookie, or config
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $userTheme =
        $_SESSION["user_theme"] ??
        ($_COOKIE["theme_preference"] ?? $config["theme"]);

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
    $currentCss = isset($themeCss[$userTheme])
        ? $themeCss[$userTheme]
        : "/css/style.css";
    ?>
    <link rel="stylesheet" href="<?php echo $currentCss; ?>" id="theme-css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/mermaid@10.6.1/dist/mermaid.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-core.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism.min.css">
    <style>
        .theme-switcher {
            position: relative;
            display: inline-block;
        }
        .theme-button {
            background: none;
            border: none;
            color: inherit;
            cursor: pointer;
            padding: 8px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .theme-button:hover {
            background-color: rgba(0, 0, 0, 0.1);
        }
        .theme-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            min-width: 150px;
            display: none;
        }
        .theme-dropdown.show {
            display: block;
        }
        .theme-option {
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
            transition: background-color 0.2s;
        }
        .theme-option:last-child {
            border-bottom: none;
        }
        .theme-option:hover {
            background-color: #f5f5f5;
        }
        .theme-option.active {
            background-color: #007cba;
            color: white;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="nav-container">
                <h1><a href="/"><?php echo htmlspecialchars(
                    $config["site_title"],
                ); ?></a></h1>
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
                        <button class="theme-button" id="theme-toggle" title="Switch Theme">
                            <i class="fas fa-palette"></i>
                        </button>
                        <div class="theme-dropdown" id="theme-dropdown">
                            <div class="theme-option" data-theme="default">Default</div>
                            <div class="theme-option" data-theme="dark">Dark</div>
                            <div class="theme-option" data-theme="minimal">Minimal</div>
                            <div class="theme-option" data-theme="minimalist">Minimalist</div>
                            <div class="theme-option" data-theme="vibrant">Vibrant</div>
                            <div class="theme-option" data-theme="nature">Nature</div>
                            <div class="theme-option" data-theme="corporate">Corporate</div>
                            <div class="theme-option" data-theme="retro">Retro</div>
                        </div>
                    </li>
                </ul>
            </div>
        </nav>
    </header>
    <main>
