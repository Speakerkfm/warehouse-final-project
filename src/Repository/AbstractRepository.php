<?php

namespace App\Repository;

use Doctrine\DBAL\Connection;

abstract class AbstractRepository
{
    /**
     * @var Connection
     */
    protected $dbConnection;

    public function __construct(Connection $dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }
}