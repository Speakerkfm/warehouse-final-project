<?php

use Doctrine\DBAL\DriverManager;
use Psr\Container\ContainerInterface;
use App\Services\UserService;
use App\Controller\UserController;
use App\Services\WarehouseService;
use App\Controller\WarehouseController;
use App\Services\ProductService;
use App\Controller\ProductController;

$container = $app->getContainer();

$container['db'] = function () {
    return DriverManager::getConnection([
        'driver' => 'pdo_mysql',
        'host' => '192.168.100.123',
        'dbname' => 'warehouse',
        'user' => 'root',
        'password' => 'root',
        'charset' => 'utf8',
    ]);
};


$container['users.controller'] = function ($c) {
    /** @var ContainerInterface $c*/
    return new UserController($c->get('users.service'));
};

$container['users.service'] = function ($c) {
    /** @var ContainerInterface $c */
    return new UserService($c->get('db'));
};

$container['warehouses.controller'] = function ($c) {
    /** @var ContainerInterface $c*/
    return new WarehouseController($c->get('warehouses.service'));
};

$container['warehouses.service'] = function ($c) {
    /** @var ContainerInterface $c*/
    return new WarehouseService($c->get('db'));
};

$container['products.controller'] = function ($c) {
    /** @var ContainerInterface $c*/
    return new ProductController($c->get('products.service'));
};

$container['products.service'] = function ($c) {
    /** @var ContainerInterface $c*/
    return new ProductService($c->get('db'));
};


$app->post('/register', 'users.controller:RegisterUser');
$app->post('/login', 'users.controller:LoginUser');
$app->group('/profile', function () use ($app) {
        $app->get('/info', 'users.controller:UserInfo');
        $app->get('/warehouses', 'warehouses.controller:GetWarehouseList');
        $app->group('/warehouses', function () use ($app) {
            $app->post('/create', 'warehouses.controller:CreateWarehouse');
            $app->post('/move', 'warehouses.controller:MoveProducts');
            $app->get('/{id}', 'warehouses.controller:GetWarehouse');
            $app->group('/{id}', function () use ($app) {
                $app->post('/update', 'warehouses.controller:UpdateWarehouse');
                $app->get('/logs', 'warehouses.controller:GetLogs');
                $app->get('/delete', 'warehouses.controller:DeleteWarehouse');
                $app->get('/products', 'warehouses.controller:GetProductsList');
            });
        });
        $app->get('/products', 'products.controller:GetProductList');
        $app->group('/products', function () use ($app) {
            $app->post('/create', 'products.controller:CreateProduct');
            $app->get('/{id}', 'products.controller:GetProduct');
            $app->group('/{id}', function () use ($app) {
                $app->post('/update', 'products.controller:UpdateProduct');
                $app->get('/logs', 'products.controller:GetLogs');
                $app->get('/delete', 'products.controller:DeleteProduct');
                $app->get('/available', 'products.controller:GetAvailableInfo');
            });
        });
        $app->get('/delete', 'users.controller:DeleteUser');
        $app->get('/logout', 'users.controller:LogoutUser');
        $app->post('/update', 'users.controller:UpdateUser');
});
