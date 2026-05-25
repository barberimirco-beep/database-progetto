<?php
// ══════════════════════════════════════════════════════════════
//  api_scan.php — Riceve la lettura RFID dall'ESP32
//  e la inserisce nella tabella rfid_readings
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
$data = json_decode(file_get_contents('php://input'), true);

$tag_rfid = trim($data['tag_rfid'] ?? '');
$tipo     = trim($data['tipo']     ?? '');   // "entrata" o "uscita"

if (!$tag_rfid || !$tipo) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'tag_rfid e tipo obbligatori']);
    exit;
}

// ── Connessione (identica al tuo api.php) ────────────────────
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'DB: ' . $conn->connect_error]);
    exit;
}
$conn->set_charset('utf8mb4');

// ── Cerca il prodotto associato al tag nella tua tabella ──────
// Se hai una tabella prodotti con tag_rfid, recupera nome e prezzo.
// Altrimenti usa il tag come product_id e lascia nome/prezzo vuoti.
$product_id   = $tag_rfid;
$product_name = $tag_rfid;
$price        = 0.0;

$stmt = $conn->prepare(
    "SELECT product_id, product_name, price
     FROM rfid_readings
     WHERE product_id = ?
     ORDER BY read_at DESC
     LIMIT 1"
);
if ($stmt) {
    $stmt->bind_param('s', $tag_rfid);
    $stmt->execute();
    $stmt->bind_result($pid, $pname, $pprice);
    if ($stmt->fetch()) {
        $product_id   = $pid;
        $product_name = $pname;
        $price        = (float)$pprice;
    }
    $stmt->close();
}

// ── Converti tipo → movement (IN / OUT) ──────────────────────
$movement = ($tipo === 'entrata') ? 'IN' : 'OUT';

// ── Inserisci la lettura ──────────────────────────────────────
$ins = $conn->prepare(
    "INSERT INTO rfid_readings (product_id, product_name, price, movement)
     VALUES (?, ?, ?, ?)"
);
if (!$ins) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Prepare: ' . $conn->error]);
    $conn->close();
    exit;
}

$ins->bind_param('ssds', $product_id, $product_name, $price, $movement);

if (!$ins->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Insert: ' . $ins->error]);
    $ins->close();
    $conn->close();
    exit;
}

$new_id = $conn->insert_id;
$ins->close();
$conn->close();

// ── Risposta ──────────────────────────────────────────────────
echo json_encode([
    'success'      => true,
    'movimento_id' => $new_id,
    'prodotto'     => [
        'product_id'   => $product_id,
        'product_name' => $product_name,
        'movement'     => $movement,
    ],
    'message' => 'Lettura registrata con successo',
], JSON_UNESCAPED_UNICODE);
