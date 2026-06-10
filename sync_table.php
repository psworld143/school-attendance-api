<?php
header('Content-Type: application/json');

// 1. Database connection
$host = 'localhost';
$db   = 'school_attendance';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// 2. Read and decode JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['table']) || !isset($input['rows'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

$table = $input['table'];
$rows = $input['rows'];

// 3. Security: Only allow certain tables
$allowedTables = [
    'teachers' => 'id',
    'classrooms' => 'id',
    'departments' => 'id',
    'schedules' => 'id',
    'attendance' => 'id',
    'subjects' => 'id'
];
if (!isset($allowedTables[$table])) {
    http_response_code(400);
    echo json_encode(['error' => 'Table not allowed']);
    exit;
}
$primaryKey = $allowedTables[$table];

// 4. Sync logic
$inserted = 0;
$updated = 0;
$skipped = 0;

foreach ($rows as $row) {
    // Build WHERE clause for primary key
    if (!isset($row[$primaryKey])) {
        $skipped++;
        continue;
    }
    $pkValue = $row[$primaryKey];

    // Fetch existing row
    $stmt = $pdo->prepare("SELECT * FROM `$table` WHERE `$primaryKey` = ?");
    $stmt->execute([$pkValue]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Compare all fields
        $isIdentical = true;
        foreach ($row as $col => $val) {
            if (!array_key_exists($col, $existing) || $existing[$col] != $val) {
                $isIdentical = false;
                break;
            }
        }
        if ($isIdentical) {
            $skipped++;
            continue;
        }
        // Update row
        $set = [];
        $values = [];
        foreach ($row as $col => $val) {
            if ($col == $primaryKey) continue;
            $set[] = "`$col` = ?";
            $values[] = $val;
        }
        $values[] = $pkValue;
        $sql = "UPDATE `$table` SET " . implode(', ', $set) . " WHERE `$primaryKey` = ?";
        $pdo->prepare($sql)->execute($values);
        $updated++;
    } else {
        // Insert row
        $cols = array_keys($row);
        $placeholders = array_fill(0, count($cols), '?');
        $sql = "INSERT INTO `$table` (" . implode(',', $cols) . ") VALUES (" . implode(',', $placeholders) . ")";
        $pdo->prepare($sql)->execute(array_values($row));
        $inserted++;
    }
}

echo json_encode([
    'inserted' => $inserted,
    'updated' => $updated,
    'skipped' => $skipped
]);