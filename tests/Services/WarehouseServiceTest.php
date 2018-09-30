<?php

use App\Repository\WarehouseRepository;
use App\Repository\ProductRepository;
use App\Repository\TransactionRepository;
use App\Repository\ProductsOnWarehouseRepository;
use App\Services\WarehouseService;
use App\Model\Warehouse;
use App\Model\Product;

class WarehouseServiceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $dbConnectionMock;

    /**
     * @var WarehouseService
     */
    private $warehouseService;


    public function setUp()
    {
        $this->dbConnectionMock = $this->getMockBuilder(\Doctrine\DBAL\Connection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $warehouseRepository = new WarehouseRepository($this->dbConnectionMock);
        $productRepository = new ProductRepository($this->dbConnectionMock);
        $transactionRepository = new TransactionRepository($this->dbConnectionMock);
        $productOnWarehouseRepository = new ProductsOnWarehouseRepository($this->dbConnectionMock);

        $this->warehouseService = new WarehouseService(
            $warehouseRepository,
            $productRepository,
            $transactionRepository,
            $productOnWarehouseRepository
        );
    }

    /**
     * @dataProvider dataGetWarehouse
     */
    public function testGetWarehouse($user_id, $warehouse_id, $data, $expected, $allOK,  $error_type = '')
    {
        $this->dbConnectionMock->expects($this->any())
            ->method('fetchAssoc')
            ->with('SELECT * FROM warehouses WHERE id = ?', [$warehouse_id])
            ->will($this->returnValue($data));

        if (!$allOK)
            $this->expectExceptionMessage($error_type);

        $warehouse = $this->warehouseService->GetWarehouse($user_id, $warehouse_id);
        $this->assertTrue(is_a($warehouse, 'App\Model\Warehouse'));
        $this->assertEquals($warehouse, $expected);
    }

    public function dataGetWarehouse()
    {
        return [
            ['11', '17',
                ['id' => '17',
                    'address' => 'add1',
                    'capacity' => '200',
                    'user_id' => '11',
                    'balance' => '0',
                    'total_size' => '0'],
                new Warehouse(17, 'add1', 200, 11, 0, 0), true
                ],
            ['11', '24',
                ['id' => '24',
                    'address' => 'add6',
                    'capacity' => '150',
                    'user_id' => '12',
                    'balance' => '50.4',
                    'total_size' => '66'],
                null, false, 'Wrong access 24'
            ],
            ['11', '50',
                [],
                null, false, 'Warehouse does not exist 50'
            ]
        ];
    }

    /**
     * @dataProvider dataGetWarehouseList
     */
    public function testGetWarehouseList($user_id, $data, $expected, $allOK,  $error_type = '')
    {
        $QueryMock = $this->getMockBuilder('\PDOStatement')
            ->getMock();

        for ($i = 0; $i <= count($data); $i++) {
            $QueryMock->expects($this->at($i))
                ->method('fetch')
                ->will($this->returnValue($data[$i]));
        }

        $this->dbConnectionMock->expects($this->once())
            ->method('executeQuery')
            ->with('SELECT * FROM warehouses WHERE user_id = ?', [$user_id])
            ->will($this->returnValue($QueryMock));

        if (!$allOK)
            $this->expectExceptionMessage($error_type);

        $warehouses = $this->warehouseService->GetWarehouseList($user_id);
        $this->assertEquals($warehouses, $expected);
    }

    public function dataGetWarehouseList()
    {
        return [
            ['11',
                [
                    [
                        'id' => '17',
                        'address' => 'add1',
                        'capacity' => '200',
                        'user_id' => '11',
                        'balance' => '0',
                        'total_size' => '0'
                    ],
                    [
                        'id' => '18',
                        'address' => 'add2',
                        'capacity' => '200',
                        'user_id' => '11',
                        'balance' => '0',
                        'total_size' => '0'
                    ],
                    [
                        'id' => '19',
                        'address' => 'add3',
                        'capacity' => '200',
                        'user_id' => '11',
                        'balance' => '0',
                        'total_size' => '0']
                ],
                [
                    new Warehouse(17, 'add1', 200, 11, 0, 0),
                    new Warehouse(18, 'add2', 200, 11, 0, 0),
                    new Warehouse(19, 'add3', 200, 11, 0, 0)
                ], true
            ],
            ['12',
                [
                    [
                        'id' => '17',
                        'address' => 'add1',
                        'capacity' => '200',
                        'user_id' => '12',
                        'balance' => '0',
                        'total_size' => '0'
                    ]
                ],
                [
                    new Warehouse(17, 'add1', 200, 12, 0, 0)
                ], true
            ],
            ['13',
                [],
                [], true
            ]
        ];
    }

    /**
     * @dataProvider dataCreateWarehouse
     */
    public function testCreateWarehouse(
        $user_id, $address, $capacity, $duplicates,
        $warehouse_id, $expected, $allOK, $error_type = '')
    {
        $this->dbConnectionMock->expects($this->any())
            ->method('fetchAssoc')
            ->with('SELECT COUNT(*) FROM warehouses WHERE address = ? AND id <> ?',
                [$address, 0])
            ->will($this->returnValue(['COUNT(*)' => $duplicates]));

        $this->dbConnectionMock->expects($this->any())
            ->method('lastInsertId')
            ->will($this->returnValue($warehouse_id));

        if (!$allOK)
            $this->expectExceptionMessage($error_type);

        $warehouse = $this->warehouseService->CreateWarehouse($user_id, $address, $capacity);
        $this->assertTrue(is_a($warehouse, 'App\Model\Warehouse'));
        $this->assertEquals($warehouse, $expected);
    }

    public function dataCreateWarehouse()
    {
        return [
            ['11', 'add1', 150, 0, 1,
                new Warehouse(1, 'add1', 150, 11, 0, 0), true
            ],
            ['11', 'add2', 150, 1, 1,
                new Warehouse(null, 'add2', 150, 11, 0, 0), false,
                'Warehouse with this address is already exist!'
            ]
        ];
    }

    /**
     * @dataProvider dataUpdateWarehouse
     */
    public function testUpdateWarehouse($user_id, $warehouse_id, $warehouse_checked, $address, $capacity,
                                        $data, $expected, $duplicates, $allOK, $error_type = '')
    {
        $this->dbConnectionMock->expects($this->at(0))
            ->method('fetchAssoc')
            ->with('SELECT * FROM warehouses WHERE id = ?', [$warehouse_id])
            ->will($this->returnValue($data));

        if ($warehouse_checked && $duplicates > 0) {
            $this->dbConnectionMock->expects($this->at(1))
                ->method('fetchAssoc')
                ->with('SELECT COUNT(*) FROM warehouses WHERE address = ? AND id <> ?',
                    [$address, $warehouse_id])
                ->will($this->returnValue(['COUNT(*)' => $duplicates]));
        }

        $this->dbConnectionMock->expects($this->any())
            ->method('executeQuery')
            ->with('UPDATE warehouses SET address = ?, capacity = ? WHERE id = ?',
                [$address, $capacity, $warehouse_id]);

        if (!$allOK)
            $this->expectExceptionMessage($error_type);

        $warehouse = $this->warehouseService->UpdateWarehouse($user_id, $warehouse_id, $address, $capacity);

        if ($allOK)
            $this->assertEquals($warehouse, $expected);
    }

    public function dataUpdateWarehouse()
    {
        return [
            [
              1, 1, true, 'add2', 200,
              [
                  'id' => '1',
                  'address' => 'add1',
                  'capacity' => '150',
                  'user_id' => '1',
                  'balance' => '0',
                  'total_size' => '0'
              ], new Warehouse(1, 'add2', 200, 1, 0, 0),
              0, true
            ],
            [
                1, 2, false, 'add2', 200,
                [], null,
                0, false,  'Warehouse does not exist 2'
            ],
            [
                1, 1, false, 'add2', 200,
                [
                    'id' => '1',
                    'address' => 'add1',
                    'capacity' => '150',
                    'user_id' => '2',
                    'balance' => '0',
                    'total_size' => '0'
                ], new Warehouse(1, 'add2', 200, 1, 0, 0),
                0, false, 'Wrong access 1'
            ],
            [
                1, 1, true, 'add2', 200,
                [
                    'id' => '1',
                    'address' => 'add1',
                    'capacity' => '150',
                    'user_id' => '1',
                    'balance' => '0',
                    'total_size' => '0'
                ], new Warehouse(1, 'add2', 200, 1, 0, 0),
                1, false, 'Warehouse with this address is already exist!'
            ]
        ];
    }

    /**
     * @dataProvider dataAppProduct
     */
    public function testAppProduct($user_id, $warehouse_id, $warehouse_checked,
                                   $product_id, $product_checked, $product_on_warehouse,
                                   $products_enough, $need_update,  $count,  $product_data, $warehouse_data,
                                   $expected_count, $allOK, $error_type = '')
    {
        $this->dbConnectionMock->expects($this->at(0))
            ->method('fetchAssoc')
            ->with('SELECT products.id as product_id, name, price, size, types.id as type_id, type_name, user_owner_id
                       FROM products, types WHERE products.id = ? AND types.id = type_id',
                [$product_id])
            ->will($this->returnValue($product_data));

        if ($product_checked) {
            $this->dbConnectionMock->expects($this->at(1))
                ->method('fetchAssoc')
                ->with('SELECT * FROM warehouses WHERE id = ?',
                    [$warehouse_id])
                ->will($this->returnValue($warehouse_data));
            if ($warehouse_checked) {
                $this->dbConnectionMock->expects($this->at(2))
                    ->method('fetchAssoc')
                    ->with('SELECT * FROM products_on_warehouse WHERE product_id = ? AND warehouse_id = ?',
                        [$product_id, $warehouse_id])
                    ->will($this->returnValue($product_on_warehouse));

                if ($products_enough) {
                    if ($need_update) {
                        $this->dbConnectionMock->expects($this->any())
                            ->method('executeQuery')
                            ->with('UPDATE products_on_warehouse SET count = ? WHERE product_id = ? AND warehouse_id = ?',
                                [$expected_count, $product_id, $warehouse_id]);
                    } else {
                        $this->dbConnectionMock->expects($this->any())
                            ->method('executeQuery')
                            ->with('INSERT INTO products_on_warehouse (product_id, warehouse_id, count) VALUES (?, ?, ?)',
                                [$product_id, $warehouse_id, $count]);
                    }
                }
            }
        }

        if (!$allOK)
            $this->expectExceptionMessage($error_type);

        $this->warehouseService->AppProduct($user_id, $warehouse_id, $product_id, $count);
    }

    public function dataAppProduct()
    {
        return [
            [
                1, 1, true, 1, true, ['product_id' => 1, 'warehouse_id' => 1, 'count' => 5],
                true, true, 4, ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 1, 'user_owner_id' => 1],
                ['id' => '1', 'address' => 'add1', 'capacity' => '150', 'user_id' => '1', 'balance' => '0', 'total_size' => '0'],
                9, true
            ],
            [
                1, 1, true, 2, false, [],
                true, true, 4, [],
                ['id' => '1', 'address' => 'add1', 'capacity' => '150', 'user_id' => '1', 'balance' => '0', 'total_size' => '0'],
                9, false, 'Product does not exist 2'
            ],
            [
                1, 1, true, 1, false, ['product_id' => 1, 'warehouse_id' => 1, 'count' => 5],
                true, true, 4, ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 1, 'user_owner_id' => 2],
                ['id' => '1', 'address' => 'add1', 'capacity' => '150', 'user_id' => '1', 'balance' => '0', 'total_size' => '0'],
                9, false, 'Wrong access 1'
            ],
            [
                1, 2, false, 1, true, [],
                true, true, 4, ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 1, 'user_owner_id' => 1],
                [],
                9, false, 'Warehouse does not exist 2'
            ],
            [
                1, 1, false, 1, true, ['product_id' => 1, 'warehouse_id' => 1, 'count' => 10],
                true, true, 10, ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 1, 'user_owner_id' => 1],
                ['id' => '1', 'address' => 'add1', 'capacity' => '150', 'user_id' => '1', 'balance' => '0', 'total_size' => '100'],
                9, false, 'Not enough space on warehouse 1'
            ],
            [
                1, 1, true, 1, true, [],
                true, false, 4, ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 1, 'user_owner_id' => 1],
                ['id' => '1', 'address' => 'add1', 'capacity' => '150', 'user_id' => '1', 'balance' => '0', 'total_size' => '0'],
                9, true
            ]
        ];
    }

    /**
     * @dataProvider dataDetachProduct
     */
    public function testDetachProduct($user_id, $warehouse_id, $warehouse_checked,
                                      $product_id, $product_checked, $product_on_warehouse,
                                      $products_enough, $need_update,  $count,  $product_data, $warehouse_data,
                                      $expected_count, $allOK, $error_type = '')
    {
        $this->dbConnectionMock->expects($this->at(0))
            ->method('fetchAssoc')
            ->with('SELECT products.id as product_id, name, price, size, types.id as type_id, type_name, user_owner_id
                       FROM products, types WHERE products.id = ? AND types.id = type_id',
                [$product_id])
            ->will($this->returnValue($product_data));

        if ($product_checked) {
            $this->dbConnectionMock->expects($this->at(1))
                ->method('fetchAssoc')
                ->with('SELECT * FROM warehouses WHERE id = ?',
                    [$warehouse_id])
                ->will($this->returnValue($warehouse_data));
            if ($warehouse_checked) {
                $this->dbConnectionMock->expects($this->at(2))
                    ->method('fetchAssoc')
                    ->with('SELECT * FROM products_on_warehouse WHERE product_id = ? AND warehouse_id = ?',
                        [$product_id, $warehouse_id])
                    ->will($this->returnValue($product_on_warehouse));

                if ($products_enough) {
                    if ($need_update) {
                        $this->dbConnectionMock->expects($this->any())
                            ->method('executeQuery')
                            ->with('UPDATE products_on_warehouse SET count = ? WHERE product_id = ? AND warehouse_id = ?',
                                [$expected_count, $product_id, $warehouse_id]);
                    } else {
                        $this->dbConnectionMock->expects($this->any())
                            ->method('executeQuery')
                            ->with('DELETE FROM products_on_warehouse WHERE product_id = ? AND warehouse_id = ?',
                                [$product_id, $warehouse_id]);
                    }
                }
            }
        }

        if (!$allOK)
            $this->expectExceptionMessage($error_type);

        $this->warehouseService->DetachProduct($user_id, $warehouse_id, $product_id, $count);
    }

    public function dataDetachProduct()
    {
        return [
            [
                1, 1, true, 1, true, ['product_id' => 1, 'warehouse_id' => 1, 'count' => 5],
                true, true, 4, ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 1, 'user_owner_id' => 1],
                ['id' => '1', 'address' => 'add1', 'capacity' => '150', 'user_id' => '1', 'balance' => '0', 'total_size' => '0'],
                1, true
            ],
            [
                1, 1, true, 2, false, [],
                true, true, 4, [],
                ['id' => '1', 'address' => 'add1', 'capacity' => '150', 'user_id' => '1', 'balance' => '0', 'total_size' => '0'],
                9, false, 'Product does not exist 2'
            ],
            [
                1, 1, true, 1, false, ['product_id' => 1, 'warehouse_id' => 1, 'count' => 5],
                true, true, 4, ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 1, 'user_owner_id' => 2],
                ['id' => '1', 'address' => 'add1', 'capacity' => '150', 'user_id' => '1', 'balance' => '0', 'total_size' => '0'],
                9, false, 'Wrong access 1'
            ],
            [
                1, 2, false, 1, true, [],
                true, true, 4, ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 1, 'user_owner_id' => 1],
                [],
                9, false, 'Warehouse does not exist 2'
            ],
            [
                1, 1, true, 1, true, ['product_id' => 1, 'warehouse_id' => 1, 'count' => 3],
                false, true, 4, ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 1, 'user_owner_id' => 1],
                ['id' => '1', 'address' => 'add1', 'capacity' => '150', 'user_id' => '1', 'balance' => '0', 'total_size' => '0'],
                1, false, 'Not enough products 1 on warehouse 1'
            ],
            [
                1, 1, true, 1, true, ['product_id' => 1, 'warehouse_id' => 1, 'count' => 5],
                true, false, 5, ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 1, 'user_owner_id' => 1],
                ['id' => '1', 'address' => 'add1', 'capacity' => '150', 'user_id' => '1', 'balance' => '0', 'total_size' => '0'],
                1, true
            ]
        ];
    }

    /**
     * @dataProvider dataMoveProduct
     */
    public function testMoveProduct($user_id, $warehouses, $warehouse_checked,
                                    $products, $transaction_id, $movement_type,
                                    $warehouse_data, $committed, $allOK, $error_type = '')
    {
        $idx = 0;

        switch ($movement_type){
            case 'app':
                $this->dbConnectionMock->expects($this->at($idx++))
                    ->method('fetchAssoc')
                    ->with('SELECT * FROM warehouses WHERE id = ?',
                        [$warehouses['to']])
                    ->will($this->returnValue($warehouse_data['to']));
                break;
            case 'detach':
                $this->dbConnectionMock->expects($this->at($idx++))
                    ->method('fetchAssoc')
                    ->with('SELECT * FROM warehouses WHERE id = ?',
                        [$warehouses['from']])
                    ->will($this->returnValue($warehouse_data['from']));
                break;
            case 'move':
                $this->dbConnectionMock->expects($this->at($idx++))
                    ->method('fetchAssoc')
                    ->with('SELECT * FROM warehouses WHERE id = ?',
                        [$warehouses['from']])
                    ->will($this->returnValue($warehouse_data['from']));
                $this->dbConnectionMock->expects($this->at($idx++))
                    ->method('fetchAssoc')
                    ->with('SELECT * FROM warehouses WHERE id = ?',
                        [$warehouses['to']])
                    ->will($this->returnValue($warehouse_data['to']));
                break;
        }

        if ($warehouse_checked['to'] || $warehouse_checked['from']){
            $this->dbConnectionMock->expects($this->at($idx++))
                ->method('beginTransaction');

            $this->dbConnectionMock->expects($this->at($idx++))
                ->method('executeQuery')
                ->with('INSERT INTO transactions (warehouse_from_id, warehouse_to_id, movement_type) VALUES (?, ?, ?)',
            [$warehouses['from'], $warehouses['to'], $movement_type]);

            $this->dbConnectionMock->expects($this->at($idx++))
                ->method('lastInsertId')
                ->will($this->returnValue($transaction_id));

            foreach ($products as $product) {
                if (!$product['end_transaction']) {
                    switch ($movement_type) {
                        case 'app':
                            $this->dbConnectionMock->expects($this->at($idx++))
                                ->method('fetchAssoc')
                                ->with('SELECT products.id as product_id, name, price, size, types.id as type_id, type_name, user_owner_id
                       FROM products, types WHERE products.id = ? AND types.id = type_id',
                                    [$product['id']])
                                ->will($this->returnValue($product['data']));

                            if ($product['checked']) {
                                $this->dbConnectionMock->expects($this->at($idx++))
                                    ->method('fetchAssoc')
                                    ->with('SELECT * FROM warehouses WHERE id = ?',
                                        [$warehouses['to']])
                                    ->will($this->returnValue($warehouse_data['to']));
                                if ($warehouse_checked['to']) {
                                    $this->dbConnectionMock->expects($this->at($idx++))
                                        ->method('fetchAssoc')
                                        ->with('SELECT * FROM products_on_warehouse WHERE product_id = ? AND warehouse_id = ?',
                                            [$product['id'], $warehouses['to']])
                                        ->will($this->returnValue($product['on_warehouse_to']));

                                    if ($product['enough']) {
                                        if ($product['need_update_to']) {
                                            $this->dbConnectionMock->expects($this->at($idx++))
                                                ->method('executeQuery')
                                                ->with('UPDATE products_on_warehouse SET count = ? WHERE product_id = ? AND warehouse_id = ?',
                                                    [$product['expected_count_to'], $product['id'], $warehouses['to']]);
                                        } else {
                                            $this->dbConnectionMock->expects($this->at($idx++))
                                                ->method('executeQuery')
                                                ->with('INSERT INTO products_on_warehouse (product_id, warehouse_id, count) VALUES (?, ?, ?)',
                                                    [$product['id'], $warehouses['to'], $product['count']]);
                                        }
                                    }
                                }

                                $this->dbConnectionMock->expects($this->at($idx++))
                                    ->method('fetchAssoc');

                                $this->dbConnectionMock->expects($this->at($idx++))
                                    ->method('executeQuery')
                                    ->with('INSERT INTO products_on_transaction (transaction_id, product_id, count) VALUES (?, ?, ?)',
                                        [
                                            $transaction_id,
                                            $product['id'],
                                            $product['count']
                                        ]);
                            }
                            break;
                        case 'detach':
                            $this->dbConnectionMock->expects($this->at($idx++))
                                ->method('fetchAssoc')
                                ->with('SELECT products.id as product_id, name, price, size, types.id as type_id, type_name, user_owner_id
                       FROM products, types WHERE products.id = ? AND types.id = type_id',
                                    [$product['id']])
                                ->will($this->returnValue($product['data']));

                            if ($product['checked']) {
                                $this->dbConnectionMock->expects($this->at($idx++))
                                    ->method('fetchAssoc')
                                    ->with('SELECT * FROM warehouses WHERE id = ?',
                                        [$warehouses['from']])
                                    ->will($this->returnValue($warehouse_data['from']));
                                if ($warehouse_checked['from']) {
                                    $this->dbConnectionMock->expects($this->at($idx++))
                                        ->method('fetchAssoc')
                                        ->with('SELECT * FROM products_on_warehouse WHERE product_id = ? AND warehouse_id = ?',
                                            [$product['id'], $warehouses['from']])
                                        ->will($this->returnValue($product['on_warehouse_from']));

                                    if ($product['enough']) {
                                        if ($product['need_update_from']) {
                                            $this->dbConnectionMock->expects($this->at($idx++))
                                                ->method('executeQuery')
                                                ->with('UPDATE products_on_warehouse SET count = ? WHERE product_id = ? AND warehouse_id = ?',
                                                    [$product['expected_count_from'], $product['id'], $warehouses['from']]);
                                        } else {
                                            $this->dbConnectionMock->expects($this->at($idx++))
                                                ->method('executeQuery')
                                                ->with('DELETE FROM products_on_warehouse WHERE product_id = ? AND warehouse_id = ?',
                                                    [$product['id'], $warehouses['from']]);
                                        }
                                    }
                                }

                                $this->dbConnectionMock->expects($this->at($idx++))
                                    ->method('fetchAssoc');

                                $this->dbConnectionMock->expects($this->at($idx++))
                                    ->method('executeQuery')
                                    ->with('INSERT INTO products_on_transaction (transaction_id, product_id, count) VALUES (?, ?, ?)',
                                        [
                                            $transaction_id,
                                            $product['id'],
                                            $product['count']
                                        ]);
                            }
                            break;
                        case 'move':
                            $this->dbConnectionMock->expects($this->at($idx++))
                                ->method('fetchAssoc')
                                ->with('SELECT products.id as product_id, name, price, size, types.id as type_id, type_name, user_owner_id
                       FROM products, types WHERE products.id = ? AND types.id = type_id',
                                    [$product['id']])
                                ->will($this->returnValue($product['data']));

                            if ($product['checked']) {
                                $this->dbConnectionMock->expects($this->at($idx++))
                                    ->method('fetchAssoc')
                                    ->with('SELECT * FROM warehouses WHERE id = ?',
                                        [$warehouses['from']])
                                    ->will($this->returnValue($warehouse_data['from']));
                                if ($warehouse_checked['from']) {
                                    $this->dbConnectionMock->expects($this->at($idx++))
                                        ->method('fetchAssoc')
                                        ->with('SELECT * FROM products_on_warehouse WHERE product_id = ? AND warehouse_id = ?',
                                            [$product['id'], $warehouses['from']])
                                        ->will($this->returnValue($product['on_warehouse_from']));

                                    if ($product['enough']) {
                                        if ($product['need_update_from']) {
                                            $this->dbConnectionMock->expects($this->at($idx++))
                                                ->method('executeQuery')
                                                ->with('UPDATE products_on_warehouse SET count = ? WHERE product_id = ? AND warehouse_id = ?',
                                                    [$product['expected_count_from'], $product['id'], $warehouses['from']]);
                                        } else {
                                            $this->dbConnectionMock->expects($this->at($idx++))
                                                ->method('executeQuery')
                                                ->with('DELETE FROM products_on_warehouse WHERE product_id = ? AND warehouse_id = ?',
                                                    [$product['id'], $warehouses['from']]);
                                        }

                                        $this->dbConnectionMock->expects($this->at($idx++))
                                            ->method('fetchAssoc')
                                            ->with('SELECT products.id as product_id, name, price, size, types.id as type_id, type_name, user_owner_id
                       FROM products, types WHERE products.id = ? AND types.id = type_id',
                                                [$product['id']])
                                            ->will($this->returnValue($product['data']));

                                        $this->dbConnectionMock->expects($this->at($idx++))
                                            ->method('fetchAssoc')
                                            ->with('SELECT * FROM warehouses WHERE id = ?',
                                                [$warehouses['to']])
                                            ->will($this->returnValue($warehouse_data['to']));

                                        if ($product['space']) {

                                            if ($warehouse_checked['to']) {
                                                $this->dbConnectionMock->expects($this->at($idx++))
                                                    ->method('fetchAssoc')
                                                    ->with('SELECT * FROM products_on_warehouse WHERE product_id = ? AND warehouse_id = ?',
                                                        [$product['id'], $warehouses['to']])
                                                    ->will($this->returnValue($product['on_warehouse_to']));

                                                if ($product['need_update_to']) {
                                                    $this->dbConnectionMock->expects($this->at($idx++))
                                                        ->method('executeQuery')
                                                        ->with('UPDATE products_on_warehouse SET count = ? WHERE product_id = ? AND warehouse_id = ?',
                                                            [$product['expected_count_to'], $product['id'], $warehouses['to']]);
                                                } else {
                                                    $this->dbConnectionMock->expects($this->at($idx++))
                                                        ->method('executeQuery')
                                                        ->with('INSERT INTO products_on_warehouse (product_id, warehouse_id, count) VALUES (?, ?, ?)',
                                                            [$product['id'], $warehouses['to'], $product['count']]);
                                                }

                                                $this->dbConnectionMock->expects($this->at($idx++))
                                                    ->method('fetchAssoc');

                                                $this->dbConnectionMock->expects($this->at($idx++))
                                                    ->method('executeQuery')
                                                    ->with('INSERT INTO products_on_transaction (transaction_id, product_id, count) VALUES (?, ?, ?)',
                                                        [
                                                            $transaction_id,
                                                            $product['id'],
                                                            $product['count']
                                                        ]);
                                            }
                                        }
                                    }
                                }
                            }
                            break;
                    }
                } else
                    break;
            }

            if ($committed)
                $this->dbConnectionMock->expects($this->at($idx++))
                    ->method('commit');
            else
                $this->dbConnectionMock->expects($this->at($idx++))
                    ->method('rollback');
        }

        if (!$allOK)
            $this->expectExceptionMessage($error_type);

        $this->warehouseService->MoveProducts($user_id, $products, $warehouses, $movement_type);
    }

    public function dataMoveProduct()
    {
        return [
            [
                1, ['to' => 1], ['to' => true], [['id' => 1, 'checked' => true,
                'on_warehouse_to' => ['product_id' => 1, 'warehouse_id' => 1, 'count' => 5], 'enough' => true,
                'need_update_to' => true, 'count' => 4, 'expected_count_to' => 9, 'allOK' => true, 'error_type' => '',
                'data' => ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 56, 'user_owner_id' => 1],
                ]], 1, 'app',
                ['to' => ['id' => '1', 'address' => 'add1', 'capacity' => '150', 'user_id' => '1', 'balance' => '0', 'total_size' => '0']],
                true, true
            ],
            [
                1, ['to' => 1], ['to' => true], [['id' => 1, 'checked' => true,
                'on_warehouse_to' => ['product_id' => 1, 'warehouse_id' => 1, 'count' => 5], 'enough' => true,
                'need_update_to' => true, 'count' => 4, 'expected_count_to' => 9, 'allOK' => true, 'error_type' => '',
                'data' => ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 56, 'user_owner_id' => 1],
            ], ['id' => 2, 'checked' => true, 'enough' => true,
                'need_update_to' => false, 'count' => 4, 'expected_count_to' => 4, 'allOK' => true, 'error_type' => '',
                'data' => ['product_id' => 2, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 56, 'user_owner_id' => 1],
            ],
                ['id' => 3, 'checked' => true, 'on_warehouse_to' => ['product_id' => 3, 'warehouse_id' => 1, 'count' => 2], 'enough' => true,
                    'need_update_to' => true, 'count' => 10, 'expected_count_to' => 12, 'allOK' => true, 'error_type' => '',
                    'data' => ['product_id' => 3, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 56, 'user_owner_id' => 1],
                ]], 1, 'app',
                ['to' => ['id' => '1', 'address' => 'add1', 'capacity' => '150', 'user_id' => '1', 'balance' => '0', 'total_size' => '0']],
                true, true
            ],
            [
                1, ['from' => 1], ['from' => true], [['id' => 1, 'checked' => true,
                'on_warehouse_from' => ['product_id' => 1, 'warehouse_id' => 1, 'count' => 5], 'enough' => true,
                'need_update_from' => true, 'count' => 4, 'expected_count_from' => 1, 'allOK' => true, 'error_type' => '',
                'data' => ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 56, 'user_owner_id' => 1],
            ], ['id' => 2, 'checked' => true,
                'on_warehouse_from' => ['product_id' => 2, 'warehouse_id' => 1, 'count' => 7], 'enough' => true,
                'need_update_from' => false, 'count' => 7, 'expected_count_from' => 0, 'allOK' => true, 'error_type' => '',
                'data' => ['product_id' => 2, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 56, 'user_owner_id' => 1],
            ]], 1, 'detach',
                ['from' => ['id' => '1', 'address' => 'add1', 'capacity' => '150', 'user_id' => '1', 'balance' => '0', 'total_size' => '0']],
                true, true
            ],
            [
                1, ['from' => 1, 'to' => 2], ['from' => true, 'to' => true], [['id' => 1, 'checked' => true, 'space' => true,
                'on_warehouse_from' => ['product_id' => 1, 'warehouse_id' => 1, 'count' => 5], 'enough' => true, 'need_update_to' => false,
                'need_update_from' => true, 'count' => 4, 'expected_count_from' => 1, 'expected_count_to' => 4, 'allOK' => true, 'error_type' => '',
                'data' => ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 56, 'user_owner_id' => 1],
            ]], 1, 'move',
                ['from' => ['id' => '1', 'address' => 'add1', 'capacity' => '150', 'user_id' => '1', 'balance' => '0', 'total_size' => '0'],
                    'to' => ['id' => '2', 'address' => 'add2', 'capacity' => '150', 'user_id' => '1', 'balance' => '0', 'total_size' => '0']],
                true, true
            ],
            [
                1, ['from' => 1, 'to' => 2], ['from' => true, 'to' => true], [['id' => 1, 'checked' => true, 'space' => true,
                'on_warehouse_from' => ['product_id' => 1, 'warehouse_id' => 1, 'count' => 5], 'enough' => true, 'need_update_to' => false,
                'need_update_from' => true, 'count' => 4, 'expected_count_from' => 1, 'expected_count_to' => 4, 'allOK' => true, 'error_type' => '',
                'data' => ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 56, 'user_owner_id' => 1],
            ], ['id' => 2, 'checked' => true, 'space' => true,
                'on_warehouse_from' => ['product_id' => 2, 'warehouse_id' => 1, 'count' => 10],
                'on_warehouse_to' => ['product_id' => 2, 'warehouse_id' => 2, 'count' => 14], 'enough' => true, 'need_update_to' => true,
                'need_update_from' => true, 'count' => 3, 'expected_count_from' => 7, 'expected_count_to' => 17, 'allOK' => true, 'error_type' => '',
                'data' => ['product_id' => 2, 'name' => 'name2', 'price' => 10, 'size' => 10, 'type_id' => 56, 'user_owner_id' => 1],
            ],
                ['id' => 3, 'checked' => true, 'space' => true,
                    'on_warehouse_from' => ['product_id' => 3, 'warehouse_id' => 1, 'count' => 6],
                    'on_warehouse_to' => ['product_id' => 3, 'warehouse_id' => 2, 'count' => 2], 'enough' => true, 'need_update_to' => true,
                    'need_update_from' => true, 'count' => 5, 'expected_count_from' => 1, 'expected_count_to' => 7, 'allOK' => true, 'error_type' => '',
                    'data' => ['product_id' => 3, 'name' => 'name2', 'price' => 10, 'size' => 10, 'type_id' => 56, 'user_owner_id' => 1],
                ]], 1, 'move',
                ['from' => ['id' => '1', 'address' => 'add1', 'capacity' => '150', 'user_id' => '1', 'balance' => '0', 'total_size' => '0'],
                    'to' => ['id' => '2', 'address' => 'add2', 'capacity' => '150', 'user_id' => '1', 'balance' => '0', 'total_size' => '0']],
                true, true
            ],
            [
                1, ['from' => 1, 'to' => 1], ['from' => false, 'to' => false], [['id' => 1, 'checked' => true,
                'on_warehouse_from' => ['product_id' => 1, 'warehouse_id' => 1, 'count' => 5], 'enough' => true, 'need_update_to' => false,
                'need_update_from' => true, 'count' => 4, 'expected_count_from' => 1, 'expected_count_to' => 4, 'allOK' => true, 'error_type' => '',
                'data' => ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 56, 'user_owner_id' => 1],
            ]], 1, 'move',
                ['from' => ['id' => '1', 'address' => 'add1', 'capacity' => '150', 'user_id' => '1', 'balance' => '0', 'total_size' => '0'],
                    'to' => ['id' => '1', 'address' => 'add1', 'capacity' => '150', 'user_id' => '1', 'balance' => '0', 'total_size' => '0']],
                true, false, 'Warehouses are the same!'
            ],
            [
                1, ['to' => 1], ['to' => true], [['id' => 1, 'checked' => false,
                'on_warehouse_to' => [], 'enough' => true,
                'need_update_to' => true, 'count' => 4, 'expected_count_to' => 9, 'allOK' => true, 'error_type' => '',
                'data' => [],
            ]], 1, 'app',
                ['to' => ['id' => '1', 'address' => 'add1', 'capacity' => '150', 'user_id' => '1', 'balance' => '0', 'total_size' => '0']],
                false, false, 'Product does not exist 1'
            ],
            [
                1, ['from' => 1, 'to' => 2], ['from' => true, 'to' => true], [['id' => 1, 'checked' => true, 'space' => true,
                'on_warehouse_from' => ['product_id' => 1, 'warehouse_id' => 1, 'count' => 5], 'enough' => true, 'need_update_to' => false,
                'need_update_from' => true, 'count' => 4, 'expected_count_from' => 1, 'expected_count_to' => 4, 'allOK' => true, 'error_type' => '',
                'data' => ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 56, 'user_owner_id' => 1],
            ], ['id' => 2, 'checked' => false, 'space' => true,
                'on_warehouse_from' => [],
                'on_warehouse_to' => [], 'enough' => true, 'need_update_to' => true,
                'need_update_from' => true, 'count' => 3, 'expected_count_from' => 7, 'expected_count_to' => 17, 'allOK' => true, 'error_type' => '',
                'data' => [], 'end_transaction' => true,
            ],
                ['id' => 3, 'checked' => false, 'space' => true,
                    'on_warehouse_from' => ['product_id' => 3, 'warehouse_id' => 1, 'count' => 6],
                    'on_warehouse_to' => ['product_id' => 3, 'warehouse_id' => 2, 'count' => 2], 'enough' => true, 'need_update_to' => true,
                    'need_update_from' => true, 'count' => 5, 'expected_count_from' => 1, 'expected_count_to' => 7, 'allOK' => true, 'error_type' => '',
                    'data' => ['product_id' => 3, 'name' => 'name2', 'price' => 10, 'size' => 10, 'type_id' => 56, 'user_owner_id' => 1],
                ]], 1, 'move',
                ['from' => ['id' => '1', 'address' => 'add1', 'capacity' => '150', 'user_id' => '1', 'balance' => '0', 'total_size' => '0'],
                    'to' => ['id' => '2', 'address' => 'add2', 'capacity' => '150', 'user_id' => '1', 'balance' => '0', 'total_size' => '0']],
                false, false, 'Product does not exist 2'
            ],
            [
                1, ['from' => 1, 'to' => 2], ['from' => true, 'to' => true], [['id' => 1, 'checked' => true, 'space' => true,
                'on_warehouse_from' => ['product_id' => 1, 'warehouse_id' => 1, 'count' => 5], 'enough' => true, 'need_update_to' => false,
                'need_update_from' => true, 'count' => 4, 'expected_count_from' => 1, 'expected_count_to' => 4, 'allOK' => true, 'error_type' => '',
                'data' => ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 56, 'user_owner_id' => 1],
            ], ['id' => 2, 'checked' => true, 'space' => true,
                'on_warehouse_from' => ['product_id' => 2, 'warehouse_id' => 1, 'count' => 10],
                'on_warehouse_to' => ['product_id' => 2, 'warehouse_id' => 2, 'count' => 14], 'enough' => true, 'need_update_to' => true,
                'need_update_from' => true, 'count' => 3, 'expected_count_from' => 7, 'expected_count_to' => 17, 'allOK' => true, 'error_type' => '',
                'data' => ['product_id' => 2, 'name' => 'name2', 'price' => 10, 'size' => 10, 'type_id' => 56, 'user_owner_id' => 1],
            ],
                ['id' => 3, 'checked' => false,
                    'on_warehouse_from' => ['product_id' => 3, 'warehouse_id' => 1, 'count' => 6], 'space' => true,
                    'on_warehouse_to' => ['product_id' => 3, 'warehouse_id' => 2, 'count' => 2], 'enough' => true, 'need_update_to' => true,
                    'need_update_from' => true, 'count' => 5, 'expected_count_from' => 1, 'expected_count_to' => 7, 'allOK' => true, 'error_type' => '',
                    'data' => ['product_id' => 3, 'name' => 'name2', 'price' => 10, 'size' => 10, 'type_id' => 56, 'user_owner_id' => 2],
                ]], 1, 'move',
                ['from' => ['id' => '1', 'address' => 'add1', 'capacity' => '150', 'user_id' => '1', 'balance' => '0', 'total_size' => '0'],
                    'to' => ['id' => '2', 'address' => 'add2', 'capacity' => '150', 'user_id' => '1', 'balance' => '0', 'total_size' => '0']],
                false, false, 'Wrong access 3'
            ],
            [
                1, ['from' => 1, 'to' => 2], ['from' => true, 'to' => true], [['id' => 1, 'checked' => true, 'space' => true,
                'on_warehouse_from' => ['product_id' => 1, 'warehouse_id' => 1, 'count' => 5], 'enough' => true, 'need_update_to' => false,
                'need_update_from' => true, 'count' => 4, 'expected_count_from' => 1, 'expected_count_to' => 4, 'allOK' => true, 'error_type' => '',
                'data' => ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 56, 'user_owner_id' => 1],
            ], ['id' => 2, 'checked' => true, 'space' => true,
                'on_warehouse_from' => ['product_id' => 2, 'warehouse_id' => 1, 'count' => 2],
                'on_warehouse_to' => ['product_id' => 2, 'warehouse_id' => 2, 'count' => 14], 'enough' => false, 'need_update_to' => true,
                'need_update_from' => true, 'count' => 3, 'expected_count_from' => 7, 'expected_count_to' => 17, 'allOK' => true, 'error_type' => '',
                'data' => ['product_id' => 2, 'name' => 'name2', 'price' => 10, 'size' => 10, 'type_id' => 56, 'user_owner_id' => 1]
            ],
                ['id' => 3, 'checked' => true, 'space' => true,
                    'on_warehouse_from' => ['product_id' => 3, 'warehouse_id' => 1, 'count' => 6],
                    'on_warehouse_to' => ['product_id' => 3, 'warehouse_id' => 2, 'count' => 2], 'enough' => true, 'need_update_to' => true,
                    'need_update_from' => true, 'count' => 5, 'expected_count_from' => 1, 'expected_count_to' => 7, 'allOK' => true, 'error_type' => '',
                    'data' => ['product_id' => 3, 'name' => 'name2', 'price' => 10, 'size' => 10, 'type_id' => 56, 'user_owner_id' => 1],
                    'end_transaction' => true
                ]], 1, 'move',
                ['from' => ['id' => '1', 'address' => 'add1', 'capacity' => '150', 'user_id' => '1', 'balance' => '0', 'total_size' => '0'],
                    'to' => ['id' => '2', 'address' => 'add2', 'capacity' => '150', 'user_id' => '1', 'balance' => '0', 'total_size' => '0']],
                false, false, 'Not enough products 2 on warehouse 1'
            ],
            [
                1, ['from' => 1, 'to' => 2], ['from' => false, 'to' => false], [['id' => 1, 'checked' => true, 'space' => true,
                'on_warehouse_from' => ['product_id' => 1, 'warehouse_id' => 1, 'count' => 5], 'enough' => true, 'need_update_to' => false,
                'need_update_from' => true, 'count' => 4, 'expected_count_from' => 1, 'expected_count_to' => 4, 'allOK' => true, 'error_type' => '',
                'data' => ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 56, 'user_owner_id' => 1]
            ], ['id' => 2, 'checked' => true,
                'on_warehouse_from' => ['product_id' => 2, 'warehouse_id' => 1, 'count' => 10], 'space' => true,
                'on_warehouse_to' => ['product_id' => 2, 'warehouse_id' => 2, 'count' => 14], 'enough' => true, 'need_update_to' => true,
                'need_update_from' => true, 'count' => 3, 'expected_count_from' => 7, 'expected_count_to' => 17, 'allOK' => true, 'error_type' => '',
                'data' => ['product_id' => 2, 'name' => 'name2', 'price' => 10, 'size' => 10, 'type_id' => 56, 'user_owner_id' => 1],
                'end_transaction' => true
            ],
                ['id' => 3, 'checked' => true,
                    'on_warehouse_from' => ['product_id' => 3, 'warehouse_id' => 1, 'count' => 6], 'space' => true,
                    'on_warehouse_to' => ['product_id' => 3, 'warehouse_id' => 2, 'count' => 2], 'enough' => true, 'need_update_to' => true,
                    'need_update_from' => true, 'count' => 5, 'expected_count_from' => 1, 'expected_count_to' => 7, 'allOK' => true, 'error_type' => '',
                    'data' => ['product_id' => 3, 'name' => 'name2', 'price' => 10, 'size' => 10, 'type_id' => 56, 'user_owner_id' => 1],
                ]], 1, 'move',
                ['from' => ['id' => '1', 'address' => 'add1', 'capacity' => '150', 'user_id' => '1', 'balance' => '0', 'total_size' => '0'],
                    'to' => ['id' => '2', 'address' => 'add2', 'capacity' => '150', 'user_id' => '2', 'balance' => '0', 'total_size' => '0']],
                false, false, 'Wrong access 2'
            ],
            [
                1, ['from' => 1, 'to' => 2], ['from' => false, 'to' => false], [['id' => 1, 'checked' => true, 'space' => true,
                'on_warehouse_from' => ['product_id' => 1, 'warehouse_id' => 1, 'count' => 5], 'enough' => true, 'need_update_to' => false,
                'need_update_from' => true, 'count' => 4, 'expected_count_from' => 1, 'expected_count_to' => 4, 'allOK' => true, 'error_type' => '',
                'data' => ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 56, 'user_owner_id' => 1]
            ], ['id' => 2, 'checked' => true, 'space' => true,
                'on_warehouse_from' => ['product_id' => 2, 'warehouse_id' => 1, 'count' => 10],
                'on_warehouse_to' => ['product_id' => 2, 'warehouse_id' => 2, 'count' => 14], 'enough' => true, 'need_update_to' => true,
                'need_update_from' => true, 'count' => 3, 'expected_count_from' => 7, 'expected_count_to' => 17, 'allOK' => true, 'error_type' => '',
                'data' => ['product_id' => 2, 'name' => 'name2', 'price' => 10, 'size' => 10, 'type_id' => 56, 'user_owner_id' => 1],
                'end_transaction' => true
            ],
                ['id' => 3, 'checked' => true, 'space' => true,
                    'on_warehouse_from' => ['product_id' => 3, 'warehouse_id' => 1, 'count' => 6],
                    'on_warehouse_to' => ['product_id' => 3, 'warehouse_id' => 2, 'count' => 2], 'enough' => true, 'need_update_to' => true,
                    'need_update_from' => true, 'count' => 5, 'expected_count_from' => 1, 'expected_count_to' => 7, 'allOK' => true, 'error_type' => '',
                    'data' => ['product_id' => 3, 'name' => 'name2', 'price' => 10, 'size' => 10, 'type_id' => 56, 'user_owner_id' => 1],
                ]], 1, 'move',
                ['from' => ['id' => '1', 'address' => 'add1', 'capacity' => '150', 'user_id' => '1', 'balance' => '0', 'total_size' => '0'],
                    'to' => []],
                false, false, 'Warehouse does not exist 2'
            ],
            [
                1, ['from' => 1, 'to' => 2], ['from' => true, 'to' => true], [['id' => 1, 'checked' => true,
                'on_warehouse_from' => ['product_id' => 1, 'warehouse_id' => 1, 'count' => 5], 'enough' => true, 'need_update_to' => false,
                'space' => true,
                'need_update_from' => true, 'count' => 4, 'expected_count_from' => 1, 'expected_count_to' => 4, 'allOK' => true, 'error_type' => '',
                'data' => ['product_id' => 1, 'name' => 'name1', 'price' => 10, 'size' => 10, 'type_id' => 56, 'user_owner_id' => 1],
            ], ['id' => 2, 'checked' => true,
                'on_warehouse_from' => ['product_id' => 2, 'warehouse_id' => 1, 'count' => 10],
                'on_warehouse_to' => ['product_id' => 2, 'warehouse_id' => 2, 'count' => 14], 'enough' => true,
                'space' => false, 'need_update_to' => true,
                'need_update_from' => true, 'count' => 3, 'expected_count_from' => 7, 'expected_count_to' => 17, 'allOK' => true, 'error_type' => '',
                'data' => ['product_id' => 2, 'name' => 'name2', 'price' => 10, 'size' => 100, 'type_id' => 56, 'user_owner_id' => 1]
            ],
                ['id' => 3, 'checked' => true,
                    'on_warehouse_from' => ['product_id' => 3, 'warehouse_id' => 1, 'count' => 6], 'space' => true,
                    'on_warehouse_to' => ['product_id' => 3, 'warehouse_id' => 2, 'count' => 2], 'enough' => true, 'need_update_to' => true,
                    'need_update_from' => true, 'count' => 5, 'expected_count_from' => 1, 'expected_count_to' => 7, 'allOK' => true, 'error_type' => '',
                    'data' => ['product_id' => 3, 'name' => 'name2', 'price' => 10, 'size' => 10, 'type_id' => 56, 'user_owner_id' => 1],
                    'end_transaction' => true
                ]], 1, 'move',
                ['from' => ['id' => '1', 'address' => 'add1', 'capacity' => '150', 'user_id' => '1', 'balance' => '0', 'total_size' => '0'],
                    'to' => ['id' => '2', 'address' => 'add2', 'capacity' => '150', 'user_id' => '1', 'balance' => '0', 'total_size' => '0']],
                false, false, 'Not enough space on warehouse 2'
            ]
        ];
    }

    /**
     * @dataProvider dataGetLogs
     */
    public function testGetLogs($user_id, $warehouse_id, $warehouse_checked,
                                $warehouse_data, $transactions, $expected, $allOK, $error_type = '')
    {
        $idx = 0;
        $current_date = date('Y-m-d H:i:s');

        $this->dbConnectionMock->expects($this->at($idx++))
            ->method('fetchAssoc')
            ->with('SELECT * FROM warehouses WHERE id = ?',
                [$warehouse_id])
            ->will($this->returnValue($warehouse_data));

        if ($warehouse_checked) {

            $QueryTransactionMock = $this->getMockBuilder('\PDOStatement')
                ->getMock();

            for ($i = 0; $i <= count($transactions); $i++) {
                $QueryTransactionMock->expects($this->at($i))
                    ->method('fetch')
                    ->will($this->returnValue($transactions[$i]));
            }

            $this->dbConnectionMock->expects($this->at($idx++))
                ->method('executeQuery')
                ->with('SELECT * FROM transactions 
                   WHERE (warehouse_from_id = ? OR warehouse_to_id = ?) AND date <= ?',
                    [$warehouse_id, $warehouse_id, $current_date])
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

        $logs = $this->warehouseService->GetLogs($user_id, $warehouse_id);

        if ($allOK)
            $this->assertEquals($logs, $expected);
    }

    public function dataGetLogs()
    {
        return [
            [
                1, 1, true, ['id' => '1', 'address' => 'add1', 'capacity' => '150', 'user_id' => '1', 'balance' => '0', 'total_size' => '0'],
                [['id' => 1, 'warehouse_from_id' => null, 'warehouse_to_id' => 1, 'movement_type' => 'app', 'date' => '2018-08-09', 'total_count' => 100,
                    'products' => [['product_id' => 1,  'count' => 5, 'amount' => 100]]]],
                [['id' => 1, 'movement_type' => 'app', 'warehouse_id' => 1, 'date' => '2018-08-09', 'total_count' => 100,
                    'product_list' => [1 => ['count' => 5, 'amount' => 100]]]], true
            ],
            [
                1, 1, true, ['id' => '1', 'address' => 'add1', 'capacity' => '150', 'user_id' => '1', 'balance' => '0', 'total_size' => '0'],
                [
                    ['id' => 1, 'warehouse_from_id' => null, 'warehouse_to_id' => 1, 'movement_type' => 'app', 'date' => '2018-08-09', 'total_count' => 100,
                    'products' =>
                        [
                            ['product_id' => 1,  'count' => 5, 'amount' => 100],
                            ['product_id' => 2,  'count' => 7, 'amount' => 150]
                        ]
                    ]
                ],
                [
                    ['id' => 1, 'movement_type' => 'app', 'warehouse_id' => 1, 'date' => '2018-08-09', 'total_count' => 100,
                    'product_list' => [
                        1 => ['count' => 5, 'amount' => 100],
                        2 => ['count' => 7, 'amount' => 150]]
                    ]
                ], true
            ],
            [
                1, 1, true, ['id' => '1', 'address' => 'add1', 'capacity' => '150', 'user_id' => '1', 'balance' => '0', 'total_size' => '0'],
                [
                    ['id' => 1, 'warehouse_from_id' => null, 'warehouse_to_id' => 1, 'movement_type' => 'app', 'date' => '2018-08-09', 'total_count' => 100,
                        'products' =>
                            [
                                ['product_id' => 1,  'count' => 5, 'amount' => 100],
                                ['product_id' => 2,  'count' => 7, 'amount' => 150]
                            ]
                    ],
                    ['id' => 2, 'warehouse_from_id' => null, 'warehouse_to_id' => 1, 'movement_type' => 'app', 'date' => '2018-08-10', 'total_count' => 100,
                        'products' =>
                            [
                                ['product_id' => 1,  'count' => 5, 'amount' => 100],
                                ['product_id' => 2,  'count' => 7, 'amount' => 150]
                            ]
                    ]
                ],
                [
                    ['id' => 1, 'movement_type' => 'app', 'warehouse_id' => 1, 'date' => '2018-08-09', 'total_count' => 100,
                        'product_list' => [
                            1 => ['count' => 5, 'amount' => 100],
                            2 => ['count' => 7, 'amount' => 150]]
                    ],
                    ['id' => 2, 'movement_type' => 'app', 'warehouse_id' => 1, 'date' => '2018-08-10', 'total_count' => 100,
                        'product_list' => [
                            1 => ['count' => 5, 'amount' => 100],
                            2 => ['count' => 7, 'amount' => 150]]
                    ]
                ], true
            ],
            [
                1, 1, true, ['id' => '1', 'address' => 'add1', 'capacity' => '150', 'user_id' => '1', 'balance' => '0', 'total_size' => '0'],
                [
                    ['id' => 1, 'warehouse_from_id' => null, 'warehouse_to_id' => 1, 'movement_type' => 'app', 'date' => '2018-08-09', 'total_count' => 100,
                        'products' =>
                            [
                                ['product_id' => 1,  'count' => 5, 'amount' => 100],
                                ['product_id' => 2,  'count' => 7, 'amount' => 150]
                            ]
                    ],
                    ['id' => 2, 'warehouse_from_id' => null, 'warehouse_to_id' => 1, 'movement_type' => 'app', 'date' => '2018-08-10', 'total_count' => 100,
                        'products' =>
                            [
                                ['product_id' => 1,  'count' => 5, 'amount' => 100],
                                ['product_id' => 2,  'count' => 7, 'amount' => 150]
                            ]
                    ],
                    ['id' => 3, 'warehouse_from_id' => 1, 'warehouse_to_id' => null, 'movement_type' => 'detach', 'date' => '2018-08-10', 'total_count' => 100,
                        'products' =>
                            [
                                ['product_id' => 1,  'count' => 5, 'amount' => 100]
                            ]
                    ]
                ],
                [
                    ['id' => 1, 'movement_type' => 'app', 'warehouse_id' => 1, 'date' => '2018-08-09', 'total_count' => 100,
                        'product_list' => [
                            1 => ['count' => 5, 'amount' => 100],
                            2 => ['count' => 7, 'amount' => 150]]
                    ],
                    ['id' => 2, 'movement_type' => 'app', 'warehouse_id' => 1, 'date' => '2018-08-10', 'total_count' => 100,
                        'product_list' => [
                            1 => ['count' => 5, 'amount' => 100],
                            2 => ['count' => 7, 'amount' => 150]]
                    ],
                    ['id' => 3, 'movement_type' => 'detach', 'warehouse_id' => 1, 'date' => '2018-08-10', 'total_count' => 100,
                        'product_list' => [
                            1 => ['count' => 5, 'amount' => 100]]
                    ]
                ], true
            ],
            [
                1, 1, true, ['id' => '1', 'address' => 'add1', 'capacity' => '150', 'user_id' => '1', 'balance' => '0', 'total_size' => '0'],
                [
                    ['id' => 1, 'warehouse_from_id' => null, 'warehouse_to_id' => 1, 'movement_type' => 'app', 'date' => '2018-08-09', 'total_count' => 100,
                        'products' =>
                            [
                                ['product_id' => 1,  'count' => 5, 'amount' => 100],
                                ['product_id' => 2,  'count' => 7, 'amount' => 150]
                            ]
                    ],
                    ['id' => 2, 'warehouse_from_id' => 2, 'warehouse_to_id' => 1, 'movement_type' => 'move', 'date' => '2018-08-10', 'total_count' => 100,
                        'products' =>
                            [
                                ['product_id' => 1,  'count' => 5, 'amount' => 100],
                                ['product_id' => 2,  'count' => 7, 'amount' => 150],
                                ['product_id' => 3,  'count' => 1, 'amount' => 90]
                            ]
                    ],
                    ['id' => 3, 'warehouse_from_id' => 1, 'warehouse_to_id' => null, 'movement_type' => 'detach', 'date' => '2018-08-10', 'total_count' => 100,
                        'products' =>
                            [
                                ['product_id' => 1,  'count' => 5, 'amount' => 100]
                            ]
                    ]
                ],
                [
                    ['id' => 1, 'movement_type' => 'app', 'warehouse_id' => 1, 'date' => '2018-08-09', 'total_count' => 100,
                        'product_list' => [
                            1 => ['count' => 5, 'amount' => 100],
                            2 => ['count' => 7, 'amount' => 150]]
                    ],
                    ['id' => 2, 'movement_type' => 'move', 'warehouse_from_id' => 2, 'warehouse_to_id' => 1, 'date' => '2018-08-10', 'total_count' => 100,
                        'product_list' => [
                            1 => ['count' => 5, 'amount' => 100],
                            2 => ['count' => 7, 'amount' => 150],
                            3 => ['count' => 1, 'amount' => 90]]
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
                    'product_list' => [1 => ['count' => 5, 'amount' => 100]]]], false, 'Warehouse does not exist 1'
            ],
            [
                1, 1, false, ['id' => '1', 'address' => 'add1', 'capacity' => '150', 'user_id' => '2', 'balance' => '0', 'total_size' => '0'],
                [['id' => 1, 'warehouse_from_id' => null, 'warehouse_to_id' => 1, 'movement_type' => 'app', 'date' => '2018-08-09', 'total_count' => 100,
                    'products' => [['product_id' => 1,  'count' => 5, 'amount' => 100]]]],
                [['id' => 1, 'movement_type' => 'app', 'warehouse_id' => 1, 'date' => '2018-08-09', 'total_count' => 100,
                    'product_list' => [1 => ['count' => 5, 'amount' => 100]]]], false, 'Wrong access 1'
            ]
        ];
    }

    /**
     * @dataProvider dataDeleteWarehouse
     */
    public function testDeleteWarehouse($user_id, $warehouse_id, $warehouse_data, $warehouse_checked,
                                        $logs_count, $allOK, $error_type = '')
    {
        $idx = 0;

        $this->dbConnectionMock->expects($this->at($idx++))
            ->method('fetchAssoc')
            ->with('SELECT * FROM warehouses WHERE id = ?',
                [$warehouse_id])
            ->will($this->returnValue($warehouse_data));

        if ($warehouse_checked) {
            $this->dbConnectionMock->expects($this->at($idx++))
                ->method('fetchAssoc')
                ->with('SELECT COUNT(*) FROM transactions
                        WHERE warehouse_from_id = ? OR warehouse_to_id = ?',
                    [$warehouse_id, $warehouse_id])
                ->will($this->returnValue(['COUNT(*)' => $logs_count]));

            if ($logs_count == 0)
                $this->dbConnectionMock->expects($this->at($idx))
                    ->method('executeQuery')
                    ->with('DELETE FROM warehouses WHERE id = ?',
                        [$warehouse_id]);
        }

        if (!$allOK)
            $this->expectExceptionMessage($error_type);

        $this->warehouseService->DeleteWarehouse($user_id, $warehouse_id);
    }

    public function dataDeleteWarehouse()
    {
        return [
            [
                1, 1, ['id' => '1', 'address' => 'add1', 'capacity' => '150', 'user_id' => '1', 'balance' => '0', 'total_size' => '0'],
                true, 0, true
            ],
            [
                1, 1, [],
                false, 0, false, 'Warehouse does not exist 1'
            ],
            [
                1, 1, ['id' => '1', 'address' => 'add1', 'capacity' => '150', 'user_id' => '2', 'balance' => '0', 'total_size' => '0'],
                false, 0, false, 'Wrong access 1'
            ],
            [
                1, 1, ['id' => '1', 'address' => 'add1', 'capacity' => '150', 'user_id' => '1', 'balance' => '0', 'total_size' => '0'],
                true, 2, false, 'This warehouse has logs'
            ]
        ];
    }

    /**
     * @dataProvider dataGetProductList
     */
    public function testGetProductList($user_id, $warehouse_id,  $warehouse_data, $warehouse_checked,
                                       $product_list, $expected, $allOK, $error_type = '')
    {
        $idx = 0;

        $this->dbConnectionMock->expects($this->at($idx++))
            ->method('fetchAssoc')
            ->with('SELECT * FROM warehouses WHERE id = ?',
                [$warehouse_id])
            ->will($this->returnValue($warehouse_data));

        if ($warehouse_checked) {
            $QueryProductMock = $this->getMockBuilder('\PDOStatement')
                ->getMock();

            for ($i = 0; $i <= count($product_list); $i++) {
                $QueryProductMock->expects($this->at($i))
                    ->method('fetch')
                    ->will($this->returnValue($product_list[$i]));
            }

            $this->dbConnectionMock->expects($this->at($idx))
                ->method('executeQuery')
                ->with('SELECT * FROM products_on_warehouse WHERE warehouse_id = ?',
                    [$warehouse_id])
                ->will($this->returnValue($QueryProductMock));
        }

        if (!$allOK)
            $this->expectExceptionMessage($error_type);

        $products = $this->warehouseService->GetProductsList($user_id, $warehouse_id);

        if ($allOK)
            $this->assertEquals($products, $expected);
    }

    public function dataGetProductList()
    {
        return [
            [
                1, 1, ['id' => '1', 'address' => 'add1', 'capacity' => '150', 'user_id' => '1', 'balance' => '150', 'total_size' => '0'],
                true, [['product_id' => 1, 'count' => 3], ['product_id' => 2, 'count' => 4], ['product_id' => 3, 'count' => 7]],
                ['balance' => 150, 'products_list' => [1 => 3, 2 => 4, 3 => 7]],
                true
            ],
            [
                1, 1, [],
                false, [['product_id' => 1, 'count' => 3], ['product_id' => 2, 'count' => 4], ['product_id' => 3, 'count' => 7]],
                ['balance' => 150, 'products_list' => [1 => 3, 2 => 4, 3 => 7]],
                false, 'Warehouse does not exist 1'
            ],
            [
                1, 1, ['id' => '1', 'address' => 'add1', 'capacity' => '150', 'user_id' => '2', 'balance' => '150', 'total_size' => '0'],
                false, [['product_id' => 1, 'count' => 3], ['product_id' => 2, 'count' => 4], ['product_id' => 3, 'count' => 7]],
                ['balance' => 150, 'products_list' => [1 => 3, 2 => 4, 3 => 7]],
                false, 'Wrong access 1'
            ]
        ];
    }

    /**
     * @dataProvider dataAppProductList
     */
    public function testAppProductList($product_list, $transaction_product_list, $expected)
    {
        $this->warehouseService->AppProductList($product_list, $transaction_product_list);

        $this->assertEquals($product_list, $expected);
    }

    public function dataAppProductList()
    {
        return [
            [
                [1 => 10, 2 => 13, 3 => 1], [1 => ['count' => 3, 'amount' => 50], 3 => ['count' => 10, 'amount' => 30]],
                [1 => 13, 2 => 13, 3 => 11]
            ]
        ];
    }

    /**
     * @dataProvider dataDetachProductList
     */
    public function testDetachProductList($product_list, $transaction_product_list, $expected)
    {
        $this->warehouseService->DetachProductList($product_list, $transaction_product_list);

        $this->assertEquals($product_list, $expected);
    }

    public function dataDetachProductList()
    {
        return [
            [
                [1 => 10, 2 => 13, 3 => 1], [1 => ['count' => 3, 'amount' => 50], 3 => ['count' => 1, 'amount' => 30]],
                [1 => 7, 2 => 13, 3 => 0]
            ]
        ];
    }

    /**
     * @dataProvider dataUnsetZeroProductList
     */
    public function testUnsetZeroProductList($product_list, $expected)
    {
        $this->warehouseService->UnsetZeroProductList($product_list);

        $this->assertEquals($product_list, $expected);
    }

    public function dataUnsetZeroProductList()
    {
        return [
            [
                [1 => 0, 2 => 13, 3 => 0],
                [2 => 13]
            ]
        ];
    }

    /**
     * @dataProvider dataGetProductListOnDate
     */
    public function testGetProductListOnDate($user_id, $warehouse_id,  $warehouse_data, $warehouse_checked,
                                             $full_product_list, $transactions, $date, $expected, $allOK, $error_type = '')
    {
        $idx = 0;

        $this->dbConnectionMock->expects($this->at($idx++))
            ->method('fetchAssoc')
            ->with('SELECT * FROM warehouses WHERE id = ?',
                [$warehouse_id])
            ->will($this->returnValue($warehouse_data));

        if ($warehouse_checked) {
            $QueryProductListMock = $this->getMockBuilder('\PDOStatement')
                ->getMock();

            for ($i = 0; $i <= count($full_product_list); $i++) {
                $QueryProductListMock->expects($this->at($i))
                    ->method('fetch')
                    ->will($this->returnValue($full_product_list[$i]));
            }

            $this->dbConnectionMock->expects($this->at($idx++))
                ->method('executeQuery')
                ->with('SELECT * FROM products WHERE user_owner_id = ?',
                    [$user_id])
                ->will($this->returnValue($QueryProductListMock));

            $QueryTransactionMock = $this->getMockBuilder('\PDOStatement')
                ->getMock();

            for ($i = 0; $i <= count($transactions); $i++) {
                $QueryTransactionMock->expects($this->at($i))
                    ->method('fetch')
                    ->will($this->returnValue($transactions[$i]));
            }

            $this->dbConnectionMock->expects($this->at($idx++))
                ->method('executeQuery')
                ->with('SELECT * FROM transactions 
                   WHERE (warehouse_from_id = ? OR warehouse_to_id = ?) AND date <= ?',
                    [$warehouse_id, $warehouse_id, $date])
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

        $products = $this->warehouseService->GetProductListOnDate($user_id, $warehouse_id, $date);

        if ($allOK)
            $this->assertEquals($products, $expected);
    }

    public function dataGetProductListOnDate()
    {
        return [
            [
                1, 1, ['id' => '1', 'address' => 'add1', 'capacity' => '150', 'user_id' => '1', 'balance' => '150', 'total_size' => '0'],
                true, [['id' => 1], ['id' => 2], ['id' => 3], ['id' => 4]],
                [
                    ['id' => 1, 'warehouse_from_id' => null, 'warehouse_to_id' => 1, 'movement_type' => 'app', 'date' => '2018-08-09', 'total_count' => 250,
                        'products' =>
                            [
                                ['product_id' => 1,  'count' => 5, 'amount' => 100],
                                ['product_id' => 2,  'count' => 7, 'amount' => 150]
                            ]
                    ],
                    ['id' => 2, 'warehouse_from_id' => 2, 'warehouse_to_id' => 1, 'movement_type' => 'move', 'date' => '2018-08-10', 'total_count' => 340,
                        'products' =>
                            [
                                ['product_id' => 1,  'count' => 5, 'amount' => 100],
                                ['product_id' => 2,  'count' => 7, 'amount' => 150],
                                ['product_id' => 3,  'count' => 1, 'amount' => 90]
                            ]
                    ],
                    ['id' => 3, 'warehouse_from_id' => 1, 'warehouse_to_id' => null, 'movement_type' => 'detach', 'date' => '2018-08-11', 'total_count' => 100,
                        'products' =>
                            [
                                ['product_id' => 1,  'count' => 5, 'amount' => 100]
                            ]
                    ]
                ], '2018-08-11',
                ['balance' => 490, 'products_list' => [1 => 5, 2 => 14,  3 => 1]], true
            ],
            [
                1, 1, [],
                false, [['id' => 1], ['id' => 2], ['id' => 3], ['id' => 4]],
                [
                    ['id' => 1, 'warehouse_from_id' => null, 'warehouse_to_id' => 1, 'movement_type' => 'app', 'date' => '2018-08-09', 'total_count' => 250,
                        'products' =>
                            [
                                ['product_id' => 1,  'count' => 5, 'amount' => 100],
                                ['product_id' => 2,  'count' => 7, 'amount' => 150]
                            ]
                    ],
                    ['id' => 2, 'warehouse_from_id' => 2, 'warehouse_to_id' => 1, 'movement_type' => 'move', 'date' => '2018-08-10', 'total_count' => 340,
                        'products' =>
                            [
                                ['product_id' => 1,  'count' => 5, 'amount' => 100],
                                ['product_id' => 2,  'count' => 7, 'amount' => 150],
                                ['product_id' => 3,  'count' => 1, 'amount' => 90]
                            ]
                    ],
                    ['id' => 3, 'warehouse_from_id' => 1, 'warehouse_to_id' => null, 'movement_type' => 'detach', 'date' => '2018-08-11', 'total_count' => 100,
                        'products' =>
                            [
                                ['product_id' => 1,  'count' => 5, 'amount' => 100]
                            ]
                    ]
                ], '2018-08-11',
                ['balance' => 490, 'products_list' => [1 => 5, 2 => 14,  3 => 1]], false, 'Warehouse does not exist 1'
            ],
            [
                1, 1, ['id' => '1', 'address' => 'add1', 'capacity' => '150', 'user_id' => '2', 'balance' => '150', 'total_size' => '0'],
                false, [['id' => 1], ['id' => 2], ['id' => 3], ['id' => 4]],
                [
                    ['id' => 1, 'warehouse_from_id' => null, 'warehouse_to_id' => 1, 'movement_type' => 'app', 'date' => '2018-08-09', 'total_count' => 250,
                        'products' =>
                            [
                                ['product_id' => 1,  'count' => 5, 'amount' => 100],
                                ['product_id' => 2,  'count' => 7, 'amount' => 150]
                            ]
                    ],
                    ['id' => 2, 'warehouse_from_id' => 2, 'warehouse_to_id' => 1, 'movement_type' => 'move', 'date' => '2018-08-10', 'total_count' => 340,
                        'products' =>
                            [
                                ['product_id' => 1,  'count' => 5, 'amount' => 100],
                                ['product_id' => 2,  'count' => 7, 'amount' => 150],
                                ['product_id' => 3,  'count' => 1, 'amount' => 90]
                            ]
                    ],
                    ['id' => 3, 'warehouse_from_id' => 1, 'warehouse_to_id' => null, 'movement_type' => 'detach', 'date' => '2018-08-11', 'total_count' => 100,
                        'products_list' =>
                            [
                                ['product_id' => 1,  'count' => 5, 'amount' => 100]
                            ]
                    ]
                ], '2018-08-11',
                ['balance' => 490, 'products' => [1 => 5, 2 => 14,  3 => 1]], false, 'Wrong access 1'
            ],
            [
                1, 1, ['id' => '1', 'address' => 'add1', 'capacity' => '150', 'user_id' => '1', 'balance' => '150', 'total_size' => '0'],
                true, [['id' => 1], ['id' => 2], ['id' => 3], ['id' => 4]],
                [
                ], '2018-08-11',
                ['balance' => 0, 'products_list' => []], true
            ]
        ];
    }
}