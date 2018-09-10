<?php
/**
 * Created by PhpStorm.
 * User: usanin
 * Date: 07.09.2018
 * Time: 17:02
 */

namespace App\Services;


use App\Model\Warehouse;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;

class WarehouseService extends AbstractService
{
    /**
     * @param $user_id int
     * @return Warehouse[]
     */
    public function GetWarehouseList($user_id)
    {
        $warehouses = [];

        $rows = $this->dbConnection->executeQuery(
            'SELECT * FROM warehouses WHERE user_id = ?',
            [$user_id]
        );

        while ($row = $rows->fetch(\PDO::FETCH_ASSOC)){
            $warehouses[] = new Warehouse($row['id'], $row['address'], $row['capacity'], $row['total_size'], $row['balance']);
        }

        return $warehouses;
    }

    public function GetWarehouse($user_id, $warehouse_id)
    {
        $row = $this->dbConnection->executeQuery(
            'SELECT * FROM warehouses WHERE id = ?',
            [$warehouse_id]
        )->fetch(\PDO::FETCH_ASSOC);

        if (!isset($row['id'])){
            throw new \InvalidArgumentException('Warehouse does not exist!');
        } elseif ($row['user_id'] != $user_id){
            throw new \InvalidArgumentException('Wrong access!');
        } else {
            $warehouse = new Warehouse($row['id'], $row['address'], $row['capacity'], $row['total_size'], $row['balance']);
        }

        return $warehouse;
    }

    public function CreateWarehouse($user_id, $address, $capacity)
    {
        $address_duplicates = $this->dbConnection->executeQuery(
            'SELECT COUNT(*) FROM warehouses WHERE address = ?',
            [$address]
        )->fetch(\PDO::FETCH_ASSOC)['COUNT(*)'];

        if ($address_duplicates > 0){
            throw new \InvalidArgumentException('Warehouse with this address is already exist!');
        } else {
            $this->dbConnection->executeQuery(
                'INSERT INTO warehouses (address, capacity, user_id) VALUES (?, ?, ?)',
                [$address, $capacity, $user_id]
            );
        }

        return $this->dbConnection->lastInsertId();
    }

    public function UpdateWarehouse($user_id, $warehouse_id, $address, $capacity)
    {
        $row = $this->dbConnection->executeQuery(
            'SELECT * FROM warehouses WHERE id = ?',
            [$warehouse_id]
        )->fetch(\PDO::FETCH_ASSOC);

        if (!isset($row['id'])){
            throw new \InvalidArgumentException('Warehouse does not exist!');
        } elseif ($row['user_id'] != $user_id){
            throw new \InvalidArgumentException('Wrong access!');
        } else {
            $address_duplicates = $this->dbConnection->executeQuery(
                'SELECT COUNT(*) FROM warehouses WHERE address = ? AND id <> ?',
                [$address, $warehouse_id]
            )->fetch(\PDO::FETCH_ASSOC)['COUNT(*)'];

            if ($row['total_size'] > $capacity){
                throw new \InvalidArgumentException(('Too much products to set this capacity'));
            } elseif ($address_duplicates > 0) {
                throw new \InvalidArgumentException('Warehouse with this address is already exist!');
            } else {
                $this->dbConnection->executeQuery(
                    'UPDATE warehouses SET address = ?, capacity = ? WHERE id = ?',
                    [$address, $capacity, $warehouse_id]
                );
                $result = 'Warehouse updated!';
            }
        }

        return $result;
    }

