<?php
require_once __DIR__ . '/../middleware/requireAuth.php';
require_once __DIR__ . '/../services/formHelpers.php';
startFormSession();
$config = require __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/ClientRepository.php';
require_once __DIR__ . '/../classes/PartRepository.php';
require_once __DIR__ . '/../classes/PaymentMethodRepository.php';
require_once __DIR__ . '/../classes/SaleRepository.php';

$message = takeFlash();
$error = '';
$clients = $parts = $paymentMethods = $sales = [];
$form = ['client_id' => '', 'part_id' => '', 'quantity' => '1', 'payment_method_id' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($form) as $key) {
        $form[$key] = is_scalar($_POST[$key] ?? null) ? (string) $_POST[$key] : '';
    }
    try {
        checkCsrf();
        $clientId = Input::positiveInt($_POST['client_id'] ?? null, 'Cliente');
        $partId = Input::positiveInt($_POST['part_id'] ?? null, 'Peca');
        $quantity = Input::positiveInt($_POST['quantity'] ?? null, 'Quantidade');
        $paymentId = Input::positiveInt($_POST['payment_method_id'] ?? null, 'Pagamento');
        $repository = new SaleRepository($config['db']);
        $saleId = $repository->createSale($clientId, $partId, $quantity, $paymentId);
        // POST -> 303 -> GET: atualizar a pagina seguinte nao repete a venda.
        flashRedirect('venda.php', 'Venda registada com sucesso. Numero: ' . $saleId);
    } catch (Throwable $exception) {
        $error = displayError($exception);
        http_response_code($exception instanceof InvalidArgumentException ? 422 : 503);
    }
}
try {
    $clients = (new ClientRepository($config['db']))->getAll();
    $parts = (new PartRepository($config['db']))->getAll();
    $paymentMethods = (new PaymentMethodRepository($config['db']))->getAll();
    $sales = (new SaleRepository($config['db']))->getAll();
} catch (Throwable $exception) {
    $error = $error ?: displayError($exception);
    http_response_code(503);
}
$pageTitle = 'Vendas - AutoLux Pecas';
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/navbar.php';
?>
<main class="container">
    <section class="page-heading"><p class="eyebrow">Clientes</p><h1>Registar venda</h1></section>
    <?php if ($message): ?><p class="notice success" role="status"><?= h($message) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="notice error" role="alert"><?= h($error) ?></p><?php endif; ?>
    <form class="panel form-grid" method="POST" action="venda.php">
        <?= csrfField() ?>
        <label>Cliente
            <select name="client_id" required>
                <option value="">Selecionar...</option>
                <?php foreach ($clients as $client): ?>
                <option value="<?= (int) $client['id'] ?>" <?= (string) $client['id'] === $form['client_id'] ? 'selected' : '' ?>>
                    <?= h($client['name'] . ' - ' . $client['email']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Peca
            <select name="part_id" required>
                <option value="">Selecionar...</option>
                <?php foreach ($parts as $part): ?>
                <option value="<?= (int) $part['id'] ?>"
                    <?= (string) $part['id'] === $form['part_id'] ? 'selected' : '' ?>
                    <?= (int) $part['stock'] === 0 ? 'disabled' : '' ?>>
                    <?= h($part['reference'] . ' - ' . $part['name'] . ' | ' . $part['price'] . ' EUR | Stock: ' . $part['stock']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Quantidade
            <input type="number" name="quantity" min="1" max="2147483647" step="1" value="<?= h($form['quantity']) ?>" required>
        </label>
        <label>Pagamento
            <select name="payment_method_id" required>
                <option value="">Selecionar...</option>
                <?php foreach ($paymentMethods as $method): ?>
                <option value="<?= (int) $method['id'] ?>" <?= (string) $method['id'] === $form['payment_method_id'] ? 'selected' : '' ?>>
                    <?= h($method['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </label>
        <button class="button primary" type="submit" <?= !$clients || !$parts || !$paymentMethods ? 'disabled' : '' ?>>Concluir venda</button>
        <p class="full muted">Registo academico da venda. Esta aplicacao nao processa pagamentos reais.</p>
    </form>
    <section class="table-section">
        <h2>Vendas registadas</h2>
        <div class="table-wrap"><table>
            <thead><tr><th>ID</th><th>Cliente</th><th>Pagamento</th><th>Total</th><th>Data</th></tr></thead>
            <tbody>
                <?php foreach ($sales as $sale): ?>
                <tr>
                    <td><?= (int) $sale['id'] ?></td><td><?= h($sale['client_name']) ?></td>
                    <td><?= h($sale['payment_method']) ?></td>
                    <td><?= number_format((float) $sale['total'], 2, ',', '.') ?> EUR</td>
                    <td><?= h($sale['sold_at']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$sales && !$error): ?><tr><td colspan="5">Ainda nao existem vendas.</td></tr><?php endif; ?>
            </tbody>
        </table></div>
    </section>
</main>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
