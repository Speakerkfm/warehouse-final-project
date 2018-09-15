<?php

namespace App\Controller;
use JsonSchema\Validator;
use JsonSchema\Constraints\Constraint;

class AbstractController
{
    /**
     * @var Validator
     */
    private $validator;

    public function __construct()
    {
        $this->validator = new Validator();
    }

    public function CheckAccess()
    {
        if (!isset($_SESSION['id']))
        {
            throw new \InvalidArgumentException('You need to login!');
        }
    }

    /**
     * @param $params object
     * @param $schema string
     * @param $type
     */
    public function Validation($params, $schema, $type = Constraint::CHECK_MODE_NORMAL)
    {
        $this->validator->validate(
            $params,
            (object)['$ref' => 'file://' . realpath('src/schemas/'.$schema)],
            $type);

        if (!$this->validator->isValid()){
            $wrong_field = $this->validator->getErrors()[0]["property"];
            throw new \InvalidArgumentException('Wrong '.$wrong_field);
        }
    }

    /**
     * @return int|null
     */
    public function GetUserId()
    {
        return $_SESSION['id'];
    }
}