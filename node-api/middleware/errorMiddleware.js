function errorMiddleware(error, req, res, next) {
  if (res.headersSent) return next(error);
  let status = 500;
  let message = 'Erro interno do servidor.';
  if (error.type === 'entity.parse.failed') {
    status = 400; message = 'O corpo do pedido nao contem JSON valido.';
  } else if (error.type === 'entity.too.large') {
    status = 413; message = 'O pedido excede o tamanho permitido.';
  } else if (error.status === 400) {
    status = 400; message = error.message;
  } else if (error.code === 'ER_DUP_ENTRY') {
    status = 409; message = 'Ja existe um registo com este email ou referencia.';
  } else if (error.code === 'ER_ROW_IS_REFERENCED_2') {
    status = 409; message = 'O registo esta associado a encomendas e nao pode ser apagado.';
  } else if (error.code === 'ER_NO_REFERENCED_ROW_2') {
    status = 400; message = 'O fornecedor ou registo associado nao existe.';
  } else if (['ER_LOCK_DEADLOCK', 'ER_LOCK_WAIT_TIMEOUT'].includes(error.code)) {
    status = 409; message = 'Os dados estao a ser alterados noutro pedido. Tente novamente.';
  } else if (['ECONNREFUSED', 'ETIMEDOUT', 'ENOTFOUND', 'PROTOCOL_CONNECTION_LOST'].includes(error.code)) {
    status = 503; message = 'A base de dados esta temporariamente indisponivel.';
  }
  if (status >= 500) console.error('AutoLux API:', error.message);
  res.status(status).json({ success: false, message });
}
module.exports = errorMiddleware;
