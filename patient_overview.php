<?php
require_once __DIR__ . '/db.php';

// ตั้งค่า timezone เป็นไทย
date_default_timezone_set('Asia/Bangkok');

// รับ key จาก URL
$patient_id = isset($_GET['patient_id']) ? (int) $_GET['patient_id'] : null;
$hn_param = isset($_GET['hn']) ? trim($_GET['hn']) : '';

$patient = null;

if ($patient_id) {
    $patient = db_get_patient_by_id($patient_id);
} elseif ($hn_param && function_exists('db_find_patient_by_hn')) {
    $pid = db_find_patient_by_hn($hn_param);
    if ($pid) {
        $patient_id = (int) $pid;
        $patient = db_get_patient_by_id($patient_id);
    }
}

// ถ้ายังไม่เจอ ลองดึงจาก hn อย่างเดียว (กรณีไม่มีในตาราง patients)
if (!$patient && $hn_param) {
    $patient = [
        'hn' => $hn_param,
        'fullname' => isset($_GET['fullname']) ? $_GET['fullname'] : '',
    ];
}

// เตรียม key สำหรับค้นใน visits / assessments
$hn_for_search = $patient['hn'] ?? $hn_param ?? '';
$pid_for_search = $patient_id ?: null;

$visits = function_exists('db_get_visits_by_patient')
    ? db_get_visits_by_patient($pid_for_search, $hn_for_search, 1)
    : [];

$assessments = function_exists('db_get_assessments_by_patient')
    ? db_get_assessments_by_patient($pid_for_search, $hn_for_search, 1000)
    : [];

