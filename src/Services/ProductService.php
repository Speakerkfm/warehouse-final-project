<?php
/**
 * Created by PhpStorm.
 * User: usanin
 * Date: 07.09.2018
 * Time: 20:20
 */

namespace App\Services;

use App\Model\Product;
use http\Exception\InvalidArgumentException;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;

class ProductService extends AbstractService
{
    /**
     * @param $user_id
     * @return product[]
     **/
    public function GetProductList($user_id)
    {
        $products =[];

        $rows = $this->dbConnection->executeQuery(
            'SELECT products.id, name, price, size, type_name 
                    FROM products, types 
                    WHERE type_id = types.id AND products.user_owner_id = ?',
            [$user_id]
        );

        while ($row = $rows->fetch(\PDO::FETCH_ASSOC)){
            $products[] = new Product($row['id'], $row['name'], $row['price'], $row['size'], $row['type_name']);
        }

        return $products;
    }

    public function GetProduct($user_id, $product_id)
    {
        $row = $this->dbConnection->executeQuery(
            'SELECT * FROM products, types WHERE products.id = ? AND types.id = type_id',
            [$product_id]
        )->fetch(\PDO::FETCH_ASSOC);

        if (!isset($row['id'])){
            throw new \InvalidArgumentException('Product does not exist!');
        } elseif ($row['user_owner_id'] != $user_id){
            throw new \InvalidArgumentException('Wrong access!');
        } else {
            $product = new Product($row['id'], $row['name'], $row['price'], $row['size'], $row['type_name']);
        }

        return $product;
    }

    public function CreateProduct($user_id, $name, $price, $size, $type_name)
    {
        $type_id = $this->dbConnection->executeQuery(
            'SELECT id FROM types WHERE type_name = ?',
            [$type_name]
        )->fetch(\PDO::FETCH_ASSOC)['id'];

        if (!isset($type_id)){
            throw new \InvalidArgumentException('Type does not exist!');
        } else {
            $this->dbConnection->executeQuery(
                'INSERT INTO products (name, price, size, type_id, user_owner_id) VALUES (?, ?, ?, ?, ?)',
                [$name, $price, $size, $type_id, $user_id]
            );
        }

        return $this->dbConnection->lastInsertId();
    }

    public function UpdateProduct($user_id, $product_id, $name, $price, $size, $type_name)
    {
        $type_id = $this->dbConnection->executeQuery(
            'SELECT id FROM types WHERE type_name = ?',
            [$type_name]
        )->fetch(\PDO::FETCH_ASSOC)['id'];

        if (!isset($type_id)){
            throw new \InvalidArgumentException('Type does not exist!');
        } else {
            $owner_id = $this->dbConnection->executeQuery(
                'SELECT * FROM products WHERE id = ?',
                [$product_id]
            )->fetch(\PDO::FETCH_ASSOC)['user_owner_id'];
            if ($user_id <> $owner_id){
                throw new \InvalidArgumentException('Wrong access!');
            } else {
                $this->dbConnection->executeQuery(
                    'UPDATE products SET name = ?, price = ?, size = ?, type_id = ? WHERE id = ?',
                    [$name, $price, $size, $type_id, $product_id]
                );

                $result = 'Product updated!';
            }
        }

        return $result;
    }

    public function GetLogs($user_id, $product_id)
    {
        $row = $this->dbConnection->executeQuery(
            'SELECT * FROM products WHERE id = ?',
            [$product_id]
        )->fetch(\PDO::FETCH_ASSOC);

        $transactions_list = [];

        if ($row['user_owner_id'] != $user_id){
            throw new \InvalidArgumentException('Wrong access!');
        } else {
            $transactions = $this->dbConnection->executeQuery(
                'SELECT * FROM transactions, products_on_transaction 
                        WHERE transactions.id = transaction_id AND product_id = ?',
                [$product_id]
            );

            while($transaction = $transactions->fetch(\PDO::FETCH_ASSOC)) {
                switch ($transaction['movement_type']){
                    case 'app':
                        $transactions_list[] = [
                            'id' => $transaction['id'],
                            'movement_type' => $transaction['movement_type'],
                            'warehouse_id' => $transaction['warehouse_to_id'],
                            'date' => $transaction['date'],
                            'balance' => $transaction['balance_to'],
                            'product_id' => $transaction['product_id'],
                            'count' => $transaction['count'],
                            'amount' => $transaction['amount']
                        ];
                        break;
                    case 'detach':
                        $transactions_list[] = [
                            'id' => $transaction['id'],
                            'movement_type' => $transaction['movement_type'],
                            'warehouse_id' => $transaction['warehouse_from_id'],
                            'date' => $transaction['date'],
                            'balance' => $transaction['balance_from'],
                            'product_id' => $transaction['product_id'],
                            'count' => $transaction['count'],
                            'amount' => $transaction['amount']
                        ];
                        break;
                    case 'move':
                        $transactions_list[] = [
                            'id' => $transaction['id'],
                            'movement_type' => $transaction['movement_type'],
                            'warehouse_from_id' => $transaction['warehouse_from_id'],
                            'warehouse_to_id' => $transaction['warehouse_to_id'],
                            'date' => $transaction['date'],
                            'balance_from' => $transaction['balance_from'],
                            'balance_to' => $transaction['balance_to'],
                            'product_id' => $transaction['product_id'],
                            'count' => $transaction['count'],
                            'amount' => $transaction['amount']
                        ];
                        break;
                }
            }
        }

        return $transactions_list;
    }

