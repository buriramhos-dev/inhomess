<?php
require_once __DIR__ . '/db.php';

// ตั้งค่า timezone เป็นไทย
date_default_timezone_set('Asia/Bangkok');

// รับข้อมูลผู้ป่วยจาก URL
$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : null;
$hn = isset($_GET['hn']) ? trim($_GET['hn']) : '';
$fullname = isset($_GET['fullname']) ? trim($_GET['fullname']) : '';
$visit_id = isset($_GET['visit_id']) ? (int)$_GET['visit_id'] : null;

// ดึงข้อมูลผู้ป่วยจากฐานข้อมูล (ถ้ามี patient_id)
$patient = null;
if ($patient_id && function_exists('db_get_patient_by_id')) {
    $patient = db_get_patient_by_id($patient_id);
    if ($patient) {
        $hn = $patient['hn'] ?? $hn;
        $fullname = $patient['fullname'] ?? $fullname;
    }
}

// ดึงข้อมูลการประเมินเดิม (ถ้ามี)
$existing_assessment = null;
$existing_data = [];
$prefill = isset($_GET['prefill']) ? $_GET['prefill'] : null;

if ($prefill !== '0' && function_exists('db_get_last_assessment_by_patient')) {
    $pidForSearch = $patient_id ?: null;
    $hnForSearch = !empty($hn) ? $hn : null;
    $fullnameForSearch = !empty($fullname) ? $fullname : null;
    
    $existing_assessment = db_get_last_assessment_by_patient($pidForSearch, $hnForSearch, $fullnameForSearch);
    
    if ($existing_assessment && !empty($existing_assessment['data'])) {
        $existing_data = is_string($existing_assessment['data']) 
            ? json_decode($existing_assessment['data'], true) 
            : $existing_assessment['data'];
        $existing_data = $existing_data ?: [];
    }
}

// ตั้งค่าเริ่มต้น - ใช้ข้อมูลเดิมถ้ามี และ prefill ไม่ปิด
$assess_date = (!empty($existing_assessment['assess_date']) && $prefill !== '0') 
    ? $existing_assessment['assess_date'] 
    : date('Y-m-d');
