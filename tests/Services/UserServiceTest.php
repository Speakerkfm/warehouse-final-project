<?php

use App\Services\UserService;
use App\Repository\UserRepository;

class UserServiceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $dbConnectionMock;

    /**
     * @var UserService
     */
    private $userService;

    public function setUp()
    {
        $this->dbConnectionMock = $this->getMockBuilder(\Doctrine\DBAL\Connection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $userRepository = new UserRepository($this->dbConnectionMock);

        $this->userService = new UserService($userRepository);
    }

    /**
     * @dataProvider dataRegisterUser
     */
    public function testRegisterUser($user_id, $email, $name, $surname, $password, $phone_number, $company_id, $company_name,
                                     $company_checked, $email_duplicates, $fio_duplicates, $expected, $allOK, $error_type = '')
    {
        $idx = 0;

        $this->dbConnectionMock->expects($this->at($idx++))
            ->method('fetchAssoc')
            ->with('SELECT id FROM companies WHERE company_name = ?',
                [$company_name])
            ->will($this->returnValue(['id' => $company_id]));

        if ($company_checked) {

            $this->dbConnectionMock->expects($this->at($idx++))
                ->method('fetchAssoc')
                ->with('SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?',
                    [$email, null])
                ->will($this->returnValue(['COUNT(*)' => $email_duplicates]));

            if ($email_duplicates == 0) {
                $this->dbConnectionMock->expects($this->at($idx++))
                    ->method('fetchAssoc')
                    ->with('SELECT COUNT(*) FROM users WHERE name = ? AND surname = ? AND company_id = ? AND id <> ?',
                        [$name, $surname, $company_id, null])
                    ->will($this->returnValue(['COUNT(*)' => $fio_duplicates]));

                if ($fio_duplicates == 0){
                    $this->dbConnectionMock->expects($this->at($idx++))
                        ->method('executeQuery');

                    $this->dbConnectionMock->expects($this->at($idx++))
                        ->method('lastInsertId')
                        ->will($this->returnValue($user_id));
                }
            }
        }

        if (!$allOK)
            $this->expectExceptionMessage($error_type);

        $user = $this->userService->RegisterUser($email, $name, $surname, $password, $phone_number, $company_name);

        $this->assertEquals($user->getId(), $expected['id']);
        $this->assertEquals($user->getEmail(), $expected['email']);
        $this->assertEquals($user->getName(), $expected['name']);
        $this->assertEquals($user->getSurname(), $expected['surname']);
        $this->assertEquals($user->getPhoneNumber(), $expected['phone_number']);
        $this->assertEquals($user->getCompanyName(), $expected['company_name']);
    }

    public function dataRegisterUser()
    {
        return [
            [
                1, 'email@mail.ru', 'name1', 'surname1', '123456', '79121231234', 1, 'company1', true,
                0, 0, ['id' => 1, 'email' => 'email@mail.ru', 'name' => 'name1', 'surname' => 'surname1', 'phone_number' => '79121231234',
                'company_name' => 'company1'], true
            ],
            [
                1, 'email@mail.ru', 'name1', 'surname1', '123456', '79121231234', null, 'company1', false,
                0, 0, ['id' => 1, 'email' => 'email@mail.ru', 'name' => 'name1', 'surname' => 'surname1', 'phone_number' => '79121231234',
                'company_name' => 'company1'], false, 'Company does not exist!'
            ],
            [
                1, 'email@mail.ru', 'name1', 'surname1', '123456', '79121231234', 1, 'company1', true,
                1, 0, ['id' => 1, 'email' => 'email@mail.ru', 'name' => 'name1', 'surname' => 'surname1', 'phone_number' => '79121231234',
                'company_name' => 'company1'], false, 'User with this email has already registered!'
            ],
            [
                1, 'email@mail.ru', 'name1', 'surname1', '123456', '79121231234', 1, 'company1', true,
                0, 1, ['id' => 1, 'email' => 'email@mail.ru', 'name' => 'name1', 'surname' => 'surname1', 'phone_number' => '79121231234',
                'company_name' => 'company1'], false, 'User with this name in this company has already registered!'
            ]
        ];
    }

    /**
     * @dataProvider dataLoginUser
     */
    public function testLoginUser($user_id, $email, $password, $password_hash, $salt, $expected, $allOK, $error_type = '')
    {
        $this->dbConnectionMock->expects($this->once())
            ->method('fetchAssoc')
            ->with('SELECT * FROM users WHERE email = ?',
                [$email])
            ->will($this->returnValue(['id' => $user_id, 'password' => $password_hash, 'salt' => $salt]));

        if (!$allOK)
            $this->expectExceptionMessage($error_type);

        $id = $this->userService->LoginUser($email, $password);
        $this->assertEquals($id, $expected);
    }

    public function dataLoginUser()
    {
        return [
            [
                1, 'email@mail.ru', '12345678', '7a97005d27389f05e20280fab6a8d4c8', '5b9d2755b9621', 1, true
            ],
            [
                1, 'email@mail.ru', '123456789', '7a97005d27389f05e20280fab6a8d4c8', '5b9d2755b9621', 1, false,
                'Wrong password!'
            ],
            [
                null, 'email@mail.ru', '12345678', '7a97005d27389f05e20280fab6a8d4c8', '5b9d2755b9621', null, false,
                'Wrong password!'
            ]
        ];
    }

    /**
     * @dataProvider dataDeleteUser
     */
    public function testDeleteUser($user_id)
    {
        $this->dbConnectionMock->expects($this->once())
            ->method('executeQuery')
            ->with('DELETE FROM users WHERE id = ?',
                [$user_id]);

        $this->userService->DeleteUser($user_id);
    }

    public function dataDeleteUser()
    {
        return [
            [
                1
            ]
        ];
    }

    /**
     * @dataProvider dataUpdateUser
     */
    public function testUpdateUser($user_id, $email, $name, $surname, $password, $phone_number, $company_id, $company_name,
                                   $company_checked, $email_duplicates, $fio_duplicates, $user, $expected, $allOK, $error_type = '')
    {
        $idx = 0;

        $this->dbConnectionMock->expects($this->at($idx++))
            ->method('fetchAssoc')
            ->with('SELECT users.id as user_id, email, name, surname, phone_number, password, company_id, company_name
                       FROM users, companies WHERE company_id = companies.id AND users.id = ?',
                [$user_id])
            ->will($this->returnValue($user));

        $this->dbConnectionMock->expects($this->at($idx++))
            ->method('fetchAssoc')
            ->with('SELECT id FROM companies WHERE company_name = ?',
                [$company_name])
            ->will($this->returnValue(['id' => $company_id]));

        if ($company_checked) {

            $this->dbConnectionMock->expects($this->at($idx++))
                ->method('fetchAssoc')
                ->with('SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?',
                    [$email, $user_id])
                ->will($this->returnValue(['COUNT(*)' => $email_duplicates]));

            if ($email_duplicates == 0) {
                $this->dbConnectionMock->expects($this->at($idx++))
                    ->method('fetchAssoc')
                    ->with('SELECT COUNT(*) FROM users WHERE name = ? AND surname = ? AND company_id = ? AND id <> ?',
                        [$name, $surname, $company_id, $user_id])
                    ->will($this->returnValue(['COUNT(*)' => $fio_duplicates]));

                if ($fio_duplicates == 0) {
                    $this->dbConnectionMock->expects($this->at($idx++))
                        ->method('executeQuery');
                }
            }
        }

        if (!$allOK)
            $this->expectExceptionMessage($error_type);

        $user = $this->userService->UpdateUser($user_id, $email, $name, $surname, $password, $phone_number, $company_name);

        $this->assertEquals($user->getId(), $expected['id']);
        $this->assertEquals($user->getEmail(), $expected['email']);
        $this->assertEquals($user->getName(), $expected['name']);
        $this->assertEquals($user->getSurname(), $expected['surname']);
        $this->assertEquals($user->getPhoneNumber(), $expected['phone_number']);
        $this->assertEquals($user->getCompanyName(), $expected['company_name']);
    }

    public function dataUpdateUser()
    {
        return [
            [
                1, 'email@mail.ru', 'name1', 'surname1', '123456', '79121231234', 1, 'company1', true,
                0, 0, ['user_id' => 1, 'email' => 'email@mail.ru', 'name' => 'name0', 'surname' => 'surname0', 'phone_number' => '79121211111',
                'company_id' => 2, 'company_name' => 'company0'],
                ['id' => 1, 'email' => 'email@mail.ru', 'name' => 'name1', 'surname' => 'surname1', 'phone_number' => '79121231234',
                'company_name' => 'company1'], true
            ],
            [
                1, 'email@mail.ru', 'name1', 'surname1', '123456', '79121231234', null, 'company1', false,
                0, 0, ['user_id' => 1, 'email' => 'email@mail.ru', 'name' => 'name0', 'surname' => 'surname0', 'phone_number' => '79121211111',
                'company_id' => 2, 'company_name' => 'company0'],
                ['id' => 1, 'email' => 'email@mail.ru', 'name' => 'name1', 'surname' => 'surname1', 'phone_number' => '79121231234',
                    'company_name' => 'company1'], false, 'Company does not exist!'
            ],
            [
                1, 'email@mail.ru', 'name1', 'surname1', '123456', '79121231234', 1, 'company1', true,
                1, 0, ['user_id' => 1, 'email' => 'email@mail.ru', 'name' => 'name0', 'surname' => 'surname0', 'phone_number' => '79121211111',
                'company_id' => 2, 'company_name' => 'company0'],
                ['id' => 1, 'email' => 'email@mail.ru', 'name' => 'name1', 'surname' => 'surname1', 'phone_number' => '79121231234',
                    'company_name' => 'company1'], false, 'User with this email has already registered!'
            ],
            [
                1, 'email@mail.ru', 'name1', 'surname1', '123456', '79121231234', 1, 'company1', true,
                0, 1, ['user_id' => 1, 'email' => 'email@mail.ru', 'name' => 'name0', 'surname' => 'surname0', 'phone_number' => '79121211111',
                'company_id' => 2, 'company_name' => 'company0'],
                ['id' => 1, 'email' => 'email@mail.ru', 'name' => 'name1', 'surname' => 'surname1', 'phone_number' => '79121231234',
                    'company_name' => 'company1'], false, 'User with this name in this company has already registered!'
            ]
        ];
    }

    /**
     * @dataProvider dataUserInfo
     */
    public function testUserInfo($user_id, $user, $expected)
    {
        $this->dbConnectionMock->expects($this->once())
            ->method('fetchAssoc')
            ->with('SELECT users.id as user_id, email, name, surname, phone_number, password, company_id, company_name
                       FROM users, companies WHERE company_id = companies.id AND users.id = ?',
                [$user_id])
            ->will($this->returnValue($user));

        $user = $this->userService->UserInfo($user_id);

        $this->assertEquals($user, $expected);
    }

    public function dataUserInfo()
    {
        return [
            [
                1, ['user_id' => 1, 'email' => 'email@mail.ru', 'name' => 'name0', 'surname' => 'surname0', 'phone_number' => '79121211111',
                'company_id' => 2, 'company_name' => 'company0'],
                ['email' => 'email@mail.ru', 'name' => 'name0', 'surname' => 'surname0', 'phone_number' => '79121211111',
                    'company_name' => 'company0']
            ]
        ];
    }
}