<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Sedema\Authorization;
use Sedema\Csrf;
use Sedema\Personnel\PersonnelContext;
use Sedema\Personnel\PersonnelException;
use Sedema\Personnel\PersonnelPage;

$context = PersonnelContext::boot();
$user = $context['user'];
$service = $context['service'];
$action = (string) ($_POST['action'] ?? '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::validate($_POST['csrf_token'] ?? null)) {
    PersonnelPage::flash('error', 'La sesión del formulario venció. Intentá nuevamente.');
    redirect('index.php');
}

$target = 'index.php';
try {
    switch ($action) {
        case 'save-employee':
            Authorization::require($user, 'personal.manage');
            $id = $service->saveEmployee($_POST);
            PersonnelPage::flash('success', ((int) ($_POST['idEmpleado'] ?? 0) > 0 ? 'Legajo actualizado.' : 'Empleado registrado.') . ' Legajo #' . $id . '.');
            break;
        case 'toggle-employee':
            Authorization::require($user, 'personal.manage');
            $active = (string) ($_POST['active'] ?? '0') === '1';
            $service->setEmployeeActive((int) ($_POST['idEmpleado'] ?? 0), $active, (int) $user['id']);
            PersonnelPage::flash('success', $active ? 'Legajo reactivado.' : 'Empleado dado de baja.');
            break;
        case 'create-payroll':
            Authorization::require($user, 'personal.payroll');
            $id = $service->createPayroll($_POST, (int) $user['id']);
            PersonnelPage::flash('success', 'Liquidación procesada y recibo generado.');
            $target = 'receipt.php?id=' . $id;
            break;
        default:
            throw new PersonnelException('La operación solicitada no es válida.');
    }
    Csrf::rotate();
} catch (PersonnelException $error) {
    PersonnelPage::flash('error', $error->getMessage());
    if ($action === 'save-employee') {
        $id = max(0, (int) ($_POST['idEmpleado'] ?? 0));
        $target = 'employee.php' . ($id > 0 ? '?id=' . $id : '');
    } elseif ($action === 'create-payroll') {
        $target = 'payroll.php';
    }
}
redirect($target);
