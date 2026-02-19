<?php
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

// Debug: เก็บข้อมูล POST สำหรับ debugging
$debug_info = [
    'post_id' => $_POST['id'] ?? 'not set',
    'id' => $id,
    'post_data' => $_POST
];

if ($id <= 0) {
    $referer = $_SERVER['HTTP_REFERER'] ?? $_POST['referer'] ?? '';
    $from_visits_summary = strpos($referer, 'visits_summary.php') !== false;
    
    // เก็บ query parameter q (ถ้ามีการค้นหา)
    $search_query = isset($_POST['q']) ? trim($_POST['q']) : '';
    if (empty($search_query) && $referer) {
        $refererParts = parse_url($referer);
        if (isset($refererParts['query'])) {
            parse_str($refererParts['query'], $refererParams);
            $search_query = $refererParams['q'] ?? '';
        }
    }
    
    if ($from_visits_summary) {
        $redirectUrl = 'visits_summary.php';
        $params = [];
        $params[] = 'toast=error';
        $params[] = 'msg=' . urlencode('ไม่พบ ID ของข้อมูลที่ต้องการลบ (ID: ' . $id . ')');
        if (!empty($search_query)) {
            $params[] = 'q=' . urlencode($search_query);
        }
        $redirectUrl .= '?' . implode('&', $params);
    } else {
        $redirectUrl = 'index.php?toast=error&msg=' . urlencode('ไม่พบ ID ของข้อมูลที่ต้องการลบ');
    }
    echo '<script>';
    echo 'console.error("Delete error - Invalid ID:", ' . json_encode($debug_info) . ');';
    echo 'window.location.replace("' . htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') . '&_t=' . time() . '");';
    echo '</script>';
    exit;
}

// ดึงข้อมูลการประเมินก่อนลบ เพื่อใช้สำหรับ redirect
$assessment = db_get_assessment_by_id($id);

// ตรวจสอบว่าพบข้อมูลการประเมินหรือไม่
if (!$assessment) {
    $referer = $_SERVER['HTTP_REFERER'] ?? $_POST['referer'] ?? '';
    $from_visits_summary = strpos($referer, 'visits_summary.php') !== false;
    
    // เก็บ query parameter q (ถ้ามีการค้นหา)
    $search_query = isset($_POST['q']) ? trim($_POST['q']) : '';
    if (empty($search_query) && $referer) {
        $refererParts = parse_url($referer);
        if (isset($refererParts['query'])) {
            parse_str($refererParts['query'], $refererParams);
            $search_query = $refererParams['q'] ?? '';
        }
    }
    
    if ($from_visits_summary) {
        $redirectUrl = 'visits_summary.php';
        $params = [];
        $params[] = 'toast=error';
        $params[] = 'msg=' . urlencode('ไม่พบข้อมูลการประเมินที่ต้องการลบ (ID: ' . $id . ')');
        if (!empty($search_query)) {
            $params[] = 'q=' . urlencode($search_query);
        }
        $redirectUrl .= '?' . implode('&', $params);
    } else {
        $redirectUrl = 'index.php?toast=error&msg=' . urlencode('ไม่พบข้อมูลการประเมินที่ต้องการลบ (ID: ' . $id . ')');
    }
    echo '<script>';
    echo 'console.error("Delete error - Assessment not found:", ' . json_encode(['id' => $id, 'debug' => $debug_info]) . ');';
    echo 'window.location.replace("' . htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') . '&_t=' . time() . '");';
    echo '</script>';
    exit;
}

$patient_id = $assessment['patient_id'] ?? null;
$hn = $assessment['hn'] ?? '';
$fullname = $assessment['fullname'] ?? '';

// ลบข้อมูลการประเมิน
$success = db_delete_assessment($id);

// ตรวจสอบว่ามาจากหน้าไหน (จาก referer หรือ parameter)
$referer = $_SERVER['HTTP_REFERER'] ?? $_POST['referer'] ?? '';
$from_visits_summary = strpos($referer, 'visits_summary.php') !== false;

