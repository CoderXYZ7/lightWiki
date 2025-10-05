<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/wiki.php';

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
    }
    
    public function get_blobs(){
        $sql = "SELECT embedding FROM pages WHERE embedding IS NOT NULL AND embedding != ''";
        $blobs = $this->db->fetchAll($sql);
        $arr = array_map(function($row) {
            return base64_encode($row['embedding']);
        }, $blobs);
        
        return $arr; // Ritorna array, non JSON
    }
    
    private function get_page_info($blob){
        $sql = "SELECT p.id, p.title, p.created_at, p.content, p.updated_at, p.created_by FROM pages p WHERE embedding = ?";
        $info = $this->db->fetch($sql, [$blob]);
        return $info;
    }
    
    public function create_graph(){
        $blobs = $this->get_blobs();
        
        if (empty($blobs)) {
            return json_encode(['error' => 'No embeddings found in database']);
        }
        
        // Passa JSON via stdin a Python
        $blobs_json = json_encode($blobs);
        
        $descriptors = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w']  // stderr
        ];
        
        $command = "{$this->pythonEnv} {$this->pythonScriptPath} graph_nearest -";
        $process = proc_open($command, $descriptors, $pipes);
        
        if (!is_resource($process)) {
            return json_encode(['error' => 'Failed to start Python process']);
        }
        
        // Scrivi JSON su stdin
        fwrite($pipes[0], $blobs_json);
        fclose($pipes[0]);
        
        // Leggi output
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        
        fclose($pipes[1]);
        fclose($pipes[2]);
        
        $return_code = proc_close($process);
        
        if ($return_code !== 0) {
            return json_encode([
                'error' => 'Python script failed',
                'stderr' => $errors,
                'return_code' => $return_code
            ]);
        }
        
        if (empty($output)) {
            return json_encode([
                'error' => 'Python script returned empty output',
                'stderr' => $errors
            ]);
        }
        
        // Salva il grafo
        if (file_put_contents($this->graphPath, $output)) {
            return json_encode([
                'success' => true,
                'message' => 'Graph created successfully',
                'nodes_count' => count($blobs)
            ]);
        } else {
            return json_encode(['error' => 'Failed to save graph file']);
        }
    }
    
    public function get_graph(){
        if (!file_exists($this->graphPath)) {
            return json_encode(['error' => 'Graph file not found. Run create-graph first.']);
        }
        return file_get_contents($this->graphPath);
    }
    
    public function search($text){
        // 1. Ottieni blob per il testo di ricerca
        $command = escapeshellcmd("{$this->pythonEnv} {$this->pythonScriptPath}") . " get_blob " . escapeshellarg($text);
        $blob_b64 = trim(shell_exec($command));
        
        if (empty($blob_b64)) {
            return json_encode(['error' => 'Failed to generate embedding for search text']);
        }
        
        // 2. Ottieni tutti i blob
        $blobs = $this->get_blobs();
        $blobs_json = json_encode($blobs);
        
        // 3. Trova k nearest
        $command = escapeshellcmd("{$this->pythonEnv} {$this->pythonScriptPath}") 
                  . " k_nearest " 
                  . escapeshellarg($blob_b64) 
                  . " " . escapeshellarg($blobs_json) 
                  . " 5";
        
        $nearest_json = shell_exec($command);
        $nearest = json_decode($nearest_json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return json_encode([
                'error' => 'Failed to parse Python output',
                'output' => $nearest_json
            ]);
        }
        
        // 4. Recupera info pagine
        $infos = [];
        foreach($nearest as $item) {
            $blob_raw = base64_decode($item['blobs']);
            $page_info = $this->get_page_info($blob_raw);
            if ($page_info) {
                $page_info['distance'] = $item['distance'];
                $infos[] = $page_info;
            }
        }
        
        return json_encode($infos);
    }
}

// USO DELL'API
header('Content-Type: application/json');

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
        if($text) {
            echo $api->search($text);
        } else {
            echo json_encode(['error' => 'Parameter q missing']);
        }
        break;
        
    case 'get-blobs':
        echo json_encode($api->get_blobs());
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>