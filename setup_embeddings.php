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
        
        if (!file_exists($this->pythonEnv)) {
            throw new Exception("Python environment not found: {$this->pythonEnv}");
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
        
        $fullCommand = escapeshellcmd("{$this->pythonEnv} {$this->pythonScriptPath} {$command}");
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
     * Genera embedding per una singola pagina - VERSIONE MIGLIORATA
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
        
        // Verifica che il testo non sia vuoto
        if (empty($text)) {
            throw new Exception("Text content is empty for page {$pageId}");
        }
        
        // Chiama Python tramite stdin per evitare problemi con shell
        $inputData = json_encode(['text' => $text]);
        
        try {
            echo "  → Calling Python script... ";
            
            // Python ritorna base64
            $blobBase64 = $this->execPython('get_blob_stdin', $inputData);
            
            if (empty($blobBase64)) {
                throw new Exception("Empty response from Python script");
            }
            
            // Decodifica base64 -> blob binario
            $blob = base64_decode($blobBase64, true);
            
            if ($blob === false) {
                throw new Exception("Invalid base64 from Python");
            }
            
            if (strlen($blob) < 4) {
                throw new Exception("Blob too short: " . strlen($blob) . " bytes");
            }
            
            echo "✓ Got " . strlen($blob) . " bytes\n";
            
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
     * Genera embeddings per tutte le pagine - VERSIONE CORRETTA
     */
    public function generateAllEmbeddings($batchSize = 10) // Ridotto batch size per stabilità
    {
        $sql = "SELECT id, title, content FROM pages WHERE embedding IS NULL OR embedding = ''";
        $pages = $this->db->fetchAll($sql);

        if (empty($pages)) {
            echo "✓ No pages need embeddings. All pages are up to date.\n";
            return 0;
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
            $titleShort = substr($page['title'], 0, 45);
            
            echo "[{$pageNum}/{$total}] Processing: {$titleShort}\n";
            
            try {
                $this->generateEmbeddingForPage($page['id'], $page['title'], $page['content']);
                $success++;
                echo "[{$pageNum}/{$total}] ✓ Completed: {$titleShort}\n";
                
                // Pausa ogni batch per non sovraccaricare il sistema
                if ($pageNum % $batchSize === 0 && $pageNum < $total) {
                    $elapsed = time() - $startTime;
                    $rate = round($pageNum / max($elapsed, 1), 2);
                    $remaining = $total - $pageNum;
                    $eta = round($remaining / $rate);
                    echo "  → Batch complete. Speed: {$rate} pages/sec, ETA: {$eta}s\n";
                    echo "  → Taking a short break...\n";
                    sleep(2); // Pausa più lunga tra i batch
                }
                
            } catch (Exception $e) {
                $errors++;
                echo "[{$pageNum}/{$total}] ✗ FAILED: {$titleShort}\n";
                echo "  Error: " . $e->getMessage() . "\n\n";
                
                // Pausa più lunga dopo un errore
                sleep(1);
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
        
        return $success;
    }

    /**
     * Rigenera tutti gli embeddings - VERSIONE CORRETTA
     */
    public function updateAllEmbeddings($force = false)
    {
        if ($force) {
            echo "FORCE MODE: Regenerating ALL embeddings...\n\n";
            
            // Prima conta quanti record ci sono
            $totalCount = $this->db->fetch("SELECT COUNT(*) as count FROM pages")['count'];
            echo "Total pages in database: {$totalCount}\n";
            
            // Poi resetta gli embeddings
            $sql = "UPDATE pages SET embedding = NULL";
            $result = $this->db->execute($sql);
            
            echo "Reset embeddings for all pages.\n\n";
            
            // Attendi un momento per assicurarsi che il database sia aggiornato
            sleep(1);
        } else {
            echo "UPDATE MODE: Generating missing embeddings...\n\n";
        }
        
        // Ora genera gli embeddings
        return $this->generateAllEmbeddings(10);
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
            "SELECT COUNT(*) as count FROM pages WHERE embedding IS NOT NULL AND length(embedding) > 0"
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
        
        $sql = "SELECT id, title, length(embedding) as blob_size FROM pages WHERE embedding IS NOT NULL AND embedding != ''";
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
                echo "✓ Valid embedding for page {$page['id']}: {$page['title']} (size: {$size} bytes)\n";
            } else {
                $invalid++;
                echo "✗ Invalid blob for page {$page['id']}: {$page['title']} (size: {$size} bytes)\n";
            }
        }
        
        echo "\n";
        echo str_repeat("=", 50) . "\n";
        echo "VALIDATION RESULTS:\n";
        echo "Valid embeddings:    {$valid}\n";
        echo "Invalid embeddings:  {$invalid}\n";
        echo "Total checked:       " . ($valid + $invalid) . "\n";
        echo str_repeat("=", 50) . "\n";
    }
}

// ============================================================================
// ESECUZIONE PRINCIPALE - VERSIONE MIGLIORATA
// ============================================================================

try {
    // Verifica che il database esista
    if (!file_exists('core/db.php')) {
        throw new Exception("Database configuration not found. Please check your setup.");
    }
    
    $generator = new EmbeddingGenerator();
    
    $command = $argv[1] ?? 'help';
    
    switch ($command) {
        case 'generate':
            echo "Starting EMBEDDING GENERATION...\n";
            $generator->updateAllEmbeddings(false);
            break;
            
        case 'regenerate':
            echo "Starting EMBEDDING REGENERATION...\n";
            $success = $generator->updateAllEmbeddings(true);
            if ($success > 0) {
                echo "\n✓ Regeneration completed successfully!\n";
            } else {
                echo "\n⚠ No embeddings were generated. Check your data.\n";
            }
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
            
        case 'test-python':
            // Nuovo comando per testare la connessione Python
            echo "Testing Python connection...\n";
            try {
                $testText = "This is a test for embedding generation.";
                $inputData = json_encode(['text' => $testText]);
                $result = $generator->execPython('get_blob_stdin', $inputData);
                echo "✓ Python test successful!\n";
                echo "Response length: " . strlen($result) . " characters\n";
            } catch (Exception $e) {
                echo "✗ Python test failed: " . $e->getMessage() . "\n";
            }
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
            echo "  test-python   - Test Python connection\n";
            echo "  help          - Show this help message\n";
            echo str_repeat("=", 50) . "\n";
            echo "\n";
            break;
    }
    
} catch (Exception $e) {
    echo "\n✗ FATAL ERROR: " . $e->getMessage() . "\n\n";
    echo "Debugging tips:\n";
    echo "1. Check if Python environment exists: {$pythonEnv}\n";
    echo "2. Check if Python script exists: {$pythonScriptPath}\n";
    echo "3. Verify database connection\n";
    echo "4. Run 'php generate_embeddings.php test-python' to test Python\n\n";
    exit(1);
}