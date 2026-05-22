<?php

define('DB_FILE', __DIR__ . '/urls.json');

function loadDb(): array {
    if (!file_exists(DB_FILE)) return [];
    $data = json_decode(file_get_contents(DB_FILE), true);
    return is_array($data) ? $data : [];
}

function saveDb(array $db): void {
    file_put_contents(DB_FILE, json_encode($db, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

$db = loadDb();

$id = $_GET['id'] ?? '';
$id = trim($id, '/');

if ($id === '' || $id === 'index.php') {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No ID provided.']);
    exit;
}

if (!isset($db[$id])) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => "ID '$id' not found or expired."]);
    exit;
}

$db[$id]['clicks'] = ($db[$id]['clicks'] ?? 0) + 1;
$db[$id]['last_used'] = date('c');
saveDb($db);

header('Location: ' . $db[$id]['url'], true, 302);
exit;
