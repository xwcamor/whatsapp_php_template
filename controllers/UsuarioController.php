<?php
// Desactivar la visualización de errores
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Definir un manejador de errores personalizado
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $error_message = date('[Y-m-d H:i:s] ') . "Error: [$errno] $errstr - $errfile:$errline\n";
    error_log($error_message, 3, "C:/xampp/htdocs/whatsapp/logs/error.log");
    return true; // No mostrar el error en la salida
}

// Establecer el manejador de errores personalizado
set_error_handler("customErrorHandler");

// Verificar si la sesión ya está iniciada
if (session_status() === PHP_SESSION_NONE) {
    try {
        session_start();
        error_log("Sesión iniciada correctamente", 3, "C:/xampp/htdocs/whatsapp/logs/error.log");
    } catch (Exception $e) {
        error_log("Error al iniciar sesión: " . $e->getMessage(), 3, "C:/xampp/htdocs/whatsapp/logs/error.log");
    }
}

require_once '../config/database.php';
require_once '../models/Usuario.php';
require_once '../models/Amigo.php';

class UsuarioController {
    private $db;
    private $usuario;

    public function __construct() {
        try {
            $database = new Database();
            $this->db = $database->getConnection();
            $this->usuario = new Usuario($this->db);
            error_log("UsuarioController construido correctamente", 3, "C:/xampp/htdocs/whatsapp/logs/error.log");
        } catch (Exception $e) {
            error_log("Error en constructor de UsuarioController: " . $e->getMessage(), 3, "C:/xampp/htdocs/whatsapp/logs/error.log");
            throw $e;
        }
    }

    private function handleError($message) {
        error_log("Error en UsuarioController: " . $message);
        $_SESSION['error'] = $message;
        $base_url = dirname(dirname($_SERVER['PHP_SELF']));
        header("Location: " . $base_url . "/views/login.php");
        exit();
    }

    private function sendJsonResponse($success, $message = '', $data = [], $redirect = null) {
        header('Content-Type: application/json');
        $response = [
            'success' => $success,
            'message' => $message
        ];
        
        if (!empty($data)) {
            $response = array_merge($response, $data);
        }
        
        if ($redirect !== null) {
            $response['redirect'] = $redirect;
        }
        
        echo json_encode($response);
        exit();
    }

    public function registrar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Validar que las contraseñas coincidan
            if ($_POST['password'] !== $_POST['confirm_password']) {
                $_SESSION['error'] = "Las contraseñas no coinciden.";
                header("Location: ../views/registro.php");
                exit();
            }

            // Validar que el email no esté ya registrado
            $usuario_existente = $this->usuario->buscarPorEmail($_POST['email']);
            if ($usuario_existente) {
                $_SESSION['error'] = "Este correo electrónico ya está registrado.";
                header("Location: ../views/registro.php");
                exit();
            }

            // Asignar valores al objeto usuario
            $this->usuario->nombre = $_POST['nombre'];
            $this->usuario->email = $_POST['email'];
            $this->usuario->password = $_POST['password'];

