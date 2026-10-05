const { pool, testConnection } = require('./config/database');
const app = require('./app');
const PORT = Number(process.env.PORT ?? 3000);
const HOST = process.env.HOST ?? '127.0.0.1'; // Desenvolvimento local, sem JWT nesta revisao.
async function start() {
  try {
    if (!Number.isInteger(PORT) || PORT < 1 || PORT > 65535) throw new Error('PORT invalida.');
    await testConnection();
    const server = app.listen(PORT, HOST, () => {
      console.log(`AutoLux Suppliers API em http://${HOST}:${PORT}`);
    });
    server.on('error', async error => {
      console.error(error.code === 'EADDRINUSE' ? `A porta ${PORT} esta ocupada.` : error.message);
      await pool.end();
      process.exitCode = 1;
    });
    for (const signal of ['SIGINT', 'SIGTERM']) {
      process.once(signal, () => {
        server.close(async () => { await pool.end(); process.exitCode = 0; });
      });
    }
  } catch (error) {
    console.error('Nao foi possivel iniciar a API:', error.message);
    await pool.end();
    process.exitCode = 1;
  }
}
start();
