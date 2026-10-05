<?php
// Validacao partilhada. Converter com (int) nao substitui validar o valor.
final class Input
{
    public static function text(mixed $value, string $label, int $max, bool $required = true): string
    {
        if (!is_string($value) && !is_int($value) && $value !== null) {
            throw new InvalidArgumentException("$label deve ser texto.");
        }
        $text = trim((string) ($value ?? ''));
        $length = preg_match_all('/./us', $text);
        if ($length === false || $length > $max || ($required && $text === '')) {
            throw new InvalidArgumentException("$label invalido: maximo de $max caracteres.");
        }
        return $text;
    }

    public static function positiveInt(mixed $value, string $label): int
    {
        if ((!is_int($value) && !is_string($value))
            || !preg_match('/^[1-9][0-9]*$/D', (string) $value)
            || strlen((string) $value) > 10
            || (int) $value > 2147483647) {
            throw new InvalidArgumentException("$label deve ser um numero inteiro positivo valido.");
        }
        return (int) $value;
    }
}
