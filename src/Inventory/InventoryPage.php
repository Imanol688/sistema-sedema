<?php
declare(strict_types=1);

namespace Sedema\Inventory;

use Sedema\Authorization;
use Sedema\Csrf;

final class InventoryPage
{
    /** @param array<string,mixed> $user */
    public static function begin(string $title, string $active, array $user): void
    {
        $displayName = trim((string) ($user['name'] ?? $user['username']));
        $initial = mb_strtoupper(mb_substr($displayName, 0, 1));
        $roles = [
            'ADMINISTRADOR' => 'Administrador', 'VENDEDOR' => 'Ventas',
            'PROVEEDOR' => 'Proveedores', 'DEPOSITO' => 'Depósito', 'LOGISTICA' => 'Logística',
        ];
        $role = $roles[(string) ($user['role'] ?? '')] ?? (string) ($user['role'] ?? 'Usuario');
        $flash = self::pullFlash();
        ?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Módulo de inventario del sistema SEDEMA.">
    <title><?= e($title) ?> | Inventario SEDEMA</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body class="dashboard-page inventory-page">
<div class="dashboard-shell">
    <aside class="dashboard-sidebar" id="dashboard-sidebar">
        <div class="sidebar-brand">
            <div class="logo-crop dashboard-logo"><img src="../assets/img/sedema-logo.png" alt="SEDEMA S.R.L."></div>
            <div><strong>SEDEMA</strong><span>Inventario</span></div>
        </div>
        <nav class="sidebar-nav" aria-label="Navegación de inventario">
            <p class="nav-label">Sistema</p>
            <a class="nav-item" href="../dashboard.php"><?= self::icon('home') ?><span>Panel principal</span></a>
            <p class="nav-label">Inventario</p>
            <?= self::navItem('index.php', 'overview', 'Resumen y productos', $active) ?>
            <?= self::navItem('movements.php', 'history', 'Historial', $active) ?>
            <?php if (Authorization::can($user, 'inventory.adjust')): ?>
                <?= self::navItem('movement.php', 'movement', 'Registrar movimiento', $active) ?>
            <?php endif; ?>
            <?php if (Authorization::can($user, 'inventory.catalogs')): ?>
                <?= self::navItem('catalogs.php', 'catalogs', 'Configuración', $active) ?>
            <?php endif; ?>
        </nav>
        <div class="sidebar-footer">
            <div class="sidebar-user">
                <span class="user-avatar" aria-hidden="true"><?= e($initial) ?></span>
                <div><strong><?= e($displayName) ?></strong><span><?= e($role) ?></span></div>
            </div>
            <form method="post" action="../logout.php">
                <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>">
                <button class="sidebar-logout" type="submit">Cerrar sesión <span aria-hidden="true">→</span></button>
            </form>
        </div>
    </aside>
    <button class="sidebar-backdrop" type="button" data-sidebar-close aria-label="Cerrar menú"></button>
    <main class="dashboard-workspace">
        <header class="dashboard-topbar inventory-topbar">
            <button class="menu-button" type="button" data-sidebar-open aria-controls="dashboard-sidebar" aria-expanded="false">
                <?= self::icon('menu') ?><span class="sr-only">Abrir menú</span>
            </button>
            <div><p class="topbar-kicker">Módulo de inventario</p><h1><?= e($title) ?></h1></div>
            <div class="topbar-profile">
                <span class="user-avatar" aria-hidden="true"><?= e($initial) ?></span>
                <div><strong><?= e($displayName) ?></strong><span><?= e($role) ?></span></div>
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
        $_SESSION['inventory_flash'] = ['type' => $type, 'message' => $message];
    }

    /** @return array{type:string,message:string}|null */
    private static function pullFlash(): ?array
    {
        $flash = $_SESSION['inventory_flash'] ?? null;
        unset($_SESSION['inventory_flash']);
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
            'overview' => '<path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z"/>',
            'history' => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5M12 7v5l3 2"/>',
            'movement' => '<path d="M4 7h14M14 3l4 4-4 4M20 17H6M10 13l-4 4 4 4"/>',
            'catalogs' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6v.2h-4V21a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9A1.7 1.7 0 0 0 3 14H2.8v-4H3a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9L4.2 7 7 4.2l.1.1A1.7 1.7 0 0 0 9 4a1.7 1.7 0 0 0 1-1.6v-.2h4v.2A1.7 1.7 0 0 0 15 4a1.7 1.7 0 0 0 1.9.3l.1-.1L19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.2v4H21a1.7 1.7 0 0 0-1.6 1Z"/>',
            'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
            'plus' => '<path d="M12 5v14M5 12h14"/>',
            'box' => '<path d="m21 8-9 5-9-5 9-5 9 5Z"/><path d="m3 8 9 5 9-5v8l-9 5-9-5V8"/>',
            'alert' => '<path d="M10.3 4.4 2.6 18a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 4.4a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/>',
            'warehouse' => '<path d="m3 10 9-6 9 6v10H3V10Z"/><path d="M7 20v-6h10v6M7 10h.01M12 10h.01M17 10h.01"/>',
        ];
        return '<svg viewBox="0 0 24 24" aria-hidden="true">' . ($icons[$name] ?? $icons['box']) . '</svg>';
    }
}
