<?php
// save.php: รับข้อมูลจากฟอร์ม add.php แล้วบันทึกลงฐานข้อมูล (หรือ fallback เป็น data.json)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        'consultation' => $_POST['consultation'] ?? '',
        'no_consultation' => $_POST['no_consultation'] ?? '',
        'imc' => $_POST['imc'] ?? '',
        'health_note' => $_POST['health_note'] ?? '',
        'consult_yes' => $_POST['consult_yes'] ?? '',
        'consult_no' => $_POST['consult_no'] ?? '',
        'medicine_yes' => $_POST['medicine_yes'] ?? '',
        'medicine_no' => $_POST['medicine_no'] ?? '',
        'wc_info' => $_POST['wc_info'] ?? '',
        'general_note' => $_POST['general_note'] ?? '',
    ];

    // try DB insert first
    $savedToDb = false;
    try {
        require_once __DIR__ . '/db.php';
        if (function_exists('db_insert_patient')) {
            $savedToDb = db_insert_patient($data);
        }
    } catch (Throwable $e) {
        $savedToDb = false;
    }

    // fallback to JSON file if DB not available
    if (!$savedToDb) {
        $file = 'data.json';
        $all = [];
        if (file_exists($file)) {
            $all = json_decode(file_get_contents($file), true) ?: [];
        }
        $all[] = $data;
        file_put_contents($file, json_encode($all, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
    }

    header('Location: index.php');
    exit;
}
?>