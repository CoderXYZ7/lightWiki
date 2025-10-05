<?php
// ============================================================================
// embedding_api.php - API REST per embeddings
// ============================================================================

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';

class EmbeddingAPI {
    private $db;
    private $pythonScriptPath;
    private $pythonEnv;
    private $graphPath;
    
    public function __construct($dbPath) {
        $this->db = new Database($dbPath);
        $this->pythonScriptPath = __DIR__ . '/../lightWikiBackEnd/lib3d.py';
        $this->pythonEnv = __DIR__ . '/../lightWikiBackEnd/lightwiki_env/bin/python';
        $this->graphPath = __DIR__ . '/assets/graph3d.json';
        
        if (!file_exists($this->pythonScriptPath)) {
            throw new Exception("Python script not found");
        }
    }
    
    /**
     * Esegue Python con stdin/stdout
     */
    private function execPython($command, $stdinData = null) {
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
            throw new Exception("Python error: {$errors}");
        }
        
        if (empty($output)) {
            throw new Exception("Python returned empty output");
        }
        
        return $output;
    }
    
    /**
     * Ottiene tutti i blob dal database come array base64
     */
    public function get_blobs() {
        $sql = "SELECT embedding FROM pages WHERE embedding IS NOT NULL AND embedding != ''";
        $blobs = $this->db->fetchAll($sql);
        
        if (empty($blobs)) {
            return [];
        }
        
        // Blob binario -> base64 per trasmissione
        return array_map(function($row) {
            return base64_encode($row['embedding']);
        }, $blobs);
    }
    
    /**
     * Ottiene info pagina da blob binario
     */
    private function get_page_info($blob) {
        $sql = "SELECT p.id, p.title, p.created_at, p.content, p.updated_at, p.created_by 
                FROM pages p WHERE embedding = ?";
        return $this->db->fetch($sql, [$blob]);
    }
    
    /**
     * Crea grafo 3D
     */
    public function create_graph() {
        try {
            $blobs = $this->get_blobs();
            
            if (empty($blobs)) {
                return json_encode(['error' => 'No embeddings found']);
            }
            
            if (count($blobs) < 2) {
                return json_encode(['error' => 'Need at least 2 embeddings for graph']);
            }
            
            // Invia blob a Python via stdin
            $output = $this->execPython('graph_nearest -', json_encode($blobs));
            
            // Salva grafo
            if (file_put_contents($this->graphPath, $output)) {
                return json_encode([
                    'success' => true,
                    'message' => 'Graph created successfully',
                    'nodes_count' => count($blobs),
                    'path' => $this->graphPath
                ]);
            } else {
                return json_encode(['error' => 'Failed to save graph file']);
            }
            
        } catch (Exception $e) {
            return json_encode(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Ottiene grafo salvato
     */
    public function get_graph() {
        if (!file_exists($this->graphPath)) {
            return json_encode([
                'error' => 'Graph file not found. Run create-graph first.'
            ]);
        }
        
        return file_get_contents($this->graphPath);
    }
    
    /**
     * Ricerca semantica AI
     */
    public function search($text) {
        try {
            if (empty($text)) {
                return json_encode(['error' => 'Search text is empty']);
            }
            
            // 1. Genera embedding per query
            $inputData = json_encode(['text' => $text]);
            $blobBase64 = trim($this->execPython('get_blob_stdin', $inputData));
            
            // 2. Ottieni tutti i blob
            $blobs = $this->get_blobs();
            
            if (empty($blobs)) {
                return json_encode(['error' => 'No embeddings in database']);
            }
            
            // 3. Trova k nearest tramite Python
            $searchInput = json_encode([
                'query_blob' => $blobBase64,
                'blobs' => $blobs,
                'k' => 5
            ]);
            
            $output = $this->execPython('k_nearest_from_stdin', $searchInput);
            $nearest = json_decode($output, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return json_encode([
                    'error' => 'Failed to parse Python output',
                    'json_error' => json_last_error_msg()
                ]);
            }
            
            // 4. Recupera info pagine
            $results = [];
            foreach ($nearest as $item) {
                $blobRaw = base64_decode($item['blobs']);
                $pageInfo = $this->get_page_info($blobRaw);
                
                if ($pageInfo) {
                    $pageInfo['distance'] = $item['distance'];
                    $pageInfo['similarity'] = round((1 / (1 + $item['distance'])) * 100, 2);
                    
                    // Limita content per performance
                    $pageInfo['content'] = substr($pageInfo['content'], 0, 500);
                    
                    $results[] = $pageInfo;
                }
            }
            
            return json_encode([
                'success' => true,
                'query' => $text,
                'results' => $results,
                'count' => count($results)
            ]);
            
        } catch (Exception $e) {
            return json_encode(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Statistiche embeddings
     */
    public function get_stats() {
        try {
            $total = $this->db->fetch("SELECT COUNT(*) as count FROM pages")['count'];
            $withEmbedding = $this->db->fetch(
                "SELECT COUNT(*) as count FROM pages WHERE embedding IS NOT NULL AND embedding != ''"
            )['count'];
            
            $pct = $total > 0 ? round(($withEmbedding / $total) * 100, 1) : 0;
            
            return json_encode([
                'success' => true,
                'total_pages' => $total,
                'with_embeddings' => $withEmbedding,
                'without_embeddings' => $total - $withEmbedding,
                'percentage_complete' => $pct
            ]);
            
        } catch (Exception $e) {
            return json_encode(['error' => $e->getMessage()]);
        }
    }
}

// ============================================================================
// ROUTING API
// ============================================================================

try {
    $api = new EmbeddingAPI(__DIR__ . "/../storage/litewiki.db");
    $action = $_GET['action'] ?? '';
    
    switch($action) {
        case 'get-graph':
            echo $api->get_graph();
            break;
            
        case 'create-graph':
            echo $api->create_graph();
            break;
            
        case 'ai-search':
            $text = $_GET['q'] ?? '';
            echo $api->search($text);
            break;
            
        case 'get-blobs':
            echo json_encode([
                'success' => true,
                'blobs' => $api->get_blobs()
            ]);
            break;
            
        case 'stats':
            echo $api->get_stats();
            break;
            
        default:
            echo json_encode([
                'error' => 'Invalid action',
                'available_actions' => [
                    'get-graph',
                    'create-graph', 
                    'ai-search',
                    'get-blobs',
                    'stats'
                ]
            ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}