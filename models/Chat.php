<?php

class Chat {
    private $conn;
    private $table_mensajes = "mensajes";
    private $table_amigos = "amigos";
    private $table_usuarios = "usuarios";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crearChat($usuario_id, $amigo_id) {
        try {
            // No necesitamos crear una entrada específica para el chat
            // ya que los chats se crean implícitamente cuando hay una amistad aceptada
            // y se envía el primer mensaje
            return true;
        } catch (PDOException $e) {
            error_log("Error en crearChat: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerChatsUsuario($usuario_id) {
        try {
            $query = "SELECT DISTINCT 
                        u.id as usuario_id,
                        u.nombre,
                        (SELECT mensaje 
                         FROM {$this->table_mensajes} m2 
                         WHERE (m2.emisor_id = :usuario_id AND m2.receptor_id = u.id) 
                            OR (m2.emisor_id = u.id AND m2.receptor_id = :usuario_id2) 
                         ORDER BY m2.fecha_envio DESC 
                         LIMIT 1) as ultimo_mensaje,
                        (SELECT editado 
                         FROM {$this->table_mensajes} m2 
                         WHERE (m2.emisor_id = :usuario_id3 AND m2.receptor_id = u.id) 
                            OR (m2.emisor_id = u.id AND m2.receptor_id = :usuario_id4) 
                         ORDER BY m2.fecha_envio DESC 
                         LIMIT 1) as mensaje_editado,
                        (SELECT eliminado 
                         FROM {$this->table_mensajes} m2 
                         WHERE (m2.emisor_id = :usuario_id5 AND m2.receptor_id = u.id) 
                            OR (m2.emisor_id = u.id AND m2.receptor_id = :usuario_id6) 
                         ORDER BY m2.fecha_envio DESC 
                         LIMIT 1) as mensaje_eliminado
                    FROM {$this->table_amigos} a
                    INNER JOIN {$this->table_usuarios} u ON 
                        CASE 
                            WHEN a.usuario_id = :usuario_id7 THEN a.amigo_id = u.id
                            WHEN a.amigo_id = :usuario_id8 THEN a.usuario_id = u.id
                        END
                    WHERE (a.usuario_id = :usuario_id9 OR a.amigo_id = :usuario_id10)
                    AND a.estado = 'aceptado'";

            $stmt = $this->conn->prepare($query);
            
            // Vincular el mismo usuario_id para todas las condiciones
            for ($i = 1; $i <= 10; $i++) {
                $stmt->bindParam(":usuario_id" . ($i > 1 ? $i : ''), $usuario_id);
            }
            
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerChatsUsuario: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerMensajes($amigo_id, $ultimo_id = 0) {
        try {
            error_log("Intentando obtener mensajes con amigo_id: $amigo_id, ultimo_id: $ultimo_id");
            
            // Primero verificamos si existe una amistad entre los usuarios
            $query = "SELECT id FROM {$this->table_amigos} 
                    WHERE ((usuario_id = :usuario_id AND amigo_id = :amigo_id) 
                    OR (usuario_id = :amigo_id2 AND amigo_id = :usuario_id2))
                    AND estado = 'aceptado'";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":usuario_id", $_SESSION['usuario_id']);
            $stmt->bindParam(":amigo_id", $amigo_id);
            $stmt->bindParam(":usuario_id2", $_SESSION['usuario_id']);
            $stmt->bindParam(":amigo_id2", $amigo_id);
            
            if (!$stmt->execute()) {
                error_log("Error al ejecutar consulta de amistad: " . print_r($stmt->errorInfo(), true));
                return false;
            }
            
            if (!$stmt->fetch()) {
                error_log("No existe amistad aceptada entre usuarios: {$_SESSION['usuario_id']} y $amigo_id");
                return false;
            }

            // Si existe la amistad, obtenemos los mensajes
            $query = "SELECT m.id, m.emisor_id, m.receptor_id, m.mensaje, m.fecha_envio, 
                            m.eliminado, m.editado, m.mensaje_original,
                            u.nombre as nombre_emisor
                    FROM {$this->table_mensajes} m
                    INNER JOIN {$this->table_usuarios} u ON m.emisor_id = u.id
                    WHERE ((m.emisor_id = :emisor_id AND m.receptor_id = :receptor_id)
                    OR (m.emisor_id = :receptor_id2 AND m.receptor_id = :emisor_id2))";
            
            // Solo aplicamos el filtro de último ID si es mayor que 0
            if ($ultimo_id > 0) {
                $query .= " AND m.id > :ultimo_id";
            }
            
            $query .= " ORDER BY m.fecha_envio ASC";

            error_log("Query de mensajes: " . $query);
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":emisor_id", $_SESSION['usuario_id']);
            $stmt->bindParam(":receptor_id", $amigo_id);
            $stmt->bindParam(":receptor_id2", $amigo_id);
            $stmt->bindParam(":emisor_id2", $_SESSION['usuario_id']);
            
            if ($ultimo_id > 0) {
                $stmt->bindParam(":ultimo_id", $ultimo_id);
            }
            
            if (!$stmt->execute()) {
                error_log("Error al ejecutar consulta de mensajes: " . print_r($stmt->errorInfo(), true));
                return false;
            }

            $mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Mensajes obtenidos: " . count($mensajes));
            error_log("Contenido de mensajes: " . print_r($mensajes, true));

            // Actualizamos el estado de los mensajes recibidos a 'leido'
            if (!empty($mensajes)) {
                $query = "UPDATE {$this->table_mensajes}
                        SET estado = 'leido'
                        WHERE emisor_id = :emisor_id 
                        AND receptor_id = :receptor_id 
                        AND estado != 'leido'";

                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(":emisor_id", $amigo_id);
                $stmt->bindParam(":receptor_id", $_SESSION['usuario_id']);
                
                if (!$stmt->execute()) {
                    error_log("Error al actualizar estado de mensajes: " . print_r($stmt->errorInfo(), true));
                }
            }

            return $mensajes;
        } catch (PDOException $e) {
            error_log("Error en obtenerMensajes: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    public function enviarMensaje($receptor_id, $emisor_id, $mensaje) {
        try {
            error_log("=== Iniciando enviarMensaje en Chat model ===");
            error_log("Emisor ID: $emisor_id, Receptor ID: $receptor_id");
            
            // Verificar si existe una amistad aceptada
            $query = "SELECT id FROM {$this->table_amigos} 
                    WHERE ((usuario_id = :emisor_id AND amigo_id = :receptor_id) 
                    OR (usuario_id = :receptor_id2 AND amigo_id = :emisor_id2))
                    AND estado = 'aceptado'";
            
            error_log("Query de verificación de amistad: " . $query);
            error_log("Parámetros: emisor_id=$emisor_id, receptor_id=$receptor_id");
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":emisor_id", $emisor_id, PDO::PARAM_INT);
            $stmt->bindParam(":receptor_id", $receptor_id, PDO::PARAM_INT);
            $stmt->bindParam(":receptor_id2", $receptor_id, PDO::PARAM_INT);
            $stmt->bindParam(":emisor_id2", $emisor_id, PDO::PARAM_INT);
            
            if (!$stmt->execute()) {
                $error = $stmt->errorInfo();
                error_log("Error al ejecutar consulta de amistad: " . print_r($error, true));
                return false;
            }
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("Resultado de verificación de amistad: " . ($result ? "Amistad encontrada (ID: {$result['id']})" : "No se encontró amistad"));
            
            if (!$result) {
                error_log("No existe amistad aceptada entre usuarios: $emisor_id y $receptor_id");
                return false;
            }

            // Iniciar transacción
            $this->conn->beginTransaction();
            error_log("Transacción iniciada");

            try {
                // Guardar el mensaje
                $query = "INSERT INTO {$this->table_mensajes} 
                        (emisor_id, receptor_id, mensaje, estado, fecha_envio) 
                        VALUES (:emisor_id, :receptor_id, :mensaje, 'enviado', NOW())";

                error_log("Query de inserción de mensaje: " . $query);
                error_log("Parámetros de inserción: emisor_id=$emisor_id, receptor_id=$receptor_id, mensaje=" . substr($mensaje, 0, 50) . "...");
                
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(":emisor_id", $emisor_id, PDO::PARAM_INT);
                $stmt->bindParam(":receptor_id", $receptor_id, PDO::PARAM_INT);
                $stmt->bindParam(":mensaje", $mensaje, PDO::PARAM_STR);
                
                if (!$stmt->execute()) {
                    $error = $stmt->errorInfo();
                    error_log("Error al insertar mensaje: " . print_r($error, true));
                    throw new PDOException("Error al insertar mensaje: " . $error[2]);
                }

                $mensaje_id = $this->conn->lastInsertId();
                error_log("Mensaje insertado correctamente con ID: $mensaje_id");
                
                $this->conn->commit();
                error_log("Transacción completada exitosamente");
                return true;
            } catch (Exception $e) {
                $this->conn->rollBack();
                error_log("Error en transacción de mensaje: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                return false;
            }
        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("Error en enviarMensaje: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }
}