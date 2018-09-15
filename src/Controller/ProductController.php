<?php

namespace App\Controller;

use App\Services\ProductService;
use App\Model\Product;
use Slim\Http\Request;
use Slim\Http\Response;
use JsonSchema\Constraints\Constraint;

class ProductController extends AbstractController
{
    /**
     * @var ProductService
     */
    private $ProductService;

    /**
     * @param $product Product
     * @return array
     */
    public function JsonProduct($product)
    {
        $jsonResponse = [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'price' => $product->getPrice(),
            'size' => $product->getSize(),
            'type_name' => $product->getTypeName()
        ];

        return $jsonResponse;
    }

    public function __construct(ProductService $ProductService)
    {
        parent::__construct();
        $this->ProductService = $ProductService;
    }

    public function GetProductList(Request $request, Response $response)
    {
        try {
            $this->CheckAccess();

            $products = $this->ProductService->GetProductList($this->GetUserId());

            $jsonResponse = [];

            foreach ($products as $product) {
                $jsonResponse[] = $this->JsonProduct($product);
            }

            return $response->withJson(
                $jsonResponse,
                200
            );
        } catch (\Exception $e) {
            return $response->withStatus(400, $e->getMessage());
        }
    }

    public function GetProduct(Request $request, Response $response, $args)
    {
        try {
            $this->CheckAccess();

            $this->Validation($args, 'IdSchema.json', Constraint::CHECK_MODE_COERCE_TYPES);

            $product_id = $args['id'];

            $product = $this->ProductService->GetProduct($this->GetUserId(), $product_id);

            return $response->withJson($this->JsonProduct($product), 200);
        } catch (\Exception $e) {
            return $response->withStatus(400, $e->getMessage());
        }
    }

    public function CreateProduct(Request $request, Response $response)
    {
        try {
            $this->CheckAccess();

            $bodyParams = $request->getParsedBody();

            $this->Validation($bodyParams, 'ProductSchema.json', Constraint::CHECK_MODE_COERCE_TYPES);

            $name = $bodyParams['name'];
            $price = doubleval($bodyParams['price']);
            $size = doubleval($bodyParams['size']);
            $type_name = $bodyParams['type_name'];

            $product = $this->ProductService->CreateProduct($this->GetUserId(), $name, $price, $size, $type_name);
            return $response->withJson($this->JsonProduct($product), 200);
        } catch (\Exception $e) {
            return $response->withStatus(400, $e->getMessage());
        }
    }

    public function UpdateProduct(Request $request, Response $response, $args)
    {
        try {
            $this->CheckAccess();

            $bodyParams = $request->getParsedBody();

            $this->Validation($args, 'IdSchema.json', Constraint::CHECK_MODE_COERCE_TYPES);
            $this->Validation($bodyParams, 'ProductSchema.json', Constraint::CHECK_MODE_COERCE_TYPES);

            $product_id = $args['id'];
            $name = $bodyParams['name'];
            $price = doubleval($bodyParams['price']);
            $size = doubleval($bodyParams['size']);
            $type_name = $bodyParams['type_name'];

            $product = $this->ProductService->UpdateProduct($this->GetUserId(), $product_id, $name, $price, $size, $type_name);
            return $response->withJson($this->JsonProduct($product),200);
        } catch (\Exception $e) {
            return $response->withStatus(400, $e->getMessage());
        }
    }

    public function GetLogs(Request $request, Response $response, $args)
    {
        try{
            $this->CheckAccess();

            $this->Validation($args, 'IdSchema.json', Constraint::CHECK_MODE_COERCE_TYPES);

            $product_id = $args['id'];

            $JsonResponse = $this->ProductService->GetLogs($this->GetUserId(), $product_id);
            return $response->withJson($JsonResponse,200);
        } catch (\Exception $e){
            return $response->withStatus(400, $e->getMessage());
        }
    }

    public function DeleteProduct(Request $request, Response $response, $args)
    {
        try{
            $this->CheckAccess();

            $this->Validation($args, 'IdSchema.json', Constraint::CHECK_MODE_COERCE_TYPES);

            $product_id = $args['id'];

            $this->ProductService->DeleteProduct($this->GetUserId(), $product_id);
            return $response->withStatus( 200, 'Success!');
        } catch (\Exception $e){
            return $response->withStatus(400, $e->getMessage());
        }
    }

    public function GetAvailableInfo(Request $request, Response $response, $args)
    {
        try {
            $this->CheckAccess();

            $this->Validation($args, 'IdSchema.json', Constraint::CHECK_MODE_COERCE_TYPES);

            $product_id = $args['id'];

            $date = $request->getParam('date');

            if (isset($date) && strtotime($date)) {
                $jsonResponse = $this->ProductService->GetAvailableOnDate($this->GetUserId(), $product_id, $date);
                return $response->withJson(
                    $jsonResponse,
                    200
                );
            } else {
                $jsonResponse = $this->ProductService->GetAvailableInfo($this->GetUserId(), $product_id);
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