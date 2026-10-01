<?php
declare(strict_types=1);
namespace Sedema\Suppliers;
use Sedema\{AuthService, Authorization, Database};
final class SupplierContext
{
    public static function boot(): array
    {
        $db = Database::connection();
        $user = (new AuthService($db))->authenticatedUser();
        if (!$user) redirect('../index.php');
        if (!empty($user['requires_first_access'])) redirect('../first-access.php');
        Authorization::require($user, 'suppliers.view');
        $repository = new SupplierRepository($db);
        return ['user' => $user, 'repository' => $repository, 'service' => new SupplierService($repository)];
    }
}
