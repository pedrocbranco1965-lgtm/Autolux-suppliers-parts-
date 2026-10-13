class AuthStatusUnavailableError extends Error {
	constructor() {
		super('Nao foi possivel confirmar o acesso no PHP.');
		this.name = 'AuthStatusUnavailableError';
	}
}

// Chamar depois de verificar o JWT no Node.
// O expectedUserId vem do token ja verificado, nao de um campo do pedido.
async function isTokenActive(token, expectedUserId, config) {
	if (typeof token !== 'string' || token === '' || Buffer.byteLength(token, 'utf8') > 4096 || !Number.isInteger(expectedUserId) || expectedUserId < 1 || expectedUserId > 2147483647) {
		throw new TypeError('Dados invalidos para consultar a autenticacao.');
	}

	try {
		const endpoint = new URL(config.authStatusUrl);

		// Esta etapa e apenas local, como o auth-status.php.
		// Nunca obter este endereco a partir de dados do pedido.
		if (endpoint.protocol !== 'http:' || endpoint.hostname !== '127.0.0.1' || endpoint.username !== '' || endpoint.password !== '' || endpoint.search !== '' || endpoint.hash !== '') {
			throw new Error('Endereco interno de autenticacao invalido.');
		}

		const response = await fetch(endpoint, {
			method: 'POST',
			headers: {
				Accept: 'application/json',
				Authorization: `Bearer ${token}`,
			},
			redirect: 'error',
			cache: 'no-store',
			signal: AbortSignal.timeout(3000),
		});

		const contentType = (response.headers.get('content-type') ?? '').split(';')[0].trim().toLowerCase();

		if (![200, 401].includes(response.status) || contentType !== 'application/json') {
			await response.body?.cancel();
			throw new Error('Resposta inesperada do servico de autenticacao.');
		}

		const data = await response.json();

		if (data === null || typeof data !== 'object' || Array.isArray(data)) {
			throw new Error('Formato de resposta invalido.');
		}

		if (response.status === 401 && data.active === false) {
			return false;
		}

		if (response.status === 200 && data.active === true && data.user_id === expectedUserId) {
			return true;
		}

		throw new Error('Resposta de autenticacao incoerente.');
	} catch {
		// Nunca aceitar o acesso apenas porque a verificacao falhou.
		// Nao incluir tokens, chaves ou a resposta bruta na mensagem de erro.
		throw new AuthStatusUnavailableError();
	}
}

module.exports = { isTokenActive, AuthStatusUnavailableError };
