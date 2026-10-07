-- AutoLux Pecas
-- Preparacao da autenticacao com JWT.
-- Acrescenta tabelas na base existente, sem apagar as anteriores.

USE autolux_sales_db;


-- 1. Contas dos utilizadores da aplicacao.
-- As palavras-passe serao guardadas como hashes gerados pelo PHP.

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(120) NOT NULL,

    email VARCHAR(150) NOT NULL,

    password_hash VARCHAR(255) NOT NULL,

    active TINYINT NOT NULL DEFAULT 1,  

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_users_email (email)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- 2. Registo dos identificadores dos tokens emitidos.
-- Nao guarda o JWT completo nem palavras-passe.
-- O codigo PHP devera gravar as datas destes tokens em UTC.

CREATE TABLE IF NOT EXISTS auth_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    jti CHAR(64)
        CHARACTER SET ascii
        COLLATE ascii_bin
        NOT NULL,

    issued_at DATETIME NOT NULL,

    expires_at DATETIME NOT NULL,

    revoked_at DATETIME NULL DEFAULT NULL,

    UNIQUE KEY uq_auth_tokens_jti (jti),

    KEY idx_auth_tokens_user_id (user_id),

    KEY idx_auth_tokens_expires_at (expires_at),

    CONSTRAINT fk_auth_tokens_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;
