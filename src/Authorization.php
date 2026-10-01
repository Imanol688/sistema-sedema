<?php
declare(strict_types=1);

namespace Sedema;

final class Authorization
{
    /** @var array<string, list<string>> */
    private const ROLE_PERMISSIONS = [
        'ADMINISTRADOR' => ['*'],
        'VENDEDOR' => ['inventory.view', 'clientes.view', 'clientes.manage', 'sales.view', 'sales.manage', 'sales.remit', 'payments.view', 'payments.manage'],
        'PROVEEDOR' => ['inventory.view', 'suppliers.view', 'suppliers.manage', 'suppliers.receive'],
        'DEPOSITO' => ['suppliers.view', 'suppliers.receive', 'inventory.view', 'inventory.manage', 'inventory.adjust', 'inventory.catalogs', 'logistics.view', 'logistics.dispatch'],
        'LOGISTICA' => ['inventory.view', 'sales.view', 'sales.remit', 'logistics.view', 'logistics.manage', 'logistics.dispatch'],
        'CAJA' => ['clientes.view', 'clientes.manage', 'sales.view', 'sales.manage', 'sales.invoice', 'payments.view', 'payments.manage'],
        'ALMACEN' => ['suppliers.view', 'suppliers.receive', 'inventory.view', 'inventory.manage', 'inventory.adjust'],
        'FINANZAS' => ['payments.view', 'payments.manage'],
        'SISTEMAS' => [],
    ];

    /** @param array<string,mixed> $user */
    public static function can(array $user, string $permission): bool
    {
        $assigned = is_array($user['permissions'] ?? null) ? $user['permissions'] : [];
        $rolePermissions = self::ROLE_PERMISSIONS[(string) ($user['role'] ?? '')] ?? [];
        $permissions = array_merge($rolePermissions, $assigned);

        if (in_array('*', $permissions, true) || in_array($permission, $permissions, true)) {
            return true;
        }

        $segments = explode('.', $permission);
        while (count($segments) > 1) {
            array_pop($segments);
            if (in_array(implode('.', $segments) . '.*', $permissions, true)) {
                return true;
            }
        }

        return false;
    }

    /** @param array<string,mixed> $user */
    public static function require(array $user, string $permission): void
    {
        if (self::can($user, $permission)) {
            return;
        }

        http_response_code(403);
        exit('No tenés permisos para realizar esta operación.');
    }
}
