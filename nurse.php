
<?php
include_once 'helpers.php';
require_login();
require_role('nurse');
include_once 'db.php';
include_once 'clinic_schema.php';
include_once 'ui_theme.php';

$province = $_SESSION['province'] ?? '';
$city     = $_SESSION['city'] ?? '';
$facility = $_SESSION['facility'] ?? '';
$currentRole = $_SESSION['role'] ?? 'nurse';
$facilityId = cs_facility_id($conn, $province, $city, $facility);

$theme = get_ui_theme($province);
$key  = strtolower(str_replace(' ', '', $province));
$logo = "assets/emblems/$key.png";

$allowedTabs = ['dashboard','patients','announcements','reports'];
$tab = $_GET['tab'] ?? 'dashboard';
if (!in_array($tab, $allowedTabs, true)) {
    $tab = 'dashboard';
}

$announcementsExists = cs_announcements_supported($conn);
$emergencyTriageSupported = cs_emergency_triage_supported($conn);
$emergencyTypes = cs_emergency_types();

if (isset($_POST['action']) && $_POST['action'] === 'flag_emergency') {
    $pid = (int)($_POST['patient_id'] ?? 0);
    $emergencyType = trim($_POST['emergency_type'] ?? '');
    $details = trim($_POST['emergency_details'] ?? '');
    $uid = (int)($_SESSION['user_id'] ?? 0);
    if ($pid > 0 && $emergencyType !== '' && $details !== '' && cs_emergency_triage_supported($conn)) {
        cs_add_emergency_alert($conn, $province, $city, $facility, $uid, $pid, $emergencyType, $details, 'Nurse');
    }
    header('Location: nurse.php?tab=patients&emergency=1');
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'save_vitals') {
    $pid = (int)($_POST['patient_id'] ?? 0);
    if ($pid > 0) {
        cs_update_patient($conn, $facilityId, $pid, [
            'bp' => trim($_POST['bp'] ?? ''),
            'temp' => trim($_POST['temp'] ?? ''),
            'pulse' => trim($_POST['pulse'] ?? ''),
            'weight' => trim($_POST['weight'] ?? ''),
            'notes' => trim($_POST['notes'] ?? ''),
            'status' => 'Waiting_Doctor',
            'department' => 'Doctor',
        ]);
    }
    header('Location: nurse.php?tab=patients&ok=1');
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'add_announcement' && $announcementsExists) {
    $msg = trim($_POST['message'] ?? '');
    $uid = (int)($_SESSION['user_id'] ?? 0);
    if ($uid > 0 && $msg !== '') {
        cs_add_announcement($conn, $province, $city, $facility, $uid, trim($_POST['role_target'] ?? 'All'), $msg);
    }
    header('Location: nurse.php?tab=announcements');
    exit;
}

$statusData = cs_patient_status_counts($conn, $facilityId);
[$hourLabels, $hourCounts] = cs_hourly_patient_counts($conn, $facilityId);
$totalNurse = cs_count_patients($conn, $facilityId, " AND p.status = 'With Nurse' ");
$waitingDoctor = cs_count_patients($conn, $facilityId, " AND p.status = 'Waiting_Doctor' ");
$withDoctor = cs_count_patients($conn, $facilityId, " AND p.status IN ('With Doctor','Consulting') ");
$totalPatients = cs_count_patients($conn, $facilityId);
$completedCount = cs_count_patients($conn, $facilityId, " AND p.status = 'Completed' ");
$waitingPharmacy = cs_count_patients($conn, $facilityId, " AND p.status = 'Waiting_Pharmacy' ");
$nurseQueueCount = $totalNurse + $waitingDoctor;
$inClinicalCount = $totalNurse + $waitingDoctor + $withDoctor;

