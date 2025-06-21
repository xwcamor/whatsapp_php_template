<?php
class Usuario {
    private $conn;
    private $table_name = "usuarios";

    public $id;
    public $nombre;
    public $email;
    public $password;
    public $descripcion;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function registrar() {
        try {
            // Validar datos
            if (empty($this->nombre) || empty($this->email) || empty($this->password)) {
                return false;
            }

            // Verificar si el email ya existe
            if ($this->emailExiste()) {
                return false;
            }

            // Preparar la consulta
            $query = "INSERT INTO " . $this->table_name . " SET nombre=:nombre, email=:email, password=:password";
            $stmt = $this->conn->prepare($query);

            // Limpiar y sanitizar datos
            $this->nombre = htmlspecialchars(strip_tags($this->nombre));
            $this->email = htmlspecialchars(strip_tags($this->email));
            $this->password = password_hash($this->password, PASSWORD_DEFAULT);

            // Vincular parámetros
            $stmt->bindParam(":nombre", $this->nombre);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":password", $this->password);

            // Ejecutar la consulta
            if($stmt->execute()) {
                return true;
            }
            return false;
        } catch(PDOException $e) {
            error_log("Error en registro: " . $e->getMessage());
            return false;
        }
    }

    private function emailExiste() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->email]);
        return $stmt->rowCount() > 0;
    }

    public function login() {
        try {
            $query = "SELECT id, nombre, email, password FROM " . $this->table_name . " WHERE email = ?";
            $stmt = $this->conn->prepare($query);
            
            // Limpiar datos
            $this->email = htmlspecialchars(strip_tags($this->email));
            
            $stmt->execute([$this->email]);
            
            if($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if(password_verify($this->password, $row['password'])) {
                    $this->id = $row['id'];
                    $this->nombre = $row['nombre'];
                    
                    // Actualizar último acceso
                    $this->actualizarUltimoAcceso();
                    
                    return true;
                }
            }
            return false;
        } catch(PDOException $e) {
            error_log("Error en login: " . $e->getMessage());
            return false;
        }
    }

    private function actualizarUltimoAcceso() {
        try {
            $query = "UPDATE " . $this->table_name . " SET ultimo_acceso = NOW() WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$this->id]);
        } catch(PDOException $e) {
            error_log("Error actualizando último acceso: " . $e->getMessage());
            return false;
        }
    }

    public function buscarPorEmail($email) {
        try {
            $query = "SELECT id, nombre, email FROM " . $this->table_name . " WHERE email = ?";
            $stmt = $this->conn->prepare($query);
            
            // Limpiar datos
            $email = htmlspecialchars(strip_tags($email));
            
            $stmt->execute([$email]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Error en búsqueda por email: " . $e->getMessage());
            return false;
        }
    }

    public function agregarAmigo($amigo_id) {
        try {
            // Verificar si ya existe la amistad
            $check_query = "SELECT id FROM amigos WHERE 
                          (usuario_id = ? AND amigo_id = ?) OR 
                          (usuario_id = ? AND amigo_id = ?)";
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->execute([$this->id, $amigo_id, $amigo_id, $this->id]);
            
            if($check_stmt->rowCount() > 0) {
                return false; // Ya existe la amistad
            }

            $query = "INSERT INTO amigos (usuario_id, amigo_id) VALUES (:usuario_id, :amigo_id)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":usuario_id", $this->id);
            $stmt->bindParam(":amigo_id", $amigo_id);
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Error al agregar amigo: " . $e->getMessage());
            return false;
        }
    }

    public function actualizarPerfil() {
        try {
            $query_parts = [];
            $params = [];

            // Siempre actualizar nombre y descripción
            $query_parts[] = "nombre = :nombre";
            $query_parts[] = "descripcion = :descripcion";
            $params[':nombre'] = $this->nombre;
            $params[':descripcion'] = $this->descripcion;
            $params[':id'] = $this->id;

            // Si hay contraseña nueva, incluirla en la actualización
            if ($this->password) {
                $query_parts[] = "password = :password";
                $params[':password'] = password_hash($this->password, PASSWORD_DEFAULT);
            }

            $query = "UPDATE usuarios SET " . implode(", ", $query_parts) . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error en actualizarPerfil: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerPerfil($usuario_id) {
        try {
            $query = "SELECT id, nombre, email, descripcion FROM " . $this->table_name . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$usuario_id]);
            
            $perfil = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($perfil) {
                $this->id = $perfil['id'];
                $this->nombre = $perfil['nombre'];
                $this->email = $perfil['email'];
                $this->descripcion = $perfil['descripcion'];
            }
            return $perfil;
        } catch(PDOException $e) {
            error_log("Error obteniendo perfil: " . $e->getMessage());
            return false;
        }
    }
}
?> 