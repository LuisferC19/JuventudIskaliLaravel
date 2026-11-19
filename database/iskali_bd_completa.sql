-- =====================================================================
-- Juventud Iskali | Base de datos completa v6 (Laravel 12 / MySQL 8)
-- Roles v6.0 (6 roles) + requerimientos matriz v5.0
-- Ejecutar con: mysql -u root < iskali_bd_completa.sql
-- ATENCION: BORRA la base `iskali` y la crea desde cero.
-- Contrasena de TODOS los usuarios de prueba: Iskali2026!
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP DATABASE IF EXISTS iskali;
CREATE DATABASE iskali CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE iskali;

-- ---------------------------------------------------------------------
-- 1. TABLAS BASE DE LARAVEL (para que `php artisan migrate` no choque)
-- ---------------------------------------------------------------------
CREATE TABLE migrations (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  migration VARCHAR(255) NOT NULL,
  batch INT NOT NULL
) ENGINE=InnoDB;

CREATE TABLE users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  email_verified_at TIMESTAMP NULL,
  password VARCHAR(255) NOT NULL,
  remember_token VARCHAR(100) NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
) ENGINE=InnoDB;

CREATE TABLE password_reset_tokens (
  email VARCHAR(255) NOT NULL PRIMARY KEY,
  token VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NULL
) ENGINE=InnoDB;

CREATE TABLE sessions (
  id VARCHAR(255) NOT NULL PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  ip_address VARCHAR(45) NULL,
  user_agent TEXT NULL,
  payload LONGTEXT NOT NULL,
  last_activity INT NOT NULL,
  INDEX sessions_user_id_index (user_id),
  INDEX sessions_last_activity_index (last_activity)
) ENGINE=InnoDB;

CREATE TABLE cache (
  `key` VARCHAR(255) NOT NULL PRIMARY KEY,
  value MEDIUMTEXT NOT NULL,
  expiration INT NOT NULL,
  INDEX cache_expiration_index (expiration)
) ENGINE=InnoDB;

CREATE TABLE cache_locks (
  `key` VARCHAR(255) NOT NULL PRIMARY KEY,
  owner VARCHAR(255) NOT NULL,
  expiration INT NOT NULL,
  INDEX cache_locks_expiration_index (expiration)
) ENGINE=InnoDB;

CREATE TABLE jobs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  queue VARCHAR(255) NOT NULL,
  payload LONGTEXT NOT NULL,
  attempts TINYINT UNSIGNED NOT NULL,
  reserved_at INT UNSIGNED NULL,
  available_at INT UNSIGNED NOT NULL,
  created_at INT UNSIGNED NOT NULL,
  INDEX jobs_queue_index (queue)
) ENGINE=InnoDB;

CREATE TABLE job_batches (
  id VARCHAR(255) NOT NULL PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  total_jobs INT NOT NULL,
  pending_jobs INT NOT NULL,
  failed_jobs INT NOT NULL,
  failed_job_ids LONGTEXT NOT NULL,
  options MEDIUMTEXT NULL,
  cancelled_at INT NULL,
  created_at INT NOT NULL,
  finished_at INT NULL
) ENGINE=InnoDB;

CREATE TABLE failed_jobs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  uuid VARCHAR(255) NOT NULL UNIQUE,
  connection TEXT NOT NULL,
  queue TEXT NOT NULL,
  payload LONGTEXT NOT NULL,
  exception LONGTEXT NOT NULL,
  failed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. ROLES, PERMISOS Y USUARIOS (RF004, RF006, RF015)
