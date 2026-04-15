<?php
// --- Konfiguration ---
define('API_KEY',   'fin_k8mL4qSy6vNw');
define('DATA_FILE', __DIR__ . '/finanzen.json');

// CORS-Header
header('Access-Control-Allow-Origin: https://ttbb-maker.github.io');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// API-Key prüfen
$key = $_SERVER['HTTP_X_API_KEY'] ?? '';
if ($key !== API_KEY) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// GET: Alle Buchungen zurückgeben
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $buchungen = file_exists(DATA_FILE)
        ? json_decode(file_get_contents(DATA_FILE), true) ?? []
        : [];
    echo json_encode($buchungen);
    exit;
}

// POST: Buchungen speichern (komplettes Array)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);

    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['error' => 'Ungültige JSON-Daten']);
        exit;
    }

    if (file_put_contents(DATA_FILE, json_encode($data)) === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Datei konnte nicht gespeichert werden']);
        exit;
    }

    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
