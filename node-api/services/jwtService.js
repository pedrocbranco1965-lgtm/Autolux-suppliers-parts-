const jwt = require('jsonwebtoken');

// Corresponde aos 900 segundos configurados no PHP para este projeto.
const MAX_LIFETIME_SECONDS = 900;

// Valida a assinatura e os campos do JWT.
// Nao consulta a base, nao verifica revogacao e nao autoriza uma rota.
function verifyAccessToken(token, config) {
	if (
		!config ||
		config.algorithm !== 'RS256' ||
		!config.publicKey ||
		typeof config.issuer !== 'string' ||
		config.issuer.trim() === '' ||
		typeof config.audience !== 'string' ||
		config.audience.trim() === ''
	) {
		throw new Error('Configuracao JWT invalida.');
	}

	if (typeof token !== 'string' || token === '' || Buffer.byteLength(token, 'utf8') > 4096) {
		throw new jwt.JsonWebTokenError('Token ausente ou demasiado longo.');
	}

	const now = Math.floor(Date.now() / 1000);

	// A chave e as regras vem do nosso config/auth.js, nunca do pedido HTTP.
	const verified = jwt.verify(token, config.publicKey, {
		algorithms: ['RS256'],
		issuer: config.issuer,
		audience: config.audience,
		complete: true,
		clockTolerance: 0,
		clockTimestamp: now,
	});

	const { header, payload } = verified;

	if (header.typ !== 'JWT' || Object.hasOwn(header, 'crit')) {
		throw new jwt.JsonWebTokenError('Cabecalho JWT nao permitido.');
	}

	if (payload === null || typeof payload !== 'object' || Array.isArray(payload) || payload.iss !== config.issuer || payload.aud !== config.audience) {
		throw new jwt.JsonWebTokenError('Campos JWT invalidos.');
	}

	if (typeof payload.sub !== 'string') {
		throw new jwt.JsonWebTokenError('Utilizador JWT invalido.');
	}

	const userId = Number(payload.sub);

	if (
		!Number.isInteger(userId) ||
		userId < 1 ||
		userId > 2147483647 ||
		String(userId) !== payload.sub ||
		typeof payload.jti !== 'string' ||
		payload.jti.length !== 64 ||
		!/^[a-f0-9]{64}$/.test(payload.jti)
	) {
		throw new jwt.JsonWebTokenError('Identificadores JWT invalidos.');
	}

	for (const field of ['iat', 'nbf', 'exp']) {
		if (!Number.isSafeInteger(payload[field])) {
			throw new jwt.JsonWebTokenError('Datas JWT ausentes ou invalidas.');
		}
	}

	if (payload.iat < 0 || payload.iat > now || payload.nbf !== payload.iat || payload.exp <= now || payload.exp <= payload.iat || payload.exp - payload.iat > MAX_LIFETIME_SECONDS) {
		throw new jwt.JsonWebTokenError('Token expirado ou com datas incoerentes.');
	}

	// Devolver apenas os campos que o nosso middleware vai precisar.
	return {
		userId,
		jti: payload.jti,
		issuedAt: payload.iat,
		expiresAt: payload.exp,
	};
}

module.exports = { verifyAccessToken };
