<?php

namespace App\Services;

use App\Model\Product;
use App\Repository\ProductRepository;
use App\Repository\TransactionRepository;
use App\Repository\ProductsOnWarehouseRepository;
use App\Repository\WarehouseRepository;

class ProductService
{
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

    /**
     * @var WarehouseRepository
     */
    private $warehouseRepository;

    public function __construct(ProductRepository $productRepository,
                                TransactionRepository $transactionRepository,
                                ProductsOnWarehouseRepository $productOnWarehouseRepository,
                                WarehouseRepository $warehouseRepository)
    {
        $this->productRepository = $productRepository;
        $this->transactionRepository = $transactionRepository;
        $this->productOnWarehouseRepository = $productOnWarehouseRepository;
        $this->warehouseRepository = $warehouseRepository;
    }

    /**
     * @param $user_id int
     * @return product[]
     * @throws
     */
    public function GetProductList($user_id)
    {
        return $this->productRepository->GetProductList($user_id);
    }

    /**
     * @param $user_id int
     * @param $product_id int
     * @return Product|null
     */
    public function GetProduct($user_id, $product_id)
    {
        return $this->productRepository->GetProduct($user_id, $product_id);
    }

    /**
     * @param $user_id int
     * @param $name string
     * @param $price double
     * @param $size double
     * @param $type_name string
     * @return Product
     */
    public function CreateProduct($user_id, $name, $price, $size, $type_name)
    {
        return $this->productRepository->CreateProduct($user_id, $name, $price, $size, $type_name);
    }

    /**
     * @param $user_id int
     * @param $product_id int
     * @param $name string
     * @param $price double
     * @param $size double
     * @param $type_name string
     * @return Product
     */
    public function UpdateProduct($user_id, $product_id, $name, $price, $size, $type_name)
    {
        $product = $this->productRepository->GetProduct($user_id, $product_id);

        if ($size != $product->getSize())
            $this->productOnWarehouseRepository->CheckProductOnWarehouses($product_id);

        $product->setName($name);
        $product->setPrice($price);
        $product->setSize($size);
        $product->setTypeName($type_name);

        return $this->productRepository->UpdateProduct($product);
    }

    /**
     * @param $user_id int
     * @param $product_id int
     * @return array
     */
    public function GetLogs($user_id, $product_id)
    {
        $this->productRepository->GetProduct($user_id, $product_id);

        $transactions_list = [];

        $current_date = date('Y-m-d H:i:s');

        $transactions =  $this->transactionRepository->GetTransactionsOnProduct($product_id, $current_date);

        foreach ($transactions as $transaction) {
            $product = $this->transactionRepository->GetProduct($transaction, $product_id);

            $transactions_list[] = $transaction->GetData($product);
        }

        return $transactions_list;
    }

    /**
     * @param $user_id int
     * @param $product_id int
     */
    public function DeleteProduct($user_id, $product_id)
    {
        $this->productRepository->GetProduct($user_id, $product_id);
        $this->transactionRepository->CheckProductLogs($product_id);

        $this->productRepository->DeleteProduct($product_id);
    }

    /**
     * @param $user_id int
     * @param $product_id int
     * @return array
     */
    public function GetAvailableInfo($user_id, $product_id)
    {
        $product = $this->productRepository->GetProduct($user_id, $product_id);

        return $this->productOnWarehouseRepository->GetWarehouseList($product);
    }

    /**
     * @param $warehouses_list array
     */
    public function UnsetZeroWarehouseList(&$warehouses_list)
    {
        foreach (array_keys($warehouses_list) as $key) {
            if ($warehouses_list[$key] == 0) {
                unset($warehouses_list[$key]);
            }
        }
    }

    /**
     * @param $user_id int
     * @param $product_id int
     * @param $date \DateTime
     * @return array
     */
    public function GetAvailableOnDate($user_id, $product_id, $date)
    {
        $this->productRepository->GetProduct($user_id, $product_id);

        $warehouses_list = $this->warehouseRepository->GetDefaultWarehouseList($user_id);

        $total_cost = 0;

        $transactions = $this->transactionRepository->GetTransactionsOnProduct($product_id, $date);

        foreach ($transactions as $transaction) {
            $transaction_products_list = $this->transactionRepository->GetProductList($transaction);

            switch ($transaction->getMovementType()) {
                case 'app':
                    $warehouses_list[$transaction->getWarehouseToId()] += $transaction_products_list[$product_id]['count'];
                    $total_cost += $transaction_products_list[$product_id]['amount'];
                    break;
                case 'detach':
                    $warehouses_list[$transaction->getWarehouseFromId()] -= $transaction_products_list[$product_id]['count'];
                    $total_cost -= $transaction_products_list[$product_id]['amount'];
                    break;
                case 'move':
                    $warehouses_list[$transaction->getWarehouseToId()] += $transaction_products_list[$product_id]['count'];
                    $warehouses_list[$transaction->getWarehouseFromId()] -= $transaction_products_list[$product_id]['count'];
                    break;
            }
        }

        $this->UnsetZeroWarehouseList($warehouses_list);

        $jsonResult = [
            'total_cost' => $total_cost,
                'warehouses_list' => $warehouses_list
            ];

        return $jsonResult;
    }
}