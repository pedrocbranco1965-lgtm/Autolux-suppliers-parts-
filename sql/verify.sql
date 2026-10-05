-- Apenas consultas de verificacao. Nao cria, altera nem apaga dados.
SELECT TABLE_SCHEMA, TABLE_NAME, ENGINE
FROM information_schema.TABLES
WHERE TABLE_SCHEMA IN ('autolux_sales_db', 'autolux_procurement_db')
ORDER BY TABLE_SCHEMA, TABLE_NAME;

SELECT 'clients' AS tabela, COUNT(*) AS registos FROM autolux_sales_db.clients
UNION ALL SELECT 'parts', COUNT(*) FROM autolux_sales_db.parts
UNION ALL SELECT 'payment_methods', COUNT(*) FROM autolux_sales_db.payment_methods
UNION ALL SELECT 'sales', COUNT(*) FROM autolux_sales_db.sales
UNION ALL SELECT 'sale_items', COUNT(*) FROM autolux_sales_db.sale_items
UNION ALL SELECT 'suppliers', COUNT(*) FROM autolux_procurement_db.suppliers
UNION ALL SELECT 'supplier_orders', COUNT(*) FROM autolux_procurement_db.supplier_orders
UNION ALL SELECT 'supplier_order_items', COUNT(*) FROM autolux_procurement_db.supplier_order_items;
