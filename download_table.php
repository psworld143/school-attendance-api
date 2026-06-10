<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

// Database credentials
$host = 'localhost';
$db   = 'school_attendance';
$user = 'root';
$pass = '';

// Allowed tables for security
$allowed_tables = ['teachers', 'classrooms', 'departments', 'schedules', 'attendance', 'subjects'];

// Get table name from GET parameter
$table = isset($_GET['table']) ? $_GET['table'] : '';

if (!in_array($table, $allowed_tables)) {
    echo json_encode(['error' => 'Invalid table name']);
    http_response_code(400);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT * FROM `$table`");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['rows' => $rows]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>