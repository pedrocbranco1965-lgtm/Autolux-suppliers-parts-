# Memoria descritiva - AutoLux Pecas

**Aluno:** Pedro Castel-Branco.  
**Versao de trabalho:** 1.3 - revisao tecnica para testes locais.  
**Base:** nossa versao integrada 1.2 (nao a versao Lovable).  
**Estado:** codigo revisto, com testes unitarios; validacao integrada no Mac pendente.

## 1. Produto e destinatarios

O AutoLux reune o site de viaturas e o modulo de pecas. O site publico original,
em React/Vite, permite consultar viaturas, filtrar, consultar detalhes e gerir
favoritos. O contacto e simulado, sem envio ou persistencia. Os favoritos estao
em memoria e nao sobrevivem a uma nova inicializacao da aplicacao.

O modulo de pecas destina-se aos funcionarios: consulta de stock, selecao de
clientes, registo de vendas e encomendas a fornecedores. Esta versao ainda nao
verifica a identidade dos funcionarios; o login com JWT sera uma etapa futura.

A navegacao usa **Pecas** (rotulo visivel: **Peças**) no React e **AutoLux Stand**
no PHP. Os catalogos de viaturas e de pecas sao distintos. Esta integracao e de
navegacao e apresentacao, nao uma migracao das viaturas para a base de pecas.

## 2. Arquitetura e requisitos

```text
React/Vite (viaturas) -- Peças --> Paginas PHP (pecas)
        ^                             |       |
        | AutoLux Stand               | PDO   | cURL / HTTP / JSON
        +-----------------------------+       v
                                    MySQL 1  Node.js / Express
                                                 |
                                                 v
                                               MySQL 2
```

O enunciado utiliza paginas dinamicas PHP para a interface de gestao e backend
de vendas. Mantivemos essas paginas: React nao substitui a camada PHP exigida.
A API Node trata exclusivamente da segunda base, de fornecedores/encomendas.
Os quatro metodos HTTP e o formulario de edicao sao melhorias do projeto; nao
sao tratados como exigencias adicionais escritas no enunciado.

## 3. Bases de dados utilizadas

**Sistema:** MySQL, tabelas InnoDB.

**autolux_sales_db**, acedida por PHP/PDO:
`clients`, `parts`, `payment_methods`, `sales`, `sale_items`.
Guarda clientes, pecas/stock, pagamentos selecionaveis, vendas e linhas.
Script: `sql/sales_database.sql`.

**autolux_procurement_db**, acedida por Node/mysql2:
`suppliers`, `supplier_orders`, `supplier_order_items`.
Guarda fornecedores, encomendas e linhas. Script: `sql/procurement_database.sql`.

Sao duas bases, nao tres. O JSON das viaturas nao constitui outra base MySQL.
O PHP nao consulta a segunda base diretamente. Referencias de pecas sao
transportadas em JSON; nao criamos chaves estrangeiras entre os dois servicos.

Os scripts nao contem DROP. `IF NOT EXISTS` nao migra tabelas ja existentes.
O motor deve ser confirmado com `sql/verify.sql` antes de testar transacoes.

## 4. PHP, PDO e programacao orientada por objetos

`DatabaseSource` e uma classe abstrata que prepara a ligacao PDO e partilha o
metodo `query()`. Os repositories concretos implementam `getAll()` e operacoes
especializadas. As paginas produzem HTML a partir dos arrays recebidos.

`PartRepository` usa consultas parametrizadas e filtros opcionais. `Input`
centraliza verificacoes de texto e inteiros. `Money` calcula em centimos e gera
strings adequadas a DECIMAL(10,2), sem usar float para calcular totais.

`SaleRepository` verifica o cliente e o pagamento ativo, bloqueia a peca com
`FOR UPDATE`, valida stock, insere venda/linha e reduz stock. Tudo acontece na
mesma ligacao/transacao. O commit confirma; o rollback e tentado perante erro.
A verificacao real de bloqueios e rollback em MySQL ainda faz parte dos testes.

Os metodos de pagamento ativos alimentam o dropdown. Adicionar uma linha na
tabela permite nova opcao de registo; integrar um gateway de pagamento real
exigiria codigo adicional e nao faz parte desta versao.

## 5. API Node e integracao

Express organiza rotas, controllers, repositories e middleware de erro. A
validacao rejeita IDs/quantidades invalidos, custos ausentes ou nao numericos,
estados fora da lista e totais fora do limite. Os dados sao normalizados antes
da query. Os repositories utilizam placeholders e transacoes para encomendas.

`SupplierApiService` no PHP envia/recebe JSON, limita o tempo de espera e
verifica codigo HTTP, JSON e formato da resposta. Nao repete automaticamente
operacoes de gravacao apos uma falha de rede.

A pagina de encomendas permite criar, listar, editar e apagar. Ao editar,
obtem primeiro o detalhe por ID e envia todos os itens no PUT. Uma encomenda
recebida nao aumenta automaticamente o stock na primeira base; essa regra
nao foi implementada nem simulada como se estivesse feita.

## 6. Seguranca e limites de utilizacao

Formularios PHP usam token CSRF, escape HTML, validacao do lado servidor e
POST/Redirect/GET depois de sucesso. Mensagens tecnicas sao registadas no log.
As sessoes usadas para CSRF/mensagens nao representam autenticacao.

A API escuta localmente em 127.0.0.1 por defeito. O .htaccess tenta bloquear
ficheiros internos no Apache e tem de ser testado no MAMP. Ainda nao existem
JWT, autorizacao por perfil ou revogacao de sessoes/tokens. Portanto nao se
considera esta versao pronta para exposicao publica ou dados reais sensiveis.

POST/Redirect/GET protege contra a repeticao simples por refresh, mas nao e
idempotencia distribuida nem protecao total contra duas submissoes separadas.

## 7. Frontend e ferramentas

React/Vite, React Router, Context API, Fetch, HTML e CSS proprio, sem Bootstrap.
O plugin React foi explicitamente configurado no Vite. A porta 5173 e fixa para
manter o link de regresso; enderecos publicos VITE_ nao devem conter segredos.
As dependencias diretas foram fixadas a partir do lockfile do frontend original.
A instalacao, a geracao de novos lockfiles e o build ainda serao feitos no Mac.

Ferramentas: VS Code, MAMP/phpMyAdmin, npm, Git/GitHub, Postman/Insomnia.

## 8. Verificacoes executadas nesta revisao

43 verificacoes PHP e 53 testes Node passaram, usando duplos PDO/MySQL quando
necessario. Tambem passaram verificacoes de sintaxe e renderizacao basica de
paginas sem servicos. Os numeros/ambiente constam de `revisao-v1.3.md`.

Esses testes nao substituem um MySQL real, transacoes concorrentes, a API HTTP
real ou um build de producao React. Estes pontos estao pendentes e o projeto
so deve ser considerado pronto para entrega depois de os confirmar no Mac.

## 9. Trabalho futuro

Login JWT e autorizacao, CRUD visual adicional, melhor tratamento de reenvios,
rececao de encomendas com movimentos de stock, persistencia de favoritos,
envio real de contactos e deploy seguro. Nao sao funcionalidades presentes
apenas porque estao descritas como ideias.

## 10. Entrega

README com aluno, instrucao de execucao, bases utilizadas e funcionalidades.
Repositorio Git com commits reais. Manter configuracoes secretas fora do Git.
A revisao nao publica alteracoes no repositorio GitHub automaticamente.
