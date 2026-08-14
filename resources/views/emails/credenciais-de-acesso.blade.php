{{--
    HTML de e-mail: estilo inline e tabela no lugar de flex/grid — clientes como
    Outlook e Gmail ignoram <style> em <head> e layouts modernos.
--}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seus dados de acesso</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif; color:#0f172a;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9; padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px; background-color:#ffffff; border-radius:16px; overflow:hidden; border:1px solid #e2e8f0;">
                    <tr>
                        <td style="background-color:#0f172a; padding:28px 32px;">
                            <div style="color:#ffffff; font-size:20px; font-weight:bold; letter-spacing:1px;">CADASTRO ABAC</div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px; font-size:16px;">Olá, {{ $user->name }}!</p>

                            <p style="margin:0 0 24px; font-size:15px; line-height:1.6; color:#334155;">
                                Seu acesso ao sistema <strong>{{ config('app.name') }}</strong> foi criado.
                                Use os dados abaixo para entrar pela primeira vez.
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:20px; margin-bottom:24px;">
                                <tr>
                                    <td style="padding:6px 0; font-size:13px; color:#64748b;">Endereço de acesso</td>
                                </tr>
                                <tr>
                                    <td style="padding:0 0 14px; font-size:15px;">
                                        <a href="{{ $loginUrl }}" style="color:#4f46e5; text-decoration:none; word-break:break-all;">{{ $loginUrl }}</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0; font-size:13px; color:#64748b;">Usuário</td>
                                </tr>
                                <tr>
                                    <td style="padding:0 0 14px; font-size:15px; font-weight:600;">{{ $user->email }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0; font-size:13px; color:#64748b;">Senha temporária</td>
                                </tr>
                                <tr>
                                    <td style="padding:0; font-size:20px; font-weight:bold; letter-spacing:2px; font-family:'Courier New',Courier,monospace;">{{ $senhaTemporaria }}</td>
                                </tr>
                            </table>

                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                                <tr>
                                    <td style="background-color:#0f172a; border-radius:12px;">
                                        <a href="{{ $loginUrl }}" style="display:inline-block; padding:14px 28px; color:#ffffff; font-size:15px; font-weight:600; text-decoration:none;">Acessar o sistema</a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 8px; font-size:14px; line-height:1.6; color:#334155;">
                                <strong>No primeiro acesso o sistema vai pedir que você troque essa senha.</strong>
                                A senha temporária só funciona até a troca ser feita.
                            </p>

                            <p style="margin:0; font-size:13px; line-height:1.6; color:#64748b;">
                                Não compartilhe esses dados com ninguém. Se você não esperava este e-mail,
                                avise o responsável pelo sistema.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color:#f8fafc; border-top:1px solid #e2e8f0; padding:18px 32px; font-size:12px; color:#94a3b8;">
                            Mensagem automática — não responda a este e-mail.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
