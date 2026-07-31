DROP DATABASE IF EXISTS `caja_menor`;
CREATE DATABASE IF NOT EXISTS `caja_menor` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `caja_menor`;

CREATE TABLE IF NOT EXISTS roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(50) NOT NULL UNIQUE,
  descripcion TEXT,
  estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS permissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL UNIQUE,
  descripcion TEXT,
  estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS role_permissions (
  role_id INT NOT NULL,
  permission_id INT NOT NULL,
  PRIMARY KEY (role_id, permission_id),
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cargos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL UNIQUE,
  estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS empleados (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cedula VARCHAR(30) NOT NULL UNIQUE,
  nombres VARCHAR(120) NOT NULL,
  apellidos VARCHAR(120) NOT NULL,
  cargo_id INT DEFAULT NULL,
  telefono VARCHAR(30),
  estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (cargo_id) REFERENCES cargos(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS proveedores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nit VARCHAR(50) NOT NULL UNIQUE,
  nombre VARCHAR(150) NOT NULL,
  telefono VARCHAR(50),
  direccion VARCHAR(250),
  estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS festivos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL,
  fecha DATE NOT NULL UNIQUE,
  estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role_id INT DEFAULT NULL,
  empleado_id INT DEFAULT NULL,
  estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL,
  FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cajas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tipo_caja ENUM('menor','mayor') NOT NULL,
  fecha_caja DATE NOT NULL,
  valor_inicial DECIMAL(14,2) NOT NULL DEFAULT 0,
  valor_final DECIMAL(14,2) NOT NULL DEFAULT 0,
  estado ENUM('abierta','cerrada') NOT NULL DEFAULT 'abierta',
  fecha_cierre DATETIME DEFAULT NULL,
  nota TEXT DEFAULT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_caja_abierta (tipo_caja, estado, fecha_caja)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS gastos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  caja_id INT NOT NULL,
  empleado_id INT DEFAULT NULL,
  proveedor_id INT DEFAULT NULL,
  fecha_gasto DATE NOT NULL,
  descripcion TEXT,
  valor DECIMAL(14,2) NOT NULL,
  tipo_soporte ENUM('factura','recibo','otro') NOT NULL DEFAULT 'otro',
  soporte VARCHAR(255),
  orden INT NOT NULL DEFAULT 0,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (caja_id) REFERENCES cajas(id) ON DELETE CASCADE,
  FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE SET NULL,
  FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reintegros (
  id INT AUTO_INCREMENT PRIMARY KEY,
  caja_id INT NOT NULL,
  valor DECIMAL(14,2) NOT NULL,
  descripcion TEXT,
  soporte VARCHAR(255),
  fecha_reintegro DATE NOT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (caja_id) REFERENCES cajas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS soportes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  gasto_id INT NOT NULL,
  tipo VARCHAR(20) NOT NULL DEFAULT 'otro',
  archivo VARCHAR(255),
  descripcion VARCHAR(255),
  orden INT NOT NULL DEFAULT 0,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (gasto_id) REFERENCES gastos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT IGNORE INTO roles (nombre, descripcion) VALUES
  ('admin', 'Administrador del sistema'),
  ('aprendiz', 'Usuario aprendiz'),
  ('facturador', 'Usuario facturador'),
  ('contador', 'Usuario contador');

INSERT IGNORE INTO permissions (nombre, descripcion) VALUES
  ('crear_gasto', 'Crear gastos'),
  ('editar_gasto', 'Editar gastos'),
  ('exportar_excel', 'Exportar a Excel'),
  ('cerrar_caja', 'Cerrar caja');
