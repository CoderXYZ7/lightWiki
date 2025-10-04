<?php
// LiteWiki Setup Script

echo "LiteWiki Setup\n";
echo "===============\n\n";

// Check PHP version
if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    die("Error: PHP 8.0 or higher is required. Current version: " . PHP_VERSION . "\n");
}
echo "✓ PHP version: " . PHP_VERSION . "\n";

// Check SQLite extension
if (!extension_loaded('sqlite3')) {
    die("Error: SQLite3 extension is required but not loaded.\n");
}
echo "✓ SQLite3 extension loaded\n";

// Check if directories exist
$requiredDirs = ['core', 'public', 'storage', 'templates', 'public/css', 'public/js', 'public/assets'];
foreach ($requiredDirs as $dir) {
    if (!is_dir($dir)) {
        die("Error: Required directory '$dir' does not exist.\n");
    }
}
echo "✓ All required directories exist\n";

// Check if core files exist
$requiredFiles = [
    'core/config.php',
    'core/db.php',
    'core/auth.php',
    'core/markdown.php',
    'core/wiki.php',
    'public/index.php',
    'public/api.php',
    'templates/header.php',
    'templates/footer.php',
    'public/css/style.css',
    'public/js/main.js'
];
foreach ($requiredFiles as $file) {
    if (!file_exists($file)) {
        die("Error: Required file '$file' does not exist.\n");
    }
}
echo "✓ All required files exist\n";

// Load configuration
$config = include 'core/config.php';
echo "✓ Configuration loaded\n";

// Initialize database
try {
    require_once 'core/db.php';
    $db = new Database($config['db_path']);
    echo "✓ Database initialized\n";
} catch (Exception $e) {
    die("Error initializing database: " . $e->getMessage() . "\n");
}

// Create default admin user
echo "\nCreating default admin user...\n";
require_once 'core/auth.php';
$auth = new Auth();

$adminUsername = 'admin';
$adminPassword = 'admin123'; // Change this in production!

$result = $auth->register($adminUsername, $adminPassword, 'admin');
if ($result['success']) {
    echo "✓ Admin user created: $adminUsername / $adminPassword\n";
    echo "  IMPORTANT: Change the default password after first login!\n";
} else {
    echo "⚠ Admin user creation failed: " . $result['message'] . "\n";
    echo "  This might be because the user already exists.\n";
}

    // Create a sample page
    echo "\nCreating sample page...\n";
    require_once 'core/wiki.php';
    $wiki = new Wiki();

    // Login as admin to create the page
    if ($auth->login($adminUsername, $adminPassword)) {
        $sampleContent = "# Welcome to LiteWiki

This is your first wiki page! LiteWiki is a lightweight wiki framework built with PHP and SQLite.

## Features

- **Markdown Support**: Write content using Markdown syntax
- **User Authentication**: Secure login and user management
- **Search**: Full-text search across all pages
- **API**: REST API for external integrations
- **Revisions**: Track changes and restore previous versions
- **Multiple Authors**: Pages can have multiple authors
- **Discoverable Control**: Pages can be hidden from search

## Getting Started

1. **Login**: Use the admin credentials above
2. **Create Pages**: Click \"Create Page\" in the navigation
3. **Edit Content**: Use Markdown for formatting
4. **Explore**: Check out the search and API features

## Example Markdown

```javascript
console.log('Hello, LiteWiki!');
```

### Mermaid Diagram

```mermaid
graph TD
    A[Start] --> B{Is it working?}
    B -->|Yes| C[Great!]
    B -->|No| D[Check setup]
```

Enjoy using LiteWiki! <i class=\"fas fa-rocket\"></i>";

        $result = $wiki->createPage('Home', $sampleContent, [], ['Admin'], true);
        if ($result['success']) {
            echo "✓ Sample page 'Home' created\n";
        } else {
            echo "⚠ Sample page creation failed: " . $result['message'] . "\n";
        }
    } else {
        echo "⚠ Could not login as admin to create sample page\n";
    }

// Setup complete
echo "\n<i class=\"fas fa-check-circle\"></i> Setup complete!\n\n";
echo "Next steps:\n";
echo "1. Point your web server to the 'public/' directory\n";
echo "2. Open your browser to the LiteWiki URL\n";
echo "3. Login with: $adminUsername / $adminPassword\n";
echo "4. Change the default password in your user settings\n";
echo "5. Update the API key in core/config.php for security\n";
echo "6. Configure your site settings as needed\n\n";

echo "For production:\n";
echo "- Set display_errors to false in core/config.php\n";
echo "- Use HTTPS\n";
echo "- Regularly backup the storage/litewiki.db file\n";
echo "- Keep PHP and dependencies updated\n\n";

echo "Documentation: Check the README.md file for more information.\n";
?>
