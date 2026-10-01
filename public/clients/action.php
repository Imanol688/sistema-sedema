<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Sedema\Authorization;
use Sedema\Clients\ClientContext;
use Sedema\Clients\ClientException;
use Sedema\Clients\ClientPage;
use Sedema\Csrf;

$context = ClientContext::boot();
$user = $context['user'];
$service = $context['service'];
$action = (string) ($_POST['action'] ?? '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::validate($_POST['csrf_token'] ?? null)) {
    ClientPage::flash('error', 'La sesión del formulario venció. Intentá nuevamente.');
    redirect('index.php');
}

$target = 'index.php';
$returnTo = (string) ($_POST['return'] ?? '');
try {
    switch ($action) {
        case 'save-client':
            Authorization::require($user, 'clientes.manage');
            $id = $service->save($_POST);
            ClientPage::flash('success', ((int) ($_POST['idCliente'] ?? 0) > 0 ? 'Cliente actualizado correctamente.' : 'Cliente registrado correctamente.') . ' Ficha #' . $id . '.');
            if ($returnTo === 'sales' && (int) ($_POST['idCliente'] ?? 0) === 0) {
                $target = '../sales/order.php?client=' . $id;
            }
            break;
        case 'toggle-client':
            Authorization::require($user, 'clientes.manage');
            $service->setActive((int) ($_POST['idCliente'] ?? 0), (string) ($_POST['active'] ?? '0') === '1');
            ClientPage::flash('success', (string) ($_POST['active'] ?? '0') === '1' ? 'Cliente reactivado.' : 'Cliente dado de baja.');
            break;
        default:
            throw new ClientException('La operación solicitada no es válida.');
    }
    Csrf::rotate();
} catch (ClientException $error) {
    ClientPage::flash('error', $error->getMessage());
    if ($action === 'save-client') {
        $id = max(0, (int) ($_POST['idCliente'] ?? 0));
        $query = [];
        if ($id > 0) { $query['id'] = $id; }
        if ($returnTo === 'sales') { $query['return'] = 'sales'; }
        $target = 'client.php' . ($query ? '?' . http_build_query($query) : '');
    }
}

redirect($target);
