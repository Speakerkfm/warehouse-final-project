<?php
/**
 * Created by PhpStorm.
 * User: usanin
 * Date: 07.09.2018
 * Time: 20:20
 */

namespace App\Controller;

use App\Services\ProductService;
use Slim\Http\Request;
use Slim\Http\Response;

class ProductController
{
    /**
     * @var ProductService
     */
    private $ProductService;

    public function __construct(ProductService $ProductService)
    {
        $this->ProductService = $ProductService;
    }

    public function GetProductList(Request $request, Response $response)
    {
        if (!isset($_SESSION['id']))
        {
            return $response->withStatus(400, 'You need to login!');
        }

        $user_id = $_SESSION['id'];

        $products = $this->ProductService->GetProductList($user_id);

        $jsonResponse = [];

        foreach ($products as $product) {
            $jsonResponse[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $product->getPrice(),
                'size' => $product->getSize(),
                'type_name' => $product->getTypeName()
            ];
        }

        return $response->withJson(
            $jsonResponse,
            200
        );
    }

    public function GetProduct(Request $request, Response $response, $args)
    {
        if (!isset($_SESSION['id']))
        {
            return $response->withStatus(400, 'You need to login!');
        }

        $user_id = $_SESSION['id'];
        $product_id = $args['id'];

        if (!isset($product_id) || !preg_match("/^[0-9]{1,100}$/", $product_id))
        {
            return $response->withStatus(400, 'Wrong product id!!!');
        }

        try {
            $product = $this->ProductService->GetProduct($user_id, $product_id);
            $jsonResponse = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $product->getPrice(),
                'size' => $product->getSize(),
                'type_name' => $product->getTypeName()
            ];
            return $response->withJson($jsonResponse, 200);
        } catch (\Exception $e) {
            return $response->withStatus(400, $e->getMessage());
        }
    }

    public function CreateProduct(Request $request, Response $response)
    {
        if (!isset($_SESSION['id']))
        {
            return $response->withStatus(400, 'You need to login!');
        }

        $bodyParams = $request->getParsedBody();

        $user_id = $_SESSION['id'];
        $name = $bodyParams['name'];
        $price = doubleval($bodyParams['price']);
        $size = doubleval($bodyParams['size']);
        $type_name = $bodyParams['type_name'];

        if (!isset($name) || !preg_match("/^[a-zA-Z0-9]{3,30}$/", $name))
        {
            return $response->withStatus(400, 'Wrong product name!');
        }
        if (!isset($price) || (gettype($price) != 'double') || $price == 0)
        {
            return $response->withStatus(400, 'Wrong product price!');
        }
        if (!isset($size) || (gettype($size) != 'double') || $size == 0)
        {
            return $response->withStatus(400, 'Wrong product size!');
        }
        if (!isset($type_name) || !preg_match("/^[a-zA-Z0-9]{1,30}$/", $type_name))
        {
            return $response->withStatus(400, 'Wrong product type!');
        }

        try {
            $product_id = $this->ProductService->CreateProduct($user_id, $name, $price, $size, $type_name);
            return $response->withJson($product_id, 200);
        } catch (\Exception $e) {
            return $response->withStatus(400, $e->getMessage());
        }
    }

    public function UpdateProduct(Request $request, Response $response, $args)
    {
        if (!isset($_SESSION['id']))
        {
            return $response->withStatus(400, 'You need to login!');
        }

        $bodyParams = $request->getParsedBody();

        $user_id = $_SESSION['id'];
        $product_id = $args['id'];
        $name = $bodyParams['name'];
        $price = $bodyParams['price'];
        $size = $bodyParams['size'];
        $type_name = $bodyParams['type_name'];

        if (!isset($product_id) || !preg_match("/^[0-9]{1,100}$/", $product_id))
        {
            return $response->withStatus(400, 'Wrong product id!!!');
        }
        if (!isset($name) || !preg_match("/^[a-zA-Z0-9]{3,30}$/", $name))
        {
            return $response->withStatus(400, 'Wrong product name!');
        }
        if (!isset($price) || (gettype($price) != 'double') || $price == 0)
        {
            return $response->withStatus(400, 'Wrong product price!');
        }
        if (!isset($size) || (gettype($size) != 'double') || $size == 0)
        {
            return $response->withStatus(400, 'Wrong product size!');
        }
        if (!isset($type_name) || !preg_match("/^[a-zA-Z0-9]{1,30}$/", $type_name))
        {
            return $response->withStatus(400, 'Wrong product type!');
        }

        try {
            $result = $this->ProductService->UpdateProduct($user_id, $product_id, $name, $price, $size, $type_name);
            return $response->withStatus(200, $result);
        } catch (\Exception $e) {
            return $response->withStatus(400, $e->getMessage());
        }
    }

    public function GetLogs(Request $request, Response $response, $args)
    {
        if (!isset($_SESSION['id']))
        {
            return $response->withStatus(400, 'You need to login!');
        }

        $user_id = $_SESSION['id'];
        $product_id = $args['id'];

        if (!isset($product_id) || !preg_match("/^[0-9]{1,100}$/", $product_id))
        {
            return $response->withStatus(400, 'Wrong product id!!!');
        }

        try{
            $data = $this->ProductService->GetLogs($user_id, $product_id);
            return $response->withJson( $data,200);
        } catch (\Exception $e){
            return $response->withStatus(400, $e->getMessage());
        }
    }

    public function DeleteProduct(Request $request, Response $response, $args)
    {
        if (!isset($_SESSION['id']))
        {
            return $response->withStatus(400, 'You need to login!');
        }

        $user_id = $_SESSION['id'];
        $product_id = $args['id'];

        if (!isset($product_id) || !preg_match("/^[0-9]{1,100}$/", $product_id))
        {
            return $response->withStatus(400, 'Wrong product id!!!');
        }

        try{
            $this->ProductService->DeleteProduct($user_id, $product_id);
            return $response->withStatus( 200, 'Done!');
        } catch (\Exception $e){
            return $response->withStatus(400, $e->getMessage());
        }
    }

    public function GetAvailableInfo(Request $request, Response $response, $args)
    {
        if (!isset($_SESSION['id']))
        {
            return $response->withStatus(400, 'You need to login!');
        }

        $user_id = $_SESSION['id'];
        $product_id = $args['id'];
        $date = $request->getParam('date');

        if (!isset($product_id) || !preg_match("/^[0-9]{1,100}$/", $product_id))
        {
            return $response->withStatus(400, 'Wrong product id!!!');
        }
        if (isset($date) && !strtotime($date))
        {
            return $response->withStatus(400, 'Wrong date!');
        }

        if (isset($date)){
            try{
                $jsonResponse = $this->ProductService->GetAvailableOnDate($user_id, $product_id, $date);
                return $response->withJson(
                    $jsonResponse,
                    200
                );
            } catch (\Exception $e){
                return $response->withStatus(400, $e->getMessage());
            }
        } else {
            try {
                $jsonResponse = $this->ProductService->GetAvailableInfo($user_id, $product_id);
                return $response->withJson(
                    $jsonResponse,
                    200
                );
            } catch (\Exception $e) {
                return $response->withStatus(400, $e->getMessage());
            }
        }
    }
}