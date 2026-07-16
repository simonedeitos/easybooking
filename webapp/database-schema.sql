-- ============================================================
-- EasyBooking – Database Schema
-- MySQL / MariaDB  |  Engine: InnoDB  |  Charset: utf8mb4
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ── Users ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `username`          VARCHAR(64)     NOT NULL UNIQUE,
    `email`             VARCHAR(255)    NOT NULL UNIQUE,
    `password_hash`     VARCHAR(255)    NOT NULL,
    `role`              ENUM('admin','user') NOT NULL DEFAULT 'user',
    `theme_preference`  ENUM('dark','light') NOT NULL DEFAULT 'dark',
    `reset_token`       VARCHAR(128)    DEFAULT NULL,
    `reset_expires`     DATETIME        DEFAULT NULL,
    `last_login`        DATETIME        DEFAULT NULL,
    `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── System Configuration ───────────────────────────────────
CREATE TABLE IF NOT EXISTS `system_config` (
    `key`           VARCHAR(64)     NOT NULL,
    `value`         TEXT            DEFAULT NULL,
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `system_config` (`key`, `value`) VALUES
    ('app_name',             'EasyBooking'),
    ('app_email',            'noreply@easybooking.local'),
    ('encryption_key',       ''),
    ('setup_complete',       '0');

-- ── Strumenti (Musical Instruments) ───────────────────────
CREATE TABLE IF NOT EXISTS `strumenti` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `nome`          VARCHAR(100)    NOT NULL,
    `lun_attivo`    TINYINT(1)      NOT NULL DEFAULT 1,
    `mar_attivo`    TINYINT(1)      NOT NULL DEFAULT 1,
    `mer_attivo`    TINYINT(1)      NOT NULL DEFAULT 1,
    `gio_attivo`    TINYINT(1)      NOT NULL DEFAULT 1,
    `ven_attivo`    TINYINT(1)      NOT NULL DEFAULT 1,
    `sab_attivo`    TINYINT(1)      NOT NULL DEFAULT 0,
    `dom_attivo`    TINYINT(1)      NOT NULL DEFAULT 0,
    `matt_inizio`   TIME            DEFAULT '09:00:00',
    `matt_fine`     TIME            DEFAULT '13:00:00',
    `pom_inizio`    TIME            DEFAULT '15:00:00',
    `pom_fine`      TIME            DEFAULT '19:00:00',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Clienti (Students / Clients) ──────────────────────────
CREATE TABLE IF NOT EXISTS `clienti` (
    `id`                        INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `nome`                      VARCHAR(100)    NOT NULL DEFAULT '',
    `cognome`                   VARCHAR(100)    NOT NULL DEFAULT '',
    `telefono`                  VARCHAR(50)     DEFAULT NULL,
    `email`                     VARCHAR(255)    DEFAULT NULL,
    `indirizzo`                 TEXT            DEFAULT NULL,
    `codice_fiscale`            VARCHAR(50)     DEFAULT NULL,
    `note`                      TEXT            DEFAULT NULL,
    `mega_cartella_pubblica`    TEXT            DEFAULT NULL,
    `mega_cartella_locale`      TEXT            DEFAULT NULL,
    `created_at`                DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`                DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Insegnanti (Teachers) ──────────────────────────────────
