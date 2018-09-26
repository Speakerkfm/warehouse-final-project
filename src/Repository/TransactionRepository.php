<?php

namespace App\Repository;

use App\Model\Transaction;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;

class TransactionRepository extends AbstractRepository
{
    /**
     * @param $warehouse_from_id
     * @param $warehouse_to_id
     * @param $movement_type
     * @return Transaction
     * @throws
     */
    public function CreateTransaction($warehouse_from_id, $warehouse_to_id, $movement_type)
    {
        $Transaction = new Transaction(null, $warehouse_from_id, $warehouse_to_id, $movement_type, null, 0);
        $this->dbConnection->executeQuery(
            'INSERT INTO transactions (warehouse_from_id, warehouse_to_id, movement_type) VALUES (?, ?, ?)',
            [$Transaction->getWarehouseFromId(), $Transaction->getWarehouseToId(), $Transaction->getMovementType()]
        );

        $Transaction->setId($this->dbConnection->lastInsertId());

        return $Transaction;
    }

    /**
     * @param $transaction Transaction
     * @param $product_id int
     * @param $count int
     * @throws
     */
    public function AddProductToTransaction($transaction, $product_id, $count)
    {
        $row = $this->dbConnection->fetchAssoc(
            'SELECT * FROM products_on_transaction 
                       WHERE transaction_id = ? AND product_id = ?',
            [$transaction->getId(), $product_id]);

        if (!isset($row['count'])) {
            $this->dbConnection->executeQuery(
                'INSERT INTO products_on_transaction (transaction_id, product_id, count) VALUES (?, ?, ?)',
                [
                    $transaction->getId(),
                    $product_id,
                    $count
                ]
            );
        } else {
            $this->dbConnection->executeQuery(
                'UPDATE products_on_transaction SET count = ? WHERE transaction_id = ? AND product_id = ?',
                [
                    $count + $row['count'],
                    $transaction->getId(),
                    $product_id
                ]
            );
        }
    }

    /**
     * @param $warehouse_id int
     * @param $date String
     * @return Transaction[]
     * @throws
     */
    public function GetTransactionsOnWarehouse($warehouse_id, $date)
    {
        $transactions = [];

        $rows = $this->dbConnection->executeQuery(
            'SELECT * FROM transactions 
                   WHERE (warehouse_from_id = ? OR warehouse_to_id = ?) AND date <= ?',
            [$warehouse_id, $warehouse_id, $date]
        );

        while ($row = $rows->fetch(\PDO::FETCH_ASSOC)){
            $transactions[] = new Transaction(
                $row['id'],
                $row['warehouse_from_id'],
                $row['warehouse_to_id'],
                $row['movement_type'],
                $row['date'],
                $row['total_count']);
        }

        return $transactions;
    }

    /**
     * @param $product_id int
     * @param $date string
     * @return Transaction[]
     * @throws
     */
    public function GetTransactionsOnProduct($product_id, $date)
    {
        $transactions = [];

        $rows = $this->dbConnection->executeQuery(
            'SELECT * FROM transactions, products_on_transaction 
                        WHERE transactions.id = transaction_id AND product_id = ? AND date <= ?',
            [$product_id, $date]
        );

        while ($row = $rows->fetch(\PDO::FETCH_ASSOC)){
            $transactions[] = new Transaction(
                $row['id'],
                $row['warehouse_from_id'],
                $row['warehouse_to_id'],
                $row['movement_type'],
                $row['date'],
                $row['total_count']);
        }

        return $transactions;
    }

    /**
     * @param $transaction Transaction
     * @throws
     * @return array
     */
    public function GetProductList($transaction)
    {
        $products_list = [];

        $products = $this->dbConnection->executeQuery(
            'SELECT * FROM products_on_transaction WHERE transaction_id = ?',
            [$transaction->getId()]
        );

        while ($product = $products->fetch(\PDO::FETCH_ASSOC)) {
            $products_list[$product['product_id']] = [
                'count' => $product['count'],
                'amount' => $product['amount']
            ];
        }

        return $products_list;
    }

    /**
     * @param $transaction Transaction
     * @param $product_id int
     * @throws
     * @return array
     */
    public function GetProduct($transaction, $product_id)
    {
        $product = $this->dbConnection->fetchAssoc(
            'SELECT * FROM products_on_transaction WHERE transaction_id = ? AND product_id = ?',
            [$transaction->getId(), $product_id]
        );

        $products_list[$product_id] = [
            'count' => $product['count'],
            'amount' => $product['amount']
        ];

        return $products_list;
    }

    /**
     * @param $warehouse_id int
     * @throws
     */
    public function CheckWarehouseLogs($warehouse_id)
    {
        $movements_count = $this->dbConnection->fetchAssoc(
            'SELECT COUNT(*) FROM transactions
                        WHERE warehouse_from_id = ? OR warehouse_to_id = ?',
            [$warehouse_id, $warehouse_id]
        )['COUNT(*)'];

        if ($movements_count > 0) {
            throw new \InvalidArgumentException('This warehouse has logs');
        }
    }

    /**
     * @param $product_id int
     * @throws
     */
    public function CheckProductLogs($product_id)
    {
        $movements_count = $this->dbConnection->fetchAssoc(
            'SELECT COUNT(*) FROM transactions, products_on_transaction 
                        WHERE transactions.id = transaction_id AND product_id = ?',
            [$product_id]
        )['COUNT(*)'];

        if ($movements_count > 0) {
            throw new \InvalidArgumentException('This product has logs');
        }
    }

    public function StartTransaction()
    {
        $this->dbConnection->beginTransaction();
    }

    public function CommitTransaction()
    {
        $this->dbConnection->commit();
    }

    public function RollbackTransaction()
    {
        $this->dbConnection->rollBack();
    }
}