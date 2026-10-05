const MAX_CENTS = 9999999999; // DECIMAL(10,2)
const STATUSES = ['pendente', 'enviado', 'recebido', 'cancelado'];

function invalid(message) {
  const error = new Error(message);
  error.status = 400;
  return error;
}

function object(value) {
  if (!value || typeof value !== 'object' || Array.isArray(value)) {
    throw invalid('Envie um objeto JSON valido.');
  }
  return value;
}

function text(value, label, max, optional = false) {
  if (optional && (value === undefined || value === null)) return '';
  if (typeof value !== 'string') throw invalid(`${label} deve ser texto.`);
  const cleaned = value.trim();
  if ((!optional && cleaned === '') || Array.from(cleaned).length > max) {
    throw invalid(`${label} invalido: maximo de ${max} caracteres.`);
  }
  return cleaned;
}

function positiveInt(value, label = 'Identificador') {
  if (!['string', 'number'].includes(typeof value)
      || !/^[1-9][0-9]*$/.test(String(value))
      || !Number.isSafeInteger(Number(value)) || Number(value) > 2147483647) {
    throw invalid(`${label} deve ser um numero inteiro positivo valido.`);
  }
  return Number(value);
}

function toCents(value) {
  if (!['number', 'string'].includes(typeof value)) throw invalid('Custo invalido.');
  const normalized = String(value).trim().replace(',', '.');
  const match = /^(\d{1,8})(?:\.(\d{1,2}))?$/.exec(normalized);
  if (!match) throw invalid('Custo invalido: use zero ou um valor positivo com ate duas casas decimais.');
  return Number(match[1]) * 100 + Number((match[2] || '').padEnd(2, '0'));
}

function decimal(cents) {
  if (!Number.isSafeInteger(cents) || cents < 0 || cents > MAX_CENTS) {
    throw invalid('O montante excede o limite permitido.');
  }
  return `${Math.floor(cents / 100)}.${String(cents % 100).padStart(2, '0')}`;
}

function lineTotal(quantity, unitCost) {
  const qty = positiveInt(quantity, 'Quantidade');
  const cents = toCents(unitCost);
  if (cents > 0 && qty > Math.floor(MAX_CENTS / cents)) {
    throw invalid('O total da linha excede o limite permitido.');
  }
  return qty * cents;
}

function orderTotal(items) {
  let total = 0;
  for (const item of items) {
    total += lineTotal(item.quantity, item.unit_cost);
    if (total > MAX_CENTS) throw invalid('O total da encomenda excede o limite permitido.');
  }
  return decimal(total);
}

function supplier(body) {
  object(body);
  const name = text(body.name, 'Nome', 150);
  const email = text(body.email, 'Email', 150);
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) throw invalid('Email invalido.');
  return { name, email, phone: text(body.phone, 'Telefone', 30, true) || null };
}

function order(body) {
  object(body);
  const supplier_id = positiveInt(body.supplier_id, 'Fornecedor');
  const reference = text(body.reference, 'Referencia', 60);
  const status = body.status === undefined ? 'pendente' : body.status;
  if (!STATUSES.includes(status)) throw invalid('Estado de encomenda invalido.');
  const notes = text(body.notes, 'Notas', 2000, true) || null;
  if (!Array.isArray(body.items) || body.items.length < 1 || body.items.length > 100) {
    throw invalid('A encomenda deve ter entre 1 e 100 itens.');
  }
  const items = body.items.map(item => {
    object(item);
    return {
      part_reference: text(item.part_reference, 'Referencia da peca', 60),
      part_name: text(item.part_name, 'Nome da peca', 150),
      quantity: positiveInt(item.quantity, 'Quantidade'),
      unit_cost: decimal(toCents(item.unit_cost))
    };
  });
  orderTotal(items); // Valida o total antes de reservar uma ligacao MySQL.
  return { supplier_id, reference, status, notes, items };
}

module.exports = { invalid, positiveInt, toCents, decimal, lineTotal, orderTotal, supplier, order };
