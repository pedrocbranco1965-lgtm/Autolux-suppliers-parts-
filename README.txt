AUTOLUX PECAS - FULL STACK INTEGRADO COM AUTOLUX STAND
===================================================

Aluno: Pedro Castel-Branco
Base funcional: 1.3, com autenticacao JWT e limite de tentativas de login.
Branch de desenvolvimento desta revisao: feature/login-jwt.
Referencia do codigo utilizada nesta revisao documental: 91749e6.
Estado: preparacao da entrega local; nao e uma publicacao em producao.

Apoio pedagogico e tecnico:
ChatGPT - apoio na explicacao dos conceitos, na elaboracao e revisao do
codigo e na resolucao de erros durante o desenvolvimento do projeto.

Esta documentacao descreve a evolucao da aplicacao integrada. Os numeros
1.3.0 nos package.json ainda identificam a base do projeto; nao significam
que esta branch esteja sem JWT. A revisao nao muda esses numeros.

IMPORTANTE PARA QUEM JA TEM O PROJETO A FUNCIONAR
Nao executar novamente toda a instalacao por estar a atualizar este README.
Nao substituir configuracoes reais pelos modelos. Nao recriar contas ou
chaves existentes. Nao importar backups ou scripts antigos por cima dos
dados atuais sem analisar primeiro a necessidade da operacao.

Os comandos indicados neste documento sao instrucoes para executar nos
locais assinalados. Guardar este README nao executa esses comandos.


1. O QUE CADA AREA FAZ
=====================

frontend/ : site de viaturas em React/Vite, com catalogo, filtros, detalhes,
            favoritos e contacto simulado. Mantem CSS proprio.
php-app/  : interface PHP/PDO para pecas, vendas, fornecedores, encomendas,
            login, logout e controlo de autenticacao.
node-api/ : API REST Express para fornecedores e encomendas.
sql/      : scripts de estrutura, dados iniciais e verificacao.
docs/     : memoria descritiva e registos de revisoes e testes.
tests/    : testes PHP da base funcional.

Arquitetura:

React/Vite -- botao "Pecas" --> PHP (paginas protegidas)
     ^                              |          |
     | "AutoLux Stand"              | PDO      | HTTP/JSON + JWT
     +------------------------------+          v
                       autolux_sales_db       Node/Express
                       contas e tokens        |           |
                              ^               | mysql2    |
                              |               v           |
                    auth-status.php    autolux_procurement_db
                              ^                           |
                              +------ verificacao HTTP ---+

A verificacao de estado feita pelo Node chama auth-status.php no PHP.
O Node nao consulta diretamente a base autolux_sales_db.

O site React le frontend/public/data/vehicles.json. Esse JSON nao e uma
terceira base MySQL. A integracao entre viaturas e pecas e de navegacao;
os catalogos nao sao sincronizados. O site de viaturas permanece publico.

Os metodos de pagamento registam a escolha na venda; nao efetuam cobrancas
reais. Os favoritos React ficam em memoria e o contacto e uma simulacao.


2. BASES DE DADOS UTILIZADAS
===========================

Sistema: MySQL, com tabelas InnoDB.

Base 1: autolux_sales_db, acedida pelo PHP atraves de PDO.
Tabelas da base funcional:
  clients, parts, payment_methods, sales, sale_items.
Tabelas acrescentadas para a autenticacao:
  users, auth_tokens, login_rate_limits.

Scripts desta base:
  sql/sales_database.sql
  sql/authentication.sql
  sql/login_rate_limits.sql

Base 2: autolux_procurement_db, acedida pelo Node atraves de mysql2.
Tabelas:
  suppliers, supplier_orders, supplier_order_items.
Script:
  sql/procurement_database.sql

A estrutura descrita totaliza 11 tabelas: 8 na primeira base e 3 na segunda.
Clientes das vendas nao sao automaticamente utilizadores da aplicacao.
O script de instalacao nao fornece uma conta de login com password publica.

