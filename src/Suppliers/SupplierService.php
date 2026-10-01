<?php
declare(strict_types=1);
namespace Sedema\Suppliers;
use PDOException;

final class SupplierService
{
    public function __construct(private readonly SupplierRepository $repo) {}
    private function decimal(mixed $value, int $scale, string $label): string
    {
        $raw = trim((string)$value);
        if (!preg_match('/^\d{1,11}(?:\.\d{1,' . $scale . '})?$/D', $raw)) throw new SupplierException($label . ' debe ser un número positivo con hasta ' . $scale . ' decimales (usar punto).');
        $number = (float)$raw;
        if ($number <= 0 || $number > 99999999999) throw new SupplierException($label . ' está fuera de rango.');
        return number_format($number, $scale, '.', '');
    }
    public function saveSupplier(array $input): int
    {
        $id = (int)($input['idProveedor'] ?? 0);
        $name = trim((string)($input['razonSocial'] ?? ''));
        $cuit = preg_replace('/[\s-]/', '', trim((string)($input['cuit'] ?? '')));
        $description = trim((string)($input['descripcionProveedor'] ?? ''));
        $contact = trim((string)($input['contacto'] ?? ''));
        if ($id < 0 || ($id && !$this->repo->supplier($id))) throw new SupplierException('Proveedor inexistente.');
        if ($name === '' || mb_strlen($name)>150 || !preg_match('/^\d{11}$/D', $cuit)) throw new SupplierException('Ingresá razón social y CUIT de 11 dígitos.');
        if (mb_strlen($description)>5000 || mb_strlen($contact)>100) throw new SupplierException('La descripción o el contacto son demasiado largos.');
        try { return $this->repo->saveSupplier($id,$name,$cuit,$description ?: null,$contact ?: null); }
        catch (PDOException $e) { if ((string)$e->getCode()==='23000') throw new SupplierException('El CUIT ya está registrado.'); throw $e; }
    }
    public function createOrder(array $input): int
    {
        $supplier = (int)($input['idProveedor'] ?? 0);
        $number = trim((string)($input['numeroRemito'] ?? ''));
        $date = trim((string)($input['fechaEmision'] ?? ''));
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d',$date);
        if (!$this->repo->supplier($supplier)) throw new SupplierException('Seleccioná un proveedor registrado.');
        if ($number === '' || mb_strlen($number)>50 || !$parsed || $parsed->format('Y-m-d')!==$date) throw new SupplierException('Ingresá un número de remito y fecha válidos.');
        $products = $input['product'] ?? []; $quantities = $input['quantity'] ?? []; $prices = $input['price'] ?? [];
        if (!is_array($products) || !is_array($quantities) || !is_array($prices) || count($products)>100) throw new SupplierException('El detalle del remito es inválido.');
        $lines=[];
        foreach ($products as $key=>$value) {
            $product=(int)$value;
            if ($product===0 && trim((string)($quantities[$key]??''))==='' && trim((string)($prices[$key]??''))==='') continue;
            $info=$this->repo->product($product);
            if (!$info || isset($lines[$product])) throw new SupplierException('Seleccioná productos activos sin repetir.');
            $quantity=$this->decimal($quantities[$key]??'',3,'La cantidad');
            $price=$this->decimal($prices[$key]??'',2,'El precio unitario');
            if (!(int)$info['allowsDecimals'] && (float)$quantity !== floor((float)$quantity)) throw new SupplierException('La unidad del producto exige cantidades enteras.');
            if ((float)$quantity>99999999999.999 || (float)$price>9999999999.99) throw new SupplierException('Cantidad o precio fuera de rango.');
            $lines[$product]=['product'=>$product,'quantity'=>$quantity,'price'=>$price];
        }
        if (!$lines) throw new SupplierException('Agregá al menos un producto.');
        try { return $this->repo->transaction(fn()=> $this->repo->createOrder($supplier,$number,$date,array_values($lines))); }
        catch (PDOException $e) { if ((string)$e->getCode()==='23000') throw new SupplierException('Ya existe un remito con ese número para el proveedor.'); throw $e; }
    }
    public function receive(array $input, int $actor): int
    {
        $id=(int)($input['idRemitoProveedor']??0); $warehouse=(int)($input['idWarehouse']??0);
        $note=trim((string)($input['observaciones']??''));
        $quantities=$input['received']??[];
        if ($id<1 || $actor<1 || !is_array($quantities) || mb_strlen($note)>500) throw new SupplierException('Datos de recepción inválidos.');
        if (!$this->repo->warehouse($warehouse)) throw new SupplierException('Seleccioná un depósito activo.');
        return $this->repo->transaction(function () use ($id,$warehouse,$note,$quantities,$actor): int {
            $order=$this->repo->order($id,true);
            if (!$order || in_array($order['estado'],['CANCELADA','RECEPCION_TOTAL'],true)) throw new SupplierException('Este remito no admite recepciones.');
            $lines=$this->repo->lines($id); $add=[]; $complete=true;
            foreach ($lines as $line) {
                $product=(int)$line['idProducto']; $raw=trim((string)($quantities[$product]??''));
                $value=($raw==='' || preg_match('/^0+(?:\.0{1,3})?$/D',$raw)) ? '0.000' : $this->decimal($raw,3,'La cantidad recibida');
                if (!(int)$line['allowsDecimals'] && (float)$value !== floor((float)$value)) throw new SupplierException('La unidad exige cantidades enteras.');
                $remaining=round((float)$line['cantidadSolicitada']-(float)$line['received'],3);
                if ((float)$value>$remaining+0.00001) throw new SupplierException('La recepción excede lo pendiente para ' . $line['name'] . '.');
                if ($remaining-(float)$value>0.00001) $complete=false;
                if ((float)$value>0) $add[$product]=$value;
            }
            if (!$add) throw new SupplierException('Ingresá al menos una cantidad recibida.');
            $receipt=$this->repo->reception($id,$warehouse,$note);
            foreach ($add as $product=>$quantity) {
                $stock=$this->repo->stock($product,$warehouse);
                if (!$stock) throw new SupplierException('Falta la existencia activa del producto #' . $product . ' en el depósito. Configurala en Inventario.');
                $before=(float)$stock['quantity']; $after=round($before+(float)$quantity,3);
                if ($after>99999999999.999) throw new SupplierException('El stock resultante supera el límite.');
                $this->repo->receiptLine($receipt,$product,$quantity);
                $this->repo->updateStock($product,$warehouse,number_format($after,3,'.',''));
                $this->repo->movement($product,$warehouse,$quantity,number_format($before,3,'.',''),number_format($after,3,'.',''),
                    'Recepción de remito ' . $order['numeroRemito'] . ($note!=='' ? ': ' . $note : ''),$actor,$receipt);
            }
            $this->repo->status($id,$complete ? 'RECEPCION_TOTAL' : 'RECEPCION_PARCIAL');
            return $receipt;
        });
    }
    public function cancel(int $id): void
    {
        $this->repo->transaction(function () use ($id): void {
            $order=$this->repo->order($id,true);
            if (!$order || !in_array($order['estado'],['PENDIENTE','BORRADOR','EMITIDA'],true)) throw new SupplierException('Solo se pueden cancelar remitos sin recepciones.');
            if ($this->repo->receptions($id)) throw new SupplierException('El remito ya tiene recepciones.');
            $this->repo->status($id,'CANCELADA');
        });
    }
}
