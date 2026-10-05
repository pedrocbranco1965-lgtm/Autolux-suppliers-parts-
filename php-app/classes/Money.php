<?php
// Dinheiro calculado em centimos inteiros; SQL recebe strings DECIMAL.
final class Money
{
    public const MAX_CENTS = 9999999999; // DECIMAL(10,2)

    public static function toCents(mixed $value): int
    {
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            throw new InvalidArgumentException('O preco deve ser um numero valido.');
        }
        $value = str_replace(',', '.', trim((string) $value));
        if (!preg_match('/^(\d{1,8})(?:\.(\d{1,2}))?$/D', $value, $match)) {
            throw new InvalidArgumentException('O preco deve ser positivo ou zero, com ate duas casas decimais.');
        }
        return (int) $match[1] * 100 + (int) str_pad($match[2] ?? '', 2, '0');
    }

    public static function decimal(int $cents): string
    {
        if ($cents < 0 || $cents > self::MAX_CENTS) {
            throw new InvalidArgumentException('O montante excede o limite permitido.');
        }
        return intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function multiply(int $unitCents, int $quantity): int
    {
        if ($unitCents < 0 || $unitCents > self::MAX_CENTS || $quantity < 1
            || ($unitCents > 0 && $quantity > intdiv(self::MAX_CENTS, $unitCents))) {
            throw new InvalidArgumentException('O total excede o limite permitido.');
        }
        return $unitCents * $quantity;
    }
}
