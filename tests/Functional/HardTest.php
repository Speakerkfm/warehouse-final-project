<?php

namespace App\Tests\Functional;


class HardTest extends ApiTestCase
{
    /**
     * Один пользователь регистрируется в системе, логинится, проверяет
     * информацию о себе. Затем создает 3 продукта, 3 склада, просматривает информаци о них.
     * Выполняет несколько перемещений, смотрит результат и удаляет всю информацию о себе.
     */
    public function testOneCleverUserWork()
    {
        $user = [
            'email' => 'user1@mail.ru',
            'name' => 'Alex',
            'surname' => 'Usanin',
            'password' => '12345678',
            'phone_number' => '+79125947072',
            'company_name' => 'company1'
        ];

        $this->request('POST', '/register', $user);
        $this->assertThatResponseHasStatus(200);

        $this->request('POST', '/login',
            [
                'email' => $user['email'],
                'password' => $user['password']
            ]
        );
        $this->assertThatResponseHasStatus(301);

        $this->request('GET', '/profile/info');
        $this->assertThatResponseHasStatus(200);
        $this->assertEquals(
            [
                'email' => $user['email'],
                'name' => $user['name'],
                'surname' => $user['surname'],
                'phone_number' => $user['phone_number'],
                'company_name' => $user['company_name']
            ],
            $this->responseData()
        );

        $products_list = [
            [
                'name' => 'product7',
                'price' => 12,
                'size' => 3.4,
                'type_name' => 'type1'
            ],
            [
                'name' => 'product8',
                'price' => 6.2,
                'size' => 7.8,
                'type_name' => 'type1'
            ],
            [
                'name' => 'product7',
                'price' => 16.1,
                'size' => 9,
                'type_name' => 'type1'
            ]
        ];

        $this->request('POST', '/profile/products/create', $products_list[0]);
        $this->assertThatResponseHasStatus(200);
        $products_list[0]['id'] = $this->responseData()['id'];

        $this->request('POST', '/profile/products/create', $products_list[1]);
        $this->assertThatResponseHasStatus(200);
        $products_list[1]['id'] = $this->responseData()['id'];

        $this->request('POST', '/profile/products/create', $products_list[2]);
        $this->assertThatResponseHasStatus(200);
        $products_list[2]['id'] = $this->responseData()['id'];

        $warehouses_list = [
            [
                'address' => 'add17',
                'capacity' => 300
            ],
            [
                'address' => 'add18',
                'capacity' => 200
            ],
            [
                'address' => 'add19',
                'capacity' => 150
            ]
        ];

        $this->request('POST', '/profile/warehouses/create', $warehouses_list[0]);
        $this->assertThatResponseHasStatus(200);
        $warehouses_list[0]['id'] = $this->responseData()['id'];
        $warehouses_list[0]['total_size'] = 0;
        $warehouses_list[0]['balance'] = 0;

        $this->request('POST', '/profile/warehouses/create', $warehouses_list[1]);
        $this->assertThatResponseHasStatus(200);
        $warehouses_list[1]['id'] = $this->responseData()['id'];
        $warehouses_list[1]['total_size'] = 0;
        $warehouses_list[1]['balance'] = 0;

        $this->request('POST', '/profile/warehouses/create', $warehouses_list[2]);
        $this->assertThatResponseHasStatus(200);
        $warehouses_list[2]['id'] = $this->responseData()['id'];
        $warehouses_list[2]['total_size'] = 0;
        $warehouses_list[2]['balance'] = 0;

        $this->request('GET', '/profile/products');
        $this->assertThatResponseHasStatus(200);
        $this->assertEquals($products_list, $this->responseData());

        $this->request('GET', '/profile/products/'.$products_list[0]['id']);
        $this->assertThatResponseHasStatus(200);
        $this->assertEquals($products_list[0], $this->responseData());

        $this->request('GET', '/profile/products/'.$products_list[1]['id']);
        $this->assertThatResponseHasStatus(200);
        $this->assertEquals($products_list[1], $this->responseData());

        $this->request('GET', '/profile/products/'.$products_list[2]['id']);
        $this->assertThatResponseHasStatus(200);
        $this->assertEquals($products_list[2], $this->responseData());

        $this->request('GET', '/profile/warehouses');
        $this->assertThatResponseHasStatus(200);
        $this->assertEquals($warehouses_list, $this->responseData());

        $this->request('GET', '/profile/warehouses/'.$warehouses_list[0]['id']);
        $this->assertThatResponseHasStatus(200);
        $this->assertEquals($warehouses_list[0], $this->responseData());

        $this->request('GET', '/profile/warehouses/'.$warehouses_list[1]['id']);
        $this->assertThatResponseHasStatus(200);
        $this->assertEquals($warehouses_list[1], $this->responseData());

        $this->request('GET', '/profile/warehouses/'.$warehouses_list[2]['id']);
        $this->assertThatResponseHasStatus(200);
        $this->assertEquals($warehouses_list[2], $this->responseData());

        $this->request('POST', '/profile/warehouses/move',
            [
                'movement_type' => 'app',
                'warehouses' => '{"to":'.$warehouses_list[0]['id'].'}',
                'product_list' => '[{"id":'.$products_list[0]['id'].', "count":4},
                                    {"id":'.$products_list[1]['id'].', "count":8}]'
            ]
            );
        $this->assertThatResponseHasStatus(200);
        $warehouses_list[0]['balance'] += $products_list[0]['price'] * 4 + $products_list[1]['price'] * 8;
        $warehouses_list[0]['total_size'] += $products_list[0]['size'] * 4 + $products_list[1]['size'] * 8;

        $this->request('POST', '/profile/warehouses/move',
            [
                'movement_type' => 'app',
                'warehouses' => '{"to":'.$warehouses_list[2]['id'].'}',
                'product_list' => '[{"id":'.$products_list[1]['id'].', "count":2},
                                    {"id":'.$products_list[2]['id'].', "count":6}]'
            ]
        );
        $this->assertThatResponseHasStatus(200);
        $warehouses_list[2]['balance'] += $products_list[1]['price'] * 2 + $products_list[2]['price'] * 6;
        $warehouses_list[2]['total_size'] += $products_list[1]['size'] * 2 + $products_list[2]['size'] * 6;

        $this->request('POST', '/profile/warehouses/move',
            [
                'movement_type' => 'move',
                'warehouses' => '{"to":'.$warehouses_list[1]['id'].', "from":'.$warehouses_list[0]['id'].'}',
                'product_list' => '[{"id":'.$products_list[0]['id'].', "count":1},
                                    {"id":'.$products_list[1]['id'].', "count":3}]'
            ]
        );
        $this->assertThatResponseHasStatus(200);
        $warehouses_list[0]['balance'] -= $products_list[0]['price'] * 1 + $products_list[1]['price'] * 3;
        $warehouses_list[0]['total_size'] -= $products_list[0]['size'] * 1 + $products_list[1]['size'] * 3;
        $warehouses_list[1]['balance'] += $products_list[0]['price'] * 1 + $products_list[1]['price'] * 3;
        $warehouses_list[1]['total_size'] += $products_list[0]['size'] * 1 + $products_list[1]['size'] * 3;

        $this->request('GET', '/profile/warehouses');
        $this->assertThatResponseHasStatus(200);
        $this->assertEquals($warehouses_list, $this->responseData());

        $this->request('GET', '/profile/delete');
        $this->assertThatResponseHasStatus(200);
    }
}