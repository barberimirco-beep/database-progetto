USE magazzino;

CREATE TABLE prodotti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codice VARCHAR(50) UNIQUE NOT NULL,
    nome VARCHAR(100) NOT NULL,
    quantita INT DEFAULT 0,
    posizione VARCHAR(50)
);

CREATE TABLE scansioni_rfid (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tag_rfid VARCHAR(100) NOT NULL,
    prodotto_id INT,
    posizione VARCHAR(100),
    data_scansione TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (prodotto_id)
    REFERENCES prodotti(id)
);

INSERT INTO prodotti (codice, nome, quantita, posizione)
VALUES
('P001', 'Bulloni M8', 120, 'A1'),
('P002', 'Viti M10', 80, 'B2'),
('P003', 'Motore X2', 12, 'C5');
