import { Link } from 'react-router-dom';
import SearchBox from '../components/SearchBox.jsx';
import VehicleCard from '../components/VehicleCard.jsx';
import { useFavorites } from '../context/FavoritesContext.jsx';
import { useVehicles } from '../hooks/useVehicles.js';

function Home() {
  const { vehicles, loading, error } = useVehicles();
  const { isFavorite, toggleFavorite } = useFavorites();
  const featuredVehicles = vehicles.filter((vehicle) => vehicle.destaque).slice(0, 4);

  return (
    <>
      <section className="hero">
        <div className="hero-content">
          <p className="eyebrow">Stand automóvel premium</p>
          <h1>Encontre a sua próxima viatura com confiança.</h1>
          <p>
            A AutoLux reúne viaturas selecionadas, informação clara e um processo simples para pedir contacto ou proposta.
          </p>
          <SearchBox />
          <div className="hero-actions">
            <Link to="/catalogo" className="button">Ver catálogo</Link>
            <Link to="/sobre" className="button button-secondary">Conhecer a AutoLux</Link>
          </div>
        </div>
      </section>

      <section className="section container">
        <div className="section-heading">
          <p className="eyebrow">Destaques</p>
          <h2>Viaturas em destaque</h2>
          <p>Uma seleção de 3 a 4 veículos para cumprir os requisitos da página inicial.</p>
        </div>

        {loading && <p>A carregar viaturas...</p>}
        {error && <p className="error-message">{error}</p>}

        <div className="vehicle-grid">
          {featuredVehicles.map((vehicle) => (
            <VehicleCard
              key={vehicle.id}
              vehicle={vehicle}
              isFavorite={isFavorite(vehicle.id)}
              onToggleFavorite={toggleFavorite}
            />
          ))}
        </div>
      </section>
    </>
  );
}

export default Home;
