<?php
/**
 * Created by PhpStorm.
 * User: usanin
 * Date: 13.09.2018
 * Time: 13:05
 */

namespace App\Model;


class Transaction
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var int|null
     */
    private $warehouse_from_id;

    /**
     * @var int|null
     */
    private $warehouse_to_id;

    /**
     * @var string
     */
    private $movement_type;

    /**
     * @var \DateTime
     */
    private $date;

    /**
     * @var double
     */
    private $total_count;

    /**
     * Transaction constructor.
     * @param $id int
     * @param $warehouse_from_id int|null
     * @param $warehouse_to_id int|null
     * @param $movement_type string
     * @param $date \DateTime
     * @param $total_count double
     */
    public function __construct($id, $warehouse_from_id, $warehouse_to_id, $movement_type, $date, $total_count)
    {
        $this->id = $id;
        $this->warehouse_to_id = $warehouse_to_id;
        $this->warehouse_from_id = $warehouse_from_id;
        $this->movement_type = $movement_type;
        $this->date = $date;
        $this->total_count = $total_count;
    }

    /**
     * @return int
     */
    public function getWarehouseFromId()
    {
        return $this->warehouse_from_id;
    }

    /**
     * @return int
     */
    public function getWarehouseToId()
    {
        return $this->warehouse_to_id;
    }

    /**
     * @return string
     */
    public function getMovementType()
    {
        return $this->movement_type;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @return float
     */
    public function getTotalCount()
    {
        return $this->total_count;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param \DateTime $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @param float $total_count
     */
    public function setTotalCount($total_count)
    {
        $this->total_count = $total_count;
    }

    /**
     * @param $product_list array
     * @return array|null
     */
    public function GetData($product_list)
    {
        $data = null;

        switch ($this->movement_type) {
            case 'app':
                $data = [
                    'id' => $this->id,
                    'movement_type' => $this->movement_type,
                    'warehouse_id' => $this->warehouse_to_id,
                    'date' => $this->date,
                    'total_count' => $this->total_count,
                    'product_list' => $product_list
                ];
                break;
            case 'detach':
                $data = [
                    'id' => $this->id,
                    'movement_type' => $this->movement_type,
                    'warehouse_id' => $this->warehouse_from_id,
                    'date' => $this->date,
                    'total_count' => $this->total_count,
                    'product_list' => $product_list
                ];
                break;
            case 'move':
                $data = [
                    'id' => $this->id,
                    'movement_type' => $this->movement_type,
                    'warehouse_from_id' => $this->warehouse_from_id,
                    'warehouse_to_id' => $this->warehouse_to_id,
                    'date' => $this->date,
                    'total_count' => $this->total_count,
                    'product_list' => $product_list
                ];
                break;
        }

        return $data;
    }
}