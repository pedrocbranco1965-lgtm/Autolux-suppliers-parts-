import { Link } from 'react-router-dom';

function NotFound() {
  return (
    <section className="section container empty-state">
      <h1>Página não encontrada</h1>
      <p>A página que procura não existe ou foi movida.</p>
      <Link to="/" className="button">Voltar ao início</Link>
    </section>
  );
}

export default NotFound;
