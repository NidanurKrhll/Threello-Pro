<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'baglan.php';

$createSql = "CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    color VARCHAR(20) DEFAULT '#6366f1',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
$conn->query($createSql);

$res = $conn->query("SELECT username, email, color FROM users ORDER BY created_at DESC");
$users = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $users[] = $row;
    }
}

echo json_encode([
    'success' => true,
    'users' => $users
]);
?>
