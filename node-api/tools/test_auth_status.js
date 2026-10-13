const path = require('node:path');
const assert = require('node:assert/strict');
const { spawnSync } = require('node:child_process');

const phpBinary = '/Applications/MAMP/bin/php/php8.3.30/bin/php';
const phpDirectory = path.resolve(__dirname, '..', '..', 'php-app');

// Executa apenas estas operacoes locais de teste.
// Os dados passam por um canal interno, nao por argumentos com tokens.
function runPhp(action, data = {}) {
	const phpCode = `
require $argv[1] . '/services/JwtService.php';
require $argv[1] . '/classes/AuthTokenRepository.php';
$config = require $argv[1] . '/config/config.php';

if ($config['db']['name'] !== 'autolux_sales_db') {
    throw new RuntimeException('Base de dados PHP inesperada.');
}

$data = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);

if ($argv[2] === 'issue') {
    $jwt = new JwtService($config['jwt']);
    $result = $jwt->issue(1);
} else {
    $tokens = new AuthTokenRepository($config['db']);

    if ($argv[2] === 'register') {
        $id = $tokens->register(
            $data['userId'], $data['jti'],
            $data['issuedAt'], $data['expiresAt']
        );
        $result = ['id' => $id];

    } elseif ($argv[2] === 'revoke') {
        $tokens->revoke($data['userId'], $data['jti']);

        if ($tokens->isActive($data['userId'], $data['jti'])) {
            throw new RuntimeException('O token continua ativo.');
        }

        $result = ['revoked' => true];
    } else {
        throw new RuntimeException('Operacao de teste invalida.');
    }
}

echo json_encode($result, JSON_THROW_ON_ERROR);
`;

	// Nao passar a configuracao da Base 2 do Node para a Base 1 do PHP.
	// O PHP deve usar o seu config.php e config.local.php.
	const phpEnv = { ...process.env };

	for (const name of ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD']) {
		delete phpEnv[name];
	}

	const result = spawnSync(phpBinary, ['-d', 'display_errors=stderr', '-d', 'log_errors=0', '-r', phpCode, phpDirectory, action], {
		encoding: 'utf8',
		input: JSON.stringify(data),
		env: phpEnv,
		stdio: ['pipe', 'pipe', 'pipe'],
		timeout: 10000,
		maxBuffer: 65536,
		shell: false,
	});

	if (result.error || result.status !== 0) {
		throw new Error('Falhou a operacao PHP de teste.');
	}

	return JSON.parse(result.stdout);
}

async function main() {
	let stage = 'carregar a configuracao';
	let record = null;
	let recordId = null;
	let mustRevoke = false;
	let completed = false;

	try {
		const config = require('../config/auth');
		const { verifyAccessToken } = require('../services/jwtService');
		const { isTokenActive } = require('../services/authStatusService');

		stage = 'emitir e verificar o token de teste';
		const issued = runPhp('issue');
		record = verifyAccessToken(issued.token, config);
		assert.equal(record.userId, 1);

		stage = 'consultar o PHP antes do registo';
		assert.equal(await isTokenActive(issued.token, 1, config), false);
		console.log('[OK] PHP recusou o token ainda nao registado.');

		stage = 'registar o token na primeira base';
		// Permite tentar a revogacao mesmo se houver falha apos o INSERT.
		mustRevoke = true;
		recordId = runPhp('register', record).id;
		assert.ok(Number.isInteger(recordId) && recordId > 0);
		console.log('[OK] Token de teste registado para o utilizador 1.');

		stage = 'confirmar pelo HTTP que o token esta ativo';
		assert.equal(await isTokenActive(issued.token, 1, config), true);
		console.log('[OK] Node recebeu do PHP a confirmacao de acesso ativo.');

		stage = 'revogar apenas o token deste teste';
		runPhp('revoke', record);

		// Confirmar que a recusa seguinte nao se deve a expiracao.
		stage = 'confirmar a assinatura e a recusa apos revogacao';
		verifyAccessToken(issued.token, config);
		assert.equal(await isTokenActive(issued.token, 1, config), false);
		console.log('[OK] PHP recusou o mesmo token depois da revogacao.');
		completed = true;
	} catch (error) {
		console.error('[FALHOU] Etapa: ' + stage);
		console.error('Tipo de erro: ' + (error?.name ?? 'Error'));
		process.exitCode = 1;
	} finally {
		if (mustRevoke && record !== null) {
			try {
				runPhp('revoke', record);
			} catch {
				console.error('[ATENCAO] Nao foi possivel confirmar a revogacao final.');
				if (recordId !== null) console.error('Registo de teste: ' + recordId);
				completed = false;
				process.exitCode = 1;
			}
		}
	}

	if (completed) {
		console.log('Teste concluido. Registo de teste: ' + recordId + ' (revogado).');
	}
}

main();
