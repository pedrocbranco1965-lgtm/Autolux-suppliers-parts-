import { Link, useNavigate, useParams } from 'react-router-dom';
import { useFavorites } from '../context/FavoritesContext.jsx';
import { useVehicles } from '../hooks/useVehicles.js';
import { formatCurrency, formatKm } from '../utils/formatters.js';

function VehicleDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { vehicles, loading, error } = useVehicles();
  const { isFavorite, toggleFavorite } = useFavorites();
  const vehicle = vehicles.find((item) => item.id === Number(id));

  if (loading) {
    return <section className="section container"><p>A carregar detalhe da viatura...</p></section>;
  }

  if (error) {
    return <section className="section container"><p className="error-message">{error}</p></section>;
  }

  if (!vehicle) {
    return (
      <section className="section container empty-state">
        <h1>Viatura não encontrada</h1>
        <p>A viatura selecionada não existe no catálogo.</p>
        <Link to="/catalogo" className="button">Voltar ao catálogo</Link>
      </section>
    );
  }

  return (
    <section className="section container">
      <Link to="/catalogo" className="back-link">← Voltar ao catálogo</Link>

      <div className="detail-layout">
        <img className="detail-image" src={vehicle.imagem} alt={`${vehicle.marca} ${vehicle.modelo}`} />

        <div className="detail-content">
          <p className="eyebrow">{vehicle.marca}</p>
          <h1>{vehicle.modelo}</h1>
          <p className="detail-price">{formatCurrency(vehicle.preco)}</p>
          <p>{vehicle.descricao}</p>

          <dl className="spec-list">
            <div><dt>Ano</dt><dd>{vehicle.ano}</dd></div>
            <div><dt>Quilometragem</dt><dd>{formatKm(vehicle.km)}</dd></div>
            <div><dt>Combustível</dt><dd>{vehicle.combustivel}</dd></div>
            <div><dt>Caixa</dt><dd>{vehicle.caixa}</dd></div>
            <div><dt>Potência</dt><dd>{vehicle.potencia}</dd></div>
            <div><dt>Cor</dt><dd>{vehicle.cor}</dd></div>
          </dl>

          <div className="detail-actions">
            <button className="button" onClick={() => toggleFavorite(vehicle)}>
              {isFavorite(vehicle.id) ? 'Remover dos favoritos' : 'Adicionar aos favoritos'}
            </button>
            <button className="button button-secondary" onClick={() => navigate(`/contacto?veiculo=${vehicle.id}`)}>
              Pedir contacto
            </button>
          </div>
        </div>
      </div>
    </section>
  );
}

export default VehicleDetail;
