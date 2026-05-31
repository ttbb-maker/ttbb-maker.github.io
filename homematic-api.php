<?php
// =====================================================================
//  Homematic Proxy  ·  Synology  ->  CCU3 / RaspberryMatic (XML-API)
// ---------------------------------------------------------------------
//  Stellt eine schlanke JSON-Schnittstelle fuer das HTML-Dashboard
//  bereit. Der Browser kann die CCU nicht direkt ansprechen
//  (HTTP / CORS / Mixed-Content) – deshalb dieser Proxy.
//
//  Voraussetzung auf der CCU: Addon "XML-API" installiert
//  (Systemsteuerung -> Zusatzsoftware). Endpunkte liegen dann unter
//  http://<CCU>/addons/xmlapi/...
//
//  >>> NUR DIESE BEIDEN ZEILEN ANPASSEN <<<
// =====================================================================
define('CCU_HOST', 'http://192.168.0.10');          // IP/Host deiner CCU
define('API_KEY',  'hm_' . 'Xy7Qp2Rm9Lk');          // frei waehlbar, muss zum Dashboard passen
// =====================================================================

define('CONFIG_FILE', __DIR__ . '/homematic-config.json');
define('CCU_TIMEOUT', 8); // Sekunden

// --- CORS / Header ---
header('Access-Control-Allow-Origin: https://ttbb-maker.github.io');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// --- Auth ---
if (($_SERVER['HTTP_X_API_KEY'] ?? '') !== API_KEY) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

/** Ruft eine XML-API-CGI auf der CCU auf und liefert SimpleXML zurueck. */
function ccu_xml($cgi, $params = []) {
    $url = rtrim(CCU_HOST, '/') . '/addons/xmlapi/' . $cgi;
    if ($params) $url .= '?' . http_build_query($params);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => CCU_TIMEOUT,
        CURLOPT_TIMEOUT        => CCU_TIMEOUT,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $raw  = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($raw === false || $code >= 400) {
        ccu_fail($err ?: ('HTTP ' . $code));
    }
    $xml = @simplexml_load_string($raw);
    if ($xml === false) ccu_fail('Ungueltige Antwort der CCU (XML-API installiert?)');
    return $xml;
}

function ccu_fail($msg) {
    http_response_code(502);
    echo json_encode(['error' => 'CCU nicht erreichbar', 'detail' => $msg]);
    exit;
}

function body_json() {
    $d = json_decode(file_get_contents('php://input'), true);
    if (!is_array($d)) { http_response_code(400); echo json_encode(['error' => 'Ungueltiges JSON']); exit; }
    return $d;
}

// =====================================================================
//  GET-Aktionen
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // --- Komplette Geraeteliste (fuer den Einrichten-Modus) ---
    if ($action === 'devices') {
        $xml = ccu_xml('statelist.cgi');
        $devices = [];
        foreach ($xml->device as $d) {
            $channels = [];
            foreach ($d->channel as $c) {
                $dps = [];
                foreach ($c->datapoint as $p) {
                    $dps[] = [
                        'ise_id' => (string)$p['ise_id'],
                        'type'   => (string)$p['type'],
                        'value'  => (string)$p['value'],
                        'unit'   => (string)$p['valueunit'],
                        'vtype'  => (string)$p['valuetype'],
                    ];
                }
                $channels[] = [
                    'ise_id'    => (string)$c['ise_id'],
                    'name'      => (string)$c['name'],
                    'datapoints'=> $dps,
                ];
            }
            $devices[] = [
                'ise_id'   => (string)$d['ise_id'],
                'name'     => (string)$d['name'],
                'type'     => (string)$d['device_type'],
                'unreach'  => (string)$d['unreach'] === 'true',
                'channels' => $channels,
            ];
        }
        echo json_encode(['devices' => $devices]);
        exit;
    }

    // --- Aktuelle Werte aller Datenpunkte (fuer das Dashboard) ---
    if ($action === 'state') {
        $xml = ccu_xml('statelist.cgi');
        $vals = [];
        foreach ($xml->device as $d) {
            $un = (string)$d['unreach'] === 'true';
            foreach ($d->channel as $c) {
                foreach ($c->datapoint as $p) {
                    $vals[(string)$p['ise_id']] = [
                        'v' => (string)$p['value'],
                        'u' => (string)$p['valueunit'],
                    ];
                }
            }
            // Geraete-Erreichbarkeit unter der Geraete-ise_id ablegen
            if ($un) $vals['unreach_' . (string)$d['ise_id']] = ['v' => 'true', 'u' => ''];
        }
        echo json_encode(['values' => $vals, 'ts' => time()]);
        exit;
    }

    // --- CCU-Programme / Zentralen-Szenen ---
    if ($action === 'programs') {
        $xml = ccu_xml('programlist.cgi');
        $progs = [];
        foreach ($xml->program as $p) {
            if ((string)$p['visible'] === 'false') continue;
            $progs[] = ['ise_id' => (string)$p['ise_id'], 'name' => (string)$p['name']];
        }
        echo json_encode(['programs' => $progs]);
        exit;
    }

    // --- Gespeicherte Dashboard-Konfiguration ---
    if ($action === 'config') {
        $cfg = file_exists(CONFIG_FILE)
            ? json_decode(file_get_contents(CONFIG_FILE), true)
            : null;
        echo json_encode($cfg ?: ['rooms' => []]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'Unbekannte Aktion']);
    exit;
}

// =====================================================================
//  POST-Aktionen
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Datenpunkt setzen (Schalten / Dimmen / Rollladen / Temperatur) ---
    if ($action === 'set') {
        $d = body_json();
        if (!isset($d['ise_id'])) { http_response_code(400); echo json_encode(['error'=>'ise_id fehlt']); exit; }
        ccu_xml('statechange.cgi', ['ise_id' => $d['ise_id'], 'new_value' => (string)($d['value'] ?? '')]);
        echo json_encode(['ok' => true]);
        exit;
    }

    // --- CCU-Programm ausloesen ---
    if ($action === 'program') {
        $d = body_json();
        if (!isset($d['ise_id'])) { http_response_code(400); echo json_encode(['error'=>'ise_id fehlt']); exit; }
        ccu_xml('programexec.cgi', ['program_id' => $d['ise_id']]);
        echo json_encode(['ok' => true]);
        exit;
    }

    // --- Dashboard-Konfiguration speichern ---
    if ($action === 'config') {
        $d = body_json();
        if (file_put_contents(CONFIG_FILE, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
            http_response_code(500); echo json_encode(['error' => 'Konnte Konfiguration nicht speichern']); exit;
        }
        echo json_encode(['ok' => true]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'Unbekannte Aktion']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
