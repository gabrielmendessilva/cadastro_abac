<?php

namespace App\Services\Relatorios;

use App\Models\Client;
use App\Models\ClientContato;
use Illuminate\Support\Collection;

/**
 * Relatórios v2 > Contatos: todos os contatos (`client_contatos`), cada um
 * com a empresa a que pertence nas primeiras colunas.
 */
final class ContatosRelatorio extends TabelaRelatorio
{
    public const TITULO = 'Relatório de Contatos';

    /** Dados da empresa que abrem cada linha. */
    private const EMPRESA = [
        'empresa_razao_social' => ['titulo' => 'Empresa (razão social)', 'tipo' => 'texto'],
        'empresa_nome_fantasia' => ['titulo' => 'Nome fantasia', 'tipo' => 'texto'],
        'empresa_documento' => ['titulo' => 'CNPJ/CPF da empresa', 'tipo' => 'texto'],
        'empresa_categoria' => ['titulo' => 'Categoria', 'tipo' => 'texto'],
        'empresa_regional' => ['titulo' => 'Regional', 'tipo' => 'texto'],
        'empresa_status' => ['titulo' => 'Empresa ativa', 'centro' => true],
    ];

    private const ROTULOS = [
        'id' => 'ID do contato',
        'nome' => 'Contato',
        'funcao' => 'Função',
        'dt_nascimento' => 'Data de nascimento',
        'aniversario' => 'Aniversário (dd/mm)',
        'email' => 'E-mail',
        'email_2' => 'E-mail 2',
        'telefone' => 'Telefone',
        'telefone_2' => 'Telefone 2',
        'ramal' => 'Ramal',
        'celular' => 'Celular',
        'obs' => 'Observação',
        'departamento' => 'Departamento',
        'outro_departamento' => 'Outro departamento',
        'representante_legal' => 'Representante legal',
        'comite' => 'Comitê',
        'unlock_whatsApp' => 'WhatsApp liberado',
        'created_at' => 'Criado em',
        'updated_at' => 'Atualizado em',
    ];

    /** A empresa já está nas colunas da frente; user_id é só quem digitou. */
    private const IGNORADAS = ['client_id', 'user_id'];

    public function titulo(): string
    {
        return self::TITULO;
    }

    public function subtitulo(): string
    {
        return 'Todos os contatos de todas as empresas (ativas e inativas), com a empresa a que cada um pertence';
    }

    public function nomeDaAba(): string
    {
        return 'Contatos';
    }

    public function rotuloTotal(): string
    {
        return 'Total de contatos';
    }

    public static function total(): int
    {
        return ClientContato::query()->count();
    }

    protected function prefixoDoArquivo(): string
    {
        return 'relatorio-contatos';
    }

    protected function definicoes(): array
    {
        return self::EMPRESA + self::colunasDaTabela(new ClientContato, self::ROTULOS, self::IGNORADAS, 'contato_');
    }

    protected function montaLinhas(): Collection
    {
        $chaves = array_keys(self::colunasDaTabela(new ClientContato, [], self::IGNORADAS));

        // Ordem por empresa e, dentro dela, por contato. Join só para ordenar:
        // cada contato tem uma empresa só, então não duplica linha.
        return ClientContato::query()
            ->select('client_contatos.*')
            ->leftJoin('clients', 'clients.id', '=', 'client_contatos.client_id')
            ->with('client.regional')
            ->orderBy('clients.name')
            ->orderBy('client_contatos.nome')
            ->orderBy('client_contatos.id')
            ->get()
            ->map(function (ClientContato $contato) use ($chaves) {
                /** @var Client|null $client */
                $client = $contato->client;

                $linha = [
                    'empresa_razao_social' => $client ? ($client->name ?: $client->fantasy_name) : null,
                    'empresa_nome_fantasia' => $client?->fantasy_name,
                    'empresa_documento' => $client ? ($client->document ?: $client->cpf) : null,
                    'empresa_categoria' => $client?->categoria,
                    'empresa_regional' => $client?->regional?->nome,
                    'empresa_status' => $client ? ($client->status ? 'Sim' : 'Não') : null,
                ];

                foreach ($chaves as $chave) {
                    $linha['contato_'.$chave] = self::valor($contato, $chave);
                }

                return $linha;
            });
    }
}
