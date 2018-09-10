<?php
/**
 * Created by PhpStorm.
 * User: usanin
 * Date: 07.09.2018
 * Time: 17:17
 */

namespace App\Controller;

use App\Services\WarehouseService;
use Slim\Http\Request;
use Slim\Http\Response;

class WarehouseController
{
    /**
     * @var WarehouseService
     */
    private $WarehouseService;

    public function __construct(WarehouseService $WarehouseService)
    {
        $this->WarehouseService = $WarehouseService;
    }

    public function GetWarehouseList(Request $request, Response $response)
    {
        if (!isset($_SESSION['id']))
        {
            return $response->withStatus(400, 'You need to login!');
        }

        $user_id = $_SESSION['id'];

        $warehouses = $this->WarehouseService->GetWarehouseList($user_id);

        $jsonResponse = [];

        foreach ($warehouses as $warehouse) {
            $jsonResponse[] = [
                'id' => $warehouse->getId(),
                'address' => $warehouse->getAddress(),
                'capacity' => $warehouse->getCapacity(),
                'total_size' => $warehouse->getTotalSize(),
                'balance' => $warehouse->getBalance()
            ];
        }

        return $response->withJson(
            $jsonResponse,
            200
        );
    }

    public function GetWarehouse(Request $request, Response $response, $args)
    {
        if (!isset($_SESSION['id']))
        {
            return $response->withStatus(400, 'You need to login!');
        }

        $user_id = $_SESSION['id'];
        $warehouse_id = $args['id'];

        if (!isset($warehouse_id) || !preg_match("/^[0-9]{1,100}$/", $warehouse_id))
        {
            return $response->withStatus(400, 'Wrong warehouse id!');
        }

        try {
            $warehouse = $this->WarehouseService->GetWarehouse($user_id, $warehouse_id);
            $jsonResponse = [
                'id' => $warehouse->getId(),
                'address' => $warehouse->getAddress(),
                'capacity' => $warehouse->getCapacity(),
                'total_size' => $warehouse->getTotalSize(),
                'balance' => $warehouse->getBalance()
            ];
            return $response->withJson($jsonResponse, 200);
        } catch (\Exception $e) {
            return $response->withStatus(400, $e->getMessage());
        }
    }

    public function CreateWarehouse(Request $request, Response $response)
    {
        if (!isset($_SESSION['id']))
        {
            return $response->withStatus(400, 'You need to login!');
        }

        $bodyParams = $request->getParsedBody();

        $user_id = $_SESSION['id'];
        $address = $bodyParams['address'];
        $capacity = doubleval($bodyParams['capacity']);

        if (!isset($address) || !preg_match("/^[a-zA-Z0-9,.]{1,100}$/", $address))
        {
            return $response->withStatus(400, 'Wrong product address!');
        }
        if (!isset($capacity) || (gettype($capacity) != 'double') || $capacity == 0)
        {
            return $response->withStatus(400, 'Wrong product price!');
        }

        try {
            $warehouse_id = $this->WarehouseService->CreateWarehouse($user_id, $address, $capacity);
            return $response->withJson($warehouse_id, 200);
        } catch (\Exception $e) {
            return $response->withStatus(400, $e->getMessage());
        }
    }

    public function UpdateWarehouse(Request $request, Response $response, $args)
    {
        if (!isset($_SESSION['id']))
        {
            return $response->withStatus(400, 'You need to login!');
        }

        $bodyParams = $request->getParsedBody();
        $user_id = $_SESSION['id'];
        $warehouse_id = $args['id'];
        $address = $bodyParams['address'];
        $capacity = $bodyParams['capacity'];

        if (!isset($warehouse_id) || !preg_match("/^[0-9]{1,100}$/", $warehouse_id))
        {
            return $response->withStatus(400, 'Wrong warehouse id!');
        }
        if (!isset($address) || !preg_match("/^[a-zA-Z0-9,.]{1,100}$/", $address))
        {
            return $response->withStatus(400, 'Wrong product address!');
        }
        if (!isset($capacity) || (gettype($capacity) != 'double') || $capacity == 0)
        {
            return $response->withStatus(400, 'Wrong product price!');
        }

        try {
            $result = $this->WarehouseService->UpdateWarehouse($user_id, $warehouse_id, $address, $capacity);
            return $response->withStatus(200, $result);
        } catch (\Exception $e) {
            return $response->withStatus(400, $e->getMessage());
        }
    }

