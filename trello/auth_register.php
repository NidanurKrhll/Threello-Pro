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
$email = strtolower(trim($data['email'] ?? ''));
$password = (string)($data['password'] ?? '');

if ($username === '' || $email === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Eksik alan var.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Gecersiz e-posta adresi.']);
    exit;
}

if (!preg_match('/^(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Sifre kurallarini saglamiyor.']);
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

if (!$conn->query($createSql)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Users tablosu olusturulamadi.']);
    exit;
}

$checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
$checkStmt->bind_param("ss", $username, $email);
$checkStmt->execute();
$checkRes = $checkStmt->get_result();
if ($checkRes && $checkRes->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Kullanici adi veya e-posta zaten kayitli.']);
    exit;
}

$colors = ['#6366f1', '#8b5cf6', '#10b981', '#f59e0b', '#ef4444', '#06b6d4', '#ec4899'];
$countRes = $conn->query("SELECT COUNT(*) AS c FROM users");
$count = 0;
if ($countRes) {
    $row = $countRes->fetch_assoc();
    $count = (int)($row['c'] ?? 0);
}
$color = $colors[$count % count($colors)];

$hash = password_hash($password, PASSWORD_DEFAULT);
$insertStmt = $conn->prepare("INSERT INTO users (username, email, password_hash, color) VALUES (?, ?, ?, ?)");
$insertStmt->bind_param("ssss", $username, $email, $hash, $color);

if (!$insertStmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kayit islemi basarisiz.']);
    exit;
}

echo json_encode([
    'success' => true,
    'user' => [
        'username' => $username,
        'email' => $email,
        'color' => $color
    ]
]);
?>