$reportPatients = cs_fetch_patients($conn, $facilityId, ['order' => 'full_name ASC']);
$reportNursePatients = cs_fetch_patients($conn, $facilityId, [
    'status_in' => ['With Nurse','Waiting_Doctor','With Doctor','Consulting','Waiting_Pharmacy','Completed'],
    'order' => 'FIELD(status,\'With Nurse\',\'Waiting_Doctor\',\'With Doctor\',\'Consulting\',\'Waiting_Pharmacy\',\'Completed\'), full_name ASC',
]);

$departmentCounts = [];
foreach ($reportPatients as $rp) {
    $dept = $rp['department'] ?? 'Nurse';
    $departmentCounts[$dept] = ($departmentCounts[$dept] ?? 0) + 1;
}

$vitalsCapturedCount = count(array_filter($reportPatients, function ($p) {
    return !empty($p['bp']) || !empty($p['temp']) || !empty($p['pulse']) || !empty($p['weight']);
}));

$reportDate = date('l, d F Y');
$reportGeneratedAt = date('H:i');

$search = trim($_GET['search'] ?? '');
$patientFilter = $_GET['filter'] ?? 'With Nurse';
$statusIn = ($patientFilter === 'All')
    ? ['With Nurse','Waiting_Doctor','With Doctor','Consulting']
    : [$patientFilter];

$patientRows = cs_fetch_patients($conn, $facilityId, [
    'search' => $search,
    'status_in' => $statusIn,
    'order' => 'id ASC',
]);

$activityRows = cs_fetch_patients($conn, $facilityId, [
    'status_in' => ['With Nurse','Waiting_Doctor','With Doctor','Consulting'],
    'limit' => 8,
    'order' => 'id DESC',
]);

$announcementRows = cs_fetch_announcements($conn, $province, $city, $facility, $currentRole, ['limit' => 20]);
$latestAnnouncementId = 0;
foreach ($announcementRows as $annRow) {
    $aid = (int)($annRow['announcement_id'] ?? 0);
    if ($aid > $latestAnnouncementId) {
        $latestAnnouncementId = $aid;
    }
}

$roleLabel = cs_role_label($currentRole);
$userName = $_SESSION['name'] ?? 'Nurse';
$userInitials = strtoupper(substr($userName, 0, 1));

