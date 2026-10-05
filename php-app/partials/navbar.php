<?php
if (!isset($config)) {
    $config = require __DIR__ . '/../config/config.php';
}

$frontendUrl = $config['frontend_url'] ?? 'http://localhost:5173/';
?>
<header class="topbar">
    <a class="brand" href="index.php">AUTOLUX <span>PARTS</span></a>
    <nav class="nav-links">
        <a href="catalogo.php">Catalogo</a>
        <a href="venda.php">Vendas</a>
        <a href="fornecedores.php">Fornecedores</a>
        <a href="encomendas.php">Encomendas</a>
        <a class="front-link" href="<?= htmlspecialchars($frontendUrl) ?>">AutoLux Stand</a>
    </nav>
</header>
