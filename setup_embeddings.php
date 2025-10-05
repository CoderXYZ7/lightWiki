<?php
// Script to generate embeddings for all pages in LiteWiki database
require_once 'core/db.php';

class EmbeddingGenerator
{
    private $db;
    private $pythonScriptPath;
    private $pythonEnv;

    public function __construct($pythonScriptPath = null, $pythonEnv = null)
    {
        $this->db = new Database();
        $this->pythonScriptPath = $pythonScriptPath ?: __DIR__ . '/lightWikiBackEnd/lib3d.py';
        $this->pythonEnv = $pythonEnv ?: __DIR__ . '/lightWikiBackEnd/lightwiki_env/bin/python';
        
        if (!file_exists($this->pythonScriptPath)) {
            throw new Exception("Python script not found: {$this->pythonScriptPath}");
        }
    }

    public function generateAllEmbeddings($batchSize = 10)
    {
        // Get all pages without embeddings or with NULL embeddings
        $sql = "SELECT id, title, content FROM pages WHERE embedding IS NULL OR embedding = ''";
        $pages = $this->db->fetchAll($sql);

        if (empty($pages)) {
            echo "No pages need embeddings. All pages are up to date.\n";
            return;
        }

        echo "Found " . count($pages) . " pages without embeddings.\n";
        echo "Starting embedding generation...\n\n";

        $successCount = 0;
        $errorCount = 0;
        $totalPages = count($pages);

        foreach ($pages as $index => $page) {
            try {
                $this->generateEmbeddingForPage($page['id'], $page['title'], $page['content']);
                $successCount++;
                echo "[" . ($index + 1) . "/$totalPages] ✓ Generated embedding for: " . substr($page['title'], 0, 50) . "\n";
                
                // Small delay to avoid overwhelming ollama
                if (($index + 1) % $batchSize === 0) {
                    echo "  → Processed $batchSize pages, pausing for 2 seconds...\n";
                    sleep(2);
                }
                
            } catch (Exception $e) {
                $errorCount++;
                echo "[" . ($index + 1) . "/$totalPages] ✗ Error for '{$page['title']}': " . $e->getMessage() . "\n";
            }
        }

        echo "\n" . str_repeat("=", 60) . "\n";
        echo "Embedding generation completed:\n";
        echo "- Success: $successCount pages\n";
        echo "- Errors: $errorCount pages\n";
        echo "- Total: $totalPages pages\n";
        echo str_repeat("=", 60) . "\n";
    }

    public function updateAllEmbeddings($force = false)
    {
        // Get all pages (or only those needing update)
        if ($force) {
            $sql = "SELECT id, title, content FROM pages";
            echo "Force update mode: regenerating ALL embeddings...\n";
        } else {
            $sql = "SELECT id, title, content FROM pages WHERE embedding IS NULL OR embedding = ''";
            echo "Update mode: generating missing embeddings...\n";
        }
        
        $pages = $this->db->fetchAll($sql);
        
        if (empty($pages)) {
            echo "No pages to process.\n";
            return;
        }

        $this->generateAllEmbeddings(10);
    }

    private function generateEmbeddingForPage($pageId, $title, $content)
    {
        // Prepare text for embedding (title + content)
        $text = $title . "\n\n" . $content;
        
        // Clean text: remove excessive whitespace and special characters
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        // Limit text length (ollama might have limits)
        $maxLength = 8000; // Adjust based on your model
        if (strlen($text) > $maxLength) {
            $text = substr($text, 0, $maxLength);
        }
        
        // Escape text for shell
        $escapedText = escapeshellarg($text);
        
        // Call Python script to generate blob
        $command = "{$this->pythonEnv} {$this->pythonScriptPath} get_blob {$escapedText} 2>&1";
        $output = shell_exec($command);
        
        if ($output === null || $output === '') {
            throw new Exception("Python script returned empty output");
        }
        
        // Check for Python errors
        if (strpos($output, 'Error') !== false || strpos($output, 'Traceback') !== false) {
            throw new Exception("Python error: " . substr($output, 0, 200));
        }
        
        // The output should be the raw binary blob
        $blob = $output;
        
        // Validate blob (should start with 4-byte length header)
        if (strlen($blob) < 4) {
            throw new Exception("Invalid blob: too short");
        }
        
        // Update database with the blob
        $this->db->update("pages", $pageId, ["embedding" => $blob]);
        
        return true;
    }

    public function generateEmbeddingForSinglePage($pageId)
    {
        $page = $this->db->fetch("SELECT id, title, content FROM pages WHERE id = ?", [$pageId]);
        
        if (!$page) {
            throw new Exception("Page not found with ID: $pageId");
        }
        
        echo "Generating embedding for page: {$page['title']}\n";
        $this->generateEmbeddingForPage($page['id'], $page['title'], $page['content']);
        echo "✓ Embedding generated successfully!\n";
    }

    public function getEmbeddingStats()
    {
        $total = $this->db->fetch("SELECT COUNT(*) as count FROM pages")['count'];
        $withEmbedding = $this->db->fetch("SELECT COUNT(*) as count FROM pages WHERE embedding IS NOT NULL AND embedding != ''")['count'];
        $withoutEmbedding = $total - $withEmbedding;
        
        echo "\nEmbedding Statistics:\n";
        echo str_repeat("-", 40) . "\n";
        echo "Total pages:              $total\n";
        echo "With embeddings:          $withEmbedding (" . round(($withEmbedding/$total)*100, 1) . "%)\n";
        echo "Without embeddings:       $withoutEmbedding (" . round(($withoutEmbedding/$total)*100, 1) . "%)\n";
        echo str_repeat("-", 40) . "\n";
    }
}

// ============================================================
// Main execution
// ============================================================

try {
    $generator = new EmbeddingGenerator();
    
    // Parse command line arguments
    $command = $argv[1] ?? 'generate';
    
    switch ($command) {
        case 'generate':
            // Generate embeddings for pages that don't have them
            $generator->updateAllEmbeddings(false);
            break;
            
        case 'regenerate':
            // Force regenerate ALL embeddings
            $generator->updateAllEmbeddings(true);
            break;
            
        case 'stats':
            // Show embedding statistics
            $generator->getEmbeddingStats();
            break;
            
        case 'single':
            // Generate embedding for a single page
            if (!isset($argv[2])) {
                echo "Usage: php generate_embeddings.php single <page_id>\n";
                exit(1);
            }
            $pageId = intval($argv[2]);
            $generator->generateEmbeddingForSinglePage($pageId);
            break;
            
        default:
            echo "Unknown command: $command\n\n";
            echo "Available commands:\n";
            echo "  generate      - Generate embeddings for pages without them\n";
            echo "  regenerate    - Force regenerate ALL embeddings\n";
            echo "  stats         - Show embedding statistics\n";
            echo "  single <id>   - Generate embedding for a single page\n";
            exit(1);
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>