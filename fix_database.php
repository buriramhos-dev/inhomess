<?php
// ไฟล์แก้ไขฐานข้อมูลให้บันทึกข้อมูลได้
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/db.php';

echo "<h2>กำลังแก้ไขฐานข้อมูล...</h2>";

if (!isset($mysqli) || $mysqli->connect_errno) {
    echo "<p style='color: red;'>ไม่สามารถเชื่อมต่อฐานข้อมูลได้: " . ($mysqli->connect_error ?? 'Unknown') . "</p>";
    exit;
}

// 1. ลบ foreign key constraints ที่อาจทำให้เกิดปัญหา
echo "<h3>1. ลบ Foreign Key Constraints...</h3>";
$mysqli->query("SET FOREIGN_KEY_CHECKS = 0");

// 2. แก้ไขตาราง inhomess_assessments
echo "<h3>2. แก้ไขตาราง inhomess_assessments...</h3>";

// ลบ foreign keys ถ้ามี
$mysqli->query("ALTER TABLE `inhomess_assessments` DROP FOREIGN KEY IF EXISTS `fk_inhomess_visit`");
$mysqli->query("ALTER TABLE `inhomess_assessments` DROP FOREIGN KEY IF EXISTS `fk_inhomess_patient`");

// แก้ไข column data จาก JSON เป็น LONGTEXT
$result = $mysqli->query("SHOW COLUMNS FROM `inhomess_assessments` LIKE 'data'");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if (stripos($row['Type'], 'json') !== false || stripos($row['Type'], 'text') === false) {
        $mysqli->query("ALTER TABLE `inhomess_assessments` MODIFY `data` LONGTEXT DEFAULT NULL");
        echo "<p style='color: green;'>✓ แก้ไข column data เป็น LONGTEXT แล้ว</p>";
    } else {
        echo "<p style='color: blue;'>- column data ถูกต้องแล้ว</p>";
    }
} else {
    echo "<p style='color: orange;'>- ไม่พบ column data</p>";
}

// ตรวจสอบและแก้ไข column อื่นๆ ให้รองรับ NULL
$columns = [
    'visit_id' => 'INT(11) DEFAULT NULL',
    'patient_id' => 'INT(11) DEFAULT NULL',
    'assessor_id' => 'INT(11) DEFAULT NULL',
    'hn' => 'VARCHAR(50) DEFAULT NULL',
    'fullname' => 'VARCHAR(255) DEFAULT NULL',
    'assess_date' => 'DATE DEFAULT NULL',
    'assessor' => 'VARCHAR(255) DEFAULT NULL',
    'score' => 'DECIMAL(10,2) DEFAULT NULL'
];

foreach ($columns as $col => $def) {
    $result = $mysqli->query("SHOW COLUMNS FROM `inhomess_assessments` LIKE '$col'");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($row['Null'] !== 'YES') {
            $mysqli->query("ALTER TABLE `inhomess_assessments` MODIFY `$col` $def");
            echo "<p style='color: green;'>✓ แก้ไข column $col ให้รองรับ NULL แล้ว</p>";
        }
    }
}

// 3. แก้ไขตาราง visits
echo "<h3>3. แก้ไขตาราง visits...</h3>";
$mysqli->query("ALTER TABLE `visits` DROP FOREIGN KEY IF EXISTS `fk_visits_patient`");

// 4. สร้างตารางใหม่ถ้ายังไม่มี
echo "<h3>4. ตรวจสอบตาราง...</h3>";
$result = $mysqli->query("SHOW TABLES LIKE 'inhomess_assessments'");
if ($result->num_rows == 0) {
    echo "<p style='color: orange;'>- สร้างตาราง inhomess_assessments...</p>";
    $mysqli->query("CREATE TABLE `inhomess_assessments` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "<p style='color: green;'>✓ สร้างตาราง inhomess_assessments แล้ว</p>";
} else {
    echo "<p style='color: blue;'>- ตาราง inhomess_assessments มีอยู่แล้ว</p>";
}

// 5. เปิด foreign key checks กลับมา
$mysqli->query("SET FOREIGN_KEY_CHECKS = 1");

// 6. ทดสอบการบันทึกข้อมูล
echo "<h3>5. ทดสอบการบันทึกข้อมูล...</h3>";
$testData = [
    'visit_id' => null,
    'patient_id' => null,
    'assessor_id' => null,
    'hn' => 'TEST001',
    'fullname' => 'ทดสอบระบบ',
    'assess_date' => date('Y-m-d'),
    'assessor' => 'ผู้ทดสอบ',
    'payload' => ['test' => 'data', 'hn' => 'TEST001', 'fullname' => 'ทดสอบระบบ'],
    'score' => 50.5
];

if (function_exists('db_insert_inhomess_assessment')) {
    $result = db_insert_inhomess_assessment($testData);
    if ($result) {
        echo "<p style='color: green;'>✓ ทดสอบการบันทึกข้อมูลสำเร็จ!</p>";
        
        // ลบข้อมูลทดสอบ
        $mysqli->query("DELETE FROM inhomess_assessments WHERE hn = 'TEST001'");
        echo "<p style='color: blue;'>- ลบข้อมูลทดสอบแล้ว</p>";
    } else {
        echo "<p style='color: red;'>✗ ทดสอบการบันทึกข้อมูลไม่สำเร็จ</p>";
        if (isset($mysqli) && $mysqli->error) {
            echo "<p style='color: red;'>Error: " . $mysqli->error . "</p>";
        }
    }
} else {
    echo "<p style='color: red;'>✗ ฟังก์ชัน db_insert_inhomess_assessment ไม่พบ</p>";
}

echo "<hr>";
echo "<h3>สรุป</h3>";
echo "<p>การแก้ไขฐานข้อมูลเสร็จสิ้นแล้ว</p>";
echo "<p><a href='index.php'>← กลับหน้าหลัก</a> | <a href='inhomess_assessment.php'>ไปหน้าประเมิน</a></p>";
?>

