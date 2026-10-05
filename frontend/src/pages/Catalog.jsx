import { useEffect, useMemo, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import FilterBar from '../components/FilterBar.jsx';
import VehicleCard from '../components/VehicleCard.jsx';
import { useFavorites } from '../context/FavoritesContext.jsx';
import { useVehicles } from '../hooks/useVehicles.js';

const emptyFilters = {
  pesquisa: '',
  marca: '',
  combustivel: '',
  precoMax: '',
  anoMin: ''
};

function Catalog() {
  const { vehicles, loading, error } = useVehicles();
  const { isFavorite, toggleFavorite } = useFavorites();
  const [searchParams] = useSearchParams();
  const [filters, setFilters] = useState(emptyFilters);

  useEffect(() => {
    const pesquisa = searchParams.get('pesquisa') || '';
    setFilters((currentFilters) => ({ ...currentFilters, pesquisa }));
  }, [searchParams]);

  const brands = useMemo(() => [...new Set(vehicles.map((vehicle) => vehicle.marca))].sort(), [vehicles]);
  const fuels = useMemo(() => [...new Set(vehicles.map((vehicle) => vehicle.combustivel))].sort(), [vehicles]);

  const filteredVehicles = useMemo(() => {
    return vehicles.filter((vehicle) => {
      const text = `${vehicle.marca} ${vehicle.modelo}`.toLowerCase();
      const matchesSearch = text.includes(filters.pesquisa.toLowerCase());
      const matchesBrand = !filters.marca || vehicle.marca === filters.marca;
      const matchesFuel = !filters.combustivel || vehicle.combustivel === filters.combustivel;
      const matchesPrice = !filters.precoMax || vehicle.preco <= Number(filters.precoMax);
      const matchesYear = !filters.anoMin || vehicle.ano >= Number(filters.anoMin);

      return matchesSearch && matchesBrand && matchesFuel && matchesPrice && matchesYear;
    });
  }, [vehicles, filters]);

  function clearFilters() {
    setFilters(emptyFilters);
  }

  return (
    <section className="section container">
      <div className="section-heading">
        <p className="eyebrow">Catálogo</p>
        <h1>Viaturas disponíveis</h1>
        <p>Use os filtros para pesquisar por marca, combustível, preço e ano sem recarregar a página.</p>
      </div>

      <FilterBar filters={filters} onChange={setFilters} brands={brands} fuels={fuels} onClear={clearFilters} />

      {loading && <p>A carregar catálogo...</p>}
      {error && <p className="error-message">{error}</p>}

      {!loading && (
        <p className="results-count">{filteredVehicles.length} viatura(s) encontrada(s)</p>
      )}

      <div className="vehicle-grid">
        {filteredVehicles.map((vehicle) => (
          <VehicleCard
            key={vehicle.id}
            vehicle={vehicle}
            isFavorite={isFavorite(vehicle.id)}
            onToggleFavorite={toggleFavorite}
          />
        ))}
      </div>

      {!loading && filteredVehicles.length === 0 && (
        <div className="empty-state">
          <h2>Não foram encontradas viaturas.</h2>
          <p>Altere ou limpe os filtros para ver mais resultados.</p>
        </div>
      )}
    </section>
  );
}

export default Catalog;
