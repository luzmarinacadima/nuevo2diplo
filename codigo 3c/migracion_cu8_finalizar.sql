-- Migracion CU8: soportar "Finalizar" asistencia
-- Ejecutar una sola vez en la BD existente.

ALTER TABLE clase
ADD COLUMN IF NOT EXISTS finalizada BOOLEAN DEFAULT FALSE;

ALTER TABLE clase
ADD COLUMN IF NOT EXISTS finalizada_en TIMESTAMP NULL;
