<?php
require_once __DIR__ . '/DatabaseSource.php';

class ClientRepository extends DatabaseSource
{
    public function getAll(): array
    {
        return $this->query('SELECT * FROM clients ORDER BY name')->fetchAll();
    }
}