Os scripts CREATE TABLE IF NOT EXISTS nao migram uma tabela ja existente
para uma estrutura diferente. Confirmar sempre a estrutura real antes de
repetir uma importacao. Fazer backup antes de qualquer alteracao estrutural.


3. REQUISITOS DO AMBIENTE
========================

Ambiente de referencia utilizado nos testes acompanhados:
  - macOS, VS Code e MAMP com Apache e MySQL.
  - PHP 8.3.30 no terminal e no MAMP; executavel indicado nos exemplos:
    /Applications/MAMP/bin/php/php8.3.30/bin/php
  - PHP com PDO MySQL, cURL, OpenSSL e suporte a sessoes.
  - Node.js: respeitar engines dos package.json; a API exige >=22.12.0.
  - npm e Composer 2. Na instalacao do aluno foi usado Composer 2.10.3.
  - Browser no mesmo computador em que o MAMP esta a executar.
  - Internet para instalar dependencias e carregar imagens externas.

As ferramentas interativas de criacao de conta e testes de login usam
/bin/bash, proc_open e um terminal interativo. Os scripts de teste Node que
chamam PHP contem o caminho do PHP 8.3.30 do MAMP. Ajustar esse caminho numa
instalacao diferente. Nao foi validada a instalacao em Windows ou Linux.

O codigo usa SensitiveParameter, disponivel no PHP desde 8.2. O ambiente
testado desta revisao e PHP 8.3.30; nao assumir que o antigo requisito
"PHP 8.1" cobre todas as caracteristicas agora utilizadas.

Bibliotecas JWT: firebase/php-jwt no PHP e jsonwebtoken no Node.
Usar composer.lock e package-lock.json para instalar as versoes registadas.
Nao copiar vendor/ ou node_modules/ de outro computador.

Portas da instalacao de referencia:
  Apache/MAMP: 8888
  MySQL/MAMP:  8889
  API Node:   3000, em 127.0.0.1
  React/Vite: 5173

HTTP local e as restricoes a loopback sao intencionais. Esta configuracao
nao deve ser exposta na Internet nem atraves de um tunel publico.


4. ATUALIZAR UMA INSTALACAO EXISTENTE SEM PERDER TRABALHO
======================================================

Guardar uma copia dos dados MySQL fora do repositorio. Preservar os .env,
config.local.php, a conta existente e o par de chaves da instalacao.

A autenticacao desta revisao esta na branch feature/login-jwt. Selecionar
essa branch apenas depois de verificar git status e salvaguardar alteracoes
locais. A main nao recebe as alteracoes automaticamente quando se faz push
de outra branch. Nao substituir a pasta atual pelo ZIP antigo da versao 1.3.

Se users, auth_tokens e login_rate_limits ja existem e foram verificadas,
nao recria-las nem voltar a executar os scripts de criacao de conta/chaves.
Se uma tabela falta, analisar e aplicar apenas o script correspondente.

Atualizar os dois modelos .env.example e config.local.example.php nao deve
substituir as configuracoes reais. Os modelos contem SEU_UTILIZADOR como
marcador; esse texto so deve ser adaptado nas configuracoes locais.

Os documentos historicos de docs/ sobre a versao 1.3 registam o estado
daquela revisao. Indicacoes antigas de que o JWT nao existe ou de que o
build ainda nao foi feito nao descrevem esta branch. A memoria descritiva
atualizada deve acompanhar a revisao final da entrega.


5. INSTALACAO LOCAL NUM MAC SEM CONFIGURACAO ANTERIOR
==================================================

Esta seccao destina-se a uma instalacao NOVA. No computador do aluno,
os passos ja realizados nao devem ser repetidos por rotina.
Executar um comando de cada vez e parar perante erros.

5.1 OBTER O PROJETO E ABRIR O MAMP

