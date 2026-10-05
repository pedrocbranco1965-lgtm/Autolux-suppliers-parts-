const { pool } = require('../config/database');

async function getAll() {
  const [rows] = await pool.execute('SELECT * FROM suppliers ORDER BY name');
  return rows;
}
async function findById(id) {
  const [rows] = await pool.execute('SELECT * FROM suppliers WHERE id = ?', [id]);
  return rows[0] || null;
}
async function create(data) {
  const [result] = await pool.execute(
    'INSERT INTO suppliers (name, email, phone) VALUES (?, ?, ?)',
    [data.name, data.email, data.phone]
  );
  return findById(result.insertId);
}
async function update(id, data) {
  const connection = await pool.getConnection();
  let begun = false;
  try {
    await connection.beginTransaction(); begun = true;
    const [rows] = await connection.execute('SELECT id FROM suppliers WHERE id = ? FOR UPDATE', [id]);
    if (!rows[0]) {
      await connection.commit(); begun = false;
      return null;
    }
    // Nao confundimos "valores iguais" com "fornecedor inexistente".
    await connection.execute('UPDATE suppliers SET name = ?, email = ?, phone = ? WHERE id = ?',
      [data.name, data.email, data.phone, id]);
    const [updated] = await connection.execute('SELECT * FROM suppliers WHERE id = ?', [id]);
    await connection.commit(); begun = false;
    return updated[0];
  } catch (error) {
    if (begun) {
      try { await connection.rollback(); }
      catch (rollbackError) { console.error('Falhou rollback:', rollbackError.message); }
    }
    throw error;
  } finally { connection.release(); }
}
async function remove(id) {
  const [result] = await pool.execute('DELETE FROM suppliers WHERE id = ?', [id]);
  return result.affectedRows > 0;
}
module.exports = { getAll, findById, create, update, remove };
