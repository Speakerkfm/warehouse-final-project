<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Services\UserService;
use PHPUnit\Runner\Exception;
use Slim\Http\Response;
use Slim\Http\Request;

class UserController extends AbstractController
{
    /**
     * @var UserService
     */
    private $UserService;

    public function __construct(UserService $UserService)
    {
        parent::__construct();
        $this->UserService = $UserService;
    }

    /**
     * @param $user_id int
     */
    public function SetUser($user_id)
    {
        $_SESSION['id'] = $user_id;
    }

    public function RegisterUser(Request $request, Response $response)
    {
        $bodyParams = $request->getParsedBody();

        try {
            $this->Validation($bodyParams, 'UserSchema.json');

            $email = $bodyParams['email'];
            $name = $bodyParams['name'];
            $surname = $bodyParams['surname'];
            $password = $bodyParams['password'];
            $phone_number = $bodyParams['phone_number'];
            $company_name = $bodyParams['company_name'];

            $this->UserService->RegisterUser($email, $name, $surname, $password, $phone_number, $company_name);

            return $response->withStatus(200, 'Success!');
        } catch (\Exception $e) {
            return $response->withStatus(400, $e->getMessage());
        }
    }

    public function LoginUser(Request $request, Response $response)
    {
        $bodyParams = $request->getParsedBody();

        try {
            $this->Validation($bodyParams, 'LoginSchema.json');

            $email = $bodyParams['email'];
            $password = $bodyParams['password'];

            $user_id = $this->UserService->LoginUser($email, $password);

            $this->SetUser($user_id);

            return $response->withRedirect('/profile/info', 301);
        } catch (\Exception $e) {
            return $response->withStatus(400, $e->getMessage());
        }
    }

    public function LogoutUser(Request $request, Response $response)
    {
        try {
            $this->CheckAccess();

            unset($_SESSION['id']);
            session_destroy();

            return $response->withStatus(200, 'You logged out');
        } catch (\Exception $e){
            return $response->withStatus(400, $e->getMessage());
        }
    }

    public function DeleteUser(Request $request, Response $response)
    {
        try {
            $this->CheckAccess();

            $this->UserService->DeleteUser($this->GetUserId());
            unset($_SESSION['id']);
            session_destroy();

            return $response->withStatus(200, 'Success!');
        } catch (\Exception $e) {
            return $response->withStatus(400, $e->getMessage());
        }
    }

    public function UpdateUser(Request $request, Response $response)
    {
        $bodyParams = $request->getParsedBody();

        try {
            $this->CheckAccess();

            $this->Validation($bodyParams, 'UserSchema.json');

            $email = $bodyParams['email'];
            $name = $bodyParams['name'];
            $surname = $bodyParams['surname'];
            $password = $bodyParams['password'];
            $phone_number = $bodyParams['phone_number'];
            $company_name = $bodyParams['company_name'];

            $this->UserService->UpdateUser(
                $this->GetUserId(),
                $email,
                $name,
                $surname,
                $password,
                $phone_number,
                $company_name
            );

            return $response->withStatus(200, 'Success!');
        } catch (\Exception $e)
        {
            return $response->withStatus(400, $e->getMessage());
        }
    }

    public function UserInfo(Request $request, Response $response)
    {
        try {
            $this->CheckAccess();

            return $response->withJson($this->UserService->UserInfo($this->GetUserId()), 200);
        } catch (\Exception $e)
        {
            return $response->withStatus(400, $e->getMessage());
        }
    }
}