<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

function loadEnvironment(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        return;
    }
    $values = parse_ini_file($path, false, INI_SCANNER_RAW);
    if ($values === false) {
        throw new RuntimeException('No se pudo leer la configuración de entorno.');
    }
    foreach ($values as $key => $value) {
        if (getenv((string) $key) === false) {
            putenv($key . '=' . $value);
            $_ENV[(string) $key] = (string) $value;
        }
    }
}

loadEnvironment(BASE_PATH . '/.env');

if ((getenv('APP_ENV') ?: 'development') === 'production' && strlen((string) getenv('APP_KEY')) < 32) {
    throw new RuntimeException('APP_KEY debe tener al menos 32 caracteres en producción.');
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'Sedema\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = BASE_PATH . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'America/Argentina/Buenos_Aires');
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/storage/logs/app.log');

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_name('SEDEMA_SESSION');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; img-src 'self' data:; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");
if ($isHttps) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

set_exception_handler(static function (Throwable $error): void {
    $incident = bin2hex(random_bytes(6));
    error_log(sprintf(
        '[%s] %s in %s:%d',
        $incident,
        $error->getMessage(),
        $error->getFile(),
        $error->getLine()
    ));
    http_response_code(500);
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=UTF-8');
    }
    echo '<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>Error | SEDEMA</title><link rel="stylesheet" href="assets/css/styles.css">';
    echo '<main class="error-page"><section class="error-card"><span class="status-mark">SEDEMA S.R.L.</span><h1>No pudimos completar la operación</h1>';
    echo '<p>Intentá nuevamente. Si el problema continúa, informá el código <strong>' . htmlspecialchars($incident, ENT_QUOTES, 'UTF-8') . '</strong> al administrador.</p>';
    echo '<a class="button button-primary" href="index.php">Volver al inicio</a></section></main></html>';
});

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $location): never
{
    header('Location: ' . $location, true, 303);
    exit;
}
