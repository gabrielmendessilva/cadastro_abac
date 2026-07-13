<?php

namespace App\Http\Controllers\Omie;

use App\Http\Controllers\Controller;
use App\Http\Requests\Omie\CreateContaReceberRequest;
use App\Http\Requests\Omie\PayContaReceberRequest;
use App\Http\Requests\Omie\UpdateContaReceberRequest;
use App\Services\Omie\ContasReceberService;
use App\Services\Omie\Exceptions\OmieException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contas a Receber — Lançamentos na Omie.
 *
 * Envelope de resposta segue o padrão do CepController: {ok: bool, data|message}.
 * OmieException vira HTTP 502 com contexto do erro (nunca vaza file/line, como
 * o controller da origem fazia).
 */
class ContasReceberController extends Controller
{
    public function __construct(
        private readonly ContasReceberService $service,
    ) {}

    public function store(CreateContaReceberRequest $request): JsonResponse
    {
        try {
            $data = $this->service->create($request->validated());
        } catch (OmieException $e) {
            return $this->omieFailure($e);
        }

        return response()->json([
            'ok'   => true,
            'data' => $data,
        ], 201);
    }

    public function update(UpdateContaReceberRequest $request): JsonResponse
    {
        try {
            $data = $this->service->update($request->validated());
        } catch (OmieException $e) {
            return $this->omieFailure($e);
        }

        return response()->json([
            'ok'   => true,
            'data' => $data,
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        $data = $request->validate([
            'omie_id' => ['required', 'integer'],
        ]);

        try {
            $result = $this->service->findById((int) $data['omie_id']);
        } catch (OmieException $e) {
            return $this->omieFailure($e);
        }

        return response()->json([
            'ok'   => true,
            'data' => $result,
        ]);
    }

    public function pay(PayContaReceberRequest $request): JsonResponse
    {
        try {
            $data = $this->service->pay($request->validated());
        } catch (OmieException $e) {
            return $this->omieFailure($e);
        }

        return response()->json([
            'ok'   => true,
            'data' => $data,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'omie_id' => ['required', 'integer'],
        ]);

        try {
            $result = $this->service->cancel((int) $data['omie_id']);
        } catch (OmieException $e) {
            return $this->omieFailure($e);
        }

        return response()->json([
            'ok'   => true,
            'data' => $result,
        ]);
    }

    private function omieFailure(OmieException $e): JsonResponse
    {
        return response()->json([
            'ok'      => false,
            'message' => $e->getMessage(),
            'call'    => $e->omieCall,
            'omie'    => [
                'status'      => $e->httpStatus,
                'faultcode'   => $e->payload['faultcode']   ?? null,
                'faultstring' => $e->payload['faultstring'] ?? null,
            ],
        ], 502);
    }
}