    public function AppProduct($user_id, $warehouse_id, $product_id, $count)
    {
        $owner_id = $this->dbConnection->executeQuery(
            'SELECT user_id FROM warehouses WHERE id = ?',
            [$warehouse_id]
        )->fetch(\PDO::FETCH_ASSOC)['user_id'];

        if ($owner_id != $user_id){
            throw new \InvalidArgumentException('Wrong access!');
        } else {
            $product = $this->dbConnection->executeQuery(
                'SELECT * FROM products WHERE id = ? AND  user_owner_id = ?',
                [$product_id, $user_id]
            )->fetch(\PDO::FETCH_ASSOC);

            if (!isset($product['id'])){
                throw new \InvalidArgumentException('Product does not exist '.$product_id);
            } else {
                $product_full_size = $count * $product['size'];

                $available_size = $this->dbConnection->executeQuery(
                    'SELECT (capacity - total_size) AS available_size FROM warehouses WHERE id = ?',
                    [$warehouse_id]
                )->fetch(\PDO::FETCH_ASSOC)['available_size'];

                if ($product_full_size > $available_size) {
                    throw new \InvalidArgumentException('Not enough space on warehouse' . $warehouse_id);
                } else {
                    $current_count = $this->dbConnection->executeQuery(
                        'SELECT count FROM products_on_warehouse WHERE product_id = ? AND warehouse_id = ?',
                        [$product_id, $warehouse_id]
                    )->fetch(\PDO::FETCH_ASSOC)['count'];
                    if ($current_count > 0) {
                        $this->dbConnection->executeQuery(
                            'UPDATE products_on_warehouse SET count = count + ? WHERE product_id = ? AND warehouse_id = ?',
                            [$count, $product_id, $warehouse_id]
                        );
                    } else {
                        $this->dbConnection->executeQuery(
                            'INSERT INTO products_on_warehouse (product_id, warehouse_id, count) VALUES (?, ?, ?)',
                            [$product_id, $warehouse_id, $count]
                        );
                    }
                }
            }
        }
    }

    public function DetachProduct($user_id, $warehouse_id, $product_id, $count)
    {
        $owner_id = $this->dbConnection->executeQuery(
            'SELECT user_id FROM warehouses WHERE id = ?',
            [$warehouse_id]
        )->fetch(\PDO::FETCH_ASSOC)['user_id'];

        if ($owner_id != $user_id){
            throw new \InvalidArgumentException('Wrong access!');
        } else {
            $current_count = $this->dbConnection->executeQuery(
                'SELECT count FROM products_on_warehouse WHERE product_id = ? AND warehouse_id = ?',
                [$product_id, $warehouse_id]
            )->fetch(\PDO::FETCH_ASSOC)['count'];

            if ($current_count < $count){
                throw new \InvalidArgumentException('Not enough products '.$product_id.' on warehouse '.$warehouse_id);
            } elseif ($current_count > $count) {
                $this->dbConnection->executeQuery(
                    'UPDATE products_on_warehouse SET count = count - ? WHERE product_id = ? and warehouse_id = ?',
                    [$count, $product_id, $warehouse_id]
                );
            } else {
                $this->dbConnection->executeQuery(
                    'DELETE FROM products_on_warehouse WHERE product_id = ? AND warehouse_id = ?',
                    [$product_id, $warehouse_id]
                );
            }
        }
    }

    public function MoveProducts($user_id, $products_in_transaction, $warehouses, $movement_type)
    {
        try {
            $this->dbConnection->beginTransaction();
            try {
                $this->dbConnection->executeQuery(
                    'INSERT INTO transactions (warehouse_from_id, warehouse_to_id, movement_type) VALUES (?, ?, ?)',
                    [$warehouses['from'], $warehouses['to'], $movement_type]
                );
                $transaction_id = $this->dbConnection->lastInsertId();
            }catch (\Exception $e){
                throw new \InvalidArgumentException('Warehouse does not exist123');
            }
            foreach ($products_in_transaction as $item) {
                $product_id = $item['id'];
                $count = $item['count'];
                switch ($movement_type) {
                    case 'app':
                        $this->AppProduct($user_id, $warehouses['to'], $product_id, $count);
                        break;
                    case 'detach':
                        $this->DetachProduct($user_id, $warehouses['from'], $product_id, $count);
                        break;
                    case 'move':
                        if ($warehouses['from'] == $warehouses['to']){
                            throw new \InvalidArgumentException('Warehouses are the same!');
                        } else {
                            $this->DetachProduct($user_id, $warehouses['from'], $product_id, $count);
                            $this->AppProduct($user_id, $warehouses['to'], $product_id, $count);
                        }
                        break;
                    default:
                        throw new \InvalidArgumentException('Wrong movement type!');
                        break;
                }
                $this->dbConnection->executeQuery(
                    'INSERT INTO products_on_transaction (transaction_id, product_id, count) VALUES (?, ?, ?)',
                    [$transaction_id, $product_id, $count]
                );
            }
            $this->dbConnection->commit();
        } catch (\Exception $e){
            $this->dbConnection->rollBack();
            throw $e;
        }
    }