$navItems = [
    'dashboard' => ['icon' => '📊', 'label' => 'Dashboard'],
    'patients' => ['icon' => '👥', 'label' => 'Patients'],
    'announcements' => ['icon' => '📢', 'label' => 'Announcements'],
    'reports' => ['icon' => '📈', 'label' => 'Reports'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nurse Dashboard</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php include __DIR__ . '/views/staff_styles.php'; ?>
<?php include __DIR__ . '/views/staff_dashboard_styles.php'; ?>
</head>
<body class="staff-app">

<div id="sidebarOverlay" class="sidebar-overlay" onclick="closeSidebar()"></div>

<aside id="staffSidebar" class="staff-sidebar">
    <div>
        <div class="brand">
            <?php if (file_exists(__DIR__ . '/' . $logo)): ?>
            <img src="<?= cs_h($logo) ?>" alt="">
            <?php endif; ?>
            <div>
                <div class="brand-title">SA Health System</div>
                <div class="brand-sub"><?= cs_h($province) ?></div>
            </div>
        </div>
        <nav class="staff-nav">
            <?php foreach ($navItems as $k => $item): ?>
            <a href="nurse.php?tab=<?= $k ?>" class="<?= $tab === $k ? 'active' : '' ?>">
                <span class="nav-icon"><?= $item['icon'] ?></span>
                <?= $item['label'] ?>
            </a>
            <?php endforeach; ?>
            <a href="logout.php" class="nav-logout">
                <span class="nav-icon">🚪</span>
                Logout
            </a>
        </nav>
    </div>
    <div class="user-panel">
        Logged in as
        <strong><?= cs_h($userName) ?></strong>
        <?= cs_h($roleLabel) ?> · <?= cs_h($facility) ?>
    </div>
</aside>

<div class="staff-main">
    <header class="staff-header">
        <div class="header-left">
            <button type="button" class="menu-btn" onclick="toggleMenu()">☰</button>
            <span class="system-title">SA HEALTH DATABASE SYSTEM</span>
        </div>
        <div class="header-right">
            <div class="header-user">
                <?php if (file_exists(__DIR__ . '/' . $logo)): ?>
                <img src="<?= cs_h($logo) ?>" class="header-emblem" alt="">
                <?php endif; ?>
                <div>
                    <div class="user-name"><?= cs_h($userName) ?></div>
                    <div class="user-role"><?= cs_h($roleLabel) ?></div>
                </div>
                <div class="avatar"><?= cs_h($userInitials) ?></div>
            </div>
            <a href="logout.php" class="btn-logout">🚪 Logout</a>
        </div>
    </header>

    <div class="staff-content">

<?php if ($tab === 'dashboard'): ?>

        <div class="dash-hero">
            <div>
                <h2>Welcome, <?= cs_h($userName) ?></h2>
                <p class="location">
                    <span>📍 <?= cs_h($province) ?></span>
                    <span>•</span>
                    <span><strong><?= cs_h($city) ?></strong></span>
                    <span>•</span>
                    <span><?= cs_h($facility) ?></span>
                </p>
            </div>
            <div class="hero-actions">
                <a href="nurse.php?tab=patients" class="hero-btn primary">Capture Vitals</a>
                <a href="nurse.php?tab=patients&filter=With+Nurse" class="hero-btn">View Queue</a>
            </div>
        </div>

        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon nurse">🩺</div>
                <div>
                    <h4>With Nurse</h4>
                    <div class="kpi-value"><?= (int)$totalNurse ?></div>
                    <div class="kpi-sub">Awaiting vitals capture</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon waiting">⏳</div>
                <div>
                    <h4>Waiting Doctor</h4>
                    <div class="kpi-value"><?= (int)$waitingDoctor ?></div>
                    <div class="kpi-sub">Sent to doctor queue</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon doctor">👨‍⚕️</div>
                <div>
                    <h4>With Doctor</h4>
                    <div class="kpi-value"><?= (int)$withDoctor ?></div>
                    <div class="kpi-sub">Currently consulting</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon total">📋</div>
                <div>
                    <h4>Total Patients</h4>
                    <div class="kpi-value"><?= (int)$totalPatients ?></div>
                    <div class="kpi-sub">Registered at facility</div>
                </div>
            </div>
        </div>

        <div class="dash-charts">
            <div class="dash-panel">
                <div class="dash-panel-head">
                    <h3>Patient Distribution</h3>
                    <span class="panel-tag">By status</span>
                </div>
                <div class="chart-wrap"><canvas id="chart"></canvas></div>
            </div>
            <div class="dash-panel">
                <div class="dash-panel-head">
                    <h3>Flow Today</h3>
                    <span class="panel-tag">Hourly</span>
                </div>
                <div class="chart-wrap"><canvas id="lineChart"></canvas></div>
            </div>
            <div class="dash-panel activity-panel">
                <div class="dash-panel-head">
                    <h3>Recent Activity</h3>
                    <span class="panel-tag">Live</span>
                </div>
                <?php if (count($activityRows) > 0): ?>
                <ul class="activity-list">
                    <?php foreach ($activityRows as $a): ?>
                    <li class="activity-item">
                        <span class="activity-dot"></span>
                        <div class="activity-info">
                            <strong><?= cs_h($a['full_name']) ?></strong>
                            <span><?= cs_h($a['status']) ?></span>
                        </div>
                        <span class="badge <?= cs_badge_class($a['status']) ?>"><?= cs_h($a['status']) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <div class="activity-empty">
                    <div class="empty-icon">📭</div>
                    <p>No active patients in queue</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($announcementsExists): ?>
        <div class="dash-panel" style="margin-top:24px;">
            <div class="dash-panel-head">
                <h3>Facility Announcements</h3>
                <span class="live-badge"><span class="live-dot"></span> Live</span>
            </div>
            <div id="liveAnnouncementFeed" class="announcement-feed" data-latest-id="<?= (int)$latestAnnouncementId ?>">
                <?php if (count($announcementRows) === 0): ?>
                <div class="activity-empty">
                    <div class="empty-icon">📢</div>
                    <p>No announcements yet for your facility.</p>
                </div>
                <?php else: ?>
                <?php foreach ($announcementRows as $ann): ?>
                <div class="announcement-item" data-id="<?= (int)($ann['announcement_id'] ?? 0) ?>">
                    <strong><?= cs_h($ann['poster_name'] ?? 'Staff') ?></strong>
                    <span class="badge other"><?= cs_h($ann['role_target'] ?? 'All') ?></span>
                    <p><?= nl2br(cs_h($ann['message'])) ?></p>
                    <div class="ann-meta">
                        <span><?= cs_h($facility) ?></span>
                        <span><?= cs_h($ann['created_at'] ?? '') ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

<?php endif; ?>

<?php if ($tab === 'patients'): ?>

        <h2 class="section-title">Patient Management</h2>

        <div class="dash-panel patients-panel">
            <?php if (isset($_GET['ok'])): ?>
            <div class="alert-success">Vitals saved successfully. Patient sent to doctor.</div>
            <?php endif; ?>
            <?php if (isset($_GET['emergency'])): ?>
            <div class="alert-success">Emergency alert sent to reception.</div>
            <?php endif; ?>

            <form method="GET" class="filterBar">
                <input type="hidden" name="tab" value="patients">
                <input type="text" name="search" value="<?= cs_h($search) ?>" placeholder="Search by name or ID...">
                <select name="filter">
                    <?php foreach (['With Nurse','Waiting_Doctor','With Doctor','All'] as $f): ?>
                    <option <?= $patientFilter === $f ? 'selected' : '' ?>><?= $f ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn">Apply Filter</button>
            </form>

            <div class="table-wrap">
                <table class="patients-table">
                    <thead>
                        <tr>
                            <th>Patient Name</th>
                            <th>ID Number</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (count($patientRows) === 0): ?>
                        <tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:30px;">No patients found for this filter.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($patientRows as $p): ?>
                        <tr onclick="loadPatient(<?= (int)$p['id'] ?>, <?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)" style="cursor:pointer;">
                            <td><strong><?= cs_h($p['full_name']) ?></strong></td>
                            <td><?= cs_h($p['id_number']) ?></td>
                            <td><span class="badge <?= cs_badge_class($p['status']) ?>"><?= cs_h($p['status']) ?></span></td>
                            <td class="smallMuted">Select →</td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div id="captureBox" class="formBox vitals-form">
                <form method="POST">
                    <input type="hidden" name="action" value="save_vitals">
                    <input type="hidden" name="patient_id" id="patient_id">
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
                        <h3 id="patientTitle" style="margin:0;">Capture Vitals</h3>
                        <a href="#" id="history_link" class="btn secondary small" style="display:none;text-decoration:none;">Medical Record</a>
                    </div>
                    <div class="vitals-grid">
                        <input name="bp" id="bp" placeholder="Blood Pressure (e.g. 120/80)" required>
                        <input name="temp" id="temp" placeholder="Temperature (°C)" required>
                        <input name="pulse" id="pulse" placeholder="Pulse (bpm)">
                        <input name="weight" id="weight" placeholder="Weight (kg)">
                        <textarea name="notes" id="notes" placeholder="Nurse notes and observations..."></textarea>
                    </div>
                    <button class="btn">Save Vitals → Send to Doctor</button>
                </form>

                <?php if ($emergencyTriageSupported): ?>
                <form method="POST" class="emergency-flag-form">
                    <input type="hidden" name="action" value="flag_emergency">
                    <input type="hidden" name="patient_id" id="emergency_patient_id">
                    <h4>🚨 Flag Emergency → Alert Reception</h4>
                    <p class="muted">Use when a patient needs urgent attention. Reception will coordinate MediAlert to relevant staff.</p>
                    <select name="emergency_type" id="emergency_type" required>
                        <option value="">Select emergency type</option>
                        <?php foreach ($emergencyTypes as $et): ?>
                        <?php if ($et !== 'Critical referral'): ?>
                        <option value="<?= cs_h($et) ?>"><?= cs_h($et) ?></option>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <textarea name="emergency_details" id="emergency_details" rows="3" placeholder="Describe symptoms, vitals, and immediate concerns..." required></textarea>
                    <button class="btn emergency-btn" type="submit">Send Emergency Alert to Reception</button>
                </form>
                <?php endif; ?>
            </div>
        </div>

<?php endif; ?>

<?php if ($tab === 'announcements'): ?>

        <h2 class="section-title">Facility Announcements</h2>

        <div class="announcements-layout">
        <div class="dash-panel">
            <?php if ($announcementsExists): ?>
            <div class="dash-panel-head">
                <h3>Post Announcement</h3>
                <span class="panel-tag"><?= cs_h($facility) ?></span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_announcement">
                <select name="role_target">
                    <option>All</option>
                    <option>Nurse</option>
                    <option>Doctor</option>
                    <option>Reception</option>
                    <option>Pharmacist</option>
                </select>
                <textarea name="message" rows="4" placeholder="Share an update with staff at your facility..." required></textarea>
                <button class="btn">Post Announcement</button>
            </form>
            <?php else: ?>
            <p style="color:#94a3b8;">Announcements are not available for this facility.</p>
            <?php endif; ?>
        </div>

        <div class="dash-panel">
            <div class="dash-panel-head">
                <h3>Live Feed</h3>
                <span class="live-badge"><span class="live-dot"></span> Auto-updating</span>
            </div>
            <div id="liveAnnouncementFeed" class="announcement-feed" data-latest-id="<?= (int)$latestAnnouncementId ?>">
                <?php if (!$announcementsExists): ?>
                <p style="color:#94a3b8;">Announcements table not available.</p>
                <?php elseif (count($announcementRows) === 0): ?>
                <div class="activity-empty">
                    <div class="empty-icon">📢</div>
                    <p>No announcements yet for your facility.</p>
                </div>
                <?php else: ?>
                <?php foreach ($announcementRows as $ann): ?>
                <div class="announcement-item" data-id="<?= (int)($ann['announcement_id'] ?? 0) ?>">
                    <strong><?= cs_h($ann['poster_name'] ?? 'Staff') ?></strong>
                    <span class="badge other"><?= cs_h($ann['role_target'] ?? 'All') ?></span>
                    <p><?= nl2br(cs_h($ann['message'])) ?></p>
                    <div class="ann-meta">
                        <span><?= cs_h($facility) ?></span>
                        <span><?= cs_h($ann['created_at'] ?? '') ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        </div>

<?php endif; ?>

<?php if ($tab === 'reports'): ?>

        <div class="dash-hero reports-hero">
            <div>
                <h2>Nursing Station Report</h2>
                <p class="location">
                    <span>📍 <?= cs_h($province) ?></span>
                    <span>•</span>
                    <span><strong><?= cs_h($city) ?></strong></span>
                    <span>•</span>
                    <span><?= cs_h($facility) ?></span>
                    <span>•</span>
                    <span>Nursing Desk</span>
                </p>
                <div class="reports-actions">
                    <button class="hero-btn primary" type="button" onclick="window.print()">🖨 Print Report</button>
                    <a class="hero-btn" href="nurse.php?tab=reports">↻ Refresh</a>
                </div>
            </div>
            <div class="reports-meta">
                <strong><?= cs_h($reportDate) ?></strong>
                Generated at <?= cs_h($reportGeneratedAt) ?><br>
                Prepared by <?= cs_h($userName) ?>
            </div>
        </div>

        <div class="kpi-grid wide">
            <div class="kpi-card">
                <div class="kpi-icon nurse">🩺</div>
                <div>
                    <h4>With Nurse</h4>
                    <div class="kpi-value"><?= (int)$totalNurse ?></div>
                    <div class="kpi-sub">Awaiting vitals capture</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon waiting">⏳</div>
                <div>
                    <h4>Waiting Doctor</h4>
                    <div class="kpi-value"><?= (int)$waitingDoctor ?></div>
                    <div class="kpi-sub">Vitals captured, in doctor queue</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon doctor">👨‍⚕️</div>
                <div>
                    <h4>With Doctor</h4>
                    <div class="kpi-value"><?= (int)$withDoctor ?></div>
                    <div class="kpi-sub">Currently consulting</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon total">📋</div>
                <div>
                    <h4>Nurse Queue</h4>
                    <div class="kpi-value"><?= (int)$nurseQueueCount ?></div>
                    <div class="kpi-sub">With nurse + waiting doctor</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon nurse">💉</div>
                <div>
                    <h4>Vitals on File</h4>
                    <div class="kpi-value"><?= (int)$vitalsCapturedCount ?></div>
                    <div class="kpi-sub">Patients with recorded vitals</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon total">✅</div>
                <div>
                    <h4>Completed Visits</h4>
                    <div class="kpi-value"><?= (int)$completedCount ?></div>
                    <div class="kpi-sub">Pharmacy queue: <?= (int)$waitingPharmacy ?></div>
                </div>
            </div>
        </div>

        <div class="reports-grid">
            <div class="dash-panel">
                <div class="report-card-head">
                    <h3>Patient Status Breakdown</h3>
                    <span class="panel-tag">Live snapshot</span>
                </div>
                <div class="chart-box tall"><canvas id="reportStatusChart"></canvas></div>
            </div>
            <div class="dash-panel">
                <div class="report-card-head">
                    <h3>Hourly Patient Flow</h3>
                    <span class="panel-tag">Today</span>
                </div>
                <div class="chart-box tall"><canvas id="reportFlowChart"></canvas></div>
            </div>
        </div>

        <div class="reports-grid">
            <div class="dash-panel">
                <div class="report-card-head">
                    <h3>Status Summary</h3>
                    <span class="panel-tag"><?= count($statusData) ?> categories</span>
                </div>
                <div class="table-wrap" style="margin-top:0;border:none;">
                    <table class="summary-table">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Count</th>
                                <th>% of Total</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($statusData)): ?>
                            <tr><td colspan="3" class="smallMuted">No patient status data available.</td></tr>
                        <?php else: ?>
                            <?php foreach ($statusData as $st => $cnt):
                                $pct = $totalPatients > 0 ? round(($cnt / $totalPatients) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td><span class="badge <?= cs_badge_class($st) ?>"><?= cs_h($st) ?></span></td>
                                <td><strong><?= (int)$cnt ?></strong></td>
                                <td><?= $pct ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="dash-panel">
                <div class="report-card-head">
                    <h3>Department Load</h3>
                    <span class="panel-tag">Current distribution</span>
                </div>
                <?php if (empty($departmentCounts)): ?>
                    <p class="smallMuted">No department data available.</p>
                <?php else: ?>
                    <?php foreach ($departmentCounts as $dept => $cnt):
                        $pct = $totalPatients > 0 ? round(($cnt / $totalPatients) * 100) : 0;
                    ?>
                    <div class="progress-row">
                        <div class="progress-label">
                            <span><?= cs_h($dept) ?></span>
                            <span><?= (int)$cnt ?> (<?= $pct ?>%)</span>
                        </div>
                        <div class="progress-track">
                            <div class="progress-fill" style="width:<?= max(4, $pct) ?>%;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="dash-panel">
            <div class="report-card-head">
                <h3>Clinical Pathway — Vitals Register</h3>
                <span class="panel-tag"><?= count($reportNursePatients) ?> patients</span>
            </div>
            <div class="table-wrap" style="margin-top:0;border:none;">
                <table class="summary-table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>ID Number</th>
                            <th>BP</th>
                            <th>Temp</th>
                            <th>Pulse</th>
                            <th>Weight</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($reportNursePatients)): ?>
                        <tr><td colspan="7" class="smallMuted">No patients in the clinical pathway.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reportNursePatients as $rp): ?>
                        <tr>
                            <td><strong><?= cs_h($rp['full_name']) ?></strong></td>
                            <td><?= cs_h($rp['id_number']) ?></td>
                            <td><?= cs_h($rp['bp'] ?: '—') ?></td>
                            <td><?= cs_h($rp['temp'] ?: '—') ?></td>
                            <td><?= cs_h($rp['pulse'] ?: '—') ?></td>
                            <td><?= cs_h($rp['weight'] ?: '—') ?></td>
                            <td><span class="badge <?= cs_badge_class($rp['status']) ?>"><?= cs_h($rp['status']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="report-footer">
            Official nursing station report for <strong><?= cs_h($facility) ?></strong>, <?= cs_h($city) ?>, <?= cs_h($province) ?>.
            This document reflects live vitals capture, patient queue, and clinical pathway data for nursing review.
        </div>

<?php endif; ?>

    </div>
</div>

<script>
function isMobileSidebar() {
    return window.innerWidth <= 768;
}

function toggleMenu() {
    if (isMobileSidebar()) {
        document.body.classList.toggle('sidebar-open');
        document.body.classList.remove('sidebar-collapsed');
    } else {
        document.body.classList.toggle('sidebar-collapsed');
        document.body.classList.remove('sidebar-open');
    }
}

function closeSidebar() {
    document.body.classList.remove('sidebar-open', 'sidebar-collapsed');
}

document.querySelectorAll('.staff-nav a').forEach(function(link) {
    link.addEventListener('click', function() {
        if (isMobileSidebar()) closeSidebar();
    });
});

window.addEventListener('resize', function() {
    if (!isMobileSidebar()) {
        document.body.classList.remove('sidebar-open');
    }
});

function loadPatient(id, row) {
    document.getElementById('captureBox').classList.add('show');
    document.getElementById('patient_id').value = id;
    var emergencyPid = document.getElementById('emergency_patient_id');
    if (emergencyPid) emergencyPid.value = id;
    document.getElementById('patientTitle').innerText = 'Vitals — ' + row.full_name;
    var historyLink = document.getElementById('history_link');
    var nurseActive = ['With Nurse', 'Waiting_Doctor', 'With Doctor', 'Consulting', 'Waiting_Pharmacy'];
    if (historyLink) {
        if (nurseActive.indexOf(row.status || '') !== -1) {
            historyLink.style.display = 'inline-flex';
            historyLink.href = 'patient_history.php?patient_id=' + id;
        } else {
            historyLink.style.display = 'none';
        }
    }
    document.getElementById('bp').value = row.bp || '';
    document.getElementById('temp').value = row.temp || '';
    document.getElementById('pulse').value = row.pulse || '';
    document.getElementById('weight').value = row.weight || '';
    document.getElementById('notes').value = row.notes || '';
    document.getElementById('captureBox').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

const chartColors = {
    primary: '<?= $theme['primary'] ?>',
    accent: '<?= $theme['accent'] ?>',
    palette: ['#16a085','#2563eb','#f59e0b','#8b5cf6','#ef4444','#64748b','#06b6d4','#ec4899']
};

const chartDefaults = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            labels: { font: { family: 'Inter', size: 11, weight: '600' }, padding: 14, usePointStyle: true }
        }
    }
};

