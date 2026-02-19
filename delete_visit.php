<?php
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: visits_summary.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

// เก็บ query parameter q (ถ้ามีการค้นหา)
$referer = $_SERVER['HTTP_REFERER'] ?? $_POST['referer'] ?? '';
$search_query = isset($_POST['q']) ? trim($_POST['q']) : '';
// ถ้าไม่มีใน POST ให้ลองดึงจาก referer URL
if (empty($search_query) && $referer) {
    $refererParts = parse_url($referer);
    if (isset($refererParts['query'])) {
        parse_str($refererParts['query'], $refererParams);
        $search_query = $refererParams['q'] ?? '';
    }
}

if ($id <= 0) {
    $redirectUrl = 'visits_summary.php';
    $params = [];
    $params[] = 'toast=error';
    $params[] = 'msg=' . urlencode('ไม่พบข้อมูลที่ต้องการลบ');
    if (!empty($search_query)) {
        $params[] = 'q=' . urlencode($search_query);
    }
    $redirectUrl .= '?' . implode('&', $params);
    echo '<script>window.location.replace("' . htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') . '&_t=' . time() . '");</script>';
    exit;
}

// ดึงข้อมูลการเยี่ยมก่อนลบ เพื่อใช้สำหรับ redirect
$visit = db_get_visit_by_id($id);

// ตรวจสอบว่าพบข้อมูลการเยี่ยมหรือไม่
if (!$visit) {
    $redirectUrl = 'visits_summary.php';
    $params = [];
    $params[] = 'toast=error';
    $params[] = 'msg=' . urlencode('ไม่พบข้อมูลการเยี่ยมบ้านที่ต้องการลบ (ID: ' . $id . ')');
    if (!empty($search_query)) {
        $params[] = 'q=' . urlencode($search_query);
    }
    $redirectUrl .= '?' . implode('&', $params);
    echo '<script>';
    echo 'console.error("Delete visit error - Visit not found:", {id: ' . $id . ', post_data: ' . json_encode($_POST) . '});';
    echo 'window.location.replace("' . htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') . '&_t=' . time() . '");';
    echo '</script>';
    exit;
}

$patient_id = $visit['patient_id'] ?? null;
$hn = $visit['hn'] ?? '';

// ลบข้อมูลการเยี่ยม
$success = db_delete_visit($id);

if ($success) {
    // สร้าง URL สำหรับ redirect ไปที่ visits_summary.php
    $redirectUrl = 'visits_summary.php';
    $params = [];
    $params[] = 'toast=success';
    $params[] = 'msg=' . urlencode('ลบข้อมูลการเยี่ยมบ้านเรียบร้อย');
    if (!empty($search_query)) {
        $params[] = 'q=' . urlencode($search_query);
    }
    $redirectUrl .= '?' . implode('&', $params);
    echo '<script>window.location.replace("' . htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') . '&_t=' . time() . '");</script>';
} else {
    // ถ้าลบไม่สำเร็จ ให้ redirect กลับไปที่ visits_summary.php
    $redirectUrl = 'visits_summary.php';
    $params = [];
    $params[] = 'toast=error';
    $params[] = 'msg=' . urlencode('ลบข้อมูลไม่สำเร็จ');
    if (!empty($search_query)) {
        $params[] = 'q=' . urlencode($search_query);
    }
    $redirectUrl .= '?' . implode('&', $params);
    echo '<script>window.location.replace("' . htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') . '&_t=' . time() . '");</script>';
}
exit;
?>
