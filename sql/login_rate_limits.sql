-- AutoLux Pecas
-- Contadores para limitar tentativas de login.
-- Acrescenta uma tabela sem alterar os dados existentes.
-- O codigo PHP devera utilizar datas em UTC.
-- Por email introduzido	Até 5 tentativas de autenticação num período de 15 minutos.
-- Por endereço IP de origem	Até 30 tentativas no mesmo período, para limitar pedidos distribuídos por vários emails.
-- Quando atingir um limite	Recusar temporariamente novas tentativas e indicar que é necessário aguardar.
-- scope	Distingue um contador por email de um contador por IP.
-- identifier_hash	Identifica o valor que estamos a contar, usando um hash calculado pelo PHP.
-- attempts	Guarda quantas tentativas já foram contabilizadas nesse período.
-- window_ends_at	Guarda quando termina o período de contagem.


USE autolux_sales_db;

CREATE TABLE IF NOT EXISTS login_rate_limits (
    -- Tipo de contador: por email ou por endereco IP.
    scope ENUM('email', 'ip') NOT NULL,

    -- Identificador calculado pelo PHP.
    -- Nao e uma palavra-passe nem um identificador de token.
    identifier_hash CHAR(64)
        CHARACTER SET ascii
        COLLATE ascii_bin
        NOT NULL,

    -- Tentativas contabilizadas no periodo atual.
    attempts INT UNSIGNED NOT NULL DEFAULT 0,

    -- Momento a partir do qual pode comecar um novo periodo.
    window_ends_at DATETIME NOT NULL,

    PRIMARY KEY (scope, identifier_hash),

    KEY idx_login_rate_limits_window_end (window_ends_at)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

