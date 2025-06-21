<?php
class Amigo {
    private $conn;
    private $table_name = "amigos";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerSolicitudes($usuario_id) {
        try {
            $query = "SELECT a.id, a.estado, u.nombre, u.email 
                     FROM " . $this->table_name . " a
                     INNER JOIN usuarios u ON a.usuario_id = u.id
                     WHERE a.amigo_id = :usuario_id
                     AND a.estado = 'pendiente'
                     ORDER BY a.fecha_amistad DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":usuario_id", $usuario_id);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Error obteniendo solicitudes: " . $e->getMessage());
            return false;
        }
    }

    public function responderSolicitud($solicitud_id, $respuesta, $usuario_id) {
        try {
            // Verificar que la solicitud sea para este usuario
            $query = "SELECT id FROM " . $this->table_name . " 
                     WHERE id = :solicitud_id AND amigo_id = :usuario_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":solicitud_id", $solicitud_id);
            $stmt->bindParam(":usuario_id", $usuario_id);
            $stmt->execute();

            if ($stmt->rowCount() == 0) {
                return false;
            }

            // Actualizar el estado de la solicitud
            $query = "UPDATE " . $this->table_name . " 
                     SET estado = :estado 
                     WHERE id = :solicitud_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":estado", $respuesta);
            $stmt->bindParam(":solicitud_id", $solicitud_id);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Error respondiendo solicitud: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerAmigos($usuario_id) {
        try {
            $query = "SELECT u.id, u.nombre, u.email, u.ultimo_acceso
                     FROM usuarios u
                     INNER JOIN amigos a ON (a.usuario_id = u.id OR a.amigo_id = u.id)
                     WHERE (a.usuario_id = :usuario_id OR a.amigo_id = :usuario_id)
                     AND a.estado = 'aceptado'
                     AND u.id != :usuario_id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":usuario_id", $usuario_id);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Error obteniendo amigos: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerDatosSolicitud($solicitud_id) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " 
                     WHERE id = :solicitud_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":solicitud_id", $solicitud_id);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Error obteniendo datos de la solicitud: " . $e->getMessage());
            return false;
        }
    }
}
?> 