    public function DeleteProduct($user_id, $product_id)
    {
        $row = $this->dbConnection->executeQuery(
            'SELECT * FROM products WHERE id = ?',
            [$product_id]
        )->fetch(\PDO::FETCH_ASSOC);

        if ($row['user_owner_id'] != $user_id){
            throw new \InvalidArgumentException('Wrong access!');
        } else {
            $movements_count = $this->dbConnection->executeQuery(
                'SELECT COUNT(*) FROM transactions, products_on_transaction 
                        WHERE transactions.id = transaction_id AND product_id = ?',
                [$product_id]
            )->fetch(\PDO::FETCH_ASSOC)['COUNT(*)'];

            if ($movements_count > 0){
                throw new \InvalidArgumentException('You can not delete product which has logs');
            } else {
                $products_count = $this->dbConnection->executeQuery(
                    'SELECT COUNT(*) FROM products_on_warehouse WHERE product_id = ?',
                    [$product_id]
                )->fetch(\PDO::FETCH_ASSOC)['COUNT(*)'];

                if ($products_count > 0){
                    throw new \InvalidArgumentException('You can not delete product which is on warehouse!');
                } else {
                    $this->dbConnection->executeQuery(
                        'DELETE FROM products WHERE id = ?',
                        [$product_id]
                    );
                }
            }
        }
    }

    public function GetAvailableInfo($user_id, $product_id)
    {
        $row = $this->dbConnection->executeQuery(
            'SELECT * FROM products WHERE id = ?',
            [$product_id]
        )->fetch(\PDO::FETCH_ASSOC);

        if ($row['user_owner_id'] != $user_id){
            throw new \InvalidArgumentException('Wrong access!');
        } else {
            $warehouses_list = [];

            $warehouses = $this->dbConnection->executeQuery(
                'SELECT * FROM products_on_warehouse  WHERE product_id =?',
                [$product_id]
            );

            $price = $this->dbConnection->executeQuery(
                'SELECT * FROM products  WHERE id =?',
                [$product_id]
            )->fetch(\PDO::FETCH_ASSOC)['price'];

            $total_cost = 0;

            while ($warehouse = $warehouses->fetch(\PDO::FETCH_ASSOC)) {
                $warehouses_list[] = [
                    'id' => $warehouse['warehouse_id'],
                    'count' => $warehouse['count']
                ];
                $total_cost += $warehouse['count'] * $price;
            }
        }

        $jsonResult = [
            'total_cost' => $total_cost,
            'warehouses_list' => $warehouses_list
        ];

        return $jsonResult;
    }

    public function GetAvailableOnDate($user_id, $product_id, $date)
    {
        $row = $this->dbConnection->executeQuery(
            'SELECT * FROM products WHERE id = ?',
            [$product_id]
        )->fetch(\PDO::FETCH_ASSOC);

        if ($row['user_owner_id'] != $user_id){
            throw new \InvalidArgumentException('Wrong access!');
        } else {
            $warehouses = $this->dbConnection->executeQuery(
                'SELECT * FROM warehouses WHERE user_id = ?',
                [$user_id]
            );

            $warehouses_list = [];

            while ($warehouse = $warehouses->fetch(\PDO::FETCH_ASSOC)) {
                $warehouses_list[$warehouse['id']] = 0;
            }


            $transactions = $this->dbConnection->executeQuery(
                'SELECT * FROM transactions, products_on_transaction 
                        WHERE transactions.id = transaction_id AND product_id = ? AND date <= ?',
                [$product_id, $date]
            );

            $total_cost = 0;

            $product_price = $this->dbConnection->executeQuery(
                'SELECT price FROM products WHERE id = ?',
                [$product_id]
            )->fetch(\PDO::FETCH_ASSOC)['price'];

            while ($transaction = $transactions->fetch(\PDO::FETCH_ASSOC)){

                switch ($transaction['movement_type']) {
                    case 'app':
                        $warehouses_list[$transaction['warehouse_to_id']] += $transaction['count'];
                        $total_cost += $product_price * $transaction['count'];
                        break;
                    case 'detach':
                        $warehouses_list[$transaction['warehouse_from_id']] -= $transaction['count'];
                        $total_cost -= $product_price * $transaction['count'];
                        break;
                    case 'move':
                        $warehouses_list[$transaction['warehouse_to_id']] += $transaction['count'];
                        $warehouses_list[$transaction['warehouse_from_id']] -= $transaction['count'];
                        break;
                }

            }

            foreach (array_keys($warehouses_list) as $key){
                if ($warehouses_list[$key] == 0){
                    unset($warehouses_list[$key]);
                }
            }

            $jsonResult = [
                'total_cost' => $total_cost,
                'warehouses' => $warehouses_list
            ];

            return $jsonResult;
        }
    }
}