# Alteracoes exatas - 1.2 para 1.3

Base: nossa versao integrada 1.2. Nenhum ficheiro removido.

## Ficheiros alterados

- `.gitignore`
- `README.txt`
- `docs/memoria-descritiva.md`
- `docs/testes-api.md`
- `frontend/README_FRONTEND.txt`
- `frontend/package.json`
- `frontend/src/pages/Contact.jsx`
- `node-api/.env.example`
- `node-api/app.js`
- `node-api/config/database.js`
- `node-api/controllers/orderController.js`
- `node-api/controllers/supplierController.js`
- `node-api/middleware/errorMiddleware.js`
- `node-api/package.json`
- `node-api/repositories/orderRepository.js`
- `node-api/repositories/supplierRepository.js`
- `node-api/server.js`
- `php-app/classes/DatabaseSource.php`
- `php-app/classes/PartRepository.php`
- `php-app/classes/SaleRepository.php`
- `php-app/config/config.php`
- `php-app/public/assets/css/style.css`
- `php-app/public/catalogo.php`
- `php-app/public/encomendas.php`
- `php-app/public/fornecedores.php`
- `php-app/public/venda.php`
- `php-app/services/SupplierApiService.php`
- `sql/procurement_database.sql`
- `sql/sales_database.sql`

## Ficheiros novos

- `.htaccess`
- `docs/resultados-testes-v1.3.txt`
- `docs/revisao-v1.3.md`
- `frontend/vite.config.js`
- `node-api/tests/controller.test.js`
- `node-api/tests/repository.test.js`
- `node-api/tests/validation.test.js`
- `node-api/utils/validation.js`
- `php-app/classes/Input.php`
- `php-app/classes/Money.php`
- `php-app/config/config.local.example.php`
- `php-app/services/formHelpers.php`
- `php-app/tools/diagnostico.php`
- `sql/verify.sql`
- `tests/php_unit.php`
- `docs/alteracoes-v1.3.md` (este indice)

## Sem alteracao

29 ficheiros preservados byte a byte, incluindo os dois menus
com Peças e AutoLux Stand, as rotas e o JSON das viaturas.

As configuracoes locais nao sao incluidas no ZIP. Preservar os .env e copiar
os valores PHP locais para config.local.php antes de substituir config.php.
