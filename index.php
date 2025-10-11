<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
date_default_timezone_set('America/Maceio');

// Se não instalado, redireciona para instalação
if (!file_exists(__DIR__ . '/config/.env.php') && strpos($_SERVER['REQUEST_URI'], '/install') === false) {
    header('Location: /install');
    exit;
}

/* =========
   Router
   ========= */
// Resolve rota da REQUEST_URI (funciona em subpastas)
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$scriptName = dirname($_SERVER['SCRIPT_NAME']);
if ($scriptName !== '/' && strpos($requestUri, $scriptName) === 0) {
    $requestUri = substr($requestUri, strlen($scriptName));
}
$cleanUri  = trim($requestUri, '/');
$url       = $cleanUri !== '' ? $cleanUri : 'dashboard';
$urlParts  = explode('/', $url);

// Controller e método
$controller = ucfirst($urlParts[0]) . 'Controller';   // ex.: /goals -> GoalsController

// normaliza método: "do-login" -> "doLogin"
$method = $urlParts[1] ?? 'index';
$method = preg_replace_callback('/-([a-z])/', function ($m) {
    return strtoupper($m[1]);
}, $method);

$param  = $urlParts[2] ?? null;

// Carrega helpers
require_once __DIR__ . '/app/Helpers/Auth.php';
require_once __DIR__ . '/app/Helpers/Utils.php';

// Verifica login (exceto auth, install, assets, uploads)
$currentRoute = strtolower($urlParts[0] ?? '');
if (!in_array($currentRoute, ['auth', 'install', 'assets', 'uploads', 'diagnostic_v3'])) {
    Auth::check();
}

// Conexão banco
require_once __DIR__ . '/config/database.php';

/* ==============================
   Localiza e carrega Controller
   ============================== */
$controllerFile = __DIR__ . '/app/Controllers/' . $controller . '.php';
if (!file_exists($controllerFile)) {
    http_response_code(404);
    echo '<h1>Página não encontrada</h1><p>Controller: ' . htmlspecialchars($controllerFile) . '</p>';
    exit;
}

require_once $controllerFile;

if (!class_exists($controller)) {
    http_response_code(500);
    echo '<h1>Erro</h1><p>Classe ' . htmlspecialchars($controller) . ' não encontrada.</p>';
    exit;
}

$ctrl = new $controller($pdo);

// Se método não existir, tenta fallback comum (ex.: do-login -> doLogin)
if (!method_exists($ctrl, $method)) {
    http_response_code(404);
    echo '<h1>Método não encontrado</h1><p>' . htmlspecialchars($method) . ' em ' . htmlspecialchars($controller) . '</p>';
    exit;
}

try {
    echo $param ? $ctrl->$method($param) : $ctrl->$method();
} catch (Throwable $e) {
    http_response_code(500);
    echo '<h1>Erro interno</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>';
}
