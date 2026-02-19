<?php
require_once __DIR__ . '/db.php';
$patient = isset($_GET['patient_id']) ? db_get_patient_by_id($_GET['patient_id']) : null;
$patient = $patient ?? [
    'hn' => '',
    'fullname' => '',
    'id' => ''
];

// ตั้งค่าเริ่มต้นป้องกัน Undefined variable
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

// ดึงข้อมูลเยี่ยมบ้าน/ประเมิน (สูงสุด 300 รายการ)
$visits = function_exists('db_get_patient_records') ? db_get_patient_records(300) : [];

// ถ้ามีค้นหา → กรองข้อมูล
if ($search_query !== '' && !empty($visits)) {

    $q = mb_strtolower($search_query, 'UTF-8');

    $visits = array_filter($visits, function ($v) use ($q) {
        $hn = mb_strtolower($v['hn'] ?? '', 'UTF-8');
        $fullname = mb_strtolower($v['fullname'] ?? '', 'UTF-8');

        // ค้นหาได้ทั้ง HN และชื่อผู้ป่วย
        return strpos($hn, $q) !== false || strpos($fullname, $q) !== false;
    });
}

?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>สรุปการเยี่ยมบ้าน</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f5f7fb;
            padding: 20px;
            color: #1f2937;
        }

        .container {
            max-width: 1300px;
            margin: 0 auto;
            background: #ffffff;
            padding: 28px 32px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-top: 20px;
        }

        /* จัดวางส่วนหัวและปุ่มให้อยู่ข้างบนตาราง */
        .top-header {
            display: flex;
            justify-content: space-between;
            /* แยกหัวข้อไว้ซ้าย ปุ่มไว้ขวา */
            align-items: center;
            margin-bottom: 8px;
            border-bottom: 5px solid #f0fdfa;
            padding-bottom: 15px;
        }

        h1 {
            font-size: 28px;
            font-weight: 600;
            color: rgb(15, 167, 154);
            margin: 0;
            /* เอา margin ออกเพื่อให้อยู่กึ่งกลางแนวตั้งกับปุ่ม */
        }

        .btn-primary {
            padding: 10px 20px;
            border-radius: 999px;
            border: none;
            background: linear-gradient(135deg, rgb(159, 235, 228) 0%, rgb(3, 163, 145) 100%);
            color: #fff;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            transition: transform 0.2s, box-shadow 0.2s;
            display: inline-flex;
            align-items: center;
            box-shadow: 0 4px 6px rgba(15, 118, 110, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 12px rgba(15, 118, 110, 0.3);
        }

        .summary-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .summary-table th,
        .summary-table td {
            border: 1px solid #e5e7eb;
            padding: 14px 16px;
            font-size: 14px;
        }

        .summary-table th {
            background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%);
            color: #ffffff;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            padding: 16px;
            border-bottom: 2px solid #0d9488;
        }

        .summary-table th:first-child {
            border-top-left-radius: 12px;
        }

        .summary-table th:last-child {
            border-top-right-radius: 12px;
        }

        /* เพิ่มสีไฮไลท์เวลาเอาเมาส์ชี้แถว */
        .summary-table tbody tr {
            transition: all 0.2s ease;
        }

        .summary-table tbody tr:hover {
            background-color: #f0fdfa;
            transform: scale(1.001);
            box-shadow: 0 2px 8px rgba(15, 118, 110, 0.1);
        }

        .summary-table tbody tr:nth-child(even) {
            background-color: #fafafa;
        }

        .summary-table tbody tr:nth-child(even):hover {
            background-color: #f0fdfa;
        }

        .actions {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .btn-small {
            padding: 4px 12px;
            font-size: 12px;
            border-radius: 999px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            color: #fff;
            transition: opacity 0.2s;
        }

        .btn-view {
            background: linear-gradient(135deg, #0ea5e9 0%, #38bdf8 100%);
        }

        .btn-small:hover {
            opacity: 0.9;
        }

        .Head {
            background: #ffffff;
            padding: 20px 28px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin: 15px auto;
            max-width: 1300px;
        }

        .back-link a {
            color: #0f766e;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            padding-left: 40rem;
        }

        .search-bar {
            padding: 20px 0 0 0;
            display: flex;
            gap: 12px;
            align-items: center;
            justify-content: flex-end;
        }

        .search-container {
            flex: 1;
            max-width: 400px;
        }

        .search-bar input {
            width: 100%;
            padding: 16px 24px;
            border: 2px solid #e5e7eb;
            border-radius: 16px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #ffffff;
            color: #1f2937;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .search-bar input:focus {
            outline: none;
            border-color: #0f766e;
            box-shadow: 0 4px 12px rgba(15, 118, 110, 0.15);
        }

        .search-bar input::placeholder {
            color: #9ca3af;
            font-weight: 400;
        }

        .btn-search {
            padding: 16px 32px !important;
            border-radius: 16px !important;
            border: none;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            margin-right: 0 !important;
            height: auto !important;
            width: auto !important;
            background: var(--primary-teal);
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 4px 16px rgba(15, 118, 110, 0.3);
            display: flex;
            align-items: center;
            gap: 10px;
            background: #009688;

        }

        .btn-search:hover {
            background: #009688;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(15, 118, 110, 0.4);
        }

        .btn-search:active {
            transform: translateY(0);
        }

        .btn-clear-search {
            padding: 16px 28px !important;
            border-radius: 16px !important;
            border: none;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            height: auto !important;
            width: auto !important;
            background: linear-gradient(135deg, #f87171 0%, #ef4444 100%);
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 4px 16px rgba(239, 68, 68, 0.3);
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
        }

        .btn-clear-search::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn-clear-search:hover {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
        }

        .btn-clear-search:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-clear-search:active {
            transform: translateY(0);
        }

        .btn-clear-search span {
            position: relative;
            z-index: 1;
        }

        .btn-clear-search span:first-child {
            font-size: 20px;
            font-weight: bold;
            line-height: 1;
        }

        .btn-clear-search span:last-child {
            font-size: 15px;
            font-weight: 600;
        }

        /* ปุ่มจัดการ - ปรับปรุงให้สวยงาม */
        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
        }

        .btn-evaluate,
        button.btn-evaluate {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
            color: white !important;
            padding: 10px 18px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            position: relative;
            overflow: hidden;
            min-width: 100px;
            justify-content: center;
        }

        .btn-evaluate span:first-child {
            font-size: 16px;
            line-height: 1;
        }

        .btn-evaluate:hover {
            background: linear-gradient(135deg, #d97706 0%, #b45309 100%) !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(245, 158, 11, 0.4);
        }

        .btn-evaluate:active {
            transform: translateY(0);
        }

        .btn-delete,
        button.btn-delete {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
            color: white !important;
            padding: 10px 18px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            position: relative;
            overflow: hidden;
            min-width: 100px;
            justify-content: center;
        }

        .btn-delete span:first-child {
            font-size: 16px;
            line-height: 1;
        }

        .btn-delete:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%) !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(239, 68, 68, 0.4);
        }

        .btn-delete:active {
            transform: translateY(0);
        }

        /* ปรับปรุงตารางให้สวยงามขึ้น */
        .summary-table td:last-child {
            text-align: center;
            padding: 16px 12px;
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
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            padding: 32px;
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            margin-bottom: 20px;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
            color: #1f2937;
        }

        .modal-body {
            padding: 20px 0;
            color: #374151;
            margin-bottom: 24px;
        }

        .modal-body p {
            margin: 0 0 12px 0;
            font-size: 16px;
        }

        .patient-name {
            font-weight: 600;
            font-size: 18px;
            color: #ef4444;
            margin-top: 8px;
        }

        .modal-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
        }

        .modal-btn {
            padding: 12px 24px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
            min-width: 100px;
        }

        .modal-btn-delete {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .modal-btn-delete:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(239, 68, 68, 0.4);
        }

        .modal-btn-cancel {
            background: #e5e7eb;
            color: #374151;
        }

        .modal-btn-cancel:hover {
            background: #d1d5db;
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
    <div class="Head">
        <div class="top-header">
            <h1>สรุปการเยี่ยมบ้าน</h1>
            <div class="back-link">
                <a href="index.php">← ย้อนกลับหน้าเดิม</a>
            </div>
        </div>

        <form class="search-bar" method="get" action="visits_summary.php">
            <div class="search-container">
                <input type="text" name="q" placeholder="ค้นหา HN หรือชื่อผู้ป่วย..."
                    value="<?php echo htmlspecialchars($search_query); ?>">
            </div>
            <button type="submit" class="btn-search">
                <span>ค้นหา</span>
            </button>
            <?php if ($search_query !== ''): ?>
                <button type="button" class="btn-clear-search" onclick="window.location.href='visits_summary.php';" title="ล้างการค้นหา">
                    <span>×</span>
                    <span>ล้าง</span>
                </button>
            <?php endif; ?>
        </form>
    </div>
    <?php
    // ตั้งค่าเริ่มต้นป้องกัน Undefined variable
    $search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
    ?>



    <div class="container">


        <table class="summary-table">
            <thead>
                <tr>
                    <th>ลำดับ</th>
                    <th>วันที่เยี่ยม</th>
                    <th>เวลา</th>
                    <th>HN</th>
                    <th>ชื่อ-สกุลผู้ป่วย</th>
                    <th>เพศ</th>
                    <th>ที่อยู่</th>
                    <th>อาศัยอยู่กับ</th>
                    <th>ผู้เยี่ยม/ผู้ประเมิน</th>
                    <th>ครั้งที่ประเมิน</th>
                    <th>ครั้งที่เยี่ยมบ้าน</th>
                    <th>ประเภท</th>
                    <th style="text-align: center;">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($visits)): ?>
                    <?php 
                    // Debug: ตรวจสอบข้อมูลก่อนแสดง
                    $debug_missing_ids = [];
                    foreach ($visits as $idx => $v) {
                        if (!isset($v['record_id']) || $v['record_id'] === null || $v['record_id'] <= 0) {
                            $debug_missing_ids[] = [
                                'index' => $idx,
                                'hn' => $v['hn'] ?? '',
                                'fullname' => $v['fullname'] ?? '',
                                'record_type' => $v['record_type'] ?? 'unknown',
                                'keys' => array_keys($v)
                            ];
                        }
                    }
                    if (!empty($debug_missing_ids)) {
                        error_log("Missing record_id in visits_summary.php: " . json_encode($debug_missing_ids));
                    }
                    
                    $i = 1;
                    foreach ($visits as $visit): ?>
                        <tr>
                            <td style="text-align: center;"><?php echo $i++; ?></td>
                            <td><?php echo htmlspecialchars($visit['visit_date'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($visit['visit_time'] ?? ''); ?></td>
                            <td><strong><?php echo htmlspecialchars($visit['hn'] ?? ''); ?></strong></td>
                            <td><?php echo htmlspecialchars($visit['fullname'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($visit['gender'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($visit['address'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($visit['live_with'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($visit['visitor'] ?? ''); ?></td>
                            <td><?php echo $visit['record_type'] === 'assessment'
                                    ? htmlspecialchars($visit['assessment_number'] ?? '')
                                    : '-'; ?></td>
                            <td><?php echo $visit['record_type'] === 'visit'
                                    ? htmlspecialchars($visit['visit_number'] ?? '')
                                    : '-'; ?></td>
                            <td><?php echo $visit['record_type'] === 'assessment' ? 'ประเมิน' : 'เยี่ยม'; ?></td>
                            <td>
                                <div class="actions">
                                    <button class="btn-evaluate" 
                                        data-id="<?= $visit['record_id']; ?>"
                                        data-record-type="<?= htmlspecialchars($visit['record_type'] ?? 'visit'); ?>"
                                        data-hn="<?= htmlspecialchars($visit['hn']); ?>"
                                        data-fullname="<?= htmlspecialchars($visit['fullname']); ?>"
                                        data-patient-id="<?= $visit['patient_id'] ?? ''; ?>"
                                        title="แก้ไขข้อมูล">
                                       
                                        <span>แก้ไข</span>
                                    </button>

                                    <?php 
                                    // ตรวจสอบว่า record_id มีค่าหรือไม่
                                    $record_id = isset($visit['record_id']) && $visit['record_id'] !== null ? (int)$visit['record_id'] : null;
                                    $record_type = $visit['record_type'] ?? 'visit';
                                    
                                    // Debug: ถ้า record_id เป็น null ให้แสดง error
                                    if ($record_id === null || $record_id <= 0) {
                                        error_log("Missing record_id for visit: " . json_encode([
                                            'hn' => $visit['hn'] ?? '',
                                            'fullname' => $visit['fullname'] ?? '',
                                            'record_type' => $record_type,
                                            'visit_data' => $visit
                                        ]));
                                    }
                                    ?>
                                    <button class="btn-delete"
                                        data-record-id="<?php echo $record_id ?? 0; ?>"
                                        data-record-type="<?php echo htmlspecialchars($record_type); ?>"
                                        data-fullname="<?php echo htmlspecialchars($visit['fullname'] ?? $visit['name'] ?? 'ผู้ป่วย'); ?>"
                                        data-hn="<?php echo htmlspecialchars($visit['hn'] ?? ''); ?>"
                                        title="ลบข้อมูล">
                                      
                                        <span>ลบ</span>
                                    </button>
                                    <?php if ($record_id === null || $record_id <= 0): ?>
                                    <!-- Debug info -->
                                    <script>
                                        console.error('Missing record_id for:', {
                                            hn: '<?php echo htmlspecialchars($visit['hn'] ?? ''); ?>',
                                            fullname: '<?php echo htmlspecialchars($visit['fullname'] ?? ''); ?>',
                                            record_type: '<?php echo htmlspecialchars($record_type); ?>',
                                            visit_keys: <?php echo json_encode(array_keys($visit)); ?>
                                        });
                                    </script>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" style="text-align:center; padding: 30px; color: #6b7280;">
                            ยังไม่มีข้อมูลการเยี่ยมบ้าน</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div id="evaluationModal" class="modal">
        <div class="modal-content">
            <h2 style="color: var(--primary-teal);">เลือกประเภทการประเมิน</h2>
            <p id="modalPatientName" style="font-weight: bold; color: #666;"></p>
            <div class="modal-buttons">
                <button class="modal-btn" style="background: #2196F3; color: white;"
                    onclick="selectEvaluation('visit_type')">📋 ประเภทการเยี่ยม</button>
                <button class="modal-btn" style="background: #4CAF50; color: white;"
                    onclick="selectEvaluation('inhomesss')">🏠 การประเมิน INHOMESSS</button>
                <button class="modal-btn" style="background: #f44336; color: white;"
                    onclick="closeModal()">ยกเลิก</button>
            </div>
        </div>
    </div>

    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">

                <h2>ยืนยันการลบข้อมูล</h2>
            </div>
            <div class="modal-body">
                <p>คุณต้องการลบข้อมูลผู้ป่วย</p>
                <div class="patient-name" id="deletePatientName"></div>

            </div>
            <div class="modal-buttons">
                <button class="modal-btn modal-btn-delete" id="confirmDeleteBtn">ลบ</button>
                <button class="modal-btn modal-btn-cancel" onclick="closeDeleteModal()">ยกเลิก</button>
            </div>
        </div>
    </div>



    <script>
        let currentPatient = null;

        // เปิด Modal สำหรับแก้ไขเรคคอร์ดที่เลือก
        document.querySelectorAll('.btn-evaluate').forEach(btn => {
            btn.addEventListener('click', function () {
                const recordType = this.dataset.recordType || 'visit';
                const recordId = this.dataset.id;
                
                // ถ้าเป็นการแก้ไข ให้ไปที่หน้าแก้ไขโดยตรง
                if (recordType === 'assessment') {
                    // แก้ไขการประเมิน
                    window.location.href = 'edit_assessment.php?id=' + recordId;
                } else {
                    // แก้ไขการเยี่ยม - ไปที่ visit_types.php พร้อม visit_id (ไม่ส่ง prefill=0 เพื่อให้โหลดข้อมูล)
                    const params = new URLSearchParams({
                        visit_id: recordId,
                        hn: this.dataset.hn,
                        fullname: this.dataset.fullname,
                        patient_id: this.dataset.patientId || ''
                    });
                    window.location.href = 'visit_types.php?' + params.toString();
                }
            });
        });

        function closeModal() {
            document.getElementById('evaluationModal').style.display = 'none';
        }

        function selectEvaluation(type) {
            let url = (type === 'visit_type') ? 'visit_types.php' : 'inhomess_assessment.php';
            const params = new URLSearchParams({
                hn: currentPatient.hn,
                fullname: currentPatient.fullname,
                patient_id: currentPatient.id,
                prefill: '0'
            });
            window.location.href = url + '?' + params.toString();
        }

        // ปิดเมื่อคลิกนอก Modal
        window.onclick = function (event) {
            const evaluationModal = document.getElementById('evaluationModal');
            const deleteModal = document.getElementById('deleteModal');
            if (event.target == evaluationModal) {
                closeModal();
            }
            if (event.target == deleteModal) {
                closeDeleteModal();
            }
        }

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
            const newUrl = window.location.pathname + (urlParams.get('q') ? '?q=' + urlParams.get('q') : '');
            window.history.replaceState({}, '', newUrl);
        }

        // ฟังก์ชันยืนยันการลบเรคคอร์ด
        let deleteRecordId = null;
        let deleteRecordType = null;

function confirmDelete(recordId, fullname, recordType) {
    // ตรวจสอบว่ามี modal หรือไม่
    const deleteModal = document.getElementById('deleteModal');
    if (!deleteModal) {
        alert('ไม่พบ Modal สำหรับยืนยันการลบ');
        console.error('deleteModal not found');
        return;
    }

    // ตรวจสอบว่ามี recordId หรือไม่
    if (!recordId || recordId <= 0) {
        alert('ไม่พบ ID ของข้อมูลที่ต้องการลบ');
        console.error('Invalid recordId:', recordId);
        return;
    }

    // เก็บ id และประเภทเรคคอร์ดที่ต้องการลบ
    deleteRecordId = parseInt(recordId);
    // แปลงเป็น lowercase และ trim เพื่อป้องกันปัญหา
    deleteRecordType = (recordType || 'visit').toString().toLowerCase().trim();

    // Debug: แสดงข้อมูลใน console
    console.log('confirmDelete called:', {
        id: deleteRecordId,
        type: deleteRecordType,
        fullname: fullname,
        originalType: recordType
    });

    // แสดงชื่อผู้ป่วยใน modal
    const patientNameEl = document.getElementById('deletePatientName');
    if (patientNameEl) {
        patientNameEl.textContent = '"' + (fullname || 'ผู้ป่วย') + '"';
    } else {
        console.error('deletePatientName element not found');
    }

    // เปิด Modal
    deleteModal.style.display = 'flex';
    console.log('Modal opened');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
    deleteRecordId = null;
    deleteRecordType = null;
}

function executeDelete() {
    console.log('executeDelete called', {
        deleteRecordId: deleteRecordId,
        deleteRecordType: deleteRecordType
    });

    if (!deleteRecordId || deleteRecordId <= 0) {
        alert('ไม่พบข้อมูลที่ต้องการลบ');
        console.error('Invalid deleteRecordId:', deleteRecordId);
        closeDeleteModal();
        return;
    }

    if (!deleteRecordType) {
        alert('ไม่พบประเภทข้อมูลที่ต้องการลบ');
        console.error('Missing deleteRecordType');
        closeDeleteModal();
        return;
    }

    // เลือกไฟล์ที่ใช้ลบตามประเภทเรคคอร์ด
    let deleteAction = null;
    
    // แปลงเป็น lowercase เพื่อป้องกันปัญหา case-sensitive
    const recordType = deleteRecordType.toLowerCase().trim();
    
    if (recordType === 'assessment') {
        deleteAction = 'delete_assessment.php';
    } else if (recordType === 'visit') {
        deleteAction = 'delete_visit.php';
    } else {
        alert('ประเภทข้อมูลไม่ถูกต้อง: ' + recordType + '\nกรุณาตรวจสอบข้อมูลอีกครั้ง');
        console.error('Invalid recordType:', recordType);
        closeDeleteModal();
        return;
    }

    // เก็บค่าไว้ในตัวแปรก่อน (เพื่อป้องกันการสูญหายเมื่อปิด modal)
    const recordIdToDelete = deleteRecordId;
    const recordTypeToDelete = recordType;
    
    // Debug: แสดงข้อมูลใน console
    console.log('Executing delete:', {
        id: recordIdToDelete,
        type: recordTypeToDelete,
        action: deleteAction,
        originalDeleteRecordId: deleteRecordId
    });

    // ตรวจสอบอีกครั้งก่อนสร้าง form
    if (!recordIdToDelete || recordIdToDelete <= 0) {
        alert('ไม่พบ ID ของข้อมูลที่ต้องการลบ\nกรุณาลองใหม่อีกครั้ง');
        console.error('recordIdToDelete is invalid:', recordIdToDelete);
        closeDeleteModal();
        return;
    }

    // ปิด Modal ก่อนส่งข้อมูล
    closeDeleteModal();

    // เก็บ query parameter q (ถ้ามีการค้นหา)
    const urlParams = new URLSearchParams(window.location.search);
    const searchQuery = urlParams.get('q') || '';

    // สร้าง form และส่งข้อมูล
    try {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = deleteAction;
        form.style.display = 'none';

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'id';
        input.value = recordIdToDelete;

        // ส่ง query parameter q ไปด้วย (ถ้ามี)
        if (searchQuery) {
            const qInput = document.createElement('input');
            qInput.type = 'hidden';
            qInput.name = 'q';
            qInput.value = searchQuery;
            form.appendChild(qInput);
        }

        // ส่ง referer เพื่อให้รู้ว่ามาจากหน้าไหน
        const refererInput = document.createElement('input');
        refererInput.type = 'hidden';
        refererInput.name = 'referer';
        refererInput.value = window.location.href;
        form.appendChild(refererInput);

        // ส่ง record_type เพื่อ debug
        const typeInput = document.createElement('input');
        typeInput.type = 'hidden';
        typeInput.name = 'record_type';
        typeInput.value = recordTypeToDelete;
        form.appendChild(typeInput);

        form.appendChild(input);
        document.body.appendChild(form);
        
        // ตรวจสอบ form ก่อน submit
        const formData = new FormData(form);
        const formDataObj = {};
        for (let [key, value] of formData.entries()) {
            formDataObj[key] = value;
        }
        
        console.log('Submitting form:', {
            action: deleteAction,
            id: recordIdToDelete,
            type: recordTypeToDelete,
            q: searchQuery,
            referer: window.location.href,
            formData: formDataObj,
            formElement: form,
            idInputValue: input.value
        });
        
        // ตรวจสอบว่ามี input id หรือไม่
        const idInput = form.querySelector('input[name="id"]');
        if (!idInput || !idInput.value || parseInt(idInput.value) <= 0) {
            alert('เกิดข้อผิดพลาด: ไม่พบ ID ที่จะส่งไปลบ\nID: ' + (idInput ? idInput.value : 'null') + '\nกรุณาลองใหม่อีกครั้ง');
            console.error('ID input is missing or invalid:', {
                idInput: idInput,
                idInputValue: idInput ? idInput.value : 'null',
                recordIdToDelete: recordIdToDelete,
                formHTML: form.outerHTML
            });
            form.remove();
            return;
        }
        
        console.log('Form is valid, submitting...');
        form.submit();
    } catch (error) {
        console.error('Error submitting form:', error);
        alert('เกิดข้อผิดพลาดในการส่งข้อมูล: ' + error.message);
    }
}

// ฟังก์ชันสำหรับ bind event listeners
function bindDeleteButtons() {
    // Bind ปุ่มลบทั้งหมด
    const deleteButtons = document.querySelectorAll('.btn-delete');
    console.log('Found delete buttons:', deleteButtons.length);
    
    deleteButtons.forEach((btn, index) => {
        // ลบ event listener เก่าออกก่อน (ถ้ามี)
        const newBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(newBtn, btn);
        
        // เพิ่ม event listener ใหม่
        newBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const recordId = this.getAttribute('data-record-id');
            const recordType = this.getAttribute('data-record-type');
            const fullname = this.getAttribute('data-fullname');
            const hn = this.getAttribute('data-hn') || '';
            
            // เก็บข้อมูลทั้งหมดของปุ่มเพื่อ debug
            const allAttributes = {};
            for (let attr of this.attributes) {
                allAttributes[attr.name] = attr.value;
            }
            
            console.log('Delete button clicked:', {
                index: index,
                recordId: recordId,
                recordIdType: typeof recordId,
                recordType: recordType,
                fullname: fullname,
                hn: hn,
                allAttributes: allAttributes,
                buttonHTML: this.outerHTML
            });
            
            // ตรวจสอบ recordId
            if (!recordId || recordId === 'null' || recordId === 'undefined' || recordId === '' || recordId === '0') {
                const errorMsg = 'ไม่พบ ID ของข้อมูลที่ต้องการลบ\n\n' +
                    'ข้อมูล:\n' +
                    '- HN: ' + hn + '\n' +
                    '- ชื่อ: ' + fullname + '\n' +
                    '- ประเภท: ' + recordType + '\n' +
                    '- Record ID: ' + recordId + '\n\n' +
                    'กรุณาติดต่อผู้ดูแลระบบ';
                alert(errorMsg);
                console.error('Missing or invalid recordId:', {
                    recordId: recordId,
                    recordType: recordType,
                    fullname: fullname,
                    hn: hn,
                    allAttributes: allAttributes
                });
                return;
            }
            
            // แปลง recordId เป็น number
            const recordIdNum = parseInt(recordId, 10);
            if (isNaN(recordIdNum) || recordIdNum <= 0) {
                const errorMsg = 'ID ของข้อมูลไม่ถูกต้อง\n\n' +
                    'Record ID: ' + recordId + '\n' +
                    'Parsed as: ' + recordIdNum + '\n' +
                    'HN: ' + hn + '\n' +
                    'ชื่อ: ' + fullname;
                alert(errorMsg);
                console.error('Invalid recordId:', {
                    recordId: recordId,
                    recordIdNum: recordIdNum,
                    recordType: recordType,
                    fullname: fullname,
                    hn: hn
                });
                return;
            }
            
            console.log('Calling confirmDelete with:', {
                recordId: recordIdNum,
                fullname: fullname || 'ผู้ป่วย',
                recordType: recordType || 'visit'
            });
            
            confirmDelete(recordIdNum, fullname || 'ผู้ป่วย', recordType || 'visit');
        });
    });
    
    // Bind ปุ่มยืนยันการลบ
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    if (confirmBtn) {
        // ลบ event listener เก่าออกก่อน (ถ้ามี)
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
        
        newConfirmBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('Confirm delete button clicked', {
                deleteRecordId: deleteRecordId,
                deleteRecordType: deleteRecordType
            });
            
            if (!deleteRecordId || deleteRecordId <= 0) {
                alert('ไม่พบ ID ของข้อมูลที่ต้องการลบ\nกรุณาลองใหม่อีกครั้ง');
                console.error('deleteRecordId is invalid when confirm button clicked:', deleteRecordId);
                closeDeleteModal();
                return;
            }
            
            executeDelete();
        });
        console.log('Confirm delete button bound');
    } else {
        console.error('confirmDeleteBtn not found');
    }
}

// รอให้ DOM โหลดเสร็จก่อน
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bindDeleteButtons);
} else {
    // DOM โหลดเสร็จแล้ว
    bindDeleteButtons();
}

    </script>
</body>

</html>