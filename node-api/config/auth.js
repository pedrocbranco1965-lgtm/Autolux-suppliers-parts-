const fs = require('node:fs');
const path = require('node:path');
const { createPublicKey } = require('node:crypto');

require('dotenv').config({
	path: path.join(__dirname, '..', '.env'),
});

function requiredSetting(name) {
	const value = process.env[name];

	if (typeof value !== 'string' || value.trim() === '') {
		throw new Error('Falta configurar ' + name + ' no .env da API.');
	}

	return value.trim();
}

const publicKeyPath = requiredSetting('JWT_PUBLIC_KEY_PATH');
const issuer = requiredSetting('JWT_ISSUER');
const audience = requiredSetting('JWT_AUDIENCE');

if (!path.isAbsolute(publicKeyPath)) {
	throw new Error('JWT_PUBLIC_KEY_PATH deve conter um caminho absoluto.');
}

let publicKey;

try {
	const pem = fs.readFileSync(publicKeyPath, 'utf8').trim();

	// Aceitar apenas o ficheiro publico, nunca uma chave privada.
	if (!pem.startsWith('-----BEGIN PUBLIC KEY-----') || !pem.endsWith('-----END PUBLIC KEY-----')) {
		throw new Error('Formato de chave publica invalido.');
	}

	publicKey = createPublicKey(pem);

	if (publicKey.asymmetricKeyType !== 'rsa' || (publicKey.asymmetricKeyDetails?.modulusLength ?? 0) < 2048) {
		throw new Error('A chave publica deve ser RSA de pelo menos 2048 bits.');
	}
} catch {
	throw new Error('Nao foi possivel carregar a chave publica RSA. Verifique o caminho, o ficheiro e as permissoes.');
}

module.exports = {
	publicKey,
	algorithm: 'RS256',
	issuer,
	audience,
	authStatusUrl: requiredSetting('AUTH_STATUS_URL'),
};
