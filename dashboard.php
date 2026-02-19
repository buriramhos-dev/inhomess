<?php
require_once __DIR__ . '/db.php';

// นับจำนวนข้อมูลเบื้องต้น
$patient_count = 0;
$assessment_count = 0;
$visit_count = 0;

// ข้อมูลสรุป
$top_tambons = [];
$top_illnesses = [];
$assessment_summary = [
    'immobility' => ['has_problem' => 0, 'no_problem' => 0, 'details' => []],
    'nutrition' => ['has_problem' => 0, 'no_problem' => 0, 'details' => []],
    'home' => ['has_problem' => 0, 'no_problem' => 0, 'details' => []],
    'people' => ['has_problem' => 0, 'no_problem' => 0, 'details' => []],
    'medication' => ['has_problem' => 0, 'no_problem' => 0, 'details' => []],
    'safety' => ['has_problem' => 0, 'no_problem' => 0, 'details' => []],
    'spiritual' => ['has_problem' => 0, 'no_problem' => 0, 'details' => []],
    'service' => ['has_problem' => 0, 'no_problem' => 0, 'details' => []]
];

if (isset($mysqli) && !$mysqli->connect_errno) {
    // 1. ผู้ป่วยทั้งหมด - นับจาก HN ที่ไม่ซ้ำ
    $res_patient = $mysqli->query("SELECT COUNT(DISTINCT hn) AS c FROM patients WHERE hn IS NOT NULL AND hn != ''");
    if ($res_patient) {
        $row_patient = $res_patient->fetch_assoc();
        $patient_count = (int) ($row_patient['c'] ?? 0);
    }

    // 2. การประเมินสำเร็จ - นับทุกครั้งที่มีการประเมิน (นับซ้ำ)
    $res1 = $mysqli->query("SELECT COUNT(*) AS c FROM inhomess_assessments");
    if ($res1) {
        $row1 = $res1->fetch_assoc();
        $assessment_count = (int) ($row1['c'] ?? 0);
    }

    // 3. จำนวนการเยี่ยมบ้าน - นับทุกครั้งที่มีการเยี่ยมบ้าน (นับซ้ำ)
    $res2 = $mysqli->query("SELECT COUNT(*) AS c FROM visits");
    if ($res2) {
        $row2 = $res2->fetch_assoc();
        $visit_count = (int) ($row2['c'] ?? 0);
    }

    // ตำบลที่เยี่ยมบ่อย (นับจำนวนผู้ป่วยที่ได้รับการเยี่ยม 1 ครั้งต่อ 1 คน)
    $tambon_query = "SELECT p.address_tambon, COUNT(DISTINCT p.id) as visit_count 
                      FROM patients p 
                      INNER JOIN visits v ON v.patient_id = p.id 
                      WHERE p.address_tambon IS NOT NULL AND p.address_tambon != '' 
                      GROUP BY p.address_tambon 
                      ORDER BY visit_count DESC, p.address_tambon ASC
                      LIMIT 10";
    $tambon_res = $mysqli->query($tambon_query);
    if ($tambon_res) {
        while ($row = $tambon_res->fetch_assoc()) {
            $tambon_name = $row['address_tambon'];
            if (!empty($tambon_name)) {
                $top_tambons[] = [
                    'name' => $tambon_name,
                    'count' => (int) $row['visit_count']
                ];
            }
        }
    }

    // ถ้ายังไม่มีข้อมูล ให้ลองนับจาก patients โดยตรง
    if (empty($top_tambons)) {
        $tambon_query2 = "SELECT address_tambon, COUNT(*) as patient_count 
                           FROM patients 
                           WHERE address_tambon IS NOT NULL AND address_tambon != '' 
                           GROUP BY address_tambon 
                           ORDER BY patient_count DESC 
                           LIMIT 10";
        $tambon_res2 = $mysqli->query($tambon_query2);
        if ($tambon_res2) {
            while ($row = $tambon_res2->fetch_assoc()) {
                $tambon_name = $row['address_tambon'];
                if (!empty($tambon_name)) {
                    $top_tambons[] = [
                        'name' => $tambon_name,
                        'count' => (int) $row['patient_count'],
                        'is_patient_count' => true
                    ];
                }
            }
        }
    }

    // โรคที่เป็นเยอะ (นับจาก patients.pre_visit_illness)
    $illness_query = "SELECT pre_visit_illness, COUNT(*) as patient_count 
                       FROM patients 
                       WHERE pre_visit_illness IS NOT NULL AND pre_visit_illness != '' 
                       GROUP BY pre_visit_illness 
                       ORDER BY patient_count DESC 
                       LIMIT 10";
    $illness_res = $mysqli->query($illness_query);
    if ($illness_res) {
        while ($row = $illness_res->fetch_assoc()) {
            $illness_text = $row['pre_visit_illness'];
            // แยกโรคถ้ามีหลายโรคในฟิลด์เดียว (คั่นด้วย , หรือ ;)
            $illnesses = preg_split('/[,;]/', $illness_text);
            foreach ($illnesses as $ill) {
                $ill = trim($ill);
                if (!empty($ill)) {
                    if (!isset($top_illnesses[$ill])) {
                        $top_illnesses[$ill] = 0;
                    }
                    $top_illnesses[$ill] += (int) $row['patient_count'];
                }
            }
        }
        // เรียงลำดับและเลือก 10 อันดับแรก
        arsort($top_illnesses);
        $top_illnesses = array_slice($top_illnesses, 0, 10, true);
    }

    // สรุปการประเมิน INHOMESSS (วิเคราะห์จาก data field)
    $assessment_query = "SELECT data FROM inhomess_assessments WHERE data IS NOT NULL AND data != ''";
    $assessment_res = $mysqli->query($assessment_query);
    if ($assessment_res) {
        while ($row = $assessment_res->fetch_assoc()) {
            $data = json_decode($row['data'], true);
            if (is_array($data)) {
                // Immobility - เก็บรายละเอียดทั้งหมด
                if (!empty($data['imm_problem_has'])) {
                    $assessment_summary['immobility']['has_problem']++;
                }
                if (!empty($data['imm_problem_none'])) {
                    $assessment_summary['immobility']['no_problem']++;
                }
                $imm_fields = [
                    'imm_self_sufficient' => 'ทำได้เอง',
                    'imm_not_self_sufficient' => 'ทำเองไม่ได้',
                    'imm_bedridden' => 'ผู้ป่วยติดเตียง',
                    'imm_housebound' => 'ผู้ป่วยติดบ้าน',
                    'imm_balance_walking_problem' => 'มีปัญหาการทรงตัว/การเดิน',
                    'imm_sensory_problem' => 'มีปัญหาระบบประสาทสัมผัส'
                ];
                foreach ($imm_fields as $key => $label) {
                    if (!empty($data[$key])) {
                        if (!isset($assessment_summary['immobility']['details'][$label])) {
                            $assessment_summary['immobility']['details'][$label] = 0;
                        }
                        $assessment_summary['immobility']['details'][$label]++;
                    }
                }

                // Nutrition - เก็บรายละเอียดทั้งหมด
                if (!empty($data['nut_problem_general'])) {
                    $assessment_summary['nutrition']['has_problem']++;
                }
                if (!empty($data['nut_no_problem_general'])) {
                    $assessment_summary['nutrition']['no_problem']++;
                }
                $nut_fields = [
                    'nut_status_normal' => 'ปกติ',
                    'nut_status_obese' => 'อ้วน',
                    'nut_status_underweight' => 'ผอม',
                    'nut_type_normal' => 'อาหารธรรมดา',
                    'nut_type_soft' => 'อาหารอ่อน/นิ่ม',
                    'nut_type_liquid' => 'อาหารเหลว/ปั่น',
                    'nut_type_sweet' => 'อาหารหวาน/เบาหวาน',
                    'nut_source_home' => 'ปรุงเอง',
                    'nut_source_ready' => 'ซื้อสำเร็จรูป',
                    'nut_source_frozen' => 'อาหารแช่แข็ง',
                    'nut_source_other' => 'อื่นๆ',
                    'nut_alcohol_drink' => 'ดื่มเหล้า',
                    'nut_alcohol_abstain' => 'ไม่ดื่มเหล้า',
                    'nut_tobacco_smoke' => 'สูบบุหรี่',
                    'nut_tobacco_not_smoke' => 'ไม่สูบบุหรี่'
                ];
                foreach ($nut_fields as $key => $label) {
                    if (!empty($data[$key])) {
                        if (!isset($assessment_summary['nutrition']['details'][$label])) {
                            $assessment_summary['nutrition']['details'][$label] = 0;
                        }
                        $assessment_summary['nutrition']['details'][$label]++;
                    }
                }

                // Home Environment
                if (!empty($data['home_problem_status']) && $data['home_problem_status'] === 'yes') {
                    $assessment_summary['home']['has_problem']++;
                } elseif (!empty($data['home_problem_status']) && $data['home_problem_status'] === 'no') {
                    $assessment_summary['home']['no_problem']++;
                }
                $home_fields = [
                    'home_indoor_crowded' => 'ภายในบ้านแออัด',
                    'home_indoor_airy' => 'ภายในบ้านโปร่งสบาย',
                    'home_indoor_clean' => 'ภายในบ้านสะอาด',
                    'home_indoor_no_pet' => 'ไม่มีสัตว์เลี้ยงในบ้าน',
                    'home_indoor_has_pet' => 'มีสัตว์เลี้ยง',
                    'home_outdoor_no_area' => 'ไม่มีบริเวณ',
                    'home_outdoor_dirty' => 'บริเวณรอบบ้านสกปรก',
                    'home_outdoor_cluttered' => 'บริเวณรอบบ้านรกรุงรัง',
                    'home_struct_stable' => 'บ้านมั่นคง/แข็งแรง',
                    'home_struct_semi_stable' => 'บ้านไม่ค่อยมั่นคง',
                    'home_struct_dilapidated' => 'บ้านเก่า/ชำรุด',
                    'home_light_sufficient' => 'แสงสว่างเพียงพอ',
                    'home_light_dark' => 'แสงสว่างไม่ชัดเจน',
                    'home_toilet_suitable' => 'มีห้องน้ำเหมาะสม',
                    'home_toilet_unsuitable' => 'มีห้องน้ำแต่ไม่เหมาะสม'
                ];
                foreach ($home_fields as $key => $label) {
                    if (!empty($data[$key])) {
                        if (!isset($assessment_summary['home']['details'][$label])) {
                            $assessment_summary['home']['details'][$label] = 0;
                        }
                        $assessment_summary['home']['details'][$label]++;
                    }
                }

                // Other People
                if (!empty($data['people_problem_status']) && $data['people_problem_status'] === 'yes') {
                    $assessment_summary['people']['has_problem']++;
                } elseif (!empty($data['people_problem_status']) && $data['people_problem_status'] === 'no') {
                    $assessment_summary['people']['no_problem']++;
                }
                $people_fields = [
                    'people_emergency_spouse' => 'ผู้นำส่ง: สามี/ภรรยา',
                    'people_emergency_father' => 'ผู้นำส่ง: พ่อ',
                    'people_emergency_mother' => 'ผู้นำส่ง: แม่',
                    'people_emergency_child' => 'ผู้นำส่ง: บุตร',
                    'people_emergency_sibling' => 'ผู้นำส่ง: พี่/น้อง',
                    'people_emergency_relative' => 'ผู้นำส่ง: ญาติ',
                    'people_carer_spouse' => 'ผู้ดูแล: สามี/ภรรยา',
                    'people_carer_father' => 'ผู้ดูแล: พ่อ',
                    'people_carer_mother' => 'ผู้ดูแล: แม่',
                    'people_carer_child' => 'ผู้ดูแล: บุตร',
                    'people_carer_sibling' => 'ผู้ดูแล: พี่/น้อง',
                    'people_carer_relative' => 'ผู้ดูแล: ญาติ'
                ];
                foreach ($people_fields as $key => $label) {
                    if (!empty($data[$key])) {
                        if (!isset($assessment_summary['people']['details'][$label])) {
                            $assessment_summary['people']['details'][$label] = 0;
                        }
                        $assessment_summary['people']['details'][$label]++;
                    }
                }

                // Medication
                if (!empty($data['med_problem_status']) && $data['med_problem_status'] === 'yes') {
                    $assessment_summary['medication']['has_problem']++;
                } elseif (!empty($data['med_problem_status']) && $data['med_problem_status'] === 'no') {
                    $assessment_summary['medication']['no_problem']++;
                }
                $med_fields = [
                    'med_follow_correct' => 'ใช้ยาตามแพทย์สั่ง: ถูกต้อง',
                    'med_follow_incorrect' => 'ใช้ยาตามแพทย์สั่ง: ไม่ถูกต้อง',
                    'med_receive_regular' => 'ได้รับยา: สม่ำเสมอ',
                    'med_receive_irregular' => 'ได้รับยา: ไม่สม่ำเสมอ',
                    'med_admin_self' => 'การบริหารยา: ด้วยตนเอง',
                    'med_admin_other' => 'การบริหารยา: ผู้อื่น',
                    'med_error_has' => 'ความผิดพลาด: มี',
                    'med_error_none' => 'ความผิดพลาด: ไม่มี',
                    'med_error_wrong_swallow' => 'กินยาผิด',
                    'med_error_wrong_injection' => 'ฉีดยาผิด',
                    'med_error_wrong_inhalation' => 'พ่นยาผิด'
                ];
                foreach ($med_fields as $key => $label) {
                    if (!empty($data[$key])) {
                        if (!isset($assessment_summary['medication']['details'][$label])) {
                            $assessment_summary['medication']['details'][$label] = 0;
                        }
                        $assessment_summary['medication']['details'][$label]++;
                    }
                }

                // Safety
                if (!empty($data['safety_fall_panel'])) {
                    $assessment_summary['safety']['has_problem']++;
                }
                if (!empty($data['safety_fall_none'])) {
                    $assessment_summary['safety']['no_problem']++;
                }
                $safety_fields = [
                    'safety_fall_safe' => 'ปลอดภัยต่อการพลัดตกหกล้ม',
                    'safety_fall_risk' => 'เสี่ยงต่อการพลัดตกหกล้ม'
                ];
                foreach ($safety_fields as $key => $label) {
                    if (!empty($data[$key])) {
                        if (!isset($assessment_summary['safety']['details'][$label])) {
                            $assessment_summary['safety']['details'][$label] = 0;
                        }
                        $assessment_summary['safety']['details'][$label]++;
                    }
                }

                // Spiritual
                if (!empty($data['Spiritual_fall_panel'])) {
                    $assessment_summary['spiritual']['has_problem']++;
                }
                if (!empty($data['Spiritual_fall_none'])) {
                    $assessment_summary['spiritual']['no_problem']++;
                }
                $spiritual_fields = [
                    'Spiritual_fall_belief' => 'ความเชื่อ/เครื่องยึดเหนี่ยวจิตใจ'
                ];
                foreach ($spiritual_fields as $key => $label) {
                    if (!empty($data[$key])) {
                        if (!isset($assessment_summary['spiritual']['details'][$label])) {
                            $assessment_summary['spiritual']['details'][$label] = 0;
                        }
                        $assessment_summary['spiritual']['details'][$label]++;
                    }
                }

                // Service
                if (!empty($data['Service _fall_panel'])) {
                    $assessment_summary['service']['has_problem']++;
                }
                if (!empty($data['Service _fall_none'])) {
                    $assessment_summary['service']['no_problem']++;
                }
                $service_fields = [
                    'Service _fall_hp' => 'โรงพยาบาล',
                    'Service _fall_brh' => 'รพ.สต./ศสม',
                    'Service _fall_clinic' => 'คลินิก'
                ];
                foreach ($service_fields as $key => $label) {
                    if (!empty($data[$key])) {
                        if (!isset($assessment_summary['service']['details'][$label])) {
                            $assessment_summary['service']['details'][$label] = 0;
                        }
                        $assessment_summary['service']['details'][$label]++;
                    }
                }
            }
        }
    }

    // เรียงลำดับรายละเอียดในแต่ละหัวข้อตามจำนวนที่ถูกเลือกมากที่สุด
    foreach ($assessment_summary as $key => &$summary) {
        if (isset($summary['details']) && is_array($summary['details'])) {
            arsort($summary['details']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ด - ระบบ INHOMESSS</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #0f766e;
            --primary-light: #14b8a6;
            --accent: #0ea5e9;
            --warning: #f59e0b;
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #1e293b;
            --muted: #64748b;
            --border: #e2e8f0;
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: var(--card);
            padding: 20px 30px;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .dashboard-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            margin: 0;
        }

        /* ส่วนของ Grid แผนภูมิ */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 24px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: var(--card);
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border);
            min-height: 400px;
        }

        .chart-header {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* ส่วนของตัวเลขสรุป */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--card);
            padding: 20px;
            border-radius: 12px;
            border-left: 5px solid var(--primary);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .stat-label {
            color: var(--muted);
            font-size: 14px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
        }

        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .quick-link {
            background: white;
            padding: 15px;
            border-radius: 12px;
            text-decoration: none;
            color: var(--text);
            font-weight: 500;
            border: 1px solid var(--border);
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quick-link:hover {
            border-color: var(--primary);
            background: #f0fdfa;
            transform: translateY(-2px);
        }

        .btn-add {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            padding: 12px 24px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(15, 118, 110, 0.3);
        }

        canvas {
            width: 100% !important;
            height: 300px !important;
        }

        .summary-section {
            background: var(--card);
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border);
            margin-bottom: 24px;
        }

        .summary-section h3 {
            font-size: 20px;
            font-weight: 600;
            color: var(--primary);
            margin: 0 0 20px 0;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--border);
        }

        .summary-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 12px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            background: rgba(15, 118, 110, 0.05);
            border-radius: 8px;
            border-left: 4px solid var(--primary);
        }

        .summary-item-name {
            font-weight: 500;
            color: var(--text);
        }

        .summary-item-count {
            font-weight: 700;
            color: var(--primary);
            font-size: 18px;
        }

        .summary-item {
            cursor: pointer;
            transition: all 0.2s;
        }

        .summary-item:hover {
            background: rgba(15, 118, 110, 0.1);
            transform: translateX(4px);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .modal-content {
            background-color: var(--card);
            margin: 5% auto;
            padding: 0;
            border-radius: 16px;
            width: 90%;
            max-width: 800px;
            max-height: 80vh;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            animation: slideDown 0.3s;
            display: flex;
            flex-direction: column;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            padding: 24px 30px;
            border-bottom: 2px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            border-radius: 16px 16px 0 0;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
        }

        .modal-close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
            transition: transform 0.2s;
        }

        .modal-close:hover {
            transform: scale(1.2);
        }

        .modal-body {
            padding: 24px 30px;
            overflow-y: auto;
            flex: 1;
        }

        .patient-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .patient-item {
            background: rgba(15, 118, 110, 0.05);
            padding: 16px 20px;
            border-radius: 10px;
            border-left: 4px solid var(--primary);
            transition: all 0.2s;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .patient-item:hover {
            background: rgba(15, 118, 110, 0.1);
            transform: translateX(4px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .patient-info {
            flex: 1;
        }

        .patient-name {
            font-weight: 600;
            font-size: 16px;
            color: var(--text);
            margin-bottom: 4px;
        }

        .patient-details {
            font-size: 14px;
            color: var(--muted);
        }

        .patient-link {
            background: var(--primary);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .patient-link:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(15, 118, 110, 0.3);
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: var(--muted);
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--muted);
        }

        .empty-state::before {
            content: "📭";
            font-size: 48px;
            display: block;
            margin-bottom: 12px;
        }

        .assessment-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 16px;
        }

        .assessment-card {
            background: rgba(15, 118, 110, 0.03);
            padding: 16px;
            border-radius: 12px;
            border: 1px solid var(--border);
        }

        .assessment-card h4 {
            font-size: 16px;
            font-weight: 600;
            color: var(--primary);
            margin: 0 0 12px 0;
        }

        .assessment-stats {
            display: flex;
            gap: 20px;
        }

        .assessment-stat {
            flex: 1;
            text-align: center;
        }

        .assessment-stat-label {
            font-size: 12px;
            color: var(--muted);
            margin-bottom: 4px;
        }

        .assessment-stat-value {
            font-size: 24px;
            font-weight: 700;
        }

        .assessment-stat-value.has-problem {
            color: #ef4444;
        }

        .assessment-stat-value.no-problem {
            color: #10b981;
        }

        .assessment-details {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--border);
        }

        .assessment-details-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 8px;
        }

        .assessment-details-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-height: 200px;
            overflow-y: auto;
        }

        .assessment-detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            padding: 8px 12px;
            background: rgba(15, 118, 110, 0.08);
            border-radius: 6px;
            border-left: 3px solid var(--primary-light);
            transition: background 0.2s;
        }

        .assessment-detail-item:hover {
            background: rgba(15, 118, 110, 0.12);
        }

        .assessment-detail-label {
            color: var(--text);
            flex: 1;
            line-height: 1.4;
        }

        .assessment-detail-count {
            font-weight: 700;
            color: var(--primary);
            margin-left: 12px;
            font-size: 14px;
            min-width: 30px;
            text-align: right;
        }

        .menu {
            padding-bottom: 30px;
        }
    </style>
