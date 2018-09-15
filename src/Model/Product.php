<?php
/**
 * Created by PhpStorm.
 * User: usanin
 * Date: 07.09.2018
 * Time: 18:54
 */

namespace App\Model;


class Product
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var double
     */
    private $price;

    /**
     * @var double
     */
    private $size;

    /**
     * @var int
     */
    private $type_id;

    /**
     * @var string
     */
    private $type_name;

    /**
     * @var int
     */
    private $user_owner_id;

    public function __construct($id, $name, $price, $size, $type_id, $type_name, $user_owner_id)
    {
        $this->id = $id;
        $this->name = $name;
        $this->price = $price;
        $this->size = $size;
        $this->type_id = $type_id;
        $this->type_name = $type_name;
        $this->user_owner_id = $user_owner_id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return double
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @return double
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @return string
     */
    public function getTypeName()
    {
        return $this->type_name;
    }

    /**
     * @return int
     */
    public function getUserOwnerId()
    {
        return $this->user_owner_id;
    }

    /**
     * @return int
     */
    public function getTypeId()
    {
        return $this->type_id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param float $price
     */
    public function setPrice($price)
    {
        $this->price = $price;
    }

    /**
     * @param float $size
     */
    public function setSize($size)
    {
        $this->size = $size;
    }

    /**
     * @param int $type_id
     */
    public function setTypeId($type_id)
    {
        $this->type_id = $type_id;
    }

    /**
     * @param string $type_name
     */
    public function setTypeName($type_name)
    {
        $this->type_name = $type_name;
    }

    /**
     * @param int $user_owner_id
     */
    public function setUserOwnerId($user_owner_id)
    {
        $this->user_owner_id = $user_owner_id;
    }
}