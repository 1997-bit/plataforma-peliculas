CREATE DATABASE cineapp 
  CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

USE cineapp;

-- 1. TABLA: users
CREATE TABLE users (
  id BINARY(16) PRIMARY KEY COMMENT 'UUID v4',
  email VARCHAR(500) NOT NULL COMMENT 'Email cifrado AES-256-GCM',
  email_hash CHAR(64) NOT NULL UNIQUE COMMENT 'HMAC-SHA256 del email',
  password_hash VARCHAR(255) NOT NULL COMMENT 'Argon2id hash',
  username VARCHAR(500) NOT NULL COMMENT 'Username cifrado AES-256-GCM',
  role ENUM('user','admin') NOT NULL DEFAULT 'user' COMMENT 'Control acceso server-side',
  preferences JSON COMMENT 'Géneros favoritos, tema visual',
  is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Baja lógica',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'ISO 8601',
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'ISO 8601',
  INDEX idx_email_hash (email_hash),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. TABLA: genres
CREATE TABLE genres (
  id SMALLINT UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT 'ID interno',
  name VARCHAR(50) NOT NULL UNIQUE COMMENT 'Nombre del género',
  tmdb_id INT UNIQUE COMMENT 'ID TMDB mapeo ETL',
  INDEX idx_tmdb_id (tmdb_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. TABLA: content
CREATE TABLE content (
  id BINARY(16) PRIMARY KEY COMMENT 'UUID v7 sortable por tiempo',
  tmdb_id INT UNIQUE COMMENT 'Referencia TMDB, evita duplicados en upsert',
  type ENUM('movie','series') NOT NULL COMMENT 'Discriminador tipo',
  title VARCHAR(255) NOT NULL COMMENT 'Índice FULLTEXT para búsqueda',
  description TEXT COMMENT 'overview de TMDB',
  poster_path VARCHAR(500) COMMENT 'Path relativo, URL en runtime',
  release_year YEAR COMMENT 'Extraído de release_date',
  rating_avg DECIMAL(3,1) NOT NULL DEFAULT 0.0 COMMENT 'Desnormalización controlada',
  rating_count INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Desnormalización controlada',
  is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Baja lógica',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'ISO 8601',
  UNIQUE KEY uq_tmdb_id (tmdb_id),
  INDEX idx_type_rating (type, rating_avg DESC),
  FULLTEXT INDEX ft_title (title),
  INDEX idx_release_year (release_year),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. TABLA: content_genres (relación M:N)
CREATE TABLE content_genres (
  content_id BINARY(16) NOT NULL COMMENT 'FK content.id cascada DELETE',
  genre_id SMALLINT UNSIGNED NOT NULL COMMENT 'FK genres.id cascada DELETE',
  PRIMARY KEY (content_id, genre_id),
  CONSTRAINT fk_content_genres_content 
  FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_content_genres_genre 
  FOREIGN KEY (genre_id) REFERENCES genres(id) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_genre_id (genre_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. TABLA: ratings

CREATE TABLE ratings (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BINARY(16) NOT NULL COMMENT 'FK users.id',
  content_id BINARY(16) NOT NULL COMMENT 'FK content.id',
  score TINYINT UNSIGNED NOT NULL COMMENT 'Validación CHECK 1-10',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'ISO 8601',
  UNIQUE KEY uq_user_content (user_id, content_id),
  CONSTRAINT fk_ratings_user 
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_ratings_content 
  FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT chk_score_range CHECK (score >= 1 AND score <= 10),
  INDEX idx_content_id (content_id),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. TABLA: view_history

CREATE TABLE view_history (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BINARY(16) NOT NULL COMMENT 'FK users.id',
  content_id BINARY(16) NOT NULL COMMENT 'FK content.id',
  viewed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'ISO 8601',
  CONSTRAINT fk_view_history_user 
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_view_history_content 
  FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_user_viewed (user_id, viewed_at DESC),
  INDEX idx_content_id (content_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. TABLA: login_attempts
CREATE TABLE login_attempts (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  ip VARCHAR(45) NOT NULL COMMENT 'IPv4 + IPv6',
  email VARCHAR(255) COMMENT 'Puede ser email inexistente, sin FK',
  attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'ISO 8601',
  success TINYINT(1) NOT NULL DEFAULT 0,
  INDEX idx_ip_attempted (ip, attempted_at),
  INDEX idx_attempted (attempted_at),
  INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. TABLA: security_events
CREATE TABLE security_events (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  event_type VARCHAR(50) NOT NULL COMMENT 'LOGIN_FAILED, IDOR_ATTEMPT, etc.',
  user_id BINARY(16) COMMENT 'FK users.id nullable',
  ip VARCHAR(45) NOT NULL COMMENT 'IPv4 + IPv6',
  payload JSON COMMENT 'Contexto del evento',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'ISO 8601',
  CONSTRAINT fk_security_events_user 
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_event_type_created (event_type, created_at),
  INDEX idx_user_id (user_id),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. TABLA: remember_tokens
CREATE TABLE remember_tokens (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BINARY(16) NOT NULL COMMENT 'FK users.id',
  token_hash VARCHAR(255) NOT NULL UNIQUE COMMENT 'Hash',
  expires_at TIMESTAMP NOT NULL COMMENT 'Expiración 30 días',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'ISO 8601',
  CONSTRAINT fk_remember_tokens_user 
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_token_hash (token_hash),
  INDEX idx_expires_at (expires_at),
  INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- COMENTARIOS TABLA NIVEL

ALTER TABLE users COMMENT='Usuarios del sistema - login, perfil, preferencias';
ALTER TABLE genres COMMENT='Catálogo de géneros desde TMDB';
ALTER TABLE content COMMENT='Películas y series - datos desnormalizados (rating_avg, rating_count)';
ALTER TABLE content_genres COMMENT='Relación M:N content-genres sin columnas extra';
ALTER TABLE ratings COMMENT='Calificaciones usuario-contenido, un rating por par';
ALTER TABLE view_history COMMENT='Historial de visualización para recomendaciones';
ALTER TABLE login_attempts COMMENT='Auditoría login intentos - rate limiting en (ip, attempted_at)';
ALTER TABLE security_events COMMENT='Eventos de seguridad ISO 27001 A.12.4';
ALTER TABLE remember_tokens COMMENT='Tokens "recuérdame" - solo hash, expiración 30 días';

