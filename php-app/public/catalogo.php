<?php
$config = require __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/PartRepository.php';

require_once __DIR__ . '/../services/formHelpers.php';
$filters = ['brand' => '', 'part_type' => '', 'min_price' => '', 'max_price' => ''];
$parts = $brands = $types = [];
$error = '';
try {
    foreach (array_keys($filters) as $key) {
        $filters[$key] = Input::text($_GET[$key] ?? '', $key, 100, false);
    }
    $repository = new PartRepository($config['db']);
    $brands = $repository->getBrands();
    $types = $repository->getTypes();
    $parts = $repository->getAll($filters);
} catch (Throwable $exception) {
    $error = displayError($exception);
    http_response_code($exception instanceof InvalidArgumentException ? 422 : 503);
}
$pageTitle = 'Catalogo - AutoLux Parts';
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/navbar.php';
?>
<main class="container">
    <section class="page-heading">
        <p class="eyebrow">Stock</p>
        <h1>Catalogo de pecas</h1>
    </section>

    <?php if ($error): ?><p class="notice error" role="alert"><?= h($error) ?></p><?php endif; ?>
    <form class="filters" method="GET">
        <label>Marca
            <select name="brand">
                <option value="">Todas</option>
                <?php foreach ($brands as $brand): ?>
                    <option value="<?= htmlspecialchars($brand) ?>" <?= $filters['brand'] === $brand ? 'selected' : '' ?>><?= htmlspecialchars($brand) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Tipo de peca
            <select name="part_type">
                <option value="">Todos</option>
                <?php foreach ($types as $type): ?>
                    <option value="<?= htmlspecialchars($type) ?>" <?= $filters['part_type'] === $type ? 'selected' : '' ?>><?= htmlspecialchars($type) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Preco minimo
            <input type="number" step="0.01" min="0" name="min_price" value="<?= htmlspecialchars((string) $filters['min_price']) ?>">
        </label>

        <label>Preco maximo
            <input type="number" step="0.01" min="0" name="max_price" value="<?= htmlspecialchars((string) $filters['max_price']) ?>">
        </label>

        <button class="button primary" type="submit">Filtrar</button>
        <a class="button ghost" href="catalogo.php">Limpar</a>
    </form>

    <div class="parts-grid">
        <?php foreach ($parts as $part): ?>
            <article class="part-card">
                <div class="part-card-top">
                    <span class="tag"><?= htmlspecialchars($part['brand']) ?></span>
                    <span class="stock <?= (int) $part['stock'] > 0 ? 'ok' : 'empty' ?>">Stock: <?= (int) $part['stock'] ?></span>
                </div>
                <h2><?= htmlspecialchars($part['name']) ?></h2>
                <p class="muted"><?= htmlspecialchars($part['reference']) ?> · <?= htmlspecialchars($part['part_type']) ?></p>
                <p><?= htmlspecialchars($part['description'] ?? '') ?></p>
                <strong class="price"><?= number_format((float) $part['price'], 2, ',', '.') ?> EUR</strong>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if (!$parts && !$error): ?>
        <p class="notice">Nao foram encontradas pecas com estes filtros.</p>
    <?php endif; ?>
</main>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
