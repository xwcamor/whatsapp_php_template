<?php
session_start();
require_once '../config/database.php';
require_once '../models/Chat.php';
require_once '../models/Usuario.php';
require_once '../models/Amigo.php';

class ChatController {
    private $db;
    private $chat;
    private $usuario;
    private $amigo;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->chat = new Chat($this->db);
        $this->usuario = new Usuario($this->db);
        $this->amigo = new Amigo($this->db);
    }

    private function sendJsonResponse($success, $message = '', $data = [], $redirect = null) {
        header('Content-Type: application/json');
        
        // Asegurar que no haya salida previa
        if (ob_get_length()) ob_clean();
        
        $response = [
            'success' => $success,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if (!empty($data)) {
            $response = array_merge($response, $data);
        }
        
        if ($redirect !== null) {
            $response['redirect'] = $redirect;
        }
        
        $json = json_encode($response);
        if ($json === false) {
            error_log("Error al codificar JSON: " . json_last_error_msg());
            $json = json_encode([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => 'Error al codificar respuesta JSON'
            ]);
        }
        
        echo $json;
        exit();
    }

    private function verificarSesion() {
        error_log("Verificando sesión. SESSION: " . print_r($_SESSION, true));
        if (!isset($_SESSION['usuario_id'])) {
            error_log("Sesión no iniciada - redirigiendo a login");
            $this->sendJsonResponse(false, 'Sesión no iniciada', [], '/whatsapp/views/login.php');
        }
        error_log("Sesión válida - usuario_id: " . $_SESSION['usuario_id']);
    }

    public function getChats() {
        try {
            $this->verificarSesion();
            
            $chats = $this->chat->obtenerChatsUsuario($_SESSION['usuario_id']);
            if ($chats === false) {
                $this->sendJsonResponse(false, 'Error al obtener los chats');
            }

            $this->sendJsonResponse(true, '', ['chats' => $chats]);
        } catch (Exception $e) {
            error_log("Error en getChats: " . $e->getMessage());
            $this->sendJsonResponse(false, 'Error al procesar la solicitud: ' . $e->getMessage());
        }
    }

    public function getMessages() {
        try {
            $this->verificarSesion();

            if (!isset($_GET['chat_id'])) {
                error_log("ID de amigo no especificado");
                $this->sendJsonResponse(false, 'ID de amigo no especificado');
                return;
            }

            $amigo_id = intval($_GET['chat_id']);
            if ($amigo_id <= 0) {
                error_log("ID de amigo inválido: $amigo_id");
                $this->sendJsonResponse(false, 'ID de amigo inválido');
                return;
            }

            $last_id = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;
            if ($last_id < 0) {
                error_log("ID de último mensaje inválido: $last_id");
                $this->sendJsonResponse(false, 'ID de último mensaje inválido');
                return;
            }

            error_log("Obteniendo mensajes para amigo_id: $amigo_id, last_id: $last_id");

            $mensajes = $this->chat->obtenerMensajes($amigo_id, $last_id);
            if ($mensajes === false) {
                error_log("Error al obtener mensajes con amigo $amigo_id");
                $this->sendJsonResponse(false, 'Error al obtener los mensajes');
                return;
            }

            // Si no hay mensajes nuevos, devolver un array vacío
            if (empty($mensajes)) {
                $mensajes = [];
                error_log("No hay mensajes nuevos con el amigo $amigo_id");
            } else {
                error_log("Se encontraron " . count($mensajes) . " mensajes nuevos");
            }

            $this->sendJsonResponse(true, '', ['mensajes' => $mensajes]);
        } catch (Exception $e) {
            error_log("Error en getMessages: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $this->sendJsonResponse(false, 'Error al procesar la solicitud: ' . $e->getMessage());
        }
    }

    public function sendMessage() {
        try {
            error_log("=== Iniciando sendMessage en ChatController ===");
            error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
            error_log("GET data: " . print_r($_GET, true));
            error_log("POST data: " . print_r($_POST, true));
            error_log("SESSION data: " . print_r($_SESSION, true));
            
            $this->verificarSesion();

            if (!isset($_POST['chat_id']) || !isset($_POST['mensaje']) || empty(trim($_POST['mensaje']))) {
                error_log("Datos incompletos al enviar mensaje. POST: " . print_r($_POST, true));
                $this->sendJsonResponse(false, 'Datos incompletos');
                return;
            }

            $receptor_id = intval($_POST['chat_id']);
            if ($receptor_id <= 0) {
                error_log("ID de receptor inválido: $receptor_id");
                $this->sendJsonResponse(false, 'ID de receptor inválido');
                return;
            }

            $mensaje = trim($_POST['mensaje']);
            $emisor_id = $_SESSION['usuario_id'];

            error_log("Intentando enviar mensaje. Emisor: $emisor_id, Receptor: $receptor_id, Mensaje: $mensaje");

            if ($this->chat->enviarMensaje($receptor_id, $emisor_id, $mensaje)) {
                error_log("Mensaje enviado correctamente");
                $this->sendJsonResponse(true, 'Mensaje enviado correctamente');
            } else {
                error_log("Error al enviar mensaje desde el modelo Chat");
                $this->sendJsonResponse(false, 'Error al enviar el mensaje');
            }
        } catch (Exception $e) {
            error_log("Error en sendMessage: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $this->sendJsonResponse(false, 'Error al procesar la solicitud: ' . $e->getMessage());
        }
    }

    public function responderSolicitud() {
        try {
            $this->verificarSesion();

            if (!isset($_POST['solicitud_id']) || !isset($_POST['respuesta'])) {
                $this->sendJsonResponse(false, 'Datos incompletos');
            }

            $solicitud_id = $_POST['solicitud_id'];
            $respuesta = $_POST['respuesta'] === 'aceptar' ? 'aceptado' : 'rechazado';
            
            if ($this->amigo->responderSolicitud($solicitud_id, $respuesta, $_SESSION['usuario_id'])) {
                $mensaje = $respuesta === 'aceptado' ? 
                    '¡Solicitud aceptada! Ahora pueden chatear.' : 
                    'Has rechazado la solicitud de amistad.';
                
                if ($respuesta === 'aceptado') {
                    $solicitud = $this->amigo->obtenerDatosSolicitud($solicitud_id);
                    if ($solicitud) {
                        $this->chat->crearChat($solicitud['usuario_id'], $solicitud['amigo_id']);
                    }
                }
                
                $this->sendJsonResponse(true, $mensaje);
            } else {
                $this->sendJsonResponse(false, 'No se pudo procesar la solicitud');
            }
        } catch (Exception $e) {
            $this->sendJsonResponse(false, 'Error al procesar la solicitud: ' . $e->getMessage());
        }
    }

    public function editarMensaje() {
        try {
            if (!isset($_SESSION['usuario_id'])) {
                $this->sendJsonResponse(false, 'No hay sesión activa');
            }

            if (!isset($_POST['mensaje_id']) || !isset($_POST['nuevo_mensaje'])) {
                $this->sendJsonResponse(false, 'Datos incompletos');
            }

            $mensaje_id = $_POST['mensaje_id'];
            $nuevo_mensaje = trim($_POST['nuevo_mensaje']);
            $usuario_id = $_SESSION['usuario_id'];

            // Verificar que el mensaje exista y pertenezca al usuario
            $stmt = $this->db->prepare("SELECT mensaje FROM mensajes WHERE id = ? AND emisor_id = ?");
            $stmt->execute([$mensaje_id, $usuario_id]);
            $mensaje = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$mensaje) {
                $this->sendJsonResponse(false, 'No tienes permiso para editar este mensaje');
            }

            // Actualizar el mensaje
            $stmt = $this->db->prepare("UPDATE mensajes SET mensaje = ?, mensaje_original = ?, editado = TRUE WHERE id = ?");
            if ($stmt->execute([$nuevo_mensaje, $mensaje['mensaje'], $mensaje_id])) {
                $this->sendJsonResponse(true, 'Mensaje editado correctamente');
            } else {
                $this->sendJsonResponse(false, 'Error al editar el mensaje');
            }
        } catch (Exception $e) {
            $this->sendJsonResponse(false, 'Error: ' . $e->getMessage());
        }
    }

    public function eliminarMensaje() {
        try {
            if (!isset($_SESSION['usuario_id'])) {
                $this->sendJsonResponse(false, 'No hay sesión activa');
                return;
            }

            if (!isset($_POST['mensaje_id'])) {
                $this->sendJsonResponse(false, 'ID de mensaje no proporcionado');
                return;
            }

            $mensaje_id = $_POST['mensaje_id'];
            $usuario_id = $_SESSION['usuario_id'];

            // Verificar que el mensaje exista y pertenezca al usuario
            $stmt = $this->db->prepare("SELECT id FROM mensajes WHERE id = ? AND emisor_id = ?");
            $stmt->execute([$mensaje_id, $usuario_id]);
            
            if (!$stmt->fetch()) {
                $this->sendJsonResponse(false, 'No tienes permiso para eliminar este mensaje');
                return;
            }

            // Marcar el mensaje como eliminado
            $stmt = $this->db->prepare("UPDATE mensajes SET eliminado = TRUE WHERE id = ?");
            if ($stmt->execute([$mensaje_id])) {
                $this->sendJsonResponse(true, 'Mensaje eliminado correctamente');
            } else {
                $this->sendJsonResponse(false, 'Error al eliminar el mensaje');
            }
        } catch (Exception $e) {
            error_log("Error en eliminarMensaje: " . $e->getMessage());
            $this->sendJsonResponse(false, 'Error: ' . $e->getMessage());
        }
    }
}

// Manejo de las acciones
error_log("=== Iniciando procesamiento de acción ===");
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("GET data: " . print_r($_GET, true));
error_log("POST data: " . print_r($_POST, true));

try {
    if (!isset($_GET['action'])) {
        error_log("No se especificó ninguna acción");
        (new ChatController())->sendJsonResponse(false, 'No se especificó ninguna acción');
        exit();
    }

    $controller = new ChatController();
    $action = $_GET['action'];
    error_log("Acción solicitada: " . $action);

    switch($action) {
        case 'getChats':
            $controller->getChats();
            break;
        case 'getMessages':
            $controller->getMessages();
            break;
        case 'sendMessage':
            $controller->sendMessage();
            break;
        case 'responderSolicitud':
            $controller->responderSolicitud();
            break;
        case 'editarMensaje':
            $controller->editarMensaje();
            break;
        case 'eliminarMensaje':
            $controller->eliminarMensaje();
            break;
        default:
            error_log("Acción no reconocida: " . $action);
            $controller->sendJsonResponse(false, 'Acción no reconocida');
    }
} catch (Exception $e) {
    error_log("Error en el manejo de la acción: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    (new ChatController())->sendJsonResponse(false, 'Error interno del servidor: ' . $e->getMessage());
}
?> 