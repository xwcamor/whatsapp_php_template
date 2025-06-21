<?php
require_once 'database.php';

try {
    // Crear conexión sin seleccionar base de datos
    $pdo = new PDO("mysql:host=localhost", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Crear la base de datos si no existe
    $sql = "CREATE DATABASE IF NOT EXISTS whatsapp_db";
    $pdo->exec($sql);
    echo "Base de datos creada o ya existente.\n";

    // Seleccionar la base de datos
    $pdo->exec("USE whatsapp_db");

    // Crear tabla usuarios
    $sql = "CREATE TABLE IF NOT EXISTS usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
        ultimo_acceso DATETIME
    )";
    $pdo->exec($sql);
    echo "Tabla usuarios creada o ya existente.\n";

    // Crear tabla amigos
    $sql = "CREATE TABLE IF NOT EXISTS amigos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT,
        amigo_id INT,
        fecha_amistad DATETIME DEFAULT CURRENT_TIMESTAMP,
        estado ENUM('pendiente', 'aceptado', 'rechazado') DEFAULT 'pendiente',
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
        FOREIGN KEY (amigo_id) REFERENCES usuarios(id)
    )";
    $pdo->exec($sql);
    echo "Tabla amigos creada o ya existente.\n";

    // Crear tabla mensajes
    $sql = "CREATE TABLE IF NOT EXISTS mensajes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        emisor_id INT,
        receptor_id INT,
        mensaje TEXT NOT NULL,
        fecha_envio DATETIME DEFAULT CURRENT_TIMESTAMP,
        estado ENUM('enviado', 'recibido', 'leido') DEFAULT 'enviado',
        FOREIGN KEY (emisor_id) REFERENCES usuarios(id),
        FOREIGN KEY (receptor_id) REFERENCES usuarios(id)
    )";
    $pdo->exec($sql);
    echo "Tabla mensajes creada o ya existente.\n";

    echo "Configuración de la base de datos completada exitosamente.\n";

} catch(PDOException $e) {
    echo "Error en la configuración de la base de datos: " . $e->getMessage() . "\n";
}
?> 