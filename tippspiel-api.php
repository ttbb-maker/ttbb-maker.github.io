<?php
// --- Konfiguration ---
define('API_KEY', 'wm_' . 'T9pZ4kQ2rXvL');
define('DATA_FILE', __DIR__ . '/tippspiel.json');

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

// GET: gesamten Datensatz (Spiele + Tipps) zurückgeben
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!file_exists(DATA_FILE)) {
        echo json_encode(['spiele' => [], 'tipps' => []]);
        exit;
    }
    echo file_get_contents(DATA_FILE);
    exit;
}

// POST: gesamten Datensatz speichern
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);

    if (!is_array($data) || !isset($data['spiele']) || !isset($data['tipps'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Ungültige Daten']);
        exit;
    }

    // Nur erwartete Felder behalten
    $clean = [
        'spiele' => $data['spiele'],
        'tipps'  => $data['tipps'],
    ];

    if (file_put_contents(DATA_FILE, json_encode($clean)) === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Datei konnte nicht gespeichert werden']);
        exit;
    }
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
