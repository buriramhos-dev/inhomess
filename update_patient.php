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

// ประมวลผล medical_equipment (checkbox array)
$medical_equipment = isset($_POST['medical_equipment']) && is_array($_POST['medical_equipment']) 
    ? implode(', ', $_POST['medical_equipment']) 
    : '';

$data = [
    'no' => $_POST['no'] ?? '',
    'visit_date' => $_POST['visit_date'] ?? '',
    'hn' => $_POST['hn'] ?? '',
    'fullname' => $_POST['fullname'] ?? '',
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
    'medical_equipment' => $medical_equipment,
    'problems_found' => $_POST['problems_found'] ?? '',
    'solution' => $_POST['solution'] ?? '',
    'consultation' => isset($_POST['consultation']) ? 1 : 0,
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

$success = db_update_patient($id, $data);

if ($success) {
    $redirectUrl = 'index.php?toast=success&msg=' . urlencode('แก้ไขข้อมูลผู้ป่วยเรียบร้อย');
    echo '<script>location.href="' . $redirectUrl . '";</script>';
} else {
    $redirectUrl = 'edit_patient.php?id=' . $id . '&toast=error&msg=' . urlencode('แก้ไขข้อมูลไม่สำเร็จ');
    echo '<script>location.href="' . $redirectUrl . '";</script>';
}
exit;
?>

