<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$type = $_GET['type'] ?? ''; // 'tambon' or 'illness'
$value = $_GET['value'] ?? '';

if (empty($type) || empty($value)) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$patients = [];

if (isset($mysqli) && !$mysqli->connect_errno) {
    if ($type === 'tambon') {
        // ดึงผู้ป่วยตามตำบล
        $sql = "SELECT id, hn, fullname, gender, address_tambon, address_amphur, address_province
                FROM patients 
                WHERE address_tambon = ?
                ORDER BY fullname ASC";
        $stmt = $mysqli->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('s', $value);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $patients[] = $row;
            }
            $stmt->close();
        }
    } elseif ($type === 'illness') {
        // ดึงผู้ป่วยตามโรค (ค้นหาใน pre_visit_illness)
        $sql = "SELECT id, hn, fullname, gender, address_tambon, address_amphur, address_province, pre_visit_illness
                FROM patients 
                WHERE pre_visit_illness LIKE ?
                ORDER BY fullname ASC";
        $search_value = '%' . $value . '%';
        $stmt = $mysqli->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('s', $search_value);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                // ตรวจสอบว่าโรคตรงกับที่ต้องการจริงๆ (เพราะใช้ LIKE)
                $illnesses = preg_split('/[,;]/', $row['pre_visit_illness']);
                $has_illness = false;
                foreach ($illnesses as $ill) {
                    if (trim($ill) === $value) {
                        $has_illness = true;
                        break;
                    }
                }
                if ($has_illness) {
                    $patients[] = $row;
                }
            }
            $stmt->close();
        }
    }
}

echo json_encode([
    'success' => true,
    'patients' => $patients,
    'count' => count($patients)
], JSON_UNESCAPED_UNICODE);

