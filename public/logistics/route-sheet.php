<?php
declare(strict_types=1);require dirname(__DIR__,2).'/src/bootstrap.php';use Sedema\Logistics\LogisticsContext;
$c=LogisticsContext::boot();$repo=$c['repository'];$service=$c['service'];$id=max(0,(int)($_GET['id']??0));$d=$repo->dispatch($id);if(!$d){http_response_code(404);exit('Despacho no encontrado.');}$items=$repo->dispatchItems($id);$documentTitle='Hoja de ruta';require __DIR__.'/print-document.php';
