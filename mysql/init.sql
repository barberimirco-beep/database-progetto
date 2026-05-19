-- ══════════════════════════════════════════════════════════════
--  init.sql — Schema e dati di esempio per il sistema RFID
--  Viene eseguito automaticamente da MySQL al primo avvio
-- ══════════════════════════════════════════════════════════════

-- Seleziona il database (creato automaticamente da MYSQL_DATABASE nel .env)
USE rfid_db;

-- ─────────────────────────────────────────────────────────────
--  Tabella principale: letture RFID
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS rfid_readings (
    id            INT           NOT NULL AUTO_INCREMENT,  -- Chiave primaria autoincrementale
    product_id    VARCHAR(50)   NOT NULL,                 -- ID del tag RFID letto dal lettore
    product_name  VARCHAR(100)  NOT NULL,                 -- Nome del prodotto associato al tag
    price         DECIMAL(10,2) NOT NULL,                 -- Prezzo del prodotto (es: 12.99)

    -- ── AGGIUNTO: stato movimento prodotto ──────────────────
    movement      ENUM('IN','OUT') NOT NULL DEFAULT 'IN',

    read_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP, -- Timestamp automatico della lettura

    PRIMARY KEY (id),
    INDEX idx_product_id (product_id),   -- Indice per velocizzare le ricerche per tag
    INDEX idx_read_at    (read_at),      -- Indice per ordinamento cronologico

    -- ── AGGIUNTO: indice per filtrare IN/OUT ────────────────
    INDEX idx_movement (movement)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
--  Dati di esempio per testare il frontend
-- ─────────────────────────────────────────────────────────────
INSERT INTO rfid_readings
(product_id, product_name, price, movement, read_at)
VALUES

    ('RFID-A001', 'Tastiera meccanica Keychron K2',    89.99, 'IN',  '2024-05-10 08:15:22'),

    ('RFID-B042', 'Monitor LG 27" 4K',                329.00, 'IN',  '2024-05-10 08:17:45'),

    ('RFID-C007', 'Hub USB-C 7 porte',                 34.50, 'IN',  '2024-05-10 09:02:10'),

    ('RFID-A001', 'Tastiera meccanica Keychron K2',    89.99, 'OUT', '2024-05-10 09:45:00'),

    ('RFID-D019', 'SSD Samsung 1TB NVMe',              94.90, 'IN',  '2024-05-10 10:30:33'),

    ('RFID-E055', 'Webcam Logitech C920',              69.99, 'IN',  '2024-05-10 11:00:05'),

    ('RFID-B042', 'Monitor LG 27" 4K',                329.00, 'OUT', '2024-05-10 11:22:17'),

    ('RFID-F003', 'Mouse Logitech MX Master 3',        89.00, 'IN',  '2024-05-10 12:10:44'),

    ('RFID-G088', 'Cuffie Sony WH-1000XM5',           279.99, 'IN',  '2024-05-10 13:05:55'),

    ('RFID-C007', 'Hub USB-C 7 porte',                 34.50, 'OUT', '2024-05-10 14:30:00');
