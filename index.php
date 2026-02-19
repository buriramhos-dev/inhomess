<?php
// index.php
require_once __DIR__ . '/db.php';

// ค่าค้นหาจากช่องค้นหา (ค้นหาจาก HN หรือชื่อ)
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

// ดึงข้อมูลผู้ป่วยจาก Database (หรือใช้ข้อมูลจำลองถ้าเชื่อมต่อไม่ได้)
$patients = [];
if (file_exists(__DIR__ . '/db.php')) {
    if (function_exists('db_get_patients')) {
        $patients = db_get_patients(200);
    }
}


// ถ้ามีการค้นหา ให้กรองรายการผู้ป่วยตาม HN หรือชื่อ
if ($search_query !== '' && !empty($patients)) {
    $q = mb_strtolower($search_query, 'UTF-8');
    $patients = array_values(array_filter($patients, function ($p) use ($q) {
        $hn = isset($p['hn']) ? mb_strtolower((string) $p['hn'], 'UTF-8') : '';
        $fullname = isset($p['fullname']) ? mb_strtolower($p['fullname'], 'UTF-8') : '';
        $name = isset($p['name']) ? mb_strtolower($p['name'], 'UTF-8') : '';

        // ให้ค้นหาได้ทั้ง HN และชื่อ (แต่เน้น HN ตามที่ผู้ใช้ต้องการ)
        return (strpos($hn, $q) !== false)
            || (strpos($fullname, $q) !== false)
            || (strpos($name, $q) !== false);
    }));
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อมูลผู้ป่วย - ระบบ INHOMESSS</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* --- CSS ส่วนที่ปรับปรุงเพิ่มเติม --- */
        :root {
            --primary-teal: #0f766e;
            --primary-teal-dark: #0d5d57;
            --light-teal: #14b8a6;
            --bg-gray: #f8fafc;
            --text-dark: #1f2937;
            --blue-action: #0ea5e9;
            --blue-action-dark: #0c8dd1;
            --orange-action: #f59e0b;
            --orange-action-dark: #d97706;
            --red-action: #ef4444;
            --red-action-dark: #dc2626;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-gray);
            margin: 0;
            color: var(--text-dark);
        }

        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 80px;
            background: white;
            border-bottom: 1px solid #e5e7eb;
        }

        /* สไตล์ปุ่มเพิ่มข้อมูลแบบใหม่ */
        .btn-add-container {
            text-decoration: none;
        }

        .btn-add-patient {
            background: #009688;
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 50px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(15, 118, 110, 0.25);
        }

        .btn-add-patient:hover {
            background: #009688;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(15, 118, 110, 0.35);
        }

        .btn-add-patient .plus-icon {
            font-size: 20px;
            font-weight: bold;
        }

        /* จัดการ Layout ของ Header */
        .header-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .header-dashboard {
            display: flex;
            align-items: center;
            gap: 20px;
            justify-content: flex-end;
            padding-left: 1150px;
        }



        /* ตารางข้อมูล */
        .patient-table-container {
            padding: 30px 40px;

        }

        .patient-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .patient-table thead th {
            background-color: #009688 !important;
            /* สีทึบ */
            background-image: none !important;
            /* ตัด gradient เดิม */
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #e2e8f0;
        }


        .patient-table td {
            padding: 15px;
            border-bottom: 1px solid #f1f5f9;
        }

        .btn-action {
            padding: 6px 14px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 13px;
            margin-right: 5px;
            transition: all 0.2s ease;
            height: 40px;
            width: 70px;
            font-weight: 500;
        }

        .btn-action:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        /* ปุ่มดู - สีฟ้า */
        .btn-action.btn-view,
        a .btn-action.btn-view,
        button.btn-action.btn-view {
            background-color: #0ea5e9 !important;
            color: white !important;
        }

        .btn-action.btn-view:hover,
        a .btn-action.btn-view:hover,
        button.btn-action.btn-view:hover {
            background-color: #0c8dd1 !important;
        }

        /* ปุ่มประเมิน - สีเขียวเข้ม */
        .btn-action.btn-evaluate,
        button.btn-action.btn-evaluate {
            background-color: #259b24 !important;
            color: white !important;
        }

        .btn-action.btn-evaluate:hover,
        button.btn-action.btn-evaluate:hover {
            background-color: #0d5d57 !important;
        }

        /* ปุ่มแก้ไข - สีส้ม */
        .btn-action.edit,
        a .btn-action.edit,
        button.btn-action.edit {
            background-color: #f59e0b !important;
            color: white !important;
        }

        .btn-action.edit:hover,
        a .btn-action.edit:hover,
        button.btn-action.edit:hover {
            background-color: #d97706 !important;
        }

        /* ปุ่มลบ - สีแดง */
        .btn-action.delete,
        button.btn-action.delete {
            background-color: #ef4444 !important;
            color: white !important;
        }

        .btn-action.delete:hover,
        button.btn-action.delete:hover {
            background-color: #dc2626 !important;
        }

        .search-bar {
            padding: 20px 40px 0 40px;
            display: flex;
            gap: 12px;
            align-items: center;
        }


        .search-container:focus-within {
            border-color: var(--primary-teal);
            box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.1), 0 6px 20px rgba(0, 0, 0, 0.12);
        }

        .search-bar input {
            width: 100%;
            padding: 16px 24px;
            border: none;
            border-radius: 16px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: transparent;
            color: #1f2937;
        }

        .search-bar input:focus {
            outline: none;
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
            border: 2px solid #e5e7eb;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            height: auto !important;
            width: auto !important;
            background: white;
            color: #6b7280;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .btn-clear-search:hover {
            background: #f9fafb;
            border-color: #d1d5db;
            color: #374151;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
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
            backdrop-filter: blur(4px);
            animation: fadeIn 0.2s ease-out;
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
            background: white;
            padding: 24px;
            border-radius: 12px;
            width: 90%;
            max-width: 360px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            animation: slideUp 0.2s ease-out;
        }

        #evaluationModal .modal-content {
            max-width: 600px;
            padding: 40px;
        }

        #evaluationModal h2 {
            font-size: 28px;
            margin-bottom: 20px;
        }

        #evaluationModal #modalPatientName {
            font-size: 18px;
            margin-bottom: 30px;
        }

        @keyframes slideUp {
            from {
                transform: translateY(10px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            margin-bottom: 16px;
        }

        .modal-header-icon {
            font-size: 32px;
            margin-bottom: 8px;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
        }

        .modal-body {
            padding: 0;
            color: #374151;
            margin-bottom: 20px;
        }

        .modal-body p {
            margin: 0 0 8px 0;
            font-size: 14px;
            line-height: 1.5;
            color: #6b7280;
        }

        .modal-body .patient-name {
            color: #ef4444;
            font-weight: 600;
            font-size: 16px;
            margin: 4px 0 12px 0;
        }



        .modal-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
            justify-content: center;
        }

        #evaluationModal .modal-buttons {
            fl14-direction: column;
            gap: 18px;
            ng: 10px 24 width: 100%;
            px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 20px;
            transition: all 0.2s ease;
            width: 100%;
        }

        #evaluationModal .modal-btn {
            width: 100%;
            padding: 20px 30px;
            font-size: 18px;
            border-radius: 12px;
            min-height: 60px;
        }

        .modal-btn-delete {
            background: #ef4444;
            color: white;
        }

        .modal-btn-delete:hover {
            background: #dc2626;
        }

        .modal-btn-cancel {
            background: #f3f4f6;
            color: #6b7280;
            border: 1px solid #e5e7eb;
        }

        .modal-btn-cancel:hover {
            background: #e5e7eb;
            color: #374151;
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
                📊
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

    <div class="container">
        <main class="main-content">
            <header class="main-header">
                <h1>ข้อมูลผู้ป่วย</h1>

                <div class="header-dashboard">
                    <a href="dashboard.php" class="btn-add-container">
                        <button class="btn-add-patient">
                            <span class="plus-icon">📊</span> Dashboard
                        </button>
                    </a>
                </div>
                <div class="header-actions">
                    <a href="add.php" class="btn-add-container">
                        <button class="btn-add-patient">
                            <span class="plus-icon">+</span> เพิ่มข้อมูลผู้ป่วย
                        </button>
                    </a>

                </div>
            </header>

            <form class="search-bar" method="get" action="index.php">
                <div class="search-container">
                    <input type="text" name="q" placeholder="ค้นหา HN หรือชื่อผู้ป่วย..."
                        value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
                <button type="submit" class="btn-search">
                    <span>ค้นหา</span>
                </button>
                <?php if ($search_query !== ''): ?>
                    <button type="button" class="btn-clear-search" onclick="window.location.href='index.php';">
                        <span>✕</span> ล้าง
                    </button>
                <?php endif; ?>
            </form>

            <div class="patient-table-container">
                <table class="patient-table">
                    <thead>
                        <tr>
                            <th>ลำดับ</th>
                            <th>วันที่เยี่ยม</th>
                            <th>HN</th>
                            <th>ชื่อ-สกุล</th>
                            <th>เพศ</th>
                            <th>ที่อยู่</th>
                            <th>อาศัยอยู่กับ</th>
                            <th>ครั้งที่ประเมิน</th>
                            <th>ครั้งที่เยี่ยมบ้าน</th>
                            <th style="text-align: center;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        foreach ($patients as $patient):
                            ?>
                            <tr>
                            <td><?php echo $i++; ?></td>
                            <td><?php echo htmlspecialchars($patient['last_visit_date'] ?? ''); ?></td>
                                <td><strong><?php echo htmlspecialchars($patient['hn']); ?></strong></td>
                                <td><?php echo htmlspecialchars($patient['fullname'] ?? ($patient['name'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars($patient['gender'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($patient['address'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($patient['live_with'] ?? ($patient['living_with'] ?? '')); ?>
                                </td>
                            <td><?php echo isset($patient['assessment_count']) && $patient['assessment_count'] !== null ? htmlspecialchars($patient['assessment_count']) : '0'; ?></td>
                            <td><?php echo isset($patient['visit_count']) && $patient['visit_count'] !== null ? htmlspecialchars($patient['visit_count']) : '0'; ?></td>
                                <td style="text-align: center;">
                                    <a
                                        href="patient_overview.php?patient_id=<?php echo $patient['id']; ?>&hn=<?php echo $patient['hn']; ?>">
                                        <button class="btn-action btn-view">ดู</button>
                                    </a>
                                    <button class="btn-action btn-evaluate" data-hn="<?php echo $patient['hn']; ?>"
                                        data-fullname="<?php echo htmlspecialchars($patient['fullname'] ?? $patient['name']); ?>"
                                        data-id="<?php echo $patient['id']; ?>">ประเมิน</button>
                                    <a href="edit_patient.php?id=<?php echo $patient['id']; ?>">
                                        <button class="btn-action edit">แก้ไข</button>
                                    </a>
                                    <button class="btn-action delete"
                                        onclick="confirmDelete(<?php echo $patient['id']; ?>, '<?php echo htmlspecialchars($patient['fullname'] ?? $patient['name'] ?? 'ผู้ป่วย'); ?>')">ลบ</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <div id="evaluationModal" class="modal">
        <div class="modal-content">
            <h2 style="color: var(--primary-teal);">เลือกประเภทการประเมิน</h2>
            <p id="modalPatientName" style="font-weight: bold; color: #666;"></p>
            <div class="modal-buttons">
                <button class="modal-btn" style="background: #10b981; color: white;"
                    onclick="selectEvaluation('visit_type')">📋 ประเมิน</button>
                <button class="modal-btn" style="background: #f44336; color: white;"
                    onclick="closeModal()">ยกเลิก</button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
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

        // เปิด Modal (เฉพาะปุ่มประเมินในตารางเท่านั้น)
        document.querySelectorAll('.btn-evaluate').forEach(btn => {
            btn.addEventListener('click', function () {
                currentPatient = {
                    hn: this.dataset.hn,
                    fullname: this.dataset.fullname,
                    id: this.dataset.id
                };
                document.getElementById('modalPatientName').innerText = "ผู้ป่วย: " + currentPatient.fullname;
                document.getElementById('evaluationModal').style.display = 'flex';
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
                prefill: '0' // ไม่ต้องโหลดค่าก่อนหน้า
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

        // ฟังก์ชันยืนยันการลบผู้ป่วย
        let deletePatientId = null;

        function confirmDelete(patientId, patientName) {
            deletePatientId = patientId;
            document.getElementById('deletePatientName').textContent = '"' + patientName + '"';
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
            deletePatientId = null;
        }

        function executeDelete() {
            if (deletePatientId) {
                // สร้าง form เพื่อส่ง POST request
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'delete_patient.php';

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'id';
                input.value = deletePatientId;

                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // ตั้งค่า event listener สำหรับปุ่มลบ
        document.getElementById('confirmDeleteBtn').addEventListener('click', executeDelete);

        // Focus ที่ช่องค้นหาเมื่อโหลดหน้า
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.querySelector('input[name="q"]');
            if (searchInput) {
                searchInput.focus();
            }
        });
    </script>
</body>

</html>