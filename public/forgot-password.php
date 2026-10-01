<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';

use Sedema\Csrf;
use Sedema\Database;
use Sedema\PasswordResetService;

$sent = false;
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
        $error = 'La sesión del formulario venció. Actualizá la página.';
    } else {
        (new PasswordResetService(Database::connection()))->request((string) ($_POST['identifier'] ?? ''));
        $sent = true;
        Csrf::rotate();
    }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="description" content="Recuperación de acceso al sistema SEDEMA."><title>Recuperar acceso | SEDEMA</title><link rel="stylesheet" href="assets/css/styles.css"></head>
<body class="simple-page"><main class="simple-card">
    <a class="back-link" href="index.php">← Volver al inicio</a>
    <div class="logo-crop simple-logo"><img src="assets/img/sedema-logo.png" alt="SEDEMA S.R.L."></div>
    <h1>Recuperar acceso</h1>
    <?php if ($sent): ?>
        <div class="alert alert-success" role="status"><span aria-hidden="true">✓</span><p>Si existe una cuenta habilitada con esos datos, recibirás un enlace válido por 30 minutos.</p></div>
        <p class="muted">Revisá también la carpeta de correo no deseado. Por seguridad, no informamos si la cuenta existe.</p>
    <?php else: ?>
        <p class="muted">Ingresá tu usuario o correo corporativo. Te enviaremos un enlace de recuperación.</p>
        <?php if ($error): ?><div class="alert alert-error" role="alert"><span>!</span><p><?= e($error) ?></p></div><?php endif; ?>
        <form method="post" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>">
            <div class="field"><label for="identifier">Usuario o correo</label><input id="identifier" name="identifier" type="text" maxlength="150" autocomplete="username" required></div>
            <button class="button button-primary button-full" type="submit">Enviar enlace</button>
        </form>
    <?php endif; ?>
</main></body></html>
