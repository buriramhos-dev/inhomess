<?php
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    $redirectUrl = 'index.php?toast=error&msg=' . urlencode('ไม่พบข้อมูลที่ต้องการลบ');
    echo '<script>location.href="' . $redirectUrl . '";</script>';
    exit;
}

$success = db_delete_patient($id);

if ($success) {
    $redirectUrl = 'index.php?toast=success&msg=' . urlencode('ลบข้อมูลผู้ป่วยเรียบร้อย');
    echo '<script>location.href="' . $redirectUrl . '";</script>';
} else {
    $redirectUrl = 'index.php?toast=error&msg=' . urlencode('ลบข้อมูลไม่สำเร็จ');
    echo '<script>location.href="' . $redirectUrl . '";</script>';
}
exit;
?>

