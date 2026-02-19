<?php
// db.php - central DB connection and helper
$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_PORT = getenv('DB_PORT') ?: '';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '';
$DB_NAME = getenv('DB_NAME') ?: 'doctorvisit';

// เชื่อมต่อ MySQL (ยังไม่เลือก database)
// หากกำหนด DB_PORT จะใช้พอร์ตนั้นเป็นตัวเชื่อมต่อ
$port = is_numeric($DB_PORT) ? (int)$DB_PORT : null;
if ($port) {
    $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, '', $port);
} else {
    $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS);
}
if ($mysqli->connect_errno) {
    $db_error = $mysqli->connect_error;
} else {
    $mysqli->set_charset('utf8mb4');
    // สร้างฐานข้อมูลถ้ายังไม่มี
    $mysqli->query("CREATE DATABASE IF NOT EXISTS `{$DB_NAME}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $mysqli->select_db($DB_NAME);
    // สร้างตารางอัตโนมัติ
    db_init_tables();
}

function db_init_tables() {
    global $mysqli, $DB_NAME;
    if (!isset($mysqli) || $mysqli->connect_errno) return false;
    
    $mysqli->select_db($DB_NAME);
    
    // สร้างตาราง patients
    $mysqli->query("CREATE TABLE IF NOT EXISTS `patients` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `no` VARCHAR(50) DEFAULT NULL,
      `visit_date` DATE DEFAULT NULL,
      `hn` VARCHAR(50) DEFAULT NULL,
      `fullname` VARCHAR(255) DEFAULT NULL,
      `age_y` INT(11) DEFAULT NULL,
      `age_m` INT(11) DEFAULT NULL,
      `gender` VARCHAR(10) DEFAULT NULL,
      `gender_note` TEXT DEFAULT NULL,
      `address_no` VARCHAR(100) DEFAULT NULL,
      `address_moo` VARCHAR(50) DEFAULT NULL,
      `address_tambon` VARCHAR(100) DEFAULT NULL,
      `address_amphur` VARCHAR(100) DEFAULT NULL,
      `address_province` VARCHAR(100) DEFAULT NULL,
      `live_with` VARCHAR(255) DEFAULT NULL,
      `residence_type` VARCHAR(100) DEFAULT NULL,
      `pre_visit_illness` TEXT DEFAULT NULL,
      `is_imc` VARCHAR(10) DEFAULT NULL,
      `visit_reason` TEXT DEFAULT NULL,
      `symptoms_found` TEXT DEFAULT NULL,
      `medical_equipment` TEXT DEFAULT NULL,
      `problems_found` TEXT DEFAULT NULL,
      `solution` TEXT DEFAULT NULL,
      `consultation` TINYINT(1) DEFAULT 0,
      `no_consultation` TEXT DEFAULT NULL,
      `imc` VARCHAR(50) DEFAULT NULL,
      `health_note` TEXT DEFAULT NULL,
      `consult_yes` TINYINT(1) DEFAULT 0,
      `consult_no` TINYINT(1) DEFAULT 0,
      `medicine_yes` TINYINT(1) DEFAULT 0,
      `medicine_no` TINYINT(1) DEFAULT 0,
      `wc_info` VARCHAR(255) DEFAULT NULL,
      `general_note` TEXT DEFAULT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_hn` (`hn`),
      KEY `idx_visit_date` (`visit_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // สร้างตาราง visits
    $mysqli->query("CREATE TABLE IF NOT EXISTS `visits` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `patient_id` INT(11) DEFAULT NULL,
      `visit_date` DATE DEFAULT NULL,
      `visit_time` TIME DEFAULT NULL,
      `visit_number` INT(11) DEFAULT NULL,
      `visit_type_id` INT(11) DEFAULT NULL,
      `visit_type_category` TEXT DEFAULT NULL,
      `hn` VARCHAR(50) DEFAULT NULL,
      `fullname` VARCHAR(255) DEFAULT NULL,
      `visitor` VARCHAR(255) DEFAULT NULL,
      `purpose` TEXT DEFAULT NULL,
      `facilities` TEXT DEFAULT NULL,
      `referral` TEXT DEFAULT NULL,
      `summary` TEXT DEFAULT NULL,
      `notes` TEXT DEFAULT NULL,
      `created_by` INT(11) DEFAULT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_patient_id` (`patient_id`),
      KEY `idx_visit_date` (`visit_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // ตรวจสอบและเพิ่มคอลัมน์ visit_type_category ถ้ายังไม่มี (สำหรับตารางที่สร้างไว้แล้ว)
    $result = $mysqli->query("SHOW COLUMNS FROM `visits` LIKE 'visit_type_category'");
    if ($result && $result->num_rows == 0) {
        $mysqli->query("ALTER TABLE `visits` ADD COLUMN `visit_type_category` TEXT DEFAULT NULL AFTER `visit_type_id`");
    }
    
    // เพิ่ม column visitor และ fullname ถ้ายังไม่มี
    $result = $mysqli->query("SHOW COLUMNS FROM `visits` LIKE 'visitor'");
    if ($result && $result->num_rows == 0) {
        $mysqli->query("ALTER TABLE `visits` ADD COLUMN `visitor` VARCHAR(255) DEFAULT NULL AFTER `hn`");
    }
    $result = $mysqli->query("SHOW COLUMNS FROM `visits` LIKE 'fullname'");
    if ($result && $result->num_rows == 0) {
        $mysqli->query("ALTER TABLE `visits` ADD COLUMN `fullname` VARCHAR(255) DEFAULT NULL AFTER `hn`");
    }
    
    // เพิ่มคอลัมน์ใหม่ในตาราง patients (สำหรับฟิลด์ใหม่)
    $new_columns = [
        'pre_visit_illness' => 'TEXT DEFAULT NULL',
        'is_imc' => 'VARCHAR(10) DEFAULT NULL',
        'visit_reason' => 'TEXT DEFAULT NULL',
        'symptoms_found' => 'TEXT DEFAULT NULL',
        'medical_equipment' => 'TEXT DEFAULT NULL',
        'problems_found' => 'TEXT DEFAULT NULL',
        'solution' => 'TEXT DEFAULT NULL'
    ];
    
    foreach ($new_columns as $column => $definition) {
        $result = $mysqli->query("SHOW COLUMNS FROM `patients` LIKE '{$column}'");
        if ($result && $result->num_rows == 0) {
            $after_column = $column === 'pre_visit_illness' ? 'residence_type' : 
                           ($column === 'is_imc' ? 'pre_visit_illness' :
                           ($column === 'visit_reason' ? 'is_imc' :
                           ($column === 'symptoms_found' ? 'visit_reason' :
                           ($column === 'medical_equipment' ? 'symptoms_found' :
                           ($column === 'problems_found' ? 'medical_equipment' : 'problems_found')))));
            $mysqli->query("ALTER TABLE `patients` ADD COLUMN `{$column}` {$definition} AFTER `{$after_column}`");
        }
    }
    
    // ปิด foreign key checks ชั่วคราวเพื่อหลีกเลี่ยงปัญหา
    $mysqli->query("SET FOREIGN_KEY_CHECKS = 0");
    
    // สร้างตาราง inhomess_assessments
    $mysqli->query("CREATE TABLE IF NOT EXISTS `inhomess_assessments` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `visit_id` INT(11) DEFAULT NULL,
      `patient_id` INT(11) DEFAULT NULL,
      `assessor_id` INT(11) DEFAULT NULL,
      `hn` VARCHAR(50) DEFAULT NULL,
      `fullname` VARCHAR(255) DEFAULT NULL,
      `assess_date` DATE DEFAULT NULL,
      `assessor` VARCHAR(255) DEFAULT NULL,
      `data` LONGTEXT DEFAULT NULL,
      `score` DECIMAL(10,2) DEFAULT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_visit_id` (`visit_id`),
      KEY `idx_patient_id` (`patient_id`),
      KEY `idx_assess_date` (`assess_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // ลบ foreign keys ถ้ามี (เพื่อหลีกเลี่ยงปัญหา constraint)
    $mysqli->query("ALTER TABLE `inhomess_assessments` DROP FOREIGN KEY IF EXISTS `fk_inhomess_visit`");
    $mysqli->query("ALTER TABLE `inhomess_assessments` DROP FOREIGN KEY IF EXISTS `fk_inhomess_patient`");
    
    // แก้ไข column data จาก JSON เป็น LONGTEXT ถ้ายังเป็น JSON อยู่
    $result = $mysqli->query("SHOW COLUMNS FROM `inhomess_assessments` LIKE 'data'");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (stripos($row['Type'], 'json') !== false || stripos($row['Type'], 'text') === false) {
            $mysqli->query("ALTER TABLE `inhomess_assessments` MODIFY `data` LONGTEXT DEFAULT NULL");
        }
    }
    
    // แก้ไข columns ให้รองรับ NULL ทั้งหมด
    $columns_to_fix = [
        'visit_id' => 'INT(11) DEFAULT NULL',
        'patient_id' => 'INT(11) DEFAULT NULL',
        'assessor_id' => 'INT(11) DEFAULT NULL',
        'hn' => 'VARCHAR(50) DEFAULT NULL',
        'fullname' => 'VARCHAR(255) DEFAULT NULL',
        'assess_date' => 'DATE DEFAULT NULL',
        'assessor' => 'VARCHAR(255) DEFAULT NULL',
        'score' => 'DECIMAL(10,2) DEFAULT NULL'
    ];
    
    foreach ($columns_to_fix as $col => $def) {
        $result = $mysqli->query("SHOW COLUMNS FROM `inhomess_assessments` LIKE '$col'");
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if ($row['Null'] !== 'YES') {
                $mysqli->query("ALTER TABLE `inhomess_assessments` MODIFY `$col` $def");
            }
        }
    }
    
    // เปิด foreign key checks กลับมา
    $mysqli->query("SET FOREIGN_KEY_CHECKS = 1");
    
    return true;
}

function db_insert_patient(array $data): bool {
    global $mysqli;
    if (!isset($mysqli) || $mysqli->connect_errno) return false;

    // แปลง medical_equipment array เป็น string
    $medical_equipment_str = '';
    if (isset($data['medical_equipment']) && is_array($data['medical_equipment'])) {
        $medical_equipment_str = implode(', ', $data['medical_equipment']);
    } elseif (isset($data['medical_equipment'])) {
        $medical_equipment_str = $data['medical_equipment'];
    }

    $sql = "INSERT INTO patients (
        `no`, visit_date, hn, fullname, age_y, age_m, gender, gender_note,
        address_no, address_moo, address_tambon, address_amphur, address_province,
        live_with, residence_type, pre_visit_illness, is_imc, visit_reason, symptoms_found,
        medical_equipment, problems_found, solution, consultation, no_consultation, imc, health_note,
        consult_yes, consult_no, medicine_yes, medicine_no, wc_info, general_note
    ) VALUES (" . rtrim(str_repeat('?,', 32), ',') . ")";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: " . $mysqli->error);
        return false;
    }

    // normalize values to strings
    $params = [
        $data['no'] ?? '', $data['visit_date'] ?? '', $data['hn'] ?? '', $data['fullname'] ?? '',
        (string)($data['age_y'] ?? ''), (string)($data['age_m'] ?? ''), $data['gender'] ?? '', $data['gender_note'] ?? '',
        $data['address_no'] ?? '', $data['address_moo'] ?? '', $data['address_tambon'] ?? '', $data['address_amphur'] ?? '', $data['address_province'] ?? '',
        $data['live_with'] ?? '', $data['residence_type'] ?? '', 
        $data['pre_visit_illness'] ?? '', $data['is_imc'] ?? '', $data['visit_reason'] ?? '', $data['symptoms_found'] ?? '',
        $medical_equipment_str, $data['problems_found'] ?? '', $data['solution'] ?? '',
        $data['consultation'] ?? '', $data['no_consultation'] ?? '', $data['imc'] ?? '', $data['health_note'] ?? '',
        $data['consult_yes'] ?? '', $data['consult_no'] ?? '', $data['medicine_yes'] ?? '', $data['medicine_no'] ?? '', $data['wc_info'] ?? '', $data['general_note'] ?? ''
    ];

    // bind as strings
    $types = str_repeat('s', count($params));

    $stmt->bind_param($types, ...$params);
    $res = $stmt->execute();
    if (!$res) {
        error_log("Execute failed: " . $stmt->error);
    }
    $stmt->close();
    return (bool)$res;
}

