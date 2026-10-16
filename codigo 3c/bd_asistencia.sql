-- Script para crear la base de datos y tablas del sistema de asistencia
-- Ejecuta primero: CREATE DATABASE asistencia; (esto hazlo fuera de cualquier bloque de transacción)

-- Luego selecciona la base de datos 'asistencia' en tu gestor antes de ejecutar el resto del script

-- Tabla usuario (generalizadora)
CREATE TABLE usuario (
    id SERIAL PRIMARY KEY,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    genero VARCHAR(10),
    nombre_usuario VARCHAR(100) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL,
    tipo_usuario VARCHAR(20) NOT NULL
);

-- Tabla profesor (especializada)
CREATE TABLE profesor (
    profesor_id SERIAL PRIMARY KEY,
    codigo_profesor VARCHAR(20) NOT NULL UNIQUE,
    usuario_id INT NOT NULL UNIQUE,
    FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE CASCADE
);

-- Tabla estudiante (especializada)
CREATE TABLE estudiante (
    estudiante_id SERIAL PRIMARY KEY,
    codigo_estudiante VARCHAR(20) NOT NULL UNIQUE,
    usuario_id INT NOT NULL UNIQUE,
    ci VARCHAR(20) NOT NULL UNIQUE,
    estado VARCHAR(20) DEFAULT 'activo',
    FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE CASCADE
);

-- Tabla materia
CREATE TABLE materia (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL
);

-- Tabla grupo
CREATE TABLE grupo (
    id_grupo SERIAL PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    capacidad_maxima INTEGER DEFAULT 100,
    capacidad_actual INTEGER DEFAULT 0,
    materia_id INT NOT NULL,
    profesor_id INT NOT NULL,
    FOREIGN KEY (materia_id) REFERENCES materia(id) ON DELETE CASCADE,
    FOREIGN KEY (profesor_id) REFERENCES profesor(profesor_id) ON DELETE CASCADE
);

-- Tabla clase
CREATE TABLE clase (
    id_clase SERIAL PRIMARY KEY,
    dia DATE NOT NULL,
    hora_inicio TIME,
    hora_fin TIME,
    codigo VARCHAR(255),
    finalizada BOOLEAN DEFAULT FALSE,
    finalizada_en TIMESTAMP NULL,
    grupo_id INT NOT NULL,
    FOREIGN KEY (grupo_id) REFERENCES grupo(id_grupo) ON DELETE CASCADE
);

-- Tabla asistencia
CREATE TABLE asistencia (
    id_asistencia SERIAL PRIMARY KEY,
    tipo VARCHAR(20) NOT NULL,
    clase_id INT NOT NULL,
    estudiante_codigo_estudiante VARCHAR(20) NOT NULL,
    FOREIGN KEY (clase_id) REFERENCES clase(id_clase) ON DELETE CASCADE,
    FOREIGN KEY (estudiante_codigo_estudiante) REFERENCES estudiante(codigo_estudiante) ON DELETE CASCADE
);

-- Tabla asignacion
CREATE TABLE asignacion (
    estudiante_id INT NOT NULL,
    grupo_id INT NOT NULL,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (estudiante_id, grupo_id),
    FOREIGN KEY (estudiante_id) REFERENCES estudiante(estudiante_id) ON DELETE CASCADE,
    FOREIGN KEY (grupo_id) REFERENCES grupo(id_grupo) ON DELETE CASCADE
);

-- Datos de ejemplo
INSERT INTO usuario (nombres, apellidos, genero, nombre_usuario, contrasena, tipo_usuario)
VALUES
('Administrador', 'Principal', 'M', 'admin', 'admin123', 'admin'),
('Juan', 'Pérez', 'M', 'profesor1', 'prof123', 'profesor'),
('Ana', 'García', 'F', 'estudiante1', 'estu123', 'estudiante');

INSERT INTO profesor (codigo_profesor, usuario_id) VALUES ('PROF001', 2);
INSERT INTO estudiante (codigo_estudiante, usuario_id, ci, estado) VALUES ('ESTU001', 3, '12345678', 'activo');

ALTER TABLE clase ADD COLUMN finalizada BOOLEAN DEFAULT FALSE;

ALTER TABLE clase ADD COLUMN finalizada BOOLEAN DEFAULT FALSE;

SELECT column_name FROM information_schema.columns WHERE table_name = 'clase';