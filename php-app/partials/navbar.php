<?php

require_once __DIR__ . '/../services/formHelpers.php';

if (!isset($config)) {
    $config = require __DIR__ . '/../config/config.php';
}

$frontendUrl = $config['frontend_url'] ?? 'http://localhost:5173/';

$menuUser = $authenticatedUser ?? null;

?>

<header class="topbar">

    <a class="brand" href="index.php">
        AUTOLUX <span>PARTS</span>
    </a>

    <nav class="nav-links" aria-label="Menu do modulo de pecas">

        <a href="catalogo.php">Cat&aacute;logo</a>

        <a href="venda.php">Vendas</a>

        <a href="fornecedores.php">Fornecedores</a>

        <a href="encomendas.php">Encomendas</a>

        <a class="front-link" href="<?= h($frontendUrl) ?>">
            AutoLux Stand
        </a>

        <?php if (is_array($menuUser)): ?>

            <span>
                <?= h($menuUser['name'] ?? '') ?>
            </span>

            <form
                class="inline-form"
                method="POST"
                action="logout.php"
            >
                <?= csrfField() ?>

                <button
                    class="button danger small"
                    type="submit"
                >
                    Terminar sess&atilde;o
                </button>

            </form>

        <?php endif; ?>

    </nav>

</header>
