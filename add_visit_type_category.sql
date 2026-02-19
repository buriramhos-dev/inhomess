-- เพิ่มคอลัมน์ visit_type_category ในตาราง visits
ALTER TABLE `visits` 
ADD COLUMN IF NOT EXISTS `visit_type_category` TEXT DEFAULT NULL 
AFTER `visit_type_id`;

