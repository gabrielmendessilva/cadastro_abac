<?php

namespace App\Http\Controllers;

use App\Services\Aniversariantes\AniversariantesExcel;
use App\Services\Aniversariantes\AniversariantesQuery;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AniversarianteController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('clients.view'), 403);

        $consulta = AniversariantesQuery::fromRequest($request);

        return view('aniversariantes.index', [
            'consulta' => $consulta,
            'linhas' => $consulta->get(),
            'meses' => AniversariantesQuery::MESES,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless(auth()->user()->can('clients.view'), 403);

        $consulta = AniversariantesQuery::fromRequest($request);
        $planilha = new AniversariantesExcel($consulta->get(), $consulta->nomeDoMes());

        // streamDownload em vez de gerar arquivo em disco: a planilha é derivada,
        // não é documento do GED, e some junto com a resposta.
        return response()->streamDownload(
            fn () => $planilha->escreveEm('php://output'),
            $planilha->nomeDoArquivo(),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }
}
