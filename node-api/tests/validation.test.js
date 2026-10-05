const test = require('node:test');
const assert = require('node:assert/strict');
const v = require('../utils/validation');
const valid = () => ({ supplier_id: 1, reference: 'ENC-1', items: [
  { part_reference: 'P-1', part_name: 'Peca', quantity: 2, unit_cost: '49.90' }
] });

test('normaliza uma encomenda valida e calcula 99.80 exatamente', () => {
  const o = v.order(valid());
  assert.equal(o.status, 'pendente');
  assert.equal(v.orderTotal(o.items), '99.80');
});
test('preco zero e permitido', () => {
  const b = valid(); b.items[0].unit_cost = 0;
  assert.equal(v.orderTotal(v.order(b).items), '0.00');
});
test('0.10 vezes 3 resulta em 0.30', () => assert.equal(v.decimal(v.lineTotal(3, '0.10')), '0.30'));
test('aceita virgula decimal normalizando para ponto', () => assert.equal(v.decimal(v.toCents('25,50')), '25.50'));
for (const quantity of ['abc', '1.5', 1.5, 0, -1, null, undefined, true, [], {}, '2147483648']) {
  test(`rejeita quantidade ${JSON.stringify(quantity)}`, () => {
    const b = valid(); b.items[0].quantity = quantity;
    assert.throws(() => v.order(b), { status: 400 });
  });
}
for (const cost of ['abc', '', -1, '3.555', null, undefined, true, [], {}, '999999999.00']) {
  test(`rejeita custo ${JSON.stringify(cost)}`, () => {
    const b = valid(); b.items[0].unit_cost = cost;
    assert.throws(() => v.order(b), { status: 400 });
  });
}
for (const body of [undefined, null, [], 'hello', {}, { ...valid(), items: [] }, { ...valid(), items: [null] }]) {
  test(`rejeita corpo invalido ${JSON.stringify(body)}`, () => assert.throws(() => v.order(body), { status: 400 }));
}
test('rejeita estado desconhecido', () => assert.throws(() => v.order({ ...valid(), status: 'banana' }), { status: 400 }));
test('rejeita referencia vazia', () => assert.throws(() => v.order({ ...valid(), reference: '  ' }), { status: 400 }));
test('rejeita referencia demasiado longa', () => assert.throws(() => v.order({ ...valid(), reference: 'x'.repeat(61) }), { status: 400 }));
test('rejeita total acima de DECIMAL(10,2)', () => {
  const b=valid(); b.items[0].quantity=2; b.items[0].unit_cost='99999999.99';
  assert.throws(() => v.order(b), { status: 400 });
});
test('limite exato de DECIMAL(10,2)', () => assert.equal(v.decimal(v.toCents('99999999.99')), '99999999.99'));
test('fornecedor valido', () => assert.deepEqual(v.supplier({name:' Teste ',email:'t@example.com'}),
  {name:'Teste',email:'t@example.com',phone:null}));
test('email invalido', () => assert.throws(() => v.supplier({name:'Teste',email:'abc'}), {status:400}));
test('nome array', () => assert.throws(() => v.supplier({name:[],email:'t@example.com'}), {status:400}));
