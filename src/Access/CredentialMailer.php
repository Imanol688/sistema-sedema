<?php
declare(strict_types=1);

namespace Sedema\Access;

use RuntimeException;
use Sedema\Config;

final class CredentialMailer
{
    public function sendTemporaryCredential(string $recipient, string $employeeName, string $temporaryPassword): void
    {
        $subject = 'Tu acceso temporal a SEDEMA';
        $message = "Hola {$employeeName},\n\n"
            . "Se creó tu cuenta interna de SEDEMA.\n\n"
            . "Usuario de acceso: {$recipient}\n"
            . "Contraseña temporal: {$temporaryPassword}\n\n"
            . "Ingresá en " . Config::appUrl() . " utilizando tu correo o DNI. "
            . "En el primer ingreso el sistema te pedirá cambiar esta contraseña y completar tu perfil.\n\n"
            . "Esta contraseña deja de ser válida una vez que completes el primer acceso.\n"
            . "Si no esperabas esta cuenta, comunicate con el administrador.";

        $transport = strtolower(trim((string) Config::get('MAIL_TRANSPORT', 'log')));
        if ($transport === 'log') {
            if (Config::get('APP_ENV', 'development') === 'production') {
                throw new RuntimeException('MAIL_TRANSPORT=log no está permitido para credenciales en producción.');
            }
            file_put_contents(
                BASE_PATH . '/storage/logs/mail.log',
                '[' . date(DATE_ATOM) . "] {$recipient}\nAsunto: {$subject}\n{$message}\n\n",
                FILE_APPEND | LOCK_EX
            );
            return;
        }

        if ($transport === 'smtp') {
            $this->sendSmtp($recipient, $subject, $message);
            return;
        }

        if ($transport !== 'mail') {
            throw new RuntimeException('MAIL_TRANSPORT debe ser smtp, mail o log.');
        }

        $headers = [
            'From: ' . Config::get('MAIL_FROM', 'no-responder@sedema.local'),
            'Content-Type: text/plain; charset=UTF-8',
        ];
        if (!mail($recipient, $subject, $message, implode("\r\n", $headers))) {
            throw new RuntimeException('No fue posible enviar la credencial temporal por correo.');
        }
    }

    private function sendSmtp(string $recipient, string $subject, string $message): void
    {
        $host = trim((string) Config::get('MAIL_SMTP_HOST', 'smtp.gmail.com'));
        $port = (int) Config::get('MAIL_SMTP_PORT', '587');
        $encryption = strtolower(trim((string) Config::get('MAIL_SMTP_ENCRYPTION', 'tls')));
        $username = trim((string) Config::get('MAIL_SMTP_USERNAME', ''));
        $password = preg_replace('/\s+/', '', (string) Config::get('MAIL_SMTP_PASSWORD', '')) ?? '';
        $from = trim((string) Config::get('MAIL_FROM', $username));

        if ($username === '' || $password === '' || $from === '') {
            throw new RuntimeException('Faltan MAIL_FROM, MAIL_SMTP_USERNAME o MAIL_SMTP_PASSWORD en el archivo .env.');
        }
        if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('MAIL_FROM no contiene una dirección de correo válida.');
        }
        if (!in_array($encryption, ['tls', 'ssl'], true)) {
            throw new RuntimeException('MAIL_SMTP_ENCRYPTION debe ser tls o ssl.');
        }

        $remote = ($encryption === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
        $socket = @stream_socket_client($remote, $errno, $errstr, 20, STREAM_CLIENT_CONNECT);
        if (!is_resource($socket)) {
            throw new RuntimeException('No se pudo conectar con Gmail SMTP: ' . ($errstr !== '' ? $errstr : 'error ' . $errno));
        }
        stream_set_timeout($socket, 20);

        try {
            $this->expect($socket, [220]);
            $this->command($socket, 'EHLO sedema.local', [250]);

            if ($encryption === 'tls') {
                $this->command($socket, 'STARTTLS', [220]);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('No se pudo iniciar el cifrado TLS con Gmail.');
                }
                $this->command($socket, 'EHLO sedema.local', [250]);
            }

            $this->command($socket, 'AUTH LOGIN', [334]);
            $this->command($socket, base64_encode($username), [334]);
            $this->command($socket, base64_encode($password), [235]);
            $this->command($socket, 'MAIL FROM:<' . $from . '>', [250]);
            $this->command($socket, 'RCPT TO:<' . $recipient . '>', [250, 251]);
            $this->command($socket, 'DATA', [354]);

            $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
            $headers = [
                'From: SEDEMA <' . $from . '>',
                'To: <' . $recipient . '>',
                'Subject: ' . $encodedSubject,
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
                'Content-Transfer-Encoding: 8bit',
            ];
            $safeMessage = preg_replace('/(?m)^\./', '..', $message) ?? $message;
            fwrite($socket, implode("\r\n", $headers) . "\r\n\r\n" . str_replace("\n", "\r\n", $safeMessage) . "\r\n.\r\n");
            $this->expect($socket, [250]);
            $this->command($socket, 'QUIT', [221]);
        } finally {
            fclose($socket);
        }
    }

    /** @param resource $socket */
    private function command($socket, string $command, array $codes): void
    {
        fwrite($socket, $command . "\r\n");
        $this->expect($socket, $codes);
    }

    /** @param resource $socket */
    private function expect($socket, array $codes): void
    {
        $response = '';
        do {
            $line = fgets($socket, 515);
            if ($line === false) {
                $meta = stream_get_meta_data($socket);
                throw new RuntimeException(!empty($meta['timed_out'])
                    ? 'Se agotó el tiempo de espera al comunicarse con Gmail SMTP.'
                    : 'El servidor SMTP cerró la conexión inesperadamente.');
            }
            $response .= $line;
        } while (isset($line[3]) && $line[3] === '-');

        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $codes, true)) {
            $detail = trim(preg_replace('/\s+/', ' ', $response) ?? $response);
            throw new RuntimeException('Gmail SMTP rechazó la operación (código ' . $code . '): ' . mb_substr($detail, 0, 220));
        }
    }
}
