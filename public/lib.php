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
        $this->graphPath = __DIR__ . '/../lightWikiBackEnd/graph3d.json';
    }

    public function get_blobs(){
        $sql = "SELECT embedding FROM pages WHERE embedding IS NOT NULL AND embedding != ''";
        $blobs = $this->db->fetchAll($sql);

        $result = [
            "blobs" => array_map(function($row) { 
                return base64_encode($row['embedding']); 
            }, $blobs)
        ];

        return json_encode($result);
    }

    private function get_page_info($blob_b64){
        $blob = base64_decode($blob_b64);
        $sql = "SELECT p.id, p.title, p.created_at FROM pages p WHERE embedding = ?";
        $info = $this->db->fetch($sql, [$blob]); // fetch singolo, non fetchAll
        return $info;
    }

    public function create_graph(){
        $blobs_json = $this->get_blobs();
        
        // Scrivi JSON in file temporaneo
        $temp_file = tempnam(sys_get_temp_dir(), 'blobs_');
        file_put_contents($temp_file, $blobs_json);
        
        // Chiama Python
        $command = "{$this->pythonEnv} {$this->pythonScriptPath} graph_nearest " . escapeshellarg($temp_file) . " 2>&1";
        $graph = shell_exec($command);
        
        // Pulisci
        unlink($temp_file);
        
        if (empty($graph)) {
            return json_encode(['error' => 'Python script returned empty output']);
        }
        
        // Salva il grafo
        if (file_put_contents($this->graphPath, $graph)) {
            return json_encode(['success' => true, 'message' => 'Graph saved successfully']);
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
        // 1. Genera blob per il testo di ricerca
        $temp_text = tempnam(sys_get_temp_dir(), 'text_');
        file_put_contents($temp_text, $text);
        
        $command = "{$this->pythonEnv} {$this->pythonScriptPath} get_blob " . escapeshellarg($temp_text);
        $blob_b64 = shell_exec($command);
        unlink($temp_text);
        
        if (empty($blob_b64)) {
            return json_encode(['error' => 'Failed to generate embedding for search text']);
        }
        
        $blob_b64 = trim($blob_b64);
        
        // 2. Ottieni tutti i blobs
        $blobs_json = $this->get_blobs();
        
        // 3. Prepara i dati per k_nearest
        $k_nearest_data = [
            'blob' => $blob_b64,
            'k' => 5,
            'blobs_json' => json_decode($blobs_json, true)
        ];
        
        $temp_k_nearest = tempnam(sys_get_temp_dir(), 'knearest_');
        file_put_contents($temp_k_nearest, json_encode($k_nearest_data));
        
        $command = "{$this->pythonEnv} {$this->pythonScriptPath} k_nearest " . escapeshellarg($temp_k_nearest);
        $nearest_json = shell_exec($command);
        unlink($temp_k_nearest);
        
        if (empty($nearest_json)) {
            return json_encode(['error' => 'Failed to find nearest embeddings']);
        }
        
        $nearest_data = json_decode($nearest_json, true);
        
        if (!isset($nearest_data['embeddings'])) {
            return json_encode(['error' => 'Invalid response format', 'raw' => $nearest_json]);
        }
        
        // 4. Ottieni info delle pagine
        $results = [];
        foreach($nearest_data['embeddings'] as $item){
            $page_info = $this->get_page_info($item['blob']);
            if ($page_info) {
                $page_info['distance'] = $item['distance'];
                $results[] = $page_info;
            }
        }
        
        return json_encode(['success' => true, 'results' => $results]);
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
        echo $api->get_blobs();
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>