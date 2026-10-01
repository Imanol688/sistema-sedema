<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Sedema\Authorization;
use Sedema\Csrf;
use Sedema\Inventory\InventoryContext;
use Sedema\Inventory\InventoryException;
use Sedema\Inventory\InventoryPage;

$context = InventoryContext::boot();
$user = $context['user'];
$service = $context['service'];
$action = (string) ($_POST['action'] ?? '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::validate($_POST['csrf_token'] ?? null)) {
    InventoryPage::flash('error', 'La sesión del formulario venció. Intentá nuevamente.');
    redirect('index.php');
}

$redirects = [
    'save-product' => 'index.php',
    'toggle-product' => 'index.php',
    'movement' => 'movements.php',
    'transfer' => 'movements.php',
    'add-category' => 'catalogs.php',
    'add-unit' => 'catalogs.php',
    'add-warehouse' => 'catalogs.php',
];
$target = $redirects[$action] ?? 'index.php';

try {
    switch ($action) {
        case 'save-product':
            Authorization::require($user, 'inventory.manage');
            $id = $service->saveProduct($_POST, is_array($_POST['minimumStock'] ?? null) ? $_POST['minimumStock'] : []);
            InventoryPage::flash('success', ((int) ($_POST['idProduct'] ?? 0) > 0 ? 'Producto actualizado.' : 'Producto creado.') . ' Código interno #' . $id . '.');
            break;
        case 'toggle-product':
            Authorization::require($user, 'inventory.manage');
            $service->setProductActive((int) ($_POST['idProduct'] ?? 0), (string) ($_POST['active'] ?? '0') === '1');
            InventoryPage::flash('success', (string) ($_POST['active'] ?? '0') === '1' ? 'Producto reactivado.' : 'Producto dado de baja.');
            break;
        case 'movement':
            Authorization::require($user, 'inventory.adjust');
            $service->recordMovement($_POST, (int) $user['id']);
            InventoryPage::flash('success', 'Movimiento registrado y existencia actualizada.');
            break;
        case 'transfer':
            Authorization::require($user, 'inventory.adjust');
            $service->transfer($_POST, (int) $user['id']);
            InventoryPage::flash('success', 'Transferencia registrada en ambos depósitos.');
            break;
        case 'add-category':
        case 'add-unit':
        case 'add-warehouse':
            Authorization::require($user, 'inventory.catalogs');
            $service->addCatalog(substr($action, 4), $_POST);
            InventoryPage::flash('success', 'Configuración guardada correctamente.');
            break;
        default:
            throw new InventoryException('La operación solicitada no es válida.');
    }
    Csrf::rotate();
} catch (InventoryException $error) {
    InventoryPage::flash('error', $error->getMessage());
    if ($action === 'save-product') {
        $productId = max(0, (int) ($_POST['idProduct'] ?? 0));
        $target = 'product.php' . ($productId > 0 ? '?id=' . $productId : '');
    } elseif ($action === 'movement' || $action === 'transfer') {
        $target = 'movement.php';
    }
}

redirect($target);
