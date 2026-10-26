<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Controllers\RelatorioCadastroController;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Cookie que avisa a tela de relatório que o arquivo saiu, para fechar o
 * "Gerando relatório...": download não navega, então a página não tem outro
 * jeito de saber.
 */
trait AvisaDownloadPronto
{
    /**
     * Devolve o token que a tela mandou: ela compara com o que gerou para não
     * confundir com o aviso de um download anterior.
     *
     * @template T of SymfonyResponse
     *
     * @param  T  $response
     * @return T
     */
    private function avisaPronto(Request $request, SymfonyResponse $response): SymfonyResponse
    {
        $token = (string) $request->query('download_token', '');

        if (preg_match('/^\d{1,20}$/', $token)) {
            $response->headers->setCookie(
                Cookie::create(RelatorioCadastroController::COOKIE_PRONTO, $token, now()->addMinutes(5), '/', null, null, false, false, Cookie::SAMESITE_LAX)
            );
        }

        return $response;
    }
}
