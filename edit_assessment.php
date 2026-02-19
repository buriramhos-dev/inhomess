<?php
require_once __DIR__ . '/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$assessment = null;

if ($id > 0) {
    $assessment = db_get_assessment_by_id($id);
}

if (!$assessment) {
    header('Location: index.php');
    exit;
}

// ดึงข้อมูลจาก assessment
$patient_hn = $assessment['hn'] ?? '';
$patient_fullname = $assessment['fullname'] ?? '';
$patient_id = $assessment['patient_id'] ?? '';
$assess_date = $assessment['assess_date'] ?? date('Y-m-d');
$assessor = $assessment['assessor'] ?? '';
$assessment_data = $assessment['data'] ?? [];
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>แก้ไขการประเมิน INHOMESSS</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .wrap { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
    .back { margin-bottom: 15px; }
    .back a { color: #0f766e; text-decoration: none; font-weight: 500; }
    h1 { color: #0f766e; margin-bottom: 20px; }
    .tabs { display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 20px; border-bottom: 2px solid #ddd; }
    .tab { padding: 10px 15px; background: #f0f0f0; border: none; cursor: pointer; border-radius: 5px 5px 0 0; }
    .tab.active { background: #0f766e; color: white; }
    .panel { display: none; }
    .panel.active { display: block; }
    .patient-info { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; padding: 15px; background: #f9f9f9; border-radius: 5px; }
    .field { display: flex; flex-direction: column; }
    .field label { font-weight: 600; margin-bottom: 5px; color: #333; }
    .field input { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #f0f0f0; font-weight: 600; }
    .section-title { background: #0f766e; color: white; padding: 10px; margin: 20px 0 10px 0; border-radius: 5px; font-weight: 600; }
    textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; min-height: 80px; }
    .actions { text-align: center; margin-top: 30px; }
    .btn-save { background: #4CAF50; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
    .btn-clear { background: #ff9800; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin: 0 10px; }
    .btn-cancel { background: #f44336; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; text-decoration: none; display: inline-block; }
    
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
  <div class="wrap">
    <div class="back"><a href="patient_overview.php<?php 
        $redirectUrl = '';
        if (!empty($patient_id)) {
            $redirectUrl = '?patient_id=' . (int)$patient_id;
        } elseif (!empty($patient_hn)) {
            $redirectUrl = '?hn=' . urlencode($patient_hn);
            if (!empty($patient_fullname)) {
                $redirectUrl .= '&fullname=' . urlencode($patient_fullname);
            }
        }
        echo $redirectUrl;
    ?>">← กลับไปหน้ารายการ</a></div>
    <h1>แก้ไขการประเมิน INHOMESSS</h1>
    
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

    <form action="update_assessment.php" method="post">
      <input type="hidden" name="id" value="<?php echo $assessment['id']; ?>">
      
      <!-- Patient Info (always visible) -->
      <div class="patient-info">
        <div class="field"><label>HN</label><input name="hn" type="text" value="<?php echo htmlspecialchars($patient_hn); ?>" required></div>
        <div class="field"><label>ชื่อ-สกุล</label><input name="fullname" type="text" value="<?php echo htmlspecialchars($patient_fullname); ?>" required></div>
        <div class="field"><label>วันที่ประเมิน</label><input name="assess_date" type="date" value="<?php echo $assess_date; ?>"></div>
        <div class="field"><label>ผู้ประเมิน</label><input name="assessor" type="text" value="<?php echo htmlspecialchars($assessor); ?>"></div>
        <?php if ($patient_id): ?>
        <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
        <?php endif; ?>
      </div>
      
      <?php
      // อ่านไฟล์ inhomess_assessment.php และดึงส่วนฟอร์มออกมา
      $form_file = __DIR__ . '/inhomess_assessment.php';
      $form_content = file_get_contents($form_file);
      
      // แยกส่วนฟอร์ม (ตั้งแต่ <div class="panels"> ถึง </form>)
      preg_match('/<div class="panels">(.*?)<\/form>/s', $form_content, $matches);
      if (isset($matches[1])) {
          echo $matches[1];
      }
      ?>
      
      
    </form>
  </div>

  <script>
    // Tab switching
    document.querySelectorAll('.tab').forEach(tab => {
      tab.addEventListener('click', function() {
        const panelId = this.dataset.panel;
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
        this.classList.add('active');
        const panel = document.getElementById(panelId);
        if (panel) panel.classList.add('active');
        window.scrollTo(0, 0);
      });
    });

    // โหลดข้อมูลเดิมเข้าไปในฟอร์ม
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (!empty($assessment_data)): ?>
        const data = <?php echo json_encode($assessment_data, JSON_UNESCAPED_UNICODE); ?>;
        
        Object.keys(data).forEach(key => {
            const value = data[key];

            // ชื่อฟิลด์ในฟอร์มอาจเป็น key หรือ key[]
            const nameSelectors = [
                `[name="${key}"]`,
                `[name="${key}[]"]`
            ];

            nameSelectors.forEach(sel => {
                const elements = document.querySelectorAll(sel);
                if (!elements.length) return;

                elements.forEach(element => {
                    if (element.type === 'checkbox' || element.type === 'radio') {
                        if (Array.isArray(value)) {
                            value.forEach(val => {
                                const checkboxSelectors = [
                                    `[name="${key}"][value="${val}"]`,
                                    `[name="${key}[]"][value="${val}"]`
                                ];
                                checkboxSelectors.forEach(csel => {
                                    const matching = document.querySelectorAll(csel);
                                    matching.forEach(el => el.checked = true);
                                });
                            });
                        } else if (value === 'yes' || value === '1' || value === true || element.value === String(value)) {
                            element.checked = true;
                        }
                    } else if (element.type !== 'hidden' && element.tagName !== 'BUTTON') {
                        element.value = value || '';
                    }
                });
            });
        });
        
        // คำนวณคะแนน Barthel Index
        if (typeof calculateBarthelCheckboxFull === 'function') {
            calculateBarthelCheckboxFull();
        }
        <?php endif; ?>
    });

    // ฟังก์ชันคำนวณคะแนน Barthel Index
    function calculateBarthelCheckboxFull() {
        const form = document.getElementById('barthelFormCheckboxFull');
        if (!form) return;
        const checkboxes = form.querySelectorAll('input[type="checkbox"]:checked');
        let totalScore = 0;
        checkboxes.forEach(checkbox => {
            totalScore += parseInt(checkbox.value) || 0;
        });
        const scoreElement = document.getElementById('totalScoreCheckboxFull');
        if (scoreElement) scoreElement.textContent = totalScore;
    }

    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('barthelFormCheckboxFull');
        if (form) {
            form.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                checkbox.addEventListener('change', calculateBarthelCheckboxFull);
            });
            calculateBarthelCheckboxFull();
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
            const newUrl = window.location.pathname + (urlParams.get('id') ? '?id=' + urlParams.get('id') : '');
            window.history.replaceState({}, '', newUrl);
        }
    });
  </script>
</body>
</html>
