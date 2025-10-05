<?php
// ============================================================================
// generate_embeddings.php - Sistema ottimizzato per generazione embeddings
// ============================================================================

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

    /**
     * Esegue Python con gestione errori ottimizzata
     */
    private function execPython($command, $stdinData = null)
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];
        
        $fullCommand = "{$this->pythonEnv} {$this->pythonScriptPath} {$command}";
        $process = proc_open($fullCommand, $descriptors, $pipes);
        
        if (!is_resource($process)) {
            throw new Exception('Failed to start Python process');
        }
        
        if ($stdinData !== null) {
            fwrite($pipes[0], $stdinData);
        }
        fclose($pipes[0]);
        
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        
        fclose($pipes[1]);
        fclose($pipes[2]);
        
        $returnCode = proc_close($process);
        
        if ($returnCode !== 0) {
            throw new Exception("Python error (code {$returnCode}): {$errors}");
        }
        
        return trim($output);
    }

    /**
     * Genera embedding per una singola pagina - CORRETTO
     */
    private function generateEmbeddingForPage($pageId, $title, $content)
    {
        // Prepara testo per embedding
        $text = $title . "\n\n" . $content;
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        // Limita lunghezza
        $maxLength = 8000;
        if (strlen($text) > $maxLength) {
            $text = substr($text, 0, $maxLength);
        }
        
        // Chiama Python tramite stdin per evitare problemi con shell
        $inputData = json_encode(['text' => $text]);
        
        try {
            // Python ritorna base64
            $blobBase64 = $this->execPython('get_blob_stdin', $inputData);
            
            // Decodifica base64 -> blob binario
            $blob = base64_decode($blobBase64, true);
            
            if ($blob === false) {
                throw new Exception("Invalid base64 from Python");
            }
            
            if (strlen($blob) < 4) {
                throw new Exception("Blob too short: " . strlen($blob) . " bytes");
            }
            
            // Salva blob binario nel database
            $sql = "UPDATE pages SET embedding = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(1, $blob, SQLITE3_BLOB);
            $stmt->bindValue(2, $pageId, SQLITE3_INTEGER);
            
            if (!$stmt->execute()) {
                throw new Exception("Database update failed");
            }
            
            return true;
            
        } catch (Exception $e) {
            throw new Exception("Embedding generation failed: " . $e->getMessage());
        }
    }

    /**
     * Genera embeddings per tutte le pagine - BATCH OTTIMIZZATO
     */
    public function generateAllEmbeddings($batchSize = 20)
    {
        $sql = "SELECT id, title, content FROM pages WHERE embedding IS NULL OR embedding = ''";
        $pages = $this->db->fetchAll($sql);

        if (empty($pages)) {
            echo "✓ No pages need embeddings. All pages are up to date.\n";
            return;
        }

        $total = count($pages);
        echo "Found {$total} pages without embeddings.\n";
        echo "Starting embedding generation...\n";
        echo str_repeat("=", 60) . "\n\n";

        $success = 0;
        $errors = 0;
        $startTime = time();

        foreach ($pages as $index => $page) {
            $pageNum = $index + 1;
            
            try {
                $this->generateEmbeddingForPage($page['id'], $page['title'], $page['content']);
                $success++;
                
                $titleShort = substr($page['title'], 0, 45);
                echo "[{$pageNum}/{$total}] ✓ {$titleShort}\n";
                
                // Pausa ogni batch per non sovraccaricare Ollama
                if ($pageNum % $batchSize === 0) {
                    $elapsed = time() - $startTime;
                    $rate = round($pageNum / $elapsed, 2);
                    echo "  → Batch complete. Speed: {$rate} pages/sec\n\n";
                    sleep(1);
                }
                
            } catch (Exception $e) {
                $errors++;
                echo "[{$pageNum}/{$total}] ✗ {$page['title']}\n";
                echo "  Error: " . $e->getMessage() . "\n\n";
            }
        }

        $totalTime = time() - $startTime;
        $avgRate = round($total / max($totalTime, 1), 2);

        echo "\n" . str_repeat("=", 60) . "\n";
        echo "EMBEDDING GENERATION COMPLETED\n";
        echo str_repeat("=", 60) . "\n";
        echo "Success:      {$success} pages\n";
        echo "Errors:       {$errors} pages\n";
        echo "Total:        {$total} pages\n";
        echo "Time:         {$totalTime} seconds\n";
        echo "Avg Speed:    {$avgRate} pages/sec\n";
        echo str_repeat("=", 60) . "\n";
    }

    public function updateAllEmbeddings($force = false)
    {
        if ($force) {
            echo "FORCE MODE: Regenerating ALL embeddings...\n\n";
            $sql = "UPDATE pages SET embedding = NULL";
            $this->db->execute($sql);
        } else {
            echo "UPDATE MODE: Generating missing embeddings...\n\n";
        }
        
        $this->generateAllEmbeddings(20);
    }

    public function generateEmbeddingForSinglePage($pageId)
    {
        $page = $this->db->fetch("SELECT id, title, content FROM pages WHERE id = ?", [$pageId]);
        
        if (!$page) {
            throw new Exception("Page not found with ID: {$pageId}");
        }
        
        echo "Generating embedding for: {$page['title']}\n";
        $this->generateEmbeddingForPage($page['id'], $page['title'], $page['content']);
        echo "✓ Embedding generated successfully!\n";
    }

    public function getEmbeddingStats()
    {
        $total = $this->db->fetch("SELECT COUNT(*) as count FROM pages")['count'];
        $withEmbedding = $this->db->fetch(
            "SELECT COUNT(*) as count FROM pages WHERE embedding IS NOT NULL AND embedding != ''"
        )['count'];
        $withoutEmbedding = $total - $withEmbedding;
        
        $pctWith = $total > 0 ? round(($withEmbedding / $total) * 100, 1) : 0;
        $pctWithout = $total > 0 ? round(($withoutEmbedding / $total) * 100, 1) : 0;
        
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "EMBEDDING STATISTICS\n";
        echo str_repeat("=", 50) . "\n";
        echo "Total pages:           {$total}\n";
        echo "With embeddings:       {$withEmbedding} ({$pctWith}%)\n";
        echo "Without embeddings:    {$withoutEmbedding} ({$pctWithout}%)\n";
        echo str_repeat("=", 50) . "\n";
    }

    public function verifyDatabase()
    {
        echo "Verifying database embeddings...\n\n";
        
        $sql = "SELECT id, title, length(embedding) as blob_size FROM pages WHERE embedding IS NOT NULL";
        $pages = $this->db->fetchAll($sql);
        
        if (empty($pages)) {
            echo "No embeddings found in database.\n";
            return;
        }
        
        $valid = 0;
        $invalid = 0;
        
        foreach ($pages as $page) {
            $size = $page['blob_size'];
            if ($size > 4) {
                $valid++;
            } else {
                $invalid++;
                echo "✗ Invalid blob for page {$page['id']}: {$page['title']} (size: {$size} bytes)\n";
            }
        }
        
        echo "\n";
        echo "Valid embeddings:    {$valid}\n";
        echo "Invalid embeddings:  {$invalid}\n";
    }
}

