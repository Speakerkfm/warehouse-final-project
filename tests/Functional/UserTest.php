<?php

namespace App\Tests\Functional;


class UserTest extends ApiTestCase
{
    /**
     * @dataProvider dataRegisterUser
     */
    public function testRegisterUser($body, $expected_status, $reason)
    {
        $this->request('POST', '/register', $body);
        $this->assertThatResponseHasStatus($expected_status);
        $this->assertThatResponseReasonPhrase($reason);
    }

    public function dataRegisterUser()
    {
        return [
            [
                [
                    'email' => 'hidan98@gmail.ru',
                    'name' => 'Alex',
                    'surname' => 'Usanin',
                    'password' => '12345678',
                    'phone_number' => '+79125947072',
                    'company_name' => 'company1'
                ], 200, 'Success!'
            ],
            [
                [
                    'email' => 'hidan98@gmail.ru',
                    'name' => 'Alexx',
                    'surname' => 'Usanin',
                    'password' => '12345678',
                    'phone_number' => '+79125947072',
                    'company_name' => 'company1'
                ], 400, 'User with this email has already registered!'
            ],
            [
                [
                    'email' => 'hidan9812@gmail.ru',
                    'name' => 'Alex',
                    'surname' => 'Usanin',
                    'password' => '12345678',
                    'phone_number' => '+79125947072',
                    'company_name' => 'company1'
                ], 400, 'User with this name in this company has already registered!'
            ],
            [
                [
                    'email' => 'hidan9812@gmail.ru',
                    'name' => 'Alexx',
                    'surname' => 'Usanin',
                    'password' => '12345678',
                    'phone_number' => '+7912591247072',
                    'company_name' => 'company1'
                ], 400, 'Wrong phone_number'
            ],
            [
                [
                    'email' => 'hida!@#n9812@gmail.ru',
                    'name' => 'Alexx',
                    'surname' => 'Usanin',
                    'password' => '12345678',
                    'phone_number' => '+79125947072',
                    'company_name' => 'company1'
                ], 400, 'Wrong email'
            ],
            [
                [
                    'email' => 'hidan9812@gmail.ru',
                    'name' => 'Alexx()',
                    'surname' => 'Usanin',
                    'password' => '12345678',
                    'phone_number' => '+79125947072',
                    'company_name' => 'company1'
                ], 400, 'Wrong name'
            ],
            [
                [
                    'email' => 'hidan9812@gmail.ru',
                    'name' => 'Alexx',
                    'surname' => 'Usanin()',
                    'password' => '12345678',
                    'phone_number' => '+79125947072',
                    'company_name' => 'company1'
                ], 400, 'Wrong surname'
            ],
            [
                [
                    'email' => 'hidan9812@gmail.ru',
                    'name' => 'Alexx',
                    'surname' => 'Usanin',
                    'password' => '1',
                    'phone_number' => '+79125947072',
                    'company_name' => 'company1'
                ], 400, 'Wrong password'
            ],
            [
                [
                    'email' => 'hidan9812@gmail.ru',
                    'name' => 'Alexx',
                    'surname' => 'Usanin',
                    'password' => '12345678',
                    'phone_number' => '+79125947072',
                    'company_name' => 'company123'
                ], 400, 'Company does not exist!'
            ],
            [
                [
                    'email' => 'hidan9812@gmail.ru',
                    'name' => 'Alexx',
                    'surname' => 'Usanin',
                    'password' => '12345678',
                    'phone_number' => '+79125947072',
                    'company_name' => 'compan()y1'
                ], 400, 'Wrong company_name'
            ],
            [
                [
                    'name' => 'Alexx',
                    'surname' => 'Usanin',
                    'password' => '12345678',
                    'phone_number' => '+79125947072',
                    'company_name' => 'company1'
                ], 400, 'Wrong email'
            ],
            [
                [
                    'email' => 'hidan9812@gmail.ru',
                    'surname' => 'Usanin',
                    'password' => '12345678',
                    'phone_number' => '+79125947072',
                    'company_name' => 'company1'
                ], 400, 'Wrong name'
            ],
            [
                [
                    'email' => 'hidan9812@gmail.ru',
                    'name' => 'Alexx',
                    'password' => '12345678',
                    'phone_number' => '+79125947072',
                    'company_name' => 'company1'
                ], 400, 'Wrong surname'
            ],
            [
                [
                    'email' => 'hidan9812@gmail.ru',
                    'name' => 'Alexx',
                    'surname' => 'Usanin',
                    'phone_number' => '+79125947072',
                    'company_name' => 'company1'
                ], 400, 'Wrong password'
            ],
            [
                [
                    'email' => 'hidan9812@gmail.ru',
                    'name' => 'Alexx',
                    'surname' => 'Usanin',
                    'password' => '12345678',
                    'company_name' => 'company1'
                ], 400, 'Wrong phone_number'
            ],
            [
                [
                    'email' => 'hidan9812@gmail.ru',
                    'name' => 'Alexx',
                    'surname' => 'Usanin',
                    'password' => '12345678',
                    'phone_number' => '+79125947072'
                ], 400, 'Wrong company_name'
            ]
        ];
    }

