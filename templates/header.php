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
        "white" => "/css/white.css",
        "dark" => "/css/dark.css",
        "corporate" => "/css/corporate.css",
        "retro" => "/css/retro.css",
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
    <link rel="stylesheet" href="/css/theme-switcher.css">
    <script src="/js/theme-switcher.js" defer></script>
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
                    <!-- <li><a href="/?action=create">Create Page</a></li> -->
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
                            <div class="theme-option" data-theme="white">White</div>
                            <div class="theme-option" data-theme="dark">Dark</div>
                            <div class="theme-option" data-theme="corporate">Minimal</div>
                            <div class="theme-option" data-theme="retro">Retro</div>
                        </div>
                    </li>
                </ul>
            </div>
        </nav>
    </header>
    <main>
