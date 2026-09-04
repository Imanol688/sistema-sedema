<?php
declare(strict_types=1);

namespace Sedema\Inventory;

use Sedema\AuthService;
use Sedema\Authorization;
use Sedema\Database;

final class InventoryContext
{
    /** @return array{user:array<string,mixed>,repository:InventoryRepository,service:InventoryService} */
    public static function boot(): array
    {
        $connection = Database::connection();
        $user = (new AuthService($connection))->authenticatedUser();
        if (!$user) {
            redirect('../index.php');
        }
        Authorization::require($user, 'inventory.view');
        $repository = new InventoryRepository($connection);

        return [
            'user' => $user,
            'repository' => $repository,
            'service' => new InventoryService($repository),
        ];
    }
}
