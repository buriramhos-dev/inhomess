-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 08, 2026 at 06:38 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `doctorvisit`
--

-- --------------------------------------------------------

--
-- Table structure for table `inhomess_assessments`
--

CREATE TABLE `inhomess_assessments` (
  `id` int(11) NOT NULL,
  `visit_id` int(11) DEFAULT NULL COMMENT 'รหัสการเยี่ยม',
  `patient_id` int(11) DEFAULT NULL COMMENT 'รหัสผู้ป่วย',
  `assessor_id` int(11) DEFAULT NULL COMMENT 'รหัสผู้ประเมิน',
  `hn` varchar(50) DEFAULT NULL COMMENT 'HN',
  `fullname` varchar(255) DEFAULT NULL COMMENT 'ชื่อ-สกุล',
  `assess_date` date DEFAULT NULL COMMENT 'วันที่ประเมิน',
  `assessor` varchar(255) DEFAULT NULL COMMENT 'ผู้ประเมิน',
  `data` longtext DEFAULT NULL COMMENT 'ข้อมูลการประเมินทั้งหมด (JSON)',
  `score` decimal(10,2) DEFAULT NULL COMMENT 'คะแนน Barthel Index',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'วันที่สร้าง',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'วันที่อัปเดต'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='การประเมิน INHOMESSS';

--
-- Dumping data for table `inhomess_assessments`
--

INSERT INTO `inhomess_assessments` (`id`, `visit_id`, `patient_id`, `assessor_id`, `hn`, `fullname`, `assess_date`, `assessor`, `data`, `score`, `created_at`, `updated_at`) VALUES
(6, NULL, 1, NULL, '100001', 'สมชาย เก่งมาก', '2025-12-24', 'Phongsakorn', '{\n    \"hn\": \"100001\",\n    \"fullname\": \"สมชาย เก่งมาก\",\n    \"assess_date\": \"2025-12-24\",\n    \"assessor\": \"Phongsakorn\",\n    \"patient_id\": \"1\",\n    \"imm_problem_has\": \"yes\",\n    \"imm_problem_none\": \"yes\",\n    \"imm_self_sufficient\": \"yes\",\n    \"imm_not_self_sufficient\": \"yes\",\n    \"imm_notes\": \"\",\n    \"nut_problem_general\": \"yes\",\n    \"nut_meals_per_day\": \"\",\n    \"nut_meal_carer\": \"\",\n    \"nut_source_ready\": \"yes\",\n    \"nut_alcohol_abstain\": \"yes\",\n    \"nut_notes\": \"\",\n    \"home_indoor_clean\": \"yes\",\n    \"home_outdoor_no_area\": \"yes\",\n    \"home_struct_semi_stable\": \"yes\",\n    \"home_light_sufficient\": \"yes\",\n    \"home_toilet_suitable\": \"yes\",\n    \"home_notes\": \"\",\n    \"people_emergency_spouse\": \"yes\",\n    \"people_emergency_other_text\": \"\",\n    \"people_carer_spouse\": \"yes\",\n    \"people_carer_other_text\": \"\",\n    \"people_notes\": \"\",\n    \"med_follow_correct\": \"yes\",\n    \"med_receive_regular\": \"yes\",\n    \"med_admin_other\": \"yes\",\n    \"med_error_has\": \"yes\",\n    \"med_list\": \"\",\n    \"exam_ulcer_problem\": \"yes\",\n    \"exam_stiff_no_problem\": \"yes\",\n    \"exam_device_ng\": \"yes\",\n    \"exam_notes\": \"\",\n    \"adl_feeding\": [\n        \"10\"\n    ],\n    \"adl_dressing\": [\n        \"10\"\n    ],\n    \"adl_bowels\": [\n        \"10\"\n    ],\n    \"adl_bladder\": [\n        \"10\"\n    ],\n    \"adl_transfer\": [\n        \"15\"\n    ],\n    \"adl_mobility\": [\n        \"10\"\n    ],\n    \"adl_stairs\": [\n        \"10\"\n    ],\n    \"mrs_note\": \"\",\n    \"safety_fall_panel\": \"on\",\n    \"safety_fall_risk\": \"on\",\n    \"Spiritual_fall_panel\": \"on\",\n    \"Spiritual_fall_belief\": \"on\",\n    \"service_other\": \"\",\n    \"another_cg\": \"ทดสอบๆๆๆๆ\",\n    \"another_needs\": \"ทดสอบๆๆๆๆ\",\n    \"another_nursing_goal\": \"ทดสอบๆๆๆๆ\",\n    \"another_nursing_activity\": \"ทดสอบๆๆๆๆ\",\n    \"another_next_appointment\": \"ทดสอบๆๆๆๆ\",\n    \"another_advice\": \"ทดสอบๆๆๆๆ\"\n}', 75.00, '2025-12-24 08:01:55', '2025-12-29 02:43:44'),
(7, NULL, 2, NULL, '12354', 'เต้ พง', '2026-01-07', '', '{\n    \"patient_id\": \"2\",\n    \"hn\": \"12354\",\n    \"fullname\": \"เต้ พง\",\n    \"assess_date\": \"2026-01-07\",\n    \"assessor\": \"\",\n    \"imm_notes\": \"\",\n    \"nut_meals_per_day\": \"\",\n    \"nut_meal_carer\": \"\",\n    \"nut_notes\": \"\",\n    \"home_notes\": \"\",\n    \"people_emergency_other_text\": \"\",\n    \"people_carer_other_text\": \"\",\n    \"people_notes\": \"\",\n    \"med_list\": \"\",\n    \"exam_ulcer_problem\": \"yes\",\n    \"exam_stiff_no_problem\": \"yes\",\n    \"exam_notes\": \"\",\n    \"mrs_note\": \"\",\n    \"service_other\": \"\",\n    \"another_needs\": \"ทดสอบ\",\n    \"another_nursing_goal\": \"\",\n    \"another_nursing_activity\": \"\",\n    \"another_evaluation\": \"\",\n    \"another_next_appointment\": \"2026-01-08\",\n    \"another_advice\": \"\",\n    \"another_cg\": \"\"\n}', NULL, '2025-12-26 03:50:33', '2026-01-07 04:10:02'),
(8, NULL, 3, NULL, '090547', 'วิ ลา', '2026-01-10', '', '{\n    \"patient_id\": \"3\",\n    \"hn\": \"090547\",\n    \"fullname\": \"วิ ลา\",\n    \"assess_date\": \"2026-01-10\",\n    \"assessor\": \"\",\n    \"imm_not_self_sufficient\": \"yes\",\n    \"imm_housebound\": \"yes\",\n    \"imm_notes\": \"\",\n    \"nut_meals_per_day\": \"\",\n    \"nut_meal_carer\": \"\",\n    \"nut_type_liquid\": \"yes\",\n    \"nut_notes\": \"\",\n    \"home_indoor_has_pet\": \"yes\",\n    \"home_notes\": \"\",\n    \"people_emergency_sibling\": \"yes\",\n    \"people_emergency_other_text\": \"\",\n    \"people_carer_other_text\": \"\",\n    \"people_notes\": \"\",\n    \"med_admin_other\": \"yes\",\n    \"med_list\": \"\",\n    \"exam_bp_flag\": \"yes\",\n    \"exam_ulcer_problem\": \"yes\",\n    \"exam_stiff_no_problem\": \"yes\",\n    \"exam_device_tt\": \"yes\",\n    \"exam_notes\": \"\",\n    \"adl_bowels\": [\n        \"10\"\n    ],\n    \"adl_bladder\": [\n        \"10\"\n    ],\n    \"adl_toilet\": [\n        \"5\"\n    ],\n    \"adl_transfer\": [\n        \"10\"\n    ],\n    \"adl_stairs\": [\n        \"5\"\n    ],\n    \"mrs_score\": [\n        \"6\"\n    ],\n    \"mrs_note\": \"\",\n    \"service_other\": \"\",\n    \"another_needs\": \"ทดดดดดด\",\n    \"another_nursing_goal\": \"ทดดดดด\",\n    \"another_nursing_activity\": \"ทดดดดด\",\n    \"another_evaluation\": \"ทดดดด\",\n    \"another_next_appointment\": \"2026-01-12\",\n    \"another_advice\": \"ทดดดดด\",\n    \"another_cg\": \"ทดดดดด\"\n}', 40.00, '2026-01-05 06:38:26', '2026-01-08 03:19:40'),
(9, NULL, 28, NULL, '090547', 'วิ ลา', '2026-01-08', '', '{\n    \"patient_id\": \"28\",\n    \"hn\": \"090547\",\n    \"fullname\": \"วิ ลา\",\n    \"assess_date\": \"2026-01-08\",\n    \"assessor\": \"\",\n    \"imm_problem_has\": \"yes\",\n    \"imm_problem_none\": \"yes\",\n    \"imm_notes\": \"\",\n    \"nut_meals_per_day\": \"\",\n    \"nut_meal_carer\": \"\",\n    \"nut_notes\": \"\",\n    \"home_notes\": \"\",\n    \"people_emergency_other_text\": \"\",\n    \"people_carer_other_text\": \"\",\n    \"people_notes\": \"\",\n    \"med_list\": \"\",\n    \"exam_ulcer_problem\": \"yes\",\n    \"exam_stiff_no_problem\": \"yes\",\n    \"exam_notes\": \"\",\n    \"mrs_note\": \"\",\n    \"service_other\": \"\",\n    \"another_needs\": \"\",\n    \"another_nursing_goal\": \"\",\n    \"another_nursing_activity\": \"\",\n    \"another_evaluation\": \"\",\n    \"another_next_appointment\": \"\",\n    \"another_advice\": \"\",\n    \"another_cg\": \"\"\n}', NULL, '2026-01-08 04:23:09', '2026-01-08 04:23:09');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `no` varchar(50) DEFAULT NULL COMMENT 'ลำดับ',
  `visit_date` date DEFAULT NULL COMMENT 'วันที่เยี่ยม',
  `hn` varchar(50) DEFAULT NULL COMMENT 'HN (Hospital Number)',
  `fullname` varchar(255) DEFAULT NULL COMMENT 'ชื่อ-สกุล',
  `age_y` int(11) DEFAULT NULL COMMENT 'อายุ (ปี)',
  `age_m` int(11) DEFAULT NULL COMMENT 'อายุ (เดือน)',
  `gender` varchar(10) DEFAULT NULL COMMENT 'เพศ',
  `gender_note` text DEFAULT NULL COMMENT 'หมายเหตุเพศ',
  `address_no` varchar(100) DEFAULT NULL COMMENT 'เลขที่',
  `address_moo` varchar(50) DEFAULT NULL COMMENT 'หมู่',
  `address_tambon` varchar(100) DEFAULT NULL COMMENT 'ตำบล',
  `address_amphur` varchar(100) DEFAULT NULL COMMENT 'อำเภอ',
  `address_province` varchar(100) DEFAULT NULL COMMENT 'จังหวัด',
  `live_with` varchar(255) DEFAULT NULL COMMENT 'อาศัยอยู่กับ',
  `residence_type` varchar(100) DEFAULT NULL COMMENT 'ประเภทที่อยู่อาศัย',
  `pre_visit_illness` text DEFAULT NULL,
  `is_imc` varchar(10) DEFAULT NULL,
  `visit_reason` text DEFAULT NULL,
  `symptoms_found` text DEFAULT NULL,
  `medical_equipment` text DEFAULT NULL,
  `problems_found` text DEFAULT NULL,
  `solution` text DEFAULT NULL,
  `consultation` tinyint(1) DEFAULT 0 COMMENT 'เบิกปรึกษา',
  `no_consultation` text DEFAULT NULL COMMENT 'ไม่เบิกปรึกษา',
  `imc` varchar(50) DEFAULT NULL COMMENT 'IMC/BMI',
  `health_note` text DEFAULT NULL COMMENT 'หมายเหตุสุขภาพ',
  `consult_yes` tinyint(1) DEFAULT 0 COMMENT 'ปรึกษา',
  `consult_no` tinyint(1) DEFAULT 0 COMMENT 'ไม่ปรึกษา',
  `medicine_yes` tinyint(1) DEFAULT 0 COMMENT 'ให้ยา',
  `medicine_no` tinyint(1) DEFAULT 0 COMMENT 'ไม่ให้ยา',
  `wc_info` varchar(255) DEFAULT NULL COMMENT 'บ้านแรม (WC)',
  `general_note` text DEFAULT NULL COMMENT 'หมายเหตุทั่วไป',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'วันที่สร้าง',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'วันที่อัปเดต'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ข้อมูลผู้ป่วย';

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `no`, `visit_date`, `hn`, `fullname`, `age_y`, `age_m`, `gender`, `gender_note`, `address_no`, `address_moo`, `address_tambon`, `address_amphur`, `address_province`, `live_with`, `residence_type`, `pre_visit_illness`, `is_imc`, `visit_reason`, `symptoms_found`, `medical_equipment`, `problems_found`, `solution`, `consultation`, `no_consultation`, `imc`, `health_note`, `consult_yes`, `consult_no`, `medicine_yes`, `medicine_no`, `wc_info`, `general_note`, `created_at`, `updated_at`) VALUES
(1, '', '2025-12-25', '100001', 'สมชาย เก่งมาก', 56, 0, 'ชาย', '', '52', '15', 'ในเมือง', 'ในเมือง', 'ouiouu', 'ลูก', '', 'เบาหวาน', '1', '', 'เิ้้นรดยน้านยานบยา', 'has, air_mattress, o2_concentrator', 'ด้กกะ่ีาัาา', 'ัาีราีาีรสี', 0, '', '', '', 0, 0, 0, 0, '', '', '2025-12-24 08:07:59', '2025-12-26 04:05:33'),
(2, '', '2025-12-26', '12354', 'เต้ พง', 30, 0, 'ชาย', '', '12', '12', 'ในเมือง', 'ในเมือง', 'บุรีรัมย์', 'แม่', '', 'ความดัน', '1', 'เอ๋อ', 'เอ๋อ', 'has, hospital_bed', 'เอ๋อ', 'เอ๋อ', 0, '', '', '', 0, 0, 0, 0, '', '', '2025-12-26 03:49:33', '2025-12-26 03:49:52'),
(3, '', '2026-01-05', '090547', 'วิ ลา', 22, 0, 'หญิง', '', '82', '15', 'ตลาด', 'ชุม', 'นครราชสีมา', 'แม่', '', '', '', '', '', 'no_equipment, air_mattress', '', '', 0, '', '', '', 0, 0, 0, 0, '', '', '2026-01-05 03:55:00', '2026-01-06 09:05:50');

