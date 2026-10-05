# AutoLux Pecas - revisao da versao 1.2 e correcoes 1.3

## Identidade e alcance

Origem: `AutoLux_FullStack_Integrado_v1.2.zip`, a nossa versao integrada.
Nao foi usada a versao Lovable como base das alteracoes. Nenhum ficheiro no
computador do Pedro foi lido diretamente ou modificado; o GitHub so foi lido.
O ZIP 1.3 e uma copia nova, nao uma substituicao automatica da pasta local.

Conferi o texto de `Enunciado_Projeto_AutoLux_pecas.pages` por extracao dos
blocos IWA e a sua pre-visualizacao; consultei tambem o PDF original e o
seu diagrama/criterios. A leitura normal de Pages na ferramenta de ficheiros
falhou; a extracao do arquivo local permitiu ler o texto integral.
A indicacao corrigida sobre as duas bases foi aplicada conforme o
esclarecimento do Pedro. Nao foi inventada uma terceira base.

## Requisitos e implementacao encontrada

| Requisito | Implementacao | Verificacao |
|---|---|---|
| Paginas dinamicas PHP | php-app/public e partials | Presente; leitura do codigo |
| Catalogo da base + marca/tipo/preco | PartRepository + catalogo.php | Presente; filtros corrigidos e testados sem MySQL |
| Clientes, peca e pagamento na venda | venda.php + repositories | Presente; validacao e transacao reforcadas |
| Pagamentos extensivos | payment_methods + dropdown | Adicionar opcao de registo; nao gateway real |
| Fornecedores por Node | SupplierApiService + supplierRoutes | Presente; integracao real ainda pendente |
| Encomendas por Node | encomendas.php + orderRoutes | Presente; edicao visual agora incluida |
| Duas bases MySQL | scripts sales/procurement | Nomes e relacoes conferidos; execucao SQL real pendente |
| README e memoria descritiva | documentos neste pacote | Atualizados para a revisao 1.3 |
| Git com historico e projeto executavel | trabalho local/repositorio | Historico e execucao completa ainda por confirmar |

GET/POST/PUT/DELETE e a navegacao React foram pedidos adicionais do Pedro.
Nao se deve confundir presenca no codigo com aprovacao de teste funcional.

## Problemas reproduzidos na versao 1.2

1. `PartRepository.php`: chamar getAll() sem argumentos produziu quatro
   avisos Undefined array key (min_price e max_price). Foi reproduzido com
   um PDOStatement de teste, sem uma base real.
2. `orderController.js`: validateOrder() devolveu null (aceitacao) para
   quantidade "abc", quantidade 1.5, custo ausente e estado "banana".
   Foi reproduzido com a funcao original num contexto de teste Node.
3. A interface das encomendas nao tinha um formulario para a atualizacao,
   apesar de a API e o service exporem PUT.
4. O fluxo POST das vendas/encomendas nao redirecionava depois do sucesso
   nem incluia token CSRF.
5. Conversoes diretas com int/float nao constituiam validacao dos inputs;
   o pagamento apenas era filtrado no dropdown, sem verificar active na venda.
6. O service cURL nao tinha timeout nem rejeitava claramente JSON invalido.
7. Calculos monetarios usavam float/Number diretamente para totais.
8. Configuracao com ?: ou || substituia uma password vazia pelo valor root.
9. O frontend usava latest, nao incluia vite.config e nao tinha lockfile.
10. A mensagem do contacto React afirmava sucesso real apesar de ser simulacao.

## Alteracoes aplicadas na copia 1.3

- Chaves de filtros opcionais tratadas com valores por defeito.
- Input PHP e validation.js para validar no servidor antes das queries.
- Money PHP/centimos JS para totais decimais exatos dentro dos limites SQL.
- Verificacao transacional de cliente, pagamento ativo, peca e stock.
- POST/Redirect/GET, mensagens de feedback e tokens CSRF nos formularios.
- Erros tecnicos da base deixados nos logs, nao na pagina.
- Timeouts e verificacoes de resposta no cURL; GET por ID no service.
- Formulario Editar com todos os itens; PUT nao descarta linhas por mostrar so uma.
- Tratamento JSON invalido, conflitos, referencias inexistentes e IDs invalidos.
- Atualizacao Node distingue registo inexistente de valores inalterados.
- Configuracao local PHP ignorada pelo Git; password vazia preservada.
- .env da API lido a partir do diretorio correto; servidor API local por defeito.
- Vite com plugin React e porta 5173 fixa; dependencias diretas fixadas a partir
  do package-lock.json do repositorio original autolux-final consultado.
- .htaccess para bloquear pastas internas na configuracao Apache documentada.
- Scripts SQL especificam InnoDB ao criar tabelas novas. Nenhum DROP/ALTER.
- README, memoria e plano de testes atualizados. Nenhum login JWT acrescentado.

## Testes efetivamente executados

Ambiente de verificacao: PHP CLI 8.4.23; Node 22.16.0.

| Verificacao | Resultado |
|---|---|
| PHP -l | 21 ficheiros, sem erro de sintaxe |
| node --check | 14 ficheiros JS da API/testes, sem erro de sintaxe |
| Parser TypeScript para JS/JSX do frontend | 18 ficheiros, sem erro de sintaxe; imports relativos existem |
| Parser PostCSS | 2 CSS analisados sem erro |
| JSON.parse | 3 JSON validos |
| Testes PHP | 43 verificacoes passaram, com duplos PDO quando necessario |
| Testes Node nativos | 53 passaram, com duplos de repository/ligacao quando necessario |
| HTTP PHP inicial | index.php devolveu 200 e o menu AutoLux Stand |
| HTTP sem extensoes locais | catalogo/venda/fornecedores/encomendas devolveram 503 com mensagem controlada |

Os 503 sao testes de falha controlada, nao testes funcionais aprovados das
paginas com a base de dados. O parser JSX nao e npm run build.

O ambiente nao tem MySQL nem os drivers/extensoes PHP pdo_mysql e cURL.
O acesso de rede ao registry npm falhou por resolucao DNS; uma tentativa
npm install --offline devolveu ENOTCACHED. Nao foi criado um lockfile ficticio.
Nao foi executado um npm audit nem uma instalacao completa de dependencias.

## Ainda por testar ou implementar

**No Mac:** instalacao npm, build React, SQL real, integracao HTTP/cURL, vendas
com stock, rollback e concorrencia reais, edicao completa de encomendas,
ligacoes dos menus e bloqueio .htaccess no Apache. Seguir testes-api.md.

**Antes de publicacao:** autenticar e autorizar PHP e API (JWT planeado),
HTTPS e gestao de segredos, testes de seguranca e dependencias. Esta revisao
continua a ser para uso local de aprendizagem.

**Nao implementado:** processamento de pagamentos reais, emails reais de
contacto, favoritos persistentes, entrada automatica de stock ao receber
compras, CRUD visual completo de clientes/pecas/fornecedores. O CRUD desses
ultimos fornecedores existe na API, nao em formularios PHP completos.

POST/Redirect/GET reduz repeticao por refresh; nao garante idempotencia sob
falhas de rede ou submissoes independentes. O sistema nao gera faturas legais.

## Proximo passo recomendado

Guardar a versao 1.2; abrir a copia 1.3, preservar os .env e configuracoes do
Mac. Iniciar MAMP e confirmar portas/bases com sql/verify.sql. Nao apagar
nem recriar uma base com trabalho real. So depois arrancar Node e React e
registar os resultados dos testes. Nao modificar o repositorio remoto sem
rever primeiro as alteracoes locais.
