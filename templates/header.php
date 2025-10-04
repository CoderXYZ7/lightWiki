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
            z-index: 10000;
        }
        .theme-button {
            background: none;
            border: none;
            color: inherit;
            cursor: pointer;
            padding: 8px;
            border-radius: 4px;
            transition: background-color 0.3s;
            position: relative;
            z-index: 10001;
        }
        .theme-button:hover {
            background-color: rgba(0, 0, 0, 0.1);
        }
        .theme-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: var(--dropdown-bg, white);
            border: 1px solid var(--dropdown-border, #ddd);
            border-radius: 6px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            z-index: 10002;
            min-width: 160px;
            display: none;
            margin-top: 5px;
            overflow: hidden;
            opacity: 0;
            transform: translateY(-10px);
            transition: opacity 0.2s ease, transform 0.2s ease;
        }
        .theme-dropdown::before {
            content: '';
            position: absolute;
            top: -6px;
            right: 12px;
            width: 12px;
            height: 12px;
            background: var(--dropdown-bg, white);
            border: 1px solid var(--dropdown-border, #ddd);
            border-right: none;
            border-bottom: none;
            transform: rotate(45deg);
            z-index: 10003;
        }
        .theme-dropdown.show {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }
        .theme-option {
            padding: 12px 16px;
            cursor: pointer;
            border-bottom: 1px solid var(--option-border, #f0f0f0);
            transition: all 0.2s ease;
            position: relative;
            z-index: 10004;
            color: var(--option-text, inherit);
        }
        .theme-option:last-child {
            border-bottom: none;
        }
        .theme-option:hover {
            background-color: var(--option-hover, #f8f9fa);
            transform: translateX(2px);
        }
        .theme-option.active {
            background-color: #007cba;
            color: white;
            font-weight: 500;
        }
        .theme-option.active:hover {
            background-color: #0056b3;
        }

        /* Enhanced dropdown visibility */
        .theme-dropdown {
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        /* Cross-theme compatibility */
        .theme-dropdown {
            color: #333;
            background: rgba(255, 255, 255, 0.95) !important;
            border: 1px solid rgba(0, 0, 0, 0.15) !important;
        }

        .theme-dropdown::before {
            background: rgba(255, 255, 255, 0.95) !important;
            border: 1px solid rgba(0, 0, 0, 0.15) !important;
        }

        .theme-option {
            color: #333 !important;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1) !important;
        }

        .theme-option:hover {
            background-color: rgba(0, 0, 0, 0.05) !important;
        }

        /* Dark theme adjustments */
        [data-current-theme="dark"] .theme-dropdown,
        .theme-dropdown.theme-dark {
            background: rgba(40, 44, 52, 0.95) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            color: #f8f9fa !important;
        }

        [data-current-theme="dark"] .theme-dropdown::before,
        .theme-dropdown.theme-dark::before {
            background: rgba(40, 44, 52, 0.95) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
        }

        [data-current-theme="dark"] .theme-option,
        .theme-dropdown.theme-dark .theme-option {
            color: #f8f9fa !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
        }

        [data-current-theme="dark"] .theme-option:hover,
        .theme-dropdown.theme-dark .theme-option:hover {
            background-color: rgba(255, 255, 255, 0.1) !important;
        }

        /* Ensure dropdown appears above common elements */
        nav {
            position: relative;
            z-index: 1000;
        }
        header {
            position: relative;
            z-index: 1000;
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