    /**
     * @dataProvider dataLogin
     */
    public function testLogin($body, $expected_status, $reason)
    {
        $this->request('POST', '/login', $body);
        $this->assertThatResponseHasStatus($expected_status);
        $this->assertThatResponseReasonPhrase($reason);

        $this->request('GET', '/profile/logout');
    }

    public function dataLogin()
    {
        return [
            [
                ['email' => 'hidan98@gmail.ru', 'password' => '12345678'], 301, 'Moved Permanently'
            ],
            [
                ['email' => 'hidan98@gmail.ru', 'password' => '1234567'], 400, 'Wrong password!'
            ],
            [
                ['email' => 'hidan9812@gmail.ru', 'password' => '12345678'], 400, 'Wrong password!'
            ],
            [
                ['email' => 'h()idan98@gmail.ru', 'password' => '12345678'], 400, 'Wrong email'
            ],
            [
                ['password' => '12345678'], 400, 'Wrong email'
            ],
            [
                ['email' => 'hidan98@gmail.ru', 'password' => '1234@#$!5678'], 400, 'Wrong password'
            ],
            [
                ['email' => 'hidan98@gmail.ru'], 400, 'Wrong password'
            ]
        ];
    }

    /**
     * @dataProvider dataProfileInfo
     */
    public function testProfileInfo($login_body, $login_status, $profile_body, $expected_status)
    {
        $this->request('POST', '/login', $login_body);
        $this->assertThatResponseHasStatus($login_status);

        $this->request('GET', '/profile/info');
        $this->assertThatResponseHasStatus($expected_status);
        $this->assertEquals(
            $profile_body,
            $this->responseData()
        );

        $this->request('GET', '/profile/logout');
    }

    public function dataProfileInfo()
    {
        return [
            [
                ['email' => 'hidan98@gmail.ru', 'password' => '12345678'], 301,
                [
                    'email' => 'hidan98@gmail.ru',
                    'name' => 'Alex',
                    'surname' => 'Usanin',
                    'phone_number' => '+79125947072',
                    'company_name' => 'company1'
                ], 200
            ],
            [
                ['email' => 'hidan98@gmail.ru', 'password' => '1234'], 400,
                null, 400
            ]
        ];
    }

    /**
     * @dataProvider dataLogoutUser
     */
    public function testLogoutUser($login_body, $login_status, $expected_status)
    {
        $this->request('POST', '/login', $login_body);
        $this->assertThatResponseHasStatus($login_status);

        $this->request('GET', '/profile/logout');
        $this->assertThatResponseHasStatus($expected_status);
    }

    public function dataLogoutUser()
    {
        return [
            [
                ['email' => 'hidan98@gmail.ru', 'password' => '12345678'], 301, 200
            ],
            [
                ['email' => 'hidan98@gmail.ru', 'password' => '1234'], 400, 400
            ]
        ];
    }

    /**
     * @dataProvider dataDeleteUser
     */
    public function testDeleteUser($login_body, $login_status, $expected_status)
    {
        $this->request('POST', '/login', $login_body);
        $this->assertThatResponseHasStatus($login_status);

        $this->request('GET', '/profile/delete');
        $this->assertThatResponseHasStatus($expected_status);
    }

    public function dataDeleteUser()
    {
        return [
            [
                ['email' => 'hidan98@gmail.ru', 'password' => '12345678'], 301, 200
            ],
            [
                ['email' => 'hidan98@gmail.ru', 'password' => '1234'], 400, 400
            ]
        ];
    }
}