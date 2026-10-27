<?php
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/core/Controller.php';

$controllerName = isset($_GET['c']) ? ucfirst(strtolower($_GET['c'])) . 'Controller' : 'AuthController';
$actionName     = isset($_GET['a']) ? $_GET['a'] : 'index';

$controllerFile = __DIR__ . "/controllers/{$controllerName}.php";

if (!file_exists($controllerFile)) {
    http_response_code(404);
    die("Không tìm thấy phân hệ: {$controllerName}");
}

require_once $controllerFile;
$controller = new $controllerName();

if (!method_exists($controller, $actionName)) {
    http_response_code(404);
    die("Không tìm thấy chức năng: {$actionName} trong {$controllerName}");
}

$controller->$actionName();
