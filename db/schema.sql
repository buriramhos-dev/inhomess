-- Schema for doctorvisit app
-- Expanded schema for doctorvisit app
CREATE DATABASE IF NOT EXISTS `doctorvisit` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `doctorvisit`;

-- Users (simple authentication/actor table)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `display_name` VARCHAR(255) DEFAULT '',
  `role` VARCHAR(50) DEFAULT 'user',
  `email` VARCHAR(255) DEFAULT '',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Patients
CREATE TABLE IF NOT EXISTS `patients` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hn` VARCHAR(64) DEFAULT '',
  `fullname` VARCHAR(255) DEFAULT '',
  `dob` DATE DEFAULT NULL,
  `age_y` SMALLINT DEFAULT NULL,
  `age_m` SMALLINT DEFAULT NULL,
  `gender` VARCHAR(16) DEFAULT '',
  `gender_note` VARCHAR(255) DEFAULT '',
  `address_no` VARCHAR(64) DEFAULT '',
  `address_moo` VARCHAR(64) DEFAULT '',
  `address_tambon` VARCHAR(128) DEFAULT '',
  `address_amphur` VARCHAR(128) DEFAULT '',
  `address_province` VARCHAR(128) DEFAULT '',
  `live_with` VARCHAR(128) DEFAULT '',
  `residence_type` VARCHAR(128) DEFAULT '',
  `notes` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`hn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Visit types (reference table)
CREATE TABLE IF NOT EXISTS `visit_types` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Visits: each visit event for a patient
CREATE TABLE IF NOT EXISTS `visits` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `patient_id` INT UNSIGNED NOT NULL,
  `visit_date` DATE NOT NULL,
  `visit_type_id` INT UNSIGNED DEFAULT NULL,
  `hn` VARCHAR(64) DEFAULT '',
  `summary` TEXT,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_patient` (`patient_id`),
  KEY `idx_visit_type` (`visit_type_id`),
  CONSTRAINT `fk_visits_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_visits_type` FOREIGN KEY (`visit_type_id`) REFERENCES `visit_types` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- INHOMESSS assessments linked to a visit (store JSON for flexible fields)
CREATE TABLE IF NOT EXISTS `inhomess_assessments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `visit_id` INT UNSIGNED DEFAULT NULL,
  `patient_id` INT UNSIGNED NOT NULL,
  `assessor_id` INT UNSIGNED DEFAULT NULL,
  `data` JSON NULL,
  `score` DECIMAL(5,2) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_inh_patient` (`patient_id`),
  KEY `idx_inh_visit` (`visit_id`),
  CONSTRAINT `fk_inhomess_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_inhomess_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Attachments for visits or assessments
CREATE TABLE IF NOT EXISTS `attachments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `entity` VARCHAR(50) NOT NULL, -- e.g. 'visit', 'assessment', 'patient'
  `entity_id` INT UNSIGNED NOT NULL,
  `filename` VARCHAR(255) NOT NULL,
  `path` VARCHAR(500) NOT NULL,
  `uploaded_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_entity` (`entity`, `entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Simple audit log
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `actor_id` INT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(255) NOT NULL,
  `resource` VARCHAR(255) DEFAULT NULL,
  `resource_id` VARCHAR(128) DEFAULT NULL,
  `meta` JSON NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_actor` (`actor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Basic seed row for visit_types (optional)
INSERT INTO `visit_types` (`id`, `name`, `description`) VALUES
  (1, 'เยี่ยมบ้านปกติ', 'เยี่ยมบ้านทั่วไป'),
  (2, 'เยี่ยมติดตามผล', 'ติดตามการรักษา/ปรึกษา'),
  (3, 'การประเมิน INHOMESSS', 'แบบประเมิน INHOMESSS')
ON DUPLICATE KEY UPDATE name=VALUES(name);
