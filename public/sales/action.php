<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Sedema\Authorization;
use Sedema\Csrf;
use Sedema\Sales\SalesContext;
use Sedema\Sales\SalesException;
use Sedema\Sales\SalesPage;

$context = SalesContext::boot();
$user = $context['user'];
$service = $context['service'];
$action = (string) ($_POST['action'] ?? '');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::validate($_POST['csrf_token'] ?? null)) {
    SalesPage::flash('error', 'La sesión del formulario venció.');
    redirect('index.php');
}
$target = 'index.php';
try {
    switch ($action) {
        case 'create-order':
            Authorization::require($user, 'sales.manage');
            $id = $service->createOrder($_POST, (int) $user['id']);
            SalesPage::flash('success', 'Pedido #' . $id . ' creado correctamente.');
            $target = 'order.php?id=' . $id;
            break;
        case 'cancel-order':
            Authorization::require($user, 'sales.manage');
            $id = (int) ($_POST['idPedido'] ?? 0);
            $service->cancel($id, (int) $user['id']);
            SalesPage::flash('success', 'Pedido cancelado.');
            $target = 'order.php?id=' . $id;
            break;
        case 'issue-remit':
            Authorization::require($user, 'sales.remit');
            $id = (int) ($_POST['idPedido'] ?? 0);
            $service->issueRemittance($id, $_POST, (int) $user['id']);
            SalesPage::flash('success', 'Remito emitido correctamente.');
            $target = 'order.php?id=' . $id;
            break;
        case 'issue-invoice':
            Authorization::require($user, 'sales.invoice');
            $id = (int) ($_POST['idPedido'] ?? 0);
            $service->issueInvoice($id, $_POST, (int) $user['id']);
            SalesPage::flash('success', 'Factura emitida correctamente.');
            $target = 'order.php?id=' . $id;
            break;
        default:
            throw new SalesException('Operación inválida.');
    }
    Csrf::rotate();
} catch (SalesException $error) {
    SalesPage::flash('error', $error->getMessage());
    $id = (int) ($_POST['idPedido'] ?? 0);
    $target = $id > 0 ? 'order.php?id=' . $id : 'order.php';
}
redirect($target);
