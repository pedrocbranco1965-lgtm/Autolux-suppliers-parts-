const repository = require('../repositories/orderRepository');
const validate = require('../utils/validation');

async function getAll(req, res, next) {
  try {
    res.json({ success: true, data: await repository.getAll() });
  } catch (error) { next(error); }
}

async function getById(req, res, next) {
  try {
    const item = await repository.findById(validate.positiveInt(req.params.id));
    if (!item) return res.status(404).json({ success: false, message: 'Encomenda nao encontrada.' });
    res.json({ success: true, data: item });
  } catch (error) { next(error); }
}

async function create(req, res, next) {
  try {
    const data = validate.order(req.body);
    const item = await repository.create(data);
    res.status(201).json({ success: true, data: item });
  } catch (error) { next(error); }
}

async function update(req, res, next) {
  try {
    const id = validate.positiveInt(req.params.id);
    const data = validate.order(req.body);
    const item = await repository.update(id, data);
    if (!item) return res.status(404).json({ success: false, message: 'Encomenda nao encontrada.' });
    res.json({ success: true, data: item });
  } catch (error) { next(error); }
}

async function remove(req, res, next) {
  try {
    const deleted = await repository.remove(validate.positiveInt(req.params.id));
    if (!deleted) return res.status(404).json({ success: false, message: 'Encomenda nao encontrada.' });
    res.json({ success: true, message: 'Encomenda apagada.' });
  } catch (error) { next(error); }
}

module.exports = { getAll, getById, create, update, remove };
