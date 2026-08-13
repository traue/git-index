-- Schema do sistema de Disciplinas (admin + API)
--
-- Pressupõe hospedagem compartilhada: o banco e o usuário do MySQL já foram
-- criados pelo painel (cPanel > MySQL(R) Databases) e as credenciais foram
-- colocadas no arquivo .env. Este script só cria as tabelas dentro do banco
-- que você já selecionou (via phpMyAdmin: selecione o banco antes de rodar
-- "Importar", ou rode "USE seu_banco;" antes deste script).

SET NAMES utf8mb4;
SET time_zone = '-03:00';

-- ---------------------------------------------------------------------
-- usuarios: contas do painel admin. Todos "admin" por enquanto, mas a
-- coluna role já existe para permitir papéis diferentes no futuro.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome             VARCHAR(120) NOT NULL,
    email            VARCHAR(190) NOT NULL,
    senha_hash       VARCHAR(255) NOT NULL,
    role             ENUM('admin') NOT NULL DEFAULT 'admin',
    ativo            TINYINT(1) NOT NULL DEFAULT 1,
    ultimo_login_em  DATETIME NULL,
    criado_em        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- login_tentativas: histórico de tentativas de login, usado para
-- bloquear força bruta (ver src/Auth.php).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS login_tentativas (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    identificador  VARCHAR(190) NOT NULL,
    ip             VARCHAR(45) NOT NULL,
    sucesso        TINYINT(1) NOT NULL,
    criado_em      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_login_tentativas_ident_data (identificador, criado_em),
    KEY idx_login_tentativas_ip_data (ip, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- semestres: cada linha é um "semestre letivo". status controla o
-- histórico: só um semestre fica "publicado" por vez (é o que a API
-- pública devolve); os demais ficam como rascunho ou arquivado.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS semestres (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo        VARCHAR(10) NOT NULL COMMENT 'ex: 26.1, 26.2',
    status        ENUM('rascunho', 'publicado', 'arquivado') NOT NULL DEFAULT 'rascunho',
    criado_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    publicado_em  DATETIME NULL,
    UNIQUE KEY uq_semestres_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- disciplinas: pertence a um semestre. tipo=ead nunca tem turno/dia;
-- tipo=presencial sempre tem turno (diurno/noturno). A CHECK abaixo é
-- reforçada de verdade na camada PHP (admin/disciplinas.php), porque
-- nem toda versão de MySQL/MariaDB do host aplica CHECK de fato.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS disciplinas (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    semestre_id   INT UNSIGNED NOT NULL,
    nome          VARCHAR(150) NOT NULL,
    curso         VARCHAR(80) NULL COMMENT 'ex: ADS 02A/B',
    tipo          ENUM('presencial', 'ead') NOT NULL,
    turno         ENUM('diurno', 'noturno') NULL,
    dia           VARCHAR(60) NULL COMMENT 'ex: 6ª (manhã)',
    repo          VARCHAR(120) NOT NULL,
    ordem         SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    criado_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_disciplinas_semestre FOREIGN KEY (semestre_id)
        REFERENCES semestres (id) ON DELETE CASCADE,
    CONSTRAINT chk_disciplinas_turno CHECK (
        (tipo = 'ead' AND turno IS NULL) OR
        (tipo = 'presencial' AND turno IS NOT NULL)
    ),
    KEY idx_disciplinas_semestre_ordem (semestre_id, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- configuracoes: pares chave/valor. Hoje só guarda o interruptor
-- geral ("active") que liga/desliga o front.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS configuracoes (
    chave VARCHAR(60) PRIMARY KEY,
    valor VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO configuracoes (chave, valor)
VALUES ('active', '1')
ON DUPLICATE KEY UPDATE chave = chave;

-- ---------------------------------------------------------------------
-- Seed opcional: migra o discs.json atual (semestre 26.1) para o banco,
-- já publicado, para a API continuar respondendo exatamente o que
-- respondia antes da troca. Rode uma vez só; se o semestre 26.1 já
-- existir, os INSERTs abaixo falham por causa do UNIQUE(codigo) — nesse
-- caso, é seguro ignorar/pular este bloco.
-- ---------------------------------------------------------------------
INSERT INTO semestres (codigo, status, publicado_em)
VALUES ('26.1', 'publicado', NOW());

SET @semestre_26_1 := LAST_INSERT_ID();

INSERT INTO disciplinas (semestre_id, nome, curso, tipo, turno, dia, repo, ordem) VALUES
    (@semestre_26_1, 'Computação Distribuída', NULL, 'presencial', 'diurno', '6ª (manhã)', '26.1_comp_dist', 1),
    (@semestre_26_1, 'Laboratório de Eng. de Software', NULL, 'presencial', 'noturno', '2ª e 3ª (noite)', '26.1_lab_eng_sw', 1),
    (@semestre_26_1, 'Desenvolvimento de Sistemas II', NULL, 'presencial', 'noturno', '3ª (noite)', '26.1_ds2', 2),
    (@semestre_26_1, 'Desenvolvimento de Sistemas I', NULL, 'presencial', 'noturno', '6ª (noite)', '26.1_ds1', 3),
    (@semestre_26_1, 'Programação de Sistemas I', 'ADS 02A/B', 'ead', NULL, NULL, '26.1_ps1_ead', 1),
    (@semestre_26_1, 'Desenvolvimento de Sistemas II', 'ADS 03A/B', 'ead', NULL, NULL, '26.1_ds2_ead', 2);
