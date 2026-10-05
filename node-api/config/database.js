const path = require('node:path');
require('dotenv').config({ path: path.join(__dirname, '..', '.env') });
const mysql = require('mysql2/promise');
const port = Number(process.env.DB_PORT ?? 8889);
if (!Number.isInteger(port) || port < 1 || port > 65535) throw new Error('DB_PORT invalida.');
const pool = mysql.createPool({
  host: process.env.DB_HOST ?? '127.0.0.1',
  port,
  user: process.env.DB_USER ?? 'root',
  password: process.env.DB_PASSWORD ?? 'root', // Aceita palavra-passe vazia no ambiente local.
  database: process.env.DB_NAME ?? 'autolux_procurement_db',
  charset: 'utf8mb4',
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 100,
  connectTimeout: 5000
});
async function testConnection() {
  const connection = await pool.getConnection();
  try {
    await connection.ping();
    console.log('Ligacao a base de fornecedores efetuada com sucesso.');
  } finally { connection.release(); }
}
module.exports = { pool, testConnection };
