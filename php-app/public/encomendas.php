<?php
require_once __DIR__ . '/../services/formHelpers.php';
require_once __DIR__ . '/../services/SupplierApiService.php';
startFormSession();
$config = require __DIR__ . '/../config/config.php';
$message = takeFlash();
$error = '';
$suppliers = $orders = [];
$editingId = null;
$form = [
    'supplier_id' => '', 'reference' => '', 'status' => 'pendente', 'notes' => '',
    'items' => [['part_reference' => '', 'part_name' => '', 'quantity' => '1', 'unit_cost' => '']],
];
$statuses = ['pendente', 'enviado', 'recebido', 'cancelado'];

try {
    $api = new SupplierApiService($config['supplier_api_url']);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        checkCsrf();
        $action = Input::text($_POST['action'] ?? '', 'Acao', 12);
        if ($action === 'delete') {
            $api->deleteOrder(Input::positiveInt($_POST['id'] ?? null, 'Encomenda'));
            flashRedirect('encomendas.php', 'Encomenda apagada.');
        }
        if (!in_array($action, ['create', 'update'], true)) {
            throw new InvalidArgumentException('Acao nao reconhecida.');
        }
        if ($action === 'update') {
            $editingId = Input::positiveInt($_POST['id'] ?? null, 'Encomenda');
        }
        $form = [
            'supplier_id' => Input::positiveInt($_POST['supplier_id'] ?? null, 'Fornecedor'),
            'reference' => Input::text($_POST['reference'] ?? '', 'Referencia', 60),
            'status' => Input::text($_POST['status'] ?? 'pendente', 'Estado', 20),
            'notes' => Input::text($_POST['notes'] ?? '', 'Notas', 2000, false),
            'items' => $_POST['items'] ?? null,
        ];
        if (!in_array($form['status'], $statuses, true)) {
            throw new InvalidArgumentException('Estado invalido.');
        }
        if (!is_array($form['items']) || count($form['items']) < 1 || count($form['items']) > 100) {
            throw new InvalidArgumentException('A encomenda deve ter entre 1 e 100 itens.');
        }
        $items = [];
        foreach ($form['items'] as $item) {
            if (!is_array($item)) {
                throw new InvalidArgumentException('Item de encomenda invalido.');
            }
            $items[] = [
                'part_reference' => Input::text($item['part_reference'] ?? '', 'Referencia da peca', 60),
                'part_name' => Input::text($item['part_name'] ?? '', 'Nome da peca', 150),
                'quantity' => Input::positiveInt($item['quantity'] ?? null, 'Quantidade'),
                'unit_cost' => Money::decimal(Money::toCents($item['unit_cost'] ?? null)),
            ];
        }
        $form['items'] = $items;
        if ($editingId !== null) {
            $api->updateOrder($editingId, $form);
            flashRedirect('encomendas.php', 'Encomenda atualizada.');
        }
        $api->createOrder($form);
        flashRedirect('encomendas.php', 'Encomenda registada na API Node.');
    }
    if (isset($_GET['edit'])) {
        $editingId = Input::positiveInt($_GET['edit'], 'Encomenda');
        $response = $api->getOrder($editingId);
        if (!is_array($response['data'] ?? null) || !is_array($response['data']['items'] ?? null)) {
            throw new RuntimeException('Dados da encomenda invalidos.');
        }
        // Ao editar, todas as linhas existentes sao apresentadas e reenviadas.
        $form = $response['data'];
    }
} catch (Throwable $exception) {
    $error = displayError($exception);
    http_response_code($exception instanceof InvalidArgumentException ? 422 : 503);
}
try {
    $api = new SupplierApiService($config['supplier_api_url']);
    $supplierResponse = $api->getSuppliers();
    $orderResponse = $api->getOrders();
    if (!is_array($supplierResponse['data'] ?? null) || !is_array($orderResponse['data'] ?? null)) {
        throw new RuntimeException('Listagens da API invalidas.');
    }
    $suppliers = $supplierResponse['data'];
    $orders = $orderResponse['data'];
} catch (Throwable $exception) {
    $error = $error ?: displayError($exception);
    http_response_code(503);
}
// Nao deixar dados inesperados do pedido quebrar a apresentacao do formulario.
$formItems = is_array($form['items'] ?? null) ? array_values($form['items']) : [];
if (!$formItems) {
    $formItems = [['part_reference' => '', 'part_name' => '', 'quantity' => '1', 'unit_cost' => '']];
}
$pageTitle = 'Encomendas - AutoLux Pecas';
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/navbar.php';
?>
<main class="container">
    <section class="page-heading"><p class="eyebrow">Compras</p><h1>Encomendas a fornecedores</h1></section>
    <?php if ($message): ?><p class="notice success" role="status"><?= h($message) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="notice error" role="alert"><?= h($error) ?></p><?php endif; ?>
    <h2><?= $editingId !== null ? 'Editar encomenda #' . $editingId : 'Nova encomenda' ?></h2>
    <form class="panel form-grid" method="POST" action="encomendas.php">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="<?= $editingId !== null ? 'update' : 'create' ?>">
        <?php if ($editingId !== null): ?><input type="hidden" name="id" value="<?= $editingId ?>"><?php endif; ?>
        <label>Fornecedor
            <select name="supplier_id" required>
                <option value="">Selecionar...</option>
                <?php foreach ($suppliers as $supplier): ?>
                <option value="<?= (int) $supplier['id'] ?>" <?= (string) $supplier['id'] === (string) ($form['supplier_id'] ?? '') ? 'selected' : '' ?>>
                    <?= h($supplier['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Referencia da encomenda
            <input name="reference" maxlength="60" value="<?= h($form['reference'] ?? '') ?>" placeholder="ENC-TESTE-001" required>
        </label>
        <label>Estado
            <select name="status" required>
                <?php foreach ($statuses as $status): ?>
                <option value="<?= h($status) ?>" <?= ($form['status'] ?? '') === $status ? 'selected' : '' ?>><?= h($status) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php foreach (array_slice($formItems, 0, 100) as $i => $item): $item = is_array($item) ? $item : []; ?>
        <fieldset class="full form-grid item-fields">
            <legend>Item <?= $i + 1 ?></legend>
            <label>Referencia da peca
                <input name="items[<?= $i ?>][part_reference]" maxlength="60" value="<?= h($item['part_reference'] ?? '') ?>" required>
            </label>
            <label>Nome da peca
                <input name="items[<?= $i ?>][part_name]" maxlength="150" value="<?= h($item['part_name'] ?? '') ?>" required>
            </label>
            <label>Quantidade
                <input type="number" name="items[<?= $i ?>][quantity]" min="1" max="2147483647" step="1" value="<?= h($item['quantity'] ?? '') ?>" required>
            </label>
            <label>Custo unitario
                <input type="number" name="items[<?= $i ?>][unit_cost]" min="0" max="99999999.99" step="0.01" value="<?= h($item['unit_cost'] ?? '') ?>" required>
            </label>
        </fieldset>
        <?php endforeach; ?>
        <label class="full">Notas<textarea name="notes" rows="3" maxlength="2000"><?= h($form['notes'] ?? '') ?></textarea></label>
        <button class="button primary" type="submit" <?= !$suppliers ? 'disabled' : '' ?>>
            <?= $editingId !== null ? 'Guardar alteracoes' : 'Submeter encomenda' ?>
        </button>
        <?php if ($editingId !== null): ?><a class="button ghost" href="encomendas.php">Cancelar edicao</a><?php endif; ?>
        <p class="full muted">Mudar o estado para recebido nao aumenta automaticamente o stock. Essa integracao ainda nao esta implementada.</p>
    </form>
    <section class="table-section">
        <h2>Encomendas registadas</h2>
        <div class="table-wrap"><table>
            <thead><tr><th>ID</th><th>Referencia</th><th>Fornecedor</th><th>Estado</th><th>Total</th><th>Acoes</th></tr></thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?= (int) $order['id'] ?></td><td><?= h($order['reference']) ?></td>
                    <td><?= h($order['supplier_name']) ?></td><td><?= h($order['status']) ?></td>
                    <td><?= number_format((float) $order['total'], 2, ',', '.') ?> EUR</td>
                    <td>
                        <a class="button ghost small" href="encomendas.php?edit=<?= (int) $order['id'] ?>">Editar</a>
                        <form method="POST" action="encomendas.php" class="inline-form" onsubmit="return confirm('Apagar esta encomenda e os seus itens?');">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
                            <button class="button danger small" type="submit">Apagar</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$orders && !$error): ?><tr><td colspan="6">Ainda nao existem encomendas.</td></tr><?php endif; ?>
            </tbody>
        </table></div>
    </section>
</main>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
