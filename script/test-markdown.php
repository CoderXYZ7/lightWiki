<?php
// Test script to check markdown rendering

require_once 'core/config.php';
require_once 'core/db.php';
require_once 'core/auth.php';
require_once 'core/markdown.php';
require_once 'core/wiki.php';

$config = include 'core/config.php';
$db = new Database($config['db_path']);
$auth = new Auth();
$wiki = new Wiki();
$markdown = new MarkdownProcessor();

// Get the Test page
$page = $wiki->getPage('Test');

if ($page) {
    echo "=== DATABASE CONTENT ===\n";
    echo $page['content'] . "\n\n";

    echo "=== RENDERED HTML ===\n";
    echo $page['rendered_content'] . "\n\n";

    echo "=== MARKDOWN PROCESSOR TEST ===\n";
    $testMarkdown = "# Test Header\n\n**Bold text** and *italic text*\n\n- List item 1\n- List item 2\n\n```php\necho 'test';\n```";
    echo "Input:\n" . $testMarkdown . "\n\n";
    echo "Output:\n" . $markdown->process($testMarkdown) . "\n";
} else {
    echo "Test page not found\n";
}
?>