Pasta de referencia:
  /Applications/MAMP/htdocs/AutoLux

Se essa pasta ainda nao existir, obter o repositorio e a branch que contem
esta revisao. Exemplo no terminal, apenas para uma pasta nova:

  cd /Applications/MAMP/htdocs
  git clone --branch feature/login-jwt https://github.com/pedrocbranco1965-lgtm/Autolux-suppliers-parts-.git AutoLux

Nao executar git clone por cima da instalacao existente. Se receber um ZIP,
confirmar que e o da revisao correta e que a pasta se chama AutoLux.
Os caminhos de cookies e redirecionamentos pressupoem esse nome.

Iniciar Apache e MySQL no MAMP. Abrir o phpMyAdmin pela interface do MAMP.

5.2 IMPORTAR OS SCRIPTS SQL

Numa instalacao vazia, importar no phpMyAdmin, um ficheiro de cada vez:
  1. sql/sales_database.sql
  2. sql/procurement_database.sql
  3. sql/authentication.sql
  4. sql/login_rate_limits.sql

Cada script seleciona a base a que se destina. Importar nao e exportar:
exportacao cria um backup; importacao executa o SQL escolhido.

Executar sql/verify.sql para as verificacoes da base funcional. Confirmar
adicionalmente as tres tabelas de autenticacao e que usam InnoDB.
Consulta de estrutura, no separador SQL do phpMyAdmin:

  SELECT TABLE_SCHEMA, TABLE_NAME, ENGINE
  FROM information_schema.TABLES
  WHERE TABLE_SCHEMA IN ('autolux_sales_db', 'autolux_procurement_db')
  ORDER BY TABLE_SCHEMA, TABLE_NAME;

Os scripts iniciais incluem dados de exemplo: clientes, pecas, pagamentos
e fornecedores. Numa instalacao nova nao incluem a conta do aluno, as suas
vendas, encomendas ou os seus tokens. Criar a conta propria no passo 5.5.
Nao utilizar um dump privado do aluno para instalar a aplicacao.

5.3 INSTALAR AS BIBLIOTECAS PHP

Instalar o Composer pelo procedimento oficial, verificando o instalador:
  https://getcomposer.org/download/

Na instalacao local acompanhada, composer.phar foi guardado em:
  $HOME/ferramentas-php/composer/composer.phar

Se essa ferramenta ja estiver disponivel nesse caminho, executar:

  cd /Applications/MAMP/htdocs/AutoLux/php-app
  "/Applications/MAMP/bin/php/php8.3.30/bin/php" "$HOME/ferramentas-php/composer/composer.phar" install

Numa instalacao com Composer global e PHP CLI corretamente selecionado,
"composer install" e a alternativa. Nao executar as duas alternativas.

Usar install com o composer.lock existente, nao update. Isso permite
reinstalar as dependencias registadas e criar vendor/autoload.php.
O Composer termina depois da instalacao; nao e um servidor para deixar aberto.
Nao ignorar requisitos de plataforma nem substituir os lockfiles por tentativa.

5.4 GERAR AS CHAVES E PREPARAR A CONFIGURACAO PHP

Gerar um par de chaves apenas se a instalacao ainda nao tiver um:

  "/Applications/MAMP/bin/php/php8.3.30/bin/php" "/Applications/MAMP/htdocs/AutoLux/php-app/tools/generate_jwt_keys.php"

O programa guarda as chaves fora do MAMP e do repositorio:
  $HOME/autolux-secrets/jwt/jwt-private.pem
  $HOME/autolux-secrets/jwt/jwt-public.pem

Se os ficheiros ja existirem, o programa recusa substitui-los. Nao os apagar
para contornar essa recusa. Nao gerar chaves novas a cada login.

Na pasta principal do projeto, criar a configuracao local somente se faltar:

  cd /Applications/MAMP/htdocs/AutoLux
  test -f php-app/config/config.local.php || cp php-app/config/config.local.example.php php-app/config/config.local.php

