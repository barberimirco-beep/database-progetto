<?php
// ══════════════════════════════════════════════════════════════
//  api_scan.php — Riceve la lettura RFID dall'ESP32
// ══════════════════════════════════════════════════════════════
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Solo POST accettato']);
    exit;
}

// ── Stesse variabili d'ambiente del tuo api.php ───────────────
$host = getenv('MYSQL_HOST')     ?: 'db';
$db   = getenv('MYSQL_DATABASE') ?: 'rfid_db';
$user = getenv('MYSQL_USER')     ?: 'rfid_user';
$pass = getenv('MYSQL_PASSWORD') ?: '';

// ── Leggi il JSON inviato dall'ESP32 ─────────────────────────
$data     = json_decode(file_get_contents('php://input'), true);
$tag_rfid = trim($data['tag_rfid'] ?? '');
$tipo     = trim($data['tipo']     ?? '');

if (!$tag_rfid || !$tipo) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'tag_rfid e tipo obbligatori']);
    exit;
}

// ── Connessione ───────────────────────────────────────────────
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'DB: ' . $conn->connect_error]);
    exit;
}
$conn->set_charset('utf8mb4');

// ── Cerca il prodotto nella tabella products ──────────────────
$stmt = $conn->prepare('SELECT product_name, price FROM products WHERE tag_rfid = ?');
$stmt->bind_param('s', $tag_rfid);
$stmt->execute();
$stmt->bind_result($product_name, $price);

if (!$stmt->fetch()) {
    // Tag non registrato nella tabella products
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error'   => "Tag $tag_rfid non registrato. Aggiungilo nella tabella products in init.sql"
    ]);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

// ── Converti tipo → movement ──────────────────────────────────
$movement = ($tipo === 'entrata') ? 'IN' : 'OUT';

// ── Inserisci la lettura ──────────────────────────────────────
$ins = $conn->prepare(
    'INSERT INTO rfid_readings (product_id, product_name, price, movement) VALUES (?, ?, ?, ?)'
);
$ins->bind_param('ssds', $tag_rfid, $product_name, $price, $movement);

if (!$ins->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $ins->error]);
    $ins->close(); $conn->close();
    exit;
}

$new_id = $conn->insert_id;
$ins->close();
$conn->close();

echo json_encode([
    'success'      => true,
    'movimento_id' => $new_id,
    'prodotto'     => [
        'tag'      => $tag_rfid,
        'nome'     => $product_name,
        'prezzo'   => $price,
        'movement' => $movement,
    ],
    'message' => "Lettura registrata: $product_name ($movement)",
], JSON_UNESCAPED_UNICODE);