<?php if ($tab === 'dashboard'): ?>
new Chart(document.getElementById('chart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_keys($statusData)) ?>,
        datasets: [{
            label: 'Patients',
            data: <?= json_encode(array_values($statusData)) ?>,
            backgroundColor: chartColors.palette,
            borderWidth: 0
        }]
    },
    options: {
        ...chartDefaults,
        cutout: '62%',
        plugins: {
            ...chartDefaults.plugins,
            legend: { position: 'bottom' }
        }
    }
});
<?php endif; ?>

<?php if ($tab === 'reports'): ?>
if (document.getElementById('reportStatusChart')) {
    new Chart(document.getElementById('reportStatusChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_keys($statusData)) ?>,
            datasets: [{
                label: 'Patient Count',
                data: <?= json_encode(array_values($statusData)) ?>,
                backgroundColor: chartColors.palette,
                borderRadius: 8,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 } },
                x: { grid: { display: false } }
            }
        }
    });
}

if (document.getElementById('reportFlowChart')) {
    new Chart(document.getElementById('reportFlowChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode($hourLabels) ?>,
            datasets: [{
                label: 'Patient Arrivals',
                data: <?= json_encode($hourCounts) ?>,
                borderColor: chartColors.accent,
                backgroundColor: chartColors.accent + '22',
                borderWidth: 2.5,
                pointBackgroundColor: chartColors.accent,
                pointRadius: 4,
                fill: true,
                tension: 0.35
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: true, position: 'top' }
            },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 } },
                x: { grid: { display: false } }
            }
        }
    });
}
<?php endif; ?>

