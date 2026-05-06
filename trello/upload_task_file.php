<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Sadece POST istegi kabul edilir.'
    ]);
    exit;
}

if (!isset($_FILES['files'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Yuklenecek dosya bulunamadi.'
    ]);
    exit;
}

$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Uploads klasoru olusturulamadi.'
    ]);
    exit;
}

$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip', 'rar'];
$maxBytes = 10 * 1024 * 1024; // 10 MB

$names = $_FILES['files']['name'];
$tmpNames = $_FILES['files']['tmp_name'];
$errors = $_FILES['files']['error'];
$sizes = $_FILES['files']['size'];

$uploaded = [];

for ($i = 0; $i < count($names); $i++) {
    if ($errors[$i] !== UPLOAD_ERR_OK) {
        continue;
    }

    $originalName = $names[$i];
    $size = (int)$sizes[$i];
    $tmpPath = $tmpNames[$i];

    if ($size <= 0 || $size > $maxBytes) {
        continue;
    }

    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($originalName));
    $ext = strtolower(pathinfo($safeName, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExtensions, true)) {
        continue;
    }

    $unique = uniqid('task_', true) . '_' . $safeName;
    $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $unique;

    if (!move_uploaded_file($tmpPath, $targetPath)) {
        continue;
    }

    $uploaded[] = [
        'name' => $originalName,
        'url'  => 'uploads/' . $unique
    ];
}

if (count($uploaded) === 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Dosyalar yuklenemedi. Uzanti ya da boyut kisiti olabilir.'
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'files' => $uploaded
]);
?>
