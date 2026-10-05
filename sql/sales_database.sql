CREATE DATABASE IF NOT EXISTS autolux_sales_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE autolux_sales_db;

CREATE TABLE IF NOT EXISTS clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(30),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS parts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference VARCHAR(60) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    brand VARCHAR(100) NOT NULL,
    part_type VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INT UNSIGNED NOT NULL DEFAULT 0,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE,
    active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    payment_method_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    sold_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sales_client
        FOREIGN KEY (client_id) REFERENCES clients(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_sales_payment
        FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    part_id INT NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_sale_items_sale
        FOREIGN KEY (sale_id) REFERENCES sales(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_sale_items_part
        FOREIGN KEY (part_id) REFERENCES parts(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

INSERT INTO payment_methods (name)
SELECT 'Dinheiro' WHERE NOT EXISTS (SELECT 1 FROM payment_methods WHERE name = 'Dinheiro');
INSERT INTO payment_methods (name)
SELECT 'Cartao' WHERE NOT EXISTS (SELECT 1 FROM payment_methods WHERE name = 'Cartao');
INSERT INTO payment_methods (name)
SELECT 'MB Way' WHERE NOT EXISTS (SELECT 1 FROM payment_methods WHERE name = 'MB Way');

INSERT INTO clients (name, email, phone)
SELECT 'Ana Martins', 'ana.martins@example.com', '910000001'
WHERE NOT EXISTS (SELECT 1 FROM clients WHERE email = 'ana.martins@example.com');
INSERT INTO clients (name, email, phone)
SELECT 'Joao Silva', 'joao.silva@example.com', '910000002'
WHERE NOT EXISTS (SELECT 1 FROM clients WHERE email = 'joao.silva@example.com');

INSERT INTO parts (reference, name, brand, part_type, price, stock, description)
SELECT 'BOS-PAD-001', 'Pastilhas de travao dianteiras', 'Bosch', 'Travagem', 49.90, 18, 'Jogo de pastilhas para eixo dianteiro.'
WHERE NOT EXISTS (SELECT 1 FROM parts WHERE reference = 'BOS-PAD-001');
INSERT INTO parts (reference, name, brand, part_type, price, stock, description)
SELECT 'MANN-FLT-001', 'Filtro de oleo', 'MANN-FILTER', 'Filtros', 14.50, 30, 'Filtro de oleo para varias motorizacoes.'
WHERE NOT EXISTS (SELECT 1 FROM parts WHERE reference = 'MANN-FLT-001');
INSERT INTO parts (reference, name, brand, part_type, price, stock, description)
SELECT 'NGK-SPK-001', 'Jogo de velas', 'NGK', 'Ignicao', 32.00, 12, 'Conjunto de velas de ignicao.'
WHERE NOT EXISTS (SELECT 1 FROM parts WHERE reference = 'NGK-SPK-001');
INSERT INTO parts (reference, name, brand, part_type, price, stock, description)
SELECT 'BREM-DISC-001', 'Discos de travao dianteiros', 'Brembo', 'Travagem', 118.00, 8, 'Par de discos ventilados.'
WHERE NOT EXISTS (SELECT 1 FROM parts WHERE reference = 'BREM-DISC-001');
