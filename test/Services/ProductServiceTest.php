<?php

use App\Repository\WarehouseRepository;
use App\Repository\ProductRepository;
use App\Repository\TransactionRepository;
use App\Repository\ProductsOnWarehouseRepository;
use App\Services\ProductService;
use App\Model\Warehouse;
use App\Model\Product;

class ProductServiceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $dbConnectionMock;

    /**
     * @var ProductService
     */
    private $productService;


    public function setUp()
    {
        $this->dbConnectionMock = $this->getMockBuilder(\Doctrine\DBAL\Connection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $warehouseRepository = new WarehouseRepository($this->dbConnectionMock);
        $productRepository = new ProductRepository($this->dbConnectionMock);
        $transactionRepository = new TransactionRepository($this->dbConnectionMock);
        $productOnWarehouseRepository = new ProductsOnWarehouseRepository($this->dbConnectionMock);

        $this->productService = new ProductService(
            $productRepository,
            $transactionRepository,
            $productOnWarehouseRepository,
            $warehouseRepository
        );
    }

    /**
     * @dataProvider dataGetProductList
     */
    public function testGetProductList($user_id, $products, $expected, $allOK, $error_type = '')
    {
        $idx = 0;

        $QueryProductMock = $this->getMockBuilder('\PDOStatement')
            ->getMock();

        for ($i = 0; $i <= count($products); $i++) {
            $QueryProductMock->expects($this->at($i))
                ->method('fetch')
                ->will($this->returnValue($products[$i]));
        }

        $this->dbConnectionMock->expects($this->at($idx++))
            ->method('executeQuery')
            ->with('SELECT products.id as product_id, name, price, size, types.id as type_id, type_name, user_owner_id
                    FROM products, types 
                    WHERE type_id = types.id AND user_owner_id = ?',
                [$user_id])
            ->will($this->returnValue($QueryProductMock));

        if (!$allOK)
            $this->expectExceptionMessage($error_type);

        $products = $this->productService->GetProductList($user_id);
        $this->assertEquals($products, $expected);
    }

    public function dataGetProductList()
    {
        return [
            [
                1,
                [
                    ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 1, 'type_name' => 'type1', 'user_owner_id' => 1],
                    ['product_id' => 2, 'name' => 'name2', 'price' => 16, 'size' => 6, 'type_id' => 2, 'type_name' => 'type2', 'user_owner_id' => 1],
                    ['product_id' => 3, 'name' => 'name3', 'price' => 8, 'size' => 13, 'type_id' => 1, 'type_name' => 'type1', 'user_owner_id' => 1]
                ],
                [
                    new Product(1, 'name1', 10, 10, 1, 'type1', 1),
                    new Product(2, 'name2', 16, 6, 2, 'type2', 1),
                    new Product(3, 'name3', 8, 13, 1, 'type1', 1)
                ], true
            ],
            [
                1,
                [],
                [], true
            ]
        ];
    }

    /**
     * @dataProvider dataGetProduct
     */
    public function testGetProduct($user_id, $product_id, $product_data, $expected, $allOK, $error_type = '')
    {
        $idx = 0;

        $this->dbConnectionMock->expects($this->at($idx++))
            ->method('fetchAssoc')
            ->with('SELECT products.id as product_id, name, price, size, types.id as type_id, type_name, user_owner_id
                       FROM products, types WHERE products.id = ? AND types.id = type_id',
                [$product_id])
            ->will($this->returnValue($product_data));

        if (!$allOK)
            $this->expectExceptionMessage($error_type);

        $product = $this->productService->GetProduct($user_id, $product_id);
        $this->assertEquals($product, $expected);
    }

    public function dataGetProduct()
    {
        return [
            [
                1, 1,
                ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 1, 'type_name' => 'type1', 'user_owner_id' => 1],
                new Product(1, 'name1', 10, 10, 1, 'type1', 1),
                true
            ],
            [
                1, 1,
                [],
                null,
                false, 'Product does not exist 1'
            ],
            [
                1, 1,
                ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 1, 'type_name' => 'type1', 'user_owner_id' => 2],
                null,
                false, 'Wrong access 1'
            ]
        ];
    }

    /**
     * @dataProvider dataCreateProduct
     */
    public function testCreateProduct($user_id, $product_id, $name, $price, $size, $type_id, $type_name, $type_checked,
                                      $expected, $allOK, $error_type = '')
    {
        $idx = 0;

        $this->dbConnectionMock->expects($this->at($idx++))
            ->method('fetchAssoc')
            ->with('SELECT id FROM types WHERE type_name = ?',
                [$type_name])
            ->will($this->returnValue(['id' => $type_id]));

        if ($type_checked){
            $this->dbConnectionMock->expects($this->at($idx++))
                ->method('executeQuery')
                ->with('INSERT INTO products (name, price, size, type_id, user_owner_id) VALUES (?, ?, ?, ?, ?)',
                    [$name, $price, $size, $type_id, $user_id]);

            $this->dbConnectionMock->expects($this->at($idx++))
                ->method('lastInsertId')
                ->will($this->returnValue($product_id));
        }

        if (!$allOK)
            $this->expectExceptionMessage($error_type);

        $product = $this->productService->CreateProduct($user_id, $name, $price, $size, $type_name);
        $this->assertEquals($product, $expected);
    }

    public function dataCreateProduct()
    {
        return [
            [
                1, 1, 'name1', 10, 5, 1, 'type1', true,
                new Product(1, 'name1', 10, 5, 1, 'type1', 1),
                true
            ],
            [
                1, 1, 'name1', 10, 5, null, 'type2', false,
                null,
                false, 'Type does not exist!'
            ]
        ];
    }

    /**
     * @dataProvider dataUpdateProduct
     */
    public function testUpdateProduct($user_id, $product_id, $product_checked, $product_data, $name, $price, $size,
                                      $type_id, $type_name, $type_checked, $expected, $allOK, $error_type = '')
    {
        $idx = 0;

        $this->dbConnectionMock->expects($this->at($idx++))
            ->method('fetchAssoc')
            ->with('SELECT products.id as product_id, name, price, size, types.id as type_id, type_name, user_owner_id
                       FROM products, types WHERE products.id = ? AND types.id = type_id',
                [$product_id])
            ->will($this->returnValue($product_data));

        if ($product_checked){
            $this->dbConnectionMock->expects($this->at($idx++))
                ->method('fetchAssoc')
                ->with('SELECT id FROM types WHERE type_name = ?',
                    [$type_name])
                ->will($this->returnValue(['id' => $type_id]));

            if ($type_checked) {
                $this->dbConnectionMock->expects($this->at($idx))
                    ->method('executeQuery')
                    ->with('UPDATE products SET name = ?, price = ?, size = ?, type_id = ? WHERE id = ?',
                        [$name, $price, $size, $type_id, $product_id]);
            }
        }

        if (!$allOK)
            $this->expectExceptionMessage($error_type);

        $product = $this->productService->UpdateProduct($user_id, $product_id, $name, $price, $size, $type_name);

        $this->assertEquals($product, $expected);
    }

    public function dataUpdateProduct()
    {
        return [
            [
            1, 1, true,
                ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 1, 'type_name' => 'type1', 'user_owner_id' => 1],
                'name2', 11, 10, 2, 'type2', true,
                new Product(1, 'name2', 11, 10, 2, 'type2', 1),
                true
            ],
            [
                1, 1, true,
                ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 1, 'type_name' => 'type1', 'user_owner_id' => 1],
                'name2', 11, 10, null, 'type2', false,
                null,
                false, 'Type does not exist!'
            ],
            [
                1, 1, false,
                [],
                'name2', 11, 10, 2, 'type2', true,
                null,
                false, 'Product does not exist 1'
            ],
            [
                1, 1, false,
                ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 1, 'type_name' => 'type1', 'user_owner_id' => 2],
                'name2', 11, 10, 2, 'type2', true,
                null,
                false, 'Wrong access 1'
            ]
        ];
    }

    public function testGetLogs()
    {

    }
}