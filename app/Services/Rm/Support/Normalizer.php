<?php

namespace App\Services\Rm\Support;

/**
 * Normalizações puras usadas pela importação do TOTVS RM.
 * Sem estado e sem dependência do framework — testável isoladamente.
 */
final class Normalizer
{
    /** Prefixo IBGE por UF (2 dígitos) para compor o código de município de 7 dígitos. */
    private const IBGE_UF = [
        'RO' => '11', 'AC' => '12', 'AM' => '13', 'RR' => '14', 'PA' => '15',
        'AP' => '16', 'TO' => '17', 'MA' => '21', 'PI' => '22', 'CE' => '23',
        'RN' => '24', 'PB' => '25', 'PE' => '26', 'AL' => '27', 'SE' => '28',
        'BA' => '29', 'MG' => '31', 'ES' => '32', 'RJ' => '33', 'SP' => '35',
        'PR' => '41', 'SC' => '42', 'RS' => '43', 'MS' => '50', 'MT' => '51',
        'GO' => '52', 'DF' => '53',
    ];

    public static function digits(?string $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    /**
     * CPF (11) ou CNPJ (14), rejeitando sequências repetidas ("000..."/"111...").
     * Não valida dígito verificador de propósito: o RM pode ter documentos
     * formalmente inválidos que o negócio quer importar mesmo assim.
     */
    public static function isValidDoc(string $digits): bool
    {
        if (! in_array(strlen($digits), [11, 14], true)) {
            return false;
        }

        return preg_match('/^(\d)\1*$/', $digits) !== 1;
    }

    /** Aplica a máscara usada no banco do app: 11 => ###.###.###-##, 14 => ##.###.###/####-##. */
    public static function formatCpfCnpj(string $digits): string
    {
        if (strlen($digits) === 11) {
            return vsprintf('%s%s%s.%s%s%s.%s%s%s-%s%s', str_split($digits));
        }

        if (strlen($digits) === 14) {
            return vsprintf('%s%s.%s%s%s.%s%s%s/%s%s%s%s-%s%s', str_split($digits));
        }

        return $digits;
    }

    public static function trimOrNull(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * O RM guarda múltiplos e-mails num campo só, separados por ';', ',' ou espaço.
     *
     * @return list<string> e-mails válidos, minúsculos e sem repetição, na ordem original
     */
    public static function splitEmails(?string $raw): array
    {
        $parts = preg_split('/[;,\s]+/', (string) $raw) ?: [];

        $emails = [];
        foreach ($parts as $part) {
            $email = mb_strtolower(trim($part, " \t\n\r\0\x0B<>\"'"));
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && ! in_array($email, $emails, true)) {
                $emails[] = $email;
            }
        }

        return $emails;
    }

    /**
     * Datas do sqlsrv chegam como string "Y-m-d H:i:s.v". Datas <= 1900-01-01
     * são a convenção RM para "vazio" e viram null.
     */
    public static function toDateOrNull(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            $date = $value->format('Y-m-d');
        } elseif (is_string($value) && preg_match('/^(\d{4}-\d{2}-\d{2})/', trim($value), $m)) {
            $date = $m[1];
        } else {
            return null;
        }

        return $date <= '1900-01-01' ? null : $date;
    }

    /** Chave de deduplicação por nome: minúsculo, sem acento não tratado, espaços colapsados. */
    public static function normalizeName(?string $value): string
    {
        $value = mb_strtolower(trim((string) $value));

        return preg_replace('/\s+/u', ' ', $value) ?? $value;
    }

    /**
     * O RM tipicamente guarda o código municipal com 5 dígitos (sem o prefixo da UF);
     * o app usa o código IBGE completo de 7 (padrão ViaCEP).
     */
    public static function composeIbge(?string $uf, ?string $codMunicipio): ?string
    {
        $digits = self::digits($codMunicipio);

        if (strlen($digits) === 7) {
            return $digits;
        }

        $prefix = self::IBGE_UF[strtoupper(trim((string) $uf))] ?? null;
        if (strlen($digits) === 5 && $prefix !== null) {
            return $prefix . $digits;
        }

        return null;
    }

    /** CEP no formato do app (ViaCEP): #####-###. Fora de 8 dígitos, mantém como veio. */
    public static function formatCep(?string $value): ?string
    {
        $digits = self::digits($value);

        if (strlen($digits) === 8) {
            return substr($digits, 0, 5) . '-' . substr($digits, 5);
        }

        return self::trimOrNull($value);
    }

    /** Trunca defensivamente para caber em varchar(N) do destino. */
    public static function limit(?string $value, int $max): ?string
    {
        $value = self::trimOrNull($value);

        return $value === null ? null : mb_substr($value, 0, $max);
    }
}
