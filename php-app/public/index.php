<?php

require_once __DIR__ . '/../middleware/requireAuth.php';

$pageTitle = 'AutoLux Parts - Gestao';
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/navbar.php';
?>

<main class="container">
    <section class="hero">
        <p class="eyebrow">AutoLux</p>
        <h1>Gestao de pecas automoveis</h1>
        <p>Catalogo, stock, clientes, vendas, fornecedores e encomendas numa unica solucao.</p>
        <div class="hero-actions">
            <a class="button primary" href="catalogo.php">Ver catalogo</a>
            <a class="button secondary" href="venda.php">Registar venda</a>
        </div>
    </section>

    <section class="dashboard-grid">
        <a class="dashboard-card" href="catalogo.php"><strong>Catalogo</strong><span>Consultar stock e filtrar pecas.</span></a>
        <a class="dashboard-card" href="venda.php"><strong>Vendas</strong><span>Selecionar cliente, peca e pagamento.</span></a>
        <a class="dashboard-card" href="fornecedores.php"><strong>Fornecedores</strong><span>Dados obtidos pela API Node.js.</span></a>
        <a class="dashboard-card" href="encomendas.php"><strong>Encomendas</strong><span>Criar e gerir encomendas a fornecedores.</span></a>
    </section>
</main>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
