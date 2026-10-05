const test=require('node:test');
const assert=require('node:assert/strict');
// Duplo de ligacao MySQL. Valida a sequencia, nao a execucao real do SQL.
let log=[];let fail=false;let exists=true;
const conn={
 beginTransaction:async()=>{log.push('begin');}, commit:async()=>{log.push('commit');},
 rollback:async()=>{log.push('rollback');}, release:()=>{log.push('release');},
 execute:async(sql,params)=>{
   assert.equal((sql.match(/\?/g)||[]).length,params.length);
   log.push(sql.replace(/\s+/g,' ').trim());
   if(fail && sql.includes('INSERT INTO supplier_order_items')) throw new Error('erro simulado');
   if(sql.includes('SELECT id FROM supplier_orders')) return [exists?[{id:1}]:[],[]];
   if(sql.includes('SELECT o.*')) return [[{id:1,reference:'T-1',total:'99.80'}],[]];
   if(sql.includes('SELECT * FROM supplier_order_items')) return [[{id:1,quantity:2,unit_cost:'49.90'}],[]];
   if(sql.includes('INSERT INTO supplier_orders')) return [{insertId:1},[]];
   return [{affectedRows:0},[]];
 }
};
const db=require.resolve('../config/database');require.cache[db]={id:db,filename:db,loaded:true,exports:{pool:{getConnection:async()=>conn}}};
const repo=require('../repositories/orderRepository');
const data=()=>({supplier_id:1,reference:'T-1',status:'pendente',notes:null,items:[{part_reference:'P',part_name:'Peca',quantity:2,unit_cost:'49.90'}]});
test('criacao le o resultado antes do commit e liberta a ligacao',async()=>{
 log=[];fail=false;const result=await repo.create(data());assert.equal(result.id,1);
 assert.equal(log[0],'begin');assert.deepEqual(log.slice(-2),['commit','release']);
 assert.ok(log.some(x=>x.includes('INSERT INTO supplier_order_items')));
});
test('erro ao criar item provoca rollback e release',async()=>{
 log=[];fail=true;await assert.rejects(repo.create(data()),/simulado/);fail=false;
 assert.deepEqual(log.slice(-2),['rollback','release']);assert.ok(!log.includes('commit'));
});
test('PUT com valores iguais nao devolve 404 por affectedRows zero',async()=>{
 log=[];exists=true;const result=await repo.update(1,data());assert.equal(result.id,1);
 assert.deepEqual(log.slice(-2),['commit','release']);
});
test('PUT para id inexistente nao altera itens',async()=>{
 log=[];exists=false;assert.equal(await repo.update(999,data()),null);exists=true;
 assert.ok(!log.some(x=>x.startsWith('DELETE')));assert.equal(log.at(-1),'release');
});
