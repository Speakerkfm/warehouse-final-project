<?php
/**
 * Created by PhpStorm.
 * User: usanin
 * Date: 07.09.2018
 * Time: 17:17
 */

namespace App\Controller;

use App\Services\WarehouseService;
use App\Model\Warehouse;
use JsonSchema\Constraints\Constraint;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use Slim\Http\Request;
use Slim\Http\Response;

class WarehouseController extends AbstractController
{
    /**
     * @var WarehouseService
     */
    private $WarehouseService;

    /**
     * @param $warehouse Warehouse
     * @return array
     */
    public function JsonWarehouse($warehouse)
    {
        $jsonResponse = [
            'id' => $warehouse->getId(),
            'address' => $warehouse->getAddress(),
            'capacity' => $warehouse->getCapacity(),
            'total_size' => $warehouse->getTotalSize(),
            'balance' => $warehouse->getBalance()
        ];

        return $jsonResponse;
    }

    public function __construct(WarehouseService $WarehouseService)
    {
        parent::__construct();
        $this->WarehouseService = $WarehouseService;
    }

    public function GetWarehouseList(Request $request, Response $response)
    {
        try {
            $this->CheckAccess();

            $warehouses = $this->WarehouseService->GetWarehouseList($this->GetUserId());

            $jsonResponse = [];

            foreach ($warehouses as $warehouse) {
                $jsonResponse[] = $this->JsonWarehouse($warehouse);
            }

            return $response->withJson(
                $jsonResponse,
                200
            );
        } catch (\Exception $e) {
            return $response->withStatus(400, $e->getMessage());
        }
    }

    public function GetWarehouse(Request $request, Response $response, $args)
    {
        try {
            $this->CheckAccess();

            $this->Validation($args, 'IdSchema.json', Constraint::CHECK_MODE_COERCE_TYPES);

            $warehouse_id = $args['id'];

            $warehouse = $this->WarehouseService->GetWarehouse($this->GetUserId(), $warehouse_id);

            return $response->withJson($this->JsonWarehouse($warehouse), 200);
        } catch (\Exception $e) {
            return $response->withStatus(400, $e->getMessage());
        }
    }

    public function CreateWarehouse(Request $request, Response $response)
    {
        try {
            $this->CheckAccess();

            $bodyParams = $request->getParsedBody();

            $this->Validation($bodyParams, 'WarehouseSchema.json', Constraint::CHECK_MODE_COERCE_TYPES);

            $address = $bodyParams['address'];
            $capacity = doubleval($bodyParams['capacity']);

            $warehouse = $this->WarehouseService->CreateWarehouse($this->GetUserId(), $address, $capacity);

            return $response->withJson($this->JsonWarehouse($warehouse), 200);
        } catch (\Exception $e) {
            return $response->withStatus(400, $e->getMessage());
        }
    }

    public function UpdateWarehouse(Request $request, Response $response, $args)
    {
        try {
            $this->CheckAccess();

            $bodyParams = $request->getParsedBody();

            $this->Validation($args, 'IdSchema.json', Constraint::CHECK_MODE_COERCE_TYPES);
            $this->Validation($bodyParams, 'WarehouseSchema.json', Constraint::CHECK_MODE_COERCE_TYPES);

            $warehouse_id = $args['id'];
            $address = $bodyParams['address'];
            $capacity = doubleval($bodyParams['capacity']);

            $warehouse = $this->WarehouseService->UpdateWarehouse($this->GetUserId(), $warehouse_id, $address, $capacity);
            return $response->withJson($this->JsonWarehouse($warehouse), 200);
        } catch (\Exception $e) {
            return $response->withStatus(400, $e->getMessage());
        }
    }

    public function MoveProducts(Request $request, Response $response)
    {
        try{
            $this->CheckAccess();

            $bodyParams = $request->getParsedBody();

            $bodyParams['product_list'] = json_decode($bodyParams['product_list'], true);
            $bodyParams['warehouses'] = json_decode($bodyParams['warehouses'], true);

            switch ($bodyParams['movement_type']){
                case 'move':
                    $schema = 'MoveProductSchema.json';
                    break;
                case 'app':
                    $schema = 'AppProductSchema.json';
                    break;
                case 'detach':
                    $schema = 'DetachProductSchema.json';
                    break;
                default:
                    return $response->withStatus(400, 'Wrong type!');
            }

            $this->Validation($bodyParams, $schema, Constraint::CHECK_MODE_COERCE_TYPES);

            $product_list = $bodyParams['product_list'];
            $movement_type = $bodyParams['movement_type'];
            $warehouses = $bodyParams['warehouses'];

            $this->WarehouseService->MoveProducts($this->GetUserId(), $product_list, $warehouses, $movement_type);
            return $response->withStatus(200);
        } catch (\Exception $e) {
            return $response->withStatus(400, $e->getMessage());
        }
    }

    public function GetLogs(Request $request, Response $response, $args)
    {
        try{
            $this->CheckAccess();

            $this->Validation($args, 'IdSchema.json', Constraint::CHECK_MODE_COERCE_TYPES);

            $warehouse_id = $args['id'];

            $jsonResult = $this->WarehouseService->GetLogs($this->GetUserId(), $warehouse_id);
            return $response->withJson( $jsonResult,200);
        } catch (\Exception $e){
            return $response->withStatus(400, $e->getMessage());
        }
    }

    public function DeleteWarehouse(Request $request, Response $response, $args)
    {
        try{
            $this->CheckAccess();

            $this->Validation($args, 'IdSchema.json', Constraint::CHECK_MODE_COERCE_TYPES);

            $warehouse_id = $args['id'];

            $this->WarehouseService->DeleteWarehouse($this->GetUserId(), $warehouse_id);
            return $response->withStatus( 200, 'Success!');
        } catch (\Exception $e){
            return $response->withStatus(400, $e->getMessage());
        }
    }

    public function GetProductsList(Request $request, Response $response, $args)
    {
        try {
            $this->CheckAccess();

            $this->Validation($args, 'IdSchema.json', Constraint::CHECK_MODE_COERCE_TYPES);

            $warehouse_id = $args['id'];
            $date = $request->getParam('date');

            if (isset($date) && strtotime($date)) {
                $jsonResponse = $this->WarehouseService->GetProductListOnDate($this->GetUserId(), $warehouse_id, $date);
                return $response->withJson(
                    $jsonResponse,
                    200
                );
            } else {
                $jsonResponse = $this->WarehouseService->GetProductsList($this->GetUserId(), $warehouse_id);
                return $response->withJson(
                    $jsonResponse,
                    200
                );
            }
        } catch (\Exception $e) {
            return $response->withStatus(400, $e->getMessage());
        }
    }
}