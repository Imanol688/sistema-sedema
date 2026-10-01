<?php
declare(strict_types=1);
namespace Sedema\Payments;

final class PaymentGateway
{
    public function mode(): string
    {
        return strtolower(trim((string)(getenv('PAYMENT_GATEWAY_MODE') ?: 'mock')));
    }

    /** @return array{provider:string,externalId:string,status:string,qrPayload:?string,qrImageUrl:?string,response:array<string,mixed>} */
    public function create(float $amount,string $description,string $payerEmail,string $method): array
    {
        $method=strtoupper($method);
        if(!in_array($method,['TARJETA','TRANSFERENCIA'],true)){
            throw new PaymentException('La pasarela solo procesa tarjeta o transferencia.');
        }
        if($this->mode()==='mock'){
            $id='SIM-'.date('YmdHis').'-'.bin2hex(random_bytes(3));
            $payload='SEDEMA|'.$method.'|'.$id.'|'.number_format($amount,2,'.','');
            return [
                'provider'=>'SIMULADOR',
                'externalId'=>$id,
                'status'=>'PENDING',
                'qrPayload'=>$payload,
                'qrImageUrl'=>null,
                'response'=>['mode'=>'mock','id'=>$id,'amount'=>$amount,'method'=>$method,'description'=>$description,'payerEmail'=>$payerEmail]
            ];
        }
        return $this->apiRequest('POST','/payments/request',[
            'amount'=>$amount,
            'description'=>$description,
            'payerEmail'=>$payerEmail,
            'method'=>$method,
            'pointOfSaleId'=>(string)(getenv('PAYMENT_POINT_OF_SALE_ID') ?: ''),
        ]);
    }

    /** @return array{status:string,response:array<string,mixed>} */
    public function status(string $externalId): array
    {
        if($this->mode()==='mock'){
            return ['status'=>'PENDING','response'=>['mode'=>'mock','id'=>$externalId,'status'=>'PENDING']];
        }
        $r=$this->apiRequest('GET','/payments/'.rawurlencode($externalId).'/status',[]);
        return ['status'=>$r['status'],'response'=>$r['response']];
    }

    /** @return array{provider:string,externalId:string,status:string,qrPayload:?string,qrImageUrl:?string,response:array<string,mixed>} */
    private function apiRequest(string $method,string $path,array $payload): array
    {
        $base=rtrim(trim((string)getenv('PAYMENT_API_BASE_URL')),'/');
        $key=trim((string)getenv('PAYMENT_API_KEY'));
        if($base===''||$key===''){
            throw new PaymentException('La pasarela no está configurada. Definí PAYMENT_API_BASE_URL y PAYMENT_API_KEY en .env.');
        }
        if(!function_exists('curl_init')){
            throw new PaymentException('La extensión cURL de PHP no está habilitada.');
        }
        $ch=curl_init($base.$path);
        if($ch===false){
            throw new PaymentException('No se pudo iniciar la conexión con la pasarela.');
        }
        $headers=['Accept: application/json','Authorization: Bearer '.$key];
        if($method==='POST'){
            $headers[]='Content-Type: application/json';
            curl_setopt($ch,CURLOPT_POST,true);
            curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
        }
        curl_setopt_array($ch,[
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_HTTPHEADER=>$headers,
            CURLOPT_TIMEOUT=>15,
            CURLOPT_CONNECTTIMEOUT=>7,
            CURLOPT_SSL_VERIFYPEER=>true,
        ]);
        $raw=curl_exec($ch);
        $errno=curl_errno($ch);
        $error=curl_error($ch);
        $code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
        curl_close($ch);
        if($raw===false||$errno!==0){
            throw new PaymentException('No se pudo contactar la pasarela: '.$error);
        }
        $data=json_decode((string)$raw,true);
        if(!is_array($data)){
            throw new PaymentException('La pasarela respondió con un formato inválido.');
        }
        if($code<200||$code>=300){
            throw new PaymentException('La pasarela rechazó la operación (HTTP '.$code.').');
        }
        $status=strtoupper((string)($data['status']??'PENDING'));
        return [
            'provider'=>(string)($data['provider']??'API'),
            'externalId'=>(string)($data['id']??$data['externalId']??''),
            'status'=>$status,
            'qrPayload'=>isset($data['qrPayload'])?(string)$data['qrPayload']:null,
            'qrImageUrl'=>isset($data['qrImageUrl'])?(string)$data['qrImageUrl']:null,
            'response'=>$data,
        ];
    }
}
