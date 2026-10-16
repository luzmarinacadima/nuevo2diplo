CREATE TABLE IF NOT EXISTS usuario (
    id SERIAL PRIMARY KEY,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    genero VARCHAR(10),
    nombre_usuario VARCHAR(100) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL,
    tipo_usuario VARCHAR(20) NOT NULL CHECK (tipo_usuario IN ('admin', 'profesor', 'estudiante'))
);

CREATE TABLE IF NOT EXISTS profesor (
    profesor_id SERIAL PRIMARY KEY,
    codigo_profesor VARCHAR(20) NOT NULL UNIQUE,
    usuario_id INT NOT NULL UNIQUE REFERENCES usuario(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS estudiante (
    estudiante_id SERIAL PRIMARY KEY,
    codigo_estudiante VARCHAR(20) NOT NULL UNIQUE,
    usuario_id INT NOT NULL UNIQUE REFERENCES usuario(id) ON DELETE CASCADE,
    ci VARCHAR(20) NOT NULL UNIQUE,
    estado VARCHAR(20) NOT NULL DEFAULT 'activo'
);

CREATE TABLE IF NOT EXISTS materia (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS grupo (
    id_grupo SERIAL PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    capacidad_maxima INTEGER NOT NULL DEFAULT 100 CHECK (capacidad_maxima > 0),
    capacidad_actual INTEGER NOT NULL DEFAULT 0 CHECK (capacidad_actual >= 0),
    materia_id INT NOT NULL REFERENCES materia(id) ON DELETE CASCADE,
    profesor_id INT NOT NULL REFERENCES profesor(profesor_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS clase (
    id_clase SERIAL PRIMARY KEY,
    dia DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    codigo VARCHAR(255) NOT NULL,
    finalizada BOOLEAN NOT NULL DEFAULT FALSE,
    finalizada_en TIMESTAMP NULL,
    grupo_id INT NOT NULL REFERENCES grupo(id_grupo) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS asistencia (
    id_asistencia SERIAL PRIMARY KEY,
    tipo VARCHAR(20) NOT NULL,
    clase_id INT NOT NULL REFERENCES clase(id_clase) ON DELETE CASCADE,
    estudiante_codigo_estudiante VARCHAR(20) NOT NULL REFERENCES estudiante(codigo_estudiante) ON DELETE CASCADE,
    hora_marcada TIME NULL,
    UNIQUE (clase_id, estudiante_codigo_estudiante)
);

CREATE TABLE IF NOT EXISTS asignacion (
    estudiante_id INT NOT NULL REFERENCES estudiante(estudiante_id) ON DELETE CASCADE,
    grupo_id INT NOT NULL REFERENCES grupo(id_grupo) ON DELETE CASCADE,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (estudiante_id, grupo_id)
);

CREATE INDEX IF NOT EXISTS idx_clase_grupo ON clase(grupo_id);
CREATE INDEX IF NOT EXISTS idx_asistencia_clase ON asistencia(clase_id);