function db_get_patients(int $limit = 100): array {
    global $mysqli;
    if (!isset($mysqli) || $mysqli->connect_errno) return [];

    // ดึงข้อมูลผู้ป่วยไม่ซ้ำ พร้อมนับจำนวนการเยี่ยมและการประเมิน
    // นับจาก patient_id เป็นหลัก ถ้าไม่มีค่อยนับจาก hn
    $sql = "
        SELECT
            p.id,
            p.hn,
            p.fullname,
            p.gender,
            CONCAT_WS(' ', p.address_no, p.address_moo, p.address_tambon, p.address_amphur, p.address_province) AS address,
            p.live_with,
            (
                SELECT COUNT(DISTINCT v.id) 
                FROM visits v 
                WHERE v.patient_id = p.id 
                   OR (v.patient_id IS NULL AND v.hn = p.hn AND v.hn IS NOT NULL AND v.hn != '')
                   OR (v.hn = p.hn AND v.hn IS NOT NULL AND v.hn != '' AND p.hn IS NOT NULL AND p.hn != '')
            ) AS visit_count,
            (
                SELECT COUNT(DISTINCT a.id) 
                FROM inhomess_assessments a 
                WHERE a.patient_id = p.id 
                   OR (a.patient_id IS NULL AND a.hn = p.hn AND a.hn IS NOT NULL AND a.hn != '')
                   OR (a.hn = p.hn AND a.hn IS NOT NULL AND a.hn != '' AND p.hn IS NOT NULL AND p.hn != '')
            ) AS assessment_count,
            COALESCE(
                (SELECT MAX(v.visit_date) FROM visits v 
                 WHERE v.patient_id = p.id 
                    OR (v.patient_id IS NULL AND v.hn = p.hn AND v.hn IS NOT NULL AND v.hn != '')
                    OR (v.hn = p.hn AND v.hn IS NOT NULL AND v.hn != '' AND p.hn IS NOT NULL AND p.hn != '')),
                (SELECT MAX(a.assess_date) FROM inhomess_assessments a 
                 WHERE a.patient_id = p.id 
                    OR (a.patient_id IS NULL AND a.hn = p.hn AND a.hn IS NOT NULL AND a.hn != '')
                    OR (a.hn = p.hn AND a.hn IS NOT NULL AND a.hn != '' AND p.hn IS NOT NULL AND p.hn != '')),
                p.visit_date
            ) AS last_visit_date
        FROM patients p
        ORDER BY last_visit_date DESC, p.created_at DESC
        LIMIT ?";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Failed to prepare db_get_patients query: " . $mysqli->error);
        return [];
    }
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

