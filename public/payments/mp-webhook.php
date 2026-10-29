<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/src/bootstrap.php';
use Sedema\Database;
use Sedema\Payments\MercadoPagoWebhook;
use Sedema\Payments\MercadoPagoQrClient;
use Sedema\Payments\MercadoPagoQrService;
use Sedema\Payments\PaymentRepository;
session_write_close();
header('Content-Type: application/json; charset=UTF-8');header('Cache-Control: no-store');
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit('{"error":"method"}');}
if((int)($_SERVER['CONTENT_LENGTH']??0)>65536){http_response_code(413);exit('{"error":"size"}');}
$raw=file_get_contents('php://input',false,null,0,65537);
$body=json_decode((string)$raw,true);
// PHP convierte el parámetro data.id de la URL en data_id.
$id=is_string($_GET['data_id']??null)?$_GET['data_id']:'';
if(!preg_match('/^ORD[A-Z0-9]{26}$/iD',$id)){http_response_code(400);exit('{"error":"id"}');}
$secret=trim((string)getenv('MP_WEBHOOK_SECRET'));
if(!MercadoPagoWebhook::valid((string)($_SERVER['HTTP_X_SIGNATURE']??''),(string)($_SERVER['HTTP_X_REQUEST_ID']??''),$id,$secret)){
    http_response_code(401);exit('{"error":"signature"}');
}
if(!is_array($body)||($body['type']??'')!=='order'||(string)($body['data']['id']??'')!==$id){http_response_code(400);exit('{"error":"payload"}');}
try{
    $db=Database::connection();$repo=new PaymentRepository($db);$client=new MercadoPagoQrClient();
    // El cuerpo notificado no se utiliza como prueba de acreditación.
    $remote=$client->get($id);
    $q=$db->prepare('SELECT idPago FROM mp_qr_order WHERE externalReference=? LIMIT 1');$q->execute([(string)($remote['external_reference']??'')]);
    $paymentId=(int)$q->fetchColumn();
    if($paymentId>0){
        $service=new MercadoPagoQrService($repo,$client);
        $service->validateRemote($service->order($paymentId),$remote);
        $service->verify($paymentId,0,$remote);
    }
    echo '{"received":true}';
}catch(Throwable $e){
    error_log('MP webhook no procesado: '.$e->getMessage());
    http_response_code(503);echo '{"error":"retry"}';
}
