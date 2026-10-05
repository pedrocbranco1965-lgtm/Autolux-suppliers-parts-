<?php
// Executar: php tests/php_unit.php. Nao liga ao MySQL; utiliza duplos PDO.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../php-app/classes/PartRepository.php';
require_once __DIR__ . '/../php-app/classes/SaleRepository.php';
require_once __DIR__ . '/../php-app/services/formHelpers.php';
set_error_handler(static function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$count = 0;
function check(bool $condition, string $message): void {
    global $count;
    if (!$condition) throw new RuntimeException('FALHOU: ' . $message);
    $count++; echo "OK $count - $message\n";
}
function rejects(callable $fn, string $message): void {
    try { $fn(); } catch (InvalidArgumentException $error) { check(true,$message); return; }
    throw new RuntimeException('FALHOU: ' . $message);
}
class TestStatement extends PDOStatement {
    public function __construct(private mixed $one = false, private array $all = []) {}
    public function fetch(int $mode = PDO::FETCH_DEFAULT, int $orientation = PDO::FETCH_ORI_NEXT, int $offset = 0): mixed { return $this->one; }
    public function fetchAll(int $mode = PDO::FETCH_DEFAULT, mixed ...$args): array { return $this->all; }
}
class TestParts extends PartRepository {
    public array $last=[];
    public function __construct() {}
    protected function query(string $sql, array $params=[]): PDOStatement {
        $this->last=[$sql,$params];return new TestStatement();
    }
}
$p=new TestParts();$p->getAll();check($p->last[1]===[],'getAll sem filtros nao gera avisos');
$p->getAll(['min_price'=>0,'max_price'=>'0.00']);check($p->last[1]===['min_price'=>'0.00','max_price'=>'0.00'],'preco zero nao e descartado');
$p->getAll(['brand'=>"Bosch' OR 1=1 --"]);check(!str_contains($p->last[0],"Bosch'") && $p->last[1]['brand']==="Bosch' OR 1=1 --",'marca e parametro, nao SQL concatenado');
rejects(fn()=> $p->getAll(['min_price'=>100,'max_price'=>20]),'minimo superior ao maximo');
rejects(fn()=> $p->getAll(['brand'=>[]]),'marca array');
rejects(fn()=> $p->getAll(['min_price'=>'abc']),'preco nao numerico');
check(Money::decimal(Money::multiply(Money::toCents('49.90'),2))==='99.80','49.90 vezes 2 exato');
check(Money::decimal(Money::multiply(Money::toCents('0.10'),3))==='0.30','0.10 vezes 3 exato');
check(Money::decimal(Money::toCents('99999999.99'))==='99999999.99','limite DECIMAL');
check(Money::toCents('25,50')===2550,'virgula decimal');
foreach (['abc','',null,[],true,-1,'1.234','999999999.00'] as $value) rejects(fn()=>Money::toCents($value),'preco invalido '.json_encode($value));
rejects(fn()=>Money::multiply(9999999999,2),'overflow do total');
foreach (['abc','1.5',1.5,0,-1,null,true,[]] as $value) rejects(fn()=>Input::positiveInt($value,'Quantidade'),'quantidade invalida '.json_encode($value));
check(Input::positiveInt('2','Quantidade')===2,'inteiro enviado pelo formulario');
check(h('<script>')==='&lt;script&gt;','escape de HTML');
class TestPDO extends PDO {
    public array $log=[];public bool $active=false;
    public function __construct() {}
    public function beginTransaction(): bool { $this->active=true;$this->log[]='begin';return true; }
    public function commit(): bool {$this->active=false;$this->log[]='commit';return true;}
    public function rollBack(): bool {$this->active=false;$this->log[]='rollback';return true;}
    public function inTransaction(): bool {return $this->active;}
    public function lastInsertId(?string $name=null): string|false {return '7';}
}
class TestSales extends SaleRepository {
    public TestPDO $db;public array $queries=[];public bool $clientExists=true;public int $active=1;public int $stock=18;public bool $failItem=false;
    public function __construct() {$this->db=new TestPDO();$this->pdo=$this->db;}
    protected function query(string $sql,array $params=[]): PDOStatement {
        $this->queries[]=[$sql,$params];
        if(str_contains($sql,'FROM clients')) return new TestStatement($this->clientExists?['id'=>1]:false);
        if(str_contains($sql,'FROM payment_methods')) return new TestStatement(['id'=>1,'active'=>$this->active]);
        if(str_contains($sql,'FROM parts')) return new TestStatement(['id'=>1,'name'=>'Peca','price'=>'49.90','stock'=>$this->stock]);
        if($this->failItem && str_contains($sql,'INSERT INTO sale_items')) throw new RuntimeException('Erro SQL simulado');
        return new TestStatement();
    }
}
$s=new TestSales();check($s->createSale(1,1,2,1)===7,'devolve id da venda');
check($s->db->log===['begin','commit'],'venda valida faz commit');
$insert=array_values(array_filter($s->queries,fn($q)=>str_contains($q[0],'INSERT INTO sales')))[0];
check($insert[1]['total']==='99.80','total SQL e decimal exato');
$s=new TestSales();$s->active=0;rejects(fn()=>$s->createSale(1,1,1,1),'pagamento inativo');check($s->db->log===['begin','rollback'],'pagamento inativo reverte');
$s=new TestSales();$s->stock=0;rejects(fn()=>$s->createSale(1,1,1,1),'stock insuficiente');check($s->db->log===['begin','rollback'],'stock insuficiente reverte');
$s=new TestSales();$s->clientExists=false;rejects(fn()=>$s->createSale(1,1,1,1),'cliente inexistente');
$s=new TestSales();$s->failItem=true;
try {$s->createSale(1,1,1,1);throw new LogicException('Esperava erro');} catch(RuntimeException $e) {check($e->getMessage()==='Erro SQL simulado','propaga erro apos falha do item');}
check($s->db->log===['begin','rollback'],'falha do item provoca rollback');
$_SESSION=['csrf'=>str_repeat('a',64)];
$_POST=['csrf'=>str_repeat('a',64)];checkCsrf();check(true,'token CSRF correspondente');
$_POST=['csrf'=>'errado'];rejects(fn()=>checkCsrf(),'token CSRF errado');
$_POST=['csrf'=>[]];rejects(fn()=>checkCsrf(),'token CSRF array');
$old=getenv('DB_PASSWORD');putenv('DB_PASSWORD=');
if (!is_file(__DIR__.'/../php-app/config/config.local.php')) {
    $cfg=require __DIR__.'/../php-app/config/config.php';check($cfg['db']['password']==='','config preserva palavra-passe vazia');
}
if($old===false){putenv('DB_PASSWORD');}else{putenv('DB_PASSWORD='.$old);}
echo "\n$count verificacoes PHP passaram (sem MySQL real).\n";
