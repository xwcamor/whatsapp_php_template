CREATE DATABASE IF NOT EXISTS whatsapp_db;
USE whatsapp_db;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    descripcion TEXT DEFAULT 'Hola, estoy en WhatsApp',
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso DATETIME
);

CREATE TABLE amigos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    amigo_id INT,
    fecha_amistad DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('pendiente', 'aceptado', 'rechazado') DEFAULT 'pendiente',
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (amigo_id) REFERENCES usuarios(id)
);

CREATE TABLE mensajes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    emisor_id INT,
    receptor_id INT,
    mensaje TEXT NOT NULL,
    fecha_envio DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('enviado', 'recibido', 'leido') DEFAULT 'enviado',
    FOREIGN KEY (emisor_id) REFERENCES usuarios(id),
    FOREIGN KEY (receptor_id) REFERENCES usuarios(id)
); 