// เก็บ query parameter q (ถ้ามีการค้นหา)
$search_query = isset($_POST['q']) ? trim($_POST['q']) : '';
// ถ้าไม่มีใน POST ให้ลองดึงจาก referer URL
if (empty($search_query) && $referer) {
    $refererParts = parse_url($referer);
    if (isset($refererParts['query'])) {
        parse_str($refererParts['query'], $refererParams);
        $search_query = $refererParams['q'] ?? '';
    }
}

if ($success) {
    // ถ้ามาจาก visits_summary.php ให้ redirect กลับไปที่ visits_summary.php
    if ($from_visits_summary) {
        $redirectUrl = 'visits_summary.php';
        
        // สร้าง query parameters
        $params = [];
        $params[] = 'toast=success';
        $params[] = 'msg=' . urlencode('ลบข้อมูลการประเมินเรียบร้อย');
        
        // เพิ่ม query parameter q ถ้ามีการค้นหา
        if (!empty($search_query)) {
            $params[] = 'q=' . urlencode($search_query);
        }
        
        $redirectUrl .= '?' . implode('&', $params);
        
        // ใช้ window.location.replace เพื่อให้ reload ข้อมูลใหม่ และเพิ่ม timestamp เพื่อป้องกัน cache
        echo '<script>';
        echo 'window.location.replace("' . htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') . '&_t=' . time() . '");';
        echo '</script>';
    } else {
        // ถ้ามาจากหน้าอื่น ให้ redirect ไปที่ patient_overview.php
        $redirectUrl = 'patient_overview.php';
        if ($patient_id) {
            $redirectUrl .= '?patient_id=' . (int)$patient_id;
        } elseif ($hn) {
            $redirectUrl .= '?hn=' . urlencode($hn);
            if ($fullname) {
                $redirectUrl .= '&fullname=' . urlencode($fullname);
            }
        } else {
            // ถ้าไม่มีข้อมูลผู้ป่วย ให้ redirect ไปที่ index.php
            $redirectUrl = 'index.php';
        }
        $redirectUrl .= '&toast=success&msg=' . urlencode('ลบข้อมูลการประเมินเรียบร้อย');
        echo '<script>location.href="' . $redirectUrl . '";</script>';
    }
} else {
    // ถ้าลบไม่สำเร็จ - อาจเกิดจาก foreign key constraint หรือปัญหาอื่นๆ
    $errorMsg = 'ลบข้อมูลการประเมินไม่สำเร็จ กรุณาตรวจสอบว่ามีข้อมูลที่เกี่ยวข้องหรือไม่';
    
    if ($from_visits_summary) {
        $redirectUrl = 'visits_summary.php';
        
        // สร้าง query parameters
        $params = [];
        $params[] = 'toast=error';
        $params[] = 'msg=' . urlencode($errorMsg);
        
        // เพิ่ม query parameter q ถ้ามีการค้นหา
        if (!empty($search_query)) {
            $params[] = 'q=' . urlencode($search_query);
        }
        
        $redirectUrl .= '?' . implode('&', $params);
        
        echo '<script>';
        echo 'window.location.replace("' . htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') . '&_t=' . time() . '");';
        echo '</script>';
    } else {
        $redirectUrl = 'patient_overview.php';
        if ($patient_id) {
            $redirectUrl .= '?patient_id=' . (int)$patient_id;
        } elseif ($hn) {
            $redirectUrl .= '?hn=' . urlencode($hn);
            if ($fullname) {
                $redirectUrl .= '&fullname=' . urlencode($fullname);
            }
        } else {
            $redirectUrl = 'index.php';
        }
        $redirectUrl .= '&toast=error&msg=' . urlencode($errorMsg);
        echo '<script>location.href="' . $redirectUrl . '";</script>';
    }
}
exit;
?>

