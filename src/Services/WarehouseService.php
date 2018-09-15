<?php

namespace App\Services;

use App\Model\Warehouse;
use App\Repository\TransactionRepository;
use App\Repository\WarehouseRepository;
use App\Repository\ProductRepository;
use App\Repository\ProductsOnWarehouseRepository;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;

class WarehouseService
{
    /**
     * @var WarehouseRepository
     */
    private $warehouseRepository;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var TransactionRepository
     */
    private $transactionRepository;

    /**
     * @var ProductsOnWarehouseRepository
     */
    private $productOnWarehouseRepository;

    public function __construct(WarehouseRepository $warehouseRepository,
                                ProductRepository $productRepository,
                                TransactionRepository $transactionRepository,
                                ProductsOnWarehouseRepository $productOnWarehouseRepository)
    {
        $this->warehouseRepository = $warehouseRepository;
        $this->productRepository = $productRepository;
        $this->transactionRepository = $transactionRepository;
        $this->productOnWarehouseRepository = $productOnWarehouseRepository;
    }

    /**
     * @param $user_id int
     * @return Warehouse[]
     */
    public function GetWarehouseList($user_id)
    {
        return $this->warehouseRepository->GetWarehouseList($user_id);
    }

    /**
     * @param $user_id int
     * @param $warehouse_id int
     * @return Warehouse Warehouse
     */
    public function GetWarehouse($user_id, $warehouse_id)
    {
        $this->warehouseRepository->CheckWarehouse($user_id, $warehouse_id);

        return $this->warehouseRepository->GetWarehouse($warehouse_id);
    }

    /**
     * @param $user_id int
     * @param $address string
     * @param $capacity double
     * @return Warehouse Warehouse
     */
    public function CreateWarehouse($user_id, $address, $capacity)
    {
        $this->warehouseRepository->CheckAddressDuplicates($address, 0);

        return $this->warehouseRepository->CreateWarehouse($address, $capacity, $user_id);
    }

    /**
     * @param $user_id int
     * @param $warehouse_id int
     * @param $address string
     * @param $capacity double
     * @return Warehouse Warehouse
     */
    public function UpdateWarehouse($user_id, $warehouse_id, $address, $capacity)
    {
        $this->warehouseRepository->CheckWarehouse($user_id, $warehouse_id);
        $this->warehouseRepository->CheckAddressDuplicates($address, $warehouse_id);

        $warehouse = $this->warehouseRepository->GetWarehouse($warehouse_id);

        $warehouse->setAddress($address);
        $warehouse->setCapacity($capacity);

        $this->warehouseRepository->UpdateWarehouse($warehouse);

        return $warehouse;
    }

    /**
     * @param $user_id int
     * @param $warehouse_id int
     * @param $product_id int
     * @param $count int
     */
    public function AppProduct($user_id, $warehouse_id, $product_id, $count)
    {
        $this->productRepository->CheckProduct($user_id, $product_id);

        $product = $this->productRepository->GetProduct($product_id);
        $product_full_size = $count * $product->getSize();

        $warehouse = $this->warehouseRepository->GetWarehouse($warehouse_id);
        $warehouse->CheckAvailableSize($product_full_size);

        try {
            $ProductOnWarehouse = $this->productOnWarehouseRepository->GetProductOnWarehouse($product_id, $warehouse_id);

            $current_count = $ProductOnWarehouse->getCount();
            $ProductOnWarehouse->setCount($current_count + $count);
            $this->productOnWarehouseRepository->UpdateProductOnWarehouse($ProductOnWarehouse);
        } catch (\InvalidArgumentException $e) {
            $this->productOnWarehouseRepository->CreateProductOnWarehouse($product_id, $warehouse_id, $count);
        }

    }

    /**
     * @param $user_id int
     * @param $warehouse_id int
     * @param $product_id int
     * @param $count int
     */
    public function DetachProduct($user_id, $warehouse_id, $product_id, $count)
    {
        $this->productRepository->CheckProduct($user_id, $product_id);

        $ProductOnWarehouse = $this->productOnWarehouseRepository->GetProductOnWarehouse($product_id, $warehouse_id);
        $ProductOnWarehouse->CheckAvailableCount($count);

        if ($ProductOnWarehouse->getCount() > $count) {
            $current_count = $ProductOnWarehouse->getCount();
            $ProductOnWarehouse->setCount($current_count - $count);
            $this->productOnWarehouseRepository->UpdateProductOnWarehouse($ProductOnWarehouse);
        } else {
            $this->productOnWarehouseRepository->DeleteProductOnWarehouse($ProductOnWarehouse);
        }

    }

