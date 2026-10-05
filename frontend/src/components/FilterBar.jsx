function FilterBar({ filters, onChange, brands, fuels, onClear }) {
  function updateFilter(event) {
    const { name, value } = event.target;
    onChange({ ...filters, [name]: value });
  }

  return (
    <section className="filter-bar" aria-label="Filtros de catálogo">
      <label>
        Pesquisa
        <input
          type="search"
          name="pesquisa"
          value={filters.pesquisa}
          placeholder="Marca ou modelo"
          onChange={updateFilter}
        />
      </label>

      <label>
        Marca
        <select name="marca" value={filters.marca} onChange={updateFilter}>
          <option value="">Todas</option>
          {brands.map((brand) => (
            <option key={brand} value={brand}>{brand}</option>
          ))}
        </select>
      </label>

      <label>
        Combustível
        <select name="combustivel" value={filters.combustivel} onChange={updateFilter}>
          <option value="">Todos</option>
          {fuels.map((fuel) => (
            <option key={fuel} value={fuel}>{fuel}</option>
          ))}
        </select>
      </label>

      <label>
        Preço máximo
        <select name="precoMax" value={filters.precoMax} onChange={updateFilter}>
          <option value="">Sem limite</option>
          <option value="20000">Até 20.000 €</option>
          <option value="30000">Até 30.000 €</option>
          <option value="40000">Até 40.000 €</option>
          <option value="60000">Até 60.000 €</option>
        </select>
      </label>

      <label>
        Ano mínimo
        <select name="anoMin" value={filters.anoMin} onChange={updateFilter}>
          <option value="">Todos</option>
          <option value="2019">Desde 2019</option>
          <option value="2020">Desde 2020</option>
          <option value="2021">Desde 2021</option>
          <option value="2022">Desde 2022</option>
        </select>
      </label>

      <button className="button button-secondary" onClick={onClear}>Limpar filtros</button>
    </section>
  );
}

export default FilterBar;
