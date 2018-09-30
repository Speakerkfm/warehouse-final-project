<?php

use Doctrine\DBAL\DriverManager;
use Psr\Container\ContainerInterface;
use App\Services\UserService;
use App\Controller\UserController;
use App\Services\WarehouseService;
use App\Controller\WarehouseController;
use App\Services\ProductService;
use App\Controller\ProductController;
use App\Repository\WarehouseRepository;
use App\Repository\UserRepository;
use App\Repository\ProductRepository;
use App\Repository\TransactionRepository;
use App\Repository\ProductsOnWarehouseRepository;

$container = $app->getContainer();

$container['db'] = function () {
    return DriverManager::getConnection([
        'driver' => 'pdo_mysql',
        'host' => '192.168.100.123',
        'dbname' => 'warehouse-test',
        'user' => 'root',
        'password' => 'root',
        'charset' => 'utf8',
    ]);
};


$container['user.controller'] = function ($c) {
    /** @var ContainerInterface $c*/
    return new UserController($c->get('user.service'));
};

$container['warehouse.controller'] = function ($c) {
    /** @var ContainerInterface $c*/
    return new WarehouseController($c->get('warehouse.service'));
};


$container['product.controller'] = function ($c) {
    /** @var ContainerInterface $c*/
    return new ProductController($c->get('product.service'));
};

$container['user.service'] = function ($c) {
    /** @var ContainerInterface $c */
    return new UserService($c->get('user.repository'));
};

$container['warehouse.service'] = function ($c) {
    /** @var ContainerInterface $c*/
    return new WarehouseService(
        $c->get('warehouse.repository'),
        $c->get('product.repository'),
        $c->get('transaction.repository'),
        $c->get('productsOnWarehouse.repository')
    );
};

$container['product.service'] = function ($c) {
    /** @var ContainerInterface $c*/
    return new ProductService(
        $c->get('product.repository'),
        $c->get('transaction.repository'),
        $c->get('productsOnWarehouse.repository'),
        $c->get('warehouse.repository')
        );
};

$container['warehouse.repository'] = function ($c) {
    /** @var ContainerInterface $c */
    return new WarehouseRepository($c->get('db'));
};

$container['user.repository'] = function ($c) {
    /** @var ContainerInterface $c */
    return new UserRepository($c->get('db'));
};

$container['product.repository'] = function ($c) {
    /** @var ContainerInterface $c */
    return new ProductRepository($c->get('db'));
};

$container['productsOnWarehouse.repository'] = function ($c) {
    /** @var ContainerInterface $c */
    return new ProductsOnWarehouseRepository($c->get('db'));
};

$container['transaction.repository'] = function ($c) {
    /** @var ContainerInterface $c */
    return new TransactionRepository($c->get('db'));
};


$app->post('/register', 'user.controller:RegisterUser');
$app->post('/login', 'user.controller:LoginUser');
$app->group('/profile', function () use ($app) {
        $app->get('/info', 'user.controller:UserInfo');
        $app->get('/warehouses', 'warehouse.controller:GetWarehouseList');
        $app->group('/warehouses', function () use ($app) {
            $app->post('/create', 'warehouse.controller:CreateWarehouse');
            $app->post('/move', 'warehouse.controller:MoveProducts');
            $app->get('/{id}', 'warehouse.controller:GetWarehouse');
            $app->group('/{id}', function () use ($app) {
                $app->post('/update', 'warehouse.controller:UpdateWarehouse');
                $app->get('/logs', 'warehouse.controller:GetLogs');
                $app->get('/delete', 'warehouse.controller:DeleteWarehouse');
                $app->get('/products', 'warehouse.controller:GetProductsList');
            });
        });
        $app->get('/products', 'product.controller:GetProductList');
        $app->group('/products', function () use ($app) {
            $app->post('/create', 'product.controller:CreateProduct');
            $app->get('/{id}', 'product.controller:GetProduct');
            $app->group('/{id}', function () use ($app) {
                $app->post('/update', 'product.controller:UpdateProduct');
                $app->get('/logs', 'product.controller:GetLogs');
                $app->get('/delete', 'product.controller:DeleteProduct');
                $app->get('/available', 'product.controller:GetAvailableInfo');
            });
        });
        $app->get('/delete', 'user.controller:DeleteUser');
        $app->get('/logout', 'user.controller:LogoutUser');
        $app->post('/update', 'user.controller:UpdateUser');
});
