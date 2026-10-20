@use('App\Services\Relatorios\CadastroRelatorio')
@php
    // Mais colunas, letra menor: o A4 deitado não estica.
    $fonte = match (true) {
        count($colunas) <= 10 => '7.5px',
        count($colunas) <= 14 => '6.5px',
        default => '5.5px',
    };
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>{{ $titulo }}</title>
    <style>
        @page { margin: 104px 24px 48px 24px; }
        * { font-family: DejaVu Sans, sans-serif; }
        body { margin: 0; color: #333; font-size: 8px; }

        header { position: fixed; top: -92px; left: 0; right: 0; height: 80px;
                 border-bottom: 3px solid #DD8F2F; }
        header table { width: 100%; border-collapse: collapse; }
        header td { border: 0; padding: 0; vertical-align: middle; background: none; }
        header img { height: 58px; }
        header h1 { margin: 0; font-size: 15px; color: #04355A; text-align: right; }
        header .filtros { margin-top: 4px; font-size: 8px; color: #595959; text-align: right; }
        header .meta { margin-top: 2px; font-size: 7px; color: #808080; text-align: right; }

        footer { position: fixed; bottom: -32px; left: 0; right: 0; height: 20px;
                 font-size: 7px; color: #808080; border-top: 1px solid #ddd; padding-top: 4px; }

        table.dados { width: 100%; border-collapse: collapse; }
        table.dados thead { display: table-header-group; }
        table.dados th { background: #04355A; color: #fff; text-align: left; padding: 5px 4px; font-size: {{ $fonte }}; }
        table.dados td { padding: 3px 4px; border-bottom: 1px solid #e5e9ee; font-size: {{ $fonte }}; vertical-align: top; }
        table.dados tr:nth-child(even) td { background: #F7F9FB; }
        table.dados .centro { text-align: center; }
        table.dados tfoot td { font-weight: bold; color: #04355A; background: #E6EDF3; border-top: 2px solid #04355A; padding: 5px 4px; }

        .vazio { text-align: center; color: #808080; padding: 30px; font-size: 10px; }
    </style>
</head>
<body>
    <header>
        <table>
            <tr>
                <td style="width: 180px;">
                    @if($logo)
                        <img src="{{ $logo }}" alt="ABAC">
                    @endif
                </td>
                <td>
                    <h1>{{ mb_strtoupper($titulo) }}</h1>
                    <div class="filtros">{{ $subtitulo }}</div>
                    <div class="meta">Gerado em {{ $geradoEm }} por {{ $geradoPor }}</div>
                </td>
            </tr>
        </table>
    </header>

    <footer>ABAC — Associação Brasileira de Administradoras de Consórcios</footer>

    <main>
        @if($linhas->isEmpty())
            <p class="vazio">Nenhum cadastro encontrado com os filtros selecionados.</p>
        @else
            <table class="dados">
                <thead>
                    <tr>
                        @foreach($colunas as $coluna)
                            <th class="{{ !empty($coluna['centro']) ? 'centro' : '' }}">{{ $coluna['titulo'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($linhas as $linha)
                        <tr>
                            @foreach($colunas as $chave => $coluna)
                                <td class="{{ !empty($coluna['centro']) ? 'centro' : '' }}">{{ CadastroRelatorio::texto($linha[$chave] ?? null) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="{{ count($colunas) }}">TOTAL DE CADASTROS: {{ number_format($linhas->count(), 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        @endif
    </main>
</body>
</html>