    public function GetLogs($user_id, $warehouse_id)
    {
        $row = $this->dbConnection->executeQuery(
            'SELECT * FROM warehouses WHERE id = ?',
            [$warehouse_id]
        )->fetch(\PDO::FETCH_ASSOC);

        $transactions_list = [];

        if ($row['user_id'] != $user_id){
            throw new \InvalidArgumentException('Wrong access!');
        } else {
            $transactions = $this->dbConnection->executeQuery(
                'SELECT * FROM transactions WHERE warehouse_from_id = ? OR warehouse_to_id = ?',
                [$warehouse_id, $warehouse_id]
            );

            while($transaction = $transactions->fetch(\PDO::FETCH_ASSOC)){
                $products = $this->dbConnection->executeQuery(
                    'SELECT * FROM products_on_transaction WHERE transaction_id = ?',
                    [$transaction['id']]
                );

                $products_list = [];

                while($product = $products->fetch(\PDO::FETCH_ASSOC)){
                    $products_list[] = [
                        'id' => $product['product_id'],
                        'count' => $product['count'],
                        'amount' => $product['amount']
                    ];
                }

                switch ($transaction['movement_type']){
                    case 'app':
                        $transactions_list[] = [
                            'id' => $transaction['id'],
                            'movement_type' => $transaction['movement_type'],
                            'warehouse_id' => $transaction['warehouse_to_id'],
                            'date' => $transaction['date'],
                            'balance' => $transaction['balance_to'],
                            'products' => $products_list
                            ];
                        break;
                    case 'detach':
                        $transactions_list[] = [
                            'id' => $transaction['id'],
                            'movement_type' => $transaction['movement_type'],
                            'warehouse_id' => $transaction['warehouse_from_id'],
                            'date' => $transaction['date'],
                            'balance' => $transaction['balance_from'],
                            'products' => $products_list
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
                            'products' => $products_list
                        ];
                        break;
                }
            }
        }

        return $transactions_list;
    }

    public function DeleteWarehouse($user_id, $warehouse_id)
    {
        $row = $this->dbConnection->executeQuery(
            'SELECT * FROM warehouses WHERE id = ?',
            [$warehouse_id]
        )->fetch(\PDO::FETCH_ASSOC);

        if ($row['user_id'] != $user_id){
            throw new \InvalidArgumentException('Wrong access!');
        } else {
            $movements_count = $this->dbConnection->executeQuery(
                'SELECT COUNT(*) FROM transactions
                        WHERE warehouse_from_id = ? OR warehouse_to_id = ?',
                [$warehouse_id, $warehouse_id]
            )->fetch(\PDO::FETCH_ASSOC)['COUNT(*)'];

            if ($movements_count > 0){
                throw new \InvalidArgumentException('You can not delete warehouse which has logs');
            } else {
                $filled_warehouses_count = $this->dbConnection->executeQuery(
                    'SELECT COUNT(*) FROM products_on_warehouse WHERE warehouse_id = ?',
                    [$warehouse_id]
                )->fetch(\PDO::FETCH_ASSOC)['COUNT(*)'];

                if ($filled_warehouses_count > 0){
                    throw new \InvalidArgumentException('You can not delete warehouse which has products!');
                } else {
                    $this->dbConnection->executeQuery(
                        'DELETE FROM warehouses WHERE id = ?',
                        [$warehouse_id]
                    );
                }
            }
        }
    }

