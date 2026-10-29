<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/bootstrap.php';
use Sedema\AuthService;
use Sedema\Authorization;
use Sedema\Database;

$user = (new AuthService(Database::connection()))->authenticatedUser();
if (!$user) { redirect('../index.php'); }
if (!empty($user['requires_first_access'])) { redirect('../first-access.php'); }
$section = (string) ($_GET['section'] ?? '');
if ($section === 'clients') {
    Authorization::require($user, 'clientes.view');
    redirect('../clients/index.php');
}
if ($section === 'orders') {
    Authorization::require($user, 'sales.view');
    redirect('../sales/index.php');
}
if (Authorization::can($user, 'sales.view')) { redirect('../sales/index.php'); }
Authorization::require($user, 'clientes.view');
redirect('../clients/index.php');
