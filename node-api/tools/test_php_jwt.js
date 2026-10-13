const path = require('node:path');
const assert = require('node:assert/strict');
const { spawnSync } = require('node:child_process');

let stage = 'carregar a configuracao e o verificador Node';

try {
	const config = require('../config/auth');
	const { verifyAccessToken } = require('../services/jwtService');

	const phpBinary = '/Applications/MAMP/bin/php/php8.3.30/bin/php';
	const phpDirectory = path.resolve(__dirname, '..', '..', 'php-app');

	// Codigo PHP fixo para este teste local, nao uma rota da API.
	// issue(1) nao consulta contas nem grava em auth_tokens.
	const phpCode = `
require $argv[1] . '/services/JwtService.php';
$config = require $argv[1] . '/config/config.php';
$service = new JwtService($config['jwt']);
$issued = $service->issue(1);
echo json_encode($issued, JSON_THROW_ON_ERROR);
`;

	stage = 'pedir ao PHP um token de teste';

	const result = spawnSync(phpBinary, ['-d', 'display_errors=stderr', '-d', 'log_errors=0', '-r', phpCode, phpDirectory], {
		encoding: 'utf8',
		timeout: 10000,
		maxBuffer: 65536,
		stdio: ['ignore', 'pipe', 'pipe'],
		shell: false,
	});

	if (result.error || result.status !== 0) {
		throw new Error('Nao foi possivel executar o PHP de teste.');
	}

	// A resposta fica na memoria deste processo; nao a imprimir.
	stage = 'interpretar a resposta do PHP';
	const issued = JSON.parse(result.stdout);

	assert.equal(typeof issued.token, 'string');
	assert.ok(issued.claims && typeof issued.claims === 'object');

	console.log('[OK] PHP emitiu um token de teste.');

	stage = 'verificar no Node o token emitido pelo PHP';
	const claims = verifyAccessToken(issued.token, config);

	assert.equal(claims.userId, 1);
	assert.equal(claims.jti, issued.claims.jti);
	assert.equal(claims.issuedAt, issued.claims.iat);
	assert.equal(claims.expiresAt, issued.claims.exp);

	console.log('[OK] Node verificou o JWT emitido pelo PHP.');
	console.log('[OK] Identificadores e datas correspondem.');

	stage = 'recusar uma copia com a assinatura alterada';
	const segments = issued.token.split('.');

	segments[2] = (segments[2][0] === 'A' ? 'B' : 'A') + segments[2].slice(1);

	const alteredToken = segments.join('.');

	assert.throws(() => verifyAccessToken(alteredToken, config), { name: 'JsonWebTokenError', message: 'invalid signature' });

	console.log('[OK] Node recusou a assinatura alterada.');
	console.log('Teste concluido. Nenhum token foi mostrado ou gravado na base.');
} catch (error) {
	// Nao mostrar tokens, chaves, saida bruta do PHP ou rastreios completos.
	console.error('[FALHOU] Etapa: ' + stage);
	console.error('Tipo de erro: ' + (error?.name ?? 'Error'));
	process.exitCode = 1;
}
