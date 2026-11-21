-- Verificación de correo: vigencia del token de verificación.
-- Segura para ejecutar más de una vez.
USE soscur_db;

SET @existe := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'usuarios'
      AND COLUMN_NAME = 'token_verificacion_expira'
);

SET @sql := IF(
    @existe = 0,
    'ALTER TABLE usuarios ADD COLUMN token_verificacion_expira DATETIME DEFAULT NULL AFTER token_verificacion',
    'SELECT 1'
);

PREPARE sentencia FROM @sql;
EXECUTE sentencia;
DEALLOCATE PREPARE sentencia;

UPDATE usuarios
SET token_verificacion_expira = DATE_ADD(NOW(), INTERVAL 24 HOUR)
WHERE token_verificacion IS NOT NULL
  AND token_verificacion_expira IS NULL;
