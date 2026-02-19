<?php
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// collect payload (keep arrays intact)
$payload = [];
foreach ($_POST as $k => $v) {
    if ($k !== 'id') {
        $payload[$k] = $v;
    }
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

$success = db_update_assessment($id, $data);

if ($success) {
    // สร้าง URL สำหรับ redirect ไปที่ patient_overview.php
    $redirectUrl = 'patient_overview.php';
    if (!empty($data['patient_id'])) {
        $redirectUrl .= '?patient_id=' . (int)$data['patient_id'];
    } elseif (!empty($data['hn'])) {
        $redirectUrl .= '?hn=' . urlencode($data['hn']);
        if (!empty($data['fullname'])) {
            $redirectUrl .= '&fullname=' . urlencode($data['fullname']);
        }
    }
    $redirectUrl .= '&toast=success&msg=' . urlencode('แก้ไขข้อมูลการประเมินเรียบร้อย');
    echo '<script>location.href="' . $redirectUrl . '";</script>';
} else {
    $redirectUrl = 'edit_assessment.php?id=' . $id . '&toast=error&msg=' . urlencode('แก้ไขข้อมูลไม่สำเร็จ');
    echo '<script>location.href="' . $redirectUrl . '";</script>';
}
exit;
?>

