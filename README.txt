AUTOLUX PECAS - FULL STACK INTEGRADO (Com frontend Autolux Stand)
Aluno: Pedro Castel-Branco
Versao de trabalho: 1.3 - revisao tecnica para testes locais
Base desta revisao: AutoLux_FullStack_Integrado_v1.2.zip
Pacote: AutoLux_FullStack_Integrado_v1.3.zip
Apoio pedagógico e técnico:
ChatGPT — apoio na explicação dos conceitos, na elaboração
e revisão do código e na resolução de erros durante
o desenvolvimento do projeto.

VERSAO INTEGRADA.
Preserva React/Vite original + PHP/PDO + Node/Express + duas bases MySQL.
Menu React: "Peças". Menu PHP: "AutoLux Stand".
Nao inclui login/JWT. Nao publicar a gestao ou a API abertamente na Internet.Proxima versão 1.4
A versao 1.3 corrige codigo.

1. O QUE CADA AREA FAZ

frontend/ : site de viaturas React/Vite, favoritos e contacto simulado.
php-app/  : paginas dinamicas PHP de pecas, vendas, fornecedores e encomendas.
node-api/ : API REST Express, exclusiva para fornecedores e encomendas.
sql/      : instalacao das duas bases e consultas de verificacao.
docs/     : memoria descritiva, revisao, alteracoes e testes.
tests/    : verificacoes PHP sem base real.

O React le frontend/public/data/vehicles.json. A ligacao ao PHP e navegacao,
nao sincronizacao entre viaturas e pecas. O JSON nao e uma terceira base.
Os contactos React sao uma simulacao e os favoritos permanecem em memoria.
Os metodos de pagamento registam a escolha; nao executam pagamentos reais.

2. BASES DE DADOS UTILIZADAS

Sistema: MySQL com tabelas InnoDB.

Base 1: autolux_sales_db
Acesso: PHP atraves de PDO.
Tabelas: clients, parts, payment_methods, sales, sale_items.
Script: sql/sales_database.sql.
Guarda clientes, pecas/stock, metodos de pagamento e vendas com os seus itens.

Base 2: autolux_procurement_db
Acesso: API Node.js atraves de mysql2.
Tabelas: suppliers, supplier_orders, supplier_order_items.
Script: sql/procurement_database.sql.
Guarda fornecedores, encomendas e os seus itens.
O PHP comunica com esta area por HTTP/JSON; nao usa PDO para a Base 2.

Nao e necessario criar uma terceira base.

3. REQUISITOS DO AMBIENTE

- PHP 8.1 ou superior, 64 bits, extensoes PDO MySQL e cURL.
- MAMP com Apache e MySQL ativos.
- Node.js >= 22.12.0 e npm para esta configuracao do frontend.
- Navegador. Internet necessaria para instalar os pacotes e carregar
  as imagens externas das viaturas.

A versao do PHP usada no Terminal pode ser diferente da usada no Apache.
Confirmar ambas no MAMP. Nao usar Live Server para executar paginas PHP.

4. ATUALIZAR A PARTIR DA VERSAO 1.2 SEM PERDER TRABALHO

- Guardar uma copia da pasta atual antes de substituir ficheiros.
- Extrair este ZIP em separado e consultar docs/alteracoes-v1.3.md.
- Preservar os .env locais do frontend e da API.
- Se alterou php-app/config/config.php, transferir os valores locais para
  php-app/config/config.local.php, usando o modelo config.local.example.php.
- Nao apagar bases nem tabelas que ja tenham registos.
- A estrutura/nomes das tabelas nao mudaram. A declaracao ENGINE=InnoDB
  afeta tabelas novas; CREATE TABLE IF NOT EXISTS nao altera tabelas existentes.
- Executar sql/verify.sql para confirmar as tabelas/engines existentes.

5. INSTALACAO LOCAL NO MAC

Pasta proposta:
/Applications/MAMP/htdocs/AutoLux

5.1 MAMP E MYSQL

Iniciar Apache e MySQL no MAMP e abrir phpMyAdmin.
Numa instalacao nova, importar:
  sql/sales_database.sql
  sql/procurement_database.sql

Em seguida executar sql/verify.sql.
Dados iniciais esperados numa base nova:
  2 clientes; 4 pecas; 3 metodos de pagamento; 3 fornecedores.
  0 vendas; 0 itens de venda; 0 encomendas; 0 itens de encomenda.
Se ja existirem dados, estes numeros podem ser diferentes.
Todas as oito tabelas devem usar InnoDB antes de testar transacoes.

Valores locais de exemplo (confirmar no MAMP):
  host 127.0.0.1, MySQL 8889, user root, password root.
  Apache 8888 e MySQL 8889 sao portas diferentes.

5.2 CONFIGURAR PHP

Copiar, apenas se ainda nao existir configuracao local:
  php-app/config/config.local.example.php
para:
  php-app/config/config.local.php

Ajustar porta, utilizador e palavra-passe do MySQL.
O valor '' e permitido para uma palavra-passe local vazia.
O ficheiro local e ignorado pelo Git e nao deve ser publicado.
O PHP NAO carrega automaticamente o .env da API Node.

Opcional no Terminal com o PHP correto do MAMP:
  php php-app/tools/diagnostico.php

5.3 API NODE - PRIMEIRO TERMINAL

cd /Applications/MAMP/htdocs/AutoLux/node-api
test -f .env || cp .env.example .env
npm install
npm test
npm start

