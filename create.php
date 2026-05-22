<?php
/**
 * FlashLink — API de création de liens courts
 * https://github.com/TON_USER/flashlink
 */

define('DB_FILE', __DIR__ . '/urls.json');
define('BASE_URL', 'https://url.baule.fr');  // ← adapte
define('API_KEY',  'CHANGE_MOI_ICI');         // ← clé secrète
define('MAX_AGE_HOURS', 24);

header('Content-Type: application/json; charset=utf-8');

function loadDb(): array {
    if (!file_exists(DB_FILE)) return [];
    $data = json_decode(file_get_contents(DB_FILE), true);
    return is_array($data) ? $data : [];
}

function saveDb(array $db): void {
    file_put_contents(DB_FILE, json_encode($db, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function generateId(int $length = 5): string {
    $chars = 'abcdefghijkmnpqrstuvwxyz23456789';
    $id = '';
    for ($i = 0; $i < $length; $i++) {
        $id .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $id;
}

function respond(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

$key = $_GET['key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? '';
if ($key !== API_KEY) {
    respond(['error' => 'Unauthorized. Pass ?key=YOUR_KEY or header X-Api-Key.'], 401);
}

$db = loadDb();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET' || $method === 'POST') {

    if ($method === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $longUrl = $body['url'] ?? $_POST['url'] ?? '';
        $custom  = $body['custom'] ?? $_POST['custom'] ?? '';
    } else {
        $longUrl = $_GET['url'] ?? '';
        $custom  = $_GET['custom'] ?? '';
    }

    if (isset($_GET['delete'])) {
        $delId = $_GET['delete'];
        if (!isset($db[$delId])) respond(['error' => "ID '$delId' not found."], 404);
        unset($db[$delId]);
        saveDb($db);
        respond(['success' => true, 'deleted' => $delId]);
    }

    if (isset($_GET['stats'])) {
        if ($_GET['stats'] === 'all') {
            $out = [];
            foreach ($db as $id => $entry) {
                $expires = date('c', strtotime($entry['created']) + MAX_AGE_HOURS * 3600);
                $out[$id] = [
                    'short'     => BASE_URL . '/' . $id,
                    'url'       => $entry['url'],
                    'clicks'    => $entry['clicks'] ?? 0,
                    'created'   => $entry['created'],
                    'expires'   => $expires,
                    'last_used' => $entry['last_used'] ?? null,
                ];
            }
            respond(['count' => count($out), 'links' => $out]);
        }
        $id = $_GET['stats'];
        if (!isset($db[$id])) respond(['error' => "ID '$id' not found or expired."], 404);
        $expires = date('c', strtotime($db[$id]['created']) + MAX_AGE_HOURS * 3600);
        respond([
            'id'        => $id,
            'short'     => BASE_URL . '/' . $id,
            'url'       => $db[$id]['url'],
            'clicks'    => $db[$id]['clicks'] ?? 0,
            'created'   => $db[$id]['created'],
            'expires'   => $expires,
            'last_used' => $db[$id]['last_used'] ?? null,
        ]);
    }

    if (empty($longUrl)) respond(['error' => 'Missing ?url= parameter.'], 400);
    if (!filter_var($longUrl, FILTER_VALIDATE_URL)) respond(['error' => 'Invalid URL.'], 400);

    if ($custom !== '') {
        $custom = preg_replace('/[^a-zA-Z0-9_-]/', '', $custom);
        if (strlen($custom) < 2) respond(['error' => 'Custom alias too short (min 2 chars).'], 400);
        if (isset($db[$custom])) respond(['error' => "Alias '$custom' already taken."], 409);
        $id = $custom;
    } else {
        foreach ($db as $existingId => $entry) {
            if ($entry['url'] === $longUrl) {
                $expires = date('c', strtotime($entry['created']) + MAX_AGE_HOURS * 3600);
                respond([
                    'id'      => $existingId,
                    'short'   => BASE_URL . '/' . $existingId,
                    'url'     => $longUrl,
                    'expires' => $expires,
                    'reused'  => true,
                ]);
            }
        }
        do { $id = generateId(); } while (isset($db[$id]));
    }

    $expires = date('c', time() + MAX_AGE_HOURS * 3600);
    $db[$id] = ['url' => $longUrl, 'created' => date('c'), 'clicks' => 0];
    saveDb($db);

    respond(['id' => $id, 'short' => BASE_URL . '/' . $id, 'url' => $longUrl, 'expires' => $expires]);
}

respond(['error' => 'Method not allowed.'], 405);
