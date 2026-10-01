<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';

use Sedema\AuthService;
use Sedema\Csrf;
use Sedema\Database;

$auth = new AuthService(Database::connection());
$user = $auth->authenticatedUser();
if (!$user) {
    redirect('index.php');
}
if (!empty($user['requires_first_access'])) {
    redirect('first-access.php');
}

$roleNames = [
    'ADMINISTRADOR' => 'Administrador',
    'VENDEDOR' => 'Ventas',
    'PROVEEDOR' => 'Proveedores',
    'DEPOSITO' => 'Depósito',
    'LOGISTICA' => 'Logística',
    'CAJA' => 'Caja',
    'ALMACEN' => 'Almacén',
    'FINANZAS' => 'Finanzas',
    'SISTEMAS' => 'Sistemas',
];

$modules = [
    'ventas' => [
        'name' => 'Ventas y pedidos',
        'description' => 'Registrar pedidos, consultar stock, aplicar ajustes y emitir comprobantes.',
        'icon' => 'cart',
        'href' => 'sales/index.php',
    ],
    'clientes' => [
        'name' => 'Clientes',
        'description' => 'Administrar el padrón y la clasificación comercial de cada cliente.',
        'icon' => 'users',
        'href' => 'clients/index.php',
    ],
    'inventario' => [
        'name' => 'Inventario',
        'description' => 'Consultar existencias, movimientos y alertas de stock mínimo.',
        'icon' => 'boxes',
        'href' => 'inventory/index.php',
    ],
    'proveedores' => [
        'name' => 'Proveedores y compras',
        'description' => 'Gestionar proveedores, compras y recepciones de mercadería.',
        'icon' => 'truck-in',
        'href' => 'suppliers/index.php',
    ],
    'pagos' => [
        'name' => 'Pagos y cobranzas',
        'description' => 'Registrar cobros, medios de pago y operaciones de cuenta corriente.',
        'icon' => 'wallet',
        'href' => 'payments/index.php',
    ],
    'logistica' => [
        'name' => 'Despacho y logística',
        'description' => 'Programar entregas, asignar vehículos y controlar despachos.',
        'icon' => 'truck',
        'href' => 'logistics/index.php',
    ],
    'personal' => [
        'name' => 'Personal y usuarios',
        'description' => 'Administrar legajos, bajas, haberes y accesos vinculados.',
        'icon' => 'badge',
        'href' => 'personal/index.php',
    ],
];

$roleAccess = [
    'ADMINISTRADOR' => array_keys($modules),
    'VENDEDOR' => ['ventas', 'clientes', 'inventario', 'pagos'],
    'PROVEEDOR' => ['proveedores', 'inventario'],
    'DEPOSITO' => ['inventario', 'proveedores', 'logistica'],
    'LOGISTICA' => ['logistica', 'ventas', 'inventario'],
    'CAJA' => ['ventas', 'clientes', 'pagos'],
    'ALMACEN' => ['inventario', 'proveedores'],
    'FINANZAS' => ['pagos'],
    'SISTEMAS' => [],
];

$permissions = array_values(array_filter(
    is_array($user['permissions'] ?? null) ? $user['permissions'] : [],
    static fn (mixed $permission): bool => is_string($permission)
));
$allowedKeys = $roleAccess[$user['role']] ?? [];

if (in_array('*', $permissions, true)) {
    $allowedKeys = array_keys($modules);
} else {
    $permissionAliases = [
        'ventas' => ['ventas', 'sales'],
        'clientes' => ['clientes', 'clients'],
        'inventario' => ['inventario', 'inventory'],
        'proveedores' => ['proveedores', 'suppliers'],
        'pagos' => ['pagos', 'payments'],
        'logistica' => ['logistica', 'logistics'],
        'personal' => ['personal', 'usuarios', 'users'],
    ];
    foreach (array_keys($modules) as $moduleKey) {
        $prefixes = $permissionAliases[$moduleKey] ?? [$moduleKey];
        $hasModulePermission = false;
        foreach ($prefixes as $prefix) {
            foreach ($permissions as $permission) {
                if ($permission === $prefix
                    || str_starts_with($permission, $prefix . '.')) {
                    $hasModulePermission = true;
                    break 2;
                }
            }
        }
        if ($hasModulePermission) {
            $allowedKeys[] = $moduleKey;
        }
    }
}

