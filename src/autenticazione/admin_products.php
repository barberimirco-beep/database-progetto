<?php
// ══════════════════════════════════════════════════════════════
//  admin_products.php — API con autenticazione a sessione
// ══════════════════════════════════════════════════════════════
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Auth-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// ── Credenziali admin (modificale qui) ────────────────────────
// Per aggiungere più utenti, aggiungi righe all'array
$UTENTI = [
    'admin' => password_hash('admin1234', PASSWORD_BCRYPT),
    // 'altro' => password_hash('altrapassword', PASSWORD_BCRYPT),
];

// ── Connessione DB ────────────────────────────────────────────
$conn = new mysqli('db', 'rfid_user', 'rfidpass_difficile', 'rfid_db');
if ($conn->connect_error) {
    http_response_code(503);
    echo json_encode(['success'=>false,'error'=>'DB: '.$conn->connect_error]);
    exit;
}
$conn->set_charset('utf8mb4');

// Crea tabella sessions se non esiste
$conn->query("CREATE TABLE IF NOT EXISTS admin_sessions (
    token       VARCHAR(64)  NOT NULL,
    username    VARCHAR(50)  NOT NULL,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (token),
    INDEX idx_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Crea tabella products se non esiste
$conn->query("CREATE TABLE IF NOT EXISTS products (
    tag_rfid     VARCHAR(50)   NOT NULL,
    product_name VARCHAR(100)  NOT NULL,
    price        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (tag_rfid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$method = $_SERVER['REQUEST_METHOD'];
$data   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $data['action'] ?? ($_GET['action'] ?? '');

// ── Funzioni sessione ─────────────────────────────────────────
function generateToken() {
    return bin2hex(random_bytes(32));
}

function validateToken($conn, $token) {
    if (!$token) return null;
    // Pulisci sessioni vecchie (> 8 ore)
    $conn->query("DELETE FROM admin_sessions WHERE created_at < DATE_SUB(NOW(), INTERVAL 8 HOUR)");
    $stmt = $conn->prepare("SELECT username FROM admin_sessions WHERE token = ?");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $stmt->bind_result($username);
    return $stmt->fetch() ? $username : null;
}

function getToken() {
    return $_SERVER['HTTP_X_AUTH_TOKEN']
        ?? $_SERVER['HTTP_X_AUTH_TOKEN']
        ?? null;
}

// ════════════════════════════════════════════════════════════
//  LOGIN
// ════════════════════════════════════════════════════════════
if ($method === 'POST' && $action === 'login') {
    $username = trim($data['username'] ?? '');
    $password = $data['password'] ?? '';

    if (!$username || !$password) {
        http_response_code(400);
        echo json_encode(['success'=>false,'error'=>'Username e password obbligatori']);
        exit;
    }

    if (!isset($UTENTI[$username]) || !password_verify($password, $UTENTI[$username])) {
        http_response_code(401);
        // Piccolo delay anti-brute force
        sleep(1);
        echo json_encode(['success'=>false,'error'=>'Credenziali non valide']);
        exit;
    }

    $token = generateToken();
    $stmt  = $conn->prepare("INSERT INTO admin_sessions (token, username) VALUES (?, ?)");
    $stmt->bind_param('ss', $token, $username);
    $stmt->execute();

    echo json_encode(['success'=>true,'token'=>$token,'username'=>$username]);
    exit;
}

// ════════════════════════════════════════════════════════════
//  LOGOUT
// ════════════════════════════════════════════════════════════
if ($method === 'POST' && $action === 'logout') {
    $token = getToken();
    if ($token) {
        $stmt = $conn->prepare("DELETE FROM admin_sessions WHERE token = ?");
        $stmt->bind_param('s', $token);
        $stmt->execute();
    }
    echo json_encode(['success'=>true]);
    exit;
}

// ════════════════════════════════════════════════════════════
//  TUTTE LE ALTRE OPERAZIONI: richiede autenticazione
// ════════════════════════════════════════════════════════════
$token    = getToken();
$loggedAs = validateToken($conn, $token);

if (!$loggedAs) {
    http_response_code(401);
    echo json_encode(['success'=>false,'error'=>'Non autenticato']);
    exit;
}

// ── GET — lista prodotti ──────────────────────────────────────
if ($method === 'GET') {
    $result = $conn->query('SELECT * FROM products ORDER BY product_name ASC');
    $rows = [];
    while ($row = $result->fetch_assoc()) $rows[] = $row;
    echo json_encode(['success'=>true,'data'=>$rows,'count'=>count($rows)], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── POST — aggiungi prodotto ──────────────────────────────────
if ($method === 'POST' && $action === 'add') {
    $tag   = trim($data['tag_rfid']     ?? '');
    $name  = trim($data['product_name'] ?? '');
    $price = floatval($data['price']    ?? 0);

    if (!$tag || !$name) {
        http_response_code(400);
        echo json_encode(['success'=>false,'error'=>'tag_rfid e product_name obbligatori']);
        exit;
    }

    $stmt = $conn->prepare('INSERT INTO products (tag_rfid, product_name, price) VALUES (?, ?, ?)');
    $stmt->bind_param('ssd', $tag, $name, $price);

    if ($stmt->execute()) {
        echo json_encode(['success'=>true,'message'=>"Prodotto '$name' aggiunto"], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(409);
        echo json_encode(['success'=>false,'error'=>'Tag già esistente']);
    }
    exit;
}

// ── PUT — modifica prodotto ───────────────────────────────────
if ($method === 'PUT') {
    $tag   = trim($data['tag_rfid']     ?? '');
    $name  = trim($data['product_name'] ?? '');
    $price = floatval($data['price']    ?? 0);

    if (!$tag || !$name) {
        http_response_code(400);
        echo json_encode(['success'=>false,'error'=>'Dati mancanti']);
        exit;
    }

    $stmt = $conn->prepare('UPDATE products SET product_name = ?, price = ? WHERE tag_rfid = ?');
    $stmt->bind_param('sds', $name, $price, $tag);
    $stmt->execute();

    if ($stmt->affected_rows > 0)
        echo json_encode(['success'=>true,'message'=>'Prodotto aggiornato']);
    else {
        http_response_code(404);
        echo json_encode(['success'=>false,'error'=>'Tag non trovato']);
    }
    exit;
}

// ── DELETE — elimina prodotto ─────────────────────────────────
if ($method === 'DELETE') {
    $tag = trim($data['tag_rfid'] ?? '');
    if (!$tag) {
        http_response_code(400);
        echo json_encode(['success'=>false,'error'=>'tag_rfid obbligatorio']);
        exit;
    }

    $stmt = $conn->prepare('DELETE FROM products WHERE tag_rfid = ?');
    $stmt->bind_param('s', $tag);
    $stmt->execute();

    if ($stmt->affected_rows > 0)
        echo json_encode(['success'=>true,'message'=>'Prodotto eliminato']);
    else {
        http_response_code(404);
        echo json_encode(['success'=>false,'error'=>'Tag non trovato']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success'=>false,'error'=>'Metodo non supportato']);
