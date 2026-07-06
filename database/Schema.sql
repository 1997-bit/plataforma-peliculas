DROP DATABASE IF EXISTS cineapp;

CREATE DATABASE cineapp 
  CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

USE cineapp;

CREATE TABLE usuarios (
  id BINARY(16) PRIMARY KEY,
  correo VARCHAR(500) NOT NULL,
  correo_hash CHAR(64) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  nombre_usuario VARCHAR(500) NOT NULL,
  rol ENUM('user','admin') NOT NULL DEFAULT 'user',
  preferencias JSON,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_correo_hash (correo_hash),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE generos (
  id SMALLINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  nombre VARCHAR(50) NOT NULL UNIQUE,
  tmdb_id INT UNIQUE,
  INDEX idx_tmdb_id (tmdb_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE contenido (
  id BINARY(16) PRIMARY KEY,
  tmdb_id INT UNIQUE,
  -- 'tmdb' = vino de la API (lazy seed). 'local' = lo creo un admin a mano,
  -- sin tmdb_id real. Es lo que permite mezclar ambos en home/recomendaciones
  -- sin que el resto del codigo (que asume tmdb_id) se rompa.
  origen ENUM('tmdb','local') NOT NULL DEFAULT 'tmdb',
  -- quien creo el registro si origen='local'; NULL para todo lo que viene de TMDB.
  created_by BINARY(16),
  type ENUM('movie','series') NOT NULL,
  titulo VARCHAR(255) NOT NULL,
  descripcion TEXT,
  poster_path VARCHAR(500),
  backdrop_path VARCHAR(500),
  logo_path VARCHAR(500),
  anio_lanzamiento YEAR,
  rating_avg DECIMAL(3,1) NOT NULL DEFAULT 0.0,
  rating_count INT UNSIGNED NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_tmdb_id (tmdb_id),
  UNIQUE KEY uq_titulo_tipo_anio (type, titulo, anio_lanzamiento),
  CONSTRAINT fk_contenido_created_by
  FOREIGN KEY (created_by) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_type_rating (type, rating_avg DESC),
  INDEX idx_origen (origen),
  FULLTEXT INDEX ft_titulo (titulo),
  INDEX idx_anio_lanzamiento (anio_lanzamiento),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE contenido_generos (
  content_id BINARY(16) NOT NULL,
  genre_id SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY (content_id, genre_id),
  CONSTRAINT fk_contenido_generos_contenido 
  FOREIGN KEY (content_id) REFERENCES contenido(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_contenido_generos_genero 
  FOREIGN KEY (genre_id) REFERENCES generos(id) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_genre_id (genre_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ratings (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BINARY(16) NOT NULL,
  content_id BINARY(16) NOT NULL,
  score TINYINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_content (user_id, content_id),
  CONSTRAINT fk_ratings_user 
  FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_ratings_content 
  FOREIGN KEY (content_id) REFERENCES contenido(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT chk_score_range CHECK (score >= 1 AND score <= 5),
  INDEX idx_content_id (content_id),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE historial_vistas (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BINARY(16) NOT NULL,
  content_id BINARY(16) NOT NULL,
  viewed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_historial_vistas_user 
  FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_historial_vistas_content 
  FOREIGN KEY (content_id) REFERENCES contenido(id) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_user_viewed (user_id, viewed_at DESC),
  INDEX idx_content_id (content_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_attempts (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  ip VARCHAR(45) NOT NULL,
  correo VARCHAR(255),
  attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  success TINYINT(1) NOT NULL DEFAULT 0,
  INDEX idx_ip_attempted (ip, attempted_at),
  INDEX idx_attempted (attempted_at),
  INDEX idx_correo (correo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE security_events (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  event_type VARCHAR(50) NOT NULL,
  user_id BINARY(16),
  ip VARCHAR(45) NOT NULL,
  payload JSON,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_security_events_user 
  FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_event_type_created (event_type, created_at),
  INDEX idx_user_id (user_id),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE remember_tokens (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BINARY(16) NOT NULL,
  token_hash VARCHAR(255) NOT NULL UNIQUE,
  expires_at TIMESTAMP NOT NULL DEFAULT '2000-01-01 00:00:00',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_remember_tokens_user 
  FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_token_hash (token_hash),
  INDEX idx_expires_at (expires_at),
  INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
