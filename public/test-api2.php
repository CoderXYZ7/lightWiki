<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Path al database SQLite
$dbPath = __DIR__ . '/../storage/litewiki.db';

// Verifica che il database esista
if (!file_exists($dbPath)) {
    echo json_encode([
        'error' => 'Database not found',
        'results' => []
    ]);
    exit;
}

try {
    // Connessione al database
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Query per recuperare tutte le pagine con informazioni autore
    $query = "
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

    $stmt = $db->prepare($query);
    $stmt->execute();
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
        'results' => $results
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage(),
        'results' => []
    ]);
}
?>
