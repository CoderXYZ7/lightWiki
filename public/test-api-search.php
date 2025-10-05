<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Includi i file necessari per usare le stesse classi
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/wiki.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/markdown.php';
require_once __DIR__ . '/../core/config.php';

try {
    // Inizializza Wiki
    $db = getDBConnection();
    $wiki = new Wiki($db);
    
    // Ottieni la query dall'URL (opzionale)
    $query = $_GET['text'] ?? $_GET['q'] ?? '';
    
    // Usa il metodo searchPages della classe Wiki
    $results = $wiki->searchPages($query, []);
    
    // Restituisci i risultati in formato JSON
    echo json_encode([
        'success' => true,
        'count' => count($results),
        'query' => $query,
        'results' => $results
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'results' => []
    ]);
}
?>