Confirmar DB_NAME=autolux_procurement_db no .env.
A API fica local em http://127.0.0.1:3000/api.
Nao fechar este terminal enquanto a aplicacao estiver em teste.
Se a porta 3000 estiver ocupada, identificar o processo ou alterar PORT
E o supplier_api_url no PHP. Nao terminar processos desconhecidos.

5.4 FRONTEND - SEGUNDO TERMINAL

cd /Applications/MAMP/htdocs/AutoLux/frontend
test -f .env || cp .env.example .env
npm install
npm run build
npm run dev

Abrir http://localhost:5173/.
A porta e fixa (strictPort). Se ocupada, o Vite avisa em vez de mudar para 5174.
Depois de alterar .env, reiniciar o Vite.
As versoes diretas foram fixadas a partir do package-lock do repositorio
original autolux-final. Nesta revisao nao foi possivel instalar npm no
ambiente de verificacao; os package-lock.json devem ser gerados no Mac,
revistos e incluidos no Git. Nao foram fabricados lockfiles.

5.5 PHP NO BROWSER

http://localhost:8888/AutoLux/php-app/public/index.php

Este endereco pressupoe a pasta AutoLux e o Apache na porta 8888.
VITE_BACKOFFICE_URL (frontend/.env) aponta para esse endereco.
frontend_url (config PHP) aponta para http://localhost:5173/.

A .htaccess incluida desativa listagem de pastas e bloqueia diretorios de
codigo/configuracao quando o Apache permite as diretivas. Confirmar que
/AutoLux/node-api/.env e /AutoLux/sql/sales_database.sql nao sao servidos.
Isso nao substitui autenticacao nem uma configuracao segura de producao.

6. FLUXOS E ROTAS

PHP:
  index.php         painel
  catalogo.php      catalogo de pecas e filtros GET
  venda.php         formulario POST + transacao + stock
  fornecedores.php fornecedores recebidos da API
  encomendas.php    criar/listar/editar/apagar encomendas pela API

Node:
  GET    /api
  GET    /api/suppliers
  GET    /api/suppliers/:id
  POST   /api/suppliers
  PUT    /api/suppliers/:id
  DELETE /api/suppliers/:id
  GET    /api/orders
  GET    /api/orders/:id
  POST   /api/orders
  PUT    /api/orders/:id
  DELETE /api/orders/:id

PUT recebe a representacao completa da encomenda, incluindo todos os itens.
O formulario de edicao le a encomenda por ID e preserva todos esses itens.
O formulario de criacao comeca com uma peca; a API aceita ate 100 itens.
Mudar o estado para recebido NAO acrescenta stock automaticamente.
Apagar uma encomenda elimina tambem os seus itens (CASCADE).

7. CORRECOES DA VERSAO 1.3

- getAll() sem filtros ja nao acede a chaves inexistentes.
- Validacao no servidor de precos, quantidades, IDs, estados e textos.
- Calculos monetarios em centimos inteiros; SQL recebe strings decimais.
- Venda valida cliente e metodo de pagamento ativo dentro da transacao.
- Formularios com token CSRF e redirecionamento 303 apos gravar.
- Erros tecnicos da base nao sao apresentados ao utilizador.
- Pedidos cURL com timeout e validacao de JSON/formato/status.
- Formulario de edicao das encomendas ligado a GET por ID e PUT.
- Atualizacao Node verifica existencia sem depender de affectedRows.
- Configuracao aceita password vazia e a API carrega .env pelo caminho certo.
- Vite configurado com o plugin React e porta fixa.

As sessoes PHP agora usadas servem APENAS para CSRF e mensagens de formulario.
Nao sao o login; a autenticacao com JWT fica para uma fase posterior.
O redirecionamento evita repetir o POST ao atualizar a pagina de sucesso.
Nao e uma garantia completa de idempotencia perante falhas de rede/reenvios.

8. TESTES E LIMITACOES

Consultar docs/revisao-v1.3.md e docs/testes-api.md.
Executados nesta revisao:
- verificacoes de sintaxe PHP/Node e analise de sintaxe JSX/CSS/JSON;
- 43 verificacoes PHP e 53 testes Node com duplos/mocks, sem MySQL real;
- pagina inicial HTTP 200 e falhas controladas quando faltam pdo_mysql/cURL.

Ainda obrigatorios no Mac:
- npm install e build React;
- importacao e validacao SQL real;
- vendas/stock/rollback/concorrencia com MySQL;
- API HTTP real e integracao PHP/cURL/Node/MySQL;
- teste visual e navegacao nos dois sentidos;
- verificar .htaccess no Apache e configuracoes locais.

Nao inclui JWT, perfis de acesso, gateway de pagamentos, envio de emails,
entrada automatica em stock ou CRUD visual de clientes/pecas/fornecedores.
Nao publicar a API ou a gestao na Internet antes de autenticar/autorizar.

9. GIT E ENTREGA

O projeto deve ter um repositorio Git com historico real de commits.
Este ZIP nao cria commits nem modifica o repositorio remoto.
Guardar os package-lock.json gerados e validados. Nao guardar node_modules,
.env reais, config.local.php, dist ou dados sensiveis no Git.
O .gitignore nao remove segredos que ja tenham sido versionados.
Instrucoes e memoria descritiva: este README e docs/memoria-descritiva.md.
