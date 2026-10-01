<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';

use Sedema\Access\AccessException;
use Sedema\Access\CredentialMailer;
use Sedema\Access\UserAccessRepository;
use Sedema\Access\UserAccessService;
use Sedema\AuthService;
use Sedema\Csrf;
use Sedema\Database;

$db = Database::connection();
$auth = new AuthService($db);
$user = $auth->authenticatedUser();
if (!$user) {
    redirect('index.php');
}
if (empty($user['requires_first_access'])) {
    redirect('dashboard.php');
}
$repository = new UserAccessRepository($db);
$account = $repository->account((int) $user['id']);
if (!$account) {
    $auth->logout();
    redirect('index.php');
}
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
        $error = 'La sesión del formulario venció. Actualizá la página e intentá nuevamente.';
    } else {
        try {
            $service = new UserAccessService($repository, new CredentialMailer());
            $service->completeFirstAccess($_POST, (int) $user['id'], $_FILES['profile_photo'] ?? null);
            $auth->logout();
            redirect('index.php?setup=done');
        } catch (AccessException $exception) {
            $error = $exception->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Configurar acceso | SEDEMA</title><link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="first-access-page">
<main class="first-access-shell">
    <section class="first-access-card">
        <header><p class="eyebrow">Primer ingreso</p><h1>Configurá tu cuenta</h1><p>Hola <?= e((string) $account['nombre']) ?>. Para continuar, reemplazá la contraseña temporal y completá tu perfil.</p></header>
        <?php if ($error): ?><div class="alert alert-error" role="alert"><span>!</span><p><?= e($error) ?></p></div><?php endif; ?>
        <form class="inventory-form" method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>">
            <section class="form-section">
                <div class="form-section-heading"><span>01</span><div><h3>Perfil</h3><p>Elegí el usuario definitivo con el que también podrás iniciar sesión.</p></div></div>
                <div class="form-grid">
                    <div class="field"><label>Correo validado</label><input value="<?= e((string) $account['email']) ?>" disabled></div>
                    <div class="field"><label>DNI</label><input value="<?= e((string) $account['dni']) ?>" disabled></div>
                    <div class="field"><label for="username">Nombre de usuario *</label><input id="username" name="username" minlength="4" maxlength="30" pattern="[a-zA-Z0-9._-]+" autocomplete="username" required></div>
                    <div class="field"><label for="profile_photo">Foto de perfil</label><input id="profile_photo" name="profile_photo" type="file" accept="image/jpeg,image/png,image/webp"><span class="field-hint">Opcional. JPG, PNG o WebP, máximo 2 MB.</span></div>
                </div>
            </section>
            <section class="form-section">
                <div class="form-section-heading"><span>02</span><div><h3>Nueva contraseña</h3><p>Debe ser diferente de la temporal y contener al menos 10 caracteres, letras y números.</p></div></div>
                <div class="form-grid">
                    <div class="field">
                        <label for="password">Nueva contraseña *</label>
                        <div class="password-input">
                            <input id="password" name="password" type="password" minlength="10" maxlength="255" autocomplete="new-password" required>
                            <button type="button" class="toggle-password" aria-label="Mostrar contraseña" aria-pressed="false" data-password-toggle>
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.7"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="field">
                        <label for="password_confirmation">Repetir contraseña *</label>
                        <div class="password-input">
                            <input id="password_confirmation" name="password_confirmation" type="password" minlength="10" maxlength="255" autocomplete="new-password" required>
                            <button type="button" class="toggle-password" aria-label="Mostrar contraseña" aria-pressed="false" data-password-toggle>
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </section>
            <button class="button button-primary button-full" type="submit">Guardar configuración y continuar</button>
        </form>
    </section>
</main>
<script src="assets/js/login.js" defer></script>
</body>
</html>