// ดึงข้อมูลแบบแถวต่อเหตุการณ์ (เยี่ยม/ประเมิน) สำหรับหน้าสรุป
function db_get_patient_records(int $limit = 300): array {
    global $mysqli;
    if (!isset($mysqli) || $mysqli->connect_errno) return [];

    $sql = "
        SELECT * FROM (
            SELECT 
                p.id AS patient_id,
                p.hn,
                p.fullname,
                p.gender,
                CONCAT_WS(' ', p.address_no, p.address_moo, p.address_tambon, p.address_amphur, p.address_province) AS address,
                p.live_with,
                v.visit_date,
                v.visit_time,
                (
                    SELECT COUNT(*) 
                    FROM visits v2
                    WHERE v2.patient_id = v.patient_id
                    AND (
                        v2.visit_date < v.visit_date
                        OR (v2.visit_date = v.visit_date AND v2.id <= v.id)
                    )
                ) AS visit_number,
                NULL AS assessment_number,
                'visit' AS record_type,
                v.id AS record_id
            FROM visits v
            INNER JOIN patients p ON v.patient_id = p.id
            UNION ALL
            SELECT 
                p.id AS patient_id,
                p.hn,
                p.fullname,
                p.gender,
                CONCAT_WS(' ', p.address_no, p.address_moo, p.address_tambon, p.address_amphur, p.address_province) AS address,
                p.live_with,
                a.assess_date AS visit_date,
                NULL AS visit_time,
                NULL AS visit_number,
                (
                    SELECT COUNT(*) 
                    FROM inhomess_assessments a2
                    WHERE a2.patient_id = a.patient_id
                    AND (
                        a2.assess_date < a.assess_date
                        OR (a2.assess_date = a.assess_date AND a2.id <= a.id)
                    )
                ) AS assessment_number,
                'assessment' AS record_type,
                a.id AS record_id
            FROM inhomess_assessments a
            INNER JOIN patients p ON a.patient_id = p.id
        ) AS combined_records
        ORDER BY visit_date DESC, record_id DESC
        LIMIT ?";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return [];
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Normalize และตรวจสอบข้อมูล
    $normalized_rows = [];
    foreach ($rows as $row) {
        // ตรวจสอบว่า record_id มีค่าหรือไม่
        if (!isset($row['record_id']) || $row['record_id'] === null) {
            error_log("Missing record_id in db_get_patient_records: " . json_encode([
                'hn' => $row['hn'] ?? '',
                'fullname' => $row['fullname'] ?? '',
                'record_type' => $row['record_type'] ?? 'unknown',
                'row_keys' => array_keys($row)
            ]));
            // ข้ามแถวที่ไม่มี record_id
            continue;
        }
        
        // แปลง record_id เป็น integer
        $row['record_id'] = (int)$row['record_id'];
        
        // ตรวจสอบว่า record_id > 0
        if ($row['record_id'] <= 0) {
            error_log("Invalid record_id (<= 0) in db_get_patient_records: " . json_encode([
                'record_id' => $row['record_id'],
                'hn' => $row['hn'] ?? '',
                'fullname' => $row['fullname'] ?? '',
                'record_type' => $row['record_type'] ?? 'unknown'
            ]));
            // ข้ามแถวที่ record_id ไม่ถูกต้อง
            continue;
        }
        
        $normalized_rows[] = $row;
    }

    return $normalized_rows;
}

