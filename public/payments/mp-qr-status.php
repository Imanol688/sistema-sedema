<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/src/bootstrap.php';
use Sedema\Authorization;
use Sedema\Csrf;
use Sedema\Payments\PaymentContext;
use Sedema\Payments\MercadoPagoQrService;
use Sedema\Payments\PaymentException;
$c=PaymentContext::boot();Authorization::require($c['user'],'payments.manage');
header('Content-Type: application/json; charset=UTF-8');header('Cache-Control: no-store');
if($_SERVER['REQUEST_METHOD']!=='POST'||!Csrf::validate($_POST['csrf_token']??null)){http_response_code(403);echo json_encode(['error'=>'Sesión vencida. Recargá la página.']);exit;}
$id=(int)($_POST['idPago']??0);session_write_close();
try{
    $service=new MercadoPagoQrService($c['repository']);$status=$service->verify($id,(int)$c['user']['id']);$row=$service->order($id);
    echo json_encode(['status'=>$status,'remoteStatus'=>$row['status']],JSON_THROW_ON_ERROR);
}catch(PaymentException $e){http_response_code(409);echo json_encode(['error'=>$e->getMessage()]);}
catch(Throwable $e){error_log('MP QR status: '.$e->getMessage());http_response_code(500);echo json_encode(['error'=>'No se pudo consultar. Intentá con Verificar estado.']);}
