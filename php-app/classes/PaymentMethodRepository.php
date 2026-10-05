<?php
require_once __DIR__ . '/DatabaseSource.php';

class PaymentMethodRepository extends DatabaseSource
{
    public function getAll(): array
    {
        return $this->query('SELECT * FROM payment_methods WHERE active = 1 ORDER BY name')->fetchAll();
    }
}
