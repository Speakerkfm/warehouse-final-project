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
    private $id;

    private $name;

    private $price;

    private $size;

    private $type_name;

    public function __construct($id, $name, $price, $size, $type_name)
    {
        $this->id = $id;
        $this->name = $name;
        $this->price = $price;
        $this->size = $size;
        $this->type_name = $type_name;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @return mixed
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @return mixed
     */
    public function getTypeName()
    {
        return $this->type_name;
    }
}