<?php
require_once __DIR__ . '/../middleware/requireAuth.php';
$config = require __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/SupplierApiService.php';
require_once __DIR__ . '/../services/formHelpers.php';
$suppliers = [];
$error = '';

try {
    $api = new SupplierApiService(
    $config['supplier_api_url'],
    $_COOKIE['autolux_access'] ?? ''
);
    $response = $api->getSuppliers();
    if (!is_array($response['data'] ?? null)) {
        throw new RuntimeException('Lista de fornecedores invalida.');
    }
    $suppliers = $response['data'];
} catch (Throwable $exception) {
    $error = displayError($exception);
    http_response_code(503);
}

$pageTitle = 'Fornecedores - AutoLux Parts';
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/navbar.php';
?>
<main class="container">
    <section class="page-heading"><p class="eyebrow">Node.js API</p><h1>Fornecedores</h1></section>
    <?php if ($error): ?><p class="notice error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <?php if (!$suppliers && !$error): ?><p class="notice">Ainda nao existem fornecedores.</p><?php endif; ?>
    <div class="parts-grid">
        <?php foreach ($suppliers as $supplier): ?>
            <article class="part-card"><span class="tag">Fornecedor</span><h2><?= htmlspecialchars($supplier['name']) ?></h2><p><?= htmlspecialchars($supplier['email']) ?></p><p class="muted"><?= htmlspecialchars($supplier['phone'] ?? '') ?></p></article>
        <?php endforeach; ?>
    </div>
</main>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
