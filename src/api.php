<?php
// ══════════════════════════════════════════════════════════════
//  api.php — Endpoint REST che restituisce le letture RFID
//  Risponde sempre in JSON. Usato dalla fetch API del frontend.
// ══════════════════════════════════════════════════════════════

header('Content-Type: application/json; charset=utf-8');
// Permette richieste dalla stessa origine (same-origin in Docker va bene)
header('Access-Control-Allow-Origin: *');

// ── Legge le variabili d'ambiente iniettate da Docker Compose ─
$host   = getenv('MYSQL_HOST')     ?: 'db';
$db     = getenv('MYSQL_DATABASE') ?: 'rfid_db';
$user   = getenv('MYSQL_USER')     ?: 'rfid_user';
$pass   = getenv('MYSQL_PASSWORD') ?: '';

// ── Connessione al database ───────────────────────────────────
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'error'   => 'Connessione al database fallita: ' . $conn->connect_error
    ]);
    exit;
}

$conn->set_charset('utf8mb4');

$conn->query("SET time_zone = '+02:00'");

// ── Query: ultimi 100 record ordinati per data decrescente ────
// AGGIUNTO: campo movement
$sql = "SELECT 
            id,
            product_id,
            product_name,
            price,
            movement,
            DATE_FORMAT(read_at, '%d/%m/%Y %H:%i:%s') AS read_at
        FROM rfid_readings
        ORDER BY read_at DESC
        LIMIT 100";

$result = $conn->query($sql);

if (!$result) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Errore query: ' . $conn->error
    ]);
    $conn->close();
    exit;
}

// ── Costruisce l'array di risultati ──────────────────────────
$rows = [];

while ($row = $result->fetch_assoc()) {

    $rows[] = [
        'id'           => (int)   $row['id'],
        'product_id'   =>         $row['product_id'],
        'product_name' =>         $row['product_name'],
        'price'        => (float) $row['price'],

        // AGGIUNTO: stato movimento
        'movement'     =>         $row['movement'] ?: 'IN',

        'read_at'      =>         $row['read_at'],
    ];
}

$conn->close();

// ── Risposta JSON ─────────────────────────────────────────────
echo json_encode([
    'success' => true,
    'count'   => count($rows),
    'data'    => $rows
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
