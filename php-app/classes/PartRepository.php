<?php
require_once __DIR__ . '/DatabaseSource.php';
require_once __DIR__ . '/Input.php';
require_once __DIR__ . '/Money.php';

class PartRepository extends DatabaseSource
{
    public function getAll(array $filters = []): array
    {
        // As chaves podem nao existir quando venda.php chama getAll() sem argumentos.
        $brand = Input::text($filters['brand'] ?? '', 'Marca', 100, false);
        $type = Input::text($filters['part_type'] ?? '', 'Tipo de peca', 100, false);
        $min = $filters['min_price'] ?? '';
        $max = $filters['max_price'] ?? '';
        $minCents = $min === '' ? null : Money::toCents($min);
        $maxCents = $max === '' ? null : Money::toCents($max);
        if ($minCents !== null && $maxCents !== null && $minCents > $maxCents) {
            throw new InvalidArgumentException('O preco minimo nao pode ser superior ao maximo.');
        }
        $sql = 'SELECT * FROM parts WHERE 1 = 1';
        $params = [];
        if ($brand !== '') {
            $sql .= ' AND brand = :brand';
            $params['brand'] = $brand;
        }
        if ($type !== '') {
            $sql .= ' AND part_type = :part_type';
            $params['part_type'] = $type;
        }
        if ($minCents !== null) {
            $sql .= ' AND price >= :min_price';
            $params['min_price'] = Money::decimal($minCents);
        }
        if ($maxCents !== null) {
            $sql .= ' AND price <= :max_price';
            $params['max_price'] = Money::decimal($maxCents);
        }
        $sql .= ' ORDER BY brand, name';
        return $this->query($sql, $params)->fetchAll();
    }

    public function findById(int $id): ?array
    {
        Input::positiveInt($id, 'Peca');
        $part = $this->query('SELECT * FROM parts WHERE id = :id', ['id' => $id])->fetch();
        return $part ?: null;
    }

    public function getBrands(): array
    {
        return $this->query('SELECT DISTINCT brand FROM parts ORDER BY brand')->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getTypes(): array
    {
        return $this->query('SELECT DISTINCT part_type FROM parts ORDER BY part_type')->fetchAll(PDO::FETCH_COLUMN);
    }
}
