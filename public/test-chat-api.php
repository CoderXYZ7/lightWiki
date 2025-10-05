<?php
// Simple test script for chat API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // Load AI API configuration (same as in index.php)
    $envPath = __DIR__ . '/../lightWikiBackEnd/.env';
    if (!file_exists($envPath)) {
        throw new Exception('AI configuration file not found: ' . $envPath);
    }

    $env = parse_ini_file($envPath);
    $apiKey = $env['OPENAI_API_KEY'] ?? null;

    if (!$apiKey) {
        throw new Exception('AI API key not found in config. Available keys: ' . implode(', ', array_keys($env ?? [])));
    }

    $message = $_GET['message'] ?? $_POST['message'] ?? 'Test message';
    $pageContent = "This is a test page content for testing the API connection.";
    $pageTitle = "Test Page";

    // Craft prompt with document context
    $prompt = "You are an AI assistant helping a user understand a document. Use the following document content as context to answer the user's question:\n\nDOCUMENT CONTENT:\n$pageContent\n\nUSER QUESTION:\n$message\n\nPlease provide a clear, helpful answer based on the document content.";

    // Prepare API request
    $apiUrl = 'https://api.deepseek.com/v1/chat/completions';

    $data = [
        'model' => 'deepseek-chat',
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
                'Authorization: Bearer ' . trim($apiKey)
            ],
            'content' => json_encode($data),
            'timeout' => 30
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ];

    $context = stream_context_create($opts);

    // Try the API call
    $response = @file_get_contents($apiUrl, false, $context);

    if ($response === false) {
        $error = error_get_last();
        echo json_encode([
            'success' => false,
            'error' => 'API call failed',
            'php_error' => $error['message'] ?? 'Unknown PHP error',
            'api_url' => $apiUrl,
            'api_key_start' => substr($apiKey, 0, 10) . '...',
            'request_data' => $data
        ]);
        exit();
    }

    $apiResponse = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid JSON response from API',
            'raw_response' => substr($response, 0, 500)
        ]);
        exit();
    }

    if (isset($apiResponse['error'])) {
        echo json_encode([
            'success' => false,
            'error' => $apiResponse['error']['message'] ?? 'DeepSeek API error',
            'full_api_response' => $apiResponse
        ]);
        exit();
    }

    $aiResponse = $apiResponse['choices'][0]['message']['content'] ?? 'No response generated';

    echo json_encode([
        'success' => true,
        'response' => $aiResponse,
        'usage' => $apiResponse['usage'] ?? null
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
