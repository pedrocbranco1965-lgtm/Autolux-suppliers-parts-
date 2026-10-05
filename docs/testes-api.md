# Guia de testes - AutoLux 1.3

Executar com dados de teste. Nao apagar encomendas ou fornecedores reais.
Usar os IDs devolvidos nos pedidos, nao assumir que 1 corresponde ao registo criado.

## Etapa 1 - Ambiente e SQL

Iniciar Apache/MySQL no MAMP. Confirmar as portas. Importar as duas bases se
for a primeira instalacao. Executar `sql/verify.sql`: 8 tabelas, todas InnoDB.
Numa instalacao nova, esperar 2 clientes, 4 pecas, 3 pagamentos, 3 fornecedores.
Nunca apagar as bases para tentar resolver um erro sem guardar copia.

No Terminal, com PHP do MAMP:

```sh
php php-app/tools/diagnostico.php
php tests/php_unit.php
```

O diagnostico nao imprime a palavra-passe. Se indicar falta de pdo_mysql/cURL,
confirmar o executavel PHP: o Terminal pode nao estar a usar o PHP do MAMP.

## Etapa 2 - Node

```sh
cd node-api
npm install
npm test
npm start
```

GET `http://localhost:3000/api`: 200, success=true.
GET `/api/suppliers`: 200 e lista de fornecedores.
GET `/api/suppliers/abc`: 400.
GET `/api/suppliers/2147483647`: 404, se nao existir.

## Etapa 3 - Fornecedor temporario

POST `/api/suppliers`, Body JSON:

```json
{"name":"Fornecedor de Teste","email":"teste-autolux@example.com","phone":"210000999"}
```

Esperado: 201. Guardar o `data.id` como FORNECEDOR_ID.
Repetir com o mesmo email: 409. Email invalido: 400.
PUT `/api/suppliers/FORNECEDOR_ID` com os mesmos campos deve devolver 200,
mesmo quando nao ha alteracoes. GET por ID deve confirmar os campos.

## Etapa 4 - Encomenda

POST `/api/orders`, substituindo supplier_id pelo ID obtido:

```json
{
  "supplier_id": 1,
  "reference": "ENC-TESTE-001",
  "status": "pendente",
  "notes": "Encomenda apenas para testes",
  "items": [
    {"part_reference":"BOS-PAD-001","part_name":"Pastilhas","quantity":2,"unit_cost":"25.50"},
    {"part_reference":"MANN-FLT-001","part_name":"Filtro","quantity":1,"unit_cost":"10.00"}
  ]
}
```

Esperado: 201, total="61.00" e dois itens. Guardar o ID da encomenda.
GET `/api/orders/ID`: detalhe com os dois itens.
PUT `/api/orders/ID`: reenviar TODO o corpo, alterando status para enviado;
esperar 200 e os mesmos dois itens. Um PUT nao e um PATCH parcial.

Pedidos negativos: quantidade "abc", 1.5, 0 ou -1; custo ausente, negativo,
"abc" ou com tres casas decimais; status "banana"; items vazio ou item null.
Esperado: 400, sem criar/alterar registos. JSON malformado: 400.
Referencia repetida: 409. Fornecedor inexistente: 400.
GET `/api/nao-existe`: 404.

DELETE do fornecedor enquanto tiver encomendas: 409.
DELETE da encomenda de teste: 200; confirmar no MySQL que os seus itens foram
apagados. Depois DELETE do fornecedor temporario: 200.

## Etapa 5 - PHP e venda

Abrir catalogo.php sem filtros: nao devem existir avisos de chaves inexistentes.
Testar marca, tipo, minimo, maximo, minimo=0 e intervalo sem resultados.
Minimo maior do que maximo deve produzir mensagem compreensivel.

Na venda, escolher uma peca, anotar stock e preco e vender 2 unidades.
Esperado: uma venda, uma linha, total exato, stock anterior menos 2.
Atualizar a pagina de sucesso: nao deve criar outra venda.
Quantidade maior do que stock: nenhuma venda/linha nova e stock inalterado.
Quantidade fracionaria ou ID adulterado: rejeitar no servidor.
Pagamento ativo=0 nao deve ser aceite para uma nova venda.

Verificar que uma falha durante a transacao nao deixa vendas/itens parciais.
Um teste real de concorrencia deve enviar duas vendas para a ultima unidade;
apenas uma deve ser concluida. Este teste ainda NAO foi executado nesta revisao.

## Etapa 6 - Integracao e interface

PHP `fornecedores.php`: lista proveniente do Node.
PHP `encomendas.php`: criar encomenda; clicar Editar; alterar estado/custo;
confirmar valores na tabela e na API; apagar somente a encomenda de teste.
Se foi criada uma encomenda de dois itens pela API, a edicao PHP deve mostrar
os dois e conserva-los ao guardar.

Parar Node e abrir fornecedores: mensagem de indisponibilidade, sem ecran branco.
Repor Node. Uma falha apos um POST pode ocorrer depois de gravar: consultar a
lista antes de repetir, para nao criar outro pedido por engano.

React: `npm install`, `npm run build`, `npm run dev` na pasta frontend.
Abrir localhost:5173, clicar **Peças**, testar o modulo PHP, regressar em
**AutoLux Stand**. Os favoritos em memoria podem ser perdidos ao sair do React.
O contacto no React informa que e uma simulacao; nao deve prometer envio real.

No Apache, pedidos a `/AutoLux/node-api/.env` e `/AutoLux/sql/sales_database.sql`
devem ser bloqueados. Confirmar a configuracao `.htaccess` do MAMP.
Nao colocar senhas reais em screenshots de configuracao.

## Etapa 7 - Fecho

Guardar o resultado real de cada teste e eventuais mensagens de erro.
Gerar/rever package-lock.json nos dois projetos npm e inclui-los no Git.
Fazer commits reais das correcoes. Login com JWT so depois desta base testada.
