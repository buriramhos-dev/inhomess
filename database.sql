-- สร้างฐานข้อมูล doctorvisit
CREATE DATABASE IF NOT EXISTS `doctorvisit` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `doctorvisit`;

-- ตาราง patients (ข้อมูลผู้ป่วย)
CREATE TABLE IF NOT EXISTS `patients` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `no` VARCHAR(50) DEFAULT NULL,
  `visit_date` DATE DEFAULT NULL,
  `hn` VARCHAR(50) DEFAULT NULL,
  `fullname` VARCHAR(255) DEFAULT NULL,
  `age_y` INT(11) DEFAULT NULL,
  `age_m` INT(11) DEFAULT NULL,
  `gender` VARCHAR(10) DEFAULT NULL,
  `gender_note` TEXT DEFAULT NULL,
  `address_no` VARCHAR(100) DEFAULT NULL,
  `address_moo` VARCHAR(50) DEFAULT NULL,
  `address_tambon` VARCHAR(100) DEFAULT NULL,
  `address_amphur` VARCHAR(100) DEFAULT NULL,
  `address_province` VARCHAR(100) DEFAULT NULL,
  `live_with` VARCHAR(255) DEFAULT NULL,
  `residence_type` VARCHAR(100) DEFAULT NULL,
  `consultation` TINYINT(1) DEFAULT 0,
  `no_consultation` TEXT DEFAULT NULL,
  `imc` VARCHAR(50) DEFAULT NULL,
  `health_note` TEXT DEFAULT NULL,
  `consult_yes` TINYINT(1) DEFAULT 0,
  `consult_no` TINYINT(1) DEFAULT 0,
  `medicine_yes` TINYINT(1) DEFAULT 0,
  `medicine_no` TINYINT(1) DEFAULT 0,
  `wc_info` VARCHAR(255) DEFAULT NULL,
  `general_note` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_hn` (`hn`),
  KEY `idx_visit_date` (`visit_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ตาราง visits (การเยี่ยมบ้าน)
CREATE TABLE IF NOT EXISTS `visits` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `patient_id` INT(11) DEFAULT NULL,
  `visit_date` DATE DEFAULT NULL,
  `visit_time` TIME DEFAULT NULL,
  `visit_number` INT(11) DEFAULT NULL,
  `visit_type_id` INT(11) DEFAULT NULL,
  `hn` VARCHAR(50) DEFAULT NULL,
  `fullname` VARCHAR(255) DEFAULT NULL,
  `visitor` VARCHAR(255) DEFAULT NULL,
  `purpose` TEXT DEFAULT NULL,
  `facilities` TEXT DEFAULT NULL,
  `referral` TEXT DEFAULT NULL,
  `summary` TEXT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_patient_id` (`patient_id`),
  KEY `idx_visit_date` (`visit_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ตาราง inhomess_assessments (การประเมิน INHOMESSS)
CREATE TABLE IF NOT EXISTS `inhomess_assessments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `visit_id` INT(11) DEFAULT NULL,
  `patient_id` INT(11) DEFAULT NULL,
  `assessor_id` INT(11) DEFAULT NULL,
  `hn` VARCHAR(50) DEFAULT NULL,
  `fullname` VARCHAR(255) DEFAULT NULL,
  `assess_date` DATE DEFAULT NULL,
  `assessor` VARCHAR(255) DEFAULT NULL,
  `data` LONGTEXT DEFAULT NULL,
  `score` DECIMAL(10,2) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_visit_id` (`visit_id`),
  KEY `idx_patient_id` (`patient_id`),
  KEY `idx_assess_date` (`assess_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

