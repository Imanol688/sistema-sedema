<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/src/bootstrap.php';

use Sedema\Database;

[$script, $username, $email, $password, $name, $surname] = array_pad($argv, 6, null);
if (!$username || !$email || !$password || !$name || !$surname) {
    fwrite(STDERR, "Uso: php database/create_admin.php usuario correo contraseña nombre apellido\n");
    exit(1);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) {
    fwrite(STDERR, "El correo debe ser válido y la contraseña debe tener al menos 12 caracteres.\n");
    exit(1);
}

$db = Database::connection();
$db->beginTransaction();
try {
    $dni = 'PENDIENTE-' . bin2hex(random_bytes(4));
    $db->prepare('INSERT INTO empleado (nombre, apellido, dni, activo) VALUES (?, ?, ?, 1)')->execute([$name, $surname, $dni]);
    $employeeId = (int) $db->lastInsertId();
    $db->prepare(
        'INSERT INTO usuario (idEmpleado, username, email, passwordHash, roles, permisos, habilitado)
         VALUES (?, ?, ?, ?, "ADMINISTRADOR", ?, 1)'
    )->execute([$employeeId, mb_strtolower($username), mb_strtolower($email), password_hash($password, PASSWORD_DEFAULT), json_encode(['*'], JSON_THROW_ON_ERROR)]);
    $db->commit();
    fwrite(STDOUT, "Administrador creado. Complete el DNI del empleado desde la base antes de producción.\n");
} catch (Throwable $error) {
    $db->rollBack();
    fwrite(STDERR, "No se pudo crear el administrador: " . $error->getMessage() . "\n");
    exit(1);
}

