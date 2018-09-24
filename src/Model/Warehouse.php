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

    /**
     * @var double
     */
    private $balance;

    /**
     * @var int
     */
    private $user_id;

    /**
     * @var double
     */
    private $capacity;

    /**
     * @var double
     */
    private $total_size;


    public function __construct($id, $address, $capacity, $user_id, $total_size, $balance)
    {
        $this->id = $id;
        $this->address = $address;
        $this->capacity = $capacity;
        $this->user_id = $user_id;
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

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * @param $capacity double
     */
    private function CheckTotalSize($capacity)
    {
        if ($this->total_size > $capacity) {
            throw new \InvalidArgumentException(('Too much products to set this capacity'));
        }
    }

    /**
     * @param $product_size double
     */
    public function CheckAvailableSize($product_size)
    {
        $available_size = $this->capacity - $this->total_size;

        if ($product_size > $available_size) {
            throw new \InvalidArgumentException('Not enough space on warehouse ' . $this->id);
        }
    }

    /**
     * @param mixed $capacity
     */
    public function setCapacity($capacity)
    {
        $this->CheckTotalSize($capacity);
        $this->capacity = $capacity;
    }

    /**
     * @param string $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }
}