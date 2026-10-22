<?php
// ---- Database settings: change these to match your MySQL setup ----
const DB_HOST = '127.0.0.1';
const DB_NAME = 'expense_tracker';
const DB_USER = 'root';
const DB_PASS = '';

session_start();
header('Content-Type: application/json; charset=utf-8');

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            respond(['error' => 'Database connection failed'], 500);
        }
    }
    return $pdo;
}

function respond(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function input(): array {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    return is_array($json) ? $json : $_POST;
}

function require_login(): int {
    if (empty($_SESSION['user_id'])) {
        respond(['error' => 'Not logged in'], 401);
    }
    return (int) $_SESSION['user_id'];
}
