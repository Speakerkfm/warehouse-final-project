<?php

namespace App\Services;

use App\Repository\UserRepository;
use App\Model\User;

class UserService
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @param $email string
     * @param $name string
     * @param $surname string
     * @param $password string
     * @param $phone_number string
     * @param $company_name string
     * @return User
     * @throws
     */
    public function RegisterUser($email, $name, $surname, $password, $phone_number, $company_name)
    {
        return $this->userRepository->RegisterUser($email, $name, $surname, $password, $phone_number, $company_name);
    }

    /**
     * @param $email string
     * @param $password string
     * @return int
     */
    public function LoginUser($email, $password)
    {
        return $this->userRepository->LoginUser($email, $password);
    }

    /**
     * @param $user_id int
     */
    public function DeleteUser($user_id)
    {
        $this->userRepository->DeleteUser($user_id);
    }

    /**
     * @param $user_id int
     * @param $email string
     * @param $name string
     * @param $surname string
     * @param $password string
     * @param $phone_number string
     * @param $company_name string
     * @return User
     */
    public function UpdateUser($user_id, $email, $name, $surname, $password, $phone_number, $company_name)
    {
        $user = $this->userRepository->GetUser($user_id);

        $user->setEmail($email);
        $user->setName($name);
        $user->setSurname($surname);
        $user->setPassword($password);
        $user->setPhoneNumber($phone_number);
        $user->setCompanyName($company_name);

        return $this->userRepository->UpdateUser($user);
    }

    /**
     * @param $user_id int
     * @return array
     */
    public function UserInfo($user_id)
    {
        $user = $this->userRepository->GetUser($user_id);

        return [
            "email" => $user->getEmail(),
            "name" => $user->getName(),
            "surname" => $user->getSurname(),
            "phone_number" => $user->getPhoneNumber(),
            "company_name" => $user->getCompanyName()
        ];
    }
}