-- Guarda la hora real en la que el estudiante pulsa "Marcar asistencia".
ALTER TABLE asistencia
ADD COLUMN IF NOT EXISTS hora_marcada TIME NULL;
