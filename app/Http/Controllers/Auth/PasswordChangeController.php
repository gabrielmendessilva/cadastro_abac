<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\TelaInicial;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

/**
 * Troca obrigatória da senha temporária enviada por e-mail no cadastro.
 *
 * Quem já trocou não tem o que fazer aqui e volta para a tela inicial — este
 * fluxo é só do primeiro acesso.
 */
class PasswordChangeController extends Controller
{
    public function create()
    {
        if (! auth()->user()->must_change_password) {
            return redirect()->to(TelaInicial::url());
        }

        return view('auth.trocar-senha');
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if (! $user->must_change_password) {
            return redirect()->to(TelaInicial::url());
        }

        // A senha temporária é pedida de novo de propósito: sem isso, uma sessão
        // deixada aberta na máquina de outra pessoa permitiria tomar a conta.
        $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(8)],
        ], [
            'current_password.current_password' => 'A senha temporária informada está incorreta.',
            'password.different' => 'A nova senha precisa ser diferente da senha temporária.',
        ]);

        $user->update([
            'password' => $request->password, // o cast 'hashed' do model aplica o Hash::make
            'must_change_password' => false,
        ]);

        $request->session()->regenerate();

        return redirect()
            ->to(TelaInicial::url($user))
            ->with('success', 'Senha alterada com sucesso. Bem-vindo!');
    }
}
