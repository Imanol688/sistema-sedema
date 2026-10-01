<?php
declare(strict_types=1);
namespace Sedema\Payments;
use Sedema\AuthService;use Sedema\Authorization;use Sedema\Database;
final class PaymentContext{public static function boot():array{$db=Database::connection();$user=(new AuthService($db))->authenticatedUser();if(!$user)redirect('../index.php');if(!empty($user['requires_first_access']))redirect('../first-access.php');Authorization::require($user,'payments.view');$r=new PaymentRepository($db);$g=new PaymentGateway();return['user'=>$user,'repository'=>$r,'gateway'=>$g,'service'=>new PaymentService($r,$g)];}}