$assessor = (!empty($existing_assessment['assessor']) && $prefill !== '0') 
    ? $existing_assessment['assessor'] 
    : '';
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>การประเมิน INHOMESSS</title>
  <style>
    :root {
        --primary-teal: #0f766e;
        --primary-teal-dark: #0d5d57;
        --light-teal: #14b8a6;
        --bg-gray: #f8fafc;
        --text-dark: #1f2937;
        --blue-action: #0ea5e9;
        --orange-action: #f59e0b;
        --red-action: #ef4444;
    }
    
    * { margin:0; padding:0; box-sizing:border-box }
    body { 
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: var(--bg-gray);
        padding: 20px;
        color: var(--text-dark);
    }
    .wrap { 
        max-width: 1200px; 
        margin: 0 auto; 
        background: #fff; 
        padding: 30px; 
        border-radius: 12px; 
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        overflow-x: auto;
    }
    h1 { 
        font-size: 28px; 
        margin-bottom: 20px;
        color: var(--primary-teal);
        font-weight: 700;
    }
    .back { margin-bottom: 20px }
    .back a { 
        color: var(--primary-teal);
        text-decoration: none; 
        font-size: 14px;
        font-weight: 500;
        transition: color 0.2s;
    }
    .back a:hover {
        color: var(--primary-teal-dark);
    }
    
    /* Tabs */
    .tabs { 
        display: flex; 
        gap: 4px; 
        flex-wrap: wrap; 
        margin-bottom: 24px; 
        border-bottom: 2px solid #e5e7eb;
    }
    .tab { 
        padding: 12px 16px; 
        background: #f0f0f0; 
        border: none; 
        cursor: pointer; 
        font-size: 13px; 
        transition: all 0.2s; 
        white-space: nowrap;
        border-radius: 8px 8px 0 0;
        font-weight: 500;
    }
    .tab:hover { 
        background: #e5e7eb;
    }
    .tab.active { 
        background: var(--primary-teal); 
        color: #fff;
    }
    
    .panels { margin-top: 20px }
    .panel { display: none }
    .panel.active { display: block }
    
    .section-title { 
        background: linear-gradient(135deg, var(--primary-teal) 0%, var(--light-teal) 100%);
        color: white;
        padding: 14px 20px; 
        margin: 20px 0 16px 0; 
        font-weight: 600; 
        border-left: 4px solid var(--primary-teal-dark);
        font-size: 15px;
        border-radius: 6px;
        box-shadow: 0 2px 4px rgba(15, 118, 110, 0.2);
    }
    
    .patient-info { 
        display: grid; 
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
        gap: 16px; 
        margin-bottom: 30px;
        padding: 20px;
        background: #f9fafb;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
    }
    .patient-info .field { 
        display: flex; 
        flex-direction: column;
    }
    .patient-info label { 
        font-size: 13px; 
        font-weight: 600; 
        margin-bottom: 6px;
        color: var(--text-dark);
    }
    .patient-info input { 
        padding: 10px 12px; 
        border: 1px solid #d1d5db; 
        border-radius: 6px; 
        font-size: 14px;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .patient-info input:focus {
        outline: none;
        border-color: var(--primary-teal);
        box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);
    }
    
    /* Table for form data */
    table { 
        width: 100%; 
        border-collapse: collapse; 
        margin: 16px 0;
        background: white;
    }
    table th { 
        background: #f0f0f0;
        color: var(--text-dark);
        padding: 12px 10px; 
        border: 1px solid #ddd; 
        text-align: left; 
        font-size: 13px; 
        font-weight: 600;
    }
    table td { 
        padding: 10px; 
        border: 1px solid #e5e7eb; 
        font-size: 13px;
        background: white;
    }
    table input, table select { 
        width: 100%; 
        padding: 8px; 
        border: 1px solid #d1d5db; 
        border-radius: 4px; 
        font-size: 13px; 
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        transition: border-color 0.2s;
    }
    table input:focus, table select:focus {
        outline: none;
        border-color: var(--primary-teal);
    }
    
    .checkbox-group { 
        display: flex; 
        gap: 12px; 
        flex-wrap: wrap; 
        margin: 12px 0;
    }
    .checkbox-item { 
        display: flex; 
        align-items: center; 
        gap: 6px; 
        font-size: 13px;
    }
    .checkbox-item input { 
        width: auto; 
        margin: 0;
    }
    
    .actions { 
        text-align: center; 
        margin-top: 40px; 
        padding-top: 30px; 
        border-top: 2px solid #e5e7eb;
    }
    button { 
        padding: 12px 24px; 
        margin: 0 8px; 
        border: none; 
        border-radius: 8px; 
        cursor: pointer; 
        font-size: 15px; 
        font-weight: 600;
        transition: all 0.2s;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    .btn-save { 
        background: var(--primary-teal);
        color: #fff;
    }
    .btn-save:hover { 
        background: var(--primary-teal-dark);
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(15, 118, 110, 0.3);
    }
    .btn-clear {
        background: var(--orange-action);
        color: #fff;
    }
    .btn-clear:hover {
        background: #d97706;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(245, 158, 11, 0.3);
    }
    .btn-cancel { 
        background: var(--red-action);
        color: #fff;
    }
    .btn-cancel:hover { 
        background: #dc2626;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(239, 68, 68, 0.3);
    }
    
    textarea { 
        width: 100%; 
        padding: 10px 12px; 
        border: 1px solid #d1d5db; 
        border-radius: 6px; 
        font-size: 13px; 
        resize: vertical; 
        min-height: 80px; 
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        transition: border-color 0.2s;
    }
    textarea:focus {
        outline: none;
        border-color: var(--primary-teal);
        box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);
    }
    
    /* Standardize small section headers inside panels to sit flush with tables */
    .panel h3 { 
        margin: 0; 
        font-size: 14px; 
        background: var(--primary-teal);
        color: black;
        padding: 10px 12px; 
        border-left: 4px solid var(--primary-teal-dark);
        border-radius: 4px 0 0 0;
    }
    /* pull table up 1px so header background meets table (removes tiny gap) */
    .panel table { 
        margin: -1px 0 12px 0;
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="back"><a href="index.php">← กลับหน้าหลัก</a></div>
    <h1>การประเมิน INHOMESSS</h1>
    
    <!-- Tab bar -->
    <div class="tabs">
      <button class="tab active" data-panel="immobility">Immobility</button>
      <button class="tab" data-panel="nutrition">Nutrition</button>
      <button class="tab" data-panel="home">Home Environment</button>
      <button class="tab" data-panel="people">Other People</button>
      <button class="tab" data-panel="med">Medication</button>
      <button class="tab" data-panel="exam">Examination</button>
      <button class="tab" data-panel="safety">Safety</button>
      <button class="tab" data-panel="spiritual">Spiritual</button>
      <button class="tab" data-panel="service">Service</button>
      <button class="tab" data-panel="another">อื่นๆ</button>
    </div>

    <form action="save_inhomess.php" method="post">
      <?php if ($patient_id): ?>
      <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
      <?php endif; ?>
      <?php if ($visit_id): ?>
      <input type="hidden" name="visit_id" value="<?php echo $visit_id; ?>">
      <?php endif; ?>
      
      <!-- Patient Info (always visible) -->
      <div class="patient-info">
        <div class="field"><label>HN</label><input name="hn" type="text" value="<?php echo htmlspecialchars($hn); ?>" required></div>
        <div class="field"><label>ชื่อ-สกุล</label><input name="fullname" type="text" value="<?php echo htmlspecialchars($fullname); ?>" required></div>
        <div class="field"><label>วันที่ประเมิน</label><input name="assess_date" type="date" value="<?php echo htmlspecialchars($assess_date); ?>"></div>
        <div class="field"><label>ผู้ประเมิน</label><input name="assessor" type="text" value="<?php echo htmlspecialchars($assessor); ?>"></div>
      </div>
      
      <?php if (!empty($existing_data)): ?>
      <input type="hidden" id="existing-data" value="<?php echo htmlspecialchars(json_encode($existing_data, JSON_UNESCAPED_UNICODE)); ?>">
      <?php endif; ?>
      <div class="panels">
       <div id="immobility" class="panel active">
  <div class="section-title">Immobility (การเคลื่อนไหว)</div>
  <table>
    <tr>
      <th width="30%">รายการประเมิน</th>
      <th></th>
    </tr>
    <tr>
      <td>มีปัญหา</td>
      <td><input type="checkbox" name="imm_problem_has" value="yes"></td>
    </tr>
    <tr>
      <td>ไม่มีปัญหา</td>
      <td><input type="checkbox" name="imm_problem_none" value="yes"></td>
    </tr>
    <tr>
      <td>ทำได้เอง</td>
      <td><input type="checkbox" name="imm_self_sufficient" value="yes"></td>
    </tr>
    <tr>
      <td>ทำเองไม่ได้</td>
      <td><input type="checkbox" name="imm_not_self_sufficient" value="yes"></td>
    </tr>
    <tr>
      <td>ผู้ป่วยติดเตียง</td>
      <td><input type="checkbox" name="imm_bedridden" value="yes"></td>
    </tr>
    <tr>
      <td>ผู้ป่วยติดบ้าน</td>
      <td><input type="checkbox" name="imm_housebound" value="yes"></td>
    </tr>
    <tr>
      <td>มีปัญหาการทรงตัว/การเดิน</td>
      <td><input type="checkbox" name="imm_balance_walking_problem" value="yes"></td>
    </tr>
    <tr>
      <td>มีปัญหาระบบประสาทสัมผัส</td>
      <td><input type="checkbox" name="imm_sensory_problem" value="yes"></td>
    </tr>
  </table>

  <div style="margin-top:20px">
    <label style="font-weight:600; display:block; margin-bottom:4px">หมายเหตุการเคลื่อนไหว:</label>
    <textarea name="imm_notes" placeholder="บันทึกรายละเอียดเพิ่มเติมเกี่ยวกับการเคลื่อนไหวของผู้ป่วย"></textarea>
  </div>
</div>
<div id="nutrition" class="panel">
  <div class="section-title">Nutrition (โภชนาการ)</div>

  
  <div style="margin-bottom:12px; font-weight:600"></div>
  <table>
    <tr>
      <th>มีปัญหา</th>
      <th>ไม่มีปัญหา</th>
      <th>ปกติ</th>
      <th>อ้วน</th>
      <th>ผอม</th>
      <th>จำนวนมื้อต่อวัน</th>
      <th>ผู้ดูแลอาหาร</th>
    </tr>
    <tr>
      <td><input type="checkbox" name="nut_problem_general" value="yes"></td>
      <td><input type="checkbox" name="nut_no_problem_general" value="yes"></td>
      <td><input type="checkbox" name="nut_status_normal" value="yes"></td>
      <td><input type="checkbox" name="nut_status_obese" value="yes"></td>
      <td><input type="checkbox" name="nut_status_underweight" value="yes"></td>
      <td><input type="text" name="nut_meals_per_day" placeholder="เช่น 3"></td>
      <td><input type="text" name="nut_meal_carer"></td>
    </tr>
  </table>

  <div style="margin-bottom:12px; font-weight:600">ชนิดอาหาร และ ที่มาของอาหาร</div>
  <table style="text-align:center">
    <tr>
      
      
    </tr>
    <tr>
      <th>ธรรมดา</th>
      <th>อ่อน/นิ่ม</th>
      <th>เหลว/ปั่น</th>
      <th>หวาน/เบาหวาน</th>
      <th>ปรุงเอง</th>
      <th>ซื้อสำเร็จรูป</th>
      <th>อาหารแช่แข็ง</th>
      <th>อื่นๆ</th>
    </tr>
    <tr>
      <td><input type="checkbox" name="nut_type_normal" value="yes"></td>
      <td><input type="checkbox" name="nut_type_soft" value="yes"></td>
      <td><input type="checkbox" name="nut_type_liquid" value="yes"></td>
      <td><input type="checkbox" name="nut_type_sweet" value="yes"></td>
      <td><input type="checkbox" name="nut_source_home" value="yes"></td>
      <td><input type="checkbox" name="nut_source_ready" value="yes"></td>
      <td><input type="checkbox" name="nut_source_frozen" value="yes"></td>
      <td><input type="checkbox" name="nut_source_other" value="yes"></td>
    </tr>
  </table>

  <div style="margin-bottom:12px; font-weight:600">การเสพกระตุ้น (Alcohol / Tobacco)</div>
  <table style="text-align:center">
    <tr>
      <th colspan="2">เหล้า/Alcohol</th>
      <th colspan="2">บุหรี่/ยาเส้น</th>
    </tr>
    <tr>
      <th>ดื่ม</th>
      <th>ไม่ดื่ม</th>
      <th>สูบ</th>
      <th>ไม่สูบ</th>
    </tr>
    <tr>
      <td><input type="checkbox" name="nut_alcohol_drink" value="yes"></td>
      <td><input type="checkbox" name="nut_alcohol_abstain" value="yes"></td>
      <td><input type="checkbox" name="nut_tobacco_smoke" value="yes"></td>
      <td><input type="checkbox" name="nut_tobacco_not_smoke" value="yes"></td>
    </tr>
  </table>
  
  <div style="margin-top:20px">
    <label style="font-weight:600; display:block; margin-bottom:4px">หมายเหตุโภชนาการ:</label>
    <textarea name="nut_notes" placeholder="บันทึกรายละเอียดเพิ่มเติมเกี่ยวกับโภชนาการ"></textarea>
  </div>
</div>
<!-- Panel: home -->
<div id="home" class="panel">
  <div class="section-title">Home Environment (สภาพแวดล้อมบ้าน)</div>
  
  <table style="text-align:center;">
    <tr>
      <th rowspan="2" width="5%">ปัญหา</th>
      <th rowspan="2" width="5%">ไม่มีปัญหา</th>
      <th colspan="5">ภายในบ้าน</th>
      <th colspan="3">บริเวณรอบบ้าน</th>
      <th colspan="3">ความมั่นคงแข็งแรง</th> <th colspan="2">ความสว่าง</th> <th colspan="2">ห้องน้ำ</th>
    </tr>
    <tr>
      <th>แออัด</th>
      <th>โปร่งสบาย</th>
      <th>สะอาด</th>
      <th>ไม่มีสัตว์เลี้ยงในบ้าน</th>
      <th>มีสัตว์เลี้ยง</th>
      <th>ไม่มีบริเวณ</th>
      <th>สกปรก</th>
      <th>รกรุงรัง</th>
      <th>มั่นคง/แข็งแรง</th>
      <th>ไม่ค่อยมั่นคง</th> <th>เก่า/ชำรุด</th>
      <th>แสงสว่างเพียงพอมองเห็นพื้นชัดเจน</th>
      <th>แสงสว่างไม่ชัดเจน</th>
      <th>มีห้องน้ำเหมาะสมกับผู้ป่วย</th>
      <th>มีห้องน้ำแต่ไม่เหมาะสมกับผู้ป่วย</th>
    </tr>
    
    <tr>
      <td><input type="radio" name="home_problem_status" value="yes"></td>
      <td><input type="radio" name="home_problem_status" value="no"></td>
      
      <td><input type="checkbox" name="home_indoor_crowded" value="yes"></td>
      <td><input type="checkbox" name="home_indoor_airy" value="yes"></td>
      <td><input type="checkbox" name="home_indoor_clean" value="yes"></td>
      <td><input type="checkbox" name="home_indoor_no_pet" value="yes"></td>
      <td><input type="checkbox" name="home_indoor_has_pet" value="yes"></td>
      
      <td><input type="checkbox" name="home_outdoor_no_area" value="yes"></td>
      <td><input type="checkbox" name="home_outdoor_dirty" value="yes"></td>
      <td><input type="checkbox" name="home_outdoor_cluttered" value="yes"></td>
      
      <td><input type="checkbox" name="home_struct_stable" value="yes"></td>
      <td><input type="checkbox" name="home_struct_semi_stable" value="yes"></td> <td><input type="checkbox" name="home_struct_dilapidated" value="yes"></td>
      
      <td><input type="checkbox" name="home_light_sufficient" value="yes"></td>
      <td><input type="checkbox" name="home_light_dark" value="yes"></td>
      
      <td><input type="checkbox" name="home_toilet_suitable" value="yes"></td>
      <td><input type="checkbox" name="home_toilet_unsuitable" value="yes"></td>
    </tr>
  </table>

  <div style="margin-top:20px">
    <label style="font-weight:600; display:block; margin-bottom:4px">หมายเหตุสภาพแวดล้อมบ้าน:</label>
    <textarea name="home_notes" placeholder="บันทึกรายละเอียดเพิ่มเติม เช่น ระดับความสูงของบันได หรือรายละเอียดห้องน้ำ"></textarea>
  </div>
</div>
<!-- Panel: people another -->
 <div id="people" class="panel">
  <div class="section-title">Other People (ผู้ดูแลและครอบครัว)</div>
  
  <table style="text-align:center;">
    <tr>
      <th rowspan="2" width="5%">มีปัญหา</th>
      <th rowspan="2" width="5%">ไม่มีปัญหา</th>
      <th colspan="7">เมื่อมีภาวะฉุกเฉิน ผู้นำส่ง ร.พ. คือ</th>
      <th colspan="7">ผู้ดูแล</th>
    </tr>
    <tr>
      <th>สามี/ภรรยา</th>
      <th>พ่อ</th>
      <th>แม่</th>
      <th>บุตร</th>
      <th>พี่/น้อง</th>
      <th>ญาติ</th>
      <th>อื่นๆ </th> 
      
      <th>สามี/ภรรยา</th>
      <th>พ่อ</th>
      <th>แม่</th>
      <th>บุตร</th>
      <th>พี่/น้อง</th>
      <th>ญาติ</th>
      <th>อื่นๆ </th>
    </tr>
    
    <tr>
      <td><input type="radio" name="people_problem_status" value="yes"></td>
      <td><input type="radio" name="people_problem_status" value="no"></td>
      
      <td><input type="checkbox" name="people_emergency_spouse" value="yes"></td>
      <td><input type="checkbox" name="people_emergency_father" value="yes"></td>
      <td><input type="checkbox" name="people_emergency_mother" value="yes"></td>
      <td><input type="checkbox" name="people_emergency_child" value="yes"></td>
      <td><input type="checkbox" name="people_emergency_sibling" value="yes"></td>
      <td><input type="checkbox" name="people_emergency_relative" value="yes"></td>
      <td><input type="text" name="people_emergency_other_text" placeholder="ระบุ"></td> <td><input type="checkbox" name="people_carer_spouse" value="yes"></td>
      <td><input type="checkbox" name="people_carer_father" value="yes"></td>
      <td><input type="checkbox" name="people_carer_mother" value="yes"></td>
      <td><input type="checkbox" name="people_carer_child" value="yes"></td>
      <td><input type="checkbox" name="people_carer_sibling" value="yes"></td>
      <td><input type="checkbox" name="people_carer_relative" value="yes"></td>
      <td><input type="text" name="people_carer_other_text" placeholder="ระบุ"></td> </tr>
  </table>

  <div style="margin-top:20px">
    <label style="font-weight:600; display:block; margin-bottom:4px">หมายเหตุผู้ดูแลและครอบครัว:</label>
    <textarea name="people_notes" placeholder="บันทึกรายละเอียดเพิ่มเติมเกี่ยวกับผู้ดูแล สมาชิกครอบครัว หรือความช่วยเหลือที่ได้รับ"></textarea>
  </div>
</div>

        <!-- Panel: Medication -->
   <div id="med" class="panel">
  <div class="section-title">Medication (การใช้ยา)</div>
  
  <table style="text-align:center;">
    <tr>
      <th rowspan="2" width="5%">มีปัญหา</th>
      <th rowspan="2" width="5%">ไม่มีปัญหา</th>
      <th colspan="2">การใช้ยาตามแพทย์สั่ง</th>
      <th colspan="2">ได้รับยา</th>
      <th colspan="2">การบริหารยา</th>
      <th colspan="5">ความผิดพลาดของการบริหารยา</th> </tr>
    <tr>
      <th>ถูกต้อง</th>
      <th>ไม่ถูกต้อง</th>
      <th>สม่ำเสมอ</th>
      <th>ไม่สม่ำเสมอ</th>
      <th>ด้วยตนเอง</th>
      <th>ผู้อื่น</th>
      <th>มี</th>
      <th>ไม่มี</th>
      <th>กินยาผิด</th> <th>ฉีดยาผิด</th> <th>พ่นยาผิด</th> </tr>
    
    <tr>
      <td><input type="radio" name="med_problem_status" value="yes"></td>
      <td><input type="radio" name="med_problem_status" value="no"></td>
      
      <td><input type="checkbox" name="med_follow_correct" value="yes"></td>
      <td><input type="checkbox" name="med_follow_incorrect" value="yes"></td>
      
      <td><input type="checkbox" name="med_receive_regular" value="yes"></td>
      <td><input type="checkbox" name="med_receive_irregular" value="yes"></td>
      
      <td><input type="checkbox" name="med_admin_self" value="yes"></td>
      <td><input type="checkbox" name="med_admin_other" value="yes"></td>
      
      <td><input type="checkbox" name="med_error_has" value="yes"></td>
      <td><input type="checkbox" name="med_error_none" value="yes"></td>
      <td><input type="checkbox" name="med_error_wrong_swallow" value="yes"></td> 
      <td><input type="checkbox" name="med_error_wrong_injection" value="yes"></td> 
      <td><input type="checkbox" name="med_error_wrong_inhalation" value="yes"></td> 
    </tr>
  </table>

  <div style="margin-top:20px">
    <label style="font-weight:600; display:block; margin-bottom:4px">รายการยา:</label>
    <textarea name="med_list" placeholder="เขียนรายการยาที่ผู้ป่วยใช้อยู่และรายละเอียดเพิ่มเติม"></textarea>
  </div>
</div>

        <!-- Panel: Examination -->
        <div id="exam" class="panel">
          <div class="section-title">Examination (การตรวจร่างกาย)</div>
          
          <!-- V/S Table (compact checkboxes) -->
          <h3 style="margin:0; font-size:14px; background:#f0f0f0; padding:8px 8px 6px 8px; border-left:4px ">V/S (สัญญาณชีพ)</h3>
          <table style="width:100%; border-collapse:collapse; text-align:center; font-size:12px; margin:0 0 12px 0;">
            <thead>
              <tr>
                <th style="border:1px solid #ddd; padding:8px;">มีปัญหา</th>
                <th style="border:1px solid #ddd; padding:8px;">ไม่มีปัญหา</th>
                <th style="border:1px solid #ddd; padding:8px;">T(°C)</th>
                <th style="border:1px solid #ddd; padding:8px;">P(/min)</th>
                <th style="border:1px solid #ddd; padding:8px;">R(/min)</th>
                <th style="border:1px solid #ddd; padding:8px;">B.P.(mmHg)</th>
                <th style="border:1px solid #ddd; padding:8px;">O2sat(%)</th>
                <th style="border:1px solid #ddd; padding:8px;">อื่นๆ</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td style="border:1px solid #ddd; padding:10px;\"><input type="checkbox" name="exam_vs_problem" value="yes"></td>
                <td style="border:1px solid #ddd; padding:10px;\"><input type="checkbox" name="exam_vs_no_problem" value="yes"></td>
                <td style="border:1px solid #ddd; padding:10px;\"><input type="checkbox" name="exam_temp_flag" value="yes"></td>
                <td style="border:1px solid #ddd; padding:10px;\"><input type="checkbox" name="exam_p_flag" value="yes"></td>
                <td style="border:1px solid #ddd; padding:10px;\"><input type="checkbox" name="exam_r_flag" value="yes"></td>
                <td style="border:1px solid #ddd; padding:10px;\"><input type="checkbox" name="exam_bp_flag" value="yes"></td>
                <td style="border:1px solid #ddd; padding:10px;\"><input type="checkbox" name="exam_o2sat_flag" value="yes"></td>
                <td style="border:1px solid #ddd; padding:10px;\"><input type="checkbox" name="exam_vs_other_flag" value="yes"></td>
              </tr>
            </tbody>
          </table>

          <div style="display:flex; gap:20px; margin-bottom:20px; margin-top:0;">
    <div style="flex:1;">
        <h3 style="margin:0; font-size:14px; background:#f0f0f0; padding:8px 8px 6px 8px; border-left:4px ">แผลกดทับ</h3>
        <table style="width:100%; border-collapse:collapse; text-align:center; font-size:12px; margin:0;">
            <thead>
                <tr>
                    <th style="border:1px solid #ddd; padding:8px;">มีปัญหา</th>
                    <th style="border:1px solid #ddd; padding:8px;">ไม่มีปัญหา</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="border:1px solid #ddd; padding:6px;">
                        <input type="checkbox" name="exam_ulcer_problem" value="yes" checked>
                    </td>
                    <td style="border:1px solid #ddd; padding:6px;">
                        <input type="checkbox" name="exam_ulcer_no_problem" value="yes">
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div style="flex:1;">
        <h3 style="margin:0; font-size:14px; background:#f0f0f0; padding:8px 8px 6px 8px; border-left:4px ">ข้อติดแข็ง</h3>
        <table style="width:100%; border-collapse:collapse; text-align:center; font-size:12px; margin:0;">
            <thead>
                <tr>
                    <th style="border:1px solid #ddd; padding:8px; width:50%;">มีปัญหา</th>
                    <th style="border:1px solid #ddd; padding:8px; width:50%;">ไม่มีปัญหา</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="border:1px solid #ddd; padding:6px;">
                        <input type="checkbox" name="exam_stiff_problem" value="yes">
                    </td>
                    <td style="border:1px solid #ddd; padding:6px;">
                        <input type="checkbox" name="exam_stiff_no_problem" value="yes" checked>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

          <!-- การติดอุปกรณ์ Table -->
          <h3 style="margin:0; font-size:14px; background:#f0f0f0; padding:8px 8px 6px 8px; border-left:4px ">การติดอุปกรณ์ในร่างกาย</h3>
          <table style="width:100%; border-collapse:collapse; text-align:center; font-size:12px; margin:0 0 12px 0;">
            <thead>
              <tr>
                
                
                <th style="border:1px solid #ddd; padding:8px;">ไม่มี</th>
                <th style="border:1px solid #ddd; padding:8px;">มี</th>
                <th style="border:1px solid #ddd; padding:8px;">O2</th>
                <th style="border:1px solid #ddd; padding:8px;">NG</th>
                <th style="border:1px solid #ddd; padding:8px;">TT/Silver tube</th>
                <th style="border:1px solid #ddd; padding:8px;">Foley's cath</th>
                <th style="border:1px solid #ddd; padding:8px;">Gastrostomy</th>
              </tr>
            </thead>
            <tbody>
              <tr>
               
                <td style="border:1px solid #ddd; padding:6px;"><input type="checkbox" name="exam_device_none" value="yes"></td>
                <td style="border:1px solid #ddd; padding:6px;"><input type="checkbox" name="exam_device_has" value="yes"></td>
                <td style="border:1px solid #ddd; padding:6px;"><input type="checkbox" name="exam_device_o2" value="yes"></td>
                <td style="border:1px solid #ddd; padding:6px;"><input type="checkbox" name="exam_device_ng" value="yes"></td>
                <td style="border:1px solid #ddd; padding:6px;"><input type="checkbox" name="exam_device_tt" value="yes"></td>
                <td style="border:1px solid #ddd; padding:6px;"><input type="checkbox" name="exam_device_foley" value="yes"></td>
                <td style="border:1px solid #ddd; padding:6px;"><input type="checkbox" name="exam_device_gastrostomy" value="yes"></td>
              </tr>
            </tbody>
          </table>

          <div style="margin-top:20px">
            <label style="font-weight:600; display:block; margin-bottom:4px">หมายเหตุการตรวจ:</label>
            <textarea name="exam_notes"></textarea>
          </div>

          <div class="section-title">ADL (Activities of Daily Living) - Barthel Index</div>
<div id="barthelFormCheckboxFull">
    <table>
        <thead>
            <tr>
                <th width="35%">กิจกรรม (Activity)</th>
                <th>คะแนน 0</th>
                <th>คะแนน 5</th>
                <th>คะแนน 10</th>
                <th>คะแนน 15</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Feeding (การรับประทานอาหาร)</td>
                <td><input type="checkbox" name="adl_feeding[]" value="0" class="adl-checkbox" data-row="feeding"></td>
                <td><input type="checkbox" name="adl_feeding[]" value="5" class="adl-checkbox" data-row="feeding"></td>
                <td><input type="checkbox" name="adl_feeding[]" value="10" class="adl-checkbox" data-row="feeding"></td>
                <td></td>
            </tr>
            <tr>
                <td>Bathing (การอาบน้ำ)</td>
                <td><input type="checkbox" name="adl_bathing[]" value="0" class="adl-checkbox" data-row="bathing"></td>
                <td><input type="checkbox" name="adl_bathing[]" value="5" class="adl-checkbox" data-row="bathing"></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>Grooming (การดูแลตนเอง/แต่งหน้า)</td>
                <td><input type="checkbox" name="adl_grooming[]" value="0" class="adl-checkbox" data-row="grooming"></td>
                <td><input type="checkbox" name="adl_grooming[]" value="5" class="adl-checkbox" data-row="grooming"></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>Dressing (การแต่งตัว)</td>
                <td><input type="checkbox" name="adl_dressing[]" value="0" class="adl-checkbox" data-row="dressing"></td>
                <td><input type="checkbox" name="adl_dressing[]" value="5" class="adl-checkbox" data-row="dressing"></td>
                <td><input type="checkbox" name="adl_dressing[]" value="10" class="adl-checkbox" data-row="dressing"></td>
                <td></td>
            </tr>
            <tr>
                <td>Bowels (การกลั้นอุจจาระ)</td>
                <td><input type="checkbox" name="adl_bowels[]" value="0" class="adl-checkbox" data-row="bowels"></td>
                <td><input type="checkbox" name="adl_bowels[]" value="5" class="adl-checkbox" data-row="bowels"></td>
                <td><input type="checkbox" name="adl_bowels[]" value="10" class="adl-checkbox" data-row="bowels"></td>
                <td></td>
            </tr>
            <tr>
                <td>Bladder (การกลั้นปัสสาวะ)</td>
                <td><input type="checkbox" name="adl_bladder[]" value="0" class="adl-checkbox" data-row="bladder"></td>
                <td><input type="checkbox" name="adl_bladder[]" value="5" class="adl-checkbox" data-row="bladder"></td>
                <td><input type="checkbox" name="adl_bladder[]" value="10" class="adl-checkbox" data-row="bladder"></td>
                <td></td>
            </tr>
            <tr>
                <td>Toilet Use (การใช้ห้องน้ำ)</td>
                <td><input type="checkbox" name="adl_toilet[]" value="0" class="adl-checkbox" data-row="toilet"></td>
                <td><input type="checkbox" name="adl_toilet[]" value="5" class="adl-checkbox" data-row="toilet"></td>
                <td><input type="checkbox" name="adl_toilet[]" value="10" class="adl-checkbox" data-row="toilet"></td>
                <td></td>
            </tr>
            <tr>
                <td>Transfer (การย้ายตัว/ลุกนั่ง)</td>
                <td><input type="checkbox" name="adl_transfer[]" value="0" class="adl-checkbox" data-row="transfer"></td>
                <td><input type="checkbox" name="adl_transfer[]" value="5" class="adl-checkbox" data-row="transfer"></td>
                <td><input type="checkbox" name="adl_transfer[]" value="10" class="adl-checkbox" data-row="transfer"></td>
                <td><input type="checkbox" name="adl_transfer[]" value="15" class="adl-checkbox" data-row="transfer"></td>
            </tr>
            <tr>
                <td>Mobility (การเคลื่อนไหว/เดิน)</td>
                <td><input type="checkbox" name="adl_mobility[]" value="0" class="adl-checkbox" data-row="mobility"></td>
                <td><input type="checkbox" name="adl_mobility[]" value="5" class="adl-checkbox" data-row="mobility"></td>
                <td><input type="checkbox" name="adl_mobility[]" value="10" class="adl-checkbox" data-row="mobility"></td>
                <td><input type="checkbox" name="adl_mobility[]" value="15" class="adl-checkbox" data-row="mobility"></td>
                <td></td>
            </tr>
            <tr>
                <td>Stairs (การขึ้นลงบันได)</td>
                <td><input type="checkbox" name="adl_stairs[]" value="0" class="adl-checkbox" data-row="stairs"></td>
                <td><input type="checkbox" name="adl_stairs[]" value="5" class="adl-checkbox" data-row="stairs"></td>
                <td><input type="checkbox" name="adl_stairs[]" value="10" class="adl-checkbox" data-row="stairs"></td>
                <td></td>
            </tr>
            <tr>
                <td colspan="4" style="text-align: right; font-weight: bold;">คะแนนรวม (Total Score)</td>
                <td style="font-weight: bold; text-align: center;"><span id="totalScoreCheckboxFull">0</span> / 100</td>
            </tr>
        </tbody>
    </table>
</div>

          <!-- MRS (moved into Examination) -->
          <div class="section-title">MRS (Modified Rankin Scale)</div>
          <table>
            <tr>
              <th>คะแนน</th>
              <th>0</th>
              <th>1</th>
              <th>2</th>
              <th>3</th>
              <th>4</th>
              <th>5</th>
              <th>6</th>
            </tr>
            <tr>
              <td>เลือก</td>
              <td><input type="checkbox" name="mrs_score[]" value="0"></td>
              <td><input type="checkbox" name="mrs_score[]" value="1"></td>
              <td><input type="checkbox" name="mrs_score[]" value="2"></td>
              <td><input type="checkbox" name="mrs_score[]" value="3"></td>
              <td><input type="checkbox" name="mrs_score[]" value="4"></td>
              <td><input type="checkbox" name="mrs_score[]" value="5"></td>
              <td><input type="checkbox" name="mrs_score[]" value="6"></td>
            </tr>
          </table>
          <div style="margin-top:12px">
            <label style="font-weight:600; display:block; margin-bottom:4px">หมายเหตุ:</label>
            <textarea name="mrs_note"></textarea>
          </div>

        </div>

        <div id="safety" class="panel">
        <div class="section-title">Safety (ความปลอดภัย)</div>
    <table>
            <tr>
                <th>มีปัญหา</th>
                <th>ไม่มีปัญหา</th>
                <th>ปลอดภัยต่อการพลัดตกหกล้ม</th>
                <th>เสี่ยงต่อการพลัดตกหกล้ม</th>
            </tr>
        <tbody>
            <tr>
                <td><input type="checkbox" name="safety_fall_panel"></td>
                <td><input type="checkbox" name="safety_fall_none"></td>
                <td><input type="checkbox" name="safety_fall_safe"></td>
                <td><input type="checkbox" name="safety_fall_risk"></td>
            </tr>
            </tbody>
    </table>
</div>

        <!-- Panel: Spiritual -->
        <div id="spiritual" class="panel">
          <div class="section-title">Spiritual Health (สุขภาพจิตใจ/ศาสนา)</div>
          <table>
            <tr>
                <th>มีปัญหา</th>
                <th>ไม่มีปัญหา</th>
                <th>ความเชื่อ/เครื่องยึดเหนี่ยวจิตใจ</th>
            </tr>
        <tbody>
            <tr>
                <td><input type="checkbox" name="Spiritual_fall_panel"></td>
                <td><input type="checkbox" name="Spiritual_fall_none"></td>
                <td><input type="checkbox" name="Spiritual_fall_belief"></td>
            </tr>
            </tbody>
    </table>
        </div>

        <!-- Panel: Service -->
        <div id="service" class="panel">
          <div class="section-title">Service (บริการที่จำเป็น)</div>
          <table>
            <tr>
                <th>มีปัญหา</th>
                <th>ไม่มีปัญหา</th>
                <th>โรงพยาบาล</th>
                <th>รพ.สต./ศสม</th>
                <th>คลินิก</th>
                <th>อื่นๆ</th>
            </tr>
        <tbody>
            <tr>
                <td><input type="checkbox" name="Service _fall_panel"></td>
                <td><input type="checkbox" name="Service _fall_none"></td>
                <td><input type="checkbox" name="Service _fall_hp"></td>
                <td><input type="checkbox" name="Service _fall_brh"></td>
                <td><input type="checkbox" name="Service _fall_clinic"></td>
                <td><input type="text" name="service_other" placeholder="ระบุ" style="width:100%; padding:8px; border:1px solid #bbb; border-radius:3px; font-size:12px; font-family:Arial;"></td>
            </tr>
            </tbody>
    </table>
          </div>
        </div>

        <!-- Panel: Another (อื่นๆ) -->
        <div id="another" class="panel">
          <div class="section-title">อื่นๆ (Additional Information)</div>
          
          <div style="display: flex; flex-direction: column; gap: 20px; margin-top: 20px;">
            <!-- 1. ปัญหาความต้องการ -->
            <div style="display: flex; flex-direction: column; gap: 8px;">
              <label style="font-weight: 600; font-size: 14px; color: #333;">1. ปัญหาความต้องการ</label>
              <textarea name="another_needs" rows="4" placeholder="กรอกรายละเอียดปัญหาความต้องการของผู้ป่วย" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; font-family: Arial; resize: vertical;"></textarea>
            </div>

            <!-- 2. เป้าหมายการพยาบาล -->
            <div style="display: flex; flex-direction: column; gap: 8px;">
              <label style="font-weight: 600; font-size: 14px; color: #333;">2. เป้าหมายการพยาบาล</label>
              <textarea name="another_nursing_goal" rows="4" placeholder="กรอกรายละเอียดเป้าหมายการพยาบาล" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; font-family: Arial; resize: vertical;"></textarea>
            </div>

            <!-- 3. กิจกรรมพยาบาล -->
            <div style="display: flex; flex-direction: column; gap: 8px;">
              <label style="font-weight: 600; font-size: 14px; color: #333;">3. กิจกรรมพยาบาล</label>
              <textarea name="another_nursing_activity" rows="4" placeholder="กรอกรายละเอียดกิจกรรมพยาบาล" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; font-family: Arial; resize: vertical;"></textarea>
            </div>

            <!-- 4. การประเมินผล -->
            <div style="display: flex; flex-direction: column; gap: 8px;">
              <label style="font-weight: 600; font-size: 14px; color: #333;">4. การประเมินผล</label>
              <textarea name="another_evaluation" rows="4" placeholder="กรอกรายละเอียดการประเมินผล" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; font-family: Arial; resize: vertical;"></textarea>
            </div>

            <!-- 5. วันนัดถัดไป -->
            <div style="display: flex; flex-direction: column; gap: 8px;">
              <label style="font-weight: 600; font-size: 14px; color: #333;">5. วันนัดถัดไป</label>
              <input type="date" name="another_next_appointment" style="width: 100%; max-width: 300px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; font-family: Arial;">
            </div>

            <!-- 6. การให้คำแนะนำ -->
            <div style="display: flex; flex-direction: column; gap: 8px;">
              <label style="font-weight: 600; font-size: 14px; color: #333;">6. การให้คำแนะนำ</label>
              <textarea name="another_advice" rows="4" placeholder="กรอกรายละเอียดการให้คำแนะนำ" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; font-family: Arial; resize: vertical;"></textarea>
            </div>

            <!-- อสม/CG ที่รับผิดชอบ (เพิ่มเติม) -->
            <div style="display: flex; flex-direction: column; gap: 8px;">
              <label style="font-weight: 600; font-size: 14px; color: #333;">อสม/CG ที่รับผิดชอบ</label>
              <input type="text" name="another_cg" placeholder="กรอกชื่ออสม/CG ที่รับผิดชอบ" style="width: 100%; max-width: 500px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; font-family: Arial;">
            </div>
          </div>
        </div>

      </div>

      <div class="actions">
        <button type="submit" class="btn-save">บันทึกการประเมิน</button>
        <button type="button" class="btn-clear" onclick="clearCurrentPanel()" style="background:#ff9800; color:#fff; margin:0 6px;">ล้างการเลือก</button>
        <a href="index.php"><button type="button" class="btn-cancel">ยกเลิก</button></a>
      </div>
    </form>
  </div>

  <script>
    // Tab switching
    document.querySelectorAll('.tab').forEach(tab => {
      tab.addEventListener('click', function() {
        const panelId = this.dataset.panel;
        
        // Remove active from all tabs and panels
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
        
        // Add active to clicked tab and its panel
        this.classList.add('active');
        document.getElementById(panelId).classList.add('active');
        
        // Scroll to top
        window.scrollTo(0, 0);
      });
    });
    
    // ฟังก์ชันล้างข้อมูลใน panel ที่ active อยู่
    function clearCurrentPanel() {
        const activePanel = document.querySelector('.panel.active');
        if (!activePanel) return;
        
        // ล้าง input, textarea, checkbox, radio ใน panel ที่ active
        const inputs = activePanel.querySelectorAll('input[type="text"], input[type="date"], input[type="number"], textarea, select');
        inputs.forEach(input => {
            if (input.type !== 'hidden') {
                input.value = '';
            }
        });
        
        // ล้าง checkbox และ radio
        const checkboxes = activePanel.querySelectorAll('input[type="checkbox"], input[type="radio"]');
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        
        // ถ้าเป็น panel ADL ให้คำนวณคะแนนใหม่
        if (activePanel.id === 'adl' && typeof calculateBarthelCheckboxFull === 'function') {
            calculateBarthelCheckboxFull();
        }
    }
  </script>

  <script>
    // ฟังก์ชันคำนวณคะแนนรวมเมื่อใช้ Checkbox (เวอร์ชันเต็ม)
    function calculateBarthelCheckboxFull() {
        const form = document.getElementById('barthelFormCheckboxFull');
        const checkboxes = form.querySelectorAll('input[type="checkbox"]:checked');
        let totalScore = 0;

        checkboxes.forEach(checkbox => {
            // รวมคะแนนของทุกช่องที่ถูกเลือก
            totalScore += parseInt(checkbox.value);
        });

        // อัปเดตคะแนนรวมในช่องแสดงผล
        document.getElementById('totalScoreCheckboxFull').textContent = totalScore;
    }

    // เพิ่ม event listener ให้กับ checkbox ทุกตัว
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('barthelFormCheckboxFull');
        if (form) {
            // จัดการ checkbox ให้เลือกได้แค่ 1 ตัวต่อแถว (radio button behavior)
            form.querySelectorAll('.adl-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const row = this.dataset.row;
                    const rowCheckboxes = form.querySelectorAll(`.adl-checkbox[data-row="${row}"]`);
                    
                    // ถ้าเลือก checkbox นี้ ให้ยกเลิกการเลือก checkbox อื่นๆ ในแถวเดียวกัน
                    if (this.checked) {
                        rowCheckboxes.forEach(cb => {
                            if (cb !== this) {
                                cb.checked = false;
                            }
                        });
                    }
                    
                    // คำนวณคะแนนใหม่
                    calculateBarthelCheckboxFull();
                });
            });
            
            // คำนวณคะแนนเริ่มต้น
            calculateBarthelCheckboxFull();
        }
        
        // โหลดข้อมูลเดิมเข้าไปในฟอร์ม
        <?php if (!empty($existing_data)): ?>
        const existingData = <?php echo json_encode($existing_data, JSON_UNESCAPED_UNICODE); ?>;
        
        // ฟังก์ชัน populate ฟอร์มด้วยข้อมูลเดิม
        function populateForm(data) {
            Object.keys(data).forEach(key => {
                const value = data[key];
                
                // หา element ที่มี name ตรงกับ key
                const nameSelectors = [
                    `[name="${key}"]`,
                    `[name="${key}[]"]`
                ];
                
                nameSelectors.forEach(sel => {
                    const elements = document.querySelectorAll(sel);
                    elements.forEach(element => {
                        if (element.type === 'checkbox' || element.type === 'radio') {
                            if (Array.isArray(value)) {
                                // สำหรับ array (เช่น adl_feeding[])
                                value.forEach(val => {
                                    const matching = document.querySelectorAll(`[name="${key}"][value="${val}"], [name="${key}[]"][value="${val}"]`);
                                    matching.forEach(el => el.checked = true);
                                });
                            } else if (value === 'yes' || value === '1' || value === true || element.value === String(value)) {
                                element.checked = true;
                            }
                        } else if (element.type === 'text' || element.type === 'date' || element.tagName === 'TEXTAREA') {
                            if (element.type !== 'hidden' && element.tagName !== 'BUTTON') {
                                element.value = value || '';
                            }
                        } else if (element.tagName === 'SELECT') {
                            element.value = value || '';
                        }
                    });
                });
            });
            
            // คำนวณคะแนน Barthel Index ใหม่หลังจาก populate
            if (typeof calculateBarthelCheckboxFull === 'function') {
                calculateBarthelCheckboxFull();
            }
        }
        
        // เรียกใช้ populateForm เมื่อ DOM พร้อม
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(() => populateForm(existingData), 100);
            });
        } else {
            setTimeout(() => populateForm(existingData), 100);
        }
        <?php endif; ?>
    });
</script>
</body>
</html>