Editar SOMENTE config.local.php para ajustar:
  - db: host, porta, utilizador e password locais; name=autolux_sales_db.
  - supplier_api_url: http://127.0.0.1:3000/api.
  - frontend_url: http://localhost:5173/.
  - jwt.private_key_path e jwt.public_key_path: caminhos absolutos reais.
  - jwt.algorithm: RS256.
  - jwt.issuer: autolux-php.
  - jwt.audience: autolux-pecas.
  - jwt.ttl_seconds: 900, coerente com o verificador do Node.

Substituir SEU_UTILIZADOR pelos caminhos apresentados pelo gerador.
Guardar caminhos, nao o conteudo das chaves. root/root sao apenas exemplos
locais do MAMP; confirmar os valores da instalacao.

O PHP le config.php e a sobreposicao config.local.php. Nao le automaticamente
o .env do Node. Confirmar que o PHP do Apache tambem consegue ler as chaves.
Nao resolver falhas de acesso aplicando permissoes 777 ou expondo a chave.

5.5 CRIAR A PRIMEIRA CONTA LOCAL

Com MySQL iniciado e config.local.php correto, executar uma vez:

  "/Applications/MAMP/bin/php/php8.3.30/bin/php" "/Applications/MAMP/htdocs/AutoLux/php-app/tools/create_user.php"

Responder no terminal ao nome, email, password e confirmacao. Escolher
uma password exclusiva, de 15 a 128 caracteres; em fallback bcrypt tambem
se aplica o limite de 72 bytes, comunicado pelo script.
A password nao aparece enquanto e introduzida, nem como asteriscos.
Nao escrever a password na linha de comandos antes de o programa a pedir.

Apenas o hash da password fica em users. Nao existe login publico padrao.
Se a conta for criada, nao repetir o programa para fazer login: utilizar
a pagina login.php. Se o email ja existir, nao criar uma conta duplicada.

Nota sobre a revisao 91749e6: create_user.php ainda imprime no fim uma
mensagem antiga de que login/JWT nao estao implementados. E texto residual
da fase de construcao; esta branch ja contem o login. Corrigir essa mensagem
na revisao final nao exige recriar a conta nem alterar o seu hash.

5.6 PREPARAR A API NODE

Na pasta da API:

  cd /Applications/MAMP/htdocs/AutoLux/node-api
  test -f .env || cp .env.example .env

Editar .env, mantendo os valores locais e ajustando:
  HOST=127.0.0.1
  PORT=3000
  DB_NAME=autolux_procurement_db
  DB_HOST, DB_PORT, DB_USER e DB_PASSWORD da instalacao MySQL.
  JWT_PUBLIC_KEY_PATH: caminho absoluto real de jwt-public.pem.
  JWT_ISSUER=autolux-php
  JWT_AUDIENCE=autolux-pecas
  AUTH_STATUS_URL=http://127.0.0.1:8888/AutoLux/php-app/public/auth-status.php

O Node recebe apenas a chave PUBLICA; nao necessita da privada.
Usar 127.0.0.1 em AUTH_STATUS_URL: o cliente interno desta revisao valida
esse destino local. Reiniciar a API depois de alterar o .env.

Instalar as dependencias registadas:

  npm ci

npm ci utiliza package-lock.json e recria node_modules se ja existir.
Usa-se aqui para uma instalacao reproduzivel; nao e para executar em cada
arranque. Se houver conflito entre package.json e o lockfile, investigar,
sem apagar o lockfile nem acrescentar --force.

5.7 GARANTIR QUE AUTHORIZATION CHEGA AO PHP NO MAMP

O Node envia o JWT ao PHP no cabecalho Authorization. No MAMP do aluno,
com mod_fastcgi e PHP em cgi-fcgi, esse cabecalho nao chegava ao PHP.
A correcao testada foi acrescentar -pass-header Authorization a linha
FastCgiServer existente em:
  /Applications/MAMP/conf/apache/httpd.conf

