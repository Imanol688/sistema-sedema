<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Sedema\Access\AccessException;
use Sedema\Access\CredentialMailer;
use Sedema\Access\UserAccessRepository;
use Sedema\Access\UserAccessService;
use Sedema\Authorization;
use Sedema\Database;
use Sedema\Csrf;
use Sedema\Personnel\PersonnelContext;
use Sedema\Personnel\PersonnelException;
use Sedema\Personnel\PersonnelPage;

$context = PersonnelContext::boot();
$user = $context['user'];
$service = $context['service'];
$accessService = new UserAccessService(new UserAccessRepository(Database::connection()), new CredentialMailer());
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
        case 'create-account':
            Authorization::require($user, 'personal.access');
            $id = $accessService->createAccount($_POST, (int) $user['id']);
            PersonnelPage::flash('success', 'Cuenta creada y credencial temporal enviada por correo. Usuario #' . $id . '.');
            $target = 'access.php';
            break;
        case 'update-account':
            Authorization::require($user, 'personal.access');
            $accessService->updateAccess($_POST, (int) $user['id']);
            PersonnelPage::flash('success', 'Acceso actualizado. Las sesiones anteriores fueron invalidadas.');
            $target = 'access.php';
            break;
        case 'resend-temporary-credential':
            Authorization::require($user, 'personal.access');
            $id = max(0, (int) ($_POST['idUsuario'] ?? 0));
            $accessService->resendTemporaryCredential($id, (int) $user['id']);
            PersonnelPage::flash('success', 'Se generó una nueva contraseña temporal y se envió al correo del empleado.');
            $target = 'account.php?id=' . $id;
            break;
        case 'delete-account':
            Authorization::require($user, 'personal.access');
            $id = max(0, (int) ($_POST['idUsuario'] ?? 0));
            $accessService->deleteAccount($id, (int) $user['id']);
            PersonnelPage::flash('success', 'Cuenta eliminada. El legajo del empleado se conservó.');
            $target = 'access.php';
            break;
        case 'delete-accounts':
            Authorization::require($user, 'personal.access');
            $rawIds = is_array($_POST['user_ids'] ?? null) ? $_POST['user_ids'] : [];
            $count = $accessService->deleteAccounts(array_map('intval', $rawIds), (int) $user['id']);
            PersonnelPage::flash('success', $count === 1 ? '1 cuenta eliminada.' : $count . ' cuentas eliminadas. Los legajos se conservaron.');
            $target = 'access.php';
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
} catch (PersonnelException|AccessException $error) {
    PersonnelPage::flash('error', $error->getMessage());
    if ($action === 'save-employee') {
        $id = max(0, (int) ($_POST['idEmpleado'] ?? 0));
        $target = 'employee.php' . ($id > 0 ? '?id=' . $id : '');
    } elseif ($action === 'create-payroll') {
        $target = 'payroll.php';
    } elseif ($action === 'create-account') {
        $target = 'account.php';
    } elseif ($action === 'update-account' || $action === 'resend-temporary-credential' || $action === 'delete-account') {
        $id = max(0, (int) ($_POST['idUsuario'] ?? 0));
        $target = $action === 'delete-account' ? 'access.php' : 'account.php?id=' . $id;
    } elseif ($action === 'delete-accounts') {
        $target = 'access.php';
    }
}
redirect($target);
