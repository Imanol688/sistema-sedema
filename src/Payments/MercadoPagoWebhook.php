<?php
declare(strict_types=1);
namespace Sedema\Payments;

final class MercadoPagoWebhook
{
    public static function valid(string $signature,string $requestId,string $dataId,string $secret): bool
    {
        if($secret===''||$requestId===''||$dataId==='')return false;
        $values=[];
        foreach(explode(',',$signature) as $part){$pair=explode('=',trim($part),2);if(count($pair)===2)$values[trim($pair[0])]=trim($pair[1]);}
        if(!preg_match('/^\d+$/D',$values['ts']??'')||!preg_match('/^[a-fA-F0-9]{64}$/D',$values['v1']??''))return false;
        // La firma utiliza data.id en minúsculas; el ID original se conserva para la API.
        $manifest='id:'.strtolower($dataId).';request-id:'.$requestId.';ts:'.$values['ts'].';';
        return hash_equals(hash_hmac('sha256',$manifest,$secret),strtolower($values['v1']));
    }
}
