const { JsonWebTokenError, TokenExpiredError, NotBeforeError } = require('jsonwebtoken');

const config = require('../config/auth');
const { verifyAccessToken } = require('../services/jwtService');
const { isTokenActive } = require('../services/authStatusService');

function denyAccess(res) {
	res.set('WWW-Authenticate', 'Bearer realm="autolux-pecas"');

	return res.status(401).json({
		success: false,
		message: 'Autenticacao necessaria ou token invalido.',
	});
}

async function authMiddleware(req, res, next) {
	res.set('Cache-Control', 'no-store');

	const authorization = req.get('Authorization');

	// Rejeitar pedidos com varios cabecalhos Authorization.
	const authorizationCount = req.rawHeaders.filter((value, index) => index % 2 === 0 && value.toLowerCase() === 'authorization').length;

	if (authorizationCount !== 1 || typeof authorization !== 'string' || Buffer.byteLength(authorization, 'utf8') > 4103) {
		return denyAccess(res);
	}

	// Aceitar apenas "Bearer" seguido de um JWT com tres segmentos.
	const match = /^Bearer ([A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+)$/i.exec(authorization);

	if (!match) {
		return denyAccess(res);
	}

	try {
		const token = match[1];

		// Primeiro: assinatura, emissor, destinatario, datas e identificadores.
		const claims = verifyAccessToken(token, config);

		// Depois: confirmar no PHP se o acesso continua ativo.
		const active = await isTokenActive(token, claims.userId, config);

		// Confirmar tambem que o token nao expirou durante o pedido ao PHP.
		if (active !== true || claims.expiresAt <= Math.floor(Date.now() / 1000)) {
			return denyAccess(res);
		}

		// Dados verificados para as etapas seguintes, sem guardar o JWT aqui.
		req.auth = {
			userId: claims.userId,
			jti: claims.jti,
			expiresAt: claims.expiresAt,
		};
	} catch (error) {
		if (error instanceof JsonWebTokenError || error instanceof TokenExpiredError || error instanceof NotBeforeError) {
			return denyAccess(res);
		}

		// Falha tecnica: recusar o pedido sem revelar dados internos.
		// Nao imprimir o token nem a resposta completa do PHP.
		console.error('AutoLux autenticacao: falha ao verificar o acesso.');

		return res.status(503).json({
			success: false,
			message: 'Nao foi possivel verificar o acesso. Tente novamente mais tarde.',
		});
	}

	// So continuar depois de todas as verificacoes terem passado.
	return next();
}

module.exports = authMiddleware;
