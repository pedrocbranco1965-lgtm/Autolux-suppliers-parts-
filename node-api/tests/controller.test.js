const test = require('node:test');
const assert = require('node:assert/strict');
// Duplo de teste: estes testes nao fazem pedidos SQL nem HTTP reais.
let calls = 0;
let row = { id: 1 };
const mock = {
  getAll: async () => [row], findById: async () => row,
  create: async data => { calls++; return { id:1, ...data }; },
  update: async (id,data) => { calls++; return row ? { id, ...data } : null; },
  remove: async () => Boolean(row)
};
const p = require.resolve('../repositories/orderRepository');
require.cache[p] = { id:p, filename:p, loaded:true, exports:mock };
const controller = require('../controllers/orderController');
function response() {
  return { code:200, payload:null, status(n) {this.code=n; return this;}, json(p) {this.payload=p; return this;} };
}
test('POST invalido nao chega ao repository', async () => {
  calls=0;let error;const res=response();
  await controller.create({body:{}},res,e=>{error=e;});
  assert.equal(error.status,400);assert.equal(calls,0);
});
test('POST valido devolve 201', async () => {
  calls=0;const res=response();
  await controller.create({body:{supplier_id:1,reference:'T-1',items:[{part_reference:'P',part_name:'Peca',quantity:1,unit_cost:2}]}},res,e=>{throw e;});
  assert.equal(res.code,201);assert.equal(res.payload.data.total,undefined);assert.equal(calls,1);
});
test('GET identificador invalido devolve erro 400', async () => {
  let error;await controller.getById({params:{id:'abc'}},response(),e=>{error=e;});assert.equal(error.status,400);
});
test('GET inexistente devolve 404', async () => {
  row=null;const res=response();await controller.getById({params:{id:'999'}},res,e=>{throw e;});assert.equal(res.code,404);row={id:1};
});
const errorMiddleware=require('../middleware/errorMiddleware');
for(const [error,code] of [[{type:'entity.parse.failed'},400],[{type:'entity.too.large'},413],
 [{code:'ER_DUP_ENTRY'},409],[{code:'ER_ROW_IS_REFERENCED_2'},409],[{code:'ER_NO_REFERENCED_ROW_2'},400]]) {
 test(`middleware ${JSON.stringify(error)}`,()=>{const res=response();errorMiddleware(error,{},res,()=>{});assert.equal(res.code,code);assert.equal(res.payload.success,false);});
}