function db_find_patient_by_hn(string $hn) {
    global $mysqli;
    if (!$hn || !isset($mysqli) || $mysqli->connect_errno) return null;
    $stmt = $mysqli->prepare('SELECT id FROM patients WHERE hn = ? LIMIT 1');
    if (!$stmt) return null;
    $stmt->bind_param('s', $hn);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    return $row['id'] ?? null;
}

function db_create_patient_minimal(array $data) {
    global $mysqli;
    if (!isset($mysqli) || $mysqli->connect_errno) return null;
    // ใช้เฉพาะฟิลด์ที่มีอยู่จริงในตาราง patients ปัจจุบัน (hn, fullname)
    $sql = "INSERT INTO patients (hn, fullname)
        VALUES (?, ?)";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return null;
    $params = [
        $data['hn'] ?? '', $data['fullname'] ?? ''
    ];
    $types = 'ss';
    $stmt->bind_param($types, ...$params);
    $ok = $stmt->execute();
    if (!$ok) { $stmt->close(); return null; }
    $id = $mysqli->insert_id;
    $stmt->close();
    return $id;
}

function db_insert_visit(array $data): bool {
    global $mysqli;
    if (!isset($mysqli) || $mysqli->connect_errno) {
        error_log("DB connection error (visits): " . ($mysqli->connect_error ?? 'Unknown'));
        return false;
    }
    // try to map patient_id by hn if present
    $patient_id = null;
    if (!empty($data['hn'])) {
        $patient_id = db_find_patient_by_hn($data['hn']);
        if (is_null($patient_id) && !empty($data['fullname'])) {
            $patient_id = db_create_patient_minimal(['hn'=>$data['hn'],'fullname'=>$data['fullname']]);
        }
    }

    $sql = "INSERT INTO visits (patient_id, visit_date, visit_time, visit_number, visit_type_id, visit_type_category, hn, fullname, visitor, purpose, facilities, referral, summary, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed (visits): " . $mysqli->error);
        return false;
    }
    $visit_date = $data['visit_date'] ?? null;
    $visit_time = $data['visit_time'] ?? null;
    $visit_number = isset($data['visit_number']) ? $data['visit_number'] : null;
    $visit_type_id = isset($data['visit_type_id']) ? $data['visit_type_id'] : null;
    $visit_type_category = $data['visit_type_category'] ?? '';
    $hn = $data['hn'] ?? '';
    $fullname = $data['fullname'] ?? '';
    $visitor = $data['visitor'] ?? '';
    $purpose = $data['purpose'] ?? '';
    $facilities = $data['facilities'] ?? '';
    $referral = $data['referral'] ?? '';
    $summary = $data['summary'] ?? ($data['purpose'] ?? '');
    $notes = $data['notes'] ?? '';
    $created_by = $data['created_by'] ?? null;
    $stmt->bind_param('issiisssssssssi', $patient_id, $visit_date, $visit_time, $visit_number, $visit_type_id, $visit_type_category, $hn, $fullname, $visitor, $purpose, $facilities, $referral, $summary, $notes, $created_by);
    $res = $stmt->execute();
    if (!$res) {
        error_log("Execute failed (visits): " . $stmt->error);
    }
    $stmt->close();
    return (bool)$res;
}

