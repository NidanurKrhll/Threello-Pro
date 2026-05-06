<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'baglan.php';

$createSql = "CREATE TABLE IF NOT EXISTS board_states (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    boards_json LONGTEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (!$conn->query($createSql)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'board_states tablosu olusturulamadi.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $username = trim($_GET['username'] ?? '');
    if ($username === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'username zorunlu.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT boards_json FROM board_states WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;

    if (!$row) {
        echo json_encode(['success' => true, 'boards' => null]);
        exit;
    }

    $decoded = json_decode($row['boards_json'], true);
    if (!is_array($decoded)) {
        $decoded = null;
    }

    echo json_encode(['success' => true, 'boards' => $decoded]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    $username = trim($data['username'] ?? '');
    $boards = $data['boards'] ?? null;

    if ($username === '' || !is_array($boards)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Gecersiz veri.']);
        exit;
    }

    $json = json_encode($boards, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Boards JSON encode hatasi.']);
        exit;
    }

    $stmt = $conn->prepare("
        INSERT INTO board_states (username, boards_json)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE boards_json = VALUES(boards_json)
    ");
    $stmt->bind_param("ss", $username, $json);

    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Kaydetme basarisiz.']);
        exit;
    }

    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Desteklenmeyen method.']);
?>
