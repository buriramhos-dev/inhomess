<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: inhomess_assessment.php');
    exit;
}

// collect payload (keep arrays intact)
$payload = [];
foreach ($_POST as $k => $v) {
    $payload[$k] = $v;
}

// คำนวณคะแนน Barthel Index
$barthelScore = 0;
$barthelFields = ['adl_feeding', 'adl_bathing', 'adl_grooming', 'adl_dressing', 'adl_bowels', 'adl_bladder', 'adl_toilet', 'adl_transfer', 'adl_mobility', 'adl_stairs'];
foreach ($barthelFields as $field) {
    if (isset($_POST[$field]) && is_array($_POST[$field])) {
        foreach ($_POST[$field] as $val) {
            $barthelScore += (int)$val;
        }
    }
}

$data = [
    'visit_id' => $_POST['visit_id'] ?? null,
    'patient_id' => $_POST['patient_id'] ?? null,
    'assessor_id' => $_POST['assessor_id'] ?? null,
    'hn' => $_POST['hn'] ?? '',
    'fullname' => $_POST['fullname'] ?? '',
    'assess_date' => $_POST['assess_date'] ?? null,
    'assessor' => $_POST['assessor'] ?? '',
    'payload' => $payload,
    'score' => $barthelScore > 0 ? $barthelScore : null,
];

$savedToDb = false;
$errorMessage = '';
$lastInsertId = null;

try {
    require_once __DIR__ . '/db.php';
    
    // ตรวจสอบการเชื่อมต่อฐานข้อมูล
    global $mysqli;
    if (!isset($mysqli) || $mysqli->connect_errno) {
        $errorMessage = 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้: ' . ($mysqli->connect_error ?? 'Unknown error');
        error_log($errorMessage);
    } elseif (function_exists('db_insert_inhomess_assessment')) {
        // สร้างเรคคอร์ดใหม่เสมอ (ไม่ update ของเดิม) เพื่อให้สามารถนับจำนวนครั้งได้ถูกต้อง
        $savedToDb = db_insert_inhomess_assessment($data);
        
        if ($savedToDb) {
            // ดึง ID ที่เพิ่งบันทึก
            $lastInsertId = $mysqli->insert_id;
        } else {
            // ตรวจสอบ error
            if (isset($mysqli) && $mysqli->error) {
                $errorMessage = $mysqli->error;
            } else {
                $errorMessage = 'ไม่สามารถบันทึกข้อมูลได้ (ไม่ทราบสาเหตุ)';
            }
            error_log("Error saving inhomess assessment: " . $errorMessage);
        }
    } else {
        $errorMessage = 'Function db_insert_inhomess_assessment not found';
        error_log($errorMessage);
    }
} catch (Throwable $e) {
    $savedToDb = false;
    $errorMessage = $e->getMessage();
    error_log("Exception saving inhomess assessment: " . $errorMessage);
}

if (!$savedToDb) {
    // fallback to JSON file if DB not available
    $file = __DIR__ . '/inhomess_data.json';
    $all = [];
    if (file_exists($file)) $all = json_decode(file_get_contents($file), true) ?: [];
    $entry = $data;
    $entry['created_at'] = date('Y-m-d H:i:s');
    $all[] = $entry;
    $jsonSaved = file_put_contents($file, json_encode($all, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
    
    // แสดง error และ redirect
    if ($errorMessage) {
        $alertMsg = "บันทึกลงฐานข้อมูลไม่สำเร็จ";
        if ($jsonSaved) {
            $alertMsg .= " แต่บันทึกลงไฟล์ JSON แล้ว";
        }
        $alertMsg .= "\\n\\nข้อผิดพลาด: " . addslashes($errorMessage);
        $redirectUrl = 'patient_overview.php';
        if (!empty($data['patient_id'])) {
            $redirectUrl .= '?patient_id=' . (int)$data['patient_id'];
        } elseif (!empty($data['hn'])) {
            $redirectUrl .= '?hn=' . urlencode($data['hn']);
            if (!empty($data['fullname'])) {
                $redirectUrl .= '&fullname=' . urlencode($data['fullname']);
            }
        }
        $redirectUrl .= '&toast=error&msg=' . urlencode($alertMsg);
        echo '<script>location.href="' . $redirectUrl . '";</script>';
    } else {
        if ($jsonSaved) {
            $redirectUrl = 'patient_overview.php';
            if (!empty($data['patient_id'])) {
                $redirectUrl .= '?patient_id=' . (int)$data['patient_id'];
            } elseif (!empty($data['hn'])) {
                $redirectUrl .= '?hn=' . urlencode($data['hn']);
                if (!empty($data['fullname'])) {
                    $redirectUrl .= '&fullname=' . urlencode($data['fullname']);
                }
            }
            $redirectUrl .= '&toast=warning&msg=' . urlencode('บันทึกการประเมินเรียบร้อย (บันทึกลงไฟล์ JSON)');
            echo '<script>location.href="' . $redirectUrl . '";</script>';
        } else {
            $redirectUrl = 'inhomess_assessment.php?toast=error&msg=' . urlencode('ไม่สามารถบันทึกข้อมูลได้ กรุณาตรวจสอบสิทธิ์การเขียนไฟล์');
            echo '<script>location.href="' . $redirectUrl . '";</script>';
        }
    }
} else {
    // บันทึกสำเร็จ
    $redirectUrl = 'patient_overview.php';
    if (!empty($data['patient_id'])) {
        $redirectUrl .= '?patient_id=' . (int)$data['patient_id'];
    } elseif (!empty($data['hn'])) {
        $redirectUrl .= '?hn=' . urlencode($data['hn']);
        if (!empty($data['fullname'])) {
            $redirectUrl .= '&fullname=' . urlencode($data['fullname']);
        }
    }
    $redirectUrl .= '&toast=success&msg=' . urlencode('บันทึกการประเมินเรียบร้อย');
    echo '<script>location.href="' . $redirectUrl . '";</script>';
}
exit;
?>