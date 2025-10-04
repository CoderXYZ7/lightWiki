<?php
// LiteWiki Authentication System

require_once __DIR__ . '/db.php';

class Auth {
    private $db;
    private $config;

    public function __construct() {
        $this->config = include __DIR__ . '/config.php';
        $this->db = new Database($this->config['db_path']);
        $this->startSession();
    }

    private function startSession() {
        // Only configure session if not already active
        if (session_status() === PHP_SESSION_NONE) {
            session_name($this->config['session_name']);
            session_set_cookie_params($this->config['session_lifetime']);
            session_start();
        }
    }

    public function login($username, $password) {
        $user = $this->db->fetch("SELECT * FROM users WHERE username = ?", [$username]);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['login_time'] = time();
            return true;
        }
        return false;
    }

    public function logout() {
        session_destroy();
        return true;
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['login_time']) &&
               (time() - $_SESSION['login_time']) < $this->config['session_lifetime'];
    }

    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role']
        ];
    }

    public function register($username, $password, $role = 'user') {
        if (strlen($password) < $this->config['password_min_length']) {
            return ['success' => false, 'message' => 'Password too short'];
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $userId = $this->db->insert('users', [
                'username' => $username,
                'password_hash' => $passwordHash,
                'role' => $role
            ]);
            return ['success' => true, 'user_id' => $userId];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Username already exists'];
        }
    }

    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: /login.php');
            exit;
        }
    }

    public function requireRole($role) {
        $user = $this->getCurrentUser();
        if (!$user || $user['role'] !== $role) {
            http_response_code(403);
            die('Access denied');
        }
    }

    public function hasRole($role) {
        $user = $this->getCurrentUser();
        return $user && $user['role'] === $role;
    }

    public function generateCSRFToken() {
        if (!isset($_SESSION[$this->config['csrf_token_name']])) {
            $_SESSION[$this->config['csrf_token_name']] = bin2hex(random_bytes(32));
        }
        return $_SESSION[$this->config['csrf_token_name']];
    }

    public function validateCSRFToken($token) {
        return isset($_SESSION[$this->config['csrf_token_name']]) &&
               hash_equals($_SESSION[$this->config['csrf_token_name']], $token);
    }

    public function getAllUsers() {
        return $this->db->fetchAll("SELECT id, username, role, created_at FROM users ORDER BY created_at DESC");
    }

    public function updateUserRole($userId, $role) {
        $this->db->update('users', ['role' => $role], 'id = ?', ['id' => $userId]);
    }

    public function deleteUser($userId) {
        $this->db->delete('users', 'id = ?', [$userId]);
    }
}
?>
