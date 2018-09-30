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

    /**
     * @dataProvider dataGetLogs
     */
    public function testGetLogs($user_id, $product_id, $product_checked, $product_data,
                                $transactions, $expected, $allOK, $error_type = '')
    {
        $idx = 0;

        $this->dbConnectionMock->expects($this->at($idx++))
            ->method('fetchAssoc')
            ->with('SELECT products.id as product_id, name, price, size, types.id as type_id, type_name, user_owner_id
                       FROM products, types WHERE products.id = ? AND types.id = type_id',
                [$product_id])
            ->will($this->returnValue($product_data));

        if ($product_checked){
            $current_date = date('Y-m-d H:i:s');

            $QueryTransactionMock = $this->getMockBuilder('\PDOStatement')
                ->getMock();

            for ($i = 0; $i <= count($transactions); $i++) {
                $QueryTransactionMock->expects($this->at($i))
                    ->method('fetch')
                    ->will($this->returnValue($transactions[$i]));
            }

            $this->dbConnectionMock->expects($this->at($idx++))
                ->method('executeQuery')
                ->with('SELECT * FROM transactions, products_on_transaction 
                        WHERE transactions.id = transaction_id AND product_id = ? AND date <= ?',
                    [$product_id, $current_date])
                ->will($this->returnValue($QueryTransactionMock));

            for ($i = 0; $i < count($transactions); $i++){
                $this->dbConnectionMock->expects($this->at($idx++))
                    ->method('fetchAssoc')
                    ->with('SELECT * FROM products_on_transaction WHERE transaction_id = ? AND product_id = ?',
                        [$transactions[$i]['id'], $product_id])
                    ->will($this->returnValue($transactions[$i]['products']));
            }
        }

        if (!$allOK)
            $this->expectExceptionMessage($error_type);

        $logs = $this->productService->GetLogs($user_id, $product_id);

        if ($allOK)
            $this->assertEquals($logs, $expected);
    }

    public function dataGetLogs()
    {
        return [
            [
                1, 1, true, ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 1, 'type_name' => 'type1', 'user_owner_id' => 1],
                [['id' => 1, 'warehouse_from_id' => null, 'warehouse_to_id' => 1, 'movement_type' => 'app', 'date' => '2018-08-09', 'total_count' => 100,
                    'products' => ['product_id' => 1,  'count' => 5, 'amount' => 100]]],
                [['id' => 1, 'movement_type' => 'app', 'warehouse_id' => 1, 'date' => '2018-08-09', 'total_count' => 100,
                    'product_list' => [1 => ['count' => 5, 'amount' => 100]]]], true
            ],
            [
                1, 1, true, ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 1, 'type_name' => 'type1', 'user_owner_id' => 1],
                [
                    ['id' => 1, 'warehouse_from_id' => null, 'warehouse_to_id' => 1, 'movement_type' => 'app', 'date' => '2018-08-09', 'total_count' => 100,
                        'products' =>
                                ['product_id' => 1,  'count' => 5, 'amount' => 100]
                    ]
                ],
                [
                    ['id' => 1, 'movement_type' => 'app', 'warehouse_id' => 1, 'date' => '2018-08-09', 'total_count' => 100,
                        'product_list' => [1 => ['count' => 5, 'amount' => 100]]
                    ]
                ], true
            ],
            [
                1, 1, true, ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 1, 'type_name' => 'type1', 'user_owner_id' => 1],
                [
                    ['id' => 1, 'warehouse_from_id' => null, 'warehouse_to_id' => 1, 'movement_type' => 'app', 'date' => '2018-08-09', 'total_count' => 100,
                        'products' =>
                                ['product_id' => 1,  'count' => 5, 'amount' => 100]
                    ],
                    ['id' => 2, 'warehouse_from_id' => null, 'warehouse_to_id' => 1, 'movement_type' => 'app', 'date' => '2018-08-10', 'total_count' => 100,
                        'products' =>
                                ['product_id' => 1,  'count' => 5, 'amount' => 100]
                    ]
                ],
                [
                    ['id' => 1, 'movement_type' => 'app', 'warehouse_id' => 1, 'date' => '2018-08-09', 'total_count' => 100,
                        'product_list' => [
                            1 => ['count' => 5, 'amount' => 100]]
                    ],
                    ['id' => 2, 'movement_type' => 'app', 'warehouse_id' => 1, 'date' => '2018-08-10', 'total_count' => 100,
                        'product_list' => [
                            1 => ['count' => 5, 'amount' => 100]]
                    ]
                ], true
            ],
            [
                1, 1, true, ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 1, 'type_name' => 'type1', 'user_owner_id' => 1],
                [
                    ['id' => 1, 'warehouse_from_id' => null, 'warehouse_to_id' => 1, 'movement_type' => 'app', 'date' => '2018-08-09', 'total_count' => 100,
                        'products' =>
                                ['product_id' => 1,  'count' => 5, 'amount' => 100]
                    ],
                    ['id' => 2, 'warehouse_from_id' => null, 'warehouse_to_id' => 1, 'movement_type' => 'app', 'date' => '2018-08-10', 'total_count' => 100,
                        'products' =>
                                ['product_id' => 1,  'count' => 5, 'amount' => 100]
                    ],
                    ['id' => 3, 'warehouse_from_id' => 1, 'warehouse_to_id' => null, 'movement_type' => 'detach', 'date' => '2018-08-10', 'total_count' => 100,
                        'products' =>
                                ['product_id' => 1,  'count' => 5, 'amount' => 100]
                    ]
                ],
                [
                    ['id' => 1, 'movement_type' => 'app', 'warehouse_id' => 1, 'date' => '2018-08-09', 'total_count' => 100,
                        'product_list' => [
                            1 => ['count' => 5, 'amount' => 100]]
                    ],
                    ['id' => 2, 'movement_type' => 'app', 'warehouse_id' => 1, 'date' => '2018-08-10', 'total_count' => 100,
                        'product_list' => [
                            1 => ['count' => 5, 'amount' => 100]]
                    ],
                    ['id' => 3, 'movement_type' => 'detach', 'warehouse_id' => 1, 'date' => '2018-08-10', 'total_count' => 100,
                        'product_list' => [
                            1 => ['count' => 5, 'amount' => 100]]
                    ]
                ], true
            ],
            [
                1, 1, true, ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 1, 'type_name' => 'type1', 'user_owner_id' => 1],
                [
                    ['id' => 1, 'warehouse_from_id' => null, 'warehouse_to_id' => 1, 'movement_type' => 'app', 'date' => '2018-08-09', 'total_count' => 100,
                        'products' =>
                                ['product_id' => 1,  'count' => 5, 'amount' => 100]
                    ],
                    ['id' => 2, 'warehouse_from_id' => 2, 'warehouse_to_id' => 1, 'movement_type' => 'move', 'date' => '2018-08-10', 'total_count' => 100,
                        'products' =>
                                ['product_id' => 1,  'count' => 5, 'amount' => 100]
                    ],
                    ['id' => 3, 'warehouse_from_id' => 1, 'warehouse_to_id' => null, 'movement_type' => 'detach', 'date' => '2018-08-10', 'total_count' => 100,
                        'products' =>
                                ['product_id' => 1,  'count' => 5, 'amount' => 100]
                    ]
                ],
                [
                    ['id' => 1, 'movement_type' => 'app', 'warehouse_id' => 1, 'date' => '2018-08-09', 'total_count' => 100,
                        'product_list' => [
                            1 => ['count' => 5, 'amount' => 100]]
                    ],
                    ['id' => 2, 'movement_type' => 'move', 'warehouse_from_id' => 2, 'warehouse_to_id' => 1, 'date' => '2018-08-10', 'total_count' => 100,
                        'product_list' => [
                            1 => ['count' => 5, 'amount' => 100]]
                    ],
                    ['id' => 3, 'movement_type' => 'detach', 'warehouse_id' => 1, 'date' => '2018-08-10', 'total_count' => 100,
                        'product_list' => [
                            1 => ['count' => 5, 'amount' => 100]]
                    ]
                ], true
            ],
            [
                1, 1, false, [],
                [['id' => 1, 'warehouse_from_id' => null, 'warehouse_to_id' => 1, 'movement_type' => 'app', 'date' => '2018-08-09', 'total_count' => 100,
                    'products' => [['product_id' => 1,  'count' => 5, 'amount' => 100]]]],
                [['id' => 1, 'movement_type' => 'app', 'warehouse_id' => 1, 'date' => '2018-08-09', 'total_count' => 100,
                    'product_list' => [1 => ['count' => 5, 'amount' => 100]]]], false, 'Product does not exist 1'
            ],
            [
                1, 1, false, ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 1, 'type_name' => 'type1', 'user_owner_id' => 2],
                [['id' => 1, 'warehouse_from_id' => null, 'warehouse_to_id' => 1, 'movement_type' => 'app', 'date' => '2018-08-09', 'total_count' => 100,
                    'products' => [['product_id' => 1,  'count' => 5, 'amount' => 100]]]],
                [['id' => 1, 'movement_type' => 'app', 'warehouse_id' => 1, 'date' => '2018-08-09', 'total_count' => 100,
                    'product_list' => [1 => ['count' => 5, 'amount' => 100]]]], false, 'Wrong access 1'
            ]
        ];
    }

    /**
     * @dataProvider dataDeleteProduct
     */
    public function testDeleteProduct($user_id, $product_id, $product_data, $product_checked,
                                      $logs_count, $allOK, $error_type = '')
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
                ->with('SELECT COUNT(*) FROM transactions, products_on_transaction 
                        WHERE transactions.id = transaction_id AND product_id = ?',
                    [$product_id])
                ->will($this->returnValue(['COUNT(*)' => $logs_count]));

            if ($logs_count == 0)
                $this->dbConnectionMock->expects($this->at($idx++))
                    ->method('executeQuery')
                    ->with('DELETE FROM products WHERE id = ?',
                        [$product_id]);
        }

        if (!$allOK)
            $this->expectExceptionMessage($error_type);

        $this->productService->DeleteProduct($user_id, $product_id);
    }

    public function dataDeleteProduct()
    {
        return [
            [
                1, 1, ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 1, 'type_name' => 'type1', 'user_owner_id' => 1],
                true, 0, true
            ],
            [
                1, 1, ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 1, 'type_name' => 'type1', 'user_owner_id' => 1],
                true, 1, false, 'This product has logs'
            ],
            [
                1, 1, [],
                false, 0, false, 'Product does not exist 1'
            ],
            [
                1, 1, ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 1, 'type_name' => 'type1', 'user_owner_id' => 2],
                false, 0, false, 'Wrong access 1'
            ]
        ];
    }

    /**
     * @dataProvider dataGetAvailableInfo
     */
    public function testGetAvailableInfo($user_id, $product_id, $product_checked, $product_data, $warehouse_list,
                                         $expected, $allOK, $error_type = '')
    {
        $idx = 0;

        $this->dbConnectionMock->expects($this->at($idx++))
            ->method('fetchAssoc')
            ->with('SELECT products.id as product_id, name, price, size, types.id as type_id, type_name, user_owner_id
                       FROM products, types WHERE products.id = ? AND types.id = type_id',
                [$product_id])
            ->will($this->returnValue($product_data));

        if ($product_checked){
            $QueryWarehouseMock = $this->getMockBuilder('\PDOStatement')
                ->getMock();

            for ($i = 0; $i <= count($warehouse_list); $i++) {
                $QueryWarehouseMock->expects($this->at($i))
                    ->method('fetch')
                    ->will($this->returnValue($warehouse_list[$i]));
            }

            $this->dbConnectionMock->expects($this->at($idx++))
                ->method('executeQuery')
                ->with('SELECT * FROM products_on_warehouse WHERE product_id = ?',
                    [$product_id])
                ->will($this->returnValue($QueryWarehouseMock));
        }

        if (!$allOK)
            $this->expectExceptionMessage($error_type);

        $products = $this->productService->GetAvailableInfo($user_id, $product_id);

        if ($allOK)
            $this->assertEquals($products, $expected);
    }

    public function dataGetAvailableInfo()
    {
        return [
            [
                1, 1, true,
                ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 1, 'type_name' => 'type1', 'user_owner_id' => 1],
                [
                    ['warehouse_id' => 1, 'count' => 10], ['warehouse_id' => 2, 'count' => 5], ['warehouse_id' => 3, 'count' => 1]
                ], ['total_cost' => 160,
                'warehouses_list' => [
                    1 => 10,
                    2 => 5,
                    3 => 1
                ]], true
            ],
            [
                1, 1, false,
                [],
                [
                    ['warehouse_id' => 1, 'count' => 10], ['warehouse_id' => 2, 'count' => 5], ['warehouse_id' => 3, 'count' => 1]
                ], ['total_cost' => 160,
                'warehouses_list' => [
                    1 => 10,
                    2 => 5,
                    3 => 1
                ]], false, 'Product does not exist 1'
            ],
            [
                1, 1, false,
                ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 1, 'type_name' => 'type1', 'user_owner_id' => 2],
                [
                    ['warehouse_id' => 1, 'count' => 10], ['warehouse_id' => 2, 'count' => 5], ['warehouse_id' => 3, 'count' => 1]
                ], ['total_cost' => 160,
                'warehouses_list' => [
                    1 => 10,
                    2 => 5,
                    3 => 1
                ]], false, 'Wrong access 1'
            ]
        ];
    }

    /**
     * @dataProvider dataUnsetZeroWarehouseList
     */
    public function testUnsetZeroWarehouseList($warehouse_list, $expected)
    {
        $this->productService->UnsetZeroWarehouseList($warehouse_list);

        $this->assertEquals($warehouse_list, $expected);
    }

    public function dataUnsetZeroWarehouseList()
    {
        return [
            [
                [1 => 0, 2 => 13, 3 => 0],
                [2 => 13]
            ]
        ];
    }

    /**
     * @dataProvider dataGetAvailableInfoOnDate
     */
    public function testGetAvailableInfoOnDate($user_id, $product_id, $product_checked, $product_data, $full_warehouses_list,
                                               $transactions, $date, $expected, $allOK, $error_type = '')
    {
        $idx = 0;

        $this->dbConnectionMock->expects($this->at($idx++))
            ->method('fetchAssoc')
            ->with('SELECT products.id as product_id, name, price, size, types.id as type_id, type_name, user_owner_id
                       FROM products, types WHERE products.id = ? AND types.id = type_id',
                [$product_id])
            ->will($this->returnValue($product_data));

        if ($product_checked){
            $QueryWarehouseListMock = $this->getMockBuilder('\PDOStatement')
                ->getMock();

            for ($i = 0; $i <= count($full_warehouses_list); $i++) {
                $QueryWarehouseListMock->expects($this->at($i))
                    ->method('fetch')
                    ->will($this->returnValue($full_warehouses_list[$i]));
            }

            $this->dbConnectionMock->expects($this->at($idx++))
                ->method('executeQuery')
                ->with('SELECT * FROM warehouses WHERE user_id = ?',
                    [$user_id])
                ->will($this->returnValue($QueryWarehouseListMock));

            $QueryTransactionMock = $this->getMockBuilder('\PDOStatement')
                ->getMock();

            for ($i = 0; $i <= count($transactions); $i++) {
                $QueryTransactionMock->expects($this->at($i))
                    ->method('fetch')
                    ->will($this->returnValue($transactions[$i]));
            }

            $this->dbConnectionMock->expects($this->at($idx++))
                ->method('executeQuery')
                ->with('SELECT * FROM transactions, products_on_transaction 
                        WHERE transactions.id = transaction_id AND product_id = ? AND date <= ?',
                    [$product_id, $date])
                ->will($this->returnValue($QueryTransactionMock));

            for ($i = 0; $i < count($transactions); $i++) {
                $QueryProductMock = $this->getMockBuilder('\PDOStatement')
                    ->getMock();

                for ($j = 0; $j <= count($transactions[$i]['products']); $j++) {
                    $QueryProductMock->expects($this->at($j))
                        ->method('fetch')
                        ->will($this->returnValue($transactions[$i]['products'][$j]));
                }

                $this->dbConnectionMock->expects($this->at($idx++))
                    ->method('executeQuery')
                    ->with('SELECT * FROM products_on_transaction WHERE transaction_id = ?',
                        [$transactions[$i]['id']])
                    ->will($this->returnValue($QueryProductMock));
            }
        }

        if (!$allOK)
            $this->expectExceptionMessage($error_type);

        $products = $this->productService->GetAvailableOnDate($user_id, $product_id, $date);

        if ($allOK)
            $this->assertEquals($products, $expected);
    }

    public function dataGetAvailableInfoOnDate()
    {
        return [
            [
                1, 1, true,
                ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 1, 'type_name' => 'type1', 'user_owner_id' => 1],
                [['id' => 1], ['id' => 2], ['id' => 3]],
                [
                    ['id' => 1, 'warehouse_from_id' => null, 'warehouse_to_id' => 1, 'movement_type' => 'app', 'date' => '2018-08-09', 'total_count' => 250,
                        'products' =>
                            [
                                ['product_id' => 1,  'count' => 5, 'amount' => 50],
                                ['product_id' => 2,  'count' => 7, 'amount' => 150]
                            ]
                    ],
                    ['id' => 1, 'warehouse_from_id' => null, 'warehouse_to_id' => 2, 'movement_type' => 'app', 'date' => '2018-08-09', 'total_count' => 250,
                        'products' =>
                            [
                                ['product_id' => 1,  'count' => 7, 'amount' => 70]
                            ]
                    ],
                    ['id' => 2, 'warehouse_from_id' => 2, 'warehouse_to_id' => 1, 'movement_type' => 'move', 'date' => '2018-08-10', 'total_count' => 340,
                        'products' =>
                            [
                                ['product_id' => 1,  'count' => 5, 'amount' => 50],
                                ['product_id' => 2,  'count' => 7, 'amount' => 150],
                                ['product_id' => 3,  'count' => 1, 'amount' => 90]
                            ]
                    ],
                    ['id' => 3, 'warehouse_from_id' => 1, 'warehouse_to_id' => null, 'movement_type' => 'detach', 'date' => '2018-08-11', 'total_count' => 100,
                        'products' =>
                            [
                                ['product_id' => 1,  'count' => 1, 'amount' => 10]
                            ]
                    ]
                ], '2018-08-11',
                [
                    'total_cost' => 110,
                    'warehouses_list' => [
                        1 => 9,
                        2 => 2
                    ]
                ], true
            ],
            [
                1, 1, false,
                [],
                [['id' => 1], ['id' => 2], ['id' => 3]],
                [
                    ['id' => 1, 'warehouse_from_id' => null, 'warehouse_to_id' => 1, 'movement_type' => 'app', 'date' => '2018-08-09', 'total_count' => 250,
                        'products' =>
                            [
                                ['product_id' => 1,  'count' => 5, 'amount' => 50],
                                ['product_id' => 2,  'count' => 7, 'amount' => 150]
                            ]
                    ],
                    ['id' => 1, 'warehouse_from_id' => null, 'warehouse_to_id' => 2, 'movement_type' => 'app', 'date' => '2018-08-09', 'total_count' => 250,
                        'products' =>
                            [
                                ['product_id' => 1,  'count' => 7, 'amount' => 70]
                            ]
                    ],
                    ['id' => 2, 'warehouse_from_id' => 2, 'warehouse_to_id' => 1, 'movement_type' => 'move', 'date' => '2018-08-10', 'total_count' => 340,
                        'products' =>
                            [
                                ['product_id' => 1,  'count' => 5, 'amount' => 50],
                                ['product_id' => 2,  'count' => 7, 'amount' => 150],
                                ['product_id' => 3,  'count' => 1, 'amount' => 90]
                            ]
                    ],
                    ['id' => 3, 'warehouse_from_id' => 1, 'warehouse_to_id' => null, 'movement_type' => 'detach', 'date' => '2018-08-11', 'total_count' => 100,
                        'products' =>
                            [
                                ['product_id' => 1,  'count' => 1, 'amount' => 10]
                            ]
                    ]
                ], '2018-08-11',
                [
                    'total_cost' => 110,
                    'warehouses_list' => [
                        1 => 9,
                        2 => 2
                    ]
                ], false, 'Product does not exist 1'
            ],
            [
                1, 1, false,
                ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 1, 'type_name' => 'type1', 'user_owner_id' => 2],
                [['id' => 1], ['id' => 2], ['id' => 3]],
                [
                    ['id' => 1, 'warehouse_from_id' => null, 'warehouse_to_id' => 1, 'movement_type' => 'app', 'date' => '2018-08-09', 'total_count' => 250,
                        'products' =>
                            [
                                ['product_id' => 1,  'count' => 5, 'amount' => 50],
                                ['product_id' => 2,  'count' => 7, 'amount' => 150]
                            ]
                    ],
                    ['id' => 1, 'warehouse_from_id' => null, 'warehouse_to_id' => 2, 'movement_type' => 'app', 'date' => '2018-08-09', 'total_count' => 250,
                        'products' =>
                            [
                                ['product_id' => 1,  'count' => 7, 'amount' => 70]
                            ]
                    ],
                    ['id' => 2, 'warehouse_from_id' => 2, 'warehouse_to_id' => 1, 'movement_type' => 'move', 'date' => '2018-08-10', 'total_count' => 340,
                        'products' =>
                            [
                                ['product_id' => 1,  'count' => 5, 'amount' => 50],
                                ['product_id' => 2,  'count' => 7, 'amount' => 150],
                                ['product_id' => 3,  'count' => 1, 'amount' => 90]
                            ]
                    ],
                    ['id' => 3, 'warehouse_from_id' => 1, 'warehouse_to_id' => null, 'movement_type' => 'detach', 'date' => '2018-08-11', 'total_count' => 100,
                        'products' =>
                            [
                                ['product_id' => 1,  'count' => 1, 'amount' => 10]
                            ]
                    ]
                ], '2018-08-11',
                [
                    'total_cost' => 110,
                    'warehouses_list' => [
                        1 => 9,
                        2 => 2
                    ]
                ], false, 'Wrong access 1'
            ]
        ];
    }
}