    public function MoveProducts(Request $request, Response $response)
    {
        if (!isset($_SESSION['id']))
        {
            return $response->withStatus(400, 'You need to login!');
        }

        $bodyParams = $request->getParsedBody();
        $user_id = $_SESSION['id'];
        $product_list = json_decode($bodyParams['product_list'], true);
        $movement_type = $bodyParams['movement_type'];
        $warehouses = json_decode($bodyParams['warehouses'], true);

        if (!isset($product_list))
        {
            return $response->withStatus(400, 'Wrong product list!');
        }
        if (!isset($movement_type))
        {
            return $response->withStatus(400, 'Wrong movement type!');
        } else {
            switch ($movement_type){
                case 'app':
                    if (!isset($warehouses['to']) || isset($warehouses['from'])){
                        return $response->withStatus(400, 'Wrong target warehouse!');
                    }
                    break;
                case 'detach':
                    if (isset($warehouses['to']) || !isset($warehouses['from'])){
                        return $response->withStatus(400, 'Wrong target warehouse!');
                    }
                    break;
                case 'move':
                    if (!isset($warehouses['to']) || !isset($warehouses['from'])){
                        return $response->withStatus(400, 'Wrong target warehouses!');
                    }
                    break;
                default:
                    return $response->withStatus(400, 'Wrong movement type!');
                    break;
            }
        }

        try{
            $this->WarehouseService->MoveProducts($user_id, $product_list, $warehouses, $movement_type);
            return $response->withStatus(200);
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
        $warehouse_id = $args['id'];

        if (!isset($warehouse_id) || !preg_match("/^[0-9]{1,100}$/", $warehouse_id))
        {
            return $response->withStatus(400, 'Wrong warehouse id!');
        }

        try{
            $data = $this->WarehouseService->GetLogs($user_id, $warehouse_id);
            return $response->withJson( $data,200);
        } catch (\Exception $e){
            return $response->withStatus(400, $e->getMessage());
        }
    }

    public function DeleteWarehouse(Request $request, Response $response, $args)
    {
        if (!isset($_SESSION['id']))
        {
            return $response->withStatus(400, 'You need to login!');
        }

        $user_id = $_SESSION['id'];
        $warehouse_id = $args['id'];

        if (!isset($warehouse_id) || !preg_match("/^[0-9]{1,100}$/", $warehouse_id))
        {
            return $response->withStatus(400, 'Wrong warehouse id!');
        }

        try{
            $this->WarehouseService->DeleteWarehouse($user_id, $warehouse_id);
            return $response->withStatus( 200, 'Done!');
        } catch (\Exception $e){
            return $response->withStatus(400, $e->getMessage());
        }
    }

    public function GetProductsList(Request $request, Response $response, $args)
    {
        if (!isset($_SESSION['id']))
        {
            return $response->withStatus(400, 'You need to login!');
        }

        $user_id = $_SESSION['id'];
        $warehouse_id = $args['id'];
        $date = $request->getParam('date');

        if (!isset($warehouse_id) || !preg_match("/^[0-9]{1,100}$/", $warehouse_id))
        {
            return $response->withStatus(400, 'Wrong warehouse id!');
        }
        if (isset($date) && !strtotime($date))
        {
            return $response->withStatus(400, 'Wrong date!');
        }

        if (isset($date)){
            try{
                $jsonResponse = $this->WarehouseService->GetProductListOnDate($user_id, $warehouse_id, $date);
                return $response->withJson(
                    $jsonResponse,
                    200
                );
            } catch (\Exception $e){
                return $response->withStatus(400, $e->getMessage());
            }
        } else {
            try {
                $jsonResponse = $this->WarehouseService->GetProductsList($user_id, $warehouse_id);
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