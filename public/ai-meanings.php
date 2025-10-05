<?php
// AI Meanings API for selected text in context
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // Load environment variables from lightWikiBackEnd/.env
    $envPath = __DIR__ . '/../lightWikiBackEnd/.env';
    if (!file_exists($envPath)) {
        throw new Exception('Environment file not found');
    }

    $env = parse_ini_file($envPath);
    $apiKey = $env['OPENAI_API_KEY'] ?? null;

    if (!$apiKey) {
        throw new Exception('API key not found');
    }

    // Get parameters
    $selectedText = $_GET['text'] ?? '';
    $pageTitle = $_GET['page_id'] ?? '';

    if (empty($selectedText) || empty($pageTitle)) {
        throw new Exception('Missing required parameters: text and page_id');
    }

    // Include necessary files and get page content
    require_once __DIR__ . '/../core/config.php';
    require_once __DIR__ . '/../core/db.php';
    require_once __DIR__ . '/../core/auth.php';
    require_once __DIR__ . '/../core/markdown.php';
    require_once __DIR__ . '/../core/wiki.php';

    $wiki = new Wiki();
    $page = $wiki->getPage($pageTitle);

    if (!$page) {
        throw new Exception('Page not found');
    }

    $pageContent = $page['content'];

    // Craft prompt for AI
    $prompt = "Explain the meaning of \"$selectedText\" in the context of the following content. Provide a clear, concise explanation:\n\n$pageContent";

    // Prepare API request (using OpenAI-style API for DeepSeek)
    $apiUrl = 'https://api.deepseek.com/v1/chat/completions'; // Assuming DeepSeek uses this endpoint

    $data = [
        'model' => 'deepseek-chat', // Or appropriate model name
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ],
        'max_tokens' => 500,
        'temperature' => 0.7
    ];

    $opts = [
        'http' => [
            'method' => 'POST',
            'header' => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ],
            'content' => json_encode($data)
        ]
    ];

    $context = stream_context_create($opts);
    $response = file_get_contents($apiUrl, false, $context);

    if ($response === false) {
        throw new Exception('Failed to get response from AI API');
    }

    $apiResponse = json_decode($response, true);

    if (isset($apiResponse['error'])) {
        throw new Exception('AI API error: ' . $apiResponse['error']['message']);
    }

    $aiResponse = $apiResponse['choices'][0]['message']['content'] ?? 'No response from AI';

    // Return the response
    echo json_encode([
        'success' => true,
        'response' => $aiResponse
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
