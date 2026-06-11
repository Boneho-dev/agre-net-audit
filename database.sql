-- =============================================================================
-- Net Auditor IA — Schéma de base de données
-- Compatible : MySQL 5.7+ (XAMPP local) et InfinityFree (MySQL 5.6+)
-- Encodage   : utf8mb4_unicode_ci
-- =============================================================================

-- -----------------------------------------------------------------------------
-- Table : audits
-- Stocke les métadonnées de chaque audit (une ligne par analyse).
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audits` (
  `id`          INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  `username`    VARCHAR(80)       NOT NULL DEFAULT '',
  `filename`    VARCHAR(255)      NOT NULL,
  `hostname`    VARCHAR(100)      NOT NULL DEFAULT 'Unknown',
  `score`       TINYINT UNSIGNED  NOT NULL DEFAULT 0,
  `score_label` VARCHAR(30)       NOT NULL DEFAULT '',
  `total`       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `nb_critical` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `nb_warning`  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `nb_info`     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`  DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_username` (`username`),
  KEY `idx_created`  (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table : findings
-- Stocke chaque vulnérabilité détectée, liée à un audit par clé étrangère.
-- ON DELETE CASCADE : supprimer un audit supprime automatiquement ses findings.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `findings` (
  `id`            INT UNSIGNED                       NOT NULL AUTO_INCREMENT,
  `audit_id`      INT UNSIGNED                       NOT NULL,
  `vuln_id`       VARCHAR(20)                        NOT NULL DEFAULT '',
  `title`         VARCHAR(120)                       NOT NULL,
  `severity`      ENUM('CRITICAL','WARNING','INFO')  NOT NULL DEFAULT 'INFO',
  `affected_line` TEXT,
  `description`   TEXT,
  `impact`        TEXT,
  `remediation`   TEXT,
  PRIMARY KEY (`id`),
  KEY `idx_audit_id` (`audit_id`),
  CONSTRAINT `fk_findings_audit`
    FOREIGN KEY (`audit_id`) REFERENCES `audits`(`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