<?php if ($tab === 'dashboard'): ?>
new Chart(document.getElementById('lineChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($hourLabels) ?>,
        datasets: [{
            label: 'Patients',
            data: <?= json_encode($hourCounts) ?>,
            borderColor: chartColors.accent,
            backgroundColor: chartColors.accent + '22',
            borderWidth: 2.5,
            pointBackgroundColor: chartColors.accent,
            pointRadius: 4,
            pointHoverRadius: 6,
            fill: true,
            tension: 0.35
        }]
    },
    options: {
        ...chartDefaults,
        plugins: {
            ...chartDefaults.plugins,
            legend: { display: true, position: 'top' }
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { font: { family: 'Inter', size: 11 } }
            },
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1, font: { family: 'Inter', size: 11 } },
                grid: { color: '#f1f5f9' }
            }
        }
    }
});
<?php endif; ?>
</script>
<script>
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

function renderAnnouncementItem(item) {
    const msg = escapeHtml(item.message).replace(/\n/g, '<br>');
    return `
        <div class="announcement-item" data-id="${item.id}">
            <strong>${escapeHtml(item.poster_name)}</strong>
            <span class="badge other">${escapeHtml(item.role_target)}</span>
            <p>${msg}</p>
            <div class="ann-meta">
                <span><?= cs_h($facility) ?></span>
                <span>${escapeHtml(item.created_at)}</span>
            </div>
        </div>
    `;
}

function pollAnnouncements() {
    const feed = document.getElementById('liveAnnouncementFeed');
    if (!feed) return;

    const latestId = parseInt(feed.dataset.latestId || '0', 10);
    fetch('fetch_announcements.php?since_id=' + latestId + '&limit=20', { credentials: 'same-origin' })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (!data || !data.ok) return;
            if (latestId === 0 && Array.isArray(data.items) && data.items.length > 0) {
                feed.innerHTML = data.items.map(renderAnnouncementItem).join('');
            } else if (Array.isArray(data.items) && data.items.length > 0) {
                data.items.slice().reverse().forEach(function(item) {
                    if (feed.querySelector('[data-id="' + item.id + '"]')) return;
                    feed.insertAdjacentHTML('afterbegin', renderAnnouncementItem(item));
                });
            }
            if (data.latest_id && data.latest_id > latestId) {
                feed.dataset.latestId = String(data.latest_id);
            }
        })
        .catch(function() {});
}

if (document.getElementById('liveAnnouncementFeed')) {
    setInterval(pollAnnouncements, 30000);
}
</script>
</body>
</html>
