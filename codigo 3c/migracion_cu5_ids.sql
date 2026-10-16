BEGIN;

-- 1) Preparar columnas nuevas de IDs enteros.
ALTER TABLE profesor ADD COLUMN IF NOT EXISTS profesor_id SERIAL;
ALTER TABLE estudiante ADD COLUMN IF NOT EXISTS estudiante_id SERIAL;
ALTER TABLE grupo ADD COLUMN IF NOT EXISTS profesor_id INT;
ALTER TABLE asignacion ADD COLUMN IF NOT EXISTS estudiante_id INT;

-- 2) Grupo: migrar FK de profesor_codigo_profesor -> profesor_id.
UPDATE grupo g
SET profesor_id = p.profesor_id
FROM profesor p
WHERE p.codigo_profesor = g.profesor_codigo_profesor
  AND g.profesor_id IS NULL;

ALTER TABLE grupo ALTER COLUMN profesor_id SET NOT NULL;
ALTER TABLE grupo DROP CONSTRAINT IF EXISTS grupo_profesor_codigo_profesor_fkey;
ALTER TABLE grupo DROP COLUMN IF EXISTS profesor_codigo_profesor;

-- 3) Profesor: cambiar PK a profesor_id y dejar codigo_profesor como UNIQUE.
ALTER TABLE profesor DROP CONSTRAINT IF EXISTS profesor_pkey;
ALTER TABLE profesor ADD CONSTRAINT profesor_pkey PRIMARY KEY (profesor_id);
ALTER TABLE profesor DROP CONSTRAINT IF EXISTS profesor_codigo_profesor_key;
ALTER TABLE profesor ADD CONSTRAINT profesor_codigo_profesor_key UNIQUE (codigo_profesor);
ALTER TABLE grupo DROP CONSTRAINT IF EXISTS grupo_profesor_id_fkey;
ALTER TABLE grupo ADD CONSTRAINT grupo_profesor_id_fkey
    FOREIGN KEY (profesor_id) REFERENCES profesor(profesor_id) ON DELETE CASCADE;

-- 4) Asignacion: migrar estudiante_codigo_estudiante -> estudiante_id.
UPDATE asignacion a
SET estudiante_id = e.estudiante_id
FROM estudiante e
WHERE e.codigo_estudiante = a.estudiante_codigo_estudiante
  AND a.estudiante_id IS NULL;

ALTER TABLE asignacion ALTER COLUMN estudiante_id SET NOT NULL;
ALTER TABLE asignacion DROP CONSTRAINT IF EXISTS asignacion_estudiante_codigo_estudiante_fkey;
ALTER TABLE asignacion DROP CONSTRAINT IF EXISTS asignacion_pkey;
ALTER TABLE asignacion ADD CONSTRAINT asignacion_pkey PRIMARY KEY (estudiante_id, grupo_id);
ALTER TABLE asignacion DROP COLUMN IF EXISTS estudiante_codigo_estudiante;

-- 5) Estudiante: cambiar PK a estudiante_id y conservar codigo_estudiante como UNIQUE.
ALTER TABLE asistencia DROP CONSTRAINT IF EXISTS asistencia_estudiante_codigo_estudiante_fkey;
ALTER TABLE estudiante DROP CONSTRAINT IF EXISTS estudiante_pkey;
ALTER TABLE estudiante ADD CONSTRAINT estudiante_pkey PRIMARY KEY (estudiante_id);
ALTER TABLE estudiante DROP CONSTRAINT IF EXISTS estudiante_codigo_estudiante_key;
ALTER TABLE estudiante ADD CONSTRAINT estudiante_codigo_estudiante_key UNIQUE (codigo_estudiante);
ALTER TABLE asignacion DROP CONSTRAINT IF EXISTS asignacion_estudiante_id_fkey;
ALTER TABLE asignacion ADD CONSTRAINT asignacion_estudiante_id_fkey
  FOREIGN KEY (estudiante_id) REFERENCES estudiante(estudiante_id) ON DELETE CASCADE;
ALTER TABLE asistencia ADD CONSTRAINT asistencia_estudiante_codigo_estudiante_fkey
    FOREIGN KEY (estudiante_codigo_estudiante) REFERENCES estudiante(codigo_estudiante) ON DELETE CASCADE;

COMMIT;