-- ---------------------------------------------------------------------
CREATE TABLE roles (
  id_rol INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL UNIQUE,
  slug VARCHAR(50) NOT NULL UNIQUE,
  descripcion VARCHAR(255) NULL,
  nivel_jerarquico TINYINT UNSIGNED NOT NULL,
  plataforma_acceso ENUM('WEB','WEB_APP','APP_WEB') NOT NULL DEFAULT 'WEB',
  activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE permisos (
  id_permiso INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(80) NOT NULL UNIQUE,
  modulo VARCHAR(50) NOT NULL,
  descripcion VARCHAR(255) NULL,
  INDEX idx_permisos_modulo (modulo)
) ENGINE=InnoDB;

CREATE TABLE rol_permiso (
  id_rol INT UNSIGNED NOT NULL,
  id_permiso INT UNSIGNED NOT NULL,
  PRIMARY KEY (id_rol, id_permiso),
  CONSTRAINT fk_rp_rol FOREIGN KEY (id_rol) REFERENCES roles(id_rol) ON DELETE CASCADE,
  CONSTRAINT fk_rp_permiso FOREIGN KEY (id_permiso) REFERENCES permisos(id_permiso) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE usuarios (
  id_usuario INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_rol INT UNSIGNED NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  apellido VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  contrasena_hash VARCHAR(255) NOT NULL,
  estatus ENUM('PENDIENTE','ACTIVO','INACTIVO','BLOQUEADO') NOT NULL DEFAULT 'PENDIENTE',
  sede VARCHAR(20) NULL COMMENT 'san_martin | tlaxcala | ambas (alcance del coordinador)',
  intentos_fallidos TINYINT UNSIGNED NOT NULL DEFAULT 0,
  fecha_bloqueo_temporal DATETIME NULL,
  ultimo_acceso DATETIME NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_usuarios_estatus (estatus),
  CONSTRAINT fk_usuarios_rol FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
) ENGINE=InnoDB;

CREATE TABLE historial_accesos (
  id_acceso INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT UNSIGNED NULL,
  tipo_accion VARCHAR(30) NOT NULL COMMENT 'login | logout | fallo_login | recuperacion',
  fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  exitoso TINYINT(1) NOT NULL DEFAULT 1,
  INDEX idx_acc_usuario (id_usuario),
  CONSTRAINT fk_acc_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE recuperacion_contrasena (
  id_recuperacion INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT UNSIGNED NOT NULL,
  token VARCHAR(255) NOT NULL,
  fecha_solicitud DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_expiracion DATETIME NOT NULL,
  usado TINYINT(1) NOT NULL DEFAULT 0,
  INDEX idx_rec_token (token),
  CONSTRAINT fk_rec_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. VOLUNTARIOS Y ACTIVIDADES (RF010, RF014)
-- ---------------------------------------------------------------------
CREATE TABLE voluntarios (
  id_voluntario INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT UNSIGNED NOT NULL UNIQUE,
  telefono_contacto VARCHAR(20) NULL,
  fecha_nacimiento DATE NULL,
  zona_asignada VARCHAR(20) NOT NULL DEFAULT 'ambas' COMMENT 'san_martin | tlaxcala | ambas | otra',
  disponibilidad VARCHAR(20) NOT NULL DEFAULT 'bajo_demanda',
  activo TINYINT(1) NOT NULL DEFAULT 1,
  fecha_ingreso DATE NULL,
  notas TEXT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_vol_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE actividades (
  id_actividad INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(200) NOT NULL,
  descripcion TEXT NULL,
  fecha_inicio DATETIME NOT NULL,
  fecha_fin DATETIME NULL,
  zona VARCHAR(20) NOT NULL DEFAULT 'ambas' COMMENT 'san_martin | tlaxcala | ambas | otra',
  estado VARCHAR(20) NOT NULL DEFAULT 'programada' COMMENT 'planeada | programada | en_proceso | completada | cancelada',
  puntos TINYINT UNSIGNED NOT NULL DEFAULT 5 COMMENT 'Puntos por asistir (5 a 20, RF014)',
  id_responsable INT UNSIGNED NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_actividades_puntos CHECK (puntos BETWEEN 5 AND 20),
  INDEX idx_act_zona (zona),
  CONSTRAINT fk_act_responsable FOREIGN KEY (id_responsable) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE asistencia_voluntarios (
  id_asistencia INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_voluntario INT UNSIGNED NOT NULL,
  id_actividad INT UNSIGNED NOT NULL,
  fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  metodo VARCHAR(10) NOT NULL DEFAULT 'manual' COMMENT 'qr | manual',
  presente TINYINT(1) NOT NULL DEFAULT 1,
  token_qr VARCHAR(100) NULL,
  observaciones VARCHAR(255) NULL,
  INDEX idx_asis_vol_act (id_voluntario, id_actividad),
  CONSTRAINT fk_asis_voluntario FOREIGN KEY (id_voluntario) REFERENCES voluntarios(id_voluntario) ON DELETE CASCADE,
  CONSTRAINT fk_asis_actividad FOREIGN KEY (id_actividad) REFERENCES actividades(id_actividad) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. CATALOGOS, BENEFICIARIOS E INVENTARIO (RF002, RF003, RF003B)
-- ---------------------------------------------------------------------
CREATE TABLE comunidades (
  id_comunidad INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL,
  municipio VARCHAR(100) NOT NULL,
  estado VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE tipos_apoyo (
  id_tipo_apoyo INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  descripcion VARCHAR(255) NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE tipos_bien (
  id_tipo_bien INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  unidad_medida VARCHAR(30) NOT NULL,
  descripcion VARCHAR(255) NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE beneficiarios (
  id_beneficiario INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  tipo_persona VARCHAR(20) NOT NULL COMMENT 'fisica | moral',
  id_comunidad INT UNSIGNED NULL,
  direccion VARCHAR(255) NULL,
  telefono VARCHAR(20) NULL,
  estado VARCHAR(20) NOT NULL DEFAULT 'activo' COMMENT 'activo | inactivo | en_espera',
  notas TEXT NULL,
  id_usuario_registrador INT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_ben_comunidad FOREIGN KEY (id_comunidad) REFERENCES comunidades(id_comunidad) ON DELETE SET NULL,
  CONSTRAINT fk_ben_registrador FOREIGN KEY (id_usuario_registrador) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE beneficiarios_fisicos (
  id_beneficiario INT UNSIGNED NOT NULL PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  apellido VARCHAR(100) NOT NULL,
  edad TINYINT UNSIGNED NULL,
  curp VARCHAR(18) NULL UNIQUE,
  fecha_nacimiento DATE NULL,
  CONSTRAINT fk_benf_ben FOREIGN KEY (id_beneficiario) REFERENCES beneficiarios(id_beneficiario) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE beneficiarios_morales (
  id_beneficiario INT UNSIGNED NOT NULL PRIMARY KEY,
  razon_social VARCHAR(200) NOT NULL,
  rfc VARCHAR(13) NULL UNIQUE,
  CONSTRAINT fk_benm_ben FOREIGN KEY (id_beneficiario) REFERENCES beneficiarios(id_beneficiario) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE beneficiario_tipos_apoyo (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_beneficiario INT UNSIGNED NOT NULL,
  id_tipo_apoyo INT UNSIGNED NOT NULL,
  fecha_asignacion DATE NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  notas VARCHAR(255) NULL,
  CONSTRAINT fk_bta_ben FOREIGN KEY (id_beneficiario) REFERENCES beneficiarios(id_beneficiario) ON DELETE CASCADE,
  CONSTRAINT fk_bta_tipo FOREIGN KEY (id_tipo_apoyo) REFERENCES tipos_apoyo(id_tipo_apoyo)
) ENGINE=InnoDB;

CREATE TABLE inventario (
  id_inventario INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_tipo_bien INT UNSIGNED NOT NULL UNIQUE,
  cantidad_total_recibida DECIMAL(12,2) NOT NULL DEFAULT 0,
  cantidad_total_entregada DECIMAL(12,2) NOT NULL DEFAULT 0,
  ultima_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  id_usuario_actualizador INT UNSIGNED NULL,
  CONSTRAINT fk_inv_bien FOREIGN KEY (id_tipo_bien) REFERENCES tipos_bien(id_tipo_bien),
  CONSTRAINT fk_inv_usuario FOREIGN KEY (id_usuario_actualizador) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE movimientos_inventario (
  id_movimiento INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_inventario INT UNSIGNED NOT NULL,
  id_usuario INT UNSIGNED NULL,
  tipo_movimiento VARCHAR(20) NOT NULL COMMENT 'entrada | salida | ajuste',
  cantidad DECIMAL(12,2) NOT NULL,
  cantidad_anterior DECIMAL(12,2) NOT NULL DEFAULT 0,
  cantidad_posterior DECIMAL(12,2) NOT NULL DEFAULT 0,
  tipo_referencia VARCHAR(30) NULL COMMENT 'donacion | entrega | ajuste',
  id_referencia INT UNSIGNED NULL,
  motivo VARCHAR(255) NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_mov_inventario (id_inventario),
  CONSTRAINT fk_mov_inventario FOREIGN KEY (id_inventario) REFERENCES inventario(id_inventario),
  CONSTRAINT fk_mov_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. GAMIFICACION DE DONADORES, CAMPANAS Y DONACIONES EN ESPECIE
--    (RF001, RF008, RF009B, RF011A)  -- SIN donaciones economicas
-- ---------------------------------------------------------------------
CREATE TABLE niveles_gamificacion (
  id_nivel INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(50) NOT NULL,
  puntos_minimos INT NOT NULL DEFAULT 0,
  puntos_maximos INT NULL,
  descripcion VARCHAR(255) NULL,
  imagen_url VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE insignias (
  id_insignia INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  descripcion VARCHAR(255) NULL,
  puntos_otorgados INT NOT NULL DEFAULT 0,
  imagen_url VARCHAR(255) NULL,
  activa TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE campanas (
  id_campana INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_usuario_creador INT UNSIGNED NULL,
  nombre VARCHAR(200) NOT NULL,
  descripcion TEXT NULL,
  tipo_meta VARCHAR(20) NOT NULL DEFAULT 'especie' COMMENT 'especie | servicio',
  meta_cantidad DECIMAL(12,2) NULL COMMENT 'Meta de acopio en especie (barra de avance, RF009B)',
  fecha_inicio DATE NULL,
  fecha_cierre DATE NULL,
  estado VARCHAR(20) NOT NULL DEFAULT 'activa' COMMENT 'activa | pausada | cerrada | cancelada',
  imagen_url VARCHAR(255) NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_camp_creador FOREIGN KEY (id_usuario_creador) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE avances_campana (
  id_avance INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_campana INT UNSIGNED NOT NULL,
  id_usuario INT UNSIGNED NULL,
  descripcion TEXT NULL,
  porcentaje_avance DECIMAL(5,2) NOT NULL DEFAULT 0,
  cantidad_acopiada DECIMAL(12,2) NOT NULL DEFAULT 0,
  evidencia_url VARCHAR(255) NULL,
  fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_av_campana FOREIGN KEY (id_campana) REFERENCES campanas(id_campana) ON DELETE CASCADE,
  CONSTRAINT fk_av_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE donadores (
  id_donador INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT UNSIGNED NULL UNIQUE COMMENT 'Cuenta del portal de donadores (rol donador)',
  id_nivel INT UNSIGNED NULL,
  tipo_donante VARCHAR(20) NOT NULL COMMENT 'fisica | moral | grupo | anonimo',
  puntos_acumulados INT NOT NULL DEFAULT 0,
  email VARCHAR(150) NULL,
  telefono VARCHAR(20) NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_don_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
  CONSTRAINT fk_don_nivel FOREIGN KEY (id_nivel) REFERENCES niveles_gamificacion(id_nivel) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE donadores_fisicos (
  id_donador INT UNSIGNED NOT NULL PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  apellido VARCHAR(100) NOT NULL,
  curp VARCHAR(18) NULL UNIQUE,
  fecha_nacimiento DATE NULL,
  CONSTRAINT fk_donf_don FOREIGN KEY (id_donador) REFERENCES donadores(id_donador) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE donadores_morales (
  id_donador INT UNSIGNED NOT NULL PRIMARY KEY,
  razon_social VARCHAR(200) NOT NULL,
  rfc VARCHAR(13) NULL UNIQUE,
  representante_legal VARCHAR(150) NULL,
  giro_comercial VARCHAR(150) NULL,
  CONSTRAINT fk_donm_don FOREIGN KEY (id_donador) REFERENCES donadores(id_donador) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE donadores_grupos (
  id_donador INT UNSIGNED NOT NULL PRIMARY KEY,
  nombre_grupo VARCHAR(150) NOT NULL,
  representante VARCHAR(150) NULL,
  CONSTRAINT fk_dong_don FOREIGN KEY (id_donador) REFERENCES donadores(id_donador) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE donador_insignias (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_donador INT UNSIGNED NOT NULL,
  id_insignia INT UNSIGNED NOT NULL,
  fecha_obtencion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  id_usuario_asignador INT UNSIGNED NULL,
  UNIQUE KEY uq_don_ins (id_donador, id_insignia),
  CONSTRAINT fk_di_donador FOREIGN KEY (id_donador) REFERENCES donadores(id_donador) ON DELETE CASCADE,
  CONSTRAINT fk_di_insignia FOREIGN KEY (id_insignia) REFERENCES insignias(id_insignia) ON DELETE CASCADE,
  CONSTRAINT fk_di_asignador FOREIGN KEY (id_usuario_asignador) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE donaciones (
  id_donacion INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_donador INT UNSIGNED NULL,
  id_campana INT UNSIGNED NULL,
  id_usuario_registrador INT UNSIGNED NULL,
  fecha_recepcion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  estado VARCHAR(20) NOT NULL DEFAULT 'recibida' COMMENT 'recibida | verificada | rechazada',
  evidencia_url VARCHAR(255) NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_dona_donador FOREIGN KEY (id_donador) REFERENCES donadores(id_donador) ON DELETE SET NULL,
  CONSTRAINT fk_dona_campana FOREIGN KEY (id_campana) REFERENCES campanas(id_campana) ON DELETE SET NULL,
  CONSTRAINT fk_dona_registrador FOREIGN KEY (id_usuario_registrador) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE donaciones_especie (
  id_donacion INT UNSIGNED NOT NULL PRIMARY KEY,
  id_tipo_bien INT UNSIGNED NOT NULL,
  descripcion VARCHAR(255) NULL,
  cantidad DECIMAL(12,2) NOT NULL,
  unidad_medida VARCHAR(30) NOT NULL,
  CONSTRAINT fk_de_donacion FOREIGN KEY (id_donacion) REFERENCES donaciones(id_donacion) ON DELETE CASCADE,
  CONSTRAINT fk_de_bien FOREIGN KEY (id_tipo_bien) REFERENCES tipos_bien(id_tipo_bien)
) ENGINE=InnoDB;

CREATE TABLE reconocimientos (
  id_reconocimiento INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_donador INT UNSIGNED NULL,
  id_usuario INT UNSIGNED NULL,
  id_campana INT UNSIGNED NULL,
  tipo VARCHAR(50) NOT NULL,
  descripcion VARCHAR(255) NULL,
  folio VARCHAR(30) NULL UNIQUE COMMENT 'Folio oficial del diploma PDF (RF011A)',
  archivo_pdf_url VARCHAR(255) NULL,
  fecha_emision DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  id_usuario_emisor INT UNSIGNED NULL,
  otorgado_por VARCHAR(150) NULL,
  CONSTRAINT fk_recon_donador FOREIGN KEY (id_donador) REFERENCES donadores(id_donador) ON DELETE SET NULL,
  CONSTRAINT fk_recon_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
  CONSTRAINT fk_recon_campana FOREIGN KEY (id_campana) REFERENCES campanas(id_campana) ON DELETE SET NULL,
  CONSTRAINT fk_recon_emisor FOREIGN KEY (id_usuario_emisor) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6. ENTREGAS Y ASIGNACION DE VOLUNTARIOS (RF003)
-- ---------------------------------------------------------------------
CREATE TABLE entregas (
  id_entrega INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_donacion INT UNSIGNED NOT NULL,
  id_beneficiario INT UNSIGNED NOT NULL,
  id_usuario_responsable INT UNSIGNED NULL,
  cantidad_entregada DECIMAL(12,2) NOT NULL,
  fecha_entrega DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  evidencia_url VARCHAR(255) NULL,
  estado VARCHAR(20) NOT NULL DEFAULT 'en_proceso' COMMENT 'en_proceso | completada | cancelada',
  observaciones TEXT NULL,
  CONSTRAINT fk_ent_donacion FOREIGN KEY (id_donacion) REFERENCES donaciones(id_donacion),
  CONSTRAINT fk_ent_beneficiario FOREIGN KEY (id_beneficiario) REFERENCES beneficiarios(id_beneficiario),
  CONSTRAINT fk_ent_responsable FOREIGN KEY (id_usuario_responsable) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE asignaciones_voluntario (
  id_asignacion INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_voluntario INT UNSIGNED NOT NULL,
  id_entrega INT UNSIGNED NOT NULL,
  id_usuario_asignador INT UNSIGNED NULL,
  fecha_asignacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_compromiso DATETIME NULL,
  estado VARCHAR(20) NOT NULL DEFAULT 'asignada' COMMENT 'asignada | aceptada | rechazada | completada | cancelada',
  observaciones_voluntario TEXT NULL,
  fecha_actualizacion DATETIME NULL,
  CONSTRAINT fk_asig_voluntario FOREIGN KEY (id_voluntario) REFERENCES voluntarios(id_voluntario) ON DELETE CASCADE,
  CONSTRAINT fk_asig_entrega FOREIGN KEY (id_entrega) REFERENCES entregas(id_entrega) ON DELETE CASCADE,
  CONSTRAINT fk_asig_asignador FOREIGN KEY (id_usuario_asignador) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 7. NOTIFICACIONES Y RESPALDOS (RF012)
-- ---------------------------------------------------------------------
CREATE TABLE tipos_referencia_notif (
  id_tipo_ref INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(50) NOT NULL,
  descripcion VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE notificaciones (
  id_notificacion INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT UNSIGNED NULL,
  destinatario_email VARCHAR(150) NULL,
  tipo VARCHAR(30) NOT NULL DEFAULT 'sistema',
  canal VARCHAR(20) NOT NULL DEFAULT 'web' COMMENT 'web | email',
  asunto VARCHAR(200) NOT NULL,
  mensaje TEXT NOT NULL,
  enviado_email TINYINT(1) NOT NULL DEFAULT 0,
  leida TINYINT(1) NOT NULL DEFAULT 0,
  id_referencia INT UNSIGNED NULL,
  id_tipo_referencia INT UNSIGNED NULL,
  fecha_envio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_not_usuario (id_usuario, leida),
  CONSTRAINT fk_not_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
  CONSTRAINT fk_not_tipo_ref FOREIGN KEY (id_tipo_referencia) REFERENCES tipos_referencia_notif(id_tipo_ref) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE respaldos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  tipo_operacion VARCHAR(20) NOT NULL COMMENT 'EXPORTACION | IMPORTACION',
  nombre_archivo VARCHAR(255) NOT NULL,
  formato VARCHAR(10) NOT NULL DEFAULT 'ZIP',
  nombre_bd VARCHAR(100) NOT NULL,
  tamanio_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
  usuario_id INT UNSIGNED NULL,
  fechayhora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  observaciones VARCHAR(255) NULL,
  CONSTRAINT fk_resp_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- DATOS INICIALES Y DE PRUEBA
-- =====================================================================
USE iskali;
SET NAMES utf8mb4;

INSERT INTO migrations (migration, batch) VALUES
 ('0001_01_01_000000_create_users_table', 1),
 ('0001_01_01_000001_create_cache_table', 1),
 ('0001_01_01_000002_create_jobs_table', 1);

-- ---- Roles (v6.0) ----
INSERT INTO roles (id_rol, nombre, slug, descripcion, nivel_jerarquico, plataforma_acceso) VALUES
 (1,'SuperAdmin / Sistemas','superadmin','Control tecnico: usuarios, RBAC, respaldos y bitacora',1,'WEB'),
 (2,'Directiva (Presidenta/Vicepresidenta)','directiva','Aprobacion de voluntarios, CMS, reportes y supervision en campo',2,'WEB_APP'),
 (3,'Tesoreria / Almacen','tesoreria','Donaciones en especie, inventario, beneficiarios, entregas y donadores',3,'WEB'),
 (4,'Coordinador Operativo','coordinador','Planning, pase de lista QR/PIN y evidencias en campo',4,'WEB_APP'),
 (5,'Voluntario / Practicante','voluntario','Credencial QR, Doble Bolsa de puntos y actividades',5,'APP_WEB'),
 (6,'Donador / Mecenas','donador','Portal de mecenazgo: historial en especie y diplomas',6,'WEB');

-- ---- Permisos ----
INSERT INTO permisos (slug, modulo, descripcion) VALUES
 ('users.view','Usuarios','Ver listado de usuarios'),
 ('users.view_zone','Usuarios','Ver voluntarios de su sede asignada'),
 ('users.create','Usuarios','Crear nuevos usuarios'),
 ('users.approve','Usuarios','Aprobar registros de voluntarios (PENDIENTE -> ACTIVO)'),
 ('users.block','Usuarios','Bloquear, desbloquear o desactivar usuarios'),
 ('roles.manage','Sistema','Administrar roles y permisos (RBAC)'),
 ('donations.create','Donaciones','Registrar entradas de donaciones en especie'),
 ('donations.view_own','Donaciones','Consultar el historial de sus propias aportaciones'),
 ('donors.manage','Donadores','Gestionar el padron de donadores fisicos y morales'),
 ('certificates.download','Donadores','Descargar sus propios diplomas PDF con folio'),
 ('inventory.view','Inventario','Consultar existencias de alimentos, ropa, medicinas y utiles'),
 ('inventory.adjust','Inventario','Registrar ajustes de stock (mermas, caducidades, reingresos)'),
 ('beneficiaries.manage','Beneficiarios','Gestion del padron de beneficiarios (CURP/ID)'),
 ('deliveries.create','Entregas','Registrar salida de apoyos con descuento de stock'),
 ('deliveries.view','Entregas','Consultar apoyos asignados y adjuntar evidencia'),
 ('campaigns.manage','Campanas','Gestionar campanas de acopio en especie'),
 ('events.create','Planning','Crear actividades en Planning (5 a 20 pts)'),
 ('events.edit','Planning','Modificar o cancelar jornadas programadas'),
 ('events.subscribe','Voluntarios','Inscribirse a convocatorias regionales'),
 ('attendance.scan','PaseLista','Escanear QR o ingresar PIN de 6 digitos'),
 ('evidence.upload','Evidencias','Subir fotografias de jornadas en modo offline/online'),
 ('qr.generate','Voluntarios','Generar credencial digital dinamica QR'),
 ('points.view','Voluntarios','Consultar saldo en Doble Bolsa de Puntos'),
 ('points.redeem','Voluntarios','Solicitar canje de productos del catalogo merch'),
 ('penalties.manage','Gamificacion','Aplicar penalizaciones y ajustes de puntos'),
 ('rewards.manage','Gamificacion','Administrar catalogo de premios y aprobar canjes'),
 ('notifications.send','Notificaciones','Enviar notificaciones y avisos'),
 ('cms.manage','CMS','Administrar la Landing Page'),
 ('reports.generate','Reportes','Generar reportes consolidados en PDF'),
 ('backups.manage','Sistema','Gestionar respaldos y politica RN-BD-02'),
 ('audit.view','Sistema','Consultar la bitacora de seguridad');

-- ---- Permisos por rol (explicitos, sin herencia) ----
INSERT INTO rol_permiso (id_rol, id_permiso)
 SELECT 1, id_permiso FROM permisos;

INSERT INTO rol_permiso (id_rol, id_permiso)
 SELECT 2, id_permiso FROM permisos WHERE slug IN
 ('users.view','users.approve','users.block','cms.manage','reports.generate','events.create','events.edit',
  'attendance.scan','evidence.upload','inventory.view','deliveries.view','campaigns.manage',
  'penalties.manage','rewards.manage','notifications.send');

INSERT INTO rol_permiso (id_rol, id_permiso)
 SELECT 3, id_permiso FROM permisos WHERE slug IN
 ('donations.create','donors.manage','inventory.view','inventory.adjust','beneficiaries.manage',
  'deliveries.create','deliveries.view','reports.generate');

INSERT INTO rol_permiso (id_rol, id_permiso)
 SELECT 4, id_permiso FROM permisos WHERE slug IN
 ('users.view_zone','events.create','events.edit','attendance.scan','evidence.upload','inventory.view',
  'deliveries.view','campaigns.manage','penalties.manage','notifications.send');

INSERT INTO rol_permiso (id_rol, id_permiso)
 SELECT 5, id_permiso FROM permisos WHERE slug IN
 ('qr.generate','points.view','points.redeem','events.subscribe');

INSERT INTO rol_permiso (id_rol, id_permiso)
 SELECT 6, id_permiso FROM permisos WHERE slug IN
 ('certificates.download','donations.view_own');

-- ---- Usuarios de prueba (2 por rol) | Contrasena: Iskali2026! ----
INSERT INTO usuarios (id_usuario, id_rol, nombre, apellido, email, contrasena_hash, estatus, sede) VALUES
 (1, 1,'Sistemas','Uno','superadmin1@iskali.test','$2y$10$uT3.EKy/rylwdD2fg3Sz7.fQd2yPvbqKrFMXXpGADsZ3o4tveBm5O','ACTIVO',NULL),
 (2, 1,'Sistemas','Dos','superadmin2@iskali.test','$2y$10$uT3.EKy/rylwdD2fg3Sz7.fQd2yPvbqKrFMXXpGADsZ3o4tveBm5O','ACTIVO',NULL),
 (3, 2,'Presidenta','Prueba','directiva1@iskali.test','$2y$10$uT3.EKy/rylwdD2fg3Sz7.fQd2yPvbqKrFMXXpGADsZ3o4tveBm5O','ACTIVO',NULL),
 (4, 2,'Vicepresidenta','Prueba','directiva2@iskali.test','$2y$10$uT3.EKy/rylwdD2fg3Sz7.fQd2yPvbqKrFMXXpGADsZ3o4tveBm5O','ACTIVO',NULL),
 (5, 3,'Tesorera','Prueba','tesoreria1@iskali.test','$2y$10$uT3.EKy/rylwdD2fg3Sz7.fQd2yPvbqKrFMXXpGADsZ3o4tveBm5O','ACTIVO',NULL),
 (6, 3,'Almacenista','Prueba','tesoreria2@iskali.test','$2y$10$uT3.EKy/rylwdD2fg3Sz7.fQd2yPvbqKrFMXXpGADsZ3o4tveBm5O','ACTIVO',NULL),
 (7, 4,'Coordinador','Texmelucan','coordinador1@iskali.test','$2y$10$uT3.EKy/rylwdD2fg3Sz7.fQd2yPvbqKrFMXXpGADsZ3o4tveBm5O','ACTIVO','san_martin'),
 (8, 4,'Coordinadora','Tlaxcala','coordinador2@iskali.test','$2y$10$uT3.EKy/rylwdD2fg3Sz7.fQd2yPvbqKrFMXXpGADsZ3o4tveBm5O','ACTIVO','tlaxcala'),
 (9, 5,'Voluntario','Activo','voluntario1@iskali.test','$2y$10$uT3.EKy/rylwdD2fg3Sz7.fQd2yPvbqKrFMXXpGADsZ3o4tveBm5O','ACTIVO',NULL),
 (10,5,'Voluntaria','Pendiente','voluntario2@iskali.test','$2y$10$uT3.EKy/rylwdD2fg3Sz7.fQd2yPvbqKrFMXXpGADsZ3o4tveBm5O','PENDIENTE',NULL),
 (11,6,'Donadora','Persona','donador1@iskali.test','$2y$10$uT3.EKy/rylwdD2fg3Sz7.fQd2yPvbqKrFMXXpGADsZ3o4tveBm5O','ACTIVO',NULL),
 (12,6,'Donador','Persona','donador2@iskali.test','$2y$10$uT3.EKy/rylwdD2fg3Sz7.fQd2yPvbqKrFMXXpGADsZ3o4tveBm5O','ACTIVO',NULL);

INSERT INTO historial_accesos (id_usuario, tipo_accion, ip_address, user_agent, exitoso) VALUES
 (1,'login','127.0.0.1','Mozilla/5.0 (prueba)',1),
 (10,'fallo_login','127.0.0.1','Mozilla/5.0 (prueba)',0);

INSERT INTO recuperacion_contrasena (id_usuario, token, fecha_expiracion, usado) VALUES
 (9,'token-prueba-activo-0001', DATE_ADD(NOW(), INTERVAL 60 MINUTE), 0),
 (11,'token-prueba-usado-0002', DATE_ADD(NOW(), INTERVAL 60 MINUTE), 1);

-- ---- Voluntarios ----
INSERT INTO voluntarios (id_voluntario, id_usuario, telefono_contacto, fecha_nacimiento, zona_asignada, disponibilidad, fecha_ingreso, notas) VALUES
 (1, 9,'2481000001','2003-05-14','san_martin','fines_semana','2026-09-01','Voluntario de prueba activo'),
 (2,10,'2481000002','2004-11-20','tlaxcala','bajo_demanda','2026-11-10','Voluntaria de prueba con registro pendiente');

-- ---- Actividades (Planning) y asistencia ----
INSERT INTO actividades (id_actividad, titulo, descripcion, fecha_inicio, fecha_fin, zona, estado, puntos, id_responsable) VALUES
 (1,'Reforestacion comunitaria','Jornada de reforestacion en San Martin Texmelucan','2026-12-05 09:00:00','2026-12-05 14:00:00','san_martin','programada',10,7),
 (2,'Donacion de despensas','Entrega de despensas en Tlaxcala','2026-12-12 10:00:00','2026-12-12 15:00:00','tlaxcala','programada',15,8);

INSERT INTO asistencia_voluntarios (id_voluntario, id_actividad, metodo, presente, token_qr, observaciones) VALUES
 (1,1,'qr',1,'QR-PRUEBA-0001','Llego puntual'),
 (2,1,'manual',1,NULL,'Registrada con PIN de 6 digitos');

-- ---- Catalogos, beneficiarios e inventario ----
INSERT INTO comunidades (id_comunidad, nombre, municipio, estado) VALUES
 (1,'Santa Rosa de Viterbo','San Martin Texmelucan','Puebla'),
 (2,'Centro','Tlaxcala','Tlaxcala');

INSERT INTO tipos_apoyo (id_tipo_apoyo, nombre, descripcion) VALUES
 (1,'Despensa','Apoyo alimentario basico'),
 (2,'Ropa','Prendas limpias y en buen estado');

INSERT INTO tipos_bien (id_tipo_bien, nombre, unidad_medida, descripcion) VALUES
 (1,'Arroz','kg','Alimento no perecedero'),
 (2,'Playeras','pieza','Ropa en buen estado');

INSERT INTO beneficiarios (id_beneficiario, tipo_persona, id_comunidad, direccion, telefono, estado, notas, id_usuario_registrador) VALUES
 (1,'fisica',1,'Calle Reforma 10','2481111111','activo','Prueba persona fisica 1',5),
 (2,'fisica',2,'Av. Juarez 25','2461111111','activo','Prueba persona fisica 2',5),
 (3,'moral',1,'Calle Hidalgo 5','2481222222','activo','Prueba organizacion 1',5),
 (4,'moral',2,'Calle Morelos 18','2461222222','activo','Prueba organizacion 2',5);

INSERT INTO beneficiarios_fisicos (id_beneficiario, nombre, apellido, edad, curp, fecha_nacimiento) VALUES
 (1,'Maria','Lopez Perez',34,'LOPM850720MPLPRR05','1985-07-20'),
 (2,'Juan','Garcia Hernandez',52,'GAHJ740315HTLRRN09','1974-03-15');

INSERT INTO beneficiarios_morales (id_beneficiario, razon_social, rfc) VALUES
 (3,'Casa Hogar Esperanza A.C.','CHE101010AB1'),
 (4,'Comedor Comunitario Tlaxcala A.C.','CCT151515XY2');

INSERT INTO beneficiario_tipos_apoyo (id_beneficiario, id_tipo_apoyo, fecha_asignacion, notas) VALUES
 (1,1,'2026-10-01','Despensa mensual'),
 (3,2,'2026-10-15','Ropa para la casa hogar');

-- ---- Gamificacion de donadores ----
INSERT INTO niveles_gamificacion (id_nivel, nombre, puntos_minimos, puntos_maximos, descripcion) VALUES
 (1,'Bronce',0,99,'Nivel inicial de mecenazgo'),
 (2,'Plata',100,499,'Aportaciones constantes'),
 (3,'Oro',500,999,'Aportaciones destacadas'),
 (4,'Diamante',1000,NULL,'Maximo nivel de mecenazgo');

INSERT INTO insignias (id_insignia, nombre, descripcion, puntos_otorgados) VALUES
 (1,'Primera donacion','Registro de su primera aportacion en especie',20),
 (2,'Aliado constante','Tres aportaciones verificadas',50);

-- ---- Campanas ----
INSERT INTO campanas (id_campana, id_usuario_creador, nombre, descripcion, tipo_meta, meta_cantidad, fecha_inicio, fecha_cierre, estado) VALUES
 (1,3,'Acopio de alimentos 2026','Campana de acopio de alimentos no perecederos','especie',500.00,'2026-11-01','2026-12-31','activa'),
 (2,7,'Re-Vistete','Campana de acopio de ropa y calzado','especie',200.00,'2026-11-01','2026-12-15','activa');

INSERT INTO avances_campana (id_campana, id_usuario, descripcion, porcentaje_avance, cantidad_acopiada) VALUES
 (1,3,'Primer corte de acopio',10.00,50.00),
 (2,7,'Primer corte de acopio',15.00,30.00);

-- ---- Donadores (1-2 fisicos con cuenta, 3-4 morales, 5-6 grupos) ----
INSERT INTO donadores (id_donador, id_usuario, id_nivel, tipo_donante, puntos_acumulados, email, telefono) VALUES
 (1,11,1,'fisica',50,'donador1@iskali.test','2481333331'),
 (2,12,2,'fisica',150,'donador2@iskali.test','2481333332'),
 (3,NULL,3,'moral',600,'contacto@panaderia.test','2481333333'),
 (4,NULL,4,'moral',1200,'contacto@distribuidora.test','2481333334'),
 (5,NULL,1,'grupo',20,'grupo1@iskali.test','2481333335'),
 (6,NULL,1,'grupo',90,'grupo2@iskali.test','2481333336');

INSERT INTO donadores_fisicos (id_donador, nombre, apellido, curp, fecha_nacimiento) VALUES
 (1,'Rosa','Sanchez Garcia','SAGR920505MPLNRS01','1992-05-05'),
 (2,'Mario','Torres Lopez','TOLM880930HPLRRR04','1988-09-30');

INSERT INTO donadores_morales (id_donador, razon_social, rfc, representante_legal, giro_comercial) VALUES
 (3,'Panaderia La Espiga S.A. de C.V.','PLE090909KL3','Carlos Mendez','Alimentos'),
 (4,'Distribuidora del Valle S.A. de C.V.','DDV121212MN4','Laura Ramirez','Abarrotes');

INSERT INTO donadores_grupos (id_donador, nombre_grupo, representante) VALUES
 (5,'Grupo Juvenil Parroquial','Ana Flores'),
 (6,'Colectivo Vecinal Centro','Pedro Cruz');

INSERT INTO donador_insignias (id_donador, id_insignia, id_usuario_asignador) VALUES
 (1,1,3),
 (3,2,3);

-- ---- Donaciones en especie (sin economicas) ----
INSERT INTO donaciones (id_donacion, id_donador, id_campana, id_usuario_registrador, estado) VALUES
 (1,1,1,5,'verificada'),
 (2,3,2,5,'verificada');

INSERT INTO donaciones_especie (id_donacion, id_tipo_bien, descripcion, cantidad, unidad_medida) VALUES
 (1,1,'Arroz en bolsas de 1 kg',50.00,'kg'),
 (2,2,'Playeras limpias tallas variadas',30.00,'pieza');

INSERT INTO inventario (id_inventario, id_tipo_bien, cantidad_total_recibida, cantidad_total_entregada, id_usuario_actualizador) VALUES
 (1,1,50.00,10.00,5),
 (2,2,30.00,0.00,5);

INSERT INTO movimientos_inventario (id_inventario, id_usuario, tipo_movimiento, cantidad, cantidad_anterior, cantidad_posterior, tipo_referencia, id_referencia, motivo) VALUES
 (1,5,'entrada',50.00,0.00,50.00,'donacion',1,'Recepcion de donacion verificada'),
 (1,5,'salida',10.00,50.00,40.00,'entrega',1,'Entrega de apoyo a beneficiario');

-- ---- Entregas y asignacion de voluntarios ----
INSERT INTO entregas (id_entrega, id_donacion, id_beneficiario, id_usuario_responsable, cantidad_entregada, estado, observaciones) VALUES
 (1,1,1,7,10.00,'completada','Entrega de prueba completada'),
 (2,2,3,7,5.00,'en_proceso','Entrega de prueba en proceso');

INSERT INTO asignaciones_voluntario (id_voluntario, id_entrega, id_usuario_asignador, fecha_compromiso, estado) VALUES
 (1,1,7,'2026-11-20 10:00:00','completada'),
 (1,2,7,'2026-11-25 10:00:00','asignada');

INSERT INTO reconocimientos (id_donador, id_campana, tipo, descripcion, folio, id_usuario_emisor, otorgado_por) VALUES
 (3,2,'diploma','Diploma de agradecimiento por donacion de ropa','ISK-2026-0001',3,'Directiva Juventud Iskali'),
 (4,1,'diploma','Diploma de agradecimiento por apoyo constante','ISK-2026-0002',3,'Directiva Juventud Iskali');

-- ---- Notificaciones y respaldos ----
INSERT INTO tipos_referencia_notif (id_tipo_ref, nombre, descripcion) VALUES
 (1,'donadores','Avisos ligados a donadores'),
 (2,'beneficiarios','Avisos ligados a beneficiarios'),
 (3,'campanas','Avisos ligados a campanas');

INSERT INTO notificaciones (id_usuario, destinatario_email, tipo, canal, asunto, mensaje, id_referencia, id_tipo_referencia) VALUES
 (3,'directiva1@iskali.test','sistema','web','Nuevo registro de voluntario','Hay un registro de voluntario pendiente de aprobacion.',10,NULL),
 (11,'donador1@iskali.test','sistema','web','Donacion verificada','Tu donacion de arroz fue verificada. Gracias.',1,1);

INSERT INTO respaldos (tipo_operacion, nombre_archivo, formato, nombre_bd, tamanio_bytes, usuario_id, observaciones) VALUES
 ('EXPORTACION','respaldo_prueba_1.zip','ZIP','iskali',20480,1,'Respaldo de prueba 1'),
 ('EXPORTACION','respaldo_prueba_2.zip','ZIP','iskali',21504,1,'Respaldo de prueba 2');

USE iskali;
INSERT INTO permisos (slug, modulo, descripcion)
VALUES ('dashboard.view', 'Dashboard', 'Ver el panel principal del sistema web');
INSERT INTO rol_permiso (id_rol, id_permiso)
SELECT r.id_rol, p.id_permiso FROM roles r
JOIN permisos p ON p.slug = 'dashboard.view'
WHERE r.slug IN ('superadmin','directiva','tesoreria','coordinador');

USE iskali;
ALTER TABLE donaciones MODIFY estado VARCHAR(20) NOT NULL DEFAULT 'pendiente'
  COMMENT 'pendiente | recibida | verificada | rechazada';
  
USE iskali;
SET NAMES utf8mb4;

-- ===== 1. CORRECCIONES =====
UPDATE donadores SET tipo_donante = 'persona'      WHERE tipo_donante = 'fisica';
UPDATE donadores SET tipo_donante = 'organizacion' WHERE tipo_donante = 'moral';
ALTER TABLE donadores MODIFY tipo_donante VARCHAR(20) NOT NULL
  COMMENT 'persona | organizacion | grupo | anonimo';
ALTER TABLE donaciones MODIFY estado VARCHAR(20) NOT NULL DEFAULT 'pendiente'
  COMMENT 'pendiente | recibida | verificada | rechazada';

-- ===== 2. DATOS DE EJEMPLO PARA EL DASHBOARD =====
INSERT INTO tipos_bien (id_tipo_bien, nombre, unidad_medida, descripcion) VALUES
 (3,'Zapatos','par','Calzado en buen estado'),
 (4,'Medicamentos','caja','Medicamentos no caducados'),
 (5,'Despensas','pieza','Despensas armadas'),
 (6,'Utiles escolares','kit','Kits de material escolar');

INSERT INTO campanas (id_campana, id_usuario_creador, nombre, descripcion, tipo_meta, meta_cantidad, fecha_inicio, fecha_cierre, estado) VALUES
 (3,3,'Medicina para todos','Acopio de medicamentos y material de primeros auxilios','especie',100.00,'2026-05-01','2026-12-31','activa'),
 (4,7,'Utiles escolares 2026','Acopio de kits escolares para el ciclo','especie',80.00,'2026-08-15','2026-12-20','activa');

INSERT INTO donadores (id_donador, id_usuario, id_nivel, tipo_donante, puntos_acumulados, email, telefono) VALUES
 (7,NULL,1,'anonimo',0,NULL,NULL);

INSERT INTO donaciones (id_donacion, id_donador, id_campana, id_usuario_registrador, fecha_recepcion, estado) VALUES
 (3, 2,1,5,'2026-03-12 10:00:00','verificada'),
 (4, 4,1,5,'2026-04-08 11:30:00','verificada'),
 (5, 3,2,5,'2026-05-15 09:45:00','verificada'),
 (6, 5,3,5,'2026-06-20 12:00:00','verificada'),
 (7, 6,1,5,'2026-08-05 16:20:00','verificada'),
 (8, 1,4,5,'2026-09-18 10:10:00','recibida'),
 (9, 2,2,5,'2026-10-10 13:00:00','recibida'),
 (10,4,3,5,'2026-10-28 15:40:00','recibida'),
 (11,3,1,5,'2026-11-12 09:00:00','pendiente'),
 (12,7,2,5,'2026-11-15 11:15:00','pendiente'),
 (13,5,4,5,'2026-07-22 10:30:00','rechazada');

INSERT INTO donaciones_especie (id_donacion, id_tipo_bien, descripcion, cantidad, unidad_medida) VALUES
 (3, 1,'Arroz en bolsas de 1 kg',80.00,'kg'),
 (4, 3,'Zapatos escolares y casuales',25.00,'par'),
 (5, 2,'Playeras tallas variadas',60.00,'pieza'),
 (6, 4,'Analgesicos y material de curacion',40.00,'caja'),
 (7, 5,'Despensas basicas',120.00,'pieza'),
 (8, 6,'Kits con cuadernos y colores',35.00,'kit'),
 (9, 2,'Playeras nuevas',45.00,'pieza'),
 (10,4,'Medicamentos de venta libre',20.00,'caja'),
 (11,1,'Arroz',30.00,'kg'),
 (12,3,'Zapatos de adulto',10.00,'par'),
 (13,6,'Kits incompletos',15.00,'kit');

UPDATE inventario SET cantidad_total_recibida = 130.00 WHERE id_inventario = 1;
UPDATE inventario SET cantidad_total_recibida = 90.00  WHERE id_inventario = 2;
INSERT INTO inventario (id_inventario, id_tipo_bien, cantidad_total_recibida, cantidad_total_entregada, id_usuario_actualizador) VALUES
 (3,3,25.00,0.00,5),
 (4,4,40.00,10.00,5),
 (5,5,120.00,60.00,5),
 (6,6,0.00,0.00,5);

INSERT INTO beneficiarios (id_beneficiario, tipo_persona, id_comunidad, direccion, telefono, estado, notas, id_usuario_registrador) VALUES
 (5,'fisica',1,'Calle Allende 7','2481444441','activo','Ejemplo dashboard',5),
 (6,'fisica',2,'Calle Lerdo 12','2461444442','en_espera','Ejemplo dashboard',5),
 (7,'fisica',1,'Calle Victoria 3','2481444443','en_espera','Ejemplo dashboard',5),
 (8,'fisica',2,'Calle Zaragoza 9','2461444444','inactivo','Ejemplo dashboard',5),
 (9,'moral',1,'Av. Mexico 100','2481444445','activo','Ejemplo dashboard',5);
INSERT INTO beneficiarios_fisicos (id_beneficiario, nombre, apellido, edad, curp, fecha_nacimiento) VALUES
 (5,'Lucia','Ramirez Soto',41,'RASL831105MPLMTC02','1983-11-05'),
 (6,'Pedro','Cruz Mendoza',29,'CUMP970214HTLRND06','1997-02-14'),
 (7,'Ana','Flores Vega',63,'FOVA610830MPLLGN03','1961-08-30'),
 (8,'Jose','Mora Diaz',48,'MODJ780422HTLRZS07','1978-04-22');
INSERT INTO beneficiarios_morales (id_beneficiario, razon_social, rfc) VALUES
 (9,'Albergue San Jose A.C.','ASJ080808QR5');

INSERT INTO entregas (id_entrega, id_donacion, id_beneficiario, id_usuario_responsable, cantidad_entregada, fecha_entrega, estado, observaciones) VALUES
 (3,3,2,7,25.00,'2026-10-20 11:00:00','completada','Ejemplo dashboard'),
 (4,5,5,7,20.00,'2026-11-05 10:00:00','completada','Ejemplo dashboard'),
 (5,7,9,8,60.00,'2026-11-14 12:30:00','completada','Ejemplo dashboard');

UPDATE donaciones    SET created_at = fecha_recepcion WHERE id_donacion > 2;
UPDATE beneficiarios SET created_at = DATE_SUB(NOW(), INTERVAL (id_beneficiario * 4) HOUR);
UPDATE donadores     SET created_at = DATE_SUB(NOW(), INTERVAL (id_donador * 7) HOUR);

USE iskali;
UPDATE actividades SET estado = 'planeada' WHERE estado = 'programada';
ALTER TABLE actividades MODIFY estado VARCHAR(20) NOT NULL DEFAULT 'planeada'
  COMMENT 'planeada | en_curso | completada | cancelada';

USE iskali;
SET NAMES utf8mb4;

-- ===== DOBLE BOLSA DE PUNTOS (RF011B) =====
CREATE TABLE bolsa_voluntario (
  id_bolsa INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_voluntario INT UNSIGNED NOT NULL UNIQUE,
  puntos_canjeables INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Bolsa 1: se canjea por premios',
  puntos_campamento INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Bolsa 2: historico para campamentos (no baja con canjes)',
  faltas_injustificadas TINYINT UNSIGNED NOT NULL DEFAULT 0,
  campamento_bloqueado TINYINT(1) NOT NULL DEFAULT 0,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_bolsa_voluntario FOREIGN KEY (id_voluntario) REFERENCES voluntarios(id_voluntario) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE cat_premios (
  id_premio INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  descripcion VARCHAR(255) NULL,
  costo_puntos INT UNSIGNED NOT NULL,
  stock INT UNSIGNED NOT NULL DEFAULT 0,
  imagen_url VARCHAR(255) NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE canjes_premios (
  id_canje INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_voluntario INT UNSIGNED NOT NULL,
  id_premio INT UNSIGNED NOT NULL,
  puntos_usados INT UNSIGNED NOT NULL,
  estado VARCHAR(20) NOT NULL DEFAULT 'pendiente' COMMENT 'pendiente | entregado | cancelado',
  fecha_solicitud DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_entrega DATETIME NULL,
  id_usuario_registra INT UNSIGNED NULL,
  observaciones VARCHAR(255) NULL,
  INDEX idx_canje_estado (estado),
  CONSTRAINT fk_canje_voluntario FOREIGN KEY (id_voluntario) REFERENCES voluntarios(id_voluntario),
  CONSTRAINT fk_canje_premio FOREIGN KEY (id_premio) REFERENCES cat_premios(id_premio),
  CONSTRAINT fk_canje_usuario FOREIGN KEY (id_usuario_registra) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE movimientos_puntos (
  id_movimiento_puntos INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_voluntario INT UNSIGNED NOT NULL,
  tipo VARCHAR(20) NOT NULL COMMENT 'asistencia | canje | penalizacion | ajuste',
  puntos_canjeables INT NOT NULL DEFAULT 0 COMMENT 'Cambio en Bolsa 1 (puede ser negativo)',
  puntos_campamento INT NOT NULL DEFAULT 0 COMMENT 'Cambio en Bolsa 2 (puede ser negativo)',
  id_actividad INT UNSIGNED NULL,
  id_canje INT UNSIGNED NULL,
  motivo VARCHAR(255) NULL,
  id_usuario INT UNSIGNED NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_mov_actividad (id_voluntario, id_actividad, tipo),
  INDEX idx_mov_voluntario (id_voluntario, fecha),
  CONSTRAINT fk_movp_voluntario FOREIGN KEY (id_voluntario) REFERENCES voluntarios(id_voluntario) ON DELETE CASCADE,
  CONSTRAINT fk_movp_actividad FOREIGN KEY (id_actividad) REFERENCES actividades(id_actividad) ON DELETE SET NULL,
  CONSTRAINT fk_movp_canje FOREIGN KEY (id_canje) REFERENCES canjes_premios(id_canje) ON DELETE SET NULL,
  CONSTRAINT fk_movp_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ===== DATOS DE EJEMPLO (2 por tabla) =====
INSERT INTO cat_premios (id_premio, nombre, descripcion, costo_puntos, stock) VALUES
 (1,'Playera Iskali','Playera oficial del colectivo',20,29),
 (2,'Termo Iskali','Termo con logotipo del colectivo',35,19);

INSERT INTO bolsa_voluntario (id_voluntario, puntos_canjeables, puntos_campamento) VALUES
 (1,5,120),
 (2,10,10);

INSERT INTO canjes_premios (id_canje, id_voluntario, id_premio, puntos_usados, estado, fecha_entrega, id_usuario_registra, observaciones) VALUES
 (1,1,1,20,'entregado','2026-11-10 12:00:00',3,'Entrega en jornada'),
 (2,1,2,35,'pendiente',NULL,3,'Pendiente de entrega');

INSERT INTO movimientos_puntos (id_voluntario, tipo, puntos_canjeables, puntos_campamento, id_actividad, id_canje, motivo, id_usuario) VALUES
 (1,'asistencia',10,10,1,NULL,'Asistencia a actividad',7),
 (1,'ajuste',50,110,NULL,NULL,'Puntos de ejemplo',3),
 (1,'canje',-20,0,NULL,1,'Canje: Playera Iskali',3),
 (1,'canje',-35,0,NULL,2,'Canje: Termo Iskali',3),
 (2,'asistencia',10,10,1,NULL,'Asistencia a actividad',7);