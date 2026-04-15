<?php
// --- Konfiguration ---
define('API_KEY',        'fin_k8mL4qSy6vNw');
define('BUCHUNGEN_FILE', __DIR__ . '/finanzen.json');
define('VERMOEGEN_FILE', __DIR__ . '/finanzen-vermoegen.json');

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

// GET: Buchungen + Vermögenswerte zurückgeben
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $buchungen       = file_exists(BUCHUNGEN_FILE)
        ? json_decode(file_get_contents(BUCHUNGEN_FILE), true) ?? []
        : [];
    $vermoegenswerte = file_exists(VERMOEGEN_FILE)
        ? json_decode(file_get_contents(VERMOEGEN_FILE), true) ?? []
        : [];
    echo json_encode(['buchungen' => $buchungen, 'vermoegenswerte' => $vermoegenswerte]);
    exit;
}

// POST: Daten speichern
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);

    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['error' => 'Ungültige JSON-Daten']);
        exit;
    }

    // Neues Format: { buchungen: [...], vermoegenswerte: [...] }
    if (isset($data['buchungen']) || isset($data['vermoegenswerte'])) {
        if (isset($data['buchungen'])) {
            file_put_contents(BUCHUNGEN_FILE, json_encode($data['buchungen']));
        }
        if (isset($data['vermoegenswerte'])) {
            file_put_contents(VERMOEGEN_FILE, json_encode($data['vermoegenswerte']));
        }
        echo json_encode(['ok' => true]);
        exit;
    }

    // Altes Format (nur Buchungen als Array) – Rückwärtskompatibilität
    if (file_put_contents(BUCHUNGEN_FILE, json_encode($data)) === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Datei konnte nicht gespeichert werden']);
        exit;
    }
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
