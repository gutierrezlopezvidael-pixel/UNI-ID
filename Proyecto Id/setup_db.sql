CREATE DATABASE IF NOT EXISTS uni_id CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE uni_id;

CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS alumnos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    matricula VARCHAR(8) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(150) DEFAULT NULL,
    telefono VARCHAR(20) DEFAULT NULL,
    carrera VARCHAR(150) NOT NULL,
    semestre INT DEFAULT 1,
    grupo VARCHAR(10) DEFAULT NULL,
    foto VARCHAR(255) DEFAULT NULL,
    estado ENUM('activo','inactivo','baja') DEFAULT 'activo',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alumno_id INT NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta VARCHAR(500) NOT NULL,
    tipo VARCHAR(100) DEFAULT NULL,
    tamano INT DEFAULT 0,
    descripcion TEXT DEFAULT NULL,
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (alumno_id) REFERENCES alumnos(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS calificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alumno_id INT NOT NULL,
    materia_nombre VARCHAR(150) NOT NULL,
    cuatrimestre INT NOT NULL,
    calificacion DECIMAL(5,2) NOT NULL,
    periodo VARCHAR(20) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (alumno_id) REFERENCES alumnos(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS docentes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) DEFAULT NULL,
    email VARCHAR(150) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS docente_asignaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    docente_id INT NOT NULL,
    materia_nombre VARCHAR(150) NOT NULL,
    carrera VARCHAR(150) NOT NULL,
    cuatrimestre INT NOT NULL,
    grupo VARCHAR(10) DEFAULT NULL,
    FOREIGN KEY (docente_id) REFERENCES docentes(id) ON DELETE CASCADE
);

-- Carreras (el admin las gestiona)
CREATE TABLE IF NOT EXISTS carreras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL UNIQUE
);

-- Materias por carrera y cuatrimestre (1-10 por carrera)
CREATE TABLE IF NOT EXISTS materias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    carrera_id INT NOT NULL,
    cuatrimestre TINYINT NOT NULL DEFAULT 1,
    UNIQUE KEY uk_materia_carrera_cuat (nombre, carrera_id, cuatrimestre),
    FOREIGN KEY (carrera_id) REFERENCES carreras(id) ON DELETE CASCADE
);

-- Docente de prueba (usuario: docente / password: docente123)
INSERT INTO docentes (usuario, password_hash, nombre, apellido) VALUES
('docente', '$2y$10$rVfuveo2oZX4kITIP0SvL.7R5Z6GQmHKgf3IoWeze3YkCDSeLO8cO', 'Docente', 'Prueba')
ON DUPLICATE KEY UPDATE usuario=usuario;

-- Admin por defecto (usuario: admin / password: admin123)
INSERT INTO admins (usuario, password_hash, nombre) VALUES
('admin', '$2y$10$E7a7AU3/qfDZ7c8tcKz4d.yG3viV1CR9xmFVLtw4sDPcownEqRwzy', 'Administrador')
ON DUPLICATE KEY UPDATE password_hash='$2y$10$E7a7AU3/qfDZ7c8tcKz4d.yG3viV1CR9xmFVLtw4sDPcownEqRwzy';

-- Carreras por defecto
INSERT INTO carreras (id, nombre) VALUES
(1, 'Lic. en Ing. en Tec. de la Información e Innovación Digital'),
(2, 'Lic. en Ing. en Manejo de Recursos Naturales'),
(3, 'Lic. en Ingeniería Petrolera'),
(4, 'Lic. en Gestión y Desarrollo Turístico'),
(5, 'Lic. en Comercio Internacional y Aduanas')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);
