<?php

namespace App\Tests\Functional;

class WarehouseTest extends ApiTestCase
{
    /**
     * @dataProvider dataGetWarehouseList
     */
    public function testGetWarehouseList($user, $warehouses)
    {
        $this->request('POST', '/login', $user);
        $this->assertThatResponseHasStatus(301);

        $this->request('GET', '/profile/warehouses');
        $this->assertThatResponseHasStatus(200);
        $this->assertEquals($warehouses, $this->responseData());

        $this->request('GET', '/profile/logout');
        $this->assertThatResponseHasStatus(200);
    }

    public function dataGetWarehouseList()
    {
        return [
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    ['id' => '23', 'address' => 'add7', 'capacity' => '50', 'total_size' => '0', 'balance' => '0'],
                    ['id' => '24', 'address' => 'add5', 'capacity' => '150', 'total_size' => '62', 'balance' => '48.4'],
                    ['id' => '26', 'address' => 'add6', 'capacity' => '50', 'total_size' => '4', 'balance' => '2']
                ]
            ],
            [
                ['email' => 'hidan9834@mail.ru', 'password' => '12345678'],
                [
                    ['id' => '17', 'address' => 'add1', 'capacity' => '200', 'total_size' => '0', 'balance' => '0'],
                    ['id' => '19', 'address' => 'add2', 'capacity' => '0', 'total_size' => '0', 'balance' => '0'],
                    ['id' => '20', 'address' => 'add3', 'capacity' => '0', 'total_size' => '0', 'balance' => '0'],
                    ['id' => '21', 'address' => 'a', 'capacity' => '0', 'total_size' => '0', 'balance' => '0'],
                    ['id' => '22', 'address' => 'b', 'capacity' => '0', 'total_size' => '0', 'balance' => '0']
                ]
            ]
        ];
    }

    /**
     * @dataProvider dataGetWarehouse
     */
    public function testGetWarehouse($user, $warehouse_id, $warehouse, $expected_status, $reason)
    {
        $this->request('POST', '/login', $user);
        $this->assertThatResponseHasStatus(301);

        $this->request('GET', '/profile/warehouses/'.$warehouse_id);
        $this->assertThatResponseHasStatus($expected_status);
        $this->assertEquals($warehouse, $this->responseData());
        $this->assertThatResponseReasonPhrase($reason);

        $this->request('GET', '/profile/logout');
        $this->assertThatResponseHasStatus(200);
    }

    public function dataGetWarehouse()
    {
        return [
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'], 23,
                ['id' => '23', 'address' => 'add7', 'capacity' => '50', 'total_size' => '0', 'balance' => '0'], 200, 'OK'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'], 26,
                ['id' => '26', 'address' => 'add6', 'capacity' => '50', 'total_size' => '4', 'balance' => '2'], 200, 'OK'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'], 24,
                ['id' => '24', 'address' => 'add5', 'capacity' => '150', 'total_size' => '62', 'balance' => '48.4'], 200, 'OK'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'], 'asdf',
                null, 400, 'Wrong id'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'], 17,
                null, 400, 'Wrong access 17'
            ],
            [
                ['email' => 'hidan9834@mail.ru', 'password' => '12345678'], 17,
                ['id' => '17', 'address' => 'add1', 'capacity' => '200', 'total_size' => '0', 'balance' => '0'], 200, 'OK'
            ]
        ];
    }

    /**
     * @dataProvider dataCreateWarehouse
     */
    public function testCreateDeleteWarehouse($user, $body, $expected_warehouse, $expected_status, $reason)
    {
        $this->request('POST', '/login', $user);
        $this->assertThatResponseHasStatus(301);

        $this->request('POST', '/profile/warehouses/create', $body);
        $this->assertThatResponseHasStatus($expected_status);
        $this->assertEquals($expected_warehouse['address'], $this->responseData()['address']);
        $this->assertEquals($expected_warehouse['capacity'], $this->responseData()['capacity']);
        $this->assertThatResponseReasonPhrase($reason);

        if ($expected_status == 200){
            $warehouse_id = $this->responseData()['id'];

            $this->request('GET', '/profile/warehouses/'.$warehouse_id.'/delete');
            $this->assertThatResponseHasStatus($expected_status);
            $this->assertThatResponseReasonPhrase('Success!');
        }

        $this->request('GET', '/profile/logout');
        $this->assertThatResponseHasStatus(200);
    }

    public function dataCreateWarehouse()
    {
        return [
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'address' => 'add10',
                    'capacity' => 200
                ],
                [
                    'address' => 'add10',
                    'capacity' => 200
                ], 200, 'OK'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'address' => 'add5',
                    'capacity' => 200
                ],
                null,
                400, 'Warehouse with this address is already exist!'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'address' => 'a',
                    'capacity' => 200
                ],
                null,
                400, 'Wrong address'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'address' => 'add!!!!',
                    'capacity' => 200
                ],
                null,
                400, 'Wrong address'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'address' => 'add11',
                    'capacity' => -100
                ],
                null,
                400, 'Wrong capacity'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'address' => 'add11',
                    'capacity' => 'asd'
                ],
                null,
                400, 'Wrong capacity'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'capacity' => 200
                ],
                null,
                400, 'Wrong address'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'address' => 'add11'
                ],
                null,
                400, 'Wrong capacity'
            ]
        ];
    }

    /**
     * @dataProvider dataUpdateWarehouse
     */
    public function testUpdateWarehouse($user, $warehouse_id, $body, $expected_warehouse, $expected_status, $reason)
    {
        $this->request('POST', '/login', $user);
        $this->assertThatResponseHasStatus(301);

        $this->request('POST', '/profile/warehouses/'.$warehouse_id.'/update', $body);
        $this->assertThatResponseHasStatus($expected_status);
        $this->assertEquals($expected_warehouse, $this->responseData());
        $this->assertThatResponseReasonPhrase($reason);

        $this->request('GET', '/profile/logout');
        $this->assertThatResponseHasStatus(200);
    }

    public function dataUpdateWarehouse(){
        return [
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                24,
                [
                    'address' => 'add12',
                    'capacity' => 300
                ],
                ['id' => '24', 'address' => 'add12', 'capacity' => '300', 'total_size' => '62', 'balance' => '48.4'],
                200, 'OK'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                24,
                [
                    'address' => 'add5',
                    'capacity' => 150
                ],
                ['id' => '24', 'address' => 'add5', 'capacity' => '150', 'total_size' => '62', 'balance' => '48.4'],
                200, 'OK'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                24,
                [
                    'address' => 'add12',
                    'capacity' => 50
                ],
                null,
                400, 'Too much products to set this capacity'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                24,
                [
                    'address' => 'add1',
                    'capacity' => 150
                ],
                null,
                400, 'Warehouse with this address is already exist!'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                24,
                [
                    'address' => 'add12!',
                    'capacity' => 150
                ],
                null,
                400, 'Wrong address'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                24,
                [
                    'capacity' => 150
                ],
                null,
                400, 'Wrong address'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                24,
                [
                    'address' => 'a',
                    'capacity' => 150
                ],
                null,
                400, 'Wrong address'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                24,
                [
                    'address' => 'add12',
                    'capacity' => -150
                ],
                null,
                400, 'Wrong capacity'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                24,
                [
                    'address' => 'add12',
                    'capacity' => '150asd'
                ],
                null,
                400, 'Wrong capacity'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                24,
                [
                    'address' => 'add12',
                    'capacity' => 0
                ],
                null,
                400, 'Wrong capacity'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                24,
                [
                    'address' => 'add12'
                ],
                null,
                400, 'Wrong capacity'
            ]
        ];
    }

    /**
     * @dataProvider dataMoveProducts
     */
    public function testMoveProducts($user, $body, $expected_status, $reason)
    {
        $this->request('POST', '/login', $user);
        $this->assertThatResponseHasStatus(301);

        $this->request('POST', '/profile/warehouses/move', $body);
        $this->assertThatResponseHasStatus($expected_status);
        $this->assertThatResponseReasonPhrase($reason);

        $this->request('GET', '/profile/logout');
        $this->assertThatResponseHasStatus(200);
    }

    public function dataMoveProducts()
    {
        return [
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'movement_type' => 'app',
                    'warehouses' => '{"to":24}',
                    'product_list' => '[{"id":1, "count":2}]'
                ], 200, 'OK'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'movement_type' => 'detach',
                    'warehouses' => '{"from":24}',
                    'product_list' => '[{"id":1, "count":2}]'
                ], 200, 'OK'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'movement_type' => 'move',
                    'warehouses' => '{"from":24, "to":23}',
                    'product_list' => '[{"id":1, "count":2}]'
                ], 200, 'OK'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'movement_type' => 'move',
                    'warehouses' => '{"from":23, "to":24}',
                    'product_list' => '[{"id":1, "count":2}]'
                ], 200, 'OK'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'movement_type' => 'move',
                    'warehouses' => '{"from":24, "to":23}',
                    'product_list' => '[{"id":1, "count":1}, {"id":3, "count":20}]'
                ], 200, 'OK'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'movement_type' => 'move',
                    'warehouses' => '{"from":23, "to":24}',
                    'product_list' => '[{"id":1, "count":1}, {"id":3, "count":20}]'
                ], 200, 'OK'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'movement_type' => 'app',
                    'warehouses' => '{"to":26}',
                    'product_list' => '[{"id":1, "count":2}, {"id":1, "count":2}, {"id":2, "count":3}, {"id":2, "count":2}, {"id":3, "count":6}]'
                ], 200, 'OK'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'movement_type' => 'move',
                    'warehouses' => '{"from":26, "to":24}',
                    'product_list' => '[{"id":1, "count":4}, {"id":2, "count":5}, {"id":3, "count":6}]'
                ], 200, 'OK'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'movement_type' => 'move',
                    'warehouses' => '{"from":24, "to":23}',
                    'product_list' => '[{"id":1, "count":3}, {"id":2, "count":4}, {"id":3, "count":5}]'
                ], 200, 'OK'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'movement_type' => 'move',
                    'warehouses' => '{"from":23, "to":26}',
                    'product_list' => '[{"id":1, "count":1}, {"id":1, "count":1}, {"id":1, "count":1}, {"id":2, "count":4}, {"id":3, "count":5}]'
                ], 200, 'OK'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'movement_type' => 'detach',
                    'warehouses' => '{"from":24}',
                    'product_list' => '[{"id":1, "count":1}, {"id":2, "count":1}, {"id":3, "count":1}]'
                ], 200, 'OK'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'movement_type' => 'detach',
                    'warehouses' => '{"from":26}',
                    'product_list' => '[{"id":1, "count":3}, {"id":2, "count":4}, {"id":3, "count":5}]'
                ], 200, 'OK'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'movement_type' => 'app',
                    'warehouses' => '{"to":2}',
                    'product_list' => '[{"id":1, "count":2}, {"id":1, "count":2}, {"id":2, "count":3}, {"id":2, "count":2}, {"id":3, "count":6}]'
                ], 400, 'Warehouse does not exist 2'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'movement_type' => 'app',
                    'warehouses' => '{"to":17}',
                    'product_list' => '[{"id":1, "count":2}, {"id":1, "count":2}, {"id":2, "count":3}, {"id":2, "count":2}, {"id":3, "count":6}]'
                ], 400, 'Wrong access 17'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'movement_type' => 'app',
                    'warehouses' => '{"to":24}',
                    'product_list' => '[{"id":1, "count":2}, {"id":1, "count":500}, {"id":2, "count":3}, {"id":2, "count":2}, {"id":3, "count":6}]'
                ], 400, 'Transaction failed: Not enough space on warehouse 24'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'movement_type' => 'app',
                    'warehouses' => '{"to":24}',
                    'product_list' => '[{"id":1, "count":2}, {"id":15, "count":1}, {"id":2, "count":3}, {"id":2, "count":2}, {"id":3, "count":6}]'
                ], 400, 'Transaction failed: Product does not exist 15'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'movement_type' => 'detach',
                    'warehouses' => '{"from":24}',
                    'product_list' => '[{"id":1, "count":2}, {"id":1, "count":500}, {"id":2, "count":3}, {"id":2, "count":2}, {"id":3, "count":6}]'
                ], 400, 'Transaction failed: Not enough products 1 on warehouse 24'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'movement_type' => 'move',
                    'warehouses' => '{"from": 24, "to":23}',
                    'product_list' => '[{"id":1, "count":1}, {"id":2, "count":3}, {"id":2, "count":2}, {"id":3, "count":6}]'
                ], 400, 'Transaction failed: Not enough products 2 on warehouse 24'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'movement_type' => 'move',
                    'warehouses' => '{"from": 24, "to":24}',
                    'product_list' => '[{"id":1, "count":1}]'
                ], 400, 'Warehouses are the same!'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'movement_type' => 'ap',
                    'warehouses' => '{"to":26}',
                    'product_list' => '[{"id":1, "count":2}]'
                ], 400, 'Wrong type!'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'warehouses' => '{"to":26}',
                    'product_list' => '[{"id":1, "count":2}]'
                ], 400, 'Wrong type!'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'movement_type' => 'app',
                    'warehouses' => '{"to":"asd"}',
                    'product_list' => '[{"id":1, "count":2}]'
                ], 400, 'Wrong warehouses.to'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'movement_type' => 'app',
                    'warehouses' => '{"to":26',
                    'product_list' => '[{"id":1, "count":2}]'
                ], 400, 'Wrong warehouses'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'movement_type' => 'app',
                    'product_list' => '[{"id":1, "count":2}]'
                ], 400, 'Wrong warehouses'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'movement_type' => 'app',
                    'warehouses' => '{"to":26, "from":24}',
                    'product_list' => '[{"id":1, "count":2}]'
                ], 400, 'Wrong warehouses'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'movement_type' => 'app',
                    'warehouses' => '{"detach":26}',
                    'product_list' => '[{"id":1, "count":2}]'
                ], 400, 'Wrong warehouses.to'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'movement_type' => 'app',
                    'warehouses' => '{"to":26}',
                    'product_list' => '[{"id":1, "count":"asdf"}]'
                ], 400, 'Wrong product_list[0].count'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'movement_type' => 'app',
                    'warehouses' => '{"to":26}',
                    'product_list' => '[{"id":"asf", "count":2}]'
                ], 400, 'Wrong product_list[0].id'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'movement_type' => 'app',
                    'warehouses' => '{"to":26}',
                    'product_list' => '[{"id":1, "count":2}'
                ], 400, 'Wrong product_list'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'movement_type' => 'app',
                    'warehouses' => '{"to":26}'
                ], 400, 'Wrong product_list'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'movement_type' => 'detach',
                    'warehouses' => '{"from":24, "to":26}',
                    'product_list' => '[{"id":1, "count":1}]'
                ], 400, 'Wrong warehouses'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'movement_type' => 'move',
                    'warehouses' => '{"to":23}',
                    'product_list' => '[{"id":1, "count":1}, {"id":3, "count":20}]'
                ], 400, 'Wrong warehouses.from'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'movement_type' => 'move',
                    'warehouses' => '{}',
                    'product_list' => '[{"id":1, "count":1}, {"id":3, "count":20}]'
                ], 400, 'Wrong warehouses.to'
            ]
        ];
    }
}