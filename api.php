<?php
// --- Konfiguration ---
define('API_KEY', 'fb_' . 'k7mP9xQr2nLw');
define('DATA_FILE', __DIR__ . '/fahrten.json');
define('BERICHTE_DIR', __DIR__ . '/berichte');
define('BASE_URL', 'https://tonibader.synology.me');

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

// GET: alle Fahrten zurückgeben
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!file_exists(DATA_FILE)) { echo json_encode([]); exit; }
    echo file_get_contents(DATA_FILE);
    exit;
}

// POST: Fahrten speichern oder Bericht erstellen
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);

    // Bericht erstellen
    if ($action === 'bericht') {
        if (!isset($data['fahrten']) || !isset($data['monat']) || !isset($data['jahr'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Ungültige Daten']);
            exit;
        }
        if (!is_dir(BERICHTE_DIR)) mkdir(BERICHTE_DIR, 0755, true);

        $fahrten  = $data['fahrten'];
        $monat    = $data['monat'];    // z.B. "April"
        $jahr     = $data['jahr'];     // z.B. 2026
        $tkm      = array_sum(array_column($fahrten, 'km'));
        $pauschale = round($tkm * 0.30, 2);
        $anzahl   = count($fahrten);
        $erstellt = date('d.m.Y H:i');

        $zeilen = '';
        foreach ($fahrten as $i => $f) {
            $datum = date('d.m.Y', strtotime($f['datum']));
            $retour = $f['retour'] ? 'Ja' : 'Nein';
            $km = number_format($f['km'], 1, ',', '.');
            $p  = number_format($f['km'] * 0.30, 2, ',', '.');
            $bg = ($i % 2 === 0) ? '#ffffff' : '#f7f8fa';
            $zeilen .= "<tr style=\"background:$bg;\">
                <td>$datum</td>
                <td>{$f['von']}</td>
                <td>{$f['nach']}</td>
                <td style=\"text-align:center;\">$retour</td>
                <td style=\"text-align:right;\">$km km</td>
                <td>{$f['zweck']}</td>
                <td style=\"text-align:right;\">$p &euro;</td>
            </tr>";
        }

        $tkmFmt = number_format($tkm, 1, ',', '.');
        $pausFmt = number_format($pauschale, 2, ',', '.');

        $html = "<!DOCTYPE html>
<html lang=\"de\">
<head>
<meta charset=\"UTF-8\">
<title>Fahrtenbuch $monat $jahr &ndash; Toni Bader</title>
<style>
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'Segoe UI',Arial,sans-serif; color:#1a1a2e; background:#fff; padding:40px; font-size:14px; }
  @media print { body { padding:20px; } .no-print { display:none; } }
  .header { border-bottom:3px solid #1a1a2e; padding-bottom:20px; margin-bottom:30px; display:flex; justify-content:space-between; align-items:flex-end; }
  .header-left h1 { font-size:28px; font-weight:800; letter-spacing:-0.5px; }
  .header-left p { font-size:14px; color:#666; margin-top:4px; }
  .header-right { text-align:right; font-size:12px; color:#666; }
  .header-right strong { display:block; font-size:16px; color:#1a1a2e; margin-bottom:2px; }
  .summary { display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; margin-bottom:30px; }
  .summary-box { background:#f0f4ff; border-radius:10px; padding:16px 20px; }
  .summary-label { font-size:10px; text-transform:uppercase; letter-spacing:.1em; color:#666; margin-bottom:4px; }
  .summary-value { font-size:24px; font-weight:800; color:#1a1a2e; }
  table { width:100%; border-collapse:collapse; font-size:13px; }
  thead tr { background:#1a1a2e; color:#fff; }
  thead th { padding:10px 12px; text-align:left; font-weight:600; font-size:11px; text-transform:uppercase; letter-spacing:.05em; }
  thead th:nth-child(5), thead th:nth-child(7) { text-align:right; }
  thead th:nth-child(4) { text-align:center; }
  tbody td { padding:9px 12px; border-bottom:1px solid #eee; }
  .total-row { background:#1a1a2e !important; color:#fff; font-weight:700; }
  .total-row td { padding:10px 12px; border:none; }
  .footer { margin-top:24px; font-size:11px; color:#999; text-align:right; }
  .btn-print { display:inline-block; margin-bottom:24px; background:#1a1a2e; color:#fff; border:none; border-radius:8px; padding:10px 24px; font-size:14px; cursor:pointer; font-family:inherit; }
</style>
</head>
<body>
<button class=\"btn-print no-print\" onclick=\"window.print()\">&#128438; Drucken / Als PDF speichern</button>
<div class=\"header\">
  <div class=\"header-left\">
    <h1>Fahrtenbuch</h1>
    <p>$monat $jahr</p>
  </div>
  <div class=\"header-right\">
    <strong>Toni Bader</strong>
    Erstellt am $erstellt
  </div>
</div>
<div class=\"summary\">
  <div class=\"summary-box\">
    <div class=\"summary-label\">Fahrten</div>
    <div class=\"summary-value\">$anzahl</div>
  </div>
  <div class=\"summary-box\">
    <div class=\"summary-label\">Kilometer gesamt</div>
    <div class=\"summary-value\">$tkmFmt km</div>
  </div>
  <div class=\"summary-box\">
    <div class=\"summary-label\">Km-Pauschale (0,30 &euro;)</div>
    <div class=\"summary-value\">$pausFmt &euro;</div>
  </div>
</div>
<table>
  <thead>
    <tr>
      <th>Datum</th>
      <th>Von</th>
      <th>Nach</th>
      <th>Hin &amp; Zur&uuml;ck</th>
      <th>Kilometer</th>
      <th>Zweck</th>
      <th>Pauschale</th>
    </tr>
  </thead>
  <tbody>
    $zeilen
    <tr class=\"total-row\">
      <td colspan=\"4\">GESAMT</td>
      <td style=\"text-align:right;\">$tkmFmt km</td>
      <td></td>
      <td style=\"text-align:right;\">$pausFmt &euro;</td>
    </tr>
  </tbody>
</table>
<div class=\"footer\">Fahrtenbuch &ndash; Toni Bader &ndash; $monat $jahr</div>
</body>
</html>";

        $dateiname = 'Fahrtenbuch_' . $monat . '_' . $jahr . '.html';
        $pfad = BERICHTE_DIR . '/' . $dateiname;
        if (file_put_contents($pfad, $html) === false) {
            http_response_code(500);
            echo json_encode(['error' => 'Bericht konnte nicht gespeichert werden']);
            exit;
        }
        echo json_encode(['ok' => true, 'url' => BASE_URL . '/berichte/' . $dateiname]);
        exit;
    }

    // Standard: Fahrten speichern
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
