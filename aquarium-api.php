<?php
// --- Konfiguration ---
define('API_KEY', 'aq_' . 'm7nK3pRx5wYz');
define('DATA_FILE', __DIR__ . '/aquarium.json');
define('SETTINGS_FILE', __DIR__ . '/aquarium-settings.json');

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

$action = $_GET['action'] ?? '';

// GET: Messungen + Einstellungen zurückgeben
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $messungen = file_exists(DATA_FILE)
        ? json_decode(file_get_contents(DATA_FILE), true) ?? []
        : [];
    $settings = file_exists(SETTINGS_FILE)
        ? json_decode(file_get_contents(SETTINGS_FILE), true) ?? []
        : [];
    $settings = array_merge(['erinnerung_tage' => 7, 'aquarium_name' => 'Mein Aquarium'], $settings);

    echo json_encode(['messungen' => $messungen, 'settings' => $settings]);
    exit;
}

// POST: Messungen speichern oder Einstellungen aktualisieren
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);

    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['error' => 'Ungültige JSON-Daten']);
        exit;
    }

    // Einstellungen speichern
    if ($action === 'settings') {
        $allowed = ['aquarium_name', 'erinnerung_tage'];
        $clean = array_intersect_key($data, array_flip($allowed));
        if (file_put_contents(SETTINGS_FILE, json_encode($clean)) === false) {
            http_response_code(500);
            echo json_encode(['error' => 'Einstellungen konnten nicht gespeichert werden']);
            exit;
        }
        echo json_encode(['ok' => true]);
        exit;
    }

    // Messungen speichern (komplettes Array)
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
