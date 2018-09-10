<?php

namespace App\Services;

use Doctrine\DBAL\Connection;

abstract class AbstractService
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