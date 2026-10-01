<?php
declare(strict_types=1);

namespace Sedema\Inventory;

use PDO;

final class InventoryRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    /** @return list<array<string,mixed>> */
    public function warehouses(bool $onlyActive = true): array
    {
        $sql = 'SELECT * FROM inventory_warehouse' . ($onlyActive ? ' WHERE active = 1' : '') . ' ORDER BY name';
        return $this->db->query($sql)->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function categories(bool $onlyActive = true): array
    {
        $sql = 'SELECT * FROM inventory_category' . ($onlyActive ? ' WHERE active = 1' : '') . ' ORDER BY name';
        return $this->db->query($sql)->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function units(bool $onlyActive = true): array
    {
        $sql = 'SELECT * FROM inventory_unit' . ($onlyActive ? ' WHERE active = 1' : '') . ' ORDER BY name';
        return $this->db->query($sql)->fetchAll();
    }

    /** @return array{products:int,stockPositions:int,lowStock:int,outOfStock:int} */
    public function summary(int $warehouseId): array
    {
        $statement = $this->db->prepare(
            'SELECT COUNT(p.idProduct) AS products,
                    COALESCE(SUM(CASE WHEN s.quantity > 0 THEN 1 ELSE 0 END), 0) AS stockPositions,
                    COALESCE(SUM(CASE WHEN s.minimumStock > 0 AND s.quantity <= s.minimumStock THEN 1 ELSE 0 END), 0) AS lowStock,
                    COALESCE(SUM(CASE WHEN s.quantity = 0 THEN 1 ELSE 0 END), 0) AS outOfStock
             FROM inventory_product p
             LEFT JOIN inventory_stock s ON s.idProduct = p.idProduct AND s.idWarehouse = ?
             WHERE p.active = 1'
        );
        $statement->execute([$warehouseId]);
        $row = $statement->fetch() ?: [];

        return [
            'products' => (int) ($row['products'] ?? 0),
            'stockPositions' => (int) ($row['stockPositions'] ?? 0),
            'lowStock' => (int) ($row['lowStock'] ?? 0),
            'outOfStock' => (int) ($row['outOfStock'] ?? 0),
        ];
    }

    /** @return list<array<string,mixed>> */
    public function products(int $warehouseId, string $search = '', int $categoryId = 0, bool $onlyLowStock = false): array
    {
        $conditions = ['p.active = 1'];
        $parameters = ['warehouse' => $warehouseId];

        if ($search !== '') {
            $conditions[] = '(p.code LIKE :searchCode OR p.name LIKE :searchName)';
            $parameters['searchCode'] = '%' . $search . '%';
            $parameters['searchName'] = '%' . $search . '%';
        }
        if ($categoryId > 0) {
            $conditions[] = 'p.idCategory = :category';
            $parameters['category'] = $categoryId;
        }
        if ($onlyLowStock) {
            $conditions[] = 's.minimumStock > 0 AND COALESCE(s.quantity, 0) <= s.minimumStock';
        }

        $statement = $this->db->prepare(
            'SELECT p.idProduct, p.code, p.name, p.description, p.salePrice, p.customAttributes,
                    c.name AS categoryName, u.name AS unitName, u.symbol,
                    COALESCE(s.quantity, 0) AS quantity, COALESCE(s.minimumStock, 0) AS minimumStock
             FROM inventory_product p
             INNER JOIN inventory_category c ON c.idCategory = p.idCategory
             INNER JOIN inventory_unit u ON u.idUnit = p.idUnit
             LEFT JOIN inventory_stock s ON s.idProduct = p.idProduct AND s.idWarehouse = :warehouse
             WHERE ' . implode(' AND ', $conditions) . '
             ORDER BY p.name
             LIMIT 250'
        );
        $statement->execute($parameters);
        return $statement->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function product(int $productId): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM inventory_product WHERE idProduct = ? LIMIT 1');
        $statement->execute([$productId]);
        $product = $statement->fetch();
        return is_array($product) ? $product : null;
    }

    /** @return array<int,float> */
    public function productMinimums(int $productId): array
    {
        $statement = $this->db->prepare('SELECT idWarehouse, minimumStock FROM inventory_stock WHERE idProduct = ?');
        $statement->execute([$productId]);
        $minimums = [];
        foreach ($statement->fetchAll() as $row) {
            $minimums[(int) $row['idWarehouse']] = (float) $row['minimumStock'];
        }
        return $minimums;
    }

    /** @return list<int> */
    public function activeProductIds(): array
    {
        $rows = $this->db->query('SELECT idProduct FROM inventory_product WHERE active = 1 ORDER BY idProduct')->fetchAll();
        return array_map(static fn (array $row): int => (int) $row['idProduct'], $rows);
    }

    /** @param array<string,mixed> $data */
    public function createProduct(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO inventory_product
                (code, name, description, idCategory, idUnit, salePrice, customAttributes, active)
             VALUES (:code, :name, :description, :category, :unit, :price, :attributes, 1)'
        );
        $statement->execute($data);
        return (int) $this->db->lastInsertId();
    }

    /** @param array<string,mixed> $data */
    public function updateProduct(int $productId, array $data): void
    {
        $data['id'] = $productId;
        $statement = $this->db->prepare(
            'UPDATE inventory_product SET
                code = :code, name = :name, description = :description,
                idCategory = :category, idUnit = :unit, salePrice = :price,
                customAttributes = :attributes
             WHERE idProduct = :id'
        );
        $statement->execute($data);
    }

    public function ensureStock(int $productId, int $warehouseId, float $minimumStock = 0): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO inventory_stock (idProduct, idWarehouse, quantity, minimumStock)
             VALUES (?, ?, 0, ?)
             ON DUPLICATE KEY UPDATE minimumStock = VALUES(minimumStock)'
        );
        $statement->execute([$productId, $warehouseId, $minimumStock]);
    }

    public function setProductActive(int $productId, bool $active): void
    {
        $statement = $this->db->prepare('UPDATE inventory_product SET active = ? WHERE idProduct = ?');
        $statement->execute([$active ? 1 : 0, $productId]);
    }

    /** @return array<string,mixed>|null */
    public function lockStock(int $productId, int $warehouseId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT s.quantity, s.minimumStock, p.name AS productName, u.symbol, u.allowsDecimals, w.name AS warehouseName
             FROM inventory_stock s
             INNER JOIN inventory_product p ON p.idProduct = s.idProduct
             INNER JOIN inventory_unit u ON u.idUnit = p.idUnit
             INNER JOIN inventory_warehouse w ON w.idWarehouse = s.idWarehouse
             WHERE s.idProduct = ? AND s.idWarehouse = ? AND p.active = 1 AND w.active = 1
             FOR UPDATE'
        );
        $statement->execute([$productId, $warehouseId]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public function updateQuantity(int $productId, int $warehouseId, float $quantity): void
    {
        $statement = $this->db->prepare(
            'UPDATE inventory_stock SET quantity = ? WHERE idProduct = ? AND idWarehouse = ?'
        );
        $statement->execute([$quantity, $productId, $warehouseId]);
    }

    /** @param array<string,mixed> $data */
    public function insertMovement(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO inventory_movement
                (idProduct, idWarehouse, movementType, quantity, previousQuantity, resultingQuantity,
                 observations, actorUserId, sourceModule, sourceReference, correlationId)
             VALUES
                (:product, :warehouse, :type, :quantity, :previous, :resulting,
                 :observations, :actor, :sourceModule, :sourceReference, :correlationId)'
        );
        $statement->execute($data);
        return (int) $this->db->lastInsertId();
    }

    /** @return list<array<string,mixed>> */
    public function movements(int $warehouseId = 0, int $productId = 0, int $limit = 150): array
    {
        $conditions = [];
        $parameters = [];
        if ($warehouseId > 0) {
            $conditions[] = 'm.idWarehouse = :warehouse';
            $parameters['warehouse'] = $warehouseId;
        }
        if ($productId > 0) {
            $conditions[] = 'm.idProduct = :product';
            $parameters['product'] = $productId;
        }
        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $limit = max(1, min($limit, 250));
        $statement = $this->db->prepare(
            'SELECT m.*, p.code, p.name AS productName, u.symbol, w.name AS warehouseName
             FROM inventory_movement m
             INNER JOIN inventory_product p ON p.idProduct = m.idProduct
             INNER JOIN inventory_unit u ON u.idUnit = p.idUnit
             INNER JOIN inventory_warehouse w ON w.idWarehouse = m.idWarehouse
             ' . $where . '
             ORDER BY m.createdAt DESC, m.idMovement DESC
             LIMIT ' . $limit
        );
        $statement->execute($parameters);
        return $statement->fetchAll();
    }

    public function addCategory(string $name, string $description): void
    {
        $statement = $this->db->prepare('INSERT INTO inventory_category (name, description) VALUES (?, ?)');
        $statement->execute([$name, $description !== '' ? $description : null]);
    }

    public function addUnit(string $code, string $name, string $symbol, bool $allowsDecimals): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO inventory_unit (code, name, symbol, allowsDecimals) VALUES (?, ?, ?, ?)'
        );
        $statement->execute([$code, $name, $symbol, $allowsDecimals ? 1 : 0]);
    }

    public function addWarehouse(string $name, string $address, string $description): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO inventory_warehouse (name, address, description) VALUES (?, ?, ?)'
        );
        $statement->execute([$name, $address !== '' ? $address : null, $description !== '' ? $description : null]);
    }

    public function transaction(callable $callback): mixed
    {
        $this->db->beginTransaction();
        try {
            $result = $callback();
            $this->db->commit();
            return $result;
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }
}
