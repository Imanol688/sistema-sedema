<?php
declare(strict_types=1);
namespace Sedema\Payments;

/** Cliente de Orders API para una cuenta propia, QR estático de caja atendida. */
class MercadoPagoQrClient
{
    public function configuration(): array
    {
        $token=trim((string)getenv('MP_ACCESS_TOKEN'));
        $seller=trim((string)getenv('MP_SELLER_ID'));
        $pos=trim((string)getenv('MP_POS_EXTERNAL_ID'));
        $environment=strtolower(trim((string)(getenv('MP_ENVIRONMENT')?:'test')));
        $invalid=[];
        if($token===''||$token==='COMPLETAR_EN_TU_EQUIPO'||str_contains($token,'PEGA_AQUI'))$invalid[]='MP_ACCESS_TOKEN (falta completar)';
        if(!preg_match('/^\d+$/D',$seller))$invalid[]='MP_SELLER_ID (debe ser numérico)';
        if(!preg_match('/^[A-Za-z0-9]{1,40}$/D',$pos))$invalid[]='MP_POS_EXTERNAL_ID (identificador de caja inválido)';
        if($invalid){
            $path=defined('BASE_PATH')?BASE_PATH.'/.env':'.env';
            throw new PaymentException('Revisá '.implode(', ',$invalid).' en '.$path.'. Versión MP v2.6.1.');
        }
        if(!in_array($environment,['test','production'],true))throw new PaymentException('MP_ENVIRONMENT debe ser test o production.');
        return ['seller'=>$seller,'pos'=>$pos,'environment'=>$environment];
    }

    public function create(array $payload,string $key): array
    {
        return $this->request('POST','/v1/orders',$payload,$key);
    }

    public function get(string $id): array
    {
        return $this->request('GET','/v1/orders/'.rawurlencode($id));
    }

    public function cancel(string $id,string $key): array
    {
        return $this->request('POST','/v1/orders/'.rawurlencode($id).'/cancel',null,$key);
    }

    public function request(string $method,string $path,?array $payload=null,?string $key=null): array
    {
        $this->configuration();
        if(!function_exists('curl_init'))throw new PaymentException('Habilitá extension=curl en C:\\xampp\\php\\php.ini y reiniciá Apache.');
        $ch=curl_init('https://api.mercadopago.com'.$path);
        $headers=['Accept: application/json','Content-Type: application/json','Authorization: Bearer '.trim((string)getenv('MP_ACCESS_TOKEN'))];
        if($key!==null)$headers[]='X-Idempotency-Key: '.$key;
        curl_setopt_array($ch,[CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>$headers,
            CURLOPT_TIMEOUT=>8,CURLOPT_CONNECTTIMEOUT=>3,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,
            CURLOPT_FOLLOWLOCATION=>false]);
        if($payload!==null)curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($payload,JSON_THROW_ON_ERROR));
        $raw=curl_exec($ch);$errno=curl_errno($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
        if($raw===false||$errno!==0)throw new PaymentException('No se confirmó la respuesta de Mercado Pago. Verificá el mismo cobro; no generes otro. Revisá Internet y los certificados de cURL.');
        $data=json_decode((string)$raw,true);
        if($code<200||$code>=300){
            $error=is_array($data)?(string)($data['errors'][0]['code']??$data['error']??'api_error'):'api_error';
            $error=preg_replace('/[^a-zA-Z0-9_\-]/','',$error);
            $detail=self::errorDetail(is_array($data)?$data:[]);
            throw new PaymentException('Mercado Pago respondió HTTP '.$code.' ('.$error.').'.($detail!==''?' '.$detail:' Revisá la configuración del cobro.'),$code);
        }
        if(!is_array($data)||empty($data['id']))throw new PaymentException('Mercado Pago devolvió una respuesta incompleta. Verificá este mismo cobro.');
        return $data;
    }

    public static function validateAmount(float $amount): void
    {
        // Mínimo confirmado por la API QR en Argentina durante esta integración.
        if(!is_finite($amount)||round($amount,2)<15.00){
            throw new PaymentException('Mercado Pago QR admite un mínimo de $15,00 por operación. El importe final es menor: elegí otro medio o usá un pedido de prueba con saldo suficiente.');
        }
    }

    public static function errorDetail(array $data): string
    {
        $parts=[];
        $walk=static function(mixed $node,int $depth=0)use(&$walk,&$parts):void{
            if($depth>5||count($parts)>=8)return;
            if(is_string($node)){$parts[]=$node;return;}
            if(!is_array($node))return;
            foreach($node as $key=>$value){
                if(is_int($key)||in_array($key,['message','description','details','errors','cause','causes','field','property'],true))$walk($value,$depth+1);
            }
        };
        $walk($data);
        $text=implode(' · ',array_unique($parts));
        $token=trim((string)getenv('MP_ACCESS_TOKEN'));
        if($token!=='')$text=str_replace($token,'[credencial oculta]',$text);
        $text=preg_replace('/(?:APP_USR|TEST)-[A-Za-z0-9_-]+/','[credencial oculta]',$text)??'';
        $text=preg_replace('/Bearer\s+[^\s"\']+/i','Bearer [credencial oculta]',$text)??'';
        $text=preg_replace('/[\x00-\x1F\x7F]/',' ',$text)??'';
        preg_match('/^.{0,200}/us',$text,$result);
        return $result[0]??'';
    }

    public static function uuid(): string
    {
        $b=random_bytes(16);$b[6]=chr((ord($b[6])&15)|64);$b[8]=chr((ord($b[8])&63)|128);
        $h=bin2hex($b);return substr($h,0,8).'-'.substr($h,8,4).'-'.substr($h,12,4).'-'.substr($h,16,4).'-'.substr($h,20);
    }
}
