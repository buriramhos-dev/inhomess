-- ============================================
-- สคริปต์อัปเดตฐานข้อมูลให้ครบทุกฟิลด์
-- รันไฟล์นี้ถ้าฐานข้อมูลมีอยู่แล้ว
-- ============================================

USE `doctorvisit`;

-- เพิ่ม column visitor และ fullname ในตาราง visits (ถ้ายังไม่มี)
ALTER TABLE `visits` 
ADD COLUMN IF NOT EXISTS `fullname` VARCHAR(255) DEFAULT NULL AFTER `hn`,
ADD COLUMN IF NOT EXISTS `visitor` VARCHAR(255) DEFAULT NULL AFTER `fullname`;

-- แก้ไข column data ในตาราง inhomess_assessments จาก JSON เป็น LONGTEXT (ถ้ายังเป็น JSON)
ALTER TABLE `inhomess_assessments` 
MODIFY COLUMN `data` LONGTEXT DEFAULT NULL;

-- ตรวจสอบและแก้ไข columns ให้รองรับ NULL ทั้งหมด
ALTER TABLE `inhomess_assessments` 
MODIFY COLUMN `visit_id` INT(11) DEFAULT NULL,
MODIFY COLUMN `patient_id` INT(11) DEFAULT NULL,
MODIFY COLUMN `assessor_id` INT(11) DEFAULT NULL,
MODIFY COLUMN `hn` VARCHAR(50) DEFAULT NULL,
MODIFY COLUMN `fullname` VARCHAR(255) DEFAULT NULL,
MODIFY COLUMN `assess_date` DATE DEFAULT NULL,
MODIFY COLUMN `assessor` VARCHAR(255) DEFAULT NULL,
MODIFY COLUMN `score` DECIMAL(10,2) DEFAULT NULL;

-- สิ้นสุดการอัปเดต