Esta correcao aplica-se a essa configuracao de mod_fastcgi; nao copiar a
linha para servidores com mod_proxy_fcgi ou outro mecanismo sem verificar.
A diretiva nao pertence ao .htaccess. Nao alterar nada se o cabecalho ja
for transmitido corretamente ou se a configuracao do servidor for diferente.

Na configuracao correspondente, primeiro guardar backup fora do projeto:

  cp -n "/Applications/MAMP/conf/apache/httpd.conf" "$HOME/Desktop/httpd.conf.antes-autolux-jwt"

No editor, alterar apenas a linha existente, sem duplicar o servidor:

  FastCgiServer /Applications/MAMP/fcgi-bin/php.fcgi -socket httpdFastCGI.sock -pass-header Authorization

Verificar antes de reiniciar:

  "/Applications/MAMP/Library/bin/httpd" -t -f "/Applications/MAMP/conf/apache/httpd.conf"

So depois de obter Syntax OK, parar e iniciar os servidores pela interface
MAMP. Se falhar, nao usar sudo ou alterar permissoes por tentativa; analisar
a mensagem e recuperar a copia adequada se necessario.

O httpd.conf e local, fora do Git. Nao publicar esse ficheiro completo.
Os diagnosticos temporarios de cabecalhos devem ser removidos. Um POST sem
JWT a auth-status.php deve devolver 401, mas isso sozinho nao comprova a
passagem do cabecalho. O teste tools/test_auth_status.js verifica tambem
um token ativo e depois revogado.

A integracao e PHP -> Node -> PHP: o servidor PHP precisa de conseguir
processar a consulta interna enquanto outra pagina aguarda a API. Nao
substituir o Apache por um servidor PHP de um unico processo sem avaliar
esta dependencia; falta de capacidade pode causar timeout.

5.8 PREPARAR O FRONTEND

Num terminal de trabalho:

  cd /Applications/MAMP/htdocs/AutoLux/frontend
  test -f .env || cp .env.example .env

Confirmar no .env local:
  VITE_BACKOFFICE_URL=http://localhost:8888/AutoLux/php-app/public/

Instalar as dependencias e construir o frontend:

  npm ci
  npm run build

O build gera dist/; nao publica sozinho a aplicacao na Internet.
Nao colocar segredos em variaveis VITE_.

5.9 ARRANQUE E UTILIZACAO DIARIA

MAMP: Apache e MySQL ligados.

Terminal 1, reservado para a API:

  cd /Applications/MAMP/htdocs/AutoLux/node-api
  npm start

Deixar este terminal a correr. Para desenvolver com reinicio automatico,
"npm run dev" utiliza node --watch server.js. Escolher UM modo de arranque,
nao iniciar os dois na mesma porta. nodemon ja nao e necessario.

Terminal 2, reservado para o site React:

  cd /Applications/MAMP/htdocs/AutoLux/frontend
  npm run dev

Terminal 3, livre para comandos Git, verificacoes e testes.

No browser do mesmo Mac:
  Site React: http://localhost:5173/
  Login: http://localhost:8888/AutoLux/php-app/public/login.php
  Painel: http://localhost:8888/AutoLux/php-app/public/index.php

O botao "Pecas" leva ao PHP. Sem autenticacao valida, o PHP encaminha
para login.php. Depois de entrar, usar "Continuar para Pecas".
O botao "AutoLux Stand" regressa ao React; nao faz logout.
O botao "Terminar sessao" revoga o token e limpa os cookies correspondentes.

Com npm start, guardar codigo nao atualiza automaticamente o processo Node.
Parar apenas a instancia correta com Control+C e arrancar novamente quando
necessario. Se a porta 3000 estiver ocupada, identificar o processo antes de
parar qualquer programa; nao iniciar outra instancia ou mudar portas ao acaso.


