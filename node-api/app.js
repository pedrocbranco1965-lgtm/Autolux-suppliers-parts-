const express = require('express');

const supplierRoutes = require('./routes/supplierRoutes');
const orderRoutes = require('./routes/orderRoutes');

const authMiddleware = require('./middleware/authMiddleware');
const errorMiddleware = require('./middleware/errorMiddleware');

const app = express();

app.disable('x-powered-by');

app.use(express.json({ limit: '100kb' }));

// Estado da API: nao apresenta dados de fornecedores ou encomendas.
app.get('/api', (req, res) => {
	res.json({
		success: true,
		message: 'AutoLux Suppliers API online.',
	});
});

// Autenticacao antes das rotas, para todos os metodos HTTP.
app.use('/api/suppliers', authMiddleware, supplierRoutes);

app.use('/api/orders', authMiddleware, orderRoutes);

// Resposta para enderecos que nao correspondem a uma rota.
app.use((req, res) => {
	res.status(404).json({
		success: false,
		message: 'Rota nao encontrada.',
	});
});

app.use(errorMiddleware);

module.exports = app;
