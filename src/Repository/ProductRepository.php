<?php

namespace App\Repository;

use App\Model\Product;

class ProductRepository extends AbstractRepository
{
    /**
     * @param $user_id int
     * @param $product_id int
     * @throws
     */
    public function CheckProduct($user_id, $product_id)
    {
        $row = $this->dbConnection->fetchAssoc(
            'SELECT * FROM products WHERE id = ? AND  user_owner_id = ?',
            [$product_id, $user_id]
        );

        if (!isset($row['id'])){
            throw new \InvalidArgumentException('Product does not exist '.$product_id);
        }
        if ($row['user_owner_id'] != $user_id){
            throw new \InvalidArgumentException('Wrong access '.$product_id);
        }
    }

    /**
     * @param $user_id int
     * @param $product_id int
     * @return Product|null
     * @throws
     */
    public function GetProduct($user_id, $product_id)
    {
        $row = $this->dbConnection->fetchAssoc(
            'SELECT products.id as product_id, name, price, size, types.id as type_id, type_name, user_owner_id
                       FROM products, types WHERE products.id = ? AND types.id = type_id',
            [$product_id]
        );
        if (!isset($row['product_id'])){
            throw new \InvalidArgumentException('Product does not exist '.$product_id);
        }
        if ($row['user_owner_id'] != $user_id){
            throw new \InvalidArgumentException('Wrong access '.$product_id);
        }

        return $row['product_id'] != null ?
            new Product(
                $row['product_id'],
                $row['name'],
                $row['price'],
                $row['size'],
                $row['type_id'],
                $row['type_name'],
                $row['user_owner_id']
            ):
            null;
    }

    /**
     * @param $user_id int
     * @return Product[]
     * @throws
     */
    public function GetProductList($user_id)
    {
        $products =[];

        $rows = $this->dbConnection->executeQuery(
            'SELECT products.id as product_id, name, price, size, types.id as type_id, type_name, user_owner_id
                    FROM products, types 
                    WHERE type_id = types.id AND user_owner_id = ?',
            [$user_id]
        );

        while ($row = $rows->fetch(\PDO::FETCH_ASSOC)){
            $products[] = new Product(
                $row['product_id'],
                $row['name'],
                $row['price'],
                $row['size'],
                $row['type_id'],
                $row['type_name'],
                $row['user_owner_id']
            );
        }

        return $products;
    }

    /**
     * @param $user_id int
     * @return array
     * @throws
     */
    public function GetDefaultProductList($user_id)
    {
        $products = $this->dbConnection->executeQuery(
            'SELECT * FROM products WHERE user_owner_id = ?',
            [$user_id]
        );

        $products_list = [];

        while ($product = $products->fetch(\PDO::FETCH_ASSOC)) {
            $products_list[$product['id']] = 0;
        }

        return $products_list;
    }

    /**
     * @param $type_name string
     * @return int|null
     * @throws
     */
    public function GetType($type_name)
    {
        $type_id = $this->dbConnection->fetchAssoc(
            'SELECT id FROM types WHERE type_name = ?',
            [$type_name]
        )['id'];

        if (!isset($type_id)){
            throw new \InvalidArgumentException('Type does not exist!');
        }

        return $type_id;
    }

    /**
     * @param $user_id int
     * @param $name string
     * @param $price double
     * @param $size double
     * @param $type_name string
     * @return Product
     * @throws
     */
    public function CreateProduct($user_id, $name, $price, $size, $type_name)
    {
        $type_id = $this->GetType($type_name);
        $product = new Product(null, $name, $price, $size, $type_id, $type_name, $user_id);

        $this->dbConnection->executeQuery(
            'INSERT INTO products (name, price, size, type_id, user_owner_id) VALUES (?, ?, ?, ?, ?)',
            [
                $product->getName(),
                $product->getPrice(),
                $product->getSize(),
                $product->getTypeId(),
                $product->getUserOwnerId()
            ]
        );

        $product->setId($this->dbConnection->lastInsertId());

        return $product;
    }

    /**
     * @param $product Product
     * @throws
     * @return Product
     */
    public function UpdateProduct($product)
    {
        $product->setTypeId($this->GetType($product->getTypeName()));

        $this->dbConnection->executeQuery(
            'UPDATE products SET name = ?, price = ?, size = ?, type_id = ? WHERE id = ?',
            [
                $product->getName(),
                $product->getPrice(),
                $product->getSize(),
                $product->getTypeId(),
                $product->getId()
            ]
        );

        return $product;
    }

    /**
     * @param $product_id int
     * @throws
     */
    public function DeleteProduct($product_id)
    {
        $this->dbConnection->executeQuery(
            'DELETE FROM products WHERE id = ?',
            [$product_id]
        );
    }
}