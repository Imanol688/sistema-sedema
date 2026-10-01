<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';

use Sedema\AuthService;
use Sedema\Csrf;
use Sedema\Database;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::validate($_POST['csrf_token'] ?? null)) {
    http_response_code(405);
    exit('Método no permitido.');
}
(new AuthService(Database::connection()))->logout();
redirect('index.php');

