<?php

namespace App\Model;

class ProductOnWarehouse
{
    /**
     * @var int
     */
    private $product_id;

    /**
     * @var int
     */
    private $warehouse_id;

    /**
     * @var int
     */
    private $count;

    public function __construct($product_id, $warehouse_id, $count)
    {
        $this->product_id = $product_id;
        $this->warehouse_id = $warehouse_id;
        $this->count = $count;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @return int
     */
    public function getProductId()
    {
        return $this->product_id;
    }

    /**
     * @return int
     */
    public function getWarehouseId()
    {
        return $this->warehouse_id;
    }

    /**
     * @param int $count
     */
    public function setCount($count)
    {
        $this->count = $count;
    }

    public function CheckAvailableCount($count)
    {
        if ($this->count < $count) {
            throw new \InvalidArgumentException(
                'Not enough products ' . $this->product_id . ' on warehouse ' . $this->warehouse_id
            );
        }
    }
}