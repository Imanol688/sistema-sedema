<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';

use Sedema\Csrf;
use Sedema\Database;
use Sedema\PasswordResetService;

$selector = (string) ($_GET['selector'] ?? $_POST['selector'] ?? '');
$validator = (string) ($_GET['validator'] ?? $_POST['validator'] ?? '');
$error = null;
$success = false;
$validFormat = (bool) preg_match('/^[a-f0-9]{18}$/', $selector) && (bool) preg_match('/^[a-f0-9]{64}$/', $validator);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string) ($_POST['password'] ?? '');
    $confirmation = (string) ($_POST['password_confirmation'] ?? '');
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
        $error = 'La sesión del formulario venció. Solicitá un nuevo enlace.';
    } elseif (strlen($password) < 12 || strlen($password) > 255) {
        $error = 'La contraseña debe tener entre 12 y 255 caracteres.';
    } elseif (!hash_equals($password, $confirmation)) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (!$validFormat || !(new PasswordResetService(Database::connection()))->reset($selector, $validator, $password)) {
        $error = 'El enlace es inválido, ya fue utilizado o venció.';
    } else {
        $success = true;
        Csrf::rotate();
    }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="description" content="Definición de una nueva contraseña para SEDEMA."><title>Nueva contraseña | SEDEMA</title><link rel="stylesheet" href="assets/css/styles.css"></head>
<body class="simple-page"><main class="simple-card">
    <div class="logo-crop simple-logo"><img src="assets/img/sedema-logo.png" alt="SEDEMA S.R.L."></div>
    <?php if ($success): ?>
        <h1>Contraseña actualizada</h1><div class="alert alert-success"><span>✓</span><p>Ya podés ingresar con tu nueva contraseña.</p></div>
        <a class="button button-primary button-full" href="index.php">Ir al inicio de sesión</a>
    <?php elseif (!$validFormat): ?>
        <h1>Enlace no válido</h1><p class="muted">Solicitá un nuevo enlace para recuperar tu acceso.</p><a class="button button-primary button-full" href="forgot-password.php">Solicitar otro enlace</a>
    <?php else: ?>
        <h1>Crear nueva contraseña</h1><p class="muted">Usá al menos 12 caracteres. Se recomienda una frase fácil de recordar y difícil de adivinar.</p>
        <?php if ($error): ?><div class="alert alert-error" role="alert"><span>!</span><p><?= e($error) ?></p></div><?php endif; ?>
        <form method="post" class="auth-form" data-password-reset-form>
            <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>"><input type="hidden" name="selector" value="<?= e($selector) ?>"><input type="hidden" name="validator" value="<?= e($validator) ?>">
            <div class="field"><label for="password">Nueva contraseña</label><input id="password" name="password" type="password" minlength="12" maxlength="255" autocomplete="new-password" required><span class="password-hint">Mínimo 12 caracteres</span></div>
            <div class="field"><label for="password_confirmation">Repetir contraseña</label><input id="password_confirmation" name="password_confirmation" type="password" minlength="12" maxlength="255" autocomplete="new-password" required></div>
            <button class="button button-primary button-full" type="submit">Guardar contraseña</button>
        </form>
    <?php endif; ?>
</main><script src="assets/js/login.js" defer></script></body></html>
