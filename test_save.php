<?php
// ไฟล์ทดสอบการบันทึกข้อมูล
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/db.php';

// ทดสอบการเชื่อมต่อฐานข้อมูล
if (isset($mysqli) && !$mysqli->connect_errno) {
    echo "<h2>การเชื่อมต่อฐานข้อมูล: สำเร็จ</h2>";
    echo "<p>Database: " . $mysqli->query("SELECT DATABASE()")->fetch_row()[0] . "</p>";
    
    // ตรวจสอบว่ามีตารางหรือไม่
    $result = $mysqli->query("SHOW TABLES LIKE 'inhomess_assessments'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✓ ตาราง inhomess_assessments มีอยู่แล้ว</p>";
        
        // แสดงโครงสร้างตาราง
        echo "<h3>โครงสร้างตาราง:</h3>";
        $result = $mysqli->query("DESCRIBE inhomess_assessments");
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>✗ ตาราง inhomess_assessments ไม่มีอยู่</p>";
    }
    
    // ทดสอบการบันทึกข้อมูล
    echo "<h3>ทดสอบการบันทึกข้อมูล:</h3>";
    $testData = [
        'visit_id' => null,
        'patient_id' => null,
        'assessor_id' => null,
        'hn' => 'TEST001',
        'fullname' => 'ทดสอบระบบ',
        'assess_date' => date('Y-m-d'),
        'assessor' => 'ผู้ทดสอบ',
        'payload' => ['test' => 'data'],
        'score' => 50.5
    ];
    
    if (function_exists('db_insert_inhomess_assessment')) {
        $result = db_insert_inhomess_assessment($testData);
        if ($result) {
            echo "<p style='color: green;'>✓ บันทึกข้อมูลสำเร็จ</p>";
        } else {
            echo "<p style='color: red;'>✗ บันทึกข้อมูลไม่สำเร็จ</p>";
            echo "<p>Error: " . $mysqli->error . "</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ ฟังก์ชัน db_insert_inhomess_assessment ไม่พบ</p>";
    }
    
} else {
    echo "<h2 style='color: red;'>การเชื่อมต่อฐานข้อมูล: ล้มเหลว</h2>";
    if (isset($mysqli)) {
        echo "<p>Error: " . $mysqli->connect_error . "</p>";
    }
}

echo "<hr>";
echo "<a href='index.php'>กลับหน้าหลัก</a>";
?>

