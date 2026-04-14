<?php
// --- Konfiguration ---
define('API_KEY', 'fb_' . 'k7mP9xQr2nLw'); // Diesen Key nicht weitergeben!
define('DATA_FILE', __DIR__ . '/fahrten.json');

// CORS-Header damit ttbb-maker.github.io zugreifen darf
header('Access-Control-Allow-Origin: https://ttbb-maker.github.io');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');
header('Content-Type: application/json');

// OPTIONS-Preflight direkt beantworten
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

// GET: alle Fahrten zurückgeben
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!file_exists(DATA_FILE)) {
        echo json_encode([]);
        exit;
    }
    echo file_get_contents(DATA_FILE);
    exit;
}

// POST: Fahrten speichern
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);
    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }
    $result = file_put_contents(DATA_FILE, json_encode($data));
    if ($result === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Datei konnte nicht gespeichert werden']);
        exit;
    }
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
