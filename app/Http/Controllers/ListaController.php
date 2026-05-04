<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientComite;
use App\Models\Lista\Departamento;
use App\Models\Lista\Estado;
use App\Models\Lista\Funcao;
use App\Models\Lista\Regional;
use App\Models\Lista\Segmento;
use App\Models\Lista\StatusOption;
use App\Models\Tag;
use Illuminate\Http\Request;

class ListaController extends Controller
{
    private const RECURSOS = [
        'regionais' => ['model' => Regional::class, 'label' => 'Regionais', 'campos' => ['nome', 'descricao']],
        'segmentos' => ['model' => Segmento::class, 'label' => 'Segmentos', 'campos' => ['nome', 'descricao']],
        'departamentos' => ['model' => Departamento::class, 'label' => 'Departamentos', 'campos' => ['nome', 'descricao']],
        'funcoes' => ['model' => Funcao::class, 'label' => 'Funções', 'campos' => ['nome', 'descricao']],
        'status' => ['model' => StatusOption::class, 'label' => 'Status', 'campos' => ['nome', 'descricao']],
        'estados' => ['model' => Estado::class, 'label' => 'Estados', 'campos' => ['uf', 'nome']],
        'tags' => ['model' => Tag::class, 'label' => 'Tags', 'campos' => ['nome', 'cor']],
    ];

    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('clients.view'), 403);

        $aba = $request->get('aba', 'mandatos');

        $abasPainel = ['mandatos', 'comites'];

        if (in_array($aba, $abasPainel, true)) {
            return view('listas.index', [
                'aba' => $aba,
                'recursos' => self::RECURSOS,
                'mandatos' => $aba === 'mandatos' ? $this->mandatosProximos() : collect(),
                'integrantesComites' => $aba === 'comites' ? $this->integrantesComites() : collect(),
                'itensRecurso' => collect(),
                'recursoAtual' => null,
            ]);
        }

        if (!isset(self::RECURSOS[$aba])) {
            abort(404);
        }

        $recurso = self::RECURSOS[$aba];
        $itens = $recurso['model']::orderBy(in_array('uf', $recurso['campos'], true) ? 'uf' : 'nome')->paginate(20)->withQueryString();

        return view('listas.index', [
            'aba' => $aba,
            'recursos' => self::RECURSOS,
            'mandatos' => collect(),
            'integrantesComites' => collect(),
            'itensRecurso' => $itens,
            'recursoAtual' => $recurso,
        ]);
    }

    public function store(Request $request, string $aba)
    {
        abort_unless(auth()->user()->can('clients.edit'), 403);
        abort_unless(isset(self::RECURSOS[$aba]), 404);

        $recurso = self::RECURSOS[$aba];
        $rules = [];
        foreach ($recurso['campos'] as $campo) {
            $rules[$campo] = ['nullable', 'string', 'max:255'];
        }
        $rules[$recurso['campos'][0]][] = 'required';

        $data = $request->validate($rules);
        $recurso['model']::create($data);

        return redirect()->route('listas.index', ['aba' => $aba])->with('success', 'Item adicionado.');
    }

    public function destroy(string $aba, int $id)
    {
        abort_unless(auth()->user()->can('clients.edit'), 403);
        abort_unless(isset(self::RECURSOS[$aba]), 404);

        self::RECURSOS[$aba]['model']::findOrFail($id)->delete();

        return redirect()->route('listas.index', ['aba' => $aba])->with('success', 'Item removido.');
    }

    private function mandatosProximos()
    {
        return Client::query()
            ->whereNotNull('mandato_termino')
            ->where('mandato_alerta', true)
            ->where('mandato_termino', '<=', now()->addMonths(3))
            ->orderBy('mandato_termino')
            ->get(['id', 'nome', 'presidente_atual', 'mandato_inicio', 'mandato_termino']);
    }

    private function integrantesComites()
    {
        return ClientComite::query()
            ->with(['client:id,nome', 'contato:id,nome,email'])
            ->orderBy('comite_nome')
            ->get();
    }
}
