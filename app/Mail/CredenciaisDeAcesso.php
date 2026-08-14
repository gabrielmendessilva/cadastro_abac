<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * E-mail de boas-vindas com os dados do primeiro acesso.
 *
 * A senha temporária viaja em texto no corpo do e-mail — é o único momento em
 * que ela existe fora do hash, e por isso o sistema obriga a troca no primeiro
 * login (User::$must_change_password).
 */
class CredenciaisDeAcesso extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $senhaTemporaria,
        public string $loginUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            // A marca é uma sigla: sai sempre em caixa alta, independente de
            // como o APP_NAME estiver escrito no .env.
            subject: 'Seus dados de acesso — '.Str::upper(config('app.name')),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.credenciais-de-acesso',
        );
    }
}
