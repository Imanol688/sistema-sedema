<?php
declare(strict_types=1);

namespace Sedema\Personnel;

use Sedema\Authorization;
use Sedema\Csrf;

final class PersonnelPage
{
    /** @param array<string,mixed> $user */
    public static function begin(string $title, string $active, array $user): void
    {
        $displayName = trim((string) ($user['name'] ?? $user['username']));
        $initial = mb_strtoupper(mb_substr($displayName, 0, 1));
        $profilePhoto = trim((string) ($user['profile_photo'] ?? ''));
        $profilePhotoExists = $profilePhoto !== '' && is_file(BASE_PATH . '/public/' . ltrim($profilePhoto, '/'));
        $profilePhotoUrl = $profilePhotoExists ? '../' . ltrim($profilePhoto, '/') : '';
        $roles = [
            'ADMINISTRADOR' => 'Administrador', 'VENDEDOR' => 'Ventas',
            'PROVEEDOR' => 'Proveedores', 'DEPOSITO' => 'Depósito', 'LOGISTICA' => 'Logística',
            'CAJA' => 'Caja', 'ALMACEN' => 'Almacén', 'FINANZAS' => 'Finanzas', 'SISTEMAS' => 'Sistemas',
        ];
        $role = $roles[(string) ($user['role'] ?? '')] ?? (string) ($user['role'] ?? 'Usuario');
        $flash = self::pullFlash();
        ?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Módulo de personal del sistema SEDEMA.">
    <title><?= e($title) ?> | Personal SEDEMA</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body class="dashboard-page inventory-page personnel-page">
<div class="dashboard-shell">
    <aside class="dashboard-sidebar" id="dashboard-sidebar">
        <div class="sidebar-brand">
            <div class="logo-crop dashboard-logo"><img src="../assets/img/sedema-logo.png" alt="SEDEMA S.R.L."></div>
            <div><strong>SEDEMA</strong><span>Personal</span></div>
        </div>
        <nav class="sidebar-nav" aria-label="Navegación de personal">
            <p class="nav-label">Sistema</p>
            <a class="nav-item" href="../dashboard.php"><?= self::icon('home') ?><span>Panel principal</span></a>
            <p class="nav-label">Personal</p>
            <?= self::navItem('index.php', 'employees', 'Legajos', $active) ?>
            <?php if (Authorization::can($user, 'personal.access')): ?>
                <?= self::navItem('access.php', 'access', 'Usuarios y accesos', $active) ?>
            <?php endif; ?>
            <?php if (Authorization::can($user, 'personal.payroll')): ?>
                <?= self::navItem('payrolls.php', 'payroll', 'Haberes', $active) ?>
            <?php endif; ?>
        </nav>
        <div class="sidebar-footer">
            <div class="user-menu" data-user-menu>
                <button class="user-menu-trigger" type="button" data-user-menu-trigger aria-expanded="false">
                    <?php if ($profilePhotoExists): ?>
                        <img class="user-avatar is-photo" src="<?= e($profilePhotoUrl) ?>" alt="Foto de perfil de <?= e($displayName) ?>">
                    <?php else: ?>
                        <span class="user-avatar" aria-hidden="true"><?= e($initial) ?></span>
                    <?php endif; ?>
                    <span class="user-menu-copy"><strong><?= e($displayName) ?></strong><span><?= e($role) ?></span></span>
                    <svg class="user-menu-chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5" fill="none" stroke="currentColor" stroke-width="2"/></svg>
                </button>
                <div class="user-menu-panel" data-user-menu-panel hidden>
                    <a class="user-menu-link" href="../profile.php#datos">Mi perfil / Modificar datos</a>
                    <a class="user-menu-link" href="../profile.php#foto">Cambiar foto</a>
                    <a class="user-menu-link" href="../profile.php#seguridad">Cambiar contraseña</a>
                    <div class="user-menu-divider"></div>
                    <form class="user-menu-form" method="post" action="../logout.php"><input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>"><button class="user-menu-logout" type="submit">Cerrar sesión</button></form>
                </div>
            </div>
        </div>
    </aside>
    <button class="sidebar-backdrop" type="button" data-sidebar-close aria-label="Cerrar menú"></button>
    <main class="dashboard-workspace">
        <header class="dashboard-topbar inventory-topbar">
            <button class="menu-button" type="button" data-sidebar-open aria-controls="dashboard-sidebar" aria-expanded="false">
                <?= self::icon('menu') ?><span class="sr-only">Abrir menú</span>
            </button>
            <div><p class="topbar-kicker">Módulo de personal</p><h1><?= e($title) ?></h1></div>
            <div class="topbar-profile user-menu" data-user-menu>
                <button class="user-menu-trigger" type="button" data-user-menu-trigger aria-expanded="false">
                    <?php if ($profilePhotoExists): ?>
                        <img class="user-avatar is-photo" src="<?= e($profilePhotoUrl) ?>" alt="Foto de perfil de <?= e($displayName) ?>">
                    <?php else: ?>
                        <span class="user-avatar" aria-hidden="true"><?= e($initial) ?></span>
                    <?php endif; ?>
                    <span class="user-menu-copy"><strong><?= e($displayName) ?></strong><span><?= e($role) ?></span></span>
                    <svg class="user-menu-chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5" fill="none" stroke="currentColor" stroke-width="2"/></svg>
                </button>
                <div class="user-menu-panel" data-user-menu-panel hidden>
                    <a class="user-menu-link" href="../profile.php#datos">Mi perfil / Modificar datos</a>
                    <a class="user-menu-link" href="../profile.php#foto">Cambiar foto</a>
                    <a class="user-menu-link" href="../profile.php#seguridad">Cambiar contraseña</a>
                    <div class="user-menu-divider"></div>
                    <form class="user-menu-form" method="post" action="../logout.php"><input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>"><button class="user-menu-logout" type="submit">Cerrar sesión</button></form>
                </div>
            </div>
        </header>
        <div class="dashboard-content inventory-content">
            <?php if ($flash): ?>
                <div class="alert <?= $flash['type'] === 'success' ? 'alert-success' : 'alert-error' ?> inventory-alert" role="<?= $flash['type'] === 'success' ? 'status' : 'alert' ?>">
                    <span aria-hidden="true"><?= $flash['type'] === 'success' ? '✓' : '!' ?></span><p><?= e($flash['message']) ?></p>
                </div>
            <?php endif; ?>
        <?php
    }

