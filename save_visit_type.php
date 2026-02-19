<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: visit_types.php'); exit; }

// normalize checkbox fields (may be single string or array)
$purpose = isset($_POST['purpose']) ? (is_array($_POST['purpose']) ? implode(', ', $_POST['purpose']) : $_POST['purpose']) : '';
$facilities = isset($_POST['facilities']) ? (is_array($_POST['facilities']) ? implode(', ', $_POST['facilities']) : $_POST['facilities']) : '';
$referral = isset($_POST['referral']) ? (is_array($_POST['referral']) ? implode(', ', $_POST['referral']) : $_POST['referral']) : '';
$visit_type_category = isset($_POST['visit_type_category']) ? (is_array($_POST['visit_type_category']) ? implode(', ', $_POST['visit_type_category']) : $_POST['visit_type_category']) : '';

// แปลงรูปแบบเวลา: 13.30 -> 13:30:00, 9.00 -> 09:00:00, 13:30 -> 13:30:00
$visit_time = $_POST['visit_time'] ?? null;
if ($visit_time && trim($visit_time) !== '') {
    $visit_time = trim($visit_time);
    // แปลงจุด (.) เป็น colon (:)
    $visit_time = str_replace('.', ':', $visit_time);
    
    // ถ้าเป็นรูปแบบ H.M หรือ H:M ให้แปลงเป็น H:M:00
    if (preg_match('/^(\d{1,2})[:.](\d{2})$/', $visit_time, $matches)) {
        $hour = (int)$matches[1];
        $minute = (int)$matches[2];
        // ตรวจสอบความถูกต้อง
        if ($hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59) {
            $visit_time = sprintf('%02d:%02d:00', $hour, $minute);
        } else {
            $visit_time = null; // ถ้าไม่ถูกต้องให้เป็น null
        }
    } 
    // ถ้าเป็นรูปแบบ H:M:S แล้ว ให้ตรวจสอบความถูกต้อง
    elseif (preg_match('/^(\d{1,2}):(\d{2}):(\d{2})$/', $visit_time, $matches)) {
        $hour = (int)$matches[1];
        $minute = (int)$matches[2];
        $second = (int)$matches[3];
        if ($hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59 && $second >= 0 && $second <= 59) {
            $visit_time = sprintf('%02d:%02d:%02d', $hour, $minute, $second);
        } else {
            $visit_time = null;
        }
    } else {
        // ถ้าไม่ตรงรูปแบบใดๆ ให้เป็น null
        $visit_time = null;
    }
}

$data = [
    'visit_number' => $_POST['visit_number'] ?? null,
    'visit_date' => $_POST['visit_date'] ?? null,
    'visit_time' => $visit_time,
    'purpose' => $purpose,
    'facilities' => $facilities,
    'referral' => $referral,
    'visit_type_category' => $visit_type_category,
    'notes' => $_POST['notes'] ?? '',
    // optional patient info (if included on form)
    'hn' => $_POST['hn'] ?? '',
    'fullname' => $_POST['fullname'] ?? '',
    'visitor' => $_POST['visitor'] ?? '',
    'patient_id' => $_POST['patient_id'] ?? null,
    'created_by' => null,
];

require_once __DIR__ . '/db.php';

$savedToDb = false;
$errorMessage = '';
$visit_id = isset($_POST['visit_id']) ? (int)$_POST['visit_id'] : null;

// ถ้ามี visit_id แสดงว่าเป็นการแก้ไข ให้อัปเดต ถ้าไม่มีให้สร้างใหม่
try {
    if ($visit_id && $visit_id > 0 && function_exists('db_update_visit')) {
        // อัปเดตเรคคอร์ดเดิม
        $savedToDb = db_update_visit($visit_id, $data);
        if (!$savedToDb && isset($mysqli) && $mysqli instanceof mysqli && $mysqli->error) {
            $errorMessage = $mysqli->error;
        }
    } elseif (function_exists('db_insert_visit')) {
        // สร้างข้อมูลใหม่เสมอ (เมื่อไม่มี visit_id)
        $savedToDb = db_insert_visit($data);
        if (!$savedToDb && isset($mysqli) && $mysqli instanceof mysqli && $mysqli->error) {
            $errorMessage = $mysqli->error;
        }
    } else {
        $errorMessage = 'ไม่พบฟังก์ชัน db_insert_visit หรือ db_update_visit';
    }
} catch (Throwable $e) {
    $savedToDb = false;
    $errorMessage = $e->getMessage();
}

if (!$savedToDb) {
    // fallback: บันทึกลงไฟล์ JSON เพื่อไม่ให้ข้อมูลหาย และแจ้งเตือนผู้ใช้
    $file = __DIR__ . '/visit_types_data.json';
    $all = [];
    if (file_exists($file)) $all = json_decode(file_get_contents($file), true) ?: [];
    $entry = $data;
    $entry['created_at'] = date('Y-m-d H:i:s');
    $all[] = $entry;
    file_put_contents($file, json_encode($all, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));

    $alert = 'บันทึกลงฐานข้อมูลไม่สำเร็จ แต่บันทึกลงไฟล์ JSON ชั่วคราวแล้ว';
    if ($errorMessage) {
        $alert .= "\n\nรายละเอียดข้อผิดพลาด: " . $errorMessage;
    }
    $redirectUrl = 'index.php?toast=warning&msg=' . urlencode($alert);
    echo '<script>location.href="' . $redirectUrl . '";</script>';
    exit;
}

// กรณีบันทึกสำเร็จ ให้ไปหน้าแบบประเมิน Inhomess Assessment
// พร้อมส่ง hn, fullname และ patient_id (ถ้าหาได้) ไปด้วย
$patient_id = $data['patient_id'] ?? null;
if ((!$patient_id || $patient_id === '') && function_exists('db_find_patient_by_hn') && !empty($data['hn'])) {
    $patient_id = db_find_patient_by_hn($data['hn']);
}

$redirectUrl = 'inhomess_assessment.php'
    . '?hn=' . urlencode($data['hn'])
    . '&fullname=' . urlencode($data['fullname']);

if ($patient_id) {
    $redirectUrl .= '&patient_id=' . urlencode($patient_id);
}

// ถ้าเป็นการแก้ไข จะมี visit_id ส่งต่อไปด้วย
if ($visit_id && $visit_id > 0) {
    $redirectUrl .= '&visit_id=' . urlencode($visit_id);
}

$redirectUrl .= '&toast=success&msg=' . urlencode('บันทึกประเภทการเยี่ยมบ้านเรียบร้อย กรุณาทำแบบประเมิน Inhomess ต่อ');

echo '<script>location.href="' . $redirectUrl . '";</script>';
exit;
?>