-- --------------------------------------------------------

--
-- Table structure for table `visits`
--

CREATE TABLE `visits` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL COMMENT 'รหัสผู้ป่วย',
  `visit_date` date DEFAULT NULL COMMENT 'วันที่เยี่ยม',
  `visit_time` time DEFAULT NULL COMMENT 'เวลาเยี่ยม',
  `visit_number` int(11) DEFAULT NULL COMMENT 'ครั้งที่',
  `visit_type_id` int(11) DEFAULT NULL COMMENT 'ประเภทการเยี่ยม',
  `visit_type_category` text DEFAULT NULL,
  `hn` varchar(50) DEFAULT NULL COMMENT 'HN',
  `fullname` varchar(255) DEFAULT NULL COMMENT 'ชื่อ-สกุลผู้ป่วย',
  `visitor` varchar(255) DEFAULT NULL COMMENT 'ผู้เยี่ยม',
  `purpose` text DEFAULT NULL COMMENT 'วัตถุประสงค์การเยี่ยม',
  `facilities` text DEFAULT NULL COMMENT 'สิ่งอำนวยความสะดวก',
  `referral` text DEFAULT NULL COMMENT 'การส่งต่อ',
  `summary` text DEFAULT NULL COMMENT 'สรุป',
  `notes` text DEFAULT NULL COMMENT 'หมายเหตุ',
  `created_by` int(11) DEFAULT NULL COMMENT 'ผู้สร้าง',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'วันที่สร้าง',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'วันที่อัปเดต'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='การเยี่ยมบ้าน';

