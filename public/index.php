<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';

use Sedema\AuthService;
use Sedema\Csrf;
use Sedema\Database;

$error = null;
$username = '';
$auth = new AuthService(Database::connection());
if ($auth->authenticatedUser()) {
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
        $error = 'La sesión del formulario venció. Actualizá la página e intentá nuevamente.';
    } else {
        $result = $auth->login(
            $username,
            (string) ($_POST['password'] ?? ''),
            (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
            (string) ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown')
        );
        if ($result['ok']) {
            redirect('dashboard.php');
        }
        $error = $result['message'];
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Acceso interno al sistema de gestión SEDEMA.">
    <title>Iniciar sesión | SEDEMA</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
<main class="auth-shell">
    <section class="brand-panel" aria-label="SEDEMA Gestión">
        <div class="brand-top">
            <div class="company-name" aria-label="SEDEMA Sociedad de Responsabilidad Limitada">SEDEMA <span>S.R.L.</span></div>
            <span class="system-label">Sistema interno</span>
        </div>
        <div class="brand-copy">
            <p class="eyebrow">Gestión para construir mejor</p>
            <h1>Materiales, ventas y logística en un solo lugar.</h1>
            <p>Acceso exclusivo para el personal autorizado de SEDEMA.</p>
        </div>
        <div class="brand-footer"><span aria-hidden="true"></span> Conexión protegida</div>
    </section>

    <section class="form-panel">
        <div class="form-wrap">
            <header class="form-header">
                <p class="mobile-brand">SEDEMA <span>S.R.L.</span></p>
                <h2>Iniciar sesión</h2>
                <p>Ingresá con tu usuario o correo corporativo.</p>
            </header>

            <?php if ($error): ?>
                <div class="alert alert-error" role="alert">
                    <span aria-hidden="true">!</span><p><?= e($error) ?></p>
                </div>
            <?php endif; ?>

            <form method="post" class="auth-form" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>">
                <div class="field">
                    <label for="username">Usuario o correo</label>
                    <input id="username" name="username" type="text" value="<?= e($username) ?>" maxlength="150" autocomplete="username" inputmode="email" required autofocus>
                    <span class="field-error" id="username-error"></span>
                </div>
                <div class="field">
                    <div class="label-row"><label for="password">Contraseña</label><a href="forgot-password.php">¿Olvidaste tu contraseña?</a></div>
                    <div class="password-input">
                        <input id="password" name="password" type="password" maxlength="255" autocomplete="current-password" required>
                        <button type="button" class="toggle-password" aria-label="Mostrar contraseña" aria-pressed="false" data-password-toggle>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.7"/></svg>
                        </button>
                    </div>
                    <span class="field-error" id="password-error"></span>
                </div>
                <button class="button button-primary button-full" type="submit">Ingresar al sistema <span aria-hidden="true">→</span></button>
            </form>

            <p class="support-note">¿Problemas para ingresar? Contactá al administrador del sistema.</p>
        </div>
    </section>
</main>
<script src="assets/js/login.js" defer></script>
</body>
</html>
