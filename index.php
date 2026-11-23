<?php
declare(strict_types=1);
// Front controller: every request goes through here (index.php?r=controller/action)
require __DIR__ . '/../app/bootstrap.php';

$routes = [
    'home/index'            => ['HomeController', 'index'],
    'menu/index'            => ['MenuController', 'index'],
    'cart/index'            => ['CartController', 'index'],
    'cart/add'              => ['CartController', 'add'],
    'cart/update'           => ['CartController', 'update'],
    'checkout/index'        => ['CheckoutController', 'index'],
    'order/index'           => ['OrderController', 'index'],
    'order/show'            => ['OrderController', 'show'],
    'auth/login'            => ['AuthController', 'login'],
    'auth/register'         => ['AuthController', 'register'],
    'auth/logout'           => ['AuthController', 'logout'],
    'api/menu'              => ['ApiController', 'menu'],
    'api/cart_add'          => ['ApiController', 'cart_add'],
    'admin/dashboard'       => ['AdminController', 'dashboard'],
    'admin_menu/index'      => ['AdminMenuController', 'index'],
    'admin_menu/form'       => ['AdminMenuController', 'form'],
    'admin_menu/save'       => ['AdminMenuController', 'save'],
    'admin_menu/delete'     => ['AdminMenuController', 'delete'],
    'admin_category/index'  => ['AdminCategoryController', 'index'],
    'admin_category/form'   => ['AdminCategoryController', 'form'],
    'admin_category/save'   => ['AdminCategoryController', 'save'],
    'admin_category/delete' => ['AdminCategoryController', 'delete'],
    'admin_order/index'     => ['AdminOrderController', 'index'],
    'admin_order/show'      => ['AdminOrderController', 'show'],
    'admin_order/status'    => ['AdminOrderController', 'status'],
    'admin_order/delete'    => ['AdminOrderController', 'delete'],
];

$route = is_string($_GET['r'] ?? null) ? $_GET['r'] : 'home/index';
if (!isset($routes[$route])) {
    (new HomeController())->notFound();
    exit;
}
[$class, $method] = $routes[$route];
(new $class())->$method();
