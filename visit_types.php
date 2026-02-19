<?php
require_once __DIR__ . '/db.php';

// รับข้อมูลผู้ป่วยจาก URL parameter
$patient_hn = isset($_GET['hn']) ? htmlspecialchars($_GET['hn']) : '';
$patient_fullname = isset($_GET['fullname']) ? htmlspecialchars($_GET['fullname']) : '';
$patient_id = isset($_GET['patient_id']) ? htmlspecialchars($_GET['patient_id']) : '';
$visit_id = isset($_GET['visit_id']) ? (int)$_GET['visit_id'] : null;

// โหลดข้อมูลการเยี่ยมที่เลือก (ถ้ามี visit_id) หรือข้อมูลการเยี่ยมล่าสุด ยกเว้นเมื่อ prefill=0
$visit_data = null;
$pid_for_search = !empty($patient_id) ? (int)$patient_id : null;
$hn_for_search = !empty($patient_hn) ? $patient_hn : null;
$prefill = isset($_GET['prefill']) ? $_GET['prefill'] : null;

// ถ้ามี visit_id แสดงว่าเป็นการแก้ไข ให้โหลดข้อมูลเสมอ (ไม่สนใจ prefill)
if ($visit_id && function_exists('db_get_visit_by_id')) {
    $visit_data = db_get_visit_by_id($visit_id);
    if ($visit_data) {
        if (empty($patient_hn) && !empty($visit_data['hn'])) $patient_hn = htmlspecialchars($visit_data['hn']);
        if (empty($patient_fullname) && !empty($visit_data['fullname'])) $patient_fullname = htmlspecialchars($visit_data['fullname']);
        if (empty($patient_id) && !empty($visit_data['patient_id'])) $patient_id = htmlspecialchars($visit_data['patient_id']);
    }
} elseif ($prefill !== '0') {
    // ถ้าไม่มี visit_id และไม่ใช่ prefill=0 ให้โหลดข้อมูลการเยี่ยมล่าสุด
    if (function_exists('db_get_visits_by_patient') && ($pid_for_search || $hn_for_search)) {
        $visits = db_get_visits_by_patient($pid_for_search, $hn_for_search, 1);
        if (!empty($visits)) {
            $visit_data = $visits[0];
            if (empty($patient_hn) && !empty($visit_data['hn'])) $patient_hn = htmlspecialchars($visit_data['hn']);
            if (empty($patient_fullname) && !empty($visit_data['fullname'])) $patient_fullname = htmlspecialchars($visit_data['fullname']);
            if (empty($patient_id) && !empty($visit_data['patient_id'])) $patient_id = htmlspecialchars($visit_data['patient_id']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประเภทการเยี่ยมบ้าน</title>
    <style>
        :root {
            --primary-color: #0f766e;
            --primary-gradient: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%);
            --bg-color: #f0f4f8;
            --text-color: #374151;
            --border-color: #e5e7eb;
        }

        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background-color: var(--bg-color);
            color: var(--text-color);
            margin: 0;
            padding: 40px 20px;
            line-height: 1.5;
        }

        .form-container { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: white; 
            padding: 40px; 
            border-radius: 16px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }

        h2 { 
            text-align: center; 
            color: var(--primary-color); 
            margin-bottom: 35px;
            font-size: 2rem;
            font-weight: 700;
        }

        .back-link { margin-bottom: 25px; }
        .back-link a { 
            color: #6b7280; 
            text-decoration: none; 
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            transition: color 0.2s;
        }
        .back-link a:hover { color: var(--primary-color); }

        /* Patient Info Section */
        .patient-info { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); 
            gap: 25px; 
            margin-bottom: 35px;
            padding: 30px;
            background: #f9fafb;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .field { display: flex; flex-direction: column; }
        .field label { 
            font-size: 0.875rem; 
            font-weight: 600; 
            margin-bottom: 8px;
            color: var(--primary-color);
        }

        .field input { 
            padding: 12px; 
            border: 1px solid var(--border-color); 
            border-radius: 8px; 
            font-size: 1rem;
            transition: all 0.3s;
            background: white;
        }

        .field input[readonly] { background-color: #f3f4f6; color: #6b7280; cursor: not-allowed; }
        .field input:focus {
            outline: none;
            border-color: #14b8a6;
            box-shadow: 0 0 0 4px rgba(20, 184, 166, 0.1);
        }

        /* Visit Type Section */
        .section-title { 
            background: var(--primary-gradient);
            color: white; 
            padding: 16px 24px; 
            margin: 40px 0 20px 0; 
            border-radius: 8px; 
            font-weight: 600;
            font-size: 1.1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        /* ปรับปรุงตารางให้มีระยะห่าง */
        .table-responsive {
            overflow-x: auto;
            margin-bottom: 30px;
        }

        table { 
            width: 100%; 
            border-collapse: separate; 
            border-spacing: 0;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
        }

        th { 
            background: #f8fafc; 
            padding: 20px 10px; 
            font-size: 0.8rem; 
            color: #4b5563;
            border-bottom: 2px solid var(--border-color);
            text-align: center;
            vertical-align: middle;
            line-height: 1.4;
        }

        td { 
            padding: 25px 10px; 
            text-align: center; 
            background: white;
            border-bottom: 1px solid var(--border-color);
        }

        /* ปรับแต่ง Checkbox ให้ใหญ่ขึ้นและกดง่าย */
        input[type="checkbox"] { 
            width: 22px; 
            height: 22px; 
            cursor: pointer;
            accent-color: var(--primary-color);
        }

        /* Button Group */
        .button-group { 
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 40px; 
        }

        button, .btn-link { 
            padding: 14px 40px; 
            cursor: pointer; 
            font-size: 1rem; 
            font-weight: 600;
            border: none; 
            border-radius: 8px; 
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-save { background-color: #10b981; color: white; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3); }
        .btn-save:hover { background-color: #059669; transform: translateY(-1px); }

        .btn-cancel { background-color: #ef4444; color: white; box-shadow: 0 4px 14px rgba(239, 68, 68, 0.3); }
        .btn-cancel:hover { background-color: #dc2626; transform: translateY(-1px); }

        @media (max-width: 768px) {
            .form-container { padding: 20px; }
            th { font-size: 0.7rem; padding: 10px 5px; }
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
            <a href="javascript:history.back()">← ย้อนกลับหน้าเดิม</a>
        </div>
        
        <h2>ประเภทการเยี่ยมบ้าน</h2>
        
        <form action="save_visit_type.php" method="post">
            <div class="patient-info">
                <div class="field">
                    <label>HN (เลขประจำตัวผู้ป่วย)</label>
                    <input name="hn" type="text" value="<?php echo $patient_hn; ?>" <?php echo $patient_hn ? 'readonly' : ''; ?>>
                </div>
                <div class="field">
                    <label>ชื่อ-นามสกุล</label>
                    <input name="fullname" type="text" value="<?php echo $patient_fullname; ?>" <?php echo $patient_fullname ? 'readonly' : ''; ?>>
                </div>
                <div class="field">
                    <label>วันที่ออกเยี่ยม</label>
                    <input name="visit_date" type="date" value="<?php echo $visit_data ? htmlspecialchars($visit_data['visit_date'] ?? date('Y-m-d')) : date('Y-m-d'); ?>">
                </div>
                <div class="field">
                    <label>ชื่อผู้บันทึก/ผู้เยี่ยม</label>
                    <input name="visitor" type="text" placeholder="ระบุชื่อผู้เยี่ยม" value="<?php echo $visit_data ? htmlspecialchars($visit_data['visitor'] ?? '') : ''; ?>">
                </div>
                <?php if ($patient_id): ?>
                    <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
                <?php endif; ?>
                <?php if ($visit_id): ?>
                    <input type="hidden" name="visit_id" value="<?php echo $visit_id; ?>">
                <?php endif; ?>
            </div>

            <div class="section-title">เกณฑ์ประเภทกลุ่มเป้าหมาย (เลือกได้มากกว่า 1 ข้อ)</div>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ครอบครัว<br>ปกติ</th>
                            <th>เด็ก<br>0-5 ปี</th>
                            <th>วัยรุ่น</th>
                            <th>วัยทำงาน</th>
                            <th>ผู้สูงอายุ<br>ติดบ้าน</th>
                            <th>ผู้สูงอายุ<br>ติดสังคม</th>
                            <th>ผู้สูงอายุ<br>ติดเตียง</th>
                            <th>ผู้พิการ/<br>ด้อยโอกาส</th>
                            <th>ผู้ติดยา<br>เสพติด</th>
                            <th>หญิง<br>ตั้งครรภ์</th>
                            <th>หญิง<br>หลังคลอด</th>
                            <th>ผู้ป่วย<br>เรื้อรัง</th>
                            <th>ผู้ป่วย<br>จิตเวช</th>
                            <th>กลับจาก<br>Admit</th>
                            <th>ผู้ป่วย<br>โรคติดต่อ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <?php 
                            $visit_type_category_str = $visit_data ? trim($visit_data['visit_type_category'] ?? '') : '';
                            $visit_type_category_values = !empty($visit_type_category_str) ? array_filter(array_map('trim', explode(',', $visit_type_category_str))) : [];
                            
                            $visit_type_options = [
                                'family_normal' => 'ครอบครัว/เด็ก/ปกติ',
                                'child_0_5' => 'เด็ก 0-5 ปี',
                                'teenager' => 'วัยรุ่น',
                                'working_age' => 'วัยทำงาน',
                                'elderly_homebound' => 'ผู้สูงอายุติดบ้าน',
                                'elderly_social' => 'ผู้สูงอายุติดสังคม',
                                'elderly_bedridden' => 'ผู้สูงอายุติดเตียง',
                                'disabled' => 'ผู้พิการ/ด้อยโอกาส',
                                'drug_addict' => 'ผู้ติดยาเสพติด',
                                'pregnant' => 'หญิงตั้งครรภ์',
                                'postpartum' => 'หญิงหลังคลอด',
                                'chronic' => 'ผู้ป่วยเรื้อรัง',
                                'psychiatric' => 'ผู้ป่วยจิตเวช',
                                'discharged' => 'ผู้ป่วยกลับจาก Admit',
                                'ongoing_case' => 'ผู้ป่วยติดต่อ'
                            ];

                            foreach ($visit_type_options as $value => $label):
                                $is_checked = in_array($value, $visit_type_category_values);
                            ?>
                            <td>
                                <input type="checkbox" name="visit_type_category[]" value="<?php echo $value; ?>" <?php echo $is_checked ? 'checked' : ''; ?>>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="button-group">
                <button type="submit" class="btn-save">บันทึกข้อมูล</button>
                <a href="index.php" class="btn-link btn-cancel">ยกเลิก</a>
            </div>
        </form>
    </div>

    <script>
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
            const newUrl = window.location.pathname;
            window.history.replaceState({}, '', newUrl);
        }
    </script>
</body>
</html>