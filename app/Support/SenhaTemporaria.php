<?php

namespace App\Support;

/**
 * Senha temporária do primeiro acesso.
 *
 * Ela é digitada à mão, a partir de um e-mail, então o alfabeto exclui os
 * caracteres que se confundem na leitura (0/O, 1/l/I) e não usa símbolos.
 * O tamanho compensa: 10 posições em 57 símbolos possíveis é bem mais do que
 * as 8 posições exigidas pela validação.
 */
final class SenhaTemporaria
{
    private const MINUSCULAS = 'abcdefghijkmnpqrstuvwxyz';

    private const MAIUSCULAS = 'ABCDEFGHJKLMNPQRSTUVWXYZ';

    private const NUMEROS = '23456789';

    public static function gerar(int $tamanho = 10): string
    {
        $tamanho = max(8, $tamanho);
        $alfabeto = self::MINUSCULAS.self::MAIUSCULAS.self::NUMEROS;

        // Garante ao menos um de cada tipo — sorteio puro pode devolver uma
        // senha só de letras, que passaria raspando em qualquer regra futura
        // de composição.
        $caracteres = [
            self::sortear(self::MINUSCULAS),
            self::sortear(self::MAIUSCULAS),
            self::sortear(self::NUMEROS),
        ];

        while (count($caracteres) < $tamanho) {
            $caracteres[] = self::sortear($alfabeto);
        }

        // Embaralha com random_int (shuffle() não é criptográfico) para que as
        // três primeiras posições não sejam sempre minúscula/maiúscula/número.
        for ($i = count($caracteres) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$caracteres[$i], $caracteres[$j]] = [$caracteres[$j], $caracteres[$i]];
        }

        return implode('', $caracteres);
    }

    private static function sortear(string $alfabeto): string
    {
        return $alfabeto[random_int(0, strlen($alfabeto) - 1)];
    }
}
