<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

define('BASE_PATH', __DIR__);

require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/app/controller/Controller.php';

/*
|--------------------------------------------------------------------------
| ROUTER MODERN
|--------------------------------------------------------------------------
*/

$route = $_GET['r'] ?? 'public/home';
$route = trim($route, '/');

[$controllerPart, $method] = array_pad(explode('/', $route), 2, null);

$controllerPart = $controllerPart ?: 'public';
$method = $method ?: 'home';

$controllerClass = ucfirst($controllerPart) . 'Controller';
$controllerFile  = BASE_PATH . '/app/controller/' . $controllerClass . '.php';

if (!file_exists($controllerFile)) {
    http_response_code(404);
    exit("Controller tidak ditemukan: " . htmlspecialchars($controllerClass));
}

require_once $controllerFile;

if (!class_exists($controllerClass)) {
    http_response_code(500);
    exit("Class controller tidak ditemukan: " . htmlspecialchars($controllerClass));
}

$controller = new $controllerClass();

if (!method_exists($controller, $method)) {
    http_response_code(404);
    exit("Method tidak ditemukan: " . htmlspecialchars("$controllerClass::$method()"));
}

call_user_func([$controller, $method]);