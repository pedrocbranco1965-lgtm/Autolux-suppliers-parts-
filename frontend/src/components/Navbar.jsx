import { NavLink, Link } from 'react-router-dom';
import { useFavorites } from '../context/FavoritesContext.jsx';

function Navbar() {
  const { favorites } = useFavorites();
  const backOfficeUrl =
    import.meta.env.VITE_BACKOFFICE_URL
    || 'http://localhost:8888/AutoLux/php-app/public/';

  return (
    <header className="navbar">
      <Link to="/" className="brand" aria-label="AutoLux página inicial">
        <span className="brand-mark">AL</span>
        <span>AutoLux</span>
      </Link>

      <nav className="nav-links" aria-label="Menu principal">
        <NavLink to="/">Início</NavLink>
        <NavLink to="/catalogo">Catálogo</NavLink>
        <NavLink to="/favoritos">Favoritos <span className="badge">{favorites.length}</span></NavLink>
        <NavLink to="/contacto">Contacto</NavLink>
        <NavLink to="/sobre">Sobre</NavLink>
        <a
          href={backOfficeUrl}
          className="management-link"
          aria-label="Abrir AutoLux Peças"
        >
          Peças
        </a>
      </nav>
    </header>
  );
}

export default Navbar;
