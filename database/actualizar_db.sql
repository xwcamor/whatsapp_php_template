-- Agregar campo descripcion si no existe
ALTER TABLE usuarios
ADD descripcion TEXT DEFAULT 'Hola, estoy en WhatsApp';

-- Actualizar usuarios existentes que no tengan descripción
UPDATE usuarios 
SET descripcion = 'Hola, estoy en WhatsApp' 
WHERE descripcion IS NULL;

-- Agregar campo para almacenar la imagen como BLOB
ALTER TABLE usuarios
ADD imagen_blob MEDIUMBLOB;

-- Agregar campo para el tipo de imagen
ALTER TABLE usuarios
ADD imagen_tipo VARCHAR(50);

-- Eliminar la columna anterior de imagen_perfil
ALTER TABLE usuarios
DROP COLUMN imagen_perfil;

-- Agregar campo para mensajes eliminados
ALTER TABLE mensajes
ADD eliminado TINYINT(1) NOT NULL DEFAULT 0;

-- Agregar campo para mensajes editados
ALTER TABLE mensajes
ADD editado TINYINT(1) NOT NULL DEFAULT 0;

-- Agregar campo para mensaje original
ALTER TABLE mensajes
ADD mensaje_original TEXT;

-- Actualizar mensajes existentes
UPDATE mensajes
SET eliminado = 0, editado = 0
WHERE eliminado IS NULL OR editado IS NULL;