    public function GetProductsList($user_id, $warehouse_id)
    {
        $row = $this->dbConnection->executeQuery(
            'SELECT * FROM warehouses WHERE id = ?',
            [$warehouse_id]
        )->fetch(\PDO::FETCH_ASSOC);

        if ($row['user_id'] != $user_id){
            throw new \InvalidArgumentException('Wrong access!');
        } else {
            $products_list = [];

            $products = $this->dbConnection->executeQuery(
                'SELECT * FROM products_on_warehouse  WHERE warehouse_id =?',
                [$warehouse_id]
            );

            while ($product = $products->fetch(\PDO::FETCH_ASSOC)) {
                $products_list[] = [
                    'id' => $product['product_id'],
                    'count' => $product['count']
                ];
            }

            $balance = $row['balance'];
        }
        $jsonResult = [
            'balance' => $balance,
            'products_list' => $products_list
        ];

        return $jsonResult;
    }

    public function GetProductListOnDate($user_id, $warehouse_id, $date)
    {
        $row = $this->dbConnection->executeQuery(
            'SELECT * FROM warehouses WHERE id = ?',
            [$warehouse_id]
        )->fetch(\PDO::FETCH_ASSOC);

        if ($row['user_id'] != $user_id){
            throw new \InvalidArgumentException('Wrong access!');
        } else {
            $products = $this->dbConnection->executeQuery(
                'SELECT * FROM products WHERE user_owner_id = ?',
                [$user_id]
            );

            $products_list = [];

            while ($product = $products->fetch(\PDO::FETCH_ASSOC)) {
                $products_list[$product['id']] = 0;
            }

            $transactions = $this->dbConnection->executeQuery(
                'SELECT * FROM transactions WHERE (warehouse_to_id = ? OR warehouse_from_id = ?) AND date <= ?',
                [$warehouse_id, $warehouse_id, $date]
            );

            while ($transaction = $transactions->fetch(\PDO::FETCH_ASSOC)){
                $products = $this->dbConnection->executeQuery(
                    'SELECT * FROM products_on_transaction WHERE transaction_id = ?',
                    [$transaction['id']]
                );

                switch ($transaction['movement_type']) {
                    case 'app':
                        while ($product = $products->fetch(\PDO::FETCH_ASSOC)) {
                            $products_list[$product['product_id']] += $product['count'];
                        }
                        break;
                    case 'detach':
                        while ($product = $products->fetch(\PDO::FETCH_ASSOC)) {
                            $products_list[$product['product_id']] -= $product['count'];
                        }
                        break;
                    case 'move':
                        if ($transaction['warehouse_to_id'] == $warehouse_id){
                            while ($product = $products->fetch(\PDO::FETCH_ASSOC)) {
                                $products_list[$product['product_id']] += $product['count'];
                            }
                        } else {
                            while ($product = $products->fetch(\PDO::FETCH_ASSOC)) {
                                $products_list[$product['product_id']] -= $product['count'];
                            }
                        }
                        break;
                }

                $last_transaction = $transaction;
            }

            if (isset($last_transaction)) {
                if ($last_transaction['warehouse_to_id'] == $warehouse_id) {
                    $balance = $last_transaction['balance_to'];
                } else {
                    $balance = $last_transaction['balance_from'];
                }
            } else {
                $balance = 0;
            }

            foreach (array_keys($products_list) as $key){
                if ($products_list[$key] == 0){
                    unset($products_list[$key]);
                }
            }

            $jsonResult = [
                'balance' => $balance,
                'products' => $products_list
            ];

            return $jsonResult;
        }
    }
}