            // Intentar registrar
            if ($this->usuario->registrar()) {
                $_SESSION['mensaje'] = "¡Registro exitoso! Por favor inicia sesión con tu cuenta.";
                header("Location: ../views/login.php");
                exit();
            } else {
                $_SESSION['error'] = "Error en el registro. Por favor intenta nuevamente.";
                header("Location: ../views/registro.php");
                exit();
            }
        } else {
            // Si alguien intenta acceder directamente a la acción registrar
            header("Location: ../views/registro.php");
            exit();
        }
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->usuario->email = $_POST['email'];
            $this->usuario->password = $_POST['password'];

            if ($this->usuario->login()) {
                $_SESSION['usuario_id'] = $this->usuario->id;
                $_SESSION['nombre'] = $this->usuario->nombre;
                header("Location: ../views/chat.php");
                exit();
            } else {
                $_SESSION['error'] = "Email o contraseña incorrectos.";
                header("Location: ../views/login.php");
                exit();
            }
        } else {
            // Si alguien intenta acceder directamente a la acción login
            header("Location: ../views/login.php");
            exit();
        }
    }

    public function agregarAmigo() {
        try {
            if (!isset($_SESSION['usuario_id'])) {
                $this->sendJsonResponse(false, 'Sesión no iniciada');
            }

            if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST['email'])) {
                $this->sendJsonResponse(false, 'Datos incompletos');
            }

            $email_amigo = $_POST['email'];
            
            if ($email_amigo === $_SESSION['email']) {
                $this->sendJsonResponse(false, 'No puedes agregarte a ti mismo como amigo');
            }
            
            $amigo = $this->usuario->buscarPorEmail($email_amigo);
            
            if ($amigo) {
                $this->usuario->id = $_SESSION['usuario_id'];
                if ($this->usuario->agregarAmigo($amigo['id'])) {
                    $this->sendJsonResponse(true, '¡Solicitud de amistad enviada exitosamente!');
                } else {
                    $this->sendJsonResponse(false, 'Ya existe una solicitud de amistad pendiente con este usuario');
                }
            } else {
                $this->sendJsonResponse(false, 'No se encontró ningún usuario con ese correo electrónico');
            }
        } catch (Exception $e) {
            $this->sendJsonResponse(false, 'Error al procesar la solicitud: ' . $e->getMessage());
        }
    }

    public function obtenerSolicitudes() {
        try {
            if (!isset($_SESSION['usuario_id'])) {
                $this->sendJsonResponse(false, 'Sesión no iniciada');
            }

            $amigo = new Amigo($this->db);
            $solicitudes = $amigo->obtenerSolicitudes($_SESSION['usuario_id']);

            if ($solicitudes === false) {
                $this->sendJsonResponse(false, 'Error al obtener las solicitudes');
            }

            $this->sendJsonResponse(true, '', ['solicitudes' => $solicitudes]);
        } catch (Exception $e) {
            $this->sendJsonResponse(false, 'Error al procesar la solicitud: ' . $e->getMessage());
        }
    }

    public function responderSolicitud() {
        try {
            if (!isset($_SESSION['usuario_id'])) {
                $this->sendJsonResponse(false, 'Sesión no iniciada');
            }

            if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST['solicitud_id']) || !isset($_POST['respuesta'])) {
                $this->sendJsonResponse(false, 'Datos incompletos');
            }

            $amigo = new Amigo($this->db);
            $solicitud_id = $_POST['solicitud_id'];
            $respuesta = $_POST['respuesta'] === 'aceptar' ? 'aceptado' : 'rechazado';
            
            if ($amigo->responderSolicitud($solicitud_id, $respuesta, $_SESSION['usuario_id'])) {
                $mensaje = $respuesta === 'aceptado' ? 
                    '¡Solicitud aceptada! Ahora pueden chatear.' : 
                    'Has rechazado la solicitud de amistad.';
                
                if ($respuesta === 'aceptado') {
                    $solicitud = $amigo->obtenerDatosSolicitud($solicitud_id);
                    if ($solicitud) {
                        $chat = new Chat($this->db);
                        $chat->crearChat($solicitud['usuario_id'], $solicitud['amigo_id']);
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

    public function cerrarSesion() {
        try {
            // Destruir todas las variables de sesión
            $_SESSION = array();

            // Si se está usando una cookie de sesión, destruirla
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time() - 3600, '/');
            }

            // Destruir la sesión
            session_destroy();

            // Enviar respuesta JSON con redirección
            $this->sendJsonResponse(true, 'Sesión cerrada correctamente', [], '../views/login.php');
        } catch (Exception $e) {
            error_log("Error en cerrarSesion: " . $e->getMessage());
            $this->sendJsonResponse(false, 'Error al cerrar sesión', [], '../views/login.php');
        }
    }

    public function actualizarPerfil() {
        try {
            if (!isset($_SESSION['usuario_id'])) {
                throw new Exception('No hay sesión activa');
            }

            $usuario_id = $_SESSION['usuario_id'];
            
            // Usar htmlspecialchars en lugar de FILTER_SANITIZE_STRING
            $nombre = htmlspecialchars(trim($_POST['nombre'] ?? ''), ENT_QUOTES, 'UTF-8');
            $descripcion = htmlspecialchars(trim($_POST['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8');
            $password = trim($_POST['password'] ?? '');
            $confirm_password = trim($_POST['confirm_password'] ?? '');

            // Validar campos obligatorios
            if (empty($nombre)) {
                throw new Exception('El nombre es obligatorio');
            }

            // Iniciar transacción
            $this->db->beginTransaction();

            // Construir la consulta SQL base
            $sql = "UPDATE usuarios SET nombre = ?, descripcion = ?";
            $params = [$nombre, $descripcion];

            // Manejar la subida de imagen si existe
            if (isset($_FILES['imagen_perfil']) && $_FILES['imagen_perfil']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['imagen_perfil'];
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                
                if (!in_array($file['type'], $allowedTypes)) {
                    throw new Exception('Tipo de archivo no permitido. Solo se permiten imágenes JPG, PNG y GIF.');
                }

                if ($file['size'] > 5 * 1024 * 1024) { // 5MB
                    throw new Exception('La imagen es demasiado grande. El tamaño máximo es 5MB.');
                }

                // Leer el contenido de la imagen
                $imagen_contenido = file_get_contents($file['tmp_name']);
                if ($imagen_contenido === false) {
                    throw new Exception('Error al leer la imagen');
                }

                // Agregar la imagen y su tipo a la consulta
                $sql .= ", imagen_blob = ?, imagen_tipo = ?";
                $params[] = $imagen_contenido;
                $params[] = $file['type'];

                // Guardar una copia temporal para la sesión actual
                $temp_filename = uniqid() . '_temp.' . pathinfo($file['name'], PATHINFO_EXTENSION);
                $temp_path = __DIR__ . '/../img/profile/' . $temp_filename;
                if (move_uploaded_file($file['tmp_name'], $temp_path)) {
                    $_SESSION['temp_image'] = $temp_filename;
                }
            }

            // Agregar contraseña si se proporcionó una nueva
            if (!empty($password)) {
                if ($password !== $confirm_password) {
                    throw new Exception('Las contraseñas no coinciden');
                }
                $sql .= ", password = ?";
                $params[] = password_hash($password, PASSWORD_DEFAULT);
            }

            $sql .= " WHERE id = ?";
            $params[] = $usuario_id;

            // Ejecutar la actualización
            $stmt = $this->db->prepare($sql);
            if (!$stmt->execute($params)) {
                throw new Exception('Error al actualizar el perfil');
            }

            // Actualizar la sesión
            $_SESSION['nombre'] = $nombre;

            $this->db->commit();

            // Devolver la URL temporal de la imagen si se subió una nueva
            $imagen_url = isset($_SESSION['temp_image']) ? 'img/profile/' . $_SESSION['temp_image'] : null;

            // Asegurarse de que no haya salida antes del JSON
            if (ob_get_length()) ob_clean();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Perfil actualizado correctamente',
                'nombre' => $nombre,
                'imagen_url' => $imagen_url
            ]);

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            // Limpiar archivo temporal si existe
            if (isset($_SESSION['temp_image'])) {
                $temp_path = __DIR__ . '/../img/profile/' . $_SESSION['temp_image'];
                if (file_exists($temp_path)) {
                    unlink($temp_path);
                }
                unset($_SESSION['temp_image']);
            }
            
            // Asegurarse de que no haya salida antes del JSON
            if (ob_get_length()) ob_clean();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function obtenerPerfil() {
        try {
            if (!isset($_SESSION['usuario_id'])) {
                $this->sendJsonResponse(false, 'Sesión no iniciada');
            }

            $perfil = $this->usuario->obtenerPerfil($_SESSION['usuario_id']);
            if ($perfil) {
                $this->sendJsonResponse(true, '', ['perfil' => $perfil]);
            } else {
                $this->sendJsonResponse(false, 'Error al obtener el perfil');
            }
        } catch (Exception $e) {
            $this->sendJsonResponse(false, 'Error al procesar la solicitud: ' . $e->getMessage());
        }
    }

    public function obtenerImagenPerfil() {
        try {
            // Limpiar cualquier salida previa
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Verificar sesión
            if (!isset($_SESSION['usuario_id'])) {
                throw new Exception('No hay sesión activa');
            }

            $usuario_id = $_SESSION['usuario_id'];
            
            // Headers para prevenir caché
            header_remove();
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Cache-Control: post-check=0, pre-check=0', false);
            header('Pragma: no-cache');
            
            // Consultar la imagen
            $stmt = $this->db->prepare("SELECT imagen_blob, imagen_tipo FROM usuarios WHERE id = ?");
            if (!$stmt || !$stmt->execute([$usuario_id])) {
                throw new Exception('Error en la consulta de la base de datos');
            }
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Si hay una imagen almacenada y es válida
            if ($resultado && !empty($resultado['imagen_blob'])) {
                $tipo = $resultado['imagen_tipo'] ?: 'image/png';
                header('Content-Type: ' . $tipo);
                echo $resultado['imagen_blob'];
                exit;
            }
            
            // Si no hay imagen, crear una por defecto
            header('Content-Type: image/png');
            
            // Crear una imagen simple
            $size = 100;
            $im = imagecreatetruecolor($size, $size);
            
            // Color de fondo gris claro
            $bg = imagecolorallocate($im, 240, 240, 240);
            imagefill($im, 0, 0, $bg);
            
            // Enviar la imagen
            imagepng($im);
            imagedestroy($im);
            exit;
            
        } catch (Exception $e) {
            error_log("Error en obtenerImagenPerfil: " . $e->getMessage() . "\n" . $e->getTraceAsString(), 3, __DIR__ . "/../logs/error.log");
            
            // Limpiar cualquier salida previa
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Enviar una imagen de error
            header_remove();
            header('Content-Type: image/png');
            
            $im = imagecreatetruecolor(50, 50);
            $red = imagecolorallocate($im, 255, 200, 200);
            imagefill($im, 0, 0, $red);
            imagepng($im);
            imagedestroy($im);
            exit;
        }
    }

    // Método por defecto
    public function index() {
        if (isset($_SESSION['usuario_id'])) {
            header("Location: ../views/chat.php");
        } else {
            header("Location: ../views/login.php");
        }
        exit();
    }
}

// Manejo de las acciones
try {
    if (!isset($_GET['action'])) {
        (new UsuarioController())->sendJsonResponse(false, 'No se especificó ninguna acción', [], '/whatsapp/views/login.php');
    }

    $action = $_GET['action'];
    $controller = new UsuarioController();

    switch($action) {
        case 'logout':
            $controller->cerrarSesion();
            break;
        case 'registrar':
            $controller->registrar();
            break;
        case 'login':
            $controller->login();
            break;
        case 'agregarAmigo':
            $controller->agregarAmigo();
            break;
        case 'responderSolicitud':
            $controller->responderSolicitud();
            break;
        case 'obtenerSolicitudes':
            $controller->obtenerSolicitudes();
            break;
        case 'actualizarPerfil':
            $controller->actualizarPerfil();
            break;
        case 'obtenerPerfil':
            $controller->obtenerPerfil();
            break;
        case 'obtenerImagenPerfil':
            $controller->obtenerImagenPerfil();
            break;
        default:
            $controller->sendJsonResponse(false, 'Acción no reconocida', [], '/whatsapp/views/login.php');
    }
} catch (Exception $e) {
    (new UsuarioController())->sendJsonResponse(false, 'Error interno del servidor: ' . $e->getMessage(), [], '/whatsapp/views/login.php');
}
?> 