<?php
declare(strict_types=1);

namespace Sedema\Clients;

use Sedema\Csrf;

final class ClientPage
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
            'ADMINISTRADOR' => 'Administrador', 'VENDEDOR' => 'Ventas', 'PROVEEDOR' => 'Proveedores',
            'DEPOSITO' => 'Depósito', 'LOGISTICA' => 'Logística', 'CAJA' => 'Caja',
            'ALMACEN' => 'Almacén', 'FINANZAS' => 'Finanzas', 'SISTEMAS' => 'Sistemas',
        ];
        $role = $roles[(string) ($user['role'] ?? '')] ?? (string) ($user['role'] ?? 'Usuario');
        $flash = self::pullFlash();
        ?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Módulo de clientes del sistema SEDEMA.">
    <title><?= e($title) ?> | Clientes SEDEMA</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body class="dashboard-page inventory-page clients-page">
<div class="dashboard-shell">
    <aside class="dashboard-sidebar" id="dashboard-sidebar">
        <div class="sidebar-brand">
            <div class="logo-crop dashboard-logo"><img src="../assets/img/sedema-logo.png" alt="SEDEMA S.R.L."></div>
            <div><strong>SEDEMA</strong><span>Clientes</span></div>
        </div>
        <nav class="sidebar-nav" aria-label="Navegación de clientes">
            <p class="nav-label">Sistema</p>
            <a class="nav-item" href="../dashboard.php"><?= self::icon('home') ?><span>Panel principal</span></a>
            <p class="nav-label">Clientes</p>
            <?= self::navItem('index.php', 'clients', 'Padrón de clientes', $active) ?>
            <a class="nav-item" href="../sales/index.php"><?= self::icon('orders') ?><span>Pedidos de venta</span></a>
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
            <button class="menu-button" type="button" data-sidebar-open aria-controls="dashboard-sidebar" aria-expanded="false"><?= self::icon('menu') ?><span class="sr-only">Abrir menú</span></button>
            <div><p class="topbar-kicker">Módulo de clientes</p><h1><?= e($title) ?></h1></div>
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
</body>
</html>
        <?php
    }

    public static function flash(string $type, string $message): void
    {
        $_SESSION['client_flash'] = ['type' => $type, 'message' => $message];
    }

    /** @return array{type:string,message:string}|null */
    private static function pullFlash(): ?array
    {
        $flash = $_SESSION['client_flash'] ?? null;
        unset($_SESSION['client_flash']);
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
            'clients' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
            'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
            'orders' => '<path d="M3 5h2l2 10h10l2-7H7"/><circle cx="9" cy="20" r="1"/><circle cx="17" cy="20" r="1"/>',
            'plus' => '<path d="M12 5v14M5 12h14"/>',
            'active' => '<path d="M20 6 9 17l-5-5"/>',
            'company' => '<path d="M4 21V5h10v16M14 9h6v12M7 9h4M7 13h4M7 17h4M17 13h1M17 17h1"/>',
            'badge' => '<circle cx="12" cy="8" r="4"/><path d="M5 21a7 7 0 0 1 14 0"/>',
        ];
        return '<svg viewBox="0 0 24 24" aria-hidden="true">' . ($icons[$name] ?? $icons['clients']) . '</svg>';
    }
}
