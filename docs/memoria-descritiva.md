# Memória descritiva — AutoLux Peças

**Aluno:** Pedro Castel-Branco.  
**Base funcional:** AutoLux Full Stack integrado 1.3, com autenticação JWT e limite de tentativas de login.  
**Referência do código:** `91749e6`, na branch `feature/login-jwt`.  
**Estado desta revisão:** preparação da entrega para execução local; não corresponde a uma publicação em produção.

**Apoio pedagógico e técnico:** ChatGPT — apoio na explicação dos conceitos, na elaboração e revisão do código e na resolução de erros durante o desenvolvimento do projeto.

Esta memória atualiza as dez secções do documento anterior com o estado descrito no [README atualizado](../README.txt). A arquitetura original é preservada; o login, anteriormente apresentado como trabalho futuro, é agora descrito como funcionalidade implementada nesta branch. Os resultados de execução indicados são os apresentados pelo aluno durante o acompanhamento, não uma nova execução de testes realizada para produzir este documento. O [código de referência](https://github.com/pedrocbranco1965-lgtm/Autolux-suppliers-parts-/tree/91749e63b382304b29728a6a8a02b4c7a8a815c7) identifica a revisão funcional usada nesta atualização documental.

## 1. Produto e destinatários

O AutoLux reúne o site de viaturas, em React/Vite, e o módulo de gestão de peças, em PHP. O site de viaturas permite consultar o catálogo, filtrar viaturas, abrir detalhes, gerir favoritos e simular um contacto. Os favoritos ficam em memória e o contacto não envia mensagens reais.

O módulo de peças destina-se aos funcionários: permite consultar stock, selecionar clientes e pagamentos, registar vendas e gerir encomendas a fornecedores. Nesta revisão, a entrada nesse módulo exige uma conta ativa e autenticação válida.

O botão **“Peças”** liga o React ao PHP. Sem autenticação válida, o PHP encaminha o utilizador para `login.php`; depois do login, apresenta “Continuar para Peças”. O botão **“AutoLux Stand”** regressa ao site de viaturas, mas não termina a sessão. A saída é feita por **“Terminar sessão”**.

A integração entre os dois catálogos é de navegação e apresentação. Não existe sincronização de viaturas com peças. O site de viaturas permanece sem exigência de login; isso não significa que tenha sido realizado um alojamento público desta aplicação integrada.

## 2. Arquitetura e requisitos

Mantém-se a separação entre a apresentação React, as páginas dinâmicas PHP e a API Node/Express. O React não substitui a interface PHP de gestão. O PHP acede à primeira base através de PDO; o Node acede à segunda através de `mysql2`.

```text
React/Vite -- botao "Pecas" --> Paginas PHP protegidas
     ^                                |         |
     | "AutoLux Stand"                | PDO     | HTTP/JSON + JWT
     +--------------------------------+         v
                              MySQL 1        Node/Express
                              vendas e          |       |
                              autenticacao      |       +--> MySQL 2
                                   ^            |            fornecedores
                                   |            |            e encomendas
                              auth-status.php <-+
                              confirma o acesso
```

O Node consulta o estado do token através de `auth-status.php`; não consulta diretamente a base de utilizadores do PHP. Esta decisão preserva as responsabilidades das duas bases, mas cria uma dependência de disponibilidade do PHP para autorizar pedidos à API. Uma falha nessa verificação não deve permitir o acesso.

O ambiente de referência é macOS com MAMP, PHP 8.3.30, MySQL, Node.js, npm e Composer 2. A API declara Node.js `>=22.12.0`. Os utilitários interativos utilizam Bash e `proc_open`; os testes Node que chamam PHP usam um caminho específico do MAMP. A instalação noutros sistemas não foi validada. Os requisitos e os passos operacionais completos estão no README, secções 3 a 5.

## 3. Bases de dados utilizadas

O sistema utiliza **duas bases MySQL**, com estrutura prevista em InnoDB:

| Base                     | Acesso      | Tabelas                                                                                                   |
| ------------------------ | ----------- | --------------------------------------------------------------------------------------------------------- |
| `autolux_sales_db`       | PHP/PDO     | `clients`, `parts`, `payment_methods`, `sales`, `sale_items`, `users`, `auth_tokens`, `login_rate_limits` |
| `autolux_procurement_db` | Node/mysql2 | `suppliers`, `supplier_orders`, `supplier_order_items`                                                    |

A estrutura totaliza **11 tabelas**. `users` representa as contas de acesso, separadas dos clientes de vendas em `clients`. `auth_tokens` regista o utilizador, o identificador `jti`, as datas e a revogação, sem guardar o JWT completo. `login_rate_limits` guarda os contadores por email e IP, identificados por hashes; não guarda palavras-passe.

Os scripts são `sales_database.sql`, `procurement_database.sql`, `authentication.sql` e `login_rate_limits.sql`, na pasta `sql`. Os dados iniciais são exemplos; não incluem a conta nem os acessos privados do aluno.

O JSON de viaturas do React não constitui uma terceira base MySQL. As referências de peças são transportadas em JSON para as encomendas; não foram criadas chaves estrangeiras entre os dois serviços. Os scripts com `CREATE TABLE IF NOT EXISTS` não atualizam uma estrutura existente diferente. As verificações e os cuidados de backup estão no README, secções 2, 4 e 5.2.

## 4. PHP, PDO e programação orientada por objetos

`DatabaseSource` é uma classe abstrata que prepara a ligação PDO e partilha `query()`. Os repositories concretos implementam `getAll()` e operações especializadas. As páginas produzem HTML a partir dos resultados, mantendo a consulta de dados nas classes correspondentes.

`PartRepository` utiliza consultas parametrizadas e filtros. `Input` centraliza validações, e `Money` trabalha com cêntimos inteiros para os cálculos monetários. `SaleRepository` valida cliente, pagamento e stock, utiliza bloqueio `FOR UPDATE` e agrupa a venda, os itens e a redução do stock numa transação. A verificação de concorrência continua distinta do teste normal de uma venda.

Na autenticação, as responsabilidades foram separadas:

| Classe                     | Responsabilidade                                                               |
| -------------------------- | ------------------------------------------------------------------------------ |
| `UserRepository`           | Consultar e criar contas; obter internamente o hash necessário à autenticação. |
| `JwtService`               | Emitir e verificar a assinatura e os campos do JWT.                            |
| `AuthTokenRepository`      | Registar tokens, consultar o seu estado e revogá-los.                          |
| `AuthService`              | Coordenar login, verificação de acesso e logout.                               |
| `LoginRateLimitRepository` | Contabilizar tentativas e controlar os períodos de espera.                     |

O limitador utiliza transações e bloqueios dos contadores. A leitura, a decisão e a atualização são coordenadas para email e IP; os testes apresentados para este componente foram sequenciais.

Os métodos de pagamento permitem registar a escolha na venda. Não existe integração com um serviço de cobrança real.

## 5. API Node e integração

A API Express organiza rotas, controllers, repositories e middleware. As operações de fornecedores e encomendas usam GET, POST, PUT e DELETE, com validação dos dados e consultas parametrizadas.

`SupplierApiService`, no PHP, recebe o endereço da API e o JWT do utilizador autenticado. Envia o token no cabeçalho `Authorization: Bearer`, controla tempos de espera, não segue redirecionamentos e valida o estado HTTP e o formato JSON recebido. Não repete automaticamente uma gravação depois de uma falha de rede.

Os grupos `/api/suppliers` e `/api/orders`, incluindo as rotas com identificador, passam por `authMiddleware`. Este utiliza `jwtService.js` para verificar o JWT e `authStatusService.js` para pedir ao PHP a confirmação de acesso ativo. Só depois permite a operação. O endpoint `GET /api` permanece disponível como mensagem de estado, sem listas de dados.

`auth-status.php` aceita POST com o token no cabeçalho, não no cookie ou na URL. Confirma assinatura, validade, conta ativa e registo não revogado. A API responde com `401` para recusa de autenticação e `503` quando uma falha técnica impede a verificação.

A edição de uma encomenda consulta o detalhe e envia a representação completa em PUT, incluindo os itens. Eliminar uma encomenda remove os seus itens por relação CASCADE. Alterar o estado para “recebido” **não aumenta automaticamente o stock** na primeira base. O detalhe das rotas encontra-se no README, secção 6.

## 6. Segurança e limites de utilização

### 6.1. Contas e autenticação JWT

As contas são criadas através de uma ferramenta local de terminal. As palavras-passe são guardadas como hashes gerados por `password_hash()` e verificadas com `password_verify()`. A ferramenta prefere Argon2id e prevê bcrypt como alternativa, com o respetivo limite de 72 bytes. Não existe uma palavra-passe pública de instalação.

O PHP assina JWT com **RS256** e a chave privada; o Node verifica-os com a chave pública correspondente. Emissor e destinatário são, respetivamente, `autolux-php` e `autolux-pecas`. Os tokens duram **900 segundos desde a emissão**, não desde a última atividade. Não existe renovação automática.

Uma assinatura correta não basta: o registo do token tem de continuar ativo e a conta não pode estar desativada. O logout revoga o token daquele navegador, remove o cookie correspondente e limpa a sessão auxiliar. Não elimina a conta nem termina automaticamente todos os outros acessos do mesmo utilizador.

### 6.2. Páginas e formulários

`requireAuth.php` protege `index.php`, `catalogo.php`, `venda.php`, `fornecedores.php` e `encomendas.php` antes do processamento e da apresentação de HTML. A proteção não depende de esconder botões.

O cookie `autolux_access` usa `HttpOnly`, `SameSite=Lax` e o caminho do módulo PHP. A opção `Secure` é ativada em pedidos HTTPS; o ambiente acompanhado utiliza HTTP local. A sessão `autolux_forms` guarda CSRF e mensagens, separadamente do JWT.

Os formulários mantêm CSRF, validação no servidor e tratamento de texto para HTML. O logout exige POST com CSRF. O redirecionamento após submissão evita a repetição simples por atualização da página, mas não constitui uma garantia geral contra reenvios ou gravações duplicadas.

### 6.3. Limite de tentativas

O formulário permite **5 tentativas por email e 30 por IP**, em períodos de 900 segundos. As submissões que avançam para verificar credenciais contam, incluindo as que resultam em login correto. Abrir a página ou navegar já autenticado não consome essas tentativas.

Quando um limite é atingido, o formulário responde `429` com `Retry-After`, sem chamar a verificação da palavra-passe. As recusas pelo limite não prolongam o período nem reiniciam os contadores. O controlo reside no MySQL; mudar de janela não apaga o contador. Não desativa a conta nem revoga tokens já válidos.

### 6.4. Limites do ambiente

A aplicação continua destinada a execução local. A API escuta em `127.0.0.1`; o PHP e a consulta interna mantêm restrições locais. O `.htaccess` depende da configuração do Apache e ainda exige verificação final de acesso aos ficheiros internos.

A autenticação implementada não equivale a uma auditoria de segurança ou a prontidão para produção. Todos os utilizadores ativos partilham as permissões de gestão; não existem perfis diferenciados, MFA, recuperação de palavra-passe ou rotação automática de chaves. As limitações estão igualmente identificadas no README, secções 7 e 8.3.

## 7. Frontend e ferramentas

O frontend mantém React/Vite, React Router, Context API, Fetch, HTML e CSS próprio, sem Bootstrap. Lê `frontend/public/data/vehicles.json`. A porta de desenvolvimento é 5173, e `VITE_BACKOFFICE_URL` define a ligação ao PHP. As variáveis públicas do frontend não devem conter segredos.

O Composer instala `firebase/php-jwt`; o npm gere as dependências JavaScript, incluindo `jsonwebtoken`. `composer.lock` e `package-lock.json` registam as versões para reinstalação. Na API, `npm start` executa `node server.js`, e `npm run dev` utiliza `node --watch server.js`. O `nodemon` foi removido desta revisão.

As ferramentas usadas no acompanhamento incluem VS Code, MAMP/phpMyAdmin, terminal, Git/GitHub e pedidos HTTP de teste. O processo Node tem um terminal reservado, enquanto outro permite executar verificações e comandos Git.

No MAMP do aluno, com `mod_fastcgi`, foi necessário acrescentar **`-pass-header Authorization`** à diretiva `FastCgiServer` existente no `httpd.conf`. Foi feita uma cópia de segurança, verificada a configuração e reiniciado o Apache. Essa alteração local não pertence ao repositório e não deve ser copiada indiscriminadamente para outros mecanismos FastCGI. O procedimento está no README, secção 5.7.

A comunicação PHP → Node → PHP requer capacidade para o PHP responder à consulta interna enquanto a página original aguarda. Esse requisito deve ser considerado numa instalação diferente.

## 8. Verificações executadas nesta revisão

Os resultados abaixo correspondem aos testes apresentados pelo aluno no Mac durante o acompanhamento, conforme registado no README, secção 8.1.

| Área                          | Resultado confirmado nos cenários executados                                                                                                 |
| ----------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------- |
| Base funcional                | Build e navegação React, catálogo e filtros, venda com redução de stock e recusa por stock insuficiente.                                     |
| Encomendas com API protegida  | Consulta e criação, edição e eliminação de uma encomenda de teste.                                                                           |
| JWT                           | Emissão no PHP, verificação no PHP e Node e recusa de assinatura alterada.                                                                   |
| Páginas PHP                   | Login correto, recusa de credenciais incorretas, encaminhamento para login sem autenticação e recusa depois do logout.                       |
| GET das duas rotas protegidas | `401` sem token, com token não registado, alterado ou revogado; `200` com token ativo.                                                       |
| Contadores                    | Limites por email e IP, reinício após o período e limpeza dos contadores fictícios no teste sequencial.                                      |
| Formulário com limitador      | Cinco recusas por credenciais fictícias, espera na sexta tentativa e entrada posterior com a conta real quando os seus contadores permitiam. |

As ferramentas estão em `tests/`, `node-api/tests/`, `php-app/tools/` e `node-api/tools/`; o README descreve os comandos, pré-requisitos e efeitos de cada teste. Alguns testes pressupõem uma conta ativa com ID 1 e deixam um registo de token revogado; não são tarefas de arranque diário.

Os totais de testes publicados para a revisão 1.3 são históricos e não representam um total atualizado desta autenticação. Verificação de sintaxe, testes isolados e testes integrados são resultados diferentes. Não se declara cobertura exaustiva, teste de carga ou auditoria de segurança.

## 9. Trabalho futuro

**Antes de fechar a entrega**, o README identifica a necessidade de repetir build e suites no estado final, validar uma instalação separada seguindo a documentação, verificar ficheiros internos no Apache e testar rollback/concorrência de vendas e contadores. O teste sequencial do limitador não prova o comportamento com pedidos simultâneos; os testes GET não cobrem todas as variações dos outros métodos HTTP.

Mantém-se também a revisão de mensagens antigas: em `create_user.php`, o texto residual que diz que login/JWT ainda não existem precisa de ser atualizado, sem recriar a conta.

**Como evoluções posteriores**, ficam a seleção automática da peça na encomenda, a receção de encomendas com movimentos de stock, CRUD visual adicional, perfis de acesso, persistência de favoritos, contactos e pagamentos reais e alojamento seguro. Estas possibilidades não são apresentadas como funcionalidades existentes nem como novos requisitos obrigatórios da entrega.

## 10. Entrega

O **README** explica a instalação, a utilização e os testes. Esta **memória descritiva** explica o produto, a arquitetura, a organização do código e as decisões. Os modelos `node-api/.env.example` e `php-app/config/config.local.example.php` devem acompanhar a documentação com a configuração JWT, sem substituir os ficheiros locais reais.

O projeto deve incluir código, scripts SQL de estrutura e exemplos, modelos, testes, documentação e ficheiros de dependências. Não deve incluir `.env` reais, `config.local.php`, chaves privadas, tokens, dumps privados ou pastas geradas de dependências. O professor cria a sua própria conta e gera as chaves da sua instalação.

A referência funcional deste documento está na branch `feature/login-jwt`; enviar commits dessa branch não os integra automaticamente na `main`. A integração final deve ocorrer depois da revisão e das verificações acordadas. Os números `1.3.0` dos ficheiros de pacotes continuam a identificar a base, sem anular as melhorias desta branch. Não é declarada aqui uma nova versão final.

Os documentos históricos da pasta `docs` conservam o estado das revisões anteriores. Esta memória e o README atualizado descrevem a evolução com JWT e limitador, distinguindo o implementado, o testado e o ainda pendente.