// ============================================================================
// ESECUZIONE PRINCIPALE
// ============================================================================

try {
    $generator = new EmbeddingGenerator();
    
    $command = $argv[1] ?? 'help';
    
    switch ($command) {
        case 'generate':
            $generator->updateAllEmbeddings(false);
            break;
            
        case 'regenerate':
            $generator->updateAllEmbeddings(true);
            break;
            
        case 'stats':
            $generator->getEmbeddingStats();
            break;
            
        case 'single':
            if (!isset($argv[2])) {
                echo "Usage: php generate_embeddings.php single <page_id>\n";
                exit(1);
            }
            $pageId = intval($argv[2]);
            $generator->generateEmbeddingForSinglePage($pageId);
            break;
            
        case 'verify':
            $generator->verifyDatabase();
            break;
            
        case 'help':
        default:
            echo "\n";
            echo "LiteWiki Embedding Generator\n";
            echo str_repeat("=", 50) . "\n";
            echo "Usage: php generate_embeddings.php [command]\n\n";
            echo "Commands:\n";
            echo "  generate      - Generate missing embeddings\n";
            echo "  regenerate    - Force regenerate ALL embeddings\n";
            echo "  stats         - Show embedding statistics\n";
            echo "  single <id>   - Generate embedding for one page\n";
            echo "  verify        - Verify database embeddings\n";
            echo "  help          - Show this help message\n";
            echo str_repeat("=", 50) . "\n";
            echo "\n";
            break;
    }
    
} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n\n";
    exit(1);
}