// คำนวณสรุป
$total_visits = count($visits);
$total_assess = count($assessments);
$last_visit_date = $total_visits ? ($visits[0]['visit_date'] ?? '') : '';
$last_assess_date = $total_assess ? ($assessments[0]['assess_date'] ?? '') : '';
$last_score = $total_assess ? ($assessments[0]['score'] ?? null) : null;
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อมูลสรุปผู้ป่วย</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* =========================================
           1. CSS VARIABLES & THEME CONFIG
           ========================================= */
        :root {
            /* Colors: Brand */
            --primary: #0f766e;
            --primary-hover: rgba(15, 118, 110, 0.1);
            --primary-light: rgba(15, 118, 110, 0.04);
            --primary-border: rgba(15, 118, 110, 0.2);

            /* Colors: Background & Text */
            --bg-body: #f5f7fb;
            --bg-card: #ffffff;
            --text-main: #1f2937;
            --text-secondary: #6b7280;
            --border-color: #e5e7eb;

            /* Spacing & Sizing */
            --container-width: 1200px;
            --radius-card: 14px;
            --radius-sm: 8px;

            /* Status Colors */
            --success-bg: rgba(16, 185, 129, 0.2);
            --success-text: #059669;
            --warning-bg: rgba(245, 158, 11, 0.2);
            --warning-text: #d97706;
            --danger-bg: rgba(239, 68, 68, 0.2);
            --danger-text: #dc2626;
        }


        /* =========================================
           2. BASE STYLES
           ========================================= */
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: var(--bg-body);
            color: var(--text-main);
            margin: 0;
            padding: 20px;
            -webkit-font-smoothing: antialiased;
        }

        a {
            text-decoration: none;
        }

        /* =========================================
           3. LAYOUT & STRUCTURE
      

        /* Grid Layouts */
        .top-section {
            display: grid;
            /* 1. แบ่งเป็น 3 ช่อง เท่าๆ กัน (1fr คือ 1 ส่วน) */
            grid-template-columns: 1fr 1fr 1fr;

            /* 2. ระยะห่างระหว่างกล่อง */
            gap: 20px;

            /* 3. จัดกึ่งกลางหน้าจอ และจำกัดความกว้างไม่ให้ยืดจนน่าเกลียด */
            width: 100%;
            max-width: 1400px;
            /* ปรับตัวเลขนี้ได้ตามความกว้างที่ชอบ */
            margin: 0 auto;
            /* คำสั่งนี้จะดันซ้ายขวาให้เท่ากัน (จัดกึ่งกลาง) */

            /* 4. ดึงความสูงให้เท่ากันทุกกล่อง (อิงตามกล่องที่ยาวสุด) */
            align-items: stretch;
            margin-bottom: 0;
            flex-grow: 0;
            /* ไม่ต้องขยายตัวจนกินพื้นที่ */
        }

        .top-section .card {
            height: 100%;
            display: flex;
            flex-direction: column;
        }


        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }

        .data-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 12px;
        }

        /* Margins helpers */
        .header-section {
            margin-bottom: 30px;
        }

        /* =========================================
           4. COMPONENTS: TYPOGRAPHY & HEADER
           ========================================= */
        h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
            margin: 0 0 30px 0;
            white-space: nowrap;
        }

        h1::before {
            content: "👤";
            font-size: 32px;
            margin-right: 10px;
        }

        .back-link {
            margin-bottom: 20px;
        }

        .back-link a {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 6px;
            color: var(--primary);
            font-size: 14px;
            transition: all 0.2s;
        }

        .back-link a:hover {
            background: var(--primary-hover);
        }

        /* =========================================
           5. COMPONENTS: CARDS & CONTAINERS
           ========================================= */
        /* General Card */
        .card {
            /* สั่งให้ความสูงปรับอัตโนมัติตามเนื้อหาข้างใน */
            height: auto !important;

            /* คงค่าเดิมอื่นๆ ไว้ */
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            /* ... */
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.1);
        }

        .card-title {
            display: flex;
            align-items: center;
            gap: 8px;

            font-size: 18px;
            font-weight: 700;
            color: var(--primary);

            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--primary-border);
        }

        .card-title::before {
            content: "📋";
            font-size: 20px;
        }

        /* Section Wrapper (Big container) */
        .section-wrapper {
            display: flex;
            flex-direction: column;

            background: var(--bg-card);
            border: 2px solid var(--border-color);
            border-radius: var(--radius-card);
            padding: 28px 32px;
            box-shadow: 0 3px 10px rgba(15, 23, 42, 0.06);
        }

        .bottom-section .section-wrapper {
            min-height: 500px;
        }

        .bottom-section {
            /* 1. สั่งให้กว้างเต็มที่ แต่ไม่เกิน 1400px (เท่ากับค่าของ .top-section) */
            width: 100%;
            max-width: 1400px;

            /* 2. จัดกึ่งกลางหน้าจอ (เพื่อให้ตรงกับชุดข้างบน) */
            margin-left: auto;
            margin-right: auto;

            /* (ค่าเดิมอื่นๆ คงไว้) */
            margin-top: 0;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 12px;

            font-size: 22px;
            font-weight: 700;
            color: var(--primary);

            background-color: #0f766e !important; /* สีทึบ */
            background-image: none !important;    /* ตัด gradient เดิม */
            color: white;
            margin-bottom: 24px;
            padding-bottom: 14px;
            font-size: 20px;
        }

      

        /* =========================================
           6. COMPONENTS: DATA DISPLAY
           ========================================= */
        /* Info Item (Green Box) */
        .info-item {
            background: var(--primary-light);
            padding: 16px;
            border-radius: 10px;
            border-left: 4px solid var(--primary);
        }

        .info-item label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-item span {
            font-size: 15px;
            font-weight: 500;
            color: #111827;
        }

        /* Assessment Card & Items */
        .assessment-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f0fdfa;
        }

        .section-header h5 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
        }

        /* Data List Item */
        .data-item {
            display: flex;
            justify-content: space-between;
            align-items: center;

            background: #f8fafc;
            border: 1px solid #f1f5f9;
            border-radius: var(--radius-sm);
            padding: 12px 18px;
            min-height: 48px;
            transition: all 0.2s;
        }

        .data-item:hover {
            background: #f0fdfa;
            border-color: var(--primary);
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .data-label {
            font-size: 14px;
            font-weight: 500;
            color: #64748b;
        }

        .data-value {
            font-size: 14px;
            font-weight: 600;
            color: #111827;
            text-align: right;
            word-break: break-word;
            max-width: 60%;
        }

        /* Mini Stats */
        .mini-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-top: 4px;
        }

        .mini-stat {
            background: linear-gradient(135deg, rgba(15, 118, 110, 0.08) 0%, rgba(20, 184, 166, 0.05) 100%);
            border: 2px solid var(--primary-border);
            border-radius: 12px;
            padding: 18px 20px;
            transition: all 0.2s;
        }

        .mini-stat:hover {
            background: linear-gradient(135deg, rgba(15, 118, 110, 0.12) 0%, rgba(20, 184, 166, 0.08) 100%);
            transform: translateY(-2px);
        }

        .mini-label {
            font-size: 12px;
            color: var(--text-secondary);
            margin-bottom: 6px;
            font-weight: 500;
        }

        .mini-value {
            font-size: 22px;
            font-weight: 700;
            color: var(--primary);
        }

        /* =========================================
           7. ACTIVITY FEED SECTION (Scrollable)
           ========================================= */
        .activity-cards {
            display: grid;
            gap: 20px;
            margin-top: 0;
            flex: 1;
            overflow-y: auto;
            max-height: 600px;
            padding-right: 8px;
        }

        /* Custom Scrollbar */
        .activity-cards::-webkit-scrollbar {
            width: 8px;
        }

        .activity-cards::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .activity-cards::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 10px;
        }

        .activity-cards::-webkit-scrollbar-thumb:hover {
            background: #14b8a6;
        }

        .activity-card {
            background: var(--bg-card);
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 22px 24px;
            border-left: 5px solid var(--primary);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: all 0.2s;
        }

        .activity-card:hover {
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.1);
            transform: translateX(5px);
            border-left-color: #14b8a6;
        }

        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
            padding-bottom: 14px;
            border-bottom: 2px solid var(--border-color);
        }

        .activity-date {
            font-size: 15px;
            font-weight: 700;
            color: var(--primary);
        }

        .activity-time {
            font-size: 13px;
            color: var(--text-secondary);
        }

        .activity-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 18px;
        }

        .activity-detail-item {
            display: flex;
            flex-direction: column;
            background: rgba(15, 118, 110, 0.02);
            padding: 12px 14px;
            border-radius: 8px;
            border: 1px solid rgba(15, 118, 110, 0.1);
        }

        .activity-detail-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .activity-detail-value {
            font-size: 15px;
            font-weight: 500;
            color: #111827;
        }

        /* =========================================
           8. HELPERS & UTILITIES
           ========================================= */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-secondary);
            font-size: 14px;
            background: #f9fafb;
            border-radius: 12px;
            border: 2px dashed var(--border-color);
        }

        .empty-state::before {
            content: "📭";
            font-size: 48px;
            display: block;
            margin-bottom: 12px;
        }

        .score-highlight {
            color: var(--primary) !important;
            font-size: 16px !important;
            font-weight: 800 !important;
        }

        .score-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 16px;
        }

        .score-badge.high {
            background: var(--success-bg);
            color: var(--success-text);
        }

        .score-badge.medium {
            background: var(--warning-bg);
            color: var(--warning-text);
        }

        .score-badge.low {
            background: var(--danger-bg);
            color: var(--danger-text);
        }

        .container {
            /* 1. สำคัญที่สุด: ยกเลิกการจองความสูงเต็มจอ */
            min-height: auto !important;
            height: auto !important;

            /* 2. ถ้ามันเป็น Flex ให้สั่งไม่ให้ขยายตัวกินที่เปล่าๆ */
            flex-grow: 0;

            /* 3. จัดระยะห่างด้านล่างนิดหน่อย ก่อนจะถึงส่วนต่อไป */
            margin-bottom: 20px;
            padding-bottom: 0;
        }

        /* แถม: แก้ตัวแม่ (.dashboard-container) ให้เรียงของชิดบน */
        .dashboard-container {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            /* เรียงจากบนลงล่าง ไม่กระจายห่างกัน */
        }

        /* =========================================
           9. RESPONSIVE MEDIA QUERIES
           ========================================= */
        @media (max-width: 1024px) {

            .top-section,
            .assessment-grid {
                grid-template-columns: 1fr;
            }

            .container {
                padding: 10px;
            }
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

        .toast.success .toast-icon { color: #10b981; }
        .toast.error .toast-icon { color: #ef4444; }
        .toast.warning .toast-icon { color: #f59e0b; }
        .toast.info .toast-icon { color: #3b82f6; }

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
    <div class="dashboard-container">
        <div class="header-section">
            <h1>📊 ข้อมูลสรุปผู้ป่วย</h1>
        </div>

        <div class="container">
            <?php if (!$patient): ?>
                <div class="alert alert-warning">ไม่พบข้อมูลผู้ป่วย</div>
            <?php else: ?>

                <div class="top-section">
                    <div class="card" style="height: fit-content; max-height: 600px; overflow-y: auto;">
                        <h3 style="margin-bottom: 15px; font-size: 1.1rem; color: #0f766e;">👤 ข้อมูลทั่วไป</h3>
                        <div class="card-body">
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>🆔 HN</label>
                                    <span><?php echo htmlspecialchars($patient['hn'] ?? '-'); ?></span>
                                </div>
                                <div class="info-item">
                                    <label>📝 ชื่อ-สกุล</label>
                                    <span><?php echo htmlspecialchars($patient['fullname'] ?? '-'); ?></span>
                                </div>
                                <div class="info-item">
                                    <label>⚧️ เพศ</label>
                                    <span><?php echo htmlspecialchars($patient['gender'] ?? '-'); ?></span>
                                </div>
                                <div class="info-item">
                                    <label>👨‍👩‍👧‍👦 อาศัยอยู่กับ</label>
                                    <span><?php echo htmlspecialchars($patient['live_with'] ?? '-'); ?></span>
                                </div>
                                <div class="info-item" style="grid-column: 1 / -1;">
                                    <label>🏠 ที่อยู่</label>
                                    <span>
                                        <?php
                                        $address_parts = array_filter([
                                            $patient['address_no'] ?? '',
                                            $patient['address_moo'] ?? '',
                                            $patient['address_tambon'] ?? '',
                                            $patient['address_amphur'] ?? '',
                                            $patient['address_province'] ?? ''
                                        ]);
                                        echo !empty($address_parts) ? htmlspecialchars(implode(' ', $address_parts)) : '-';
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card" style="height: fit-content; max-height: 600px; overflow-y: auto;">
                        <h3 style="margin-bottom: 15px; font-size: 1.1rem; color: #0f766e;">📈 ภาพรวมกิจกรรม</h3>
                        <div class="card-body">
                            <div class="mini-stats">
                                <div class="mini-stat">
                                    <div class="mini-label">เยี่ยมบ้าน (ครั้ง)</div>
                                    <div class="mini-value"><?php echo number_format($total_visits); ?></div>
                                </div>
                                <div class="mini-stat">
                                    <div class="mini-label">ประเมิน (ครั้ง)</div>
                                    <div class="mini-value"><?php echo number_format($total_assess); ?></div>
                                </div>
                                <div class="mini-stat">
                                    <div class="mini-label">📅 เยี่ยมบ้านล่าสุด</div>
                                    <div class="mini-value" style="font-size: 18px;">
                                        <?php
                                        if ($last_visit_date) {
                                            $d = new DateTime($last_visit_date);
                                            echo $d->format('d/m/Y');
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div class="mini-stat">
                                    <div class="mini-label">⭐ คะแนน Barthel ล่าสุด</div>
                                    <div class="mini-value">
                                        <?php
                                        if ($last_score !== null) {
                                            $score_class = '';
                                            if ($last_score >= 80)
                                                $score_class = 'high';
                                            elseif ($last_score >= 50)
                                                $score_class = 'medium';
                                            else
                                                $score_class = 'low';

                                            echo '<span class="score-badge ' . $score_class . '" style="font-size: 18px; padding: 4px 10px;">' . number_format($last_score, 2) . '</span>';
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card" style="height: fit-content; max-height: 600px; overflow-y: auto;">
                        <h3 style="margin-bottom: 15px; font-size: 1.1rem; color: #0f766e;">🏠 ประเภทการเยี่ยมบ้าน
                        </h3>

                        <?php
                        // --- แก้ไขตรงนี้: เพิ่มการเช็คว่ามีฟังก์ชันนี้หรือยัง เพื่อกัน Error ---
                        if (!function_exists('convertToLabels')) {
                            function convertToLabels($value, $map)
                            {
                                if (empty($value))
                                    return '';
                                $values = array_map('trim', explode(',', $value));
                                $labels = [];
                                foreach ($values as $val) {
                                    if (isset($map[$val])) {
                                        $labels[] = $map[$val];
                                    } else {
                                        $labels[] = $val;
                                    }
                                }
                                return implode(', ', $labels);
                            }
                        }
                        // -----------------------------------------------------------
                    
                        // ตัวแปร Maps ต่างๆ (ต้องประกาศไว้เหมือนเดิม)
                        $purpose_map = [
                            'care' => 'ดูแล',
                            'health_check' => 'ตรวจสุขภาพ',
                            'medicine' => 'ส่งยา/เยี่ยมอื่น ๆ',
                            'follow_up' => 'Follow up',
                            'education' => 'สอนอื่น'
                        ];
                        $facilities_map = [
                            'oxygen' => 'ออกซิเจน',
                            'ventilator' => 'Ventilator',
                            'feeding_tube' => 'ท่อให้อาหาร',
                            'catheter' => 'สายสวน',
                            'tracheostomy' => 'Tracheostomy',
                            'colostomy' => 'Colostomy',
                            'ileostomy' => 'Ileostomy',
                            'other' => 'อื่น ๆ'
                        ];
                        $referral_map = [
                            'doctor' => 'ส่งแพทย์',
                            'nurse' => 'ส่งพยาบาล',
                            'hospital' => 'ส่งโรงพยาบาล',
                            'social_work' => 'Social Work',
                            'admin' => 'Admin'
                        ];
                        $visit_type_category_map = [
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

                        if (isset($total_visits) && $total_visits):
                            ?>
                            <div class="activity-cards">
                                <?php foreach ($visits as $v): ?>
                                    <div class="activity-card"
                                        style="border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 15px;">
                                        <div class="activity-date" style="font-weight: bold; color: #0f766e;">
                                            📅
                                            <?php echo ($v['visit_date']) ? (new DateTime($v['visit_date']))->format('d/m/Y') : '-'; ?>
                                        </div>

                                        <?php if (!empty($v['visit_time'])):
                                            $time_display = $v['visit_time'];
                                            if (preg_match('/^(\d{2}):(\d{2}):(\d{2})$/', $time_display, $matches)) {
                                                $time_display = $matches[1] . ':' . $matches[2];
                                            }
                                            ?>
                                            <div class="activity-time" style="font-size: 0.9rem; color: #666;">🕐 เวลา
                                                <?php echo htmlspecialchars($time_display); ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($v['visitor'])): ?>
                                            <div style="margin-top: 5px;"><small><strong>ผู้เยี่ยม:</strong> 👤
                                                    <?php echo htmlspecialchars($v['visitor']); ?></small></div>
                                        <?php endif; ?>

                                        <?php if (!empty($v['visit_type_category'])): ?>
                                            <div
                                                style="margin-top: 8px; padding: 6px; background: rgba(15,118,110,0.05); border-radius: 4px;">
                                                <small><strong>ประเภท:</strong>
                                                    <?php echo convertToLabels($v['visit_type_category'], $visit_type_category_map); ?></small>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($v['purpose'])): ?>
                                            <div style="margin-top: 5px;">
                                                <small><strong>🎯 วัตถุประสงค์:</strong>
                                                    <?php echo convertToLabels($v['purpose'], $purpose_map); ?></small>
                                            </div>
                                        <?php endif; ?>

                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div style="text-align: center; color: #999; padding: 20px;">ไม่มีข้อมูล</div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php endif; ?>
        </div>
    </div>
    </div>
    <!-- End top-section -->

    <!-- ส่วนล่าง: ประวัติการประเมิน (กล่องใหญ่ที่มี 2 กล่องย่อย) -->
    <div class="bottom-section">
        <div class="section-wrapper">
            <div class="section-title">📋 ประวัติการประเมิน INHOMESSS (ทั้งหมด <?php echo $total_assess; ?> รายการ)
            </div>
            <?php if ($total_assess): ?>
                <?php 
                // ใช้ข้อมูลจากการประเมินล่าสุดเท่านั้น
                $latest_assessment = $assessments[0] ?? null;
                if ($latest_assessment && !empty($latest_assessment['data']) && is_array($latest_assessment['data'])):
                    $assessment_data = $latest_assessment['data'];
                ?>
                <div class="assessment-grid">
                    <?php
                    // แสดงข้อมูลการประเมินแต่ละหมวด (ถึงแค่ MRS)
                    $sections = [
                        'Immobility' => ['imm_', 'การเคลื่อนไหว'],
                        'Nutrition' => ['nut_', 'โภชนาการ'],
                        'Home Environment' => ['home_', 'สภาพแวดล้อมบ้าน'],
                        'Other People' => ['people_', 'ผู้ดูแลและครอบครัว'],
                        'Medication' => ['med_', 'การใช้ยา'],
                        'Examination' => ['exam_', 'การตรวจร่างกาย'],
                        'Safety' => ['safety_', 'ความปลอดภัย'],
                        'Spiritual' => ['Spiritual_', 'สุขภาพจิตใจ/ศาสนา'],
                        'Service' => ['Service ', 'บริการที่จำเป็น'],
                        'Another' => ['another_', 'อื่นๆ'],
                        'ADL' => ['adl_', 'ADL (Activities of Daily Living)'],
                        'MRS' => ['mrs_', 'MRS (Modified Rankin Scale)']
                    ];

                    // Mapping สำหรับ label จริงจากฟอร์ม
                    $labelMap = [
                        // Immobility
                        'imm_problem_has' => 'มีปัญหา',
                        'imm_problem_none' => 'ไม่มีปัญหา',
                        'imm_self_sufficient' => 'ทำได้เอง',
                        'imm_not_self_sufficient' => 'ทำเองไม่ได้',
                        'imm_bedridden' => 'ผู้ป่วยติดเตียง',
                        'imm_housebound' => 'ผู้ป่วยติดบ้าน',
                        'imm_balance_walking_problem' => 'มีปัญหาการทรงตัว/การเดิน',
                        'imm_sensory_problem' => 'มีปัญหาระบบประสาทสัมผัส',
                        'imm_notes' => 'หมายเหตุการเคลื่อนไหว',

                        // Nutrition
                        'nut_problem_general' => 'มีปัญหา',
                        'nut_no_problem_general' => 'ไม่มีปัญหา',
                        'nut_status_normal' => 'ปกติ',
                        'nut_status_obese' => 'อ้วน',
                        'nut_status_underweight' => 'ผอม',
                        'nut_meals_per_day' => 'จำนวนมื้อต่อวัน',
                        'nut_meal_carer' => 'ผู้ดูแลอาหาร',
                        'nut_type_normal' => 'ธรรมดา',
                        'nut_type_soft' => 'อ่อน/นิ่ม',
                        'nut_type_liquid' => 'เหลว/ปั่น',
                        'nut_type_sweet' => 'หวาน/เบาหวาน',
                        'nut_source_home' => 'ปรุงเอง',
                        'nut_source_ready' => 'ซื้อสำเร็จรูป',
                        'nut_source_frozen' => 'อาหารแช่แข็ง',
                        'nut_source_other' => 'อื่นๆ',
                        'nut_alcohol_drink' => 'ดื่มเหล้า',
                        'nut_alcohol_abstain' => 'ไม่ดื่มเหล้า',
                        'nut_tobacco_smoke' => 'สูบบุหรี่',
                        'nut_tobacco_not_smoke' => 'ไม่สูบบุหรี่',
                        'nut_notes' => 'หมายเหตุโภชนาการ',

                        // Examination
                        'exam_vs_problem' => 'V/S มีปัญหา',
                        'exam_vs_no_problem' => 'V/S ไม่มีปัญหา',
                        'exam_temp_flag' => 'T(°C)',
                        'exam_p_flag' => 'P(/min)',
                        'exam_r_flag' => 'R(/min)',
                        'exam_bp_flag' => 'B.P.(mmHg)',
                        'exam_o2sat_flag' => 'O2sat(%)',
                        'exam_vs_other_flag' => 'V/S อื่นๆ',
                        'exam_ulcer_problem' => 'แผลกดทับ มีปัญหา',
                        'exam_ulcer_no_problem' => 'แผลกดทับ ไม่มีปัญหา',
                        'exam_stiff_problem' => 'ข้อติดแข็ง มีปัญหา',
                        'exam_stiff_no_problem' => 'ข้อติดแข็ง ไม่มีปัญหา',
                        'exam_device_none' => 'การติดอุปกรณ์ ไม่มี',
                        'exam_device_has' => 'การติดอุปกรณ์ มี',
                        'exam_device_o2' => 'O2',
                        'exam_device_ng' => 'NG',
                        'exam_device_tt' => 'TT/Silver tube',
                        'exam_device_foley' => "Foley's cath",
                        'exam_device_gastrostomy' => 'Gastrostomy',
                        'exam_notes' => 'หมายเหตุการตรวจ',

                        // Safety
                        'safety_fall_panel' => 'มีปัญหา',
                        'safety_fall_none' => 'ไม่มีปัญหา',
                        'safety_fall_safe' => 'ปลอดภัยต่อการพลัดตกหกล้ม',
                        'safety_fall_risk' => 'เสี่ยงต่อการพลัดตกหกล้ม',

                        // Spiritual
                        'Spiritual_fall_panel' => 'มีปัญหา',
                        'Spiritual_fall_none' => 'ไม่มีปัญหา',
                        'Spiritual_fall_belief' => 'ความเชื่อ/เครื่องยึดเหนี่ยวจิตใจ',

                        // Service
                        'Service _fall_panel' => 'มีปัญหา',
                        'Service _fall_none' => 'ไม่มีปัญหา',
                        'Service _fall_hp' => 'โรงพยาบาล',
                        'Service _fall_brh' => 'รพ.สต./ศสม',
                        'Service _fall_clinic' => 'คลินิก',
                        'service_other' => 'อื่นๆ',

                        // Another
                        'another_cg' => 'อสม/CG ที่รับผิดชอบ',
                        'another_needs' => 'ปัญหาความต้องการ',
                        'another_nursing_goal' => 'เป้าหมายการพยาบาล',
                        'another_nursing_activity' => 'กิจกรรมพยาบาล',
                        'another_evaluation' => 'การประเมินผล',
                        'another_next_appointment' => 'วันนัดถัดไป',
                        'another_advice' => 'การให้คำแนะนำ',

                        // Home Environment
                        'home_problem_status' => 'ปัญหา',
                        'home_indoor_crowded' => 'ภายในบ้าน แออัด',
                        'home_indoor_airy' => 'ภายในบ้าน โปร่งสบาย',
                        'home_indoor_clean' => 'ภายในบ้าน สะอาด',
                        'home_indoor_no_pet' => 'ภายในบ้าน ไม่มีสัตว์เลี้ยง',
                        'home_indoor_has_pet' => 'ภายในบ้าน มีสัตว์เลี้ยง',
                        'home_outdoor_no_area' => 'บริเวณรอบบ้าน ไม่มีบริเวณ',
                        'home_outdoor_dirty' => 'บริเวณรอบบ้าน สกปรก',
                        'home_outdoor_cluttered' => 'บริเวณรอบบ้าน รกรุงรัง',
                        'home_struct_stable' => 'ความมั่นคงแข็งแรง มั่นคง/แข็งแรง',
                        'home_struct_semi_stable' => 'ความมั่นคงแข็งแรง ไม่ค่อยมั่นคง',
                        'home_struct_dilapidated' => 'ความมั่นคงแข็งแรง เก่า/ชำรุด',
                        'home_light_sufficient' => 'ความสว่าง เพียงพอมองเห็นพื้นชัดเจน',
                        'home_light_dark' => 'ความสว่าง ไม่ชัดเจน',
                        'home_toilet_suitable' => 'ห้องน้ำ เหมาะสมกับผู้ป่วย',
                        'home_toilet_unsuitable' => 'ห้องน้ำ ไม่เหมาะสมกับผู้ป่วย',
                        'home_notes' => 'หมายเหตุสภาพแวดล้อมบ้าน',

                        // Other People
                        'people_problem_status' => 'ปัญหา',
                        'people_emergency_spouse' => 'ผู้นำส่ง สามี/ภรรยา',
                        'people_emergency_father' => 'ผู้นำส่ง พ่อ',
                        'people_emergency_mother' => 'ผู้นำส่ง แม่',
                        'people_emergency_child' => 'ผู้นำส่ง บุตร',
                        'people_emergency_sibling' => 'ผู้นำส่ง พี่/น้อง',
                        'people_emergency_relative' => 'ผู้นำส่ง ญาติ',
                        'people_emergency_other_text' => 'ผู้นำส่ง อื่นๆ',
                        'people_carer_spouse' => 'ผู้ดูแล สามี/ภรรยา',
                        'people_carer_father' => 'ผู้ดูแล พ่อ',
                        'people_carer_mother' => 'ผู้ดูแล แม่',
                        'people_carer_child' => 'ผู้ดูแล บุตร',
                        'people_carer_sibling' => 'ผู้ดูแล พี่/น้อง',
                        'people_carer_relative' => 'ผู้ดูแล ญาติ',
                        'people_carer_other_text' => 'ผู้ดูแล อื่นๆ',
                        'people_notes' => 'หมายเหตุผู้ดูแลและครอบครัว',

                        // Medication
                        'med_follow_correct' => 'Follow Correct',
                        'med_receive_regular' => 'Receive Regular',
                        'med_admin_other' => 'Admin Other',
                        'med_error_has' => 'Error Has',
                        'med_list' => 'รายการยา',

                        // ADL (Barthel Index)
                        'adl_feeding' => 'Feeding (การกินอาหาร)',
                        'adl_bathing' => 'Bathing (การอาบน้ำ)',
                        'adl_grooming' => 'Grooming (การแต่งตัว)',
                        'adl_dressing' => 'Dressing (การสวมใส่เสื้อผ้า)',
                        'adl_bowel' => 'Bowel (การควบคุมอุจจาระ)',
                        'adl_bladder' => 'Bladder (การควบคุมปัสสาวะ)',
                        'adl_toilet' => 'Toilet (การใช้ห้องน้ำ)',
                        'adl_transfers' => 'Transfers (การเคลื่อนย้าย)',
                        'adl_mobility' => 'Mobility (การเคลื่อนไหว/เดิน)',
                        'adl_stairs' => 'Stairs (การขึ้นลงบันได)',

                        // MRS
                        'mrs_score' => 'คะแนน MRS',
                        'mrs_note' => 'หมายเหตุ MRS',
                    ];

                    // ฟังก์ชันแปลง key เป็น label
                    if (!function_exists('getFieldLabel')) {
                        function getFieldLabel($key, $prefix, $labelMap)
                        {
                            if (isset($labelMap[$key])) {
                                return $labelMap[$key];
                            }
                            // ถ้าไม่มีใน map ให้แปลงจาก key
                            $label = str_replace($prefix, '', $key);
                            $label = str_replace('_', ' ', $label);
                            $label = str_replace('Service ', 'Service', $label);
                            $label = str_replace('Spiritual ', 'Spiritual', $label);
                            return ucwords($label);
                        }
                    }

                    foreach ($sections as $sectionKey => $sectionInfo) {
                        $prefix = $sectionInfo[0];
                        $sectionName = $sectionInfo[1];
                        $sectionData = [];

                        // กรองข้อมูลตามหมวด
                        foreach ($assessment_data as $key => $value) {
                            // สำหรับ Service section ต้องตรวจสอบทั้ง 'Service ' และ 'service_'
                            if ($sectionKey === 'Service') {
                                if (strpos($key, 'Service ') === 0 || strpos($key, 'service_') === 0) {
                                    $sectionData[$key] = $value;
                                }
                            } else {
                                if (strpos($key, $prefix) === 0) {
                                    $sectionData[$key] = $value;
                                }
                            }
                        }

                        if (!empty($sectionData)): ?>
                            <div class="assessment-card">
                                <div class="section-header">
                                    <h5><?php echo $sectionName; ?></h5>
                                </div>

                                <?php if ($sectionKey === 'Another'): ?>
                                    <!-- Another section: แสดงเป็น textarea style -->
                                    <div style="display: flex; flex-direction: column; gap: 16px;">
                                        <?php foreach ($sectionData as $key => $value):
                                            if ($value && $value !== '' && trim($value) !== ''):
                                                $label = getFieldLabel($key, $prefix, $labelMap);
                                                $displayValue = is_array($value) ? implode(', ', $value) : $value;
                                                ?>
                                                <div
                                                    style="background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px;">
                                                    <div
                                                        style="color: #64748b; font-weight: 600; font-size: 14px; margin-bottom: 8px;">
                                                        <?php echo htmlspecialchars($label); ?>
                                                    </div>
                                                    <div
                                                        style="color: #111827; font-size: 15px; font-weight: 500; white-space: pre-wrap; line-height: 1.6;">
                                                        <?php echo nl2br(htmlspecialchars($displayValue)); ?>
                                                    </div>
                                                </div>
                                            <?php endif; endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <!-- Other sections: แสดงเป็น grid -->
                                    <div class="data-grid">
                                        <?php foreach ($sectionData as $key => $value):
                                            if ($value && $value !== '' && $value !== '0' && $value !== 'no' && $value !== 'off'): 
                                                $label = getFieldLabel($key, $prefix, $labelMap);
                                                $displayValue = is_array($value) ? implode(', ', $value) : $value;

                                                // แปลงค่าที่เป็น Boolean/Flag
                                                if (in_array($displayValue, ['yes', 'on', '1', true], true)) {
                                                    $displayValue = 'ใช่';
                                                }

                                                // ตรวจสอบว่าเป็น text field (service_other) เพื่อแสดงแบบเต็มความกว้าง
                                                $isTextField = (strpos($key, 'service_other') === 0);

                                                // ตรวจสอบว่าเป็นส่วนคะแนนหรือไม่เพื่อเพิ่ม Class พิเศษ
                                                $isScore = (strpos($key, 'adl_') !== false || strpos($key, 'mrs_') !== false);
                                                ?>
                                                <?php if ($isTextField && strlen(trim($displayValue)) > 50): ?>
                                                    <!-- Text fields ที่ยาว: แสดงแบบเต็มความกว้าง -->
                                                    <div
                                                        style="grid-column: 1 / -1; background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 8px;">
                                                        <div
                                                            style="color: #64748b; font-weight: 600; font-size: 14px; margin-bottom: 8px;">
                                                            <?php echo htmlspecialchars($label); ?>
                                                        </div>
                                                        <div
                                                            style="color: #111827; font-size: 15px; font-weight: 500; white-space: pre-wrap; line-height: 1.6;">
                                                            <?php echo nl2br(htmlspecialchars($displayValue)); ?>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <!-- Normal data items -->
                                                    <div class="data-item">
                                                        <span class="data-label"><?php echo htmlspecialchars($label); ?></span>
                                                        <span class="data-value <?php echo $isScore ? 'score-highlight' : ''; ?>">
                                                            <?php echo htmlspecialchars($displayValue); ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endif; endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif;
                    } ?>
                </div>
                <?php else: ?>
                    <div class="empty-state">ยังไม่มีข้อมูลการประเมิน INHOMESSS</div>
                <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state">ยังไม่มีข้อมูลการประเมิน INHOMESSS</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    </div>
    <!-- End bottom-section -->

    <div class="back-link">
        <a href="index.php">← กลับไปหน้าข้อมูลผู้ป่วย</a>
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
            const newParams = new URLSearchParams(window.location.search);
            newParams.delete('toast');
            newParams.delete('msg');
            const newUrl = window.location.pathname + (newParams.toString() ? '?' + newParams.toString() : '');
            window.history.replaceState({}, '', newUrl);
        }
    </script>
</body>
</html>