    /**
     * @param $user_id int
     * @param $products_in_transaction array
     * @param $warehouses array
     * @param $movement_type string
     */
    public function MoveProducts($user_id, $products_in_transaction, $warehouses, $movement_type)
    {
        $this->warehouseRepository->CheckWarehousesInTransaction(
            $user_id,
            $warehouses['from'],
            $warehouses['to'],
            $movement_type
        );

        try {
            $this->transactionRepository->StartTransaction();

            $transaction = $this->transactionRepository->CreateTransaction(
                $warehouses['from'],
                $warehouses['to'],
                $movement_type
            );

            foreach ($products_in_transaction as $product) {
                switch ($movement_type) {
                    case 'app':
                        $this->AppProduct($user_id, $warehouses['to'], $product['id'], $product['count']);
                        break;
                    case 'detach':
                        $this->DetachProduct($user_id, $warehouses['from'], $product['id'], $product['count']);
                        break;
                    case 'move':
                        $this->DetachProduct($user_id, $warehouses['from'], $product['id'], $product['count']);
                        $this->AppProduct($user_id, $warehouses['to'], $product['id'], $product['count']);
                        break;
                }
                $this->transactionRepository->AddProductToTransaction($transaction, $product['id'], $product['count']);
            }
            $this->transactionRepository->CommitTransaction();
        } catch (\Exception $e) {
            $this->transactionRepository->RollbackTransaction();
            throw new \InvalidArgumentException('Transaction failed: ' . $e->getMessage());
        }
    }

    /**
     * @param $user_id int
     * @param $warehouse_id int
     * @return array
     */
    public function GetLogs($user_id, $warehouse_id)
    {
        $this->warehouseRepository->CheckWarehouse($user_id, $warehouse_id);

        $current_date = date('Y-m-d H:i:s');

        $transactions = $this->transactionRepository->GetTransactionsOnWarehouse($warehouse_id, $current_date);
        $transactions_list = [];


        foreach ($transactions as $transaction) {
            $products_list = $this->transactionRepository->GetProductList($transaction);

            $transactions_list[] = $transaction->GetData($products_list);
        }

        return $transactions_list;
    }

    /**
     * @param $user_id int
     * @param $warehouse_id int
     */
    public function DeleteWarehouse($user_id, $warehouse_id)
    {
        $this->warehouseRepository->CheckWarehouse($user_id, $warehouse_id);

        $this->transactionRepository->CheckWarehouseLogs($warehouse_id);
        $this->warehouseRepository->DeleteWarehouse($warehouse_id);
    }

    /**
     * @param $user_id int
     * @param $warehouse_id int
     * @return array
     */
    public function GetProductsList($user_id, $warehouse_id)
    {
        $this->warehouseRepository->CheckWarehouse($user_id, $warehouse_id);

        $warehouse = $this->warehouseRepository->GetWarehouse($warehouse_id);
        $products_list = $this->productOnWarehouseRepository->GetProductList($warehouse);

        $jsonResult = [
            'balance' => $warehouse->getBalance(),
            'products_list' => $products_list
        ];

        return $jsonResult;
    }

    /**
     * @param $transaction_product_list array
     * @param $product_list array
     */
    public function AppProductList(&$product_list, $transaction_product_list)
    {
        foreach (array_keys($transaction_product_list) as $key){
            $product_list[$key] += $transaction_product_list[$key]['count'];
        }
    }

    /**
     * @param $transaction_products_list array
     * @param $products_list array
     */
    public function DetachProductList(&$products_list, $transaction_products_list)
    {
        foreach (array_keys($transaction_products_list) as $key){
            $products_list[$key] -= $transaction_products_list[$key]['count'];
        }
    }

    /**
     * @param $products_list array
     */
    public function UnsetZeroProductList(&$products_list)
    {
        foreach (array_keys($products_list) as $key) {
            if ($products_list[$key] == 0) {
                unset($products_list[$key]);
            }
        }
    }

    /**
     * @param $user_id int
     * @param $warehouse_id int
     * @param $date \DateTime
     * @return array
     */
    public function GetProductListOnDate($user_id, $warehouse_id, $date)
    {
        $this->warehouseRepository->CheckWarehouse($user_id, $warehouse_id);

        $products_list = $this->productRepository->GetDefaultProductList($user_id);

        $transactions = $this->transactionRepository->GetTransactionsOnWarehouse($warehouse_id, $date);

        $balance = 0;

        foreach ($transactions as $transaction) {
            $transaction_products_list = $this->transactionRepository->GetProductList($transaction);

            switch ($transaction->getMovementType()) {
                case 'app':
                    $this->AppProductList($products_list, $transaction_products_list);
                    $balance += $transaction->getTotalCount();
                    break;
                case 'detach':
                    $this->DetachProductList($products_list, $transaction_products_list);
                    $balance -= $transaction->getTotalCount();
                    break;
                case 'move':
                    if ($transaction->getWarehouseToId() == $warehouse_id) {
                        $this->AppProductList($products_list, $transaction_products_list);
                        $balance += $transaction->getTotalCount();
                    } elseif ($transaction->getWarehouseFromId() == $warehouse_id) {
                        $this->DetachProductList($products_list, $transaction_products_list);
                        $balance -= $transaction->getTotalCount();
                    }
                    break;
            }
        }

        $this->UnsetZeroProductList($products_list);

        $jsonResult = [
            'balance' => $balance,
            'products' => $products_list
        ];

        return $jsonResult;
    }
}