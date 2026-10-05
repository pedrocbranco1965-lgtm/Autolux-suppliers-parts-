import { Link } from 'react-router-dom';
import VehicleCard from '../components/VehicleCard.jsx';
import { useFavorites } from '../context/FavoritesContext.jsx';

function Favorites() {
  const { favorites, isFavorite, toggleFavorite, clearFavorites } = useFavorites();

  return (
    <section className="section container">
      <div className="section-heading with-action">
        <div>
          <p className="eyebrow">Favoritos</p>
          <h1>Viaturas guardadas</h1>
          <p>Consulte, remova ou limpe a lista de favoritos.</p>
        </div>

        {favorites.length > 0 && (
          <button className="button button-secondary" onClick={clearFavorites}>Limpar lista</button>
        )}
      </div>

      {favorites.length === 0 ? (
        <div className="empty-state">
          <h2>Ainda não adicionou favoritos.</h2>
          <p>Explore o catálogo e guarde as viaturas que quer comparar.</p>
          <Link to="/catalogo" className="button">Ver catálogo</Link>
        </div>
      ) : (
        <div className="vehicle-grid">
          {favorites.map((vehicle) => (
            <VehicleCard
              key={vehicle.id}
              vehicle={vehicle}
              isFavorite={isFavorite(vehicle.id)}
              onToggleFavorite={toggleFavorite}
            />
          ))}
        </div>
      )}
    </section>
  );
}

export default Favorites;