6. FLUXOS E ROTAS
================

PHP publico para o utilizador local:
  login.php: GET mostra o formulario; POST processa a tentativa com CSRF.
  logout.php: aceita POST com CSRF; abrir por GET nao termina sessao.

Paginas PHP protegidas por requireAuth.php:
  index.php, catalogo.php, venda.php, fornecedores.php, encomendas.php.

Endpoint interno PHP:
  POST auth-status.php com Authorization: Bearer <JWT>.
  Responde JSON: 200 quando confirma acesso; 401 quando recusa;
  405 para metodo nao permitido; 503 quando nao consegue verificar.
  Nao utilizar o cookie do navegador nem o JWT na URL para esta consulta.

API Node:
  GET /api: mensagem de estado, sem dados de fornecedores ou encomendas.
  /api/suppliers e /api/orders, incluindo as respetivas rotas com :id:
    autenticacao JWT e estado ativo obrigatorios.
  GET lista e consulta, POST cria, PUT atualiza, DELETE elimina.
  401 recusa autenticacao; 503 comunica uma falha tecnica de verificacao.
  A autenticacao e aplicada antes dos routers de fornecedores/encomendas.

O PHP envia automaticamente o JWT com SupplierApiService. Nao copiar
tokens manualmente para ficheiros, argumentos de comandos ou repositorio.

Encomendas: PUT envia a representacao completa, incluindo os itens.
Mudar o estado para recebido NAO acrescenta stock automaticamente.
Apagar uma encomenda elimina os seus itens por chave estrangeira CASCADE.
Uma falha de rede depois de gravar pode exigir consultar antes de reenviar;
nao ha uma garantia geral de idempotencia para todos os pedidos repetidos.


7. CORRECOES E AUTENTICACAO DESTA REVISAO
======================================

Mantem a base 1.3: filtros, validacao no servidor, consultas parametrizadas,
calculos monetarios em centimos, transacoes nas vendas e encomendas,
CSRF nos formularios, redirecionamento apos gravacao e tratamento de erros.

Acrescenta:
  - Contas em users, com hashes criados por password_hash().
  - Login verificado com password_verify() e conta ativa.
  - JWT RS256 assinado no PHP e verificado no PHP e no Node.
  - Emissor autolux-php, destinatario autolux-pecas e duracao de 900 segundos.
  - Registo de jti e datas em auth_tokens; nao guarda o JWT completo.
  - Revogacao no logout e confirmacao do estado pelo endpoint PHP.
  - Cookie autolux_access, HttpOnly e SameSite=Lax, restrito ao caminho PHP.
  - Secure quando o pedido e HTTPS; os testes acompanhados foram em HTTP local.
  - Sessao PHP auxiliar para CSRF e mensagens, separada da autenticacao JWT.
  - Limite de tentativas por email e IP em login_rate_limits.

O token expira 15 minutos apos a emissao, nao apos 15 minutos sem atividade.
Nao existe renovacao automatica. Navegar nao prolonga a validade do token.
Uma assinatura correta nao dispensa verificar revogacao e conta ativa.

Limitador do formulario: 5 tentativas por email e 30 por IP em janelas de
900 segundos. As tentativas que avancam para verificar credenciais contam,
mesmo quando o login e bem-sucedido. GET, navegacao autenticada e pedidos
recusados pelo limite nao reiniciam o periodo. Uma recusa devolve 429 com
Retry-After. Nao apaga a conta nem revoga tokens ja emitidos.

Os hashes dos identificadores dos contadores nao sao hashes de passwords.
Nao tornam um dump da base adequado para publicacao. O limite esta ligado
ao formulario web; as ferramentas administrativas CLI nao constituem uma
API publica nem devem ser disponibilizadas como tal.


8. TESTES E LIMITACOES
=====================

