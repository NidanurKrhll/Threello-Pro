<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'baglan.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Sadece POST kabul edilir.']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$username = trim($data['username'] ?? '');
$password = (string)($data['password'] ?? '');

if ($username === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Kullanici adi ve sifre zorunlu.']);
    exit;
}

$createSql = "CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    color VARCHAR(20) DEFAULT '#6366f1',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
$conn->query($createSql);

$stmt = $conn->prepare("SELECT username, email, password_hash, color FROM users WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$res = $stmt->get_result();

if (!$res || $res->num_rows === 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Kullanici adi veya sifre hatali.']);
    exit;
}

$user = $res->fetch_assoc();
if (!password_verify($password, $user['password_hash'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Kullanici adi veya sifre hatali.']);
    exit;
}

echo json_encode([
    'success' => true,
    'user' => [
        'username' => $user['username'],
        'email' => $user['email'],
        'color' => $user['color'] ?: '#6366f1'
    ]
]);
?>