--
-- Dumping data for table `visits`
--

INSERT INTO `visits` (`id`, `patient_id`, `visit_date`, `visit_time`, `visit_number`, `visit_type_id`, `visit_type_category`, `hn`, `fullname`, `visitor`, `purpose`, `facilities`, `referral`, `summary`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, '2025-12-24', '00:00:00', 0, NULL, NULL, '100001', 'สมชาย เก่งมาก', '', 'medicine', 'catheter', 'social_work', 'medicine', '', NULL, '2025-12-24 08:07:59', '2025-12-24 08:07:59'),
(2, NULL, '2025-12-24', '00:00:09', 1, NULL, NULL, '', '', '', 'follow_up', 'tracheostomy', 'social_work', 'follow_up', '10101010', NULL, '2025-12-24 08:08:26', '2025-12-24 08:08:26'),
(3, 1, '2025-12-25', '00:00:10', 1, NULL, NULL, '100001', 'สมชาย เก่งมาก', '', 'care', 'feeding_tube', 'hospital', 'care', 'ทดสอบ', NULL, '2025-12-25 03:56:45', '2025-12-25 03:56:45'),
(4, 1, '2025-12-25', '00:00:10', 1, NULL, NULL, '100001', 'สมชาย เก่งมาก', '', 'care', 'feeding_tube', 'hospital', 'care', 'ทดสอบ', NULL, '2025-12-25 04:03:31', '2025-12-25 04:03:31'),
(5, 1, '2025-12-25', '00:00:10', 1, NULL, NULL, '100001', 'สมชาย เก่งมาก', '', 'care', 'catheter', 'hospital', 'care', 'ทดสอบ', NULL, '2025-12-25 04:05:41', '2025-12-25 04:05:41'),
(6, 1, '2025-12-25', '00:00:10', 1, NULL, NULL, '100001', 'สมชาย เก่งมาก', '', 'care', 'catheter', 'hospital', 'care', 'ทดสอบ', NULL, '2025-12-25 06:28:31', '2025-12-25 06:28:31'),
(7, 1, '2025-12-25', '00:00:13', 1, NULL, NULL, '100001', 'สมชาย เก่งมาก', '', 'care', 'catheter', 'hospital', 'care', 'ทดสอบ', NULL, '2025-12-25 06:30:58', '2025-12-25 06:30:58'),
(8, 1, '2025-12-25', '13:30:00', 1, NULL, NULL, '100001', 'สมชาย เก่งมาก', '', 'care', 'catheter', 'hospital', 'care', 'ทดสอบ', NULL, '2025-12-25 06:32:58', '2025-12-25 06:32:58'),
(9, 1, '2025-12-25', '12:00:00', 1, NULL, NULL, '100001', 'สมชาย เก่งมาก', '', 'care', 'catheter', 'hospital', 'care', 'ทดสอบ', NULL, '2025-12-25 06:33:15', '2025-12-25 06:33:15'),
(10, 1, '2025-12-25', '09:24:00', 1, NULL, NULL, '100001', 'สมชาย เก่งมาก', '', 'care', 'catheter', 'hospital', 'care', 'ทดสอบ', NULL, '2025-12-25 06:33:38', '2025-12-25 06:33:38'),
(11, 1, '2025-12-25', NULL, NULL, NULL, 'child_0_5, elderly_bedridden, ongoing_case', '100001', 'สมชาย เก่งมาก', '', '', '', '', '', '', NULL, '2025-12-26 02:38:23', '2025-12-26 02:38:23'),
(12, 1, '2025-12-25', NULL, NULL, NULL, 'child_0_5, elderly_bedridden, postpartum, chronic', '100001', 'สมชาย เก่งมาก', '', '', '', '', '', '', NULL, '2025-12-26 02:41:45', '2025-12-26 02:41:45'),
(13, 1, '2025-12-25', NULL, NULL, NULL, 'child_0_5, working_age, elderly_bedridden, postpartum, chronic', '100001', 'สมชาย เก่งมาก', '', '', '', '', '', '', NULL, '2025-12-26 02:50:34', '2025-12-26 02:50:34'),
(14, 2, '2025-12-26', NULL, NULL, NULL, 'family_normal', '12354', 'เต้ พง', '', '', '', '', '', '', NULL, '2025-12-26 03:50:00', '2025-12-26 03:50:00'),
(15, 2, '2025-12-26', NULL, NULL, NULL, 'family_normal', '12354', 'เต้ พง', 'วิลาวรรณ', '', '', '', '', '', NULL, '2025-12-26 03:50:17', '2025-12-26 03:50:17'),
(16, NULL, '2025-12-29', NULL, NULL, NULL, 'elderly_social', '', '', '', '', '', '', '', '', NULL, '2025-12-29 07:43:03', '2025-12-29 07:43:03'),
(17, 3, '2026-01-05', NULL, NULL, NULL, '', '090547', 'วิ ลา', 'วิลาวรรณ', '', '', '', '', '', NULL, '2026-01-05 03:57:31', '2026-01-05 03:57:31'),
(18, 3, '2026-01-05', NULL, NULL, NULL, 'elderly_bedridden', '090547', 'วิ ลา', 'วิลาวรรณ', '', '', '', '', '', NULL, '2026-01-05 06:38:01', '2026-01-05 06:38:01'),
(19, 3, '2026-01-05', NULL, NULL, NULL, 'elderly_homebound, elderly_social, elderly_bedridden', '090547', 'วิ ลา', 'วิลาวรรณ', '', '', '', '', '', NULL, '2026-01-05 09:09:00', '2026-01-05 09:09:00'),
(20, 3, '2026-01-05', NULL, NULL, NULL, 'elderly_homebound, elderly_social, elderly_bedridden', '090547', 'วิ ลา', 'วิลาวรรณ', '', '', '', '', '', NULL, '2026-01-06 02:13:47', '2026-01-06 02:13:47'),
(21, 3, '2026-01-05', NULL, NULL, NULL, 'elderly_homebound, elderly_social, elderly_bedridden', '090547', 'วิ ลา', 'วิลาวรรณ', '', '', '', '', '', NULL, '2026-01-06 08:47:12', '2026-01-06 08:47:12'),
(22, 3, '2026-01-05', NULL, NULL, NULL, 'elderly_homebound, elderly_social, elderly_bedridden', '090547', 'วิ ลา', 'วิลาวรรณ', '', '', '', '', '', NULL, '2026-01-06 08:47:27', '2026-01-06 08:47:27'),
(23, 3, '2026-01-05', NULL, NULL, NULL, 'elderly_homebound, elderly_social, elderly_bedridden', '090547', 'วิ ลา', 'วิลาวรรณ', '', '', '', '', '', NULL, '2026-01-06 08:49:51', '2026-01-06 08:49:51'),
(24, 3, '2026-01-05', NULL, NULL, NULL, 'elderly_homebound, elderly_social, elderly_bedridden', '090547', 'วิ ลา', 'วิลาวรรณ', '', '', '', '', '', NULL, '2026-01-07 01:48:46', '2026-01-07 01:48:46'),
(25, 3, '2026-01-05', NULL, NULL, NULL, 'elderly_homebound, elderly_social, elderly_bedridden', '090547', 'วิ ลา', 'วิลาวรรณ', '', '', '', '', '', NULL, '2026-01-07 07:32:35', '2026-01-07 07:32:35'),
(26, 3, '2026-01-10', NULL, NULL, NULL, 'elderly_homebound, elderly_social, elderly_bedridden', '090547', 'วิ ลา', 'วิลาวรรณ', '', '', '', '', '', NULL, '2026-01-07 07:33:10', '2026-01-07 07:33:10'),
(27, 3, '2026-01-10', NULL, NULL, NULL, 'elderly_homebound, elderly_social, elderly_bedridden', '090547', 'วิ ลา', 'วิลาวรรณ', '', '', '', '', '', NULL, '2026-01-08 01:49:23', '2026-01-08 01:49:23'),
(28, 3, '2026-01-10', NULL, NULL, NULL, 'family_normal, child_0_5, teenager, elderly_homebound, elderly_social, elderly_bedridden', '090547', 'วิ ลา', 'วิลาวรรณ', '', '', '', '', '', NULL, '2026-01-08 03:19:03', '2026-01-08 03:19:03');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `inhomess_assessments`
--
ALTER TABLE `inhomess_assessments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_visit_id` (`visit_id`),
  ADD KEY `idx_patient_id` (`patient_id`),
  ADD KEY `idx_assess_date` (`assess_date`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hn` (`hn`),
  ADD KEY `idx_visit_date` (`visit_date`);

--
-- Indexes for table `visits`
--
ALTER TABLE `visits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_patient_id` (`patient_id`),
  ADD KEY `idx_visit_date` (`visit_date`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `inhomess_assessments`
--
ALTER TABLE `inhomess_assessments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `visits`
--
ALTER TABLE `visits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
