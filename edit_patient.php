<?php
require_once __DIR__ . '/db.php';

// ตั้งค่า timezone เป็นไทย
date_default_timezone_set('Asia/Bangkok');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$patient = null;

if ($id > 0) {
    $patient = db_get_patient_by_id($id);
}

if (!$patient) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขข้อมูลผู้ป่วย</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            padding: 20px; 
            background-color: #f5f7fb;
            color: #1f2937;
        }
        .form-container { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: white; 
            padding: 30px; 
            border-radius: 12px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        h2 { 
            text-align: center; 
            color: #0f766e; 
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: 600;
        }
        .section-title { 
            background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%);
            color: white; 
            padding: 15px 20px; 
            margin-top: 25px; 
            margin-bottom: 15px; 
            border-radius: 6px; 
            font-weight: 600;
            font-size: 15px;
            box-shadow: 0 2px 8px rgba(15, 118, 110, 0.2);
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 20px; 
            background: white;
        }
        th, td { 
            border: 1px solid #e5e7eb; 
            padding: 12px; 
            text-align: left; 
        }
        th { 
            background: #f9fafb; 
            font-weight: 600;
            color: #0f766e;
        }
        input[type="text"], input[type="date"], input[type="number"], select, textarea { 
            width: 95%; 
            padding: 10px; 
            border: 2px solid #e5e7eb; 
            border-radius: 6px; 
            font-size: 14px;
            transition: border-color 0.3s;
        }
        input[type="text"]:focus, input[type="date"]:focus, input[type="number"]:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #0f766e;
            box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);
        }
        textarea { 
            resize: vertical; 
            min-height: 60px; 
            font-family: inherit;
        }
        input[type="radio"] {
            margin-right: 8px;
            margin-left: 12px;
            cursor: pointer;
        }
        input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        .button-group { 
            text-align: center; 
            margin-top: 30px; 
        }
        button { 
            padding: 12px 25px; 
            margin: 0 8px; 
            cursor: pointer; 
            font-size: 16px; 
            border: none; 
            border-radius: 5px; 
            transition: all 0.3s;
        }
        .btn-save { 
            background-color: #4CAF50; 
            color: white; 
        }
        .btn-save:hover { 
            background-color: #45a049; 
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(76, 175, 80, 0.3);
        }
        .btn-cancel { 
            background-color: #f44336; 
            color: white; 
        }
        .btn-cancel:hover { 
            background-color: #da190b; 
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(244, 67, 54, 0.3);
        }
        .back-link { 
            display: inline-block; 
            margin-bottom: 20px; 
        }
        .back-link a { 
            color: #0f766e; 
            text-decoration: none; 
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 6px;
            transition: all 0.2s;
        }
        .back-link a:hover { 
            background: rgba(15,118,110,0.1);
            text-decoration: none;
        }
        .radio-group {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .radio-group label {
            cursor: pointer;
            font-weight: normal;
        }

        /* Toast Notification Styles */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            display: flex;
            flex-direction: column;
            gap: 12px;
            pointer-events: none;
        }

        .toast {
            min-width: 320px;
            max-width: 420px;
            padding: 16px 20px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 14px;
            animation: slideInRight 0.3s ease-out;
            pointer-events: auto;
            background: white;
            border-left: 4px solid;
        }

        .toast.success {
            border-left-color: #10b981;
            background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%);
        }

        .toast.error {
            border-left-color: #ef4444;
            background: linear-gradient(135deg, #ffffff 0%, #fef2f2 100%);
        }

        .toast.warning {
            border-left-color: #f59e0b;
            background: linear-gradient(135deg, #ffffff 0%, #fffbeb 100%);
        }

        .toast.info {
            border-left-color: #3b82f6;
            background: linear-gradient(135deg, #ffffff 0%, #eff6ff 100%);
        }

        .toast-icon {
            font-size: 24px;
            flex-shrink: 0;
        }

        .toast.success .toast-icon {
            color: #10b981;
        }

        .toast.error .toast-icon {
            color: #ef4444;
        }

        .toast.warning .toast-icon {
            color: #f59e0b;
        }

        .toast.info .toast-icon {
            color: #3b82f6;
        }

        .toast-content {
            flex: 1;
        }

        .toast-title {
            font-weight: 600;
            font-size: 15px;
            margin-bottom: 4px;
            color: #1f2937;
        }

        .toast-message {
            font-size: 14px;
            color: #6b7280;
            line-height: 1.4;
        }

        .toast-close {
            background: none;
            border: none;
            font-size: 20px;
            color: #9ca3af;
            cursor: pointer;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: all 0.2s;
            flex-shrink: 0;
        }

        .toast-close:hover {
            background: rgba(0, 0, 0, 0.05);
            color: #374151;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        .toast.hiding {
            animation: slideOutRight 0.3s ease-out forwards;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="back-link">
            <a href="index.php">← กลับไปหน้าหลัก</a>
        </div>
        
        <h2>แก้ไขข้อมูลผู้ป่วย</h2>
        <form action="update_patient.php" method="post">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($patient['id']); ?>">
            
            <!-- ข้อมูลพื้นฐาน -->
            <div class="section-title">ข้อมูลพื้นฐาน</div>
            <table>
                <tr>
                    <th style="width: 15%;">ลำดับ</th>
                    <th style="width: 15%;">วันที่เยี่ยม</th>
                    <th style="width: 15%;">HN</th>
                    <th style="width: 55%;">ชื่อ - สกุล</th>
                </tr>
                <tr>
                    <td><input type="text" name="no" value="<?php echo htmlspecialchars($patient['no'] ?? ''); ?>"></td>
                    <td><input type="date" name="visit_date" value="<?php echo htmlspecialchars($patient['visit_date'] ?? ''); ?>"></td>
                    <td><input type="text" name="hn" value="<?php echo htmlspecialchars($patient['hn'] ?? ''); ?>"></td>
                    <td><input type="text" name="fullname" value="<?php echo htmlspecialchars($patient['fullname'] ?? ''); ?>"></td>
                </tr>
            </table>

            <!-- อายุและเพศ -->
            <div class="section-title">อายุและเพศ</div>
            <table>
                <tr>
                    <th style="width: 50%;">อายุ (ปี)</th>
                    <th style="width: 50%;">เพศ</th>
                </tr>
                <tr>
                    <td><input type="number" name="age_y" min="0" value="<?php echo htmlspecialchars($patient['age_y'] ?? ''); ?>" placeholder="ปี"></td>
                    <td>
                        <div class="radio-group">
                            <label>
                                <input type="radio" name="gender" value="ชาย" <?php echo ($patient['gender'] ?? '') === 'ชาย' ? 'checked' : ''; ?>> ชาย
                            </label>
                            <label>
                                <input type="radio" name="gender" value="หญิง" <?php echo ($patient['gender'] ?? '') === 'หญิง' ? 'checked' : ''; ?>> หญิง
                            </label>
                        </div>
                    </td>
                </tr>
            </table>

            <!-- ที่อยู่ -->
            <div class="section-title">ที่อยู่</div>
            <table>
                <tr>
                    <th style="width: 15%;">เลขที่</th>
                    <th style="width: 15%;">หมู่</th>
                    <th style="width: 20%;">จังหวัด</th>
                    <th style="width: 20%;">อำเภอ</th>
                    <th style="width: 30%;">ตำบล</th>
                </tr>
                <tr>
                    <td>
                        <input type="text"
                               name="address_no"
                               value="<?php echo htmlspecialchars($patient['address_no'] ?? ''); ?>"
                               placeholder="เลขที่">
                    </td>
                    <td>
                        <input type="text"
                               name="address_moo"
                               value="<?php echo htmlspecialchars($patient['address_moo'] ?? ''); ?>"
                               placeholder="หมู่">
                    </td>
                    <td>
                        <select name="address_province"
                                id="address_province"
                                style="width: 100%;"
                                required>
                            <option value="">-- เลือกจังหวัด --</option>
                        </select>
                    </td>
                    <td>
                        <input type="text"
                               name="address_amphur"
                               id="address_amphur"
                               list="address_amphur_list"
                               value="<?php echo htmlspecialchars($patient['address_amphur'] ?? ''); ?>"
                               placeholder="เลือก/ค้นหาอำเภอ"
                               style="width: 100%;">
                        <datalist id="address_amphur_list"></datalist>
                    </td>
                    <td>
                        <input type="text"
                               name="address_tambon"
                               id="address_tambon"
                               list="address_tambon_list"
                               value="<?php echo htmlspecialchars($patient['address_tambon'] ?? ''); ?>"
                               placeholder="เลือก/ค้นหาตำบล"
                               style="width: 100%;">
                        <datalist id="address_tambon_list"></datalist>
                    </td>
                </tr>
            </table>

            <!-- อาศัยอยู่กับ -->
            <div class="section-title">อาศัยอยู่กับ</div>
            <table>
                <tr>
                    
                </tr>
                <tr>
                    <td><input type="text" name="live_with" value="<?php echo htmlspecialchars($patient['live_with'] ?? ''); ?>" placeholder="เช่น ครอบครัว, คนเดียว, ผู้สูงอายุ"></td>
                </tr>
            </table>

            <!-- โรคที่เป็นก่อนเยี่ยม -->
            <div class="section-title">โรคที่เป็นก่อนเยี่ยม</div>
            <table>
                <tr>
                    
                </tr>
                <tr>
                    <td><input type="text" name="pre_visit_illness" value="<?php echo htmlspecialchars($patient['pre_visit_illness'] ?? ''); ?>" placeholder="เช่น Stroke, เบาหวาน, ความดัน"></td>
                </tr>
            </table>

            <!-- เป็นผู้ป่วย IMC -->
            <div class="section-title">เป็นผู้ป่วย IMC</div>
            <table>
                <tr>
                    <th style="width: 50%;">ใช่</th>
                    <th style="width: 50%;">ไม่ใช่</th>
                </tr>
                <tr>
                    <td style="text-align: center;">
                        <input type="radio" name="is_imc" value="1" <?php echo ($patient['is_imc'] ?? '') == '1' ? 'checked' : ''; ?>>
                    </td>
                    <td style="text-align: center;">
                        <input type="radio" name="is_imc" value="0" <?php echo ($patient['is_imc'] ?? '') == '0' ? 'checked' : ''; ?>>
                    </td>
                </tr>
            </table>

            <!-- สาเหตุที่ไปเยี่ยม -->
            <div class="section-title">สาเหตุที่ไปเยี่ยม</div>
            <table>
                <tr>
                    
                </tr>
                <tr>
                    <td><textarea name="visit_reason" placeholder="เช่น ติดตามเคส, ตรวจสุขภาพ, ส่งยา" rows="3"><?php echo htmlspecialchars($patient['visit_reason'] ?? ''); ?></textarea></td>
                </tr>
            </table>

            <!-- อาการที่พบ -->
            <div class="section-title">อาการที่พบ</div>
            <table>
                <tr>
                   
                </tr>
                <tr>
                    <td><textarea name="symptoms_found" placeholder="รายละเอียดอาการที่พบ" rows="3"><?php echo htmlspecialchars($patient['symptoms_found'] ?? ''); ?></textarea></td>
                </tr>
            </table>

            <!-- อุปกรณ์ทางการแพทย์ที่ใช้ -->
            <div class="section-title">อุปกรณ์ทางการแพทย์ที่ใช้</div>
            <table>
                <tr>
                    <th style="width: 14.28%;">มี</th>
                    <th style="width: 14.28%;">ไม่มี</th>
                    <th style="width: 14.28%;">เตียงนอน</th>
                    <th style="width: 14.28%;">ที่นอนลม</th>
                    <th style="width: 14.28%;">เครื่องผลิต O2</th>
                    <th style="width: 14.28%;">อุปกรณ์ช่วยทรงตัว</th>
                    <th style="width: 14.28%;">Suction</th>
                    
                </tr>
                <tr>
                    <?php 
                    $medical_equipment_str = $patient['medical_equipment'] ?? '';
                    $medical_equipment_values = [];
                    if (!empty($medical_equipment_str)) {
                        $medical_equipment_values = array_map('trim', explode(',', $medical_equipment_str));
                        $medical_equipment_values = array_filter($medical_equipment_values);
                    }
                    ?>
                    <td style="text-align: center;">
                        <input type="checkbox" name="medical_equipment[]" value="has" <?php echo in_array('has', $medical_equipment_values) ? 'checked' : ''; ?>>
                    </td>
                    <td style="text-align: center;">
                        <input type="checkbox" name="medical_equipment[]" value="no_equipment" <?php echo in_array('no_equipment', $medical_equipment_values) ? 'checked' : ''; ?>>
                    </td>
                    <td style="text-align: center;">
                        <input type="checkbox" name="medical_equipment[]" value="air_mattress" <?php echo in_array('air_mattress', $medical_equipment_values) ? 'checked' : ''; ?>>
                    </td>
                    <td style="text-align: center;">
                        <input type="checkbox" name="medical_equipment[]" value="o2_concentrator" <?php echo in_array('o2_concentrator', $medical_equipment_values) ? 'checked' : ''; ?>>
                    </td>
                    <td style="text-align: center;">
                        <input type="checkbox" name="medical_equipment[]" value="mobility_aids" <?php echo in_array('mobility_aids', $medical_equipment_values) ? 'checked' : ''; ?>>
                    </td>
                    <td style="text-align: center;">
                        <input type="checkbox" name="medical_equipment[]" value="suction" <?php echo in_array('suction', $medical_equipment_values) ? 'checked' : ''; ?>>
                    </td>
                    <td style="text-align: center;">
                        <input type="checkbox" name="medical_equipment[]" value="hospital_bed" <?php echo in_array('hospital_bed', $medical_equipment_values) ? 'checked' : ''; ?>>
                    </td>
                </tr>
            </table>

            <!-- ปัญหาที่พบ -->
            <div class="section-title">ปัญหาที่พบ</div>
            <table>
                <tr>
                    
                </tr>
                <tr>
                    <td><textarea name="problems_found" placeholder="รายละเอียดปัญหาที่พบ" rows="3"><?php echo htmlspecialchars($patient['problems_found'] ?? ''); ?></textarea></td>
                </tr>
            </table>

            <!-- การแก้ปัญหา -->
            <div class="section-title">การแก้ปัญหา</div>
            <table>
                <tr>
                    
                </tr>
                <tr>
                    <td><textarea name="solution" placeholder="รายละเอียดการแก้ปัญหา" rows="3"><?php echo htmlspecialchars($patient['solution'] ?? ''); ?></textarea></td>
                </tr>
            </table>

            <!-- ปุ่มบันทึกและยกเลิก -->
            <div class="button-group">
                <button type="submit" class="btn-save">บันทึกการแก้ไข</button>
                <a href="index.php"><button type="button" class="btn-cancel">ยกเลิก</button></a>
            </div>
        </form>
    </div>

    <script>
        // ค่าเริ่มต้นจากฐานข้อมูล
        const initialProvince = <?php echo json_encode($patient['address_province'] ?? ''); ?>;
        const initialAmphur   = <?php echo json_encode($patient['address_amphur'] ?? ''); ?>;
        const initialTambon   = <?php echo json_encode($patient['address_tambon'] ?? ''); ?>;

        // อ้างอิง element เหมือนหน้า add.php
        const provinceSelect = document.getElementById('address_province');
        const amphurInput = document.getElementById('address_amphur');
        const tambonInput = document.getElementById('address_tambon');
        const amphurList = document.getElementById('address_amphur_list');
        const tambonList = document.getElementById('address_tambon_list');
        let amphurOptions = [];
        let tambonOptions = [];

        // ข้อมูลบุรีรัมย์จากไฟล์ JSON
        let buriramData = null;

        function setOptions(datalistEl, optionsArr) {
            datalistEl.innerHTML = '';
            optionsArr.forEach(item => {
                const opt = document.createElement('option');
                opt.value = item;
                datalistEl.appendChild(opt);
            });
        }

        async function loadBuriramData() {
            if (buriramData) return;
            try {
                const res = await fetch('buriram_addresses.json');
                buriramData = await res.json();
            } catch (e) {
                console.error('ไม่สามารถโหลด buriram_addresses.json ได้', e);
            }
        }

        function populateBuriramAmphurs() {
            if (!buriramData || !buriramData.amphurs) return;
            amphurOptions = Object.keys(buriramData.amphurs).sort();
            setOptions(amphurList, amphurOptions);
            tambonOptions = [];
            setOptions(tambonList, tambonOptions);
        }

        function populateBuriramTambonsFor(amphur) {
            if (!buriramData || !buriramData.amphurs || !amphur) return;
            tambonOptions = (buriramData.amphurs[amphur] || []).slice().sort();
            setOptions(tambonList, tambonOptions);
        }

        async function loadProvinces() {
            try {
                const response = await fetch('get_address_data.php?action=provinces');
                const provinces = await response.json();
                provinces.forEach(province => {
                    const option = document.createElement('option');
                    option.value = province;
                    option.textContent = province;
                    provinceSelect.appendChild(option);
                });
            } catch (error) {
                console.error('Error loading provinces:', error);
                loadBasicProvinces();
            }
        }

        function loadBasicProvinces() {
            const basicProvinces = [
                'กรุงเทพมหานคร', 'กระบี่', 'กาญจนบุรี', 'กาฬสินธุ์', 'กำแพงเพชร', 'ขอนแก่น', 'จันทบุรี', 'ฉะเชิงเทรา',
                'ชลบุรี', 'ชัยนาท', 'ชัยภูมิ', 'ชุมพร', 'เชียงราย', 'เชียงใหม่', 'ตรัง', 'ตราด',
                'ตาก', 'นครนายก', 'นครปฐม', 'นครพนม', 'นครราชสีมา', 'นครศรีธรรมราช', 'นครสวรรค์', 'นนทบุรี',
                'บึงกาฬ', 'บุรีรัมย์', 'ปทุมธานี', 'ประจวบคีรีขันธ์', 'ปราจีนบุรี', 'ปัตตานี', 'พระนครศรีอยุธยา', 'พังงา',
                'พัทลุง', 'พิจิตร', 'พิษณุโลก', 'เพชรบุรี', 'เพชรบูรณ์', 'แพร่', 'ภูเก็ต', 'มหาสารคาม',
                'มุกดาหาร', 'แม่ฮ่องสอน', 'ยะลา', 'ยโสธร', 'ร้อยเอ็ด', 'ระนอง', 'ระยอง', 'ราชบุรี',
                'ลพบุรี', 'ลำปาง', 'ลำพูน', 'เลย', 'ศรีสะเกษ', 'สกลนคร', 'สงขลา', 'สตูล',
                'สมุทรปราการ', 'สมุทรสงคราม', 'สมุทรสาคร', 'สระแก้ว', 'สระบุรี', 'สิงห์บุรี', 'สุโขทัย', 'สุพรรณบุรี',
                'สุราษฎร์ธานี', 'สุรินทร์', 'หนองคาย', 'หนองบัวลำภู', 'อ่างทอง', 'อำนาจเจริญ', 'อุดรธานี', 'อุตรดิตถ์',
                'อุทัยธานี', 'อุบลราชธานี'
            ];
            basicProvinces.forEach(province => {
                const option = document.createElement('option');
                option.value = province;
                option.textContent = province;
                provinceSelect.appendChild(option);
            });
        }

        async function loadAmphurs(province) {
            amphurOptions = [];
            tambonOptions = [];
            setOptions(amphurList, amphurOptions);
            setOptions(tambonList, tambonOptions);

            if (!province) return;

            try {
                const response = await fetch('get_address_data.php?action=amphurs&province=' + encodeURIComponent(province));
                const amphurs = await response.json();
                if (amphurs.length > 0) {
                    amphurOptions = amphurs;
                } else {
                    amphurOptions = loadBasicAmphurs(province);
                }
            } catch (error) {
                console.error('Error loading amphurs:', error);
                amphurOptions = loadBasicAmphurs(province);
            }
            setOptions(amphurList, amphurOptions);
        }

        function loadBasicAmphurs(province) {
            const basicAmphurs = {
                'กรุงเทพมหานคร': ['เขตพระนคร', 'เขตดุสิต', 'เขตหนองจอก', 'เขตบางรัก', 'เขตบางเขน', 'เขตบางกะปิ', 'เขตปทุมวัน', 'เขตป้อมปราบศัตรูพ่าย', 'เขตพระโขนง', 'เขตมีนบุรี', 'เขตลาดกระบัง', 'เขตยานนาวา', 'เขตสัมพันธวงศ์', 'เขตพญาไท', 'เขตธนบุรี', 'เขตบางกอกใหญ่', 'เขตห้วยขวาง', 'เขตคลองสาน', 'เขตตลิ่งชัน', 'เขตบางกอกน้อย', 'เขตบางซื่อ', 'เขตจตุจักร', 'เขตบางคอแหลม', 'เขตประเวศ', 'เขตคลองเตย', 'เขตสวนหลวง', 'เขตจอมทอง', 'เขตดอนเมือง', 'เขตราชเทวี', 'เขตลาดพร้าว', 'เขตวัฒนา', 'เขตบางแค', 'เขตหลักสี่', 'เขตสายไหม', 'เขตคันนายาว', 'เขตสะพานสูง', 'เขตวังทองหลาง', 'เขตคลองสามวา', 'เขตบางนา', 'เขตทวีวัฒนา', 'เขตทุ่งครุ', 'เขตบางบอน'],
                'นครราชสีมา': ['เมืองนครราชสีมา', 'โชคชัย', 'เฉลิมพระเกียรติ', 'ครบุรี', 'เสิงสาง', 'คง', 'บ้านเหลื่อม', 'จักราช', 'ห้วยแถลง', 'ชุมพวง', 'โนนสูง', 'โนนแดง', 'โนนไทย', 'โนนสมบูรณ์', 'โนนคูณ', 'โนนลาน', 'โนนสำราญ', 'โนนแดง', 'โนนไทย', 'โนนสมบูรณ์', 'โนนคูณ', 'โนนลาน', 'โนนสำราญ']
            };
            return basicAmphurs[province] || [];
        }

        async function loadTambons(province, amphur) {
            tambonOptions = [];
            setOptions(tambonList, tambonOptions);
            if (!province || !amphur) return;

            try {
                const response = await fetch(
                    'get_address_data.php?action=tambons&province=' +
                    encodeURIComponent(province) +
                    '&amphur=' + encodeURIComponent(amphur)
                );
                const tambons = await response.json();
                if (tambons.length > 0) {
                    tambonOptions = tambons;
                } else {
                    tambonOptions = loadBasicTambons(province, amphur);
                }
            } catch (error) {
                console.error('Error loading tambons:', error);
                tambonOptions = loadBasicTambons(province, amphur);
            }
            setOptions(tambonList, tambonOptions);
        }

        function loadBasicTambons(province, amphur) {
            const basicTambons = {
                'กรุงเทพมหานคร': {
                    'เขตพระนคร': ['พระบรมมหาราชวัง', 'วังบูรพาภิรมย์', 'วัดราชบพิธ', 'สำราญราษฎร์', 'ศาลเจ้าพ่อเสือ', 'เสาชิงช้า', 'บวรนิเวศ', 'ตลาดยอด', 'ชนะสงคราม', 'บ้านพานถม', 'บางขุนพรหม', 'วัดสามพระยา'],
                    'เขตดุสิต': ['ดุสิต', 'วชิรพยาบาล', 'สวนจิตรลดา', 'ถนนนครไชยศรี', 'อารีย์', 'สามเสนใน']
                },
                'นครราชสีมา': {
                    'เมืองนครราชสีมา': ['ในเมือง', 'โพธิ์', 'ตลาด', 'หัวทะเล', 'จอหอ', 'โคกกรวด', 'ด่านขุนทด', 'หนองบัว', 'บ้านเกาะ', 'บ้านใหม่', 'ปากช่อง', 'วังไทร', 'หนองน้ำแดง', 'หนองไผ่', 'หนองแซง']
                }
            };
            return basicTambons[province]?.[amphur] || [];
        }

        // เปลี่ยนจังหวัด
        provinceSelect.addEventListener('change', async function () {
            tambonOptions = [];
            amphurOptions = [];
            tambonInput.value = '';
            amphurInput.value = '';
            setOptions(tambonList, tambonOptions);
            setOptions(amphurList, amphurOptions);

            if (this.value === 'บุรีรัมย์') {
                await loadBuriramData();
                populateBuriramAmphurs();
            } else {
                loadAmphurs(this.value);
            }
        });

        // เปลี่ยนอำเภอ
        amphurInput.addEventListener('change', function () {
            const amphur = this.value.trim();
            if (!amphur) {
                tambonOptions = [];
                tambonInput.value = '';
                setOptions(tambonList, tambonOptions);
                return;
            }
            if (provinceSelect.value === 'บุรีรัมย์') {
                populateBuriramTambonsFor(amphur);
            } else {
                loadTambons(provinceSelect.value, amphur);
            }
        });

        // โหลดค่าเริ่มต้นเมื่อเปิดหน้าแก้ไข
        document.addEventListener('DOMContentLoaded', async function () {
            await loadProvinces();

            if (initialProvince) {
                provinceSelect.value = initialProvince;

                if (initialProvince === 'บุรีรัมย์') {
                    await loadBuriramData();
                    populateBuriramAmphurs();
                    if (initialAmphur) {
                        amphurInput.value = initialAmphur;
                        populateBuriramTambonsFor(initialAmphur);
                        if (initialTambon) {
                            tambonInput.value = initialTambon;
                        }
                    }
                } else {
                    await loadAmphurs(initialProvince);
                    if (initialAmphur) {
                        amphurInput.value = initialAmphur;
                        await loadTambons(initialProvince, initialAmphur);
                        if (initialTambon) {
                            tambonInput.value = initialTambon;
                        }
                    }
                }
            }
        });

        // Toast Notification System
        function showToast(message, type = 'success', title = '') {
            const container = document.getElementById('toast-container') || createToastContainer();

            const toast = document.createElement('div');
            toast.className = `toast ${type}`;

            const icons = {
                success: '✅',
                error: '❌',
                warning: '⚠️',
                info: 'ℹ️'
            };

            const defaultTitles = {
                success: 'สำเร็จ',
                error: 'เกิดข้อผิดพลาด',
                warning: 'คำเตือน',
                info: 'ข้อมูล'
            };

            toast.innerHTML = `
                <span class="toast-icon">${icons[type] || icons.info}</span>
                <div class="toast-content">
                    ${title ? `<div class="toast-title">${title}</div>` : ''}
                    <div class="toast-message">${message}</div>
                </div>
                <button class="toast-close" onclick="this.parentElement.remove()">×</button>
            `;

            container.appendChild(toast);

            // Auto remove after 5 seconds
            setTimeout(() => {
                toast.classList.add('hiding');
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }

        function createToastContainer() {
            const container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container';
            document.body.appendChild(container);
            return container;
        }

        // Initialize toast container
        if (!document.getElementById('toast-container')) {
            createToastContainer();
        }

        // Check for URL parameters to show toast
        const urlParams = new URLSearchParams(window.location.search);
        const toastType = urlParams.get('toast');
        const toastMsg = urlParams.get('msg');

        if (toastType && toastMsg) {
            const decodedMsg = decodeURIComponent(toastMsg);
            showToast(decodedMsg, toastType);
            // Clean URL
            const newUrl = window.location.pathname + (urlParams.get('id') ? '?id=' + urlParams.get('id') : '');
            window.history.replaceState({}, '', newUrl);
        }
    </script>
</body>
</html>