8.1 O QUE FOI CONFIRMADO DURANTE O ACOMPANHAMENTO

Segundo os resultados do Mac apresentados pelo aluno, passaram os percursos
acompanhados de build/navegacao React, catalogo e filtros, venda e reducao de
stock, recusa de stock insuficiente, consulta de fornecedores e CRUD de
uma encomenda de teste. Depois de proteger a API, foram repetidas a consulta
e as operacoes de criar, editar e apagar uma encomenda separada.

Foram apresentados resultados de sucesso nos testes de assinatura PHP/Node,
consulta de estado ativo/revogado, GET protegido de fornecedores/encomendas,
login/logout, acesso direto as paginas sem login e limitador de tentativas.
No formulario, as cinco primeiras tentativas ficticias falharam por
credenciais incorretas e a sexta apresentou a espera; o login real continuou
possivel quando os seus contadores permitiam.

Estes sao resultados dos cenarios executados, nao uma auditoria de seguranca
nem uma afirmacao de cobertura exaustiva. Registar os totais e resultados
da nova execucao quando for validada a revisao final.
A instalacao limpa seguindo apenas este README ainda precisa de ser
repetida antes de declarar a revisao final pronta para entrega.

8.2 COMANDOS DISPONIVEIS PARA REPETIR OS TESTES

A partir da raiz AutoLux, teste PHP da base funcional:

  "/Applications/MAMP/bin/php/php8.3.30/bin/php" tests/php_unit.php

Na pasta node-api, testes existentes da API:

  npm test

As suites antigas nao substituem os testes integrados com JWT. Reexecutar
todas depois das ultimas alteracoes e analisar falhas, sem mudar expectativas
apenas para obter sucesso.

Ferramentas PHP, executar no terminal com o PHP do MAMP:
  php-app/tools/test_jwt.php
    Assinatura e rejeicao de alteracao; nao grava tokens na base.
  php-app/tools/test_auth_tokens.php
    Regista e revoga um token para a conta ID 1.
  php-app/tools/test_auth_service.php
    Pede credenciais locais e testa login, authenticate e logout.
  php-app/tools/test_login_rate_limits.php
    Contadores ficticios, teste sequencial e limpeza apenas desses contadores.

Exemplo de execucao, a partir da raiz:
  "/Applications/MAMP/bin/php/php8.3.30/bin/php" php-app/tools/test_login_rate_limits.php

Ferramentas Node, executar na pasta node-api:
  node tools/test_php_jwt.js
    PHP emite, Node verifica; nao grava registos de tokens.
  node tools/test_auth_status.js
    Requer Apache/MySQL; regista e revoga um token de teste.
  node tools/test_api_auth.js
    Requer tambem a API em 3000; testa GET de suppliers e orders sem token,
    token nao registado, ativo, assinatura alterada e token revogado.

Os testes que usam ID 1 pressupoem uma conta ativa com esse ID na base
local de teste. Numa instalacao com outro ID, adaptar conscientemente o
dado de teste; nao recriar ou apagar utilizadores reais para obter esse ID.
Cada execucao dos testes de registo pode deixar uma nova linha revogada em
auth_tokens. Sao testes de desenvolvimento, nao tarefas de arranque diario.
Nao os executar sobre dados reais de producao.

Verificacoes de sintaxe:
  PHP: usar -l antes do caminho do ficheiro PHP.
  Node: usar node --check antes do caminho do ficheiro JavaScript.
Uma verificacao de sintaxe nao executa o teste e nao prova o funcionamento.

Consultas sem token, no terminal, com a API iniciada:
  curl -i --max-time 10 "http://127.0.0.1:3000/api/suppliers"
  curl -i --max-time 10 "http://127.0.0.1:3000/api/orders"
Esperado: 401 e success=false, sem listas de dados.

Se um teste apresentar FALHOU ou ATENCAO, registar etapa e mensagem, sem
tokens, e verificar antes de o repetir. Nao retirar o middleware para
transformar uma recusa esperada em resposta 200.

