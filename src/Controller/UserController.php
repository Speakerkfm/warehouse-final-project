<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Services\UserService;
use PHPUnit\Runner\Exception;
use Slim\Http\Response;
use Slim\Http\Request;

class UserController
{
    private $UserService;

    public function __construct(UserService $UserService)
    {
        $this->UserService = $UserService;
    }

    public function RegisterUser(Request $request, Response $response)
    {
        $bodyParams = $request->getParsedBody();

        $email = $bodyParams['email'];
        $name = $bodyParams['name'];
        $surname = $bodyParams['surname'];
        $password = $bodyParams['password'];
        $phone_number = $bodyParams['phone_number'];
        $company_name = $bodyParams['company_name'];

        if (!isset($email) || !preg_match("/^[a-zA-Z0-9@.]{3,30}$/", $email)){
            return $response->withStatus(400, 'Wrong email!');
        }
        if (!isset($name) || !preg_match("/^[a-zA-Z0-9]{3,30}$/", $name)){
            return $response->withStatus(400, 'Wrong name!');
        }
        if (!isset($surname) || !preg_match("/^[a-zA-Z0-9]{3,30}$/", $surname)){
            return $response->withStatus(400, 'Wrong surname!');
        }
        if (!isset($password) || !preg_match("/^[a-zA-Z0-9]{3,30}$/", $password)){
            return $response->withStatus(400, 'Wrong password!');
        }
        if (!isset($phone_number) || !preg_match("/^[+0-9]{3,30}$/", $phone_number)){
            return $response->withStatus(400, 'Wrong phone number!');
        }
        if (!isset($company_name) || !preg_match("/^[a-zA-Z0-9]{3,30}$/", $company_name)){
            return $response->withStatus(400, 'Wrong company name!');
        }

        try {
            $id = $this->UserService->RegisterUser($email, $name, $surname, $password, $phone_number, $company_name);

            return $response->withStatus(200, 'Success!');
        } catch (\Exception $e)
        {
            return $response->withStatus(400, $e->getMessage());
        }
    }

    public function LoginUser(Request $request, Response $response)
    {
        $bodyParams = $request->getParsedBody();

        $email = $bodyParams['email'];
        $password = $bodyParams['password'];

        if (!isset($email) || !preg_match("/^[a-zA-Z0-9@.]{3,30}$/", $email)){
            return $response->withStatus(400, 'Wrong email!');
        }
        if (!isset($password) || !preg_match("/^[a-zA-Z0-9]{3,30}$/", $password)){
            return $response->withStatus(400, 'Wrong password!');
        }

        $login_result = $this->UserService->LoginUser($email, $password);

        if ($login_result['status'] == 'OK') {
            $_SESSION['id'] = $login_result['id'];
            return $response->withRedirect('/profile/info', 301);
        } else {
            return $response->withStatus(400, 'Wrong password!');
        }
    }

    public function LogoutUser(Request $request, Response $response)
    {
        if (!isset($_SESSION['id']))
        {
            return $response->withStatus(400, 'You need to login!');
        }
        unset($_SESSION['id']);
        session_destroy();
        return $response->withStatus(200, 'You logged out');
    }

    public function DeleteUser(Request $request, Response $response)
    {
        if (!isset($_SESSION['id']))
        {
            return $response->withStatus(400, 'You need to login!');
        }
        $user_id = $_SESSION['id'];

        $this->UserService->DeleteUser($user_id);
        unset($_SESSION['id']);
        session_destroy();
    }

    public function UpdateUser(Request $request, Response $response)
    {
        if (!isset($_SESSION['id']))
        {
            return $response->withStatus(400, 'You need to login!');
        }
        $bodyParams = $request->getParsedBody();

        $email = $bodyParams['email'];
        $name = $bodyParams['name'];
        $surname = $bodyParams['surname'];
        $password = $bodyParams['password'];
        $phone_number = $bodyParams['phone_number'];
        $company_name = $bodyParams['company_name'];
        if (!isset($email) || !preg_match("/^[a-zA-Z0-9@.]{3,30}$/", $email)){
            return $response->withStatus(400, 'Wrong email!');
        }
        if (!isset($name) || !preg_match("/^[a-zA-Z0-9]{3,30}$/", $name)){
            return $response->withStatus(400, 'Wrong name!');
        }
        if (!isset($surname) || !preg_match("/^[a-zA-Z0-9]{3,30}$/", $surname)){
            return $response->withStatus(400, 'Wrong surname!');
        }
        if (!isset($password) || !preg_match("/^[a-zA-Z0-9]{3,30}$/", $password)){
            return $response->withStatus(400, 'Wrong password!');
        }
        if (!isset($phone_number) || !preg_match("/^[+0-9]{3,30}$/", $phone_number)){
            return $response->withStatus(400, 'Wrong phone number!');
        }
        if (!isset($company_name) || !preg_match("/^[a-zA-Z0-9]{3,30}$/", $company_name)){
            return $response->withStatus(400, 'Wrong company name!');
        }

        try {
            $id = $_SESSION['id'];
            $this->UserService->UpdateUser($id, $email, $name, $surname, $password, $phone_number, $company_name);

            return $response->withStatus(200, 'Success!');
        } catch (\Exception $e)
        {
            return $response->withStatus(400, $e->getMessage());
        }
    }

    public function UserInfo(Request $request, Response $response)
    {
        if (!isset($_SESSION['id']))
        {
            return $response->withStatus(400, 'You need to login!');
        }
        return $response->withJson($this->UserService->UserInfo($_SESSION['id']), 200);
    }
}