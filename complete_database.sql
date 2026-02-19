-- ============================================
-- สคริปต์ SQL สมบูรณ์สำหรับระบบ INHOMESSS
-- สร้างฐานข้อมูลและตารางทั้งหมดพร้อมข้อมูลครบถ้วน
-- ============================================

-- สร้างฐานข้อมูล
CREATE DATABASE IF NOT EXISTS `doctorvisit` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `doctorvisit`;

-- ปิด foreign key checks ชั่วคราวเพื่อหลีกเลี่ยงปัญหา
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- ตาราง patients (ข้อมูลผู้ป่วย)
-- ============================================
DROP TABLE IF EXISTS `patients`;
CREATE TABLE `patients` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `no` VARCHAR(50) DEFAULT NULL COMMENT 'ลำดับ',
  `visit_date` DATE DEFAULT NULL COMMENT 'วันที่เยี่ยม',
  `hn` VARCHAR(50) DEFAULT NULL COMMENT 'HN (Hospital Number)',
  `fullname` VARCHAR(255) DEFAULT NULL COMMENT 'ชื่อ-สกุล',
  `age_y` INT(11) DEFAULT NULL COMMENT 'อายุ (ปี)',
  `age_m` INT(11) DEFAULT NULL COMMENT 'อายุ (เดือน)',
  `gender` VARCHAR(10) DEFAULT NULL COMMENT 'เพศ',
  `gender_note` TEXT DEFAULT NULL COMMENT 'หมายเหตุเพศ',
  `address_no` VARCHAR(100) DEFAULT NULL COMMENT 'เลขที่',
  `address_moo` VARCHAR(50) DEFAULT NULL COMMENT 'หมู่',
  `address_tambon` VARCHAR(100) DEFAULT NULL COMMENT 'ตำบล',
  `address_amphur` VARCHAR(100) DEFAULT NULL COMMENT 'อำเภอ',
  `address_province` VARCHAR(100) DEFAULT NULL COMMENT 'จังหวัด',
  `live_with` VARCHAR(255) DEFAULT NULL COMMENT 'อาศัยอยู่กับ',
  `residence_type` VARCHAR(100) DEFAULT NULL COMMENT 'ประเภทที่อยู่อาศัย',
  `consultation` TINYINT(1) DEFAULT 0 COMMENT 'เบิกปรึกษา',
  `no_consultation` TEXT DEFAULT NULL COMMENT 'ไม่เบิกปรึกษา',
  `imc` VARCHAR(50) DEFAULT NULL COMMENT 'IMC/BMI',
  `health_note` TEXT DEFAULT NULL COMMENT 'หมายเหตุสุขภาพ',
  `consult_yes` TINYINT(1) DEFAULT 0 COMMENT 'ปรึกษา',
  `consult_no` TINYINT(1) DEFAULT 0 COMMENT 'ไม่ปรึกษา',
  `medicine_yes` TINYINT(1) DEFAULT 0 COMMENT 'ให้ยา',
  `medicine_no` TINYINT(1) DEFAULT 0 COMMENT 'ไม่ให้ยา',
  `wc_info` VARCHAR(255) DEFAULT NULL COMMENT 'บ้านแรม (WC)',
  `general_note` TEXT DEFAULT NULL COMMENT 'หมายเหตุทั่วไป',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่สร้าง',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'วันที่อัปเดต',
  PRIMARY KEY (`id`),
  KEY `idx_hn` (`hn`),
  KEY `idx_visit_date` (`visit_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ข้อมูลผู้ป่วย';

-- ============================================
-- ตาราง visits (การเยี่ยมบ้าน)
-- ============================================
DROP TABLE IF EXISTS `visits`;
CREATE TABLE `visits` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `patient_id` INT(11) DEFAULT NULL COMMENT 'รหัสผู้ป่วย',
  `visit_date` DATE DEFAULT NULL COMMENT 'วันที่เยี่ยม',
  `visit_time` TIME DEFAULT NULL COMMENT 'เวลาเยี่ยม',
  `visit_number` INT(11) DEFAULT NULL COMMENT 'ครั้งที่',
  `visit_type_id` INT(11) DEFAULT NULL COMMENT 'ประเภทการเยี่ยม',
  `hn` VARCHAR(50) DEFAULT NULL COMMENT 'HN',
  `fullname` VARCHAR(255) DEFAULT NULL COMMENT 'ชื่อ-สกุลผู้ป่วย',
  `visitor` VARCHAR(255) DEFAULT NULL COMMENT 'ผู้เยี่ยม',
  `purpose` TEXT DEFAULT NULL COMMENT 'วัตถุประสงค์การเยี่ยม',
  `facilities` TEXT DEFAULT NULL COMMENT 'สิ่งอำนวยความสะดวก',
  `referral` TEXT DEFAULT NULL COMMENT 'การส่งต่อ',
  `summary` TEXT DEFAULT NULL COMMENT 'สรุป',
  `notes` TEXT DEFAULT NULL COMMENT 'หมายเหตุ',
  `created_by` INT(11) DEFAULT NULL COMMENT 'ผู้สร้าง',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่สร้าง',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'วันที่อัปเดต',
  PRIMARY KEY (`id`),
  KEY `idx_patient_id` (`patient_id`),
  KEY `idx_visit_date` (`visit_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='การเยี่ยมบ้าน';

-- ============================================
-- ตาราง inhomess_assessments (การประเมิน INHOMESSS)
-- ============================================
DROP TABLE IF EXISTS `inhomess_assessments`;
CREATE TABLE `inhomess_assessments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `visit_id` INT(11) DEFAULT NULL COMMENT 'รหัสการเยี่ยม',
  `patient_id` INT(11) DEFAULT NULL COMMENT 'รหัสผู้ป่วย',
  `assessor_id` INT(11) DEFAULT NULL COMMENT 'รหัสผู้ประเมิน',
  `hn` VARCHAR(50) DEFAULT NULL COMMENT 'HN',
  `fullname` VARCHAR(255) DEFAULT NULL COMMENT 'ชื่อ-สกุล',
  `assess_date` DATE DEFAULT NULL COMMENT 'วันที่ประเมิน',
  `assessor` VARCHAR(255) DEFAULT NULL COMMENT 'ผู้ประเมิน',
  `data` LONGTEXT DEFAULT NULL COMMENT 'ข้อมูลการประเมินทั้งหมด (JSON)',
  `score` DECIMAL(10,2) DEFAULT NULL COMMENT 'คะแนน Barthel Index',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่สร้าง',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'วันที่อัปเดต',
  PRIMARY KEY (`id`),
  KEY `idx_visit_id` (`visit_id`),
  KEY `idx_patient_id` (`patient_id`),
  KEY `idx_assess_date` (`assess_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='การประเมิน INHOMESSS';

-- เปิด foreign key checks กลับมา
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- ข้อมูลตัวอย่าง (Optional - ลบ comment เพื่อใช้งาน)
-- ============================================
/*
-- ข้อมูลผู้ป่วยตัวอย่าง
INSERT INTO `patients` (`hn`, `fullname`, `gender`, `visit_date`, `age_y`, `address_no`, `address_moo`, `address_tambon`, `address_amphur`, `address_province`, `live_with`) VALUES
('100001', 'สมชาย เก่งมาก', 'ชาย', '2025-12-01', 45, '123', '5', 'ตำบลตัวอย่าง', 'อำเภอตัวอย่าง', 'จังหวัดตัวอย่าง', 'ภรรยา'),
('100002', 'สมหญิง อ่อนโยน', 'หญิง', '2025-12-05', 42, '456', '7', 'ตำบลตัวอย่าง', 'อำเภอตัวอย่าง', 'จังหวัดตัวอย่าง', 'ลูกสาว');
*/

-- ============================================
-- สิ้นสุดสคริปต์
-- ============================================

