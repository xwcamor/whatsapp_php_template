<?php
// Mostrar errores durante el desarrollo
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Definir constantes
define('BASE_PATH', __DIR__);
define('BASE_URL', '/whatsapp');

// Función para cargar automáticamente las clases
function autoload($class) {
    $paths = [
        BASE_PATH . '/controllers/',
        BASE_PATH . '/models/',
        BASE_PATH . '/config/'
    ];

    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
}

spl_autoload_register('autoload');

// Si es la página principal y el usuario no está autenticado, redirigir al login
if (empty($_GET['url']) && !isset($_SESSION['usuario_id'])) {
    header('Location: ' . BASE_URL . '/views/login.php');
    exit();
}

// Si es la página principal y el usuario está autenticado, redirigir al chat
if (empty($_GET['url']) && isset($_SESSION['usuario_id'])) {
    header('Location: ' . BASE_URL . '/views/chat.php');
    exit();
}

// Obtener la URL solicitada
$url = isset($_GET['url']) ? $_GET['url'] : '';
$url = rtrim($url, '/');
$url = filter_var($url, FILTER_SANITIZE_URL);
$url = explode('/', $url);

// Determinar el controlador y la acción
$controllerName = !empty($url[0]) ? ucfirst($url[0]) . 'Controller' : 'UsuarioController';
$actionName = isset($url[1]) ? $url[1] : 'index';

// Rutas públicas que no requieren autenticación
$publicRoutes = [
    'UsuarioController' => ['login', 'registrar', 'index']
];

// Verificar autenticación para rutas protegidas
if (!isset($_SESSION['usuario_id']) && 
    (!isset($publicRoutes[$controllerName]) || 
    !in_array($actionName, $publicRoutes[$controllerName]))) {
    header('Location: ' . BASE_URL . '/views/login.php');
    exit();
}

// Manejar la solicitud
try {
    if (file_exists(BASE_PATH . '/controllers/' . $controllerName . '.php')) {
        require_once BASE_PATH . '/controllers/' . $controllerName . '.php';
        $controller = new $controllerName();
        
        if (method_exists($controller, $actionName)) {
            $params = array_slice($url, 2);
            call_user_func_array([$controller, $actionName], $params);
        } else {
            throw new Exception("Método no encontrado: $actionName en $controllerName");
        }
    } else {
        throw new Exception("Controlador no encontrado: $controllerName");
    }
} catch (Exception $e) {
    // Mostrar error durante el desarrollo
    echo "<h1>Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?> 