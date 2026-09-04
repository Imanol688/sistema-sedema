<?php
declare(strict_types=1);

namespace Sedema\Personnel;

use Sedema\AuthService;
use Sedema\Authorization;
use Sedema\Database;

final class PersonnelContext
{
    /** @return array{user:array<string,mixed>,repository:PersonnelRepository,service:PersonnelService} */
    public static function boot(): array
    {
        $connection = Database::connection();
        $user = (new AuthService($connection))->authenticatedUser();
        if (!$user) {
            redirect('../index.php');
        }
        Authorization::require($user, 'personal.view');
        $repository = new PersonnelRepository($connection);
        return [
            'user' => $user,
            'repository' => $repository,
            'service' => new PersonnelService($repository),
        ];
    }
}
