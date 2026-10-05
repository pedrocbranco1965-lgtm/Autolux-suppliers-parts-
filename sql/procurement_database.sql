CREATE DATABASE IF NOT EXISTS autolux_procurement_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE autolux_procurement_db;

CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(30),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS supplier_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    reference VARCHAR(60) NOT NULL UNIQUE,
    status ENUM('pendente','enviado','recebido','cancelado') NOT NULL DEFAULT 'pendente',
    notes TEXT,
    total DECIMAL(10,2) NOT NULL DEFAULT 0,
    ordered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_supplier
        FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS supplier_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    part_reference VARCHAR(60) NOT NULL,
    part_name VARCHAR(150) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    unit_cost DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id) REFERENCES supplier_orders(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO suppliers (name, email, phone)
SELECT 'Bosch Portugal', 'vendas@bosch-exemplo.pt', '210000001'
WHERE NOT EXISTS (SELECT 1 FROM suppliers WHERE email = 'vendas@bosch-exemplo.pt');
INSERT INTO suppliers (name, email, phone)
SELECT 'Brembo Distribuicao', 'encomendas@brembo-exemplo.pt', '210000002'
WHERE NOT EXISTS (SELECT 1 FROM suppliers WHERE email = 'encomendas@brembo-exemplo.pt');
INSERT INTO suppliers (name, email, phone)
SELECT 'AutoParts Iberia', 'comercial@autoparts-exemplo.pt', '210000003'
WHERE NOT EXISTS (SELECT 1 FROM suppliers WHERE email = 'comercial@autoparts-exemplo.pt');
