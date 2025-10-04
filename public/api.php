<?php
// LiteWiki REST API

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/wiki.php';

$config = include __DIR__ . '/../core/config.php';

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Check API key
$apiKey = getApiKey();
if (!$apiKey || $apiKey !== $config['api_key']) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid API key']);
    exit;
}

$auth = new Auth();
$wiki = new Wiki();

// Parse request
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$page = $_GET['page'] ?? '';

try {
    switch ($method) {
        case 'GET':
            handleGet($action, $page, $wiki);
            break;
        case 'POST':
            handlePost($action, $wiki, $auth);
            break;
        case 'PUT':
            handlePut($action, $page, $wiki, $auth);
            break;
        case 'DELETE':
            handleDelete($action, $page, $wiki, $auth);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error', 'message' => $e->getMessage()]);
}

function getApiKey() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';

    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        return $matches[1];
    }

    return $_GET['api_key'] ?? $_POST['api_key'] ?? '';
}

function handleGet($action, $page, $wiki) {
    switch ($action) {
        case 'view':
            if (!$page) {
                http_response_code(400);
                echo json_encode(['error' => 'Page parameter required']);
                return;
            }

            $pageData = $wiki->getPage($page);
            if (!$pageData) {
                http_response_code(404);
                echo json_encode(['error' => 'Page not found']);
                return;
            }

            echo json_encode([
                'title' => $pageData['title'],
                'content' => $pageData['content'],
                'rendered_content' => $pageData['rendered_content'],
                'author' => $pageData['author'],
                'created_at' => $pageData['created_at'],
                'updated_at' => $pageData['updated_at']
            ]);
            break;

        case 'list':
            $limit = (int)($_GET['limit'] ?? 50);
            $offset = (int)($_GET['offset'] ?? 0);

            $pages = $wiki->listPages($limit, $offset);
            echo json_encode(['pages' => $pages]);
            break;

        case 'search':
            $query = $_GET['q'] ?? '';
            if (!$query) {
                http_response_code(400);
                echo json_encode(['error' => 'Query parameter required']);
                return;
            }

            $results = $wiki->searchPages($query);
            echo json_encode(['results' => $results]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
}

function handlePost($action, $wiki, $auth) {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }

    switch ($action) {
        case 'create':
            if (!isset($data['title']) || !isset($data['content'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Title and content required']);
                return;
            }

            // For API, we need to authenticate as a user or use a service account
            // For simplicity, we'll create a temporary session
            if (!$auth->isLoggedIn()) {
                // Try to login with a default user or create one
                $defaultUser = getOrCreateDefaultUser($auth);
                if (!$defaultUser) {
                    http_response_code(401);
                    echo json_encode(['error' => 'Authentication required']);
                    return;
                }
            }

            $result = $wiki->createPage($data['title'], $data['content']);
            if ($result['success']) {
                http_response_code(201);
                echo json_encode(['message' => 'Page created', 'page_id' => $result['page_id']]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => $result['message']]);
            }
            break;

        case 'edit':
            if (!isset($data['page']) || !isset($data['content'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Page and content required']);
                return;
            }

            if (!$auth->isLoggedIn()) {
                $defaultUser = getOrCreateDefaultUser($auth);
                if (!$defaultUser) {
                    http_response_code(401);
                    echo json_encode(['error' => 'Authentication required']);
                    return;
                }
            }

            $result = $wiki->updatePage($data['page'], $data['content']);
            if ($result['success']) {
                echo json_encode(['message' => 'Page updated']);
            } else {
                http_response_code(400);
                echo json_encode(['error' => $result['message']]);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
}

function handlePut($action, $page, $wiki, $auth) {
    if ($action !== 'edit' || !$page) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || !isset($data['content'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Content required']);
        return;
    }

    if (!$auth->isLoggedIn()) {
        $defaultUser = getOrCreateDefaultUser($auth);
        if (!$defaultUser) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }
    }

    $result = $wiki->updatePage($page, $data['content']);
    if ($result['success']) {
        echo json_encode(['message' => 'Page updated']);
    } else {
        http_response_code(400);
        echo json_encode(['error' => $result['message']]);
    }
}

function handleDelete($action, $page, $wiki, $auth) {
    if ($action !== 'delete' || !$page) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request']);
        return;
    }

    if (!$auth->isLoggedIn()) {
        $defaultUser = getOrCreateDefaultUser($auth);
        if (!$defaultUser) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }
    }

    $result = $wiki->deletePage($page);
    if ($result['success']) {
        echo json_encode(['message' => 'Page deleted']);
    } else {
        http_response_code(400);
        echo json_encode(['error' => $result['message']]);
    }
}

function getOrCreateDefaultUser($auth) {
    // Try to login as 'api' user
    if ($auth->login('api', 'api_password')) {
        return true;
    }

    // If not, try to register
    $result = $auth->register('api', 'api_password', 'user');
    if ($result['success']) {
        return $auth->login('api', 'api_password');
    }

    return false;
}
?>
