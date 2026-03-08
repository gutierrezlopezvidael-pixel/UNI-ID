CREATE DATABASE IF NOT EXISTS uni_id CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE uni_id;

-- --------------------------------------------------
-- Tablas principales
-- --------------------------------------------------
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS carreras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS alumnos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    matricula VARCHAR(20) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    telefono VARCHAR(20) DEFAULT NULL,
    carrera VARCHAR(150) NOT NULL,
    semestre INT DEFAULT 1,
    grupo VARCHAR(10) DEFAULT NULL,
    foto VARCHAR(255) DEFAULT NULL,
    estado ENUM('activo','inactivo','baja') DEFAULT 'activo',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_alumnos_carrera_semestre (carrera, semestre),
    INDEX idx_alumnos_estado (estado),
    INDEX idx_alumnos_email (email)
) ENGINE=InnoDB;

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
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS materias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    carrera_id INT NOT NULL,
    cuatrimestre TINYINT NOT NULL DEFAULT 1,
    UNIQUE KEY uk_materia_carrera_cuat (nombre, carrera_id, cuatrimestre),
    INDEX idx_materias_carrera_cuat (carrera_id, cuatrimestre),
    FOREIGN KEY (carrera_id) REFERENCES carreras(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS calificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alumno_id INT NOT NULL,
    materia_nombre VARCHAR(150) NOT NULL,
    cuatrimestre INT NOT NULL,
    calificacion DECIMAL(5,2) NOT NULL,
    periodo VARCHAR(20) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_calif_alumno_cuat (alumno_id, cuatrimestre),
    FOREIGN KEY (alumno_id) REFERENCES alumnos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS docentes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) DEFAULT NULL,
    email VARCHAR(150) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS docente_asignaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    docente_id INT NOT NULL,
    materia_nombre VARCHAR(150) NOT NULL,
    carrera VARCHAR(150) NOT NULL,
    cuatrimestre INT NOT NULL,
    grupo VARCHAR(10) DEFAULT NULL,
    INDEX idx_asig_docente (docente_id),
    FOREIGN KEY (docente_id) REFERENCES docentes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Migración: agregar cuatrimestre a materias si viene de BD antigua
DROP PROCEDURE IF EXISTS uni_id_migrate_materias;
DELIMITER //
CREATE PROCEDURE uni_id_migrate_materias()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'uni_id' AND TABLE_NAME = 'materias' AND COLUMN_NAME = 'cuatrimestre') THEN
        ALTER TABLE materias ADD COLUMN cuatrimestre TINYINT NOT NULL DEFAULT 1 AFTER carrera_id;
        ALTER TABLE materias DROP INDEX uk_materia_carrera;
        ALTER TABLE materias ADD UNIQUE KEY uk_materia_carrera_cuat (nombre, carrera_id, cuatrimestre);
    END IF;
END //
DELIMITER ;
CALL uni_id_migrate_materias();
DROP PROCEDURE uni_id_migrate_materias;

DROP PROCEDURE IF EXISTS uni_id_migrate_alumnos;
DELIMITER //
CREATE PROCEDURE uni_id_migrate_alumnos()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'uni_id' AND TABLE_NAME = 'alumnos' AND COLUMN_NAME = 'password_hash') THEN
        ALTER TABLE alumnos ADD COLUMN password_hash VARCHAR(255) DEFAULT NULL AFTER telefono;
        UPDATE alumnos SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE password_hash IS NULL;
    END IF;
END //
DELIMITER ;
CALL uni_id_migrate_alumnos();
DROP PROCEDURE uni_id_migrate_alumnos;

-- --------------------------------------------------
-- Datos iniciales
-- --------------------------------------------------
INSERT INTO admins (usuario, password_hash, nombre) VALUES
('admin', '$2y$10$E7a7AU3/qfDZ7c8tcKz4d.yG3viV1CR9xmFVLtw4sDPcownEqRwzy', 'Administrador')
ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash);

INSERT INTO docentes (usuario, password_hash, nombre, apellido) VALUES
('docente', '$2y$10$rVfuveo2oZX4kITIP0SvL.7R5Z6GQmHKgf3IoWeze3YkCDSeLO8cO', 'Docente', 'Prueba')
ON DUPLICATE KEY UPDATE usuario = usuario;

INSERT INTO carreras (id, nombre) VALUES
(1, 'Lic. en Ing. en Tec. de la Información e Innovación Digital'),
(2, 'Lic. en Ing. en Manejo de Recursos Naturales'),
(3, 'Lic. en Ingeniería Petrolera'),
(4, 'Lic. en Gestión y Desarrollo Turístico'),
(5, 'Lic. en Comercio Internacional y Aduanas')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);
