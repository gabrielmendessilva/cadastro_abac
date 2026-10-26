<?php

namespace App\Services\Relatorios;

use App\Models\Client;
use App\Models\ClientEndereco;
use Illuminate\Support\Collection;

/**
 * Relatórios v2 > Clientes: a tabela `clients` inteira, todos os cadastros
 * (ativos e inativos) e todos os campos, mais o endereço preferido — o
 * endereço mora em `client_enderecos` desde que saiu da tabela de clientes.
 */
final class ClientesRelatorio extends TabelaRelatorio
{
    public const TITULO = 'Relatório de Clientes';

    /** Campos que já vêm com nome em inglês ou abreviado do legado. */
    private const ROTULOS = [
        'id' => 'ID',
        'name' => 'Razão social',
        'fantasy_name' => 'Nome fantasia',
        'document' => 'CNPJ',
        'cpf' => 'CPF',
        'rg' => 'RG',
        'email' => 'E-mail',
        'phone' => 'Telefone',
        'mobile' => 'Celular',
        'site' => 'Site',
        'notes' => 'Observações',
        'status' => 'Ativo',
        'regional_id' => 'Regional',
        'cod_omie' => 'Código Omie',
        'inscri_estadual' => 'Inscrição estadual',
        'inscri_municipal' => 'Inscrição municipal',
        'dt_nascimento' => 'Data de nascimento',
        'dt_filiacao_abac' => 'Filiação ABAC',
        'dt_desfiliacao_abac' => 'Desfiliação ABAC',
        'dt_filiacao_sinac' => 'Filiação SINAC',
        'dt_desfiliacao_sinac' => 'Desfiliação SINAC',
        'num_filiacao_abac' => 'Nº filiação ABAC',
        'num_filiacao_sinac' => 'Nº filiação SINAC',
        'motivo_desfiliacao_abac' => 'Motivo desfiliação ABAC',
        'motivo_desfiliacao_sinac' => 'Motivo desfiliação SINAC',
        'associado_abac' => 'Associado ABAC',
        'associado_sinac' => 'Associado SINAC',
        'obs_abac' => 'Obs. ABAC',
        'obs_sinac' => 'Obs. SINAC',
        'obs_sinac_juridico' => 'Obs. SINAC jurídico',
        'obs_juridico' => 'Obs. jurídico',
        'obs_cadastro' => 'Obs. cadastro',
        'ocorrencia_abac' => 'Ocorrência ABAC',
        'situacao_abac' => 'Situação ABAC',
        'dt_abertura_empresa' => 'Abertura da empresa',
        'dt_aniversario_empresa' => 'Aniversário da empresa',
        'dt_autorizacao_consorcio' => 'Autorização consórcio',
        'dt_pedido_consorcio' => 'Pedido consórcio',
        'dt_bacen' => 'Data BACEN',
        'email_conac' => 'E-mail CONAC',
        'email_ouvidoria' => 'E-mail ouvidoria',
        'email_presidente' => 'E-mail presidente',
        'email_secretaria' => 'E-mail secretaria',
        'emails_boletos' => 'E-mails boletos',
        'telefone_ouvidoria' => 'Telefone ouvidoria',
        'classificao_administradora' => 'Classificação administradora',
        'area_atuacao' => 'Área de atuação',
        'autenticacao_whatsapp' => 'Autenticação WhatsApp',
        'possui_outro_nome' => 'Possui outro nome',
        'possui_contrato_ativo' => 'Possui contrato ativo',
        'mandato_inicio' => 'Início do mandato',
        'mandato_termino' => 'Término do mandato',
        'mandato_alerta' => 'Alerta de mandato',
        'created_by' => 'Criado por',
        'updated_by' => 'Atualizado por',
        'created_at' => 'Criado em',
        'updated_at' => 'Atualizado em',
    ];

    /** Endereço preferido, nas mesmas regras do relatório de cadastro. */
    private const ENDERECO = [
        'tipo' => 'Tipo de endereço',
        'cep' => 'CEP',
        'rua' => 'Logradouro',
        'numero' => 'Número',
        'complemento' => 'Complemento',
        'bairro' => 'Bairro',
        'municipio' => 'Cidade',
        'estado' => 'UF',
        'pais' => 'País',
        'cod_ibge' => 'Código IBGE',
    ];

    public function titulo(): string
    {
        return self::TITULO;
    }

    public function subtitulo(): string
    {
        return 'Base completa: todos os cadastros, ativos e inativos, com todos os campos e o endereço principal';
    }

    public function nomeDaAba(): string
    {
        return 'Clientes';
    }

    public function rotuloTotal(): string
    {
        return 'Total de clientes';
    }

    public static function total(): int
    {
        return Client::query()->count();
    }

    protected function prefixoDoArquivo(): string
    {
        return 'relatorio-clientes';
    }

    protected function definicoes(): array
    {
        $colunas = self::colunasDaTabela(new Client, self::ROTULOS);

        // Razão social primeiro: o título da planilha começa na coluna B, ao
        // lado da logo, e a primeira coluna precisa ser larga.
        $colunas = ['name' => $colunas['name']] + $colunas;

        foreach (self::ENDERECO as $campo => $titulo) {
            $colunas['endereco_'.$campo] = ['titulo' => $titulo, 'tipo' => 'texto'] + ($campo === 'estado' ? ['centro' => true] : []);
        }

        return $colunas;
    }

    protected function montaLinhas(): Collection
    {
        $chaves = array_keys(self::colunasDaTabela(new Client, []));
        $ordem = array_flip(CadastroRelatorio::PRIORIDADE_ENDERECO);

        return Client::query()
            ->with(['regional', 'creator', 'updater', 'enderecos'])
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->map(function (Client $client) use ($chaves, $ordem) {
                $linha = [];
                foreach ($chaves as $chave) {
                    $linha[$chave] = self::valor($client, $chave);
                }

                // Nome no lugar do id: id solto não diz nada na planilha.
                $linha['regional_id'] = $client->regional?->nome;
                $linha['created_by'] = $client->creator?->name;
                $linha['updated_by'] = $client->updater?->name;

                /** @var ClientEndereco|null $endereco */
                $endereco = $client->enderecos->sortBy(fn ($e) => $ordem[$e->tipo] ?? PHP_INT_MAX)->first();
                foreach (array_keys(self::ENDERECO) as $campo) {
                    $linha['endereco_'.$campo] = $endereco?->{$campo};
                }

                return $linha;
            });
    }
}
