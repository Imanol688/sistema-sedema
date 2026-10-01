<?php
declare(strict_types=1);
namespace Sedema\Sales;
use Sedema\AuthService;use Sedema\Authorization;use Sedema\Database;
final class SalesContext{public static function boot():array{$db=Database::connection();$user=(new AuthService($db))->authenticatedUser();if(!$user)redirect('../index.php');if(!empty($user['requires_first_access']))redirect('../first-access.php');Authorization::require($user,'sales.view');$r=new SalesRepository($db);return['user'=>$user,'repository'=>$r,'service'=>new SalesService($r)];}}
