<?php
declare(strict_types=1);

namespace Sedema\Logistics;

use Sedema\AuthService;
use Sedema\Authorization;
use Sedema\Database;

final class LogisticsContext
{
    /** @return array{user:array<string,mixed>,repository:LogisticsRepository,service:LogisticsService} */
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
        Authorization::require($user, 'logistics.view');
        $repository = new LogisticsRepository($connection);
        return ['user' => $user, 'repository' => $repository, 'service' => new LogisticsService($repository)];
    }
}