$allowedKeys = array_values(array_unique($allowedKeys));
$visibleModules = array_intersect_key($modules, array_flip($allowedKeys));
$displayName = trim((string) ($user['name'] ?? $user['username']));
$firstName = explode(' ', $displayName)[0] ?: (string) $user['username'];
$roleLabel = $roleNames[$user['role']] ?? (string) $user['role'];
$loginTime = date('d/m/Y \a \l\a\s H:i', (int) ($user['logged_at'] ?? time()));
$profilePhoto = trim((string) ($user['profile_photo'] ?? ''));
$profilePhotoExists = $profilePhoto !== '' && is_file(BASE_PATH . '/public/' . ltrim($profilePhoto, '/'));

function dashboardIcon(string $name): string
{
    $icons = [
        'home' => '<path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 10v10h13V10"/><path d="M9.5 20v-6h5v6"/>',
        'cart' => '<circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/><path d="M3 4h2l2.4 10.2a2 2 0 0 0 2 1.5h7.8a2 2 0 0 0 1.9-1.4L21 8H6"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'boxes' => '<path d="m21 8-9 5-9-5 9-5 9 5Z"/><path d="m3 8 9 5 9-5M3 16l9 5 9-5M3 12l9 5 9-5"/>',
        'truck-in' => '<path d="M3 6h11v11H3zM14 10h4l3 3v4h-7z"/><circle cx="7" cy="19" r="2"/><circle cx="18" cy="19" r="2"/><path d="M8 10h4M10 8v4"/>',
        'wallet' => '<path d="M4 6h15a2 2 0 0 1 2 2v11H4a2 2 0 0 1-2-2V6a3 3 0 0 1 3-3h13"/><path d="M16 11h5v4h-5a2 2 0 0 1 0-4Z"/>',
        'truck' => '<path d="M3 6h11v11H3zM14 10h4l3 3v4h-7z"/><circle cx="7" cy="19" r="2"/><circle cx="18" cy="19" r="2"/>',
        'badge' => '<circle cx="12" cy="8" r="4"/><path d="M5 21a7 7 0 0 1 14 0M18 3l1 2 2 .5-1.5 1.5.3 2.2"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'shield' => '<path d="M12 3 4.5 6v5c0 4.7 3.2 8.1 7.5 10 4.3-1.9 7.5-5.3 7.5-10V6L12 3Z"/><path d="m9 12 2 2 4-4"/>',
    ];

    return '<svg viewBox="0 0 24 24" aria-hidden="true">' . ($icons[$name] ?? $icons['home']) . '</svg>';
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Panel principal del sistema de gestión SEDEMA.">
    <title>Panel principal | SEDEMA</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="dashboard-page">
<div class="dashboard-shell">
    <aside class="dashboard-sidebar" id="dashboard-sidebar">
        <div class="sidebar-brand">
            <div class="logo-crop dashboard-logo"><img src="assets/img/sedema-logo.png" alt="SEDEMA S.R.L."></div>
            <div><strong>SEDEMA</strong><span>Sistema interno</span></div>
        </div>

        <nav class="sidebar-nav" aria-label="Navegación principal">
            <p class="nav-label">Principal</p>
            <a class="nav-item is-active" href="#inicio" aria-current="page">
                <?= dashboardIcon('home') ?><span>Panel principal</span>
            </a>

            <p class="nav-label">Módulos</p>
            <?php foreach ($visibleModules as $key => $module): ?>
                <a class="nav-item" href="<?= e((string) ($module['href'] ?? '#modulo-' . $key)) ?>">
                    <?= dashboardIcon($module['icon']) ?><span><?= e($module['name']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-footer">
            <div class="user-menu" data-user-menu>
                <button class="user-menu-trigger" type="button" data-user-menu-trigger aria-expanded="false">
                    <?php if ($profilePhotoExists): ?>
                        <img class="user-avatar is-photo" src="<?= e($profilePhoto) ?>" alt="Foto de perfil de <?= e($displayName) ?>">
                    <?php else: ?>
                        <span class="user-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($firstName, 0, 1))) ?></span>
                    <?php endif; ?>
                    <span class="user-menu-copy"><strong><?= e($displayName) ?></strong><span><?= e($roleLabel) ?></span></span>
                    <svg class="user-menu-chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5" fill="none" stroke="currentColor" stroke-width="2"/></svg>
                </button>
                <div class="user-menu-panel" data-user-menu-panel hidden>
                    <a class="user-menu-link" href="profile.php#datos">Mi perfil / Modificar datos</a>
                    <a class="user-menu-link" href="profile.php#foto">Cambiar foto</a>
                    <a class="user-menu-link" href="profile.php#seguridad">Cambiar contraseña</a>
                    <div class="user-menu-divider"></div>
                    <form class="user-menu-form" method="post" action="logout.php"><input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>"><button class="user-menu-logout" type="submit">Cerrar sesión</button></form>
                </div>
            </div>
        </div>
    </aside>

    <button class="sidebar-backdrop" type="button" data-sidebar-close aria-label="Cerrar menú"></button>

    <main class="dashboard-workspace" id="inicio">
        <header class="dashboard-topbar">
            <button class="menu-button" type="button" data-sidebar-open aria-controls="dashboard-sidebar" aria-expanded="false">
                <?= dashboardIcon('menu') ?><span class="sr-only">Abrir menú</span>
            </button>
            <div>
                <p class="topbar-kicker">Sistema de gestión</p>
                <h1>Panel principal</h1>
            </div>
            <div class="topbar-profile user-menu" data-user-menu>
                <button class="user-menu-trigger" type="button" data-user-menu-trigger aria-expanded="false">
                    <?php if ($profilePhotoExists): ?>
                        <img class="user-avatar is-photo" src="<?= e($profilePhoto) ?>" alt="Foto de perfil de <?= e($displayName) ?>">
                    <?php else: ?>
                        <span class="user-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($firstName, 0, 1))) ?></span>
                    <?php endif; ?>
                    <span class="user-menu-copy"><strong><?= e($displayName) ?></strong><span><?= e($roleLabel) ?></span></span>
                    <svg class="user-menu-chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5" fill="none" stroke="currentColor" stroke-width="2"/></svg>
                </button>
                <div class="user-menu-panel" data-user-menu-panel hidden>
                    <a class="user-menu-link" href="profile.php#datos">Mi perfil / Modificar datos</a>
                    <a class="user-menu-link" href="profile.php#foto">Cambiar foto</a>
                    <a class="user-menu-link" href="profile.php#seguridad">Cambiar contraseña</a>
                    <div class="user-menu-divider"></div>
                    <form class="user-menu-form" method="post" action="logout.php"><input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>"><button class="user-menu-logout" type="submit">Cerrar sesión</button></form>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <section class="welcome-panel" aria-labelledby="welcome-title">
                <div>
                    <p class="eyebrow">Acceso correcto</p>
                    <h2 id="welcome-title">Buen día, <?= e($firstName) ?>.</h2>
                    <p>Seleccioná uno de los módulos habilitados para tu perfil.</p>
                </div>
                <div class="session-badge">
                    <?= dashboardIcon('shield') ?>
                    <div><span>Sesión protegida</span><strong><?= e($roleLabel) ?></strong></div>
                </div>
            </section>

            <section class="dashboard-summary" aria-label="Resumen de la sesión">
                <article class="summary-card">
                    <span class="summary-icon"><?= dashboardIcon('badge') ?></span>
                    <div><span>Perfil activo</span><strong><?= e($roleLabel) ?></strong></div>
                </article>
                <article class="summary-card">
                    <span class="summary-number"><?= count($visibleModules) ?></span>
                    <div><span>Accesos habilitados</span><strong>Módulos visibles</strong></div>
                </article>
                <article class="summary-card">
                    <span class="summary-icon"><?= dashboardIcon('clock') ?></span>
                    <div><span>Inicio de sesión</span><strong><?= e($loginTime) ?></strong></div>
                </article>
            </section>

            <section class="modules-section" aria-labelledby="modules-title">
                <div class="section-heading">
                    <div><p class="section-kicker">Accesos</p><h2 id="modules-title">Módulos del sistema</h2></div>
                    <span><?= count($visibleModules) ?> habilitados</span>
                </div>

                <?php if ($visibleModules): ?>
                    <div class="module-grid">
                        <?php foreach ($visibleModules as $key => $module): ?>
                            <?php $available = isset($module['href']); ?>
                            <article class="module-card <?= $available ? 'module-available' : '' ?>" id="modulo-<?= e($key) ?>">
                                <div class="module-card-top">
                                    <span class="module-icon"><?= dashboardIcon($module['icon']) ?></span>
                                    <span class="module-status <?= $available ? 'status-available' : '' ?>"><?= $available ? 'Disponible' : 'En desarrollo' ?></span>
                                </div>
                                <h3><?= e($module['name']) ?></h3>
                                <p><?= e($module['description']) ?></p>
                                <?php if ($available): ?>
                                    <a class="module-action" href="<?= e((string) $module['href']) ?>">Abrir módulo <span aria-hidden="true">→</span></a>
                                <?php else: ?>
                                    <span class="module-action">Próximamente <span aria-hidden="true">→</span></span>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-modules">
                        <h3>Sin módulos asignados</h3>
                        <p>Solicitá al administrador que revise los permisos de tu cuenta.</p>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>
</div>
<script src="assets/js/dashboard.js" defer></script>
</body>
</html>
