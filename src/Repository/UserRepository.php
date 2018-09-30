<?php

namespace App\Repository;

use App\Model\User;

class UserRepository extends AbstractRepository
{
    /**
     * @param $user User
     * @throws
     */
    public function UserCheck($user)
    {
        $email_duplicates = $this->dbConnection->fetchAssoc(
            'SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?',
            [$user->getEmail(), $user->getId()]
        )['COUNT(*)'];

        if ($email_duplicates > 0){
            throw new \InvalidArgumentException('User with this email has already registered!');
        }

        $fio_duplicates = $this->dbConnection->fetchAssoc(
            'SELECT COUNT(*) FROM users WHERE name = ? AND surname = ? AND company_id = ? AND id <> ?',
            [$user->getName(), $user->getSurname(), $user->getCompanyId(), $user->getId()]
        )['COUNT(*)'];

        if ($fio_duplicates > 0){
            throw new \InvalidArgumentException('User with this name in this company has already registered!');
        }
    }

    /**
     * @param $company_name string
     * @return int
     * @throws
     */
    public function GetCompany($company_name)
    {
        $company_id = $this->dbConnection->fetchAssoc(
            'SELECT id FROM companies WHERE company_name = ?',
            [$company_name]
        )['id'];

        if ($company_id == null){
            throw new \InvalidArgumentException('Company does not exist!');
        }

        return $company_id;
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
        $user = new User(0, $email, $name, $surname, $phone_number, $password, 0, $company_name);

        $user->setCompanyId($this->GetCompany($user->getCompanyName()));

        $this->UserCheck($user);

        $this->dbConnection->executeQuery(
            'INSERT INTO users 
                    (email, name, surname, password, phone_number, company_id, salt)
                    VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $user->getEmail(),
                $user->getName(),
                $user->getSurname(),
                $user->getPassword(),
                $user->getPhoneNumber(),
                $user->getCompanyId(),
                $user->getSalt()
            ]
        );

        $user->setId($this->dbConnection->lastInsertId());

        return $user;
    }

    /**
     * @param $user_id int
     * @return User
     * @throws
     */
    public function GetUser($user_id)
    {
        $row = $this->dbConnection->fetchAssoc(
            'SELECT users.id as user_id, email, name, surname, phone_number, password, company_id, company_name
                       FROM users, companies WHERE company_id = companies.id AND users.id = ?',
            [$user_id]
        );

        $user = new User(
            $row['user_id'],
            $row['email'],
            $row['name'],
            $row['surname'],
            $row['phone_number'],
            $row['password'],
            $row['company_id'],
            $row['company_name']
        );

        return $user;
    }

    /**
     * @param $user User
     * @return User
     * @throws
     */
    public function UpdateUser($user)
    {
        $user->setCompanyId($this->GetCompany($user->getCompanyName()));

        $this->UserCheck($user);

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
                $user->getEmail(),
                $user->getName(),
                $user->getSurname(),
                $user->getPassword(),
                $user->getPhoneNumber(),
                $user->getCompanyId(),
                $user->getSalt(),
                $user->getId()
            ]
        );

        return $user;
    }

    /**
     * @param $email string
     * @param $password string
     * @throws
     * @return int
     */
    public function LoginUser($email, $password)
    {
        $row = $this->dbConnection->fetchAssoc(
            'SELECT * FROM users WHERE email = ?',
            [$email]
        );

        if (isset($row['id'])) {
            $password_hash = md5($password . $row['salt']);
            if ($password_hash != $row['password']) {
                throw new \InvalidArgumentException('Wrong password!');
            }
        } else {
            throw new \InvalidArgumentException('Wrong password!');
        }

        return $row['id'];
    }

    /**
     * @param $user_id int
     * @throws
     */
    public function DeleteUser($user_id)
    {
        $this->dbConnection->executeQuery(
            'DELETE FROM users WHERE id = ?',
            [$user_id]
        );
    }
}