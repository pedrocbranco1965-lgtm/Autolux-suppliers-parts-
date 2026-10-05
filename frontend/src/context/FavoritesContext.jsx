import { createContext, useContext, useMemo, useState } from 'react';

const FavoritesContext = createContext(null);

export function FavoritesProvider({ children }) {
  const [favorites, setFavorites] = useState([]);

  function isFavorite(vehicleId) {
    return favorites.some((vehicle) => vehicle.id === vehicleId);
  }

  function addFavorite(vehicle) {
    setFavorites((currentFavorites) => {
      if (currentFavorites.some((item) => item.id === vehicle.id)) {
        return currentFavorites;
      }
      return [...currentFavorites, vehicle];
    });
  }

  function removeFavorite(vehicleId) {
    setFavorites((currentFavorites) => currentFavorites.filter((vehicle) => vehicle.id !== vehicleId));
  }

  function toggleFavorite(vehicle) {
    if (isFavorite(vehicle.id)) {
      removeFavorite(vehicle.id);
    } else {
      addFavorite(vehicle);
    }
  }

  function clearFavorites() {
    setFavorites([]);
  }

  const value = useMemo(
    () => ({ favorites, isFavorite, addFavorite, removeFavorite, toggleFavorite, clearFavorites }),
    [favorites]
  );

  return <FavoritesContext.Provider value={value}>{children}</FavoritesContext.Provider>;
}

export function useFavorites() {
  const context = useContext(FavoritesContext);

  if (!context) {
    throw new Error('useFavorites deve ser usado dentro de FavoritesProvider');
  }

  return context;
}