    public static function end(): void
    {
        ?>
        </div>
    </main>
</div>
<script src="../assets/js/dashboard.js" defer></script>
<script src="../assets/js/inventory.js" defer></script>
<script src="../assets/js/personnel.js" defer></script>
</body>
</html>
        <?php
    }

    public static function flash(string $type, string $message): void
    {
        $_SESSION['personnel_flash'] = ['type' => $type, 'message' => $message];
    }

    /** @return array{type:string,message:string}|null */
    private static function pullFlash(): ?array
    {
        $flash = $_SESSION['personnel_flash'] ?? null;
        unset($_SESSION['personnel_flash']);
        return is_array($flash) && isset($flash['type'], $flash['message']) ? $flash : null;
    }

    private static function navItem(string $href, string $key, string $label, string $active): string
    {
        $class = $key === $active ? 'nav-item is-active' : 'nav-item';
        $current = $key === $active ? ' aria-current="page"' : '';
        return '<a class="' . $class . '" href="' . $href . '"' . $current . '>'
            . self::icon($key) . '<span>' . e($label) . '</span></a>';
    }

    public static function icon(string $name): string
    {
        $icons = [
            'home' => '<path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 10v10h13V10"/><path d="M9.5 20v-6h5v6"/>',
            'employees' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
            'payroll' => '<rect x="4" y="3" width="16" height="18" rx="1"/><path d="M8 8h8M8 12h8M8 16h4"/>',
            'access' => '<path d="M12 3 4.5 6v5c0 4.7 3.2 8.1 7.5 10 4.3-1.9 7.5-5.3 7.5-10V6L12 3Z"/><path d="M9 12h6M12 9v6"/>',
            'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
            'plus' => '<path d="M12 5v14M5 12h14"/>',
            'badge' => '<circle cx="12" cy="8" r="4"/><path d="M5 21a7 7 0 0 1 14 0"/>',
            'active' => '<path d="M20 6 9 17l-5-5"/>',
            'inactive' => '<circle cx="12" cy="12" r="9"/><path d="m7 7 10 10"/>',
            'money' => '<circle cx="12" cy="12" r="9"/><path d="M16 8h-6a2 2 0 0 0 0 4h4a2 2 0 0 1 0 4H8M12 6v12"/>',
        ];
        return '<svg viewBox="0 0 24 24" aria-hidden="true">' . ($icons[$name] ?? $icons['employees']) . '</svg>';
    }
}