// ฟังก์ชันดึงรายการการเยี่ยมบ้านทั้งหมด (ใช้แสดงในหน้าสรุป)
function db_get_visits(int $limit = 200): array {
    global $mysqli;
    if (!isset($mysqli) || $mysqli->connect_errno) return [];
    $sql = "SELECT id, patient_id, visit_date, visit_time, visit_number, visit_type_category, hn, fullname, visitor, purpose, facilities, referral, summary, notes, created_at
            FROM visits
            ORDER BY visit_date DESC, id DESC
            LIMIT ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return [];
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

// ฟังก์ชันดึงรายการการเยี่ยมบ้านของผู้ป่วยรายหนึ่ง (ตาม patient_id หรือ hn)
function db_get_visits_by_patient(?int $patient_id = null, ?string $hn = null, int $limit = 50): array {
    global $mysqli;
    if (!isset($mysqli) || $mysqli->connect_errno) return [];
    if (!$patient_id && !$hn) return [];

    $sql = "SELECT id, patient_id, visit_date, visit_time, visit_number, visit_type_category, hn, fullname, visitor, purpose, facilities, referral, summary, notes, created_at
            FROM visits
            WHERE 1=1";
    $types = '';
    $params = [];

    if ($patient_id) {
        $sql .= " AND patient_id = ?";
        $types .= 'i';
        $params[] = $patient_id;
    }
    if ($hn) {
        $sql .= " AND hn = ?";
        $types .= 's';
        $params[] = $hn;
    }

    $sql .= " ORDER BY visit_date DESC, id DESC LIMIT ?";
    $types .= 'i';
    $params[] = $limit;

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return [];
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

// ฟังก์ชันดึงรายการการประเมินของผู้ป่วยรายหนึ่ง
function db_get_assessments_by_patient(?int $patient_id = null, ?string $hn = null, int $limit = 50): array {
    global $mysqli;
    if (!isset($mysqli) || $mysqli->connect_errno) return [];
    if (!$patient_id && !$hn) return [];

    $sql = "SELECT id, visit_id, patient_id, hn, fullname, assess_date, assessor, score, data, created_at
            FROM inhomess_assessments
            WHERE 1=1";
    $types = '';
    $params = [];

    if ($patient_id) {
        $sql .= " AND patient_id = ?";
        $types .= 'i';
        $params[] = $patient_id;
    }
    if ($hn) {
        $sql .= " AND hn = ?";
        $types .= 's';
        $params[] = $hn;
    }

    $sql .= " ORDER BY assess_date DESC, created_at DESC LIMIT ?";
    $types .= 'i';
    $params[] = $limit;

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return [];
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Decode JSON data for each row
    foreach ($rows as &$row) {
        if (!empty($row['data'])) {
            $row['data'] = json_decode($row['data'], true);
        }
    }
    
    return $rows;
}

function db_insert_inhomess_assessment(array $data): bool {
    global $mysqli;
    if (!isset($mysqli) || $mysqli->connect_errno) {
        error_log("DB connection error: " . ($mysqli->connect_error ?? 'Unknown'));
        return false;
    }
    
    // หา patient_id จาก hn ถ้ามี
    $patient_id = $data['patient_id'] ?? null;
    $hn = $data['hn'] ?? ($data['payload']['hn'] ?? '');
    if (!$patient_id && !empty($hn)) {
        $patient_id = db_find_patient_by_hn($hn);
    }
    
    $visit_id = $data['visit_id'] ?? null;
    $assessor_id = $data['assessor_id'] ?? null;
    $fullname = $data['fullname'] ?? ($data['payload']['fullname'] ?? '');
    $assess_date = $data['assess_date'] ?? ($data['payload']['assess_date'] ?? null);
    $assessor = $data['assessor'] ?? ($data['payload']['assessor'] ?? '');
    
    // แปลง payload เป็น JSON string สำหรับเก็บใน LONGTEXT
    $json_data = json_encode($data['payload'] ?? $data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $score = isset($data['score']) ? $data['score'] : null;
    
    // แปลง null เป็น empty string สำหรับ string fields
    $visit_id = $visit_id ?: null;
    $patient_id = $patient_id ?: null;
    $assessor_id = $assessor_id ?: null;
    $hn = $hn ?: '';
    $fullname = $fullname ?: '';
    // สำหรับ date field ถ้าเป็น null ให้เป็น null ไม่ต้องแปลง
    $assess_date = !empty($assess_date) ? $assess_date : null;
    $assessor = $assessor ?: '';
    
    $sql = "INSERT INTO inhomess_assessments (visit_id, patient_id, assessor_id, hn, fullname, assess_date, assessor, data, score) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: " . $mysqli->error);
        return false;
    }
    
    // bind_param: i=integer, s=string, d=double
    // visit_id, patient_id, assessor_id = i (integer, nullable)
    // hn, fullname = s (string)
    // assess_date = s (string date, nullable) - MySQL จะแปลง string date เป็น DATE
    // assessor = s (string)
    // data = s (LONGTEXT - JSON string)
    // score = d (decimal/double, nullable)
    // สำหรับ nullable fields ต้องส่งเป็น null หรือ empty string
    $assess_date_param = $assess_date ?: '';
    
    // ตรวจสอบว่ามีข้อมูลที่จำเป็นหรือไม่
    if (empty($hn) && empty($fullname)) {
        error_log("Missing required fields: hn and fullname are both empty");
        return false;
    }
    
    $stmt->bind_param('iiisssssd', $visit_id, $patient_id, $assessor_id, $hn, $fullname, $assess_date_param, $assessor, $json_data, $score);
    $res = $stmt->execute();
    
    if (!$res) {
        $error = $stmt->error;
        error_log("Execute failed: " . $error);
        error_log("SQL: " . $sql);
        error_log("Data: hn=$hn, fullname=$fullname, assess_date=$assess_date_param");
        $stmt->close();
        return false;
    }
    
    $stmt->close();
    return true;
}

// ฟังก์ชันดึงข้อมูลการประเมินทั้งหมด
function db_get_assessments(int $limit = 100): array {
    global $mysqli;
    if (!isset($mysqli) || $mysqli->connect_errno) return [];
    $sql = "SELECT id, visit_id, patient_id, hn, fullname, assess_date, assessor, score, created_at
        FROM inhomess_assessments
        ORDER BY created_at DESC
        LIMIT ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return [];
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

// ฟังก์ชันดึงข้อมูลการประเมินตาม ID
function db_get_assessment_by_id(int $id): ?array {
    global $mysqli;
    if (!isset($mysqli) || $mysqli->connect_errno) return null;
    $sql = "SELECT * FROM inhomess_assessments WHERE id = ? LIMIT 1";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return null;
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    
    if ($row && !empty($row['data'])) {
        $row['data'] = json_decode($row['data'], true);
    }
    
    return $row ?: null;
}

// ฟังก์ชันดึงข้อมูลการประเมินล่าสุดของผู้ป่วย (โดย patient_id, hn และ/หรือ fullname)
function db_get_last_assessment_by_patient(?int $patient_id = null, ?string $hn = null, ?string $fullname = null): ?array {
    global $mysqli;
    if (!isset($mysqli) || $mysqli->connect_errno) return null;
    if (!$patient_id && !$hn && !$fullname) return null;

    $sql = "SELECT * FROM inhomess_assessments
            WHERE 1=1";
    $params = [];
    $types = '';

    if ($patient_id) {
        $sql .= " AND patient_id = ?";
        $types .= 'i';
        $params[] = $patient_id;
    }
    if ($hn) {
        $sql .= " AND hn = ?";
        $types .= 's';
        $params[] = $hn;
    }
    if ($fullname) {
        $sql .= " AND fullname = ?";
        $types .= 's';
        $params[] = $fullname;
    }

    $sql .= " ORDER BY assess_date DESC, created_at DESC LIMIT 1";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return null;

    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    if ($row && !empty($row['data'])) {
        $row['data'] = json_decode($row['data'], true);
    }

    return $row ?: null;
}

// ฟังก์ชันดึงข้อมูลผู้ป่วยตาม ID
function db_get_patient_by_id(int $id): ?array {
    global $mysqli;
    if (!isset($mysqli) || $mysqli->connect_errno) return null;
    $sql = "SELECT * FROM patients WHERE id = ? LIMIT 1";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return null;
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

// ฟังก์ชันอัปเดตข้อมูลผู้ป่วย
function db_update_patient(int $id, array $data): bool {
    global $mysqli;
    if (!isset($mysqli) || $mysqli->connect_errno) return false;
    
    $sql = "UPDATE patients SET 
        `no` = ?, visit_date = ?, hn = ?, fullname = ?, age_y = ?, age_m = ?, gender = ?, gender_note = ?,
        address_no = ?, address_moo = ?, address_tambon = ?, address_amphur = ?, address_province = ?,
        live_with = ?, residence_type = ?, pre_visit_illness = ?, is_imc = ?, visit_reason = ?, symptoms_found = ?,
        medical_equipment = ?, problems_found = ?, solution = ?, consultation = ?, no_consultation = ?, imc = ?, health_note = ?,
        consult_yes = ?, consult_no = ?, medicine_yes = ?, medicine_no = ?, wc_info = ?, general_note = ?
        WHERE id = ?";
    
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return false;
    
    $params = [
        $data['no'] ?? '', $data['visit_date'] ?? '', $data['hn'] ?? '', $data['fullname'] ?? '',
        (string)($data['age_y'] ?? ''), (string)($data['age_m'] ?? ''), $data['gender'] ?? '', $data['gender_note'] ?? '',
        $data['address_no'] ?? '', $data['address_moo'] ?? '', $data['address_tambon'] ?? '', $data['address_amphur'] ?? '', $data['address_province'] ?? '',
        $data['live_with'] ?? '', $data['residence_type'] ?? '', $data['pre_visit_illness'] ?? '', $data['is_imc'] ?? '', 
        $data['visit_reason'] ?? '', $data['symptoms_found'] ?? '', $data['medical_equipment'] ?? '', 
        $data['problems_found'] ?? '', $data['solution'] ?? '', $data['consultation'] ?? '', $data['no_consultation'] ?? '', 
        $data['imc'] ?? '', $data['health_note'] ?? '', $data['consult_yes'] ?? '', $data['consult_no'] ?? '', 
        $data['medicine_yes'] ?? '', $data['medicine_no'] ?? '', $data['wc_info'] ?? '', $data['general_note'] ?? '',
        $id
    ];
    
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
    $res = $stmt->execute();
    $stmt->close();
    return (bool)$res;
}

// ฟังก์ชันลบข้อมูลผู้ป่วย
function db_delete_patient(int $id): bool {
    global $mysqli;
    if (!isset($mysqli) || $mysqli->connect_errno) return false;
    $stmt = $mysqli->prepare('DELETE FROM patients WHERE id = ?');
    if (!$stmt) return false;
    $stmt->bind_param('i', $id);
    $res = $stmt->execute();
    $stmt->close();
    return (bool)$res;
}

// ฟังก์ชันอัปเดตข้อมูลการประเมิน
function db_update_assessment(int $id, array $data): bool {
    global $mysqli;
    if (!isset($mysqli) || $mysqli->connect_errno) return false;
    
    // คำนวณคะแนน Barthel Index
    $barthelScore = 0;
    if (isset($data['payload'])) {
        $barthelFields = ['adl_feeding', 'adl_bathing', 'adl_grooming', 'adl_dressing', 'adl_bowels', 'adl_bladder', 'adl_toilet', 'adl_transfer', 'adl_mobility', 'adl_stairs'];
        foreach ($barthelFields as $field) {
            if (isset($data['payload'][$field]) && is_array($data['payload'][$field])) {
                foreach ($data['payload'][$field] as $val) {
                    $barthelScore += (int)$val;
                }
            }
        }
    }
    
    $patient_id = $data['patient_id'] ?? null;
    $hn = $data['hn'] ?? ($data['payload']['hn'] ?? '');
    if (!$patient_id && !empty($hn)) {
        $patient_id = db_find_patient_by_hn($hn);
    }
    
    $visit_id = $data['visit_id'] ?? null;
    $assessor_id = $data['assessor_id'] ?? null;
    $fullname = $data['fullname'] ?? ($data['payload']['fullname'] ?? '');
    $assess_date = $data['assess_date'] ?? ($data['payload']['assess_date'] ?? null);
    $assessor = $data['assessor'] ?? ($data['payload']['assessor'] ?? '');
    $json_data = json_encode($data['payload'] ?? $data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $score = $barthelScore > 0 ? $barthelScore : ($data['score'] ?? null);
    
    $assess_date_param = $assess_date ?: '';
    
    $sql = "UPDATE inhomess_assessments SET 
        visit_id = ?, patient_id = ?, assessor_id = ?, hn = ?, fullname = ?, assess_date = ?, assessor = ?, data = ?, score = ?
        WHERE id = ?";
    
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return false;
    
    $stmt->bind_param('iiisssssdi', $visit_id, $patient_id, $assessor_id, $hn, $fullname, $assess_date_param, $assessor, $json_data, $score, $id);
    $res = $stmt->execute();
    $stmt->close();
    return (bool)$res;
}

// ฟังก์ชันลบข้อมูลการประเมิน
function db_delete_assessment(int $id): bool {
    global $mysqli;
    if (!isset($mysqli) || $mysqli->connect_errno) return false;
    $stmt = $mysqli->prepare('DELETE FROM inhomess_assessments WHERE id = ?');
    if (!$stmt) return false;
    $stmt->bind_param('i', $id);
    $res = $stmt->execute();
    if (!$res) {
        $stmt->close();
        return false;
    }
    // ตรวจสอบว่ามีแถวที่ถูกลบจริงๆ หรือไม่
    $affected_rows = $stmt->affected_rows;
    $stmt->close();
    return $affected_rows > 0;
}

// ฟังก์ชันดึงข้อมูลการเยี่ยมตาม ID
function db_get_visit_by_id(int $id): ?array {
    global $mysqli;
    if (!isset($mysqli) || $mysqli->connect_errno) return null;
    $sql = "SELECT * FROM visits WHERE id = ? LIMIT 1";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return null;
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

// ฟังก์ชันอัปเดตข้อมูลการเยี่ยม
function db_update_visit(int $id, array $data): bool {
    global $mysqli;
    if (!isset($mysqli) || $mysqli->connect_errno) return false;
    
    $patient_id = $data['patient_id'] ?? null;
    if (!$patient_id && !empty($data['hn'])) {
        $patient_id = db_find_patient_by_hn($data['hn']);
    }
    
    $visit_date = $data['visit_date'] ?? null;
    $visit_time = $data['visit_time'] ?? null;
    $visit_number = isset($data['visit_number']) ? $data['visit_number'] : null;
    $visit_type_id = isset($data['visit_type_id']) ? $data['visit_type_id'] : null;
    $hn = $data['hn'] ?? '';
    $fullname = $data['fullname'] ?? '';
    $visitor = $data['visitor'] ?? '';
    $purpose = $data['purpose'] ?? '';
    $facilities = $data['facilities'] ?? '';
    $referral = $data['referral'] ?? '';
    $summary = $data['summary'] ?? ($data['purpose'] ?? '');
    $notes = $data['notes'] ?? '';
    $created_by = $data['created_by'] ?? null;
    
    $visit_type_category = $data['visit_type_category'] ?? '';
    
    $sql = "UPDATE visits SET 
        patient_id = ?, visit_date = ?, visit_time = ?, visit_number = ?, visit_type_id = ?, visit_type_category = ?, hn = ?, fullname = ?, visitor = ?, 
        purpose = ?, facilities = ?, referral = ?, summary = ?, notes = ?, created_by = ?
        WHERE id = ?";
    
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return false;
    
    $stmt->bind_param('issiisssssssssii', $patient_id, $visit_date, $visit_time, $visit_number, $visit_type_id, $visit_type_category, $hn, $fullname, $visitor, $purpose, $facilities, $referral, $summary, $notes, $created_by, $id);
    $res = $stmt->execute();
    $stmt->close();
    return (bool)$res;
}

// ฟังก์ชันลบข้อมูลการเยี่ยม
function db_delete_visit(int $id): bool {
    global $mysqli;
    if (!isset($mysqli) || $mysqli->connect_errno) return false;
    $stmt = $mysqli->prepare('DELETE FROM visits WHERE id = ?');
    if (!$stmt) return false;
    $stmt->bind_param('i', $id);
    $res = $stmt->execute();
    if (!$res) {
        $stmt->close();
        return false;
    }
    // ตรวจสอบว่ามีแถวที่ถูกลบจริงๆ หรือไม่
    $affected_rows = $stmt->affected_rows;
    $stmt->close();
    return $affected_rows > 0;
}

?>