</head>

<body>

    <div class="dashboard-container">
        <header class="dashboard-header">
            <div>
                <h1 class="dashboard-title">สรุปภาพรวมระบบ INHOMESSS</h1>
                <p style="color: var(--muted); margin: 5px 0 0 0;">อัปเดตข้อมูลล่าสุด: <?php echo date('d/m/Y H:i'); ?>
                </p>
            </div>
            <div class="back-link">
                <a href="index.php">← กลับไปหน้าหลัก</a>
            </div>
        </header>
        <div class="menu">
            <h3 style="margin-bottom: 20px; font-size: 25px; color: #0f766e;">เมนูจัดการด่วน</h3>
            <div class="quick-links">
                <a href="index.php" class="quick-link"><span>👥</span> รายชื่อผู้ป่วย</a>
                <a href="visits_summary.php" class="quick-link"><span>🏠</span> ประวัติการเยี่ยมบ้าน</a>
            </div>
        </div>



        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-label">ผู้ป่วยทั้งหมด</div>
                <div class="stat-value"><?php echo number_format($patient_count); ?></div>
            </div>
            <div class="stat-card" style="border-left-color: var(--accent);">
                <div class="stat-label">การประเมินสำเร็จ</div>
                <div class="stat-value"><?php echo number_format($assessment_count); ?></div>
            </div>
            <div class="stat-card" style="border-left-color: var(--warning);">
                <div class="stat-label">จำนวนการเยี่ยมบ้าน</div>
                <div class="stat-value"><?php echo number_format($visit_count); ?></div>
            </div>
        </div>

        <div class="charts-grid">
            <div class="chart-card">
                <div class="chart-header">📊 สัดส่วนกิจกรรมในระบบ</div>
                <canvas id="doughnutChart"></canvas>
            </div>
            <div class="chart-card">
                <div class="chart-header">📈 กราฟเปรียบเทียบข้อมูล</div>
                <canvas id="barChart"></canvas>
            </div>
        </div>

        <!-- ตำบลที่เยี่ยมบ่อย -->
        <div class="summary-section">
            <h3>🏘️ ตำบลที่เยี่ยมบ่อย (Top 10)</h3>
            <?php if (!empty($top_tambons)): ?>
                <div class="summary-list">
                    <?php foreach ($top_tambons as $index => $tambon): ?>
                        <div class="summary-item" data-type="tambon"
                            data-value="<?php echo htmlspecialchars($tambon['name']); ?>">
                            <span class="summary-item-name"><?php echo htmlspecialchars($tambon['name'] ?: 'ไม่ระบุ'); ?></span>
                            <span class="summary-item-count"><?php echo number_format($tambon['count']); ?>
                                <?php echo isset($tambon['is_patient_count']) ? 'คน' : 'ครั้ง'; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: var(--muted); text-align: center; padding: 20px;">ยังไม่มีข้อมูลตำบล</p>
            <?php endif; ?>
        </div>

        <!-- โรคที่เป็นเยอะ -->
        <div class="summary-section">
            <h3>🏥 โรคที่เป็นบ่อย (Top 10)</h3>
            <?php if (!empty($top_illnesses)): ?>
                <div class="summary-list">
                    <?php foreach ($top_illnesses as $illness => $count): ?>
                        <div class="summary-item" data-type="illness" data-value="<?php echo htmlspecialchars($illness); ?>">
                            <span class="summary-item-name"><?php echo htmlspecialchars($illness); ?></span>
                            <span class="summary-item-count"><?php echo number_format($count); ?> คน</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: var(--muted); text-align: center; padding: 20px;">ยังไม่มีข้อมูลโรค</p>
            <?php endif; ?>
        </div>

        <!-- สรุปการประเมิน INHOMESSS -->
        <div class="summary-section">
            <h3>📋 สรุปการประเมิน INHOMESSS (ทุกหัวข้อ)</h3>
            <div class="assessment-grid">
                <div class="assessment-card">
                    <h4>Immobility (การเคลื่อนไหว)</h4>
                    <div class="assessment-stats">
                        <div class="assessment-stat">
                            <div class="assessment-stat-label">มีปัญหา</div>
                            <div class="assessment-stat-value has-problem">
                                <?php echo $assessment_summary['immobility']['has_problem']; ?>
                            </div>
                        </div>
                        <div class="assessment-stat">
                            <div class="assessment-stat-label">ไม่มีปัญหา</div>
                            <div class="assessment-stat-value no-problem">
                                <?php echo $assessment_summary['immobility']['no_problem']; ?>
                            </div>
                        </div>
                    </div>
                    <?php if (!empty($assessment_summary['immobility']['details'])): ?>
                        <div class="assessment-details">
                            <div class="assessment-details-title">รายละเอียดที่ถูกเลือกมากที่สุด:</div>
                            <div class="assessment-details-list">
                                <?php
                                $top_details = array_slice($assessment_summary['immobility']['details'], 0, 5, true);
                                foreach ($top_details as $label => $count):
                                    ?>
                                    <div class="assessment-detail-item">
                                        <span class="assessment-detail-label"><?php echo htmlspecialchars($label); ?></span>
                                        <span class="assessment-detail-count"><?php echo $count; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="assessment-card">
                    <h4>Nutrition (โภชนาการ)</h4>
                    <div class="assessment-stats">
                        <div class="assessment-stat">
                            <div class="assessment-stat-label">มีปัญหา</div>
                            <div class="assessment-stat-value has-problem">
                                <?php echo $assessment_summary['nutrition']['has_problem']; ?>
                            </div>
                        </div>
                        <div class="assessment-stat">
                            <div class="assessment-stat-label">ไม่มีปัญหา</div>
                            <div class="assessment-stat-value no-problem">
                                <?php echo $assessment_summary['nutrition']['no_problem']; ?>
                            </div>
                        </div>
                    </div>
                    <?php if (!empty($assessment_summary['nutrition']['details'])): ?>
                        <div class="assessment-details">
                            <div class="assessment-details-title">รายละเอียดที่ถูกเลือกมากที่สุด:</div>
                            <div class="assessment-details-list">
                                <?php
                                $top_details = array_slice($assessment_summary['nutrition']['details'], 0, 5, true);
                                foreach ($top_details as $label => $count):
                                    ?>
                                    <div class="assessment-detail-item">
                                        <span class="assessment-detail-label"><?php echo htmlspecialchars($label); ?></span>
                                        <span class="assessment-detail-count"><?php echo $count; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="assessment-card">
                    <h4>Home Environment (สภาพแวดล้อมบ้าน)</h4>
                    <div class="assessment-stats">
                        <div class="assessment-stat">
                            <div class="assessment-stat-label">มีปัญหา</div>
                            <div class="assessment-stat-value has-problem">
                                <?php echo $assessment_summary['home']['has_problem']; ?>
                            </div>
                        </div>
                        <div class="assessment-stat">
                            <div class="assessment-stat-label">ไม่มีปัญหา</div>
                            <div class="assessment-stat-value no-problem">
                                <?php echo $assessment_summary['home']['no_problem']; ?>
                            </div>
                        </div>
                    </div>
                    <?php if (!empty($assessment_summary['home']['details'])): ?>
                        <div class="assessment-details">
                            <div class="assessment-details-title">รายละเอียดที่ถูกเลือกมากที่สุด:</div>
                            <div class="assessment-details-list">
                                <?php
                                $top_details = array_slice($assessment_summary['home']['details'], 0, 5, true);
                                foreach ($top_details as $label => $count):
                                    ?>
                                    <div class="assessment-detail-item">
                                        <span class="assessment-detail-label"><?php echo htmlspecialchars($label); ?></span>
                                        <span class="assessment-detail-count"><?php echo $count; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="assessment-card">
                    <h4>Other People (ผู้ดูแลและครอบครัว)</h4>
                    <div class="assessment-stats">
                        <div class="assessment-stat">
                            <div class="assessment-stat-label">มีปัญหา</div>
                            <div class="assessment-stat-value has-problem">
                                <?php echo $assessment_summary['people']['has_problem']; ?>
                            </div>
                        </div>
                        <div class="assessment-stat">
                            <div class="assessment-stat-label">ไม่มีปัญหา</div>
                            <div class="assessment-stat-value no-problem">
                                <?php echo $assessment_summary['people']['no_problem']; ?>
                            </div>
                        </div>
                    </div>
                    <?php if (!empty($assessment_summary['people']['details'])): ?>
                        <div class="assessment-details">
                            <div class="assessment-details-title">รายละเอียดที่ถูกเลือกมากที่สุด:</div>
                            <div class="assessment-details-list">
                                <?php
                                $top_details = array_slice($assessment_summary['people']['details'], 0, 5, true);
                                foreach ($top_details as $label => $count):
                                    ?>
                                    <div class="assessment-detail-item">
                                        <span class="assessment-detail-label"><?php echo htmlspecialchars($label); ?></span>
                                        <span class="assessment-detail-count"><?php echo $count; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="assessment-card">
                    <h4>Medication (การใช้ยา)</h4>
                    <div class="assessment-stats">
                        <div class="assessment-stat">
                            <div class="assessment-stat-label">มีปัญหา</div>
                            <div class="assessment-stat-value has-problem">
                                <?php echo $assessment_summary['medication']['has_problem']; ?>
                            </div>
                        </div>
                        <div class="assessment-stat">
                            <div class="assessment-stat-label">ไม่มีปัญหา</div>
                            <div class="assessment-stat-value no-problem">
                                <?php echo $assessment_summary['medication']['no_problem']; ?>
                            </div>
                        </div>
                    </div>
                    <?php if (!empty($assessment_summary['medication']['details'])): ?>
                        <div class="assessment-details">
                            <div class="assessment-details-title">รายละเอียดที่ถูกเลือกมากที่สุด:</div>
                            <div class="assessment-details-list">
                                <?php
                                $top_details = array_slice($assessment_summary['medication']['details'], 0, 5, true);
                                foreach ($top_details as $label => $count):
                                    ?>
                                    <div class="assessment-detail-item">
                                        <span class="assessment-detail-label"><?php echo htmlspecialchars($label); ?></span>
                                        <span class="assessment-detail-count"><?php echo $count; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="assessment-card">
                    <h4>Safety (ความปลอดภัย)</h4>
                    <div class="assessment-stats">
                        <div class="assessment-stat">
                            <div class="assessment-stat-label">มีปัญหา</div>
                            <div class="assessment-stat-value has-problem">
                                <?php echo $assessment_summary['safety']['has_problem']; ?>
                            </div>
                        </div>
                        <div class="assessment-stat">
                            <div class="assessment-stat-label">ไม่มีปัญหา</div>
                            <div class="assessment-stat-value no-problem">
                                <?php echo $assessment_summary['safety']['no_problem']; ?>
                            </div>
                        </div>
                    </div>
                    <?php if (!empty($assessment_summary['safety']['details'])): ?>
                        <div class="assessment-details">
                            <div class="assessment-details-title">รายละเอียดที่ถูกเลือกมากที่สุด:</div>
                            <div class="assessment-details-list">
                                <?php
                                $top_details = array_slice($assessment_summary['safety']['details'], 0, 5, true);
                                foreach ($top_details as $label => $count):
                                    ?>
                                    <div class="assessment-detail-item">
                                        <span class="assessment-detail-label"><?php echo htmlspecialchars($label); ?></span>
                                        <span class="assessment-detail-count"><?php echo $count; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="assessment-card">
                    <h4>Spiritual (สุขภาพจิตใจ/ศาสนา)</h4>
                    <div class="assessment-stats">
                        <div class="assessment-stat">
                            <div class="assessment-stat-label">มีปัญหา</div>
                            <div class="assessment-stat-value has-problem">
                                <?php echo $assessment_summary['spiritual']['has_problem']; ?>
                            </div>
                        </div>
                        <div class="assessment-stat">
                            <div class="assessment-stat-label">ไม่มีปัญหา</div>
                            <div class="assessment-stat-value no-problem">
                                <?php echo $assessment_summary['spiritual']['no_problem']; ?>
                            </div>
                        </div>
                    </div>
                    <?php if (!empty($assessment_summary['spiritual']['details'])): ?>
                        <div class="assessment-details">
                            <div class="assessment-details-title">รายละเอียดที่ถูกเลือกมากที่สุด:</div>
                            <div class="assessment-details-list">
                                <?php
                                $top_details = array_slice($assessment_summary['spiritual']['details'], 0, 5, true);
                                foreach ($top_details as $label => $count):
                                    ?>
                                    <div class="assessment-detail-item">
                                        <span class="assessment-detail-label"><?php echo htmlspecialchars($label); ?></span>
                                        <span class="assessment-detail-count"><?php echo $count; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="assessment-card">
                    <h4>Service (บริการที่จำเป็น)</h4>
                    <div class="assessment-stats">
                        <div class="assessment-stat">
                            <div class="assessment-stat-label">มีปัญหา</div>
                            <div class="assessment-stat-value has-problem">
                                <?php echo $assessment_summary['service']['has_problem']; ?>
                            </div>
                        </div>
                        <div class="assessment-stat">
                            <div class="assessment-stat-label">ไม่มีปัญหา</div>
                            <div class="assessment-stat-value no-problem">
                                <?php echo $assessment_summary['service']['no_problem']; ?>
                            </div>
                        </div>
                    </div>
                    <?php if (!empty($assessment_summary['service']['details'])): ?>
                        <div class="assessment-details">
                            <div class="assessment-details-title">รายละเอียดที่ถูกเลือกมากที่สุด:</div>
                            <div class="assessment-details-list">
                                <?php
                                $top_details = array_slice($assessment_summary['service']['details'], 0, 5, true);
                                foreach ($top_details as $label => $count):
                                    ?>
                                    <div class="assessment-detail-item">
                                        <span class="assessment-detail-label"><?php echo htmlspecialchars($label); ?></span>
                                        <span class="assessment-detail-count"><?php echo $count; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <script>
        // ข้อมูลจาก PHP ส่งมายัง JavaScript
        const statsData = {
            patients: <?php echo $patient_count; ?>,
            assessments: <?php echo $assessment_count; ?>,
            visits: <?php echo $visit_count; ?>
        };

        // 1. Doughnut Chart (วงกลม)
        const ctx1 = document.getElementById('doughnutChart').getContext('2d');
        new Chart(ctx1, {
            type: 'doughnut',
            data: {
                labels: ['การประเมิน', 'การเยี่ยมบ้าน'],
                datasets: [{
                    data: [statsData.assessments, statsData.visits],
                    backgroundColor: ['#14b8a6', '#f59e0b'],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                },
                cutout: '70%'
            }
        });

        // 2. Bar Chart (แท่ง)
        const ctx2 = document.getElementById('barChart').getContext('2d');
        new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: ['ผู้ป่วย', 'การประเมิน', 'การเยี่ยมบ้าน'],
                datasets: [{
                    label: 'จำนวนรวม',
                    data: [statsData.patients, statsData.assessments, statsData.visits],
                    backgroundColor: ['#0f766e', '#14b8a6', '#f59e0b'],
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, grid: { display: false } },
                    x: { grid: { display: false } }
                }
            }
        });

        // Modal สำหรับแสดงรายชื่อผู้ป่วย
        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.id = 'patientModal';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modalTitle">รายชื่อผู้ป่วย</h2>
                    <span class="modal-close" id="modalClose">&times;</span>
                </div>
                <div class="modal-body" id="modalBody">
                    <div class="loading">กำลังโหลดข้อมูล...</div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);

        // เปิด/ปิด modal
        const modalClose = document.getElementById('modalClose');
        modalClose.addEventListener('click', () => {
            modal.style.display = 'none';
        });

        window.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });

        // จัดการการคลิกที่ตำบลและโรค
        document.querySelectorAll('.summary-item[data-type]').forEach(item => {
            item.addEventListener('click', function () {
                const type = this.dataset.type;
                const value = this.dataset.value;

                if (!type || !value) return;

                // ตั้งค่าหัวข้อ modal
                const title = type === 'tambon'
                    ? `รายชื่อผู้ป่วยในตำบล: ${value}`
                    : `รายชื่อผู้ป่วยที่มีโรค: ${value}`;

                document.getElementById('modalTitle').textContent = title;
                document.getElementById('modalBody').innerHTML = '<div class="loading">กำลังโหลดข้อมูล...</div>';
                modal.style.display = 'block';

                // ดึงข้อมูลผู้ป่วย
                fetch(`get_patients_by_filter.php?type=${encodeURIComponent(type)}&value=${encodeURIComponent(value)}`)
                    .then(response => response.json())
                    .then(data => {
                        const modalBody = document.getElementById('modalBody');

                        if (!data.success || !data.patients || data.patients.length === 0) {
                            modalBody.innerHTML = '<div class="empty-state">ไม่พบข้อมูลผู้ป่วย</div>';
                            return;
                        }

                        let html = `<div style="margin-bottom: 16px; color: var(--muted); font-size: 14px;">
                            พบทั้งหมด <strong style="color: var(--primary);">${data.count}</strong> ราย
                        </div>`;
                        html += '<div class="patient-list">';

                        data.patients.forEach(patient => {
                            const patientName = patient.fullname || 'ไม่ระบุชื่อ';
                            const patientHN = patient.hn || 'ไม่ระบุ HN';
                            const patientGender = patient.gender || '-';
                            const address = [
                                patient.address_tambon,
                                patient.address_amphur,
                                patient.address_province
                            ].filter(Boolean).join(', ') || 'ไม่ระบุที่อยู่';

                            const patientUrl = `patient_overview.php?patient_id=${patient.id}${patient.hn ? '&hn=' + encodeURIComponent(patient.hn) : ''}`;

                            html += `
                                <div class="patient-item">
                                    <div class="patient-info">
                                        <div class="patient-name">${patientName}</div>
                                        <div class="patient-details">
                                            HN: ${patientHN} | เพศ: ${patientGender} | ${address}
                                        </div>
                                    </div>
                                    <a href="${patientUrl}" class="patient-link">ดูข้อมูล</a>
                                </div>
                            `;
                        });

                        html += '</div>';
                        modalBody.innerHTML = html;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        document.getElementById('modalBody').innerHTML =
                            '<div class="empty-state">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
                    });
            });
        });
    </script>
</body>

</html>