8.3 LIMITACOES E VERIFICACOES FINAIS PENDENTES

A aplicacao permanece limitada ao ambiente local. A autenticacao nao e,
por si so, uma declaracao de prontidao para producao. Faltam uma avaliacao
de alojamento/HTTPS, isolamento de servicos e revisao de seguranca completa.
Nao remover as restricoes locais apenas para mostrar a aplicacao remotamente.

Todos os utilizadores ativos deste modelo partilham as permissoes de gestao;
nao existem perfis diferenciados, MFA, recuperacao de password, registo
publico de contas, renovacao automatica ou rotacao automatica de chaves.

Nao implementado: rececao de encomendas com entrada automatica em stock,
CRUD visual completo de clientes/pecas/fornecedores, selecao automatica da
peca na encomenda, pagamentos reais ou envio real do contacto React.

Antes da entrega, repetir o build e as suites no estado final, testar a
instalacao noutro ambiente separado, validar rollback/concorrencia de vendas
e dos contadores e verificar que ficheiros internos nao sao servidos pelo
Apache. O teste sequencial dos contadores nao prova o comportamento sob
pedidos simultaneos. Os testes GET de autenticacao nao cobrem todas as
variacoes de todos os metodos HTTP.

A .htaccess inclui restricoes a ficheiros/pastas e depende das permissoes
AllowOverride do Apache. Nao presumir que basta existir: verificar os .env,
SQL, configuracoes, dependencias e ferramentas internas. Em producao, servir
apenas o diretorio publico adequado; nao expor a raiz completa do projeto.
As chaves privadas devem continuar fora do document root e do repositorio.


9. GIT, DOCUMENTACAO E ENTREGA
============================

Trabalhar na revisao correta, rever git status e o conteudo selecionado
antes de cada commit. Enviar feature/login-jwt nao integra automaticamente
na main. Efetuar essa integracao apenas depois da revisao final.

Guardar no Git: codigo, scripts SQL com estrutura/dados de exemplo, README,
modelos de configuracao, testes, composer.json/composer.lock e os dois
package.json/package-lock.json. Nao guardar .env reais, config.local.php,
chaves privadas, dumps com contas, tokens reais, node_modules, vendor ou dist.

O commit guarda ficheiros selecionados; nao exporta os dados MySQL. O push
envia commits; nao inicia PHP, Node ou MySQL num alojamento publico.
O professor deve criar a sua conta e gerar as chaves da sua instalacao.

A memoria descritiva deve explicar a arquitetura e as decisoes. Este README
explica a instalacao e a utilizacao. Ambos devem referir as mesmas bases,
funcionalidades, limites e estado de testes, preservando os registos
historicos de revisoes anteriores sem os apresentar como estado atual.

Preparacao final ainda a concluir: atualizar a memoria descritiva, corrigir
mensagens residuais de desenvolvimento, repetir as verificacoes finais e
guardar/integrar a revisao acordada para entrega. Funcionalidades extra
ficam para uma etapa posterior, sem alterar a base antes de a entregar.

Referencias tecnicas de apoio a esta revisao documental:
  Codigo da branch: https://github.com/pedrocbranco1965-lgtm/Autolux-suppliers-parts-/tree/feature/login-jwt
  Composer: https://getcomposer.org/doc/01-basic-usage.md
  Instalador Composer: https://getcomposer.org/download/
  npm ci: https://docs.npmjs.com/cli/v11/commands/npm-ci/
  Apache - verificacao: https://httpd.apache.org/docs/2.4/programs/httpd.html
  mod_fastcgi - pass-header: https://github.com/FastCGI-Archives/mod_fastcgi/blob/master/CONFIG.md
  PHP SensitiveParameter: https://www.php.net/manual/en/class.sensitiveparameter.php

FIM DO README
