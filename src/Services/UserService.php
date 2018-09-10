<?php

namespace App\Services;

use Doctrine\DBAL\Connection;
use http\Exception\InvalidArgumentException;

class UserService extends AbstractService
{
    /**
     * @param $email string
     * @param $name string
     * @param $surname string
     * @param $password string
     * @param $phone_number string
     * @param $company_name string
     * @return null|string
     * @throws \Doctrine\DBAL\DBALException
     */
    public function RegisterUser($email, $name, $surname, $password, $phone_number, $company_name)
    {
        $company_id = $this->dbConnection->executeQuery(
            'SELECT id FROM companies WHERE company_name = ?',
            [$company_name]
        )->fetch(\PDO::FETCH_ASSOC)['id'];

        if ($company_id == null){
            throw new \InvalidArgumentException('Company does not exist!');
        }

        $email_duplicates = $this->dbConnection->executeQuery(
            'SELECT COUNT(*) FROM users WHERE email = ?',
            [$email]
        )->fetch(\PDO::FETCH_ASSOC)['COUNT(*)'];

        if ($email_duplicates > 0){
            throw new \InvalidArgumentException('User with this email has already registered!');
        }

        $fio_duplicates = $this->dbConnection->executeQuery(
            'SELECT COUNT(*) FROM users WHERE name = ? AND surname = ? AND company_id = ?',
            [$name, $surname, $company_id]
        )->fetch(\PDO::FETCH_ASSOC)['COUNT(*)'];

        if ($fio_duplicates > 0){
            throw new \InvalidArgumentException('User with this name in this company has already registered!');
        }

        if ($company_id != null && $email_duplicates == 0 && $fio_duplicates == 0) {
            $salt = uniqid();
            $password_hash = md5($password . $salt);
            $this->dbConnection->executeQuery(
                'INSERT INTO users 
                    (email, name, surname, password, phone_number, company_id, salt)
                    VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $email,
                    $name,
                    $surname,
                    $password_hash,
                    $phone_number,
                    $company_id,
                    $salt
                ]
            );
            return $this->dbConnection->lastInsertId();
        } else {
            return null;
        }
    }

    public function LoginUser($email, $password)
    {
        //if (preg_match("/^[a-zA-Z0-9]{3,30}$/", $email))
        {
            $row = $this->dbConnection->executeQuery(
                'SELECT * FROM users WHERE email = ?',
                [
                    $email
                ]
            )->fetch(\PDO::FETCH_ASSOC);

            if (isset($row)) {
                $password_hash = md5($password.$row['salt']);
                if ($password_hash == $row['password']) {
                    return [
                        'id' => $row['id'],
                        'status' => 'OK'
                        ];
                } else {
                    return [
                        'status' => 'Bad'
                    ];
                }
            }
        }
    }

    public function DeleteUser($user_id)
    {
        $this->dbConnection->executeQuery(
            'DELETE FROM users WHERE id = ?',
            [$user_id]
        );
    }

    public function UpdateUser($user_id, $email, $name, $surname, $password, $phone_number, $company_name)
    {
        $company_id = $this->dbConnection->executeQuery(
            'SELECT id FROM companies WHERE company_name = ?',
            [$company_name]
        )->fetch(\PDO::FETCH_ASSOC)['id'];

        if ($company_id == null){
            throw new \InvalidArgumentException('Company does not exist!');
        }

        $email_duplicates = $this->dbConnection->executeQuery(
            'SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?',
            [$email, $user_id]
        )->fetch(\PDO::FETCH_ASSOC)['COUNT(*)'];

        if ($email_duplicates > 0){
            throw new \InvalidArgumentException('User with this email has already registered!');
        }

        $fio_duplicates = $this->dbConnection->executeQuery(
            'SELECT COUNT(*) FROM users WHERE name = ? AND surname = ? AND company_id = ? AND id <> ?',
            [$name, $surname, $company_id, $user_id]
        )->fetch(\PDO::FETCH_ASSOC)['COUNT(*)'];

        if ($fio_duplicates > 0){
            throw new \InvalidArgumentException('User with this name in this company has already registered!');
        }

        if ($company_id != null && $email_duplicates == 0 && $fio_duplicates == 0) {
            $salt = uniqid();
            $password_hash = md5($password . $salt);
            $this->dbConnection->executeQuery(
                'UPDATE users 
                       SET email = ?, 
                           name = ?,
                           surname = ?,
                           password = ?,
                           phone_number = ?,
                           company_id = ?,
                           salt = ?
                      WHERE id = ?',
                [
                    $email,
                    $name,
                    $surname,
                    $password_hash,
                    $phone_number,
                    $company_id,
                    $salt,
                    $user_id
                ]
            );
            return true;
        } else {
            return false;
        }
    }

    public function UserInfo($id)
    {
        $row = $this->dbConnection->executeQuery(
            'SELECT * FROM users, companies WHERE company_id = companies.id AND users.id = ?',
            [
                $id
            ]
        )->fetch(\PDO::FETCH_ASSOC);
        return [
            "email" => $row['email'],
            "name" => $row['name'],
            "surname" => $row['surname'],
            "phone_number" => $row['phone_number'],
            "company_name" => $row['company_name']
        ];
    }
}