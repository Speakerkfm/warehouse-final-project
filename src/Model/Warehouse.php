<?php

namespace App\Model;

class Warehouse
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $address;

    private $balance;

    private $capacity;

    private $total_size;


    public function __construct($id, $address, $capacity, $total_size, $balance)
    {
        $this->id = $id;
        $this->address = $address;
        $this->capacity = $capacity;
        $this->total_size = $total_size;
        $this->balance = $balance;
    }


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @return mixed
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * @return mixed
     */
    public function getCapacity()
    {
        return $this->capacity;
    }

    /**
     * @return mixed
     */
    public function getTotalSize()
    {
        return $this->total_size;
    }
}