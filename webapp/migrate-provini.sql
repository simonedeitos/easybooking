-- ============================================================
-- EasyBooking – Migration: Aggiungi supporto Provini
-- Eseguire una sola volta su database esistenti.
-- I nuovi database creati con database-schema.sql già includono
-- queste colonne e non richiedono questa migrazione.
-- ============================================================

-- 1. Aggiungere colonna tipo_evento
ALTER TABLE `prenotazioni`
    ADD COLUMN `tipo_evento` ENUM('lezione','provino') NOT NULL DEFAULT 'lezione' AFTER `id`;

-- 2. Aggiungere colonna strumento_id con FK a strumenti
ALTER TABLE `prenotazioni`
    ADD COLUMN `strumento_id` INT UNSIGNED DEFAULT NULL AFTER `strumento`,
    ADD CONSTRAINT `fk_prenotazioni_strumento_id`
        FOREIGN KEY (`strumento_id`) REFERENCES `strumenti`(`id`) ON DELETE SET NULL;

-- 3. Aggiungere indici per performance
ALTER TABLE `prenotazioni`
    ADD INDEX `idx_tipo_evento` (`tipo_evento`),
    ADD INDEX `idx_tipo_insegnante_data` (`tipo_evento`, `insegnante_id`, `data`);

-- Le lezioni esistenti manterranno tipo_evento='lezione' (valore DEFAULT).
