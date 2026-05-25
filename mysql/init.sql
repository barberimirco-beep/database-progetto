-- ══════════════════════════════════════════════════════════════
--  init.sql — Schema per il sistema RFID
-- ══════════════════════════════════════════════════════════════
USE rfid_db;

-- ─────────────────────────────────────────────────────────────
--  Tabella prodotti: mappa tag RFID → prodotto
--  Aggiungi qui ogni etichetta fisica con il suo prodotto
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS products (
    tag_rfid     VARCHAR(50)    NOT NULL,
    product_name VARCHAR(100)   NOT NULL,
    price        DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    PRIMARY KEY (tag_rfid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
--  Tabella letture RFID
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS rfid_readings (
    id            INT              NOT NULL AUTO_INCREMENT,
    product_id    VARCHAR(50)      NOT NULL,
    product_name  VARCHAR(100)     NOT NULL,
    price         DECIMAL(10,2)    NOT NULL,
    movement      ENUM('IN','OUT') NOT NULL DEFAULT 'IN',
    read_at       TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_product_id (product_id),
    INDEX idx_read_at    (read_at),
    INDEX idx_movement   (movement)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
--  Prodotti registrati (aggiungi qui i tuoi tag fisici reali)
-- ─────────────────────────────────────────────────────────────
INSERT INTO products (tag_rfid, product_name, price) VALUES
    ('RFID-A001',    'Tastiera meccanica Keychron K2',  89.99),
    ('RFID-B042',    'Monitor LG 27" 4K',              329.00),
    ('RFID-C007',    'Hub USB-C 7 porte',               34.50),
    ('RFID-D019',    'SSD Samsung 1TB NVMe',             94.90),
    ('RFID-E055',    'Webcam Logitech C920',             69.99),
    ('RFID-F003',    'Mouse Logitech MX Master 3',       89.00),
    ('RFID-G088',    'Cuffie Sony WH-1000XM5',          279.99),
    ('RFID-C30EF02C','Prodotto Test ESP32',               0.00);
-- ↑ Aggiungi qui il tag letto dal tuo ESP32 con nome e prezzo reali

-- ─────────────────────────────────────────────────────────────
--  Dati di esempio nelle letture
-- ─────────────────────────────────────────────────────────────
/*INSERT INTO rfid_readings (product_id, product_name, price, movement, read_at) VALUES
    ('RFID-A001', 'Tastiera meccanica Keychron K2',  89.99, 'IN',  '2024-05-10 08:15:22'),
    ('RFID-B042', 'Monitor LG 27" 4K',              329.00, 'IN',  '2024-05-10 08:17:45'),
    ('RFID-C007', 'Hub USB-C 7 porte',               34.50, 'IN',  '2024-05-10 09:02:10'),
    ('RFID-A001', 'Tastiera meccanica Keychron K2',  89.99, 'OUT', '2024-05-10 09:45:00'),
    ('RFID-D019', 'SSD Samsung 1TB NVMe',             94.90, 'IN',  '2024-05-10 10:30:33'),
    ('RFID-E055', 'Webcam Logitech C920',             69.99, 'IN',  '2024-05-10 11:00:05'),
    ('RFID-B042', 'Monitor LG 27" 4K',              329.00, 'OUT', '2024-05-10 11:22:17'),
    ('RFID-F003', 'Mouse Logitech MX Master 3',       89.00, 'IN',  '2024-05-10 12:10:44'),
    ('RFID-G088', 'Cuffie Sony WH-1000XM5',          279.99, 'IN',  '2024-05-10 13:05:55'),
    ('RFID-C007', 'Hub USB-C 7 porte',               34.50, 'OUT', '2024-05-10 14:30:00');*/
