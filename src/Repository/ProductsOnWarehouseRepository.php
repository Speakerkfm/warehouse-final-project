<?php

namespace App\Repository;

use App\Model\Product;
use App\Model\ProductOnWarehouse;
use App\Model\Warehouse;

class ProductsOnWarehouseRepository extends AbstractRepository
{
    /**
     * @param $product_id string
     * @param $warehouse_id string
     * @return ProductOnWarehouse|null
     * @throws
     */
    public function GetProductOnWarehouse($product_id, $warehouse_id)
    {
        $row = $this->dbConnection->fetchAssoc(
            'SELECT * FROM products_on_warehouse WHERE product_id = ? AND warehouse_id = ?',
            [$product_id, $warehouse_id]
        );

        if ($row['product_id'] != null) {
            return new ProductOnWarehouse($row['product_id'], $row['warehouse_id'], $row['count']);
        } else {
            throw new \InvalidArgumentException('Not enough products '.$product_id.' on warehouse '.$warehouse_id);
        }
    }

    /**
     * @param $ProductOnWarehouse ProductOnWarehouse
     * @throws
     */
    public function UpdateProductOnWarehouse($ProductOnWarehouse)
    {
        $this->dbConnection->executeQuery(
            'UPDATE products_on_warehouse SET count = ? WHERE product_id = ? AND warehouse_id = ?',
            [$ProductOnWarehouse->getCount(), $ProductOnWarehouse->getProductId(), $ProductOnWarehouse->getWarehouseId()]
        );
    }

    /**
     * @param $product_id int
     * @param $warehouse_id int
     * @param $count int
     * @return ProductOnWarehouse ProductOnWarehouse
     * @throws
     */
    public function CreateProductOnWarehouse($product_id, $warehouse_id, $count)
    {
        $ProductOnWarehouse = new ProductOnWarehouse($product_id, $warehouse_id, $count);

        $this->dbConnection->executeQuery(
            'INSERT INTO products_on_warehouse (product_id, warehouse_id, count) VALUES (?, ?, ?)',
            [$ProductOnWarehouse->getProductId(), $ProductOnWarehouse->getWarehouseId(), $ProductOnWarehouse->getCount()]
        );

        return $ProductOnWarehouse;
    }

    /**
     * @param $ProductOnWarehouse ProductOnWarehouse
     * @throws
     */
    public function DeleteProductOnWarehouse($ProductOnWarehouse)
    {
        $this->dbConnection->executeQuery(
            'DELETE FROM products_on_warehouse WHERE product_id = ? AND warehouse_id = ?',
            [$ProductOnWarehouse->getProductId(), $ProductOnWarehouse->getWarehouseId()]
        );
    }

    /**
     * @param $warehouse Warehouse
     * @return array
     * @throws
     */
    public function GetProductList($warehouse)
    {
        $products_list = [];

        $products = $this->dbConnection->executeQuery(
            'SELECT * FROM products_on_warehouse WHERE warehouse_id = ?',
            [$warehouse->getId()]
        );

        while ($product = $products->fetch(\PDO::FETCH_ASSOC)) {
            $products_list[] = [
                'id' => $product['product_id'],
                'count' => $product['count']
                ];
        }

        return $products_list;
    }

    /**
     * @param $product Product
     * @throws
     * @return array
     */
    public function GetWarehouseList($product)
    {
        $warehouses_list = [];

        $warehouses = $this->dbConnection->executeQuery(
            'SELECT * FROM products_on_warehouse WHERE product_id = ?',
            [$product->getId()]
        );

        $total_cost = 0;

        while ($warehouse = $warehouses->fetch(\PDO::FETCH_ASSOC)) {
            $warehouses_list[] = [
                'id' => $warehouse['warehouse_id'],
                'count' => $warehouse['count']
            ];
            $total_cost += $warehouse['count'] * $product->getPrice();
        }

        $jsonResult = [
            'total_cost' => $total_cost,
            'warehouses_list' => $warehouses_list
        ];

        return $jsonResult;
    }
}