CREATE TABLE IF NOT EXISTS `insegnanti` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `nome`          VARCHAR(100)    NOT NULL DEFAULT '',
    `cognome`       VARCHAR(100)    NOT NULL DEFAULT '',
    `telefono`      VARCHAR(50)     DEFAULT NULL,
    `email`         VARCHAR(255)    DEFAULT NULL,
    `tariffa_oraria` DECIMAL(8,2)   NOT NULL DEFAULT 0.00,
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Insegnanti ↔ Strumenti (many-to-many) ─────────────────
CREATE TABLE IF NOT EXISTS `insegnanti_strumenti` (
    `insegnante_id` INT UNSIGNED    NOT NULL,
    `strumento_id`  INT UNSIGNED    NOT NULL,
    PRIMARY KEY (`insegnante_id`, `strumento_id`),
    FOREIGN KEY (`insegnante_id`) REFERENCES `insegnanti`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`strumento_id`)  REFERENCES `strumenti`(`id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Pacchetti (Lesson Packages) ────────────────────────────
CREATE TABLE IF NOT EXISTS `pacchetti` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `nome`              VARCHAR(150)    NOT NULL DEFAULT '',
    `descrizione`       TEXT            DEFAULT NULL,
    `numero_lezioni`    INT             NOT NULL DEFAULT 0,
    `durata_minuti`     INT             NOT NULL DEFAULT 60,
    `frequenza`         VARCHAR(100)    DEFAULT NULL,
    `prezzo`            DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    `strumento`         VARCHAR(100)    DEFAULT NULL,
    `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Acquisti (Purchases) ────────────────────────────────────
CREATE TABLE IF NOT EXISTS `acquisti` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `data_acquisto`     DATE            NOT NULL,
    `cliente_id`        INT UNSIGNED    NOT NULL,
    `pacchetto_id`      INT UNSIGNED    DEFAULT NULL,
    `importo_pagato`    DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    `stato_pagamento`   VARCHAR(50)     NOT NULL DEFAULT 'Non Pagato',
    `pianificato`       TINYINT(1)      NOT NULL DEFAULT 0,
    `numero_fattura`    VARCHAR(100)    DEFAULT NULL,
    `note`              TEXT            DEFAULT NULL,
    `numero_lezioni`    INT             NOT NULL DEFAULT 0,
    `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`cliente_id`)   REFERENCES `clienti`(`id`)   ON DELETE RESTRICT,
    FOREIGN KEY (`pacchetto_id`) REFERENCES `pacchetti`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Prenotazioni (Bookings / Lessons) ─────────────────────
CREATE TABLE IF NOT EXISTS `prenotazioni` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `data`          DATE            NOT NULL,
    `ora_inizio`    TIME            NOT NULL,
    `ora_fine`      TIME            NOT NULL,
    `cliente_id`    INT UNSIGNED    NOT NULL,
    `insegnante_id` INT UNSIGNED    NOT NULL,
    `strumento`     VARCHAR(100)    DEFAULT NULL,
    `stato`         ENUM('Programmata','Svolta','Assente','Rimandata','Riprogrammata') NOT NULL DEFAULT 'Programmata',
    `pacchetto_nome` VARCHAR(150)   DEFAULT NULL,
    `acquisto_id`   INT UNSIGNED    DEFAULT NULL,
    `note`          TEXT            DEFAULT NULL,
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`cliente_id`)    REFERENCES `clienti`(`id`)    ON DELETE RESTRICT,
    FOREIGN KEY (`insegnante_id`) REFERENCES `insegnanti`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`acquisto_id`)   REFERENCES `acquisti`(`id`)   ON DELETE SET NULL,
    INDEX `idx_data` (`data`),
    INDEX `idx_stato` (`stato`),
    INDEX `idx_insegnante_data` (`insegnante_id`, `data`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Impostazioni Generali ──────────────────────────────────
CREATE TABLE IF NOT EXISTS `impostazioni_generali` (
    `id`                    INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `lun_attivo`            TINYINT(1)      NOT NULL DEFAULT 1,
    `mar_attivo`            TINYINT(1)      NOT NULL DEFAULT 1,
    `mer_attivo`            TINYINT(1)      NOT NULL DEFAULT 1,
    `gio_attivo`            TINYINT(1)      NOT NULL DEFAULT 1,
    `ven_attivo`            TINYINT(1)      NOT NULL DEFAULT 1,
    `sab_attivo`            TINYINT(1)      NOT NULL DEFAULT 0,
    `dom_attivo`            TINYINT(1)      NOT NULL DEFAULT 0,
    `matt_inizio`           TIME            NOT NULL DEFAULT '09:00:00',
    `matt_fine`             TIME            NOT NULL DEFAULT '13:00:00',
    `pom_inizio`            TIME            NOT NULL DEFAULT '15:00:00',
    `pom_fine`              TIME            NOT NULL DEFAULT '19:00:00',
    `durata_lezione_default` INT            NOT NULL DEFAULT 60,
    `updated_at`            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `impostazioni_generali`
    (`id`,`lun_attivo`,`mar_attivo`,`mer_attivo`,`gio_attivo`,`ven_attivo`,`sab_attivo`,`dom_attivo`,
     `matt_inizio`,`matt_fine`,`pom_inizio`,`pom_fine`,`durata_lezione_default`)
VALUES (1, 1, 1, 1, 1, 1, 0, 0, '09:00:00','13:00:00','15:00:00','19:00:00', 60);

-- ── Notifiche Config ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `notifiche_config` (
    `id`                        INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`                   INT UNSIGNED    NOT NULL UNIQUE,
    `email_notifiche`           VARCHAR(255)    DEFAULT NULL,
    `abilita_notifiche`         TINYINT(1)      NOT NULL DEFAULT 1,
    `pacchetto_scadenza_giorni` INT             NOT NULL DEFAULT 7,
    `lezione_reminder_giorni`   INT             NOT NULL DEFAULT 1,
    `sommario_settimanale`      TINYINT(1)      NOT NULL DEFAULT 0,
    `sommario_mensile`          TINYINT(1)      NOT NULL DEFAULT 0,
    `updated_at`                DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Tariffe di Coppia ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS `tariffe_coppia` (
    `insegnante_id` INT UNSIGNED    NOT NULL,
    `tariffa`       DECIMAL(8,2)    NOT NULL DEFAULT 0.00,
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`insegnante_id`),
    FOREIGN KEY (`insegnante_id`) REFERENCES `insegnanti`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
