<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';

use Sedema\AuthService;
use Sedema\Csrf;
use Sedema\Database;
use Sedema\Profile\ProfileException;
use Sedema\Profile\ProfileRepository;
use Sedema\Profile\ProfileService;

$db = Database::connection();
$auth = new AuthService($db);
$user = $auth->authenticatedUser();
if (!$user) {
    redirect('index.php');
}
if (!empty($user['requires_first_access'])) {
    redirect('first-access.php');
}

$repository = new ProfileRepository($db);
$profile = $repository->profile((int) $user['id']);
if (!$profile) {
    $auth->logout();
    redirect('index.php');
}

$roleNames = [
    'ADMINISTRADOR'=>'Administrador','VENDEDOR'=>'Ventas','PROVEEDOR'=>'Proveedores','DEPOSITO'=>'Depósito',
    'LOGISTICA'=>'Logística','CAJA'=>'Caja','ALMACEN'=>'Almacén','FINANZAS'=>'Finanzas','SISTEMAS'=>'Sistemas',
];
$error = null;
$success = isset($_GET['saved']) ? 'Tus datos se actualizaron correctamente.' : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
        $error = 'La sesión del formulario venció. Actualizá la página e intentá nuevamente.';
    } else {
        try {
            $service = new ProfileService($repository);
            $profile = $service->update($_POST, (int) $user['id'], $_FILES['profile_photo'] ?? null);
            redirect('profile.php?saved=1');
        } catch (ProfileException $exception) {
            $error = $exception->getMessage();
            $profile = $repository->profile((int) $user['id']) ?? $profile;
        }
    }
}

$displayName = trim((string) $profile['nombre'] . ' ' . (string) $profile['apellido']);
$initial = mb_strtoupper(mb_substr($displayName !== '' ? $displayName : (string) $profile['username'], 0, 1));
$photo = trim((string) ($profile['profilePhoto'] ?? ''));
$photoExists = $photo !== '' && is_file(BASE_PATH . '/public/' . ltrim($photo, '/'));
$roleLabel = $roleNames[(string) $profile['roles']] ?? (string) $profile['roles'];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mi perfil | SEDEMA</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="dashboard-page inventory-page profile-page">
<main class="profile-shell">
    <header class="profile-header">
        <a class="profile-back" href="dashboard.php">← Volver al panel</a>
        <div class="profile-heading">
            <?php if ($photoExists): ?>
                <img class="profile-large-avatar" src="<?= e($photo) ?>" alt="Foto de perfil de <?= e($displayName) ?>">
            <?php else: ?>
                <span class="profile-large-avatar profile-initial" aria-hidden="true"><?= e($initial) ?></span>
            <?php endif; ?>
            <div><p class="eyebrow">Cuenta personal</p><h1>Mi perfil</h1><p><?= e($displayName) ?> · <?= e($roleLabel) ?></p></div>
        </div>
    </header>

    <section class="profile-card">
        <?php if ($error): ?><div class="alert alert-error" role="alert"><span>!</span><p><?= e($error) ?></p></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success" role="status"><span>✓</span><p><?= e($success) ?></p></div><?php endif; ?>

        <form class="inventory-form profile-form" method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>">

            <section class="form-section" id="datos">
                <div class="form-section-heading"><span>01</span><div><h3>Datos personales</h3><p>Podés mantener actualizados tus datos de contacto y acceso.</p></div></div>
                <div class="form-grid">
                    <div class="field"><label for="nombre">Nombre *</label><input id="nombre" name="nombre" maxlength="100" value="<?= e((string) $profile['nombre']) ?>" required></div>
                    <div class="field"><label for="apellido">Apellido *</label><input id="apellido" name="apellido" maxlength="100" value="<?= e((string) $profile['apellido']) ?>" required></div>
                    <div class="field"><label for="username">Nombre de usuario *</label><input id="username" name="username" minlength="4" maxlength="30" pattern="[A-Za-z0-9._-]+" value="<?= e((string) $profile['username']) ?>" required></div>
                    <div class="field"><label for="email">Correo electrónico *</label><input id="email" name="email" type="email" maxlength="150" value="<?= e((string) $profile['email']) ?>" required></div>
                    <div class="field"><label for="telefono">Teléfono</label><input id="telefono" name="telefono" maxlength="50" value="<?= e((string) ($profile['telefono'] ?? '')) ?>"></div>
                    <div class="field"><label>DNI</label><input value="<?= e((string) $profile['dni']) ?>" disabled><span class="field-hint">Solo el Administrador puede gestionar este dato desde Personal.</span></div>
                    <div class="field"><label>Rol de acceso</label><input value="<?= e($roleLabel) ?>" disabled><span class="field-hint">El rol y los permisos se administran desde Usuarios y accesos.</span></div>
                </div>
            </section>

            <section class="form-section" id="foto">
                <div class="form-section-heading"><span>02</span><div><h3>Foto de perfil</h3><p>La imagen se mostrará en tu menú personal en todo el sistema.</p></div></div>
                <div class="profile-photo-row">
                    <?php if ($photoExists): ?><img class="profile-photo-preview" src="<?= e($photo) ?>" alt="Foto actual"><?php else: ?><span class="profile-photo-preview profile-initial"><?= e($initial) ?></span><?php endif; ?>
                    <div class="field profile-photo-field"><label for="profile_photo">Cambiar foto</label><input id="profile_photo" name="profile_photo" type="file" accept="image/jpeg,image/png,image/webp"><span class="field-hint">JPG, PNG o WebP. Máximo 2 MB.</span></div>
                </div>
            </section>

            <section class="form-section" id="seguridad">
                <div class="form-section-heading"><span>03</span><div><h3>Cambiar contraseña</h3><p>Dejá estos campos vacíos si no querés cambiarla.</p></div></div>
                <div class="form-grid">
                    <div class="field"><label for="current_password">Contraseña actual</label><div class="password-input"><input id="current_password" name="current_password" type="password" autocomplete="current-password"><button type="button" class="toggle-password" aria-label="Mostrar contraseña" aria-pressed="false" data-password-toggle><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.7"/></svg></button></div></div>
                    <div class="field"><label for="new_password">Nueva contraseña</label><div class="password-input"><input id="new_password" name="new_password" type="password" minlength="10" autocomplete="new-password"><button type="button" class="toggle-password" aria-label="Mostrar contraseña" aria-pressed="false" data-password-toggle><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.7"/></svg></button></div></div>
                    <div class="field"><label for="new_password_confirmation">Repetir nueva contraseña</label><div class="password-input"><input id="new_password_confirmation" name="new_password_confirmation" type="password" minlength="10" autocomplete="new-password"><button type="button" class="toggle-password" aria-label="Mostrar contraseña" aria-pressed="false" data-password-toggle><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.7"/></svg></button></div></div>
                </div>
            </section>

            <div class="profile-actions"><button class="button button-primary" type="submit">Guardar cambios</button><a class="button button-secondary" href="dashboard.php">Cancelar</a></div>
        </form>
    </section>
</main>
<script src="assets/js/login.js" defer></script>
</body>
</html>
