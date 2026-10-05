<?php
require_once __DIR__ . '/DatabaseSource.php';
require_once __DIR__ . '/Input.php';
require_once __DIR__ . '/Money.php';

class SaleRepository extends DatabaseSource
{
    public function getAll(): array
    {
        return $this->query(
            'SELECT s.id, c.name AS client_name, pm.name AS payment_method, s.total, s.sold_at
             FROM sales s JOIN clients c ON c.id = s.client_id
             JOIN payment_methods pm ON pm.id = s.payment_method_id ORDER BY s.id DESC'
        )->fetchAll();
    }

    public function createSale(int $clientId, int $partId, int $quantity, int $paymentMethodId): int
    {
        Input::positiveInt($clientId, 'Cliente');
        Input::positiveInt($partId, 'Peca');
        Input::positiveInt($quantity, 'Quantidade');
        Input::positiveInt($paymentMethodId, 'Pagamento');
        $this->pdo->beginTransaction();
        try {
            if (!$this->query('SELECT id FROM clients WHERE id = :id FOR UPDATE', ['id' => $clientId])->fetch()) {
                throw new InvalidArgumentException('O cliente selecionado nao existe.');
            }
            $payment = $this->query(
                'SELECT id, active FROM payment_methods WHERE id = :id FOR UPDATE',
                ['id' => $paymentMethodId]
            )->fetch();
            if (!$payment || (int) $payment['active'] !== 1) {
                throw new InvalidArgumentException('O metodo de pagamento nao esta disponivel.');
            }
            $part = $this->query(
                'SELECT id, name, price, stock FROM parts WHERE id = :id FOR UPDATE',
                ['id' => $partId]
            )->fetch();
            if (!$part) {
                throw new InvalidArgumentException('A peca selecionada nao existe.');
            }
            if ((int) $part['stock'] < $quantity) {
                throw new InvalidArgumentException('Stock insuficiente para esta venda.');
            }
            $unitCents = Money::toCents($part['price']);
            $unitPrice = Money::decimal($unitCents);
            $subtotal = Money::decimal(Money::multiply($unitCents, $quantity));
            $this->query(
                'INSERT INTO sales (client_id, payment_method_id, total)
                 VALUES (:client_id, :payment_method_id, :total)',
                ['client_id' => $clientId, 'payment_method_id' => $paymentMethodId, 'total' => $subtotal]
            );
            $saleId = (int) $this->pdo->lastInsertId();
            $this->query(
                'INSERT INTO sale_items (sale_id, part_id, quantity, unit_price, subtotal)
                 VALUES (:sale_id, :part_id, :quantity, :unit_price, :subtotal)',
                ['sale_id' => $saleId, 'part_id' => $partId, 'quantity' => $quantity,
                 'unit_price' => $unitPrice, 'subtotal' => $subtotal]
            );
            $this->query('UPDATE parts SET stock = stock - :quantity WHERE id = :id',
                ['quantity' => $quantity, 'id' => $partId]);
            $this->pdo->commit();
            return $saleId;
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }
}
