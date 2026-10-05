import { useEffect, useState } from 'react';

export function useVehicles() {
  const [vehicles, setVehicles] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    async function loadVehicles() {
      try {
        const response = await fetch('/data/vehicles.json');
        if (!response.ok) {
          throw new Error('Não foi possível carregar os dados das viaturas.');
        }
        const data = await response.json();
        setVehicles(data);
      } catch (err) {
        setError(err.message);
      } finally {
        setLoading(false);
      }
    }

    loadVehicles();
  }, []);

  return { vehicles, loading, error };
}
