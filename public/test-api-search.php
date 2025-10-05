<?php
// Disabilita la visualizzazione degli errori PHP
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // Path al database SQLite
    $dbPath = __DIR__ . '/../storage/litewiki.db';

    // Verifica che il database esista
    if (!file_exists($dbPath)) {
        throw new Exception('Database not found at: ' . $dbPath);
    }

    // Connessione al database
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Ottieni la query dall'URL
    $query = $_GET['text'] ?? $_GET['q'] ?? '';

    // Query SQL - se c'è una ricerca, filtra, altrimenti prendi tutto
    if ($query) {
        $sql = "
            SELECT 
                p.id,
                p.title,
                p.content,
                p.created_at,
                p.updated_at,
                u.username as created_by,
                GROUP_CONCAT(DISTINCT ua.username) as authors
            FROM pages p
            LEFT JOIN users u ON p.user_id = u.id
            LEFT JOIN page_authors pa ON p.id = pa.page_id
            LEFT JOIN users ua ON pa.user_id = ua.id
            WHERE p.title LIKE :query OR p.content LIKE :query
            GROUP BY p.id
            ORDER BY p.updated_at DESC
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':query' => '%' . $query . '%']);
    } else {
        $sql = "
            SELECT 
                p.id,
                p.title,
                p.content,
                p.created_at,
                p.updated_at,
                u.username as created_by,
                GROUP_CONCAT(DISTINCT ua.username) as authors
            FROM pages p
            LEFT JOIN users u ON p.user_id = u.id
            LEFT JOIN page_authors pa ON p.id = pa.page_id
            LEFT JOIN users ua ON pa.user_id = ua.id
            GROUP BY p.id
            ORDER BY p.updated_at DESC
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->execute();
    }

    $pages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatta i risultati
    $results = [];
    foreach ($pages as $page) {
        $results[] = [
            'id' => $page['id'],
            'title' => $page['title'],
            'content' => $page['content'],
            'created_by' => $page['created_by'] ?? 'Unknown',
            'authors' => $page['authors'] ?? $page['created_by'] ?? 'Unknown',
            'created_at' => $page['created_at'],
            'updated_at' => $page['updated_at']
        ];
    }

    // Restituisci i risultati
    echo json_encode([
        'success' => true,
        'count' => count($results),
        'query' => $query,
        'results' => $results
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'results' => []
    ]);
}
?>
