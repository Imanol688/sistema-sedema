<?php
declare(strict_types=1);

namespace Sedema;

use RuntimeException;

final class ResetMailer
{
    public function send(string $recipient, string $resetUrl): void
    {
        $subject = 'Recuperación de acceso a SEDEMA';
        $message = "Se solicitó restablecer tu contraseña.\n\nAbrí este enlace dentro de los próximos 30 minutos:\n{$resetUrl}\n\nSi no realizaste la solicitud, ignorá este mensaje.";
        $transport = Config::get('MAIL_TRANSPORT', 'log');

        if ($transport === 'log' && Config::get('APP_ENV', 'development') !== 'production') {
            file_put_contents(BASE_PATH . '/storage/logs/mail.log', '[' . date(DATE_ATOM) . "] {$recipient}\n{$message}\n\n", FILE_APPEND | LOCK_EX);
            return;
        }

        if ($transport !== 'mail') {
            throw new RuntimeException('El servicio de correo no está configurado.');
        }

        $headers = [
            'From: ' . Config::get('MAIL_FROM', 'no-responder@sedema.local'),
            'Content-Type: text/plain; charset=UTF-8',
        ];
        if (!mail($recipient, $subject, $message, implode("\r\n", $headers))) {
            throw new RuntimeException('No fue posible enviar el correo de recuperación.');
        }
    }
}

