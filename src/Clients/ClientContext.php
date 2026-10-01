<?php
declare(strict_types=1);

namespace Sedema\Clients;

use Sedema\AuthService;
use Sedema\Authorization;
use Sedema\Database;

final class ClientContext
{
    /** @return array{user:array<string,mixed>,repository:ClientRepository,service:ClientService} */
    public static function boot(): array
    {
        $connection = Database::connection();
        $user = (new AuthService($connection))->authenticatedUser();
        if (!$user) {
            redirect('../index.php');
        }
        if (!empty($user['requires_first_access'])) {
            redirect('../first-access.php');
        }
        Authorization::require($user, 'clientes.view');

        $repository = new ClientRepository($connection);
        return [
            'user' => $user,
            'repository' => $repository,
            'service' => new ClientService($repository),
        ];
    }
}
