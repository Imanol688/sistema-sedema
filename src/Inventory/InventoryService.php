<?php
declare(strict_types=1);

namespace Sedema\Inventory;

use PDOException;

final class InventoryService
{
    private const MOVEMENT_TYPES = ['INGRESO', 'EGRESO', 'AJUSTE_POSITIVO', 'AJUSTE_NEGATIVO'];

    public function __construct(private readonly InventoryRepository $repository)
    {
    }

    /** @param array<string,mixed> $input @param array<int|string,mixed> $minimums */
    public function saveProduct(array $input, array $minimums): int
    {
        $id = max(0, (int) ($input['idProduct'] ?? 0));
        $code = mb_strtoupper(trim((string) ($input['code'] ?? '')));
        $name = trim((string) ($input['name'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $category = (int) ($input['idCategory'] ?? 0);
        $unit = (int) ($input['idUnit'] ?? 0);
        $price = $this->decimal($input['salePrice'] ?? 0, 'El precio');

        if ($code === '' || mb_strlen($code) > 50) {
            throw new InventoryException('Ingresá un código de producto de hasta 50 caracteres.');
        }
        if ($name === '' || mb_strlen($name) > 150) {
            throw new InventoryException('Ingresá un nombre de producto de hasta 150 caracteres.');
        }
        if ($category < 1 || $unit < 1) {
            throw new InventoryException('Seleccioná una categoría y una unidad de medida.');
        }
        if ($price < 0) {
            throw new InventoryException('El precio no puede ser negativo.');
        }

        $attributes = $this->parseAttributes((string) ($input['customAttributes'] ?? ''));
        $data = [
            'code' => $code,
            'name' => $name,
            'description' => $description !== '' ? $description : null,
            'category' => $category,
            'unit' => $unit,
            'price' => number_format($price, 2, '.', ''),
            'attributes' => $attributes ? json_encode($attributes, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) : null,
        ];

        try {
            return $this->repository->transaction(function () use ($id, $data, $minimums): int {
                $productId = $id > 0 ? $id : $this->repository->createProduct($data);
                if ($id > 0) {
                    if (!$this->repository->product($id)) {
                        throw new InventoryException('El producto que intentás modificar no existe.');
                    }
                    $this->repository->updateProduct($id, $data);
                }

                foreach ($this->repository->warehouses() as $warehouse) {
                    $warehouseId = (int) $warehouse['idWarehouse'];
                    $minimum = $this->decimal($minimums[$warehouseId] ?? 0, 'El stock mínimo');
                    if ($minimum < 0) {
                        throw new InventoryException('El stock mínimo no puede ser negativo.');
                    }
                    $this->repository->ensureStock($productId, $warehouseId, $minimum);
                }
                return $productId;
            });
        } catch (PDOException $error) {
            if ((string) $error->getCode() === '23000') {
                throw new InventoryException('El código del producto ya está registrado.');
            }
            throw $error;
        }
    }

    public function setProductActive(int $productId, bool $active): void
    {
        if ($productId < 1 || !$this->repository->product($productId)) {
            throw new InventoryException('El producto indicado no existe.');
        }
        $this->repository->setProductActive($productId, $active);
    }

    /** @param array<string,mixed> $input */
    public function recordMovement(array $input, int $actorUserId, string $sourceModule = 'INVENTARIO'): int
    {
        $productId = (int) ($input['idProduct'] ?? 0);
        $warehouseId = (int) ($input['idWarehouse'] ?? 0);
        $type = mb_strtoupper(trim((string) ($input['movementType'] ?? '')));
        $quantity = $this->decimal($input['quantity'] ?? 0, 'La cantidad');
        $observations = trim((string) ($input['observations'] ?? ''));
        $sourceReference = trim((string) ($input['sourceReference'] ?? ''));

        if (!in_array($type, self::MOVEMENT_TYPES, true)) {
            throw new InventoryException('Seleccioná un tipo de movimiento válido.');
        }
        if ($productId < 1 || $warehouseId < 1 || $quantity <= 0) {
            throw new InventoryException('Seleccioná producto, depósito y una cantidad mayor que cero.');
        }
        if ($observations === '' || mb_strlen($observations) > 500) {
            throw new InventoryException('Ingresá una observación de hasta 500 caracteres.');
        }
        if (!preg_match('/^[A-Z][A-Z0-9_]{1,39}$/', $sourceModule)) {
            throw new InventoryException('El módulo de origen no es válido.');
        }

        return $this->repository->transaction(function () use (
            $productId, $warehouseId, $type, $quantity, $observations, $actorUserId, $sourceModule, $sourceReference
        ): int {
            $stock = $this->repository->lockStock($productId, $warehouseId);
            if (!$stock) {
                throw new InventoryException('No existe una existencia activa para el producto y depósito elegidos.');
            }
            if ((int) $stock['allowsDecimals'] !== 1 && abs($quantity - round($quantity)) > 0.0001) {
                throw new InventoryException('La unidad de este producto solo admite cantidades enteras.');
            }
            $previous = (float) $stock['quantity'];
            $positive = in_array($type, ['INGRESO', 'AJUSTE_POSITIVO'], true);
            $resulting = round($previous + ($positive ? $quantity : -$quantity), 3);
            if ($resulting < 0) {
                throw new InventoryException('La operación dejaría el stock en negativo. Disponible: ' . $this->formatQuantity($previous) . ' ' . $stock['symbol'] . '.');
            }

            $this->repository->updateQuantity($productId, $warehouseId, $resulting);
            return $this->repository->insertMovement([
                'product' => $productId,
                'warehouse' => $warehouseId,
                'type' => $type,
                'quantity' => number_format($quantity, 3, '.', ''),
                'previous' => number_format($previous, 3, '.', ''),
                'resulting' => number_format($resulting, 3, '.', ''),
                'observations' => $observations,
                'actor' => $actorUserId ?: null,
                'sourceModule' => $sourceModule,
                'sourceReference' => $sourceReference !== '' ? $sourceReference : null,
                'correlationId' => null,
            ]);
        });
    }

    /** @param array<string,mixed> $input */
    public function transfer(array $input, int $actorUserId): void
    {
        $productId = (int) ($input['idProduct'] ?? 0);
        $from = (int) ($input['idWarehouse'] ?? 0);
        $to = (int) ($input['targetWarehouse'] ?? 0);
        $quantity = $this->decimal($input['quantity'] ?? 0, 'La cantidad');
        $observations = trim((string) ($input['observations'] ?? ''));

        if ($productId < 1 || $from < 1 || $to < 1 || $from === $to || $quantity <= 0) {
            throw new InventoryException('Seleccioná un producto, dos depósitos diferentes y una cantidad válida.');
        }
        if ($observations === '' || mb_strlen($observations) > 500) {
            throw new InventoryException('Ingresá una observación de hasta 500 caracteres.');
        }

        $this->repository->transaction(function () use ($productId, $from, $to, $quantity, $observations, $actorUserId): void {
            $ordered = [$from, $to];
            sort($ordered, SORT_NUMERIC);
            $locked = [];
            foreach ($ordered as $warehouseId) {
                $locked[$warehouseId] = $this->repository->lockStock($productId, $warehouseId);
            }
            $source = $locked[$from] ?? null;
            $target = $locked[$to] ?? null;
            if (!$source || !$target) {
                throw new InventoryException('El producto debe tener existencias creadas en ambos depósitos.');
            }
            if ((int) $source['allowsDecimals'] !== 1 && abs($quantity - round($quantity)) > 0.0001) {
                throw new InventoryException('La unidad de este producto solo admite cantidades enteras.');
            }
            $sourceResult = round((float) $source['quantity'] - $quantity, 3);
            $targetResult = round((float) $target['quantity'] + $quantity, 3);
            if ($sourceResult < 0) {
                throw new InventoryException('El depósito de origen no tiene stock suficiente.');
            }

            $correlation = $this->uuid();
            $this->repository->updateQuantity($productId, $from, $sourceResult);
            $this->repository->updateQuantity($productId, $to, $targetResult);
            $common = [
                'product' => $productId,
                'quantity' => number_format($quantity, 3, '.', ''),
                'observations' => $observations,
                'actor' => $actorUserId ?: null,
                'sourceModule' => 'INVENTARIO',
                'sourceReference' => null,
                'correlationId' => $correlation,
            ];
            $this->repository->insertMovement($common + [
                'warehouse' => $from,
                'type' => 'TRANSFERENCIA_SALIDA',
                'previous' => number_format((float) $source['quantity'], 3, '.', ''),
                'resulting' => number_format($sourceResult, 3, '.', ''),
            ]);
            $this->repository->insertMovement($common + [
                'warehouse' => $to,
                'type' => 'TRANSFERENCIA_ENTRADA',
                'previous' => number_format((float) $target['quantity'], 3, '.', ''),
                'resulting' => number_format($targetResult, 3, '.', ''),
            ]);
        });
    }

    /** @param array<string,mixed> $input */
    public function addCatalog(string $catalog, array $input): void
    {
        try {
            if ($catalog === 'category') {
                $name = $this->requiredText($input['name'] ?? '', 'Ingresá el nombre de la categoría.', 100);
                $this->repository->addCategory($name, trim((string) ($input['description'] ?? '')));
                return;
            }
            if ($catalog === 'unit') {
                $code = mb_strtoupper($this->requiredText($input['code'] ?? '', 'Ingresá el código de la unidad.', 20));
                $name = $this->requiredText($input['name'] ?? '', 'Ingresá el nombre de la unidad.', 80);
                $symbol = $this->requiredText($input['symbol'] ?? '', 'Ingresá el símbolo de la unidad.', 15);
                $this->repository->addUnit($code, $name, $symbol, isset($input['allowsDecimals']));
                return;
            }
            if ($catalog === 'warehouse') {
                $name = $this->requiredText($input['name'] ?? '', 'Ingresá el nombre del depósito.', 100);
                $this->repository->transaction(function () use ($name, $input): void {
                    $this->repository->addWarehouse(
                        $name,
                        trim((string) ($input['address'] ?? '')),
                        trim((string) ($input['description'] ?? ''))
                    );
                    $warehouseId = 0;
                    foreach ($this->repository->warehouses() as $warehouse) {
                        if ((string) $warehouse['name'] === $name) {
                            $warehouseId = (int) $warehouse['idWarehouse'];
                            break;
                        }
                    }
                    if ($warehouseId > 0) {
                        foreach ($this->repository->activeProductIds() as $productId) {
                            $this->repository->ensureStock($productId, $warehouseId, 0);
                        }
                    }
                });
                return;
            }
        } catch (PDOException $error) {
            if ((string) $error->getCode() === '23000') {
                throw new InventoryException('Ya existe un registro con esos datos.');
            }
            throw $error;
        }

        throw new InventoryException('El catálogo indicado no es válido.');
    }

    /** @return array<string,string> */
    public function decodeAttributes(?string $json): array
    {
        if (!$json) {
            return [];
        }
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }
        $result = [];
        foreach ($decoded as $key => $value) {
            if (is_scalar($value)) {
                $result[(string) $key] = (string) $value;
            }
        }
        return $result;
    }

    public function attributesForForm(?string $json): string
    {
        $lines = [];
        foreach ($this->decodeAttributes($json) as $key => $value) {
            $lines[] = $key . '=' . $value;
        }
        return implode("\n", $lines);
    }

    public function formatQuantity(float $quantity): string
    {
        return rtrim(rtrim(number_format($quantity, 3, ',', '.'), '0'), ',');
    }

    /** @return array<string,string> */
    private function parseAttributes(string $input): array
    {
        $attributes = [];
        foreach (preg_split('/\R/', trim($input)) ?: [] as $line) {
            if (trim($line) === '') {
                continue;
            }
            [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
            $key = trim($key);
            $value = trim($value);
            if ($key === '' || $value === '') {
                throw new InventoryException('Los atributos personalizados deben escribirse como Nombre=Valor, uno por línea.');
            }
            if (mb_strlen($key) > 80 || mb_strlen($value) > 180) {
                throw new InventoryException('Los atributos personalizados son demasiado extensos.');
            }
            $attributes[$key] = $value;
        }
        return $attributes;
    }

    private function decimal(mixed $value, string $label): float
    {
        $normalized = str_replace(',', '.', trim((string) $value));
        if ($normalized === '' || !preg_match('/^\d+(?:\.\d{1,3})?$/', $normalized)) {
            if ($normalized === '' || $normalized === '0') {
                return 0.0;
            }
            throw new InventoryException($label . ' debe ser un número válido con hasta tres decimales.');
        }
        return round((float) $normalized, 3);
    }

    private function requiredText(mixed $value, string $message, int $maxLength): string
    {
        $text = trim((string) $value);
        if ($text === '' || mb_strlen($text) > $maxLength) {
            throw new InventoryException($message);
        }
        return $text;
    }

    private function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);
        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20);
    }
}
