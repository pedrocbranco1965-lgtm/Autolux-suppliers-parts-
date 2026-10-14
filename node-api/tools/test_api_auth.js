const path = require('node:path');
const assert = require('node:assert/strict');
const { spawnSync } = require('node:child_process');

const phpBinary = '/Applications/MAMP/bin/php/php8.3.30/bin/php';
const phpDirectory = path.resolve(__dirname, '..', '..', 'php-app');
const apiBase = 'http://127.0.0.1:3000/api';

// Preparacao local do teste. Nao colocar este codigo numa rota publica.
function runPhp(action, data = {}) {
	const phpCode = `
require $argv[1] . '/services/JwtService.php';
require $argv[1] . '/classes/AuthTokenRepository.php';
$config = require $argv[1] . '/config/config.php';

if ($config['db']['name'] !== 'autolux_sales_db') {
    throw new RuntimeException('Base de dados inesperada.');
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
        throw new RuntimeException('Operacao invalida.');
    }
}
echo json_encode($result, JSON_THROW_ON_ERROR);
`;

	// Nao transmitir ao PHP as credenciais da base de fornecedores do Node.
	const env = { ...process.env };

	for (const key of ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD']) {
		delete env[key];
	}

	const result = spawnSync(phpBinary, ['-d', 'display_errors=stderr', '-d', 'log_errors=0', '-r', phpCode, phpDirectory, action], {
		encoding: 'utf8',
		input: JSON.stringify(data),
		env,
		stdio: ['pipe', 'pipe', 'pipe'],
		shell: false,
		timeout: 10000,
		maxBuffer: 65536,
	});

	if (result.error || result.status !== 0) {
		throw new Error('Falhou a preparacao PHP do teste.');
	}

	return JSON.parse(result.stdout);
}

async function checkRoutes(label, token, expectedStatus) {
	for (const route of ['/suppliers', '/orders']) {
		const headers = { Accept: 'application/json' };

		if (token !== null) {
			headers.Authorization = 'Bearer ' + token;
		}

		const response = await fetch(apiBase + route, {
			method: 'GET',
			headers,
			redirect: 'error',
			cache: 'no-store',
			signal: AbortSignal.timeout(8000),
		});

		if (response.status !== expectedStatus) {
			console.error('[HTTP] GET /api' + route + ': esperado ' + expectedStatus + ', recebido ' + response.status + '.');

			await response.body?.cancel();

			throw new Error('Codigo HTTP inesperado.');
		}

		const data = await response.json();

		assert.equal(data.success, expectedStatus === 200);

		if (expectedStatus === 200) {
			assert.ok(Array.isArray(data.data));
		} else {
			assert.equal(Object.hasOwn(data, 'data'), false);
		}

		console.log('[OK] ' + label + ': GET /api' + route + ' -> ' + expectedStatus);
	}
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

		stage = 'pedidos sem token';
		await checkRoutes('Sem token', null, 401);

		stage = 'emitir e verificar um token de teste';
		const issued = runPhp('issue');

		record = verifyAccessToken(issued.token, config);

		assert.equal(record.userId, 1);

		stage = 'pedidos com token ainda nao registado';
		await checkRoutes('Nao registado', issued.token, 401);

		stage = 'registar o token de teste';
		mustRevoke = true;

		recordId = runPhp('register', record).id;

		assert.ok(Number.isInteger(recordId) && recordId > 0);

		stage = 'pedidos com token ativo';
		await checkRoutes('Ativo', issued.token, 200);

		stage = 'pedidos com assinatura alterada';
		const segments = issued.token.split('.');

		segments[2] = (segments[2][0] === 'A' ? 'B' : 'A') + segments[2].slice(1);

		await checkRoutes('Assinatura alterada', segments.join('.'), 401);

		stage = 'revogar o token e repetir os pedidos';
		runPhp('revoke', record);

		verifyAccessToken(issued.token, config);

		await checkRoutes('Revogado', issued.token, 401);

		// Confirmar que a recusa nao resultou simplesmente de o JWT expirar.
		verifyAccessToken(issued.token, config);

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

				if (recordId !== null) {
					console.error('Registo de teste: ' + recordId);
				}

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
