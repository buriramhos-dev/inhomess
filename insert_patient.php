<?php
require_once __DIR__ . '/db.php';

// ตรวจสอบว่าเป็น POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: add.php');
    exit;
}

// รับข้อมูลจาก form
$data = [
    'no' => $_POST['no'] ?? '',
    'visit_date' => $_POST['visit_date'] ?? '',
    'hn' => trim($_POST['hn'] ?? ''),
    'fullname' => trim($_POST['fullname'] ?? ''),
    'age_y' => $_POST['age_y'] ?? '',
    'age_m' => $_POST['age_m'] ?? '',
    'gender' => $_POST['gender'] ?? '',
    'gender_note' => $_POST['gender_note'] ?? '',
    'address_no' => $_POST['address_no'] ?? '',
    'address_moo' => $_POST['address_moo'] ?? '',
    'address_tambon' => $_POST['address_tambon'] ?? '',
    'address_amphur' => $_POST['address_amphur'] ?? '',
    'address_province' => $_POST['address_province'] ?? '',
    'live_with' => $_POST['live_with'] ?? '',
    'residence_type' => $_POST['residence_type'] ?? '',
    'pre_visit_illness' => $_POST['pre_visit_illness'] ?? '',
    'is_imc' => $_POST['is_imc'] ?? '',
    'visit_reason' => $_POST['visit_reason'] ?? '',
    'symptoms_found' => $_POST['symptoms_found'] ?? '',
    'medical_equipment' => $_POST['medical_equipment'] ?? [],
    'problems_found' => $_POST['problems_found'] ?? '',
    'solution' => $_POST['solution'] ?? '',
    'consultation' => $_POST['consultation'] ?? '',
    'no_consultation' => $_POST['no_consultation'] ?? '',
    'imc' => $_POST['imc'] ?? '',
    'health_note' => $_POST['health_note'] ?? '',
    'consult_yes' => isset($_POST['consult_yes']) ? 1 : 0,
    'consult_no' => isset($_POST['consult_no']) ? 1 : 0,
    'medicine_yes' => isset($_POST['medicine_yes']) ? 1 : 0,
    'medicine_no' => isset($_POST['medicine_no']) ? 1 : 0,
    'wc_info' => $_POST['wc_info'] ?? '',
    'general_note' => $_POST['general_note'] ?? '',
];

// ตรวจสอบข้อมูลที่จำเป็น
if (empty($data['hn']) || empty($data['fullname'])) {
    $redirectUrl = 'add.php?toast=error&msg=' . urlencode('กรุณากรอก HN และชื่อ-สกุล');
    echo '<script>location.href="' . $redirectUrl . '";</script>';
    exit;
}

// บันทึกข้อมูลลงฐานข้อมูล
$savedToDb = false;
$errorMessage = '';

try {
    if (function_exists('db_insert_patient')) {
        $savedToDb = db_insert_patient($data);
        if (!$savedToDb) {
            global $mysqli;
            $errorMessage = isset($mysqli) && $mysqli->error ? $mysqli->error : 'ไม่สามารถบันทึกข้อมูลได้';
            error_log("Error inserting patient: " . $errorMessage);
        }
    } else {
        $errorMessage = 'Function db_insert_patient not found';
        error_log($errorMessage);
    }
} catch (Throwable $e) {
    $savedToDb = false;
    $errorMessage = $e->getMessage();
    error_log("Exception inserting patient: " . $errorMessage);
}

// แสดงผลลัพธ์
if ($savedToDb) {
    // หาค่า patient_id จาก HN ที่เพิ่งบันทึก (ถ้ามีในฐานข้อมูล)
    $patient_id = null;
    if (function_exists('db_find_patient_by_hn') && !empty($data['hn'])) {
        $patient_id = db_find_patient_by_hn($data['hn']);
    }

    // หลังบันทึกสำเร็จ ให้ไปหน้าเลือกประเภทการเยี่ยมบ้านทันที
    $redirectUrl = 'visit_types.php'
        . '?hn=' . urlencode($data['hn'])
        . '&fullname=' . urlencode($data['fullname']);

    if ($patient_id) {
        $redirectUrl .= '&patient_id=' . urlencode($patient_id);
    }

    // ส่ง toast แจ้งเตือนความสำเร็จที่หน้าถัดไป
    $redirectUrl .= '&toast=success&msg=' . urlencode('บันทึกข้อมูลผู้ป่วยเรียบร้อย กรุณาเลือกประเภทการเยี่ยมบ้าน');

    echo '<script>location.href="' . $redirectUrl . '";</script>';
} else {
    // fallback to JSON file if DB not available
    $file = __DIR__ . '/data.json';
    $all = [];
    if (file_exists($file)) {
        $all = json_decode(file_get_contents($file), true) ?: [];
    }
    $all[] = $data;
    $jsonSaved = file_put_contents($file, json_encode($all, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
    
    if ($jsonSaved) {
        $alertMsg = 'บันทึกข้อมูลผู้ป่วยเรียบร้อย (บันทึกลงไฟล์ JSON)' . "\n\n" . 'ข้อผิดพลาดฐานข้อมูล: ' . $errorMessage;
        $redirectUrl = 'index.php?toast=warning&msg=' . urlencode($alertMsg);
        echo '<script>location.href="' . $redirectUrl . '";</script>';
    } else {
        $alertMsg = 'ไม่สามารถบันทึกข้อมูลได้' . "\n\n" . 'ข้อผิดพลาด: ' . $errorMessage;
        $redirectUrl = 'add.php?toast=error&msg=' . urlencode($alertMsg);
        echo '<script>location.href="' . $redirectUrl . '";</script>';
    }
}
exit;
?>

