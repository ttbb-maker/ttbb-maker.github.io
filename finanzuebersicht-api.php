<?php
// --- Konfiguration ---
define('API_KEY',        'fin_k8mL4qSy6vNw');
define('DATA_FILE',      __DIR__ . '/finanzuebersicht.json');
// Anthropic API Key für den Claude-Proxy (PDF-Import)
define('ANTHROPIC_KEY',  'your-anthropic-api-key-here');

// CORS-Header
header('Access-Control-Allow-Origin: https://ttbb-maker.github.io');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key, X-AI-Proxy');
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

// ── Claude AI-Proxy (für PDF-Import) ────────────────────────────────────────
// POST + Header "X-AI-Proxy: 1" → leitet an Anthropic weiter
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_AI_PROXY'])) {
    if (ANTHROPIC_KEY === 'your-anthropic-api-key-here') {
        http_response_code(503);
        echo json_encode(['error' => 'Anthropic API Key nicht konfiguriert. Bitte in finanzuebersicht-api.php eintragen.']);
        exit;
    }
    $body = file_get_contents('php://input');
    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . ANTHROPIC_KEY,
            'anthropic-version: 2023-06-01',
        ],
        CURLOPT_TIMEOUT        => 60,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    http_response_code($httpCode);
    echo $response;
    exit;
}

// ── GET: Portfolio-Daten laden ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (file_exists(DATA_FILE)) {
        $d = json_decode(file_get_contents(DATA_FILE), true);
        echo json_encode($d ?? emptyData());
    } else {
        echo json_encode(emptyData());
    }
    exit;
}

// ── POST: Portfolio-Daten speichern ──────────────────────────────────────────
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
        echo json_encode(['error' => 'Speichern fehlgeschlagen']);
        exit;
    }
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);

function emptyData() {
    return ['immobilien'=>[],'aktien'=>[],'edelmetalle'=>[],'kredite'=>[],'bauspar'=>[],'snapshots'=>[]];
}
