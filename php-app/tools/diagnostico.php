<?php
// Apenas Terminal; nao publica configuracoes ou credenciais no browser.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$ok = true;
function report(string $label, bool $passed): void {
    global $ok; $ok = $ok && $passed;
    echo ($passed ? '[OK] ' : '[FALTA] ') . $label . PHP_EOL;
}
report('PHP 8.1 ou superior', PHP_VERSION_ID >= 80100);
report('PHP de 64 bits', PHP_INT_SIZE >= 8);
report('PDO MySQL', in_array('mysql', PDO::getAvailableDrivers(), true));
report('cURL', function_exists('curl_init'));
echo 'PHP executado: ' . PHP_BINARY . PHP_EOL;
if (in_array('--connect', $argv, true) && $ok) {
    require_once __DIR__ . '/../classes/ClientRepository.php';
    require_once __DIR__ . '/../services/SupplierApiService.php';
    $config = require __DIR__ . '/../config/config.php';
    try {
        $clients = (new ClientRepository($config['db']))->getAll();
        report('Ligacao PHP -> MySQL: ' . count($clients) . ' clientes', true);
    } catch (Throwable $error) { report('Ligacao PHP -> MySQL (confirme MAMP/config)', false); }
    try {
        $data = (new SupplierApiService($config['supplier_api_url']))->getSuppliers();
        report('Ligacao PHP -> Node -> MySQL', is_array($data['data'] ?? null));
    } catch (Throwable $error) { report('Ligacao a API (confirme npm start)', false); }
}
exit($ok ? 0 : 1);
