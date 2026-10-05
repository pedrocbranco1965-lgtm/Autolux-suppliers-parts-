const { pool } = require('../config/database');
const { orderTotal, lineTotal, decimal } = require('../utils/validation');

async function getAll() {
  const [rows] = await pool.execute(`
    SELECT o.*, s.name AS supplier_name FROM supplier_orders o
    JOIN suppliers s ON s.id = o.supplier_id ORDER BY o.id DESC
  `);
  return rows;
}

// Todas as operacoes de uma transacao usam a mesma ligacao.
async function transaction(work) {
  const connection = await pool.getConnection();
  let begun = false;
  try {
    await connection.beginTransaction();
    begun = true;
    const result = await work(connection);
    await connection.commit();
    begun = false;
    return result;
  } catch (error) {
    if (begun) {
      try { await connection.rollback(); }
      catch (rollbackError) { console.error('Falhou rollback:', rollbackError.message); }
    }
    throw error;
  } finally {
    connection.release();
  }
}

async function readOrder(connection, id) {
  const [orders] = await connection.execute(`
    SELECT o.*, s.name AS supplier_name FROM supplier_orders o
    JOIN suppliers s ON s.id = o.supplier_id WHERE o.id = ?
  `, [id]);
  if (!orders[0]) return null;
  const [items] = await connection.execute(
    'SELECT * FROM supplier_order_items WHERE order_id = ? ORDER BY id', [id]
  );
  return { ...orders[0], items };
}

async function findById(id) {
  return transaction(connection => readOrder(connection, id));
}

async function insertItems(connection, id, items) {
  for (const item of items) {
    await connection.execute(`
      INSERT INTO supplier_order_items
      (order_id, part_reference, part_name, quantity, unit_cost, subtotal)
      VALUES (?, ?, ?, ?, ?, ?)
    `, [id, item.part_reference, item.part_name, item.quantity, item.unit_cost,
      decimal(lineTotal(item.quantity, item.unit_cost))]);
  }
}

async function create(data) {
  return transaction(async connection => {
    const total = orderTotal(data.items);
    const [result] = await connection.execute(`
      INSERT INTO supplier_orders (supplier_id, reference, status, notes, total)
      VALUES (?, ?, ?, ?, ?)
    `, [data.supplier_id, data.reference, data.status, data.notes, total]);
    await insertItems(connection, result.insertId, data.items);
    // Leitura antes do commit: se falhar, a criacao inteira e revertida.
    return readOrder(connection, result.insertId);
  });
}

async function update(id, data) {
  return transaction(async connection => {
    const [rows] = await connection.execute('SELECT id FROM supplier_orders WHERE id = ? FOR UPDATE', [id]);
    if (!rows[0]) return null;
    await connection.execute(`
      UPDATE supplier_orders SET supplier_id = ?, reference = ?, status = ?, notes = ?, total = ?
      WHERE id = ?
    `, [data.supplier_id, data.reference, data.status, data.notes, orderTotal(data.items), id]);
    // PUT substitui a representacao completa, incluindo todos os itens.
    await connection.execute('DELETE FROM supplier_order_items WHERE order_id = ?', [id]);
    await insertItems(connection, id, data.items);
    return readOrder(connection, id);
  });
}

async function remove(id) {
  const [result] = await pool.execute('DELETE FROM supplier_orders WHERE id = ?', [id]);
  return result.affectedRows > 0;
}

module.exports = { getAll, findById, create, update, remove };
