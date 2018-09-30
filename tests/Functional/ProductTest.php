<?php

namespace App\Tests\Functional;


class ProductTest extends ApiTestCase
{
    /**
     * @dataProvider dataGetProductList
     */
    public function testGetProductList($user, $expected_data)
    {
        $this->request('POST', '/login', $user);
        $this->assertThatResponseHasStatus(301);

        $this->request('GET', '/profile/products');
        $this->assertThatResponseHasStatus(200);
        $this->assertEquals($expected_data, $this->responseData());

        $this->request('GET', '/profile/logout');
        $this->assertThatResponseHasStatus(200);
    }

    public function dataGetProductList()
    {
        return [
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    ['id' => 1, 'name' => 'product1', 'price' => 10.2, 'size' => 3, 'type_name' => 'type1'],
                    ['id' => 2, 'name' => 'product2', 'price' => 15, 'size' => 5.1, 'type_name' => 'type1'],
                    ['id' => 3, 'name' => 'product3', 'price' => 0.5, 'size' => 1, 'type_name' => 'type1']
                ]
            ],
            [
                ['email' => 'hidan9834@mail.ru', 'password' => '12345678'],
                [
                    ['id' => 5, 'name' => 'product5', 'price' => 36, 'size' => 10, 'type_name' => 'type1']
                ]
            ]
        ];
    }

    /**
     * @dataProvider dataGetProduct
     */
    public function testGetProduct($user, $product_id, $expected_data, $expected_status, $reason)
    {
        $this->request('POST', '/login', $user);
        $this->assertThatResponseHasStatus(301);

        $this->request('GET', '/profile/products/'.$product_id);
        $this->assertThatResponseHasStatus($expected_status);
        $this->assertEquals($expected_data, $this->responseData());
        $this->assertThatResponseReasonPhrase($reason);

        $this->request('GET', '/profile/logout');
        $this->assertThatResponseHasStatus(200);
    }

    public function dataGetProduct()
    {
        return [
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                1,
                ['id' => 1, 'name' => 'product1', 'price' => 10.2, 'size' => 3, 'type_name' => 'type1'],
                200, 'OK'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                2,
                ['id' => 2, 'name' => 'product2', 'price' => 15, 'size' => 5.1, 'type_name' => 'type1'],
                200, 'OK'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                -1,
                null,
                400, 'Wrong id'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                'asd',
                null,
                400, 'Wrong id'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                4,
                null,
                400, 'Product does not exist 4'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                5,
                null,
                400, 'Wrong access 5'
            ]
        ];
    }

    /**
     * @dataProvider dataCreateDeleteProduct
     */
    public function testCreateDeleteProduct($user, $body, $expected_data, $expected_status, $reason)
    {
        $this->request('POST', '/login', $user);
        $this->assertThatResponseHasStatus(301);

        $this->request('POST', '/profile/products/create', $body);
        $this->assertThatResponseHasStatus($expected_status);

        if ($expected_status == 200){
            $expected_data['id'] = $this->responseData()['id'];

            $this->assertEquals($expected_data, $this->responseData());
            $this->assertThatResponseReasonPhrase($reason);

            $this->request('GET', '/profile/products/'.$expected_data['id'].'/delete');
            $this->assertThatResponseHasStatus($expected_status);
            $this->assertThatResponseReasonPhrase('Success!');
        }

        $this->request('GET', '/profile/logout');
        $this->assertThatResponseHasStatus(200);
    }

    public function dataCreateDeleteProduct()
    {
        return [
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'name' => 'product6',
                    'price' => 12,
                    'size' => 3.4,
                    'type_name' => 'type1'
                ],
                [
                    'name' => 'product6',
                    'price' => 12,
                    'size' => 3.4,
                    'type_name' => 'type1'
                ], 200, 'OK'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'name' => 'product6',
                    'price' => 12,
                    'size' => 3.4,
                    'type_name' => 'type12'
                ],
                null, 400, 'Type does not exist!'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'name' => 'p',
                    'price' => 12,
                    'size' => 3.4,
                    'type_name' => 'type1'
                ],
                null, 400, 'Wrong name'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'price' => 12,
                    'size' => 3.4,
                    'type_name' => 'type1'
                ],
                null, 400, 'Wrong name'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'name' => 'product6@#',
                    'price' => 12,
                    'size' => 3.4,
                    'type_name' => 'type1'
                ],
                null, 400, 'Wrong name'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'name' => 'product6',
                    'price' => 'asdf',
                    'size' => 3.4,
                    'type_name' => 'type1'
                ],
                null, 400, 'Wrong price'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'name' => 'product6',
                    'price' => 0,
                    'size' => 3.4,
                    'type_name' => 'type1'
                ],
                null, 400, 'Wrong price'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'name' => 'product6',
                    'size' => 3.4,
                    'type_name' => 'type1'
                ],
                null, 400, 'Wrong price'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'name' => 'product6',
                    'price' => 12,
                    'size' => 0,
                    'type_name' => 'type1'
                ],
                null, 400, 'Wrong size'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'name' => 'product6',
                    'price' => 12,
                    'size' => 'asd',
                    'type_name' => 'type1'
                ],
                null, 400, 'Wrong size'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'name' => 'product6',
                    'price' => 12,
                    'type_name' => 'type1'
                ],
                null, 400, 'Wrong size'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'name' => 'product6',
                    'price' => 12,
                    'size' => 3.4,
                    'type_name' => '@#ASD'
                ],
                null, 400, 'Wrong type_name'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                [
                    'name' => 'product6',
                    'price' => 12,
                    'size' => 3.4
                ],
                null, 400, 'Wrong type_name'
            ]
        ];
    }

    /**
     * @dataProvider dataUpdateProduct
     */
    public function testUpdateProduct($user, $product_id, $body, $expected_data, $expected_status, $reason)
    {
        $this->request('POST', '/login', $user);
        $this->assertThatResponseHasStatus(301);

        $this->request('POST', '/profile/products/'.$product_id.'/update', $body);
        $this->assertThatResponseHasStatus($expected_status);
        $this->assertEquals($expected_data, $this->responseData());
        $this->assertThatResponseReasonPhrase($reason);

        $this->request('GET', '/profile/logout');
        $this->assertThatResponseHasStatus(200);
    }

    public function dataUpdateProduct()
    {
        return [
            [

            ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                1,
                [
                    'name' => 'product6',
                    'price' => 12,
                    'size' => 3,
                    'type_name' => 'type1'
                ],
                [
                    'id' => 1,
                    'name' => 'product6',
                    'price' => 12,
                    'size' => 3,
                    'type_name' => 'type1'
                ], 200, 'OK'
            ],
            [

                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                1,
                [
                    'name' => 'product1',
                    'price' => 10.2,
                    'size' => 3,
                    'type_name' => 'type1'
                ],
                [
                    'id' => 1,
                    'name' => 'product1',
                    'price' => 10.2,
                    'size' => 3,
                    'type_name' => 'type1'
                ], 200, 'OK'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                1,
                [
                    'name' => 'product6',
                    'price' => 12,
                    'size' => 3,
                    'type_name' => 'type12'
                ],
                null, 400, 'Type does not exist!'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                1,
                [
                    'name' => 'p',
                    'price' => 12,
                    'size' => 3,
                    'type_name' => 'type1'
                ],
                null, 400, 'Wrong name'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                1,
                [
                    'price' => 12,
                    'size' => 3,
                    'type_name' => 'type1'
                ],
                null, 400, 'Wrong name'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                1,
                [
                    'name' => 'product6@#',
                    'price' => 12,
                    'size' => 3,
                    'type_name' => 'type1'
                ],
                null, 400, 'Wrong name'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                1,
                [
                    'name' => 'product6',
                    'price' => 'asdf',
                    'size' => 3,
                    'type_name' => 'type1'
                ],
                null, 400, 'Wrong price'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                1,
                [
                    'name' => 'product6',
                    'price' => 0,
                    'size' => 3,
                    'type_name' => 'type1'
                ],
                null, 400, 'Wrong price'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                1,
                [
                    'name' => 'product6',
                    'size' => 3,
                    'type_name' => 'type1'
                ],
                null, 400, 'Wrong price'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                1,
                [
                    'name' => 'product6',
                    'price' => 12,
                    'size' => 4,
                    'type_name' => 'type1'
                ],
                null, 400, 'This product is already on warehouse!'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                1,
                [
                    'name' => 'product6',
                    'price' => 12,
                    'size' => 'asd',
                    'type_name' => 'type1'
                ],
                null, 400, 'Wrong size'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                1,
                [
                    'name' => 'product6',
                    'price' => 12,
                    'type_name' => 'type1'
                ],
                null, 400, 'Wrong size'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                1,
                [
                    'name' => 'product6',
                    'price' => 12,
                    'size' => 3.4,
                    'type_name' => '@#ASD'
                ],
                null, 400, 'Wrong type_name'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                1,
                [
                    'name' => 'product6',
                    'price' => 12,
                    'size' => 3.4
                ],
                null, 400, 'Wrong type_name'
            ]
        ];
    }

    /**
     * @dataProvider dataGetLogs
     */
    public function testGetLogs($user, $product_id, $expected_status, $reason)
    {
        $this->request('POST', '/login', $user);
        $this->assertThatResponseHasStatus(301);

        $this->request('GET', '/profile/products/'.$product_id.'/logs');
        $this->assertThatResponseHasStatus($expected_status);
        if ($expected_status == 200)
            $this->assertThatResponseHasContentType('application/json');
        $this->assertThatResponseReasonPhrase($reason);

        $this->request('GET', '/profile/logout');
        $this->assertThatResponseHasStatus(200);
    }

    public function dataGetLogs()
    {
        return [
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                1, 200, 'OK'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                2, 200, 'OK'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                3, 200, 'OK'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                4, 400, 'Product does not exist 4'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                5, 400, 'Wrong access 5'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                -1, 400, 'Wrong id'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                'asf', 400, 'Wrong id'
            ]
        ];
    }

    /**
     * @dataProvider dataGetAvailableInfo
     */
    public function testGetAvailableInfo($user, $product_id, $date, $expected_data, $expected_status, $reason)
    {
        $this->request('POST', '/login', $user);
        $this->assertThatResponseHasStatus(301);

        $this->request('GET', '/profile/products/'.$product_id.'/available'.$date);
        $this->assertThatResponseHasStatus($expected_status);
        $this->assertEquals($expected_data, $this->responseData());
        $this->assertThatResponseReasonPhrase($reason);

        $this->request('GET', '/profile/logout');
        $this->assertThatResponseHasStatus(200);
    }

    public function dataGetAvailableInfo()
    {
        return [
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                1, '',
                [
                    'total_cost' => 20.4,
                    'warehouses_list' =>
                    [
                        24 => 2
                    ]
                ], 200, 'OK'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                2, '',
                [
                    'total_cost' => 0,
                    'warehouses_list' =>
                        []
                ], 200, 'OK'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                3, '',
                [
                    'total_cost' => 30,
                    'warehouses_list' =>
                        [
                            24 => 56,
                            26 => 4
                        ]
                ], 200, 'OK'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                -1, '',
                null, 400, 'Wrong id'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                'asdf', '',
                null, 400, 'Wrong id'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                4, '',
                null, 400, 'Product does not exist 4'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                5, '',
                null, 400, 'Wrong access 5'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                1, '?date=2018-09-15 17:38:10',
                [
                    'total_cost' => 10.200000000000003,
                    'warehouses_list' =>
                        [
                            24 => 1
                        ]
                ], 200, 'OK'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                1, '?date=2018-09-15 17:34:10',
                [
                    'total_cost' => 51,
                    'warehouses_list' =>
                        [
                            23 => 5
                        ]
                ], 200, 'OK'
            ],
            [
                ['email' => 'hidan98@mail.ru', 'password' => '12345678'],
                1, '?date=2018-09-15 17:40:10',
                [
                    'total_cost' => 20.400000000000002,
                    'warehouses_list' =>
                        [
                            24 => 2
                        ]
                ], 200, 'OK'
            ]
        ];
    }
}