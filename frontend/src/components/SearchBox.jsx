import { useState } from 'react';
import { useNavigate } from 'react-router-dom';

function SearchBox({ placeholder = 'Pesquisar por marca ou modelo' }) {
  const [search, setSearch] = useState('');
  const navigate = useNavigate();

  function handleSubmit(event) {
    event.preventDefault();
    const query = search.trim();
    navigate(query ? `/catalogo?pesquisa=${encodeURIComponent(query)}` : '/catalogo');
  }

  return (
    <form className="search-box" onSubmit={handleSubmit}>
      <input
        type="search"
        value={search}
        placeholder={placeholder}
        onChange={(event) => setSearch(event.target.value)}
      />
      <button className="button" type="submit">Pesquisar</button>
    </form>
  );
}

export default SearchBox;
