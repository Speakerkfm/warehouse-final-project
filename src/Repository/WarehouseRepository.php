<?php

namespace App\Repository;

use App\Model\Warehouse;

class WarehouseRepository extends AbstractRepository
{
    /**
     * @param $user_id int
     * @param $warehouse_id int
     * @throws
     *
    public function CheckWarehouse($user_id, $warehouse_id)
    {
        $row = $this->dbConnection->fetchAssoc(
            'SELECT * FROM warehouses WHERE id = ?',
            [$warehouse_id]
        );

        if (!isset($row['id'])){
            throw new \InvalidArgumentException('Warehouse does not exist '.$warehouse_id);
        }
        if ($row['user_id'] != $user_id){
            throw new \InvalidArgumentException('Wrong access '.$warehouse_id);
        }
    }
     * /

    /**
     * @param $address string
     * @param $warehouse_id int|null
     * @throws
     */
    public function CheckAddressDuplicates($address, $warehouse_id)
    {
        $address_duplicates = $this->dbConnection->fetchAssoc(
            'SELECT COUNT(*) FROM warehouses WHERE address = ? AND id <> ?',
            [$address, $warehouse_id]
        )['COUNT(*)'];

        if ($address_duplicates > 0){
            throw new \InvalidArgumentException('Warehouse with this address is already exist!');
        }
    }

    /**
     * @param $user_id int
     * @param $warehouse_from_id int|null
     * @param $warehouse_to_id int|null
     * @param $movement_type string
     */
    public function CheckWarehousesInTransaction($user_id, $warehouse_from_id, $warehouse_to_id, $movement_type)
    {
        switch ($movement_type){
            case 'app':
                $this->GetWarehouse($user_id, $warehouse_to_id);
                break;
            case 'detach':
                $this->GetWarehouse($user_id, $warehouse_from_id);
                break;
            case 'move':
                $this->GetWarehouse($user_id, $warehouse_from_id);
                $this->GetWarehouse($user_id, $warehouse_to_id);
                break;
        }
        if ($warehouse_from_id == $warehouse_to_id) {
            throw new \InvalidArgumentException('Warehouses are the same!');
        }
    }

    /**
     * @param $user_id
     * @param $warehouse_id int
     * @return Warehouse
     * @throws
     */
    public function GetWarehouse($user_id, $warehouse_id)
    {
        $row = $this->dbConnection->fetchAssoc(
            'SELECT * FROM warehouses WHERE id = ?',
            [$warehouse_id]
        );

        if (!isset($row['id'])){
            throw new \InvalidArgumentException('Warehouse does not exist '.$warehouse_id);
        }
        if ($row['user_id'] != $user_id){
            throw new \InvalidArgumentException('Wrong access '.$warehouse_id);
        }

        return $row['id'] !== null ?
            new Warehouse($row['id'], $row['address'], $row['capacity'], $row['user_id'], $row['total_size'], $row['balance']) :
            null;
    }

    /**
     * @param $user_id int
     * @return Warehouse[]
     * @throws
     */
    public function GetWarehouseList($user_id)
    {
        $warehouses = [];

        $rows = $this->dbConnection->executeQuery(
            'SELECT * FROM warehouses WHERE user_id = ?',
            [$user_id]
        );

        while ($row = $rows->fetch(\PDO::FETCH_ASSOC)){
            $warehouses[] = new Warehouse($row['id'], $row['address'], $row['capacity'], $row['user_id'], $row['total_size'], $row['balance']);
        }

        return $warehouses;
    }

    /**
     * @param $address string
     * @param $capacity double
     * @param $user_id int
     * @return Warehouse
     * @throws
     */
    public function CreateWarehouse($address, $capacity, $user_id)
    {
        $warehouse = new Warehouse(null, $address, $capacity, $user_id, 0, 0);

        $this->dbConnection->executeQuery(
            'INSERT INTO warehouses (address, capacity, user_id) VALUES (?, ?, ?)',
            [$warehouse->getAddress(), $warehouse->getCapacity(), $warehouse->getUserId()]
        );

        $warehouse->setId($this->dbConnection->lastInsertId());

        return $warehouse;
    }

    /**
     * @param $warehouse Warehouse
     * @throws
     */
    public function UpdateWarehouse($warehouse)
    {
        $this->dbConnection->executeQuery(
            'UPDATE warehouses SET address = ?, capacity = ? WHERE id = ?',
            [$warehouse->getAddress(), $warehouse->getCapacity(), $warehouse->getId()]
        );
    }

    /**
     * @param $warehouse_id int
     * @throws
     */
    public function DeleteWarehouse($warehouse_id)
    {
        $this->dbConnection->executeQuery(
            'DELETE FROM warehouses WHERE id = ?',
            [$warehouse_id]
        );
    }

    /**
     * @param $user_id int
     * @return array
     * @throws
     */
    public function GetDefaultWarehouseList($user_id)
    {
        $warehouses = $this->dbConnection->executeQuery(
            'SELECT * FROM warehouses WHERE user_id = ?',
            [$user_id]
        );

        $warehouses_list = [];

        while ($warehouse = $warehouses->fetch(\PDO::FETCH_ASSOC)) {
            $warehouses_list[$warehouse['id']] = 0;
        }

        return $warehouses_list;
    }
}