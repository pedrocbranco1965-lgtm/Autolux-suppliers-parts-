import { Link } from 'react-router-dom';
import { formatCurrency, formatKm } from '../utils/formatters.js';

function VehicleCard({ vehicle, isFavorite, onToggleFavorite }) {
  return (
    <article className="vehicle-card">
      <Link to={`/veiculo/${vehicle.id}`} className="vehicle-image-link" aria-label={`Ver detalhes de ${vehicle.marca} ${vehicle.modelo}`}>
        <img src={vehicle.imagem} alt={`${vehicle.marca} ${vehicle.modelo}`} />
      </Link>

      <div className="vehicle-card-body">
        <div className="vehicle-title-row">
          <div>
            <p className="eyebrow">{vehicle.marca}</p>
            <h3>{vehicle.modelo}</h3>
          </div>
          <button className="icon-button" onClick={() => onToggleFavorite(vehicle)} aria-label="Adicionar ou remover favorito">
            {isFavorite ? '★' : '☆'}
          </button>
        </div>

        <ul className="vehicle-meta">
          <li>{vehicle.ano}</li>
          <li>{vehicle.combustivel}</li>
          <li>{formatKm(vehicle.km)}</li>
        </ul>

        <div className="card-footer-row">
          <strong className="price">{formatCurrency(vehicle.preco)}</strong>
          <Link to={`/veiculo/${vehicle.id}`} className="button button-small">Ver detalhes</Link>
        </div>
      </div>
    </article>
  );
}

export default VehicleCard;
