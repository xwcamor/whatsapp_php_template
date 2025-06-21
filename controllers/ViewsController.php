<?php
class ViewsController {
    public function __construct() {
        // Verificar si el usuario está autenticado para vistas protegidas
        if ($this->requiresAuth() && !isset($_SESSION['usuario_id'])) {
            header('Location: /whatsapp/views/login.php');
            exit();
        }
    }

    private function requiresAuth() {
        // Lista de vistas que requieren autenticación
        $authViews = ['chat', 'amigos'];
        
        // Obtener la vista actual
        $currentView = isset($_GET['view']) ? $_GET['view'] : '';
        
        return in_array($currentView, $authViews);
    }

    public function render() {
        $view = isset($_GET['view']) ? $_GET['view'] : 'login';
        $viewFile = BASE_PATH . '/views/' . $view . '.php';

        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            require_once BASE_PATH . '/views/404.php';
        }
    }
}
?> 