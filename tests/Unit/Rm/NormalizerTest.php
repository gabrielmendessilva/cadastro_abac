<?php

namespace Tests\Unit\Rm;

use App\Services\Rm\Support\Normalizer;
use PHPUnit\Framework\TestCase;

class NormalizerTest extends TestCase
{
    public function test_digits_remove_tudo_que_nao_e_numero(): void
    {
        $this->assertSame('12345678000195', Normalizer::digits('12.345.678/0001-95'));
        $this->assertSame('12345678000195', Normalizer::digits(' 12345678000195 '));
        $this->assertSame('', Normalizer::digits(null));
        $this->assertSame('', Normalizer::digits('ABC'));
    }

    public function test_is_valid_doc_aceita_cpf_e_cnpj_e_rejeita_lixo(): void
    {
        $this->assertTrue(Normalizer::isValidDoc('12345678000195'));  // CNPJ
        $this->assertTrue(Normalizer::isValidDoc('52998224725'));     // CPF
        $this->assertFalse(Normalizer::isValidDoc(''));
        $this->assertFalse(Normalizer::isValidDoc('123'));
        $this->assertFalse(Normalizer::isValidDoc('123456789012'));   // 12 dígitos
        $this->assertFalse(Normalizer::isValidDoc('00000000000000')); // repetido
        $this->assertFalse(Normalizer::isValidDoc('11111111111'));    // repetido
    }

    public function test_format_cpf_cnpj_aplica_mascara_do_banco(): void
    {
        $this->assertSame('12.345.678/0001-95', Normalizer::formatCpfCnpj('12345678000195'));
        $this->assertSame('529.982.247-25', Normalizer::formatCpfCnpj('52998224725'));
        $this->assertSame('123', Normalizer::formatCpfCnpj('123')); // fora do padrão, devolve como veio
    }

    public function test_split_emails_trata_separadores_invalidos_e_duplicados(): void
    {
        $this->assertSame(
            ['a@x.com', 'b@x.com', 'c@x.com'],
            Normalizer::splitEmails('A@X.com; b@x.com,c@x.com  a@x.com'),
        );
        $this->assertSame([], Normalizer::splitEmails(null));
        $this->assertSame([], Normalizer::splitEmails('sem-arroba ; @invalido'));
        $this->assertSame(['ok@x.com'], Normalizer::splitEmails('<ok@x.com>; naoemail'));
    }

    public function test_to_date_or_null_converte_datetime_sqlsrv_e_trata_1900_como_vazio(): void
    {
        $this->assertSame('2010-05-10', Normalizer::toDateOrNull('2010-05-10 00:00:00.000'));
        $this->assertSame('2010-05-10', Normalizer::toDateOrNull('2010-05-10'));
        $this->assertNull(Normalizer::toDateOrNull('1900-01-01 00:00:00.000')); // convenção RM de vazio
        $this->assertNull(Normalizer::toDateOrNull('1753-01-01 00:00:00.000'));
        $this->assertNull(Normalizer::toDateOrNull(null));
        $this->assertNull(Normalizer::toDateOrNull('texto'));
        $this->assertSame('2020-02-29', Normalizer::toDateOrNull(new \DateTimeImmutable('2020-02-29')));
    }

    public function test_compose_ibge_prefixa_uf_quando_codigo_tem_5_digitos(): void
    {
        $this->assertSame('3550308', Normalizer::composeIbge('SP', '50308'));
        $this->assertSame('3550308', Normalizer::composeIbge('sp', '3550308')); // já completo, passa direto
        $this->assertNull(Normalizer::composeIbge('XX', '50308'));              // UF desconhecida
        $this->assertNull(Normalizer::composeIbge('SP', '123'));                // tamanho inesperado
        $this->assertNull(Normalizer::composeIbge(null, null));
    }

    public function test_format_cep(): void
    {
        $this->assertSame('01310-100', Normalizer::formatCep('01310100'));
        $this->assertSame('01310-100', Normalizer::formatCep('01310-100'));
        $this->assertNull(Normalizer::formatCep('   '));
        $this->assertSame('1310', Normalizer::formatCep('1310')); // fora de 8 dígitos, mantém
    }

    public function test_normalize_name_e_limit(): void
    {
        $this->assertSame('fulano silva', Normalizer::normalizeName('  FULANO   Silva '));
        $this->assertSame('', Normalizer::normalizeName(null));
        $this->assertSame('abc', Normalizer::limit('  abc  ', 10));
        $this->assertSame('ab', Normalizer::limit('abcd', 2));
        $this->assertNull(Normalizer::limit('   ', 10));
    }

    public function test_trim_or_null(): void
    {
        $this->assertNull(Normalizer::trimOrNull('   '));
        $this->assertNull(Normalizer::trimOrNull(null));
        $this->assertSame('x', Normalizer::trimOrNull(' x '));
    }
}
