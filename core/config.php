<?php
// LiteWiki Configuration

$config = [
    // Site settings
    "site_title" => "LiteWiki",
    "site_description" => "A lightweight wiki framework",
    "base_url" => "http://localhost/litewiki", // Change this to your actual URL

    // Logo settings
    "logo_path" => "", // Path to your logo image (e.g., '/assets/images/logo.png')
    "logo_width" => "40px", // Logo width in CSS units
    "logo_height" => "auto", // Logo height in CSS units

    // Database settings
    "db_path" => __DIR__ . "/../storage/litewiki.db",

    // Authentication settings
    "session_name" => "litewiki_session",
    "session_lifetime" => 3600 * 24 * 7, // 7 days

    // API settings
    "api_key" => "test-api-key", // Change this to a secure key
    "api_rate_limit" => 100, // requests per hour

    // Markdown settings
    "markdown_extensions" => [
        "extra" => true, // GitHub Flavored Markdown
        "footnotes" => true,
        "tables" => true,
        "task_lists" => true,
    ],

    // Theme settings
    "theme" => "default", // Options: 'default', 'dark', 'minimal', 'vibrant', 'nature', 'corporate', 'retro', 'minimalist'
    "css_path" => "/css/style.css",
    "js_path" => "/js/main.js",

    // Upload settings
    "upload_path" => __DIR__ . "/../public/assets/",
    "max_upload_size" => 5 * 1024 * 1024, // 5MB
    "allowed_extensions" => [
        "jpg",
        "jpeg",
        "png",
        "gif",
        "svg",
        "pdf",
        "txt",
        "md",
    ],

    // Security settings
    "password_min_length" => 8,
    "csrf_token_name" => "csrf_token",

    // Feature flags
    "enable_search" => true,
    "enable_revisions" => true,
    "enable_api" => true,
    "enable_auth" => true,

    // Mermaid.js settings
    "mermaid_config" => [
        "theme" => "default",
        "startOnLoad" => true,
    ],

    // Pagination
    "items_per_page" => 20,
];

// Timezone
date_default_timezone_set("UTC");

// Error reporting (set to false in production)
ini_set("display_errors", true);
error_reporting(E_ALL);

// Return config array
return $config;
?>
