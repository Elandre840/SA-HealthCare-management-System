
<?php
include_once 'helpers.php';
require_login();
require_role('doctor');
include_once 'db.php';
include_once 'clinic_schema.php';
include_once 'ui_theme.php';

$province = $_SESSION['province'] ?? '';
$city     = $_SESSION['city'] ?? '';
$facility = $_SESSION['facility'] ?? '';
$currentRole = $_SESSION['role'] ?? 'doctor';
$doctorId = (int)($_SESSION['user_id'] ?? 0);
$doctorName = $_SESSION['name'] ?? 'Doctor';
$facilityId = cs_facility_id($conn, $province, $city, $facility);

$theme = get_ui_theme($province);
$key  = strtolower(str_replace(' ', '', $province));
$logo = "assets/emblems/$key.png";

$allowedTabs = ['dashboard','patients','appointments','queue','referrals','announcements','reports'];
$referralsSupported = cs_referrals_supported($conn);
$tab = $_GET['tab'] ?? 'dashboard';
if (!in_array($tab, $allowedTabs, true)) {
    $tab = 'dashboard';
}

$announcementsExists = cs_announcements_supported($conn);
$emergencyTriageSupported = cs_emergency_triage_supported($conn);
$emergencyTypes = cs_emergency_types();

if (isset($_POST['action']) && $_POST['action'] === 'add_announcement' && $announcementsExists) {
    $msg = trim($_POST['message'] ?? '');
    $uid = (int)($_SESSION['user_id'] ?? 0);
    if ($uid > 0 && $msg !== '') {
        cs_add_announcement($conn, $province, $city, $facility, $uid, trim($_POST['role_target'] ?? 'All'), $msg);
    }
    header('Location: doctor.php?tab=announcements');
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'start_consultation') {
    $pid = (int)($_POST['patient_id'] ?? 0);
    if ($pid > 0) {
        cs_update_patient($conn, $facilityId, $pid, [
            'status' => 'With Doctor',
            'department' => 'Doctor',
        ]);
        cs_clear_doctor_medi_alerts_for_patient($conn, $doctorId, $pid, $province, $city, $facility);
    }
    header('Location: doctor.php?tab=patients&started=1');
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'flag_emergency') {
    $pid = (int)($_POST['patient_id'] ?? 0);
    $emergencyType = trim($_POST['emergency_type'] ?? '');
    $details = trim($_POST['emergency_details'] ?? '');
    if ($pid > 0 && $emergencyType !== '' && $details !== '' && cs_emergency_triage_supported($conn)) {
        cs_add_emergency_alert($conn, $province, $city, $facility, $doctorId, $pid, $emergencyType, $details, 'Doctor');
    }
    header('Location: doctor.php?tab=patients&emergency=1');
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'create_referral') {
    $pid = (int)($_POST['patient_id'] ?? 0);
    $toFacilityId = (int)($_POST['referred_to_facility_id'] ?? 0);
    $referralType = trim($_POST['referral_type'] ?? 'Beyond expertise');
    $clinicalSummary = trim($_POST['clinical_summary'] ?? '');
    $details = '[' . $referralType . '] ' . $clinicalSummary;

    if ($pid > 0 && $toFacilityId > 0 && $clinicalSummary !== '') {
        if (cs_add_referral($conn, $facilityId, $pid, $doctorId, $toFacilityId, $details)) {
            cs_clear_doctor_medi_alerts_for_patient($conn, $doctorId, $pid, $province, $city, $facility);
        }

        if (cs_emergency_triage_supported($conn) && stripos($referralType, 'Critical') !== false) {
            $destName = 'another facility';
            $destinations = cs_fetch_referral_destinations($conn, $facilityId);
            foreach ($destinations as $dest) {
                if ((int)$dest['facility_id'] === $toFacilityId) {
                    $destName = trim(($dest['facility_name'] ?? '') . ' — ' . ($dest['city'] ?? '') . ', ' . ($dest['province'] ?? ''));
                    break;
                }
            }
            $alertDetails = "Critical referral submitted to $destName.\n\n$clinicalSummary";
            cs_add_emergency_alert($conn, $province, $city, $facility, $doctorId, $pid, 'Critical referral', $alertDetails, 'Doctor');
        }
    }
    header('Location: doctor.php?tab=patients&referred=1');
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'finish_consultation') {
    $pid = (int)($_POST['patient_id'] ?? 0);
    $sendPharmacy = isset($_POST['send_pharmacy']);
    $status = $sendPharmacy ? 'Waiting_Pharmacy' : 'Completed';
    $dept = $sendPharmacy ? 'Pharmacy' : 'Doctor';

    if ($pid > 0) {
        cs_update_patient($conn, $facilityId, $pid, [
            'status' => $status,
            'department' => $dept,
            'diagnosis' => trim($_POST['diagnosis'] ?? ''),
            'prescription' => trim($_POST['prescription'] ?? ''),
            'notes' => trim($_POST['doctor_notes'] ?? ''),
        ]);
        cs_save_consultation(
            $conn,
            $pid,
            $doctorId,
            trim($_POST['diagnosis'] ?? ''),
            trim($_POST['prescription'] ?? ''),
            trim($_POST['doctor_notes'] ?? ''),
            $facilityId
        );
        cs_clear_doctor_medi_alerts_for_patient($conn, $doctorId, $pid, $province, $city, $facility);
    }
    header('Location: doctor.php?tab=patients&saved=1');
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'queue_update_status') {
    $pid = (int)($_POST['patient_id'] ?? 0);
    $status = trim($_POST['queue_status'] ?? '');
    if ($pid > 0 && $status !== '') {
        $fields = ['status' => $status];
        if ($status === 'With Doctor') $fields['department'] = 'Doctor';
        if ($status === 'Waiting_Pharmacy') $fields['department'] = 'Pharmacy';
        cs_update_patient($conn, $facilityId, $pid, $fields);
    }
    header('Location: doctor.php?tab=queue');
    exit;
}

$statusData = cs_patient_status_counts($conn, $facilityId);
[$hourLabels, $hourCounts] = cs_hourly_patient_counts($conn, $facilityId);
$waitingDoctorCount = cs_count_patients($conn, $facilityId, " AND p.status = 'Waiting_Doctor' ");
$withDoctorCount = cs_count_patients($conn, $facilityId, " AND p.status IN ('With Doctor','Consulting') ");
$waitingPharmacyCount = cs_count_patients($conn, $facilityId, " AND p.status = 'Waiting_Pharmacy' ");
$completedCount = cs_count_patients($conn, $facilityId, " AND p.status = 'Completed' ");
$totalPatients = cs_count_patients($conn, $facilityId);
$todayAppointmentsCount = count(cs_fetch_appointments($conn, $facilityId, [
    'date' => date('Y-m-d'),
    'doctor_id' => $doctorId,
]));

$search = trim($_GET['search'] ?? '');
$patientRows = cs_fetch_patients($conn, $facilityId, [
    'search' => $search,
    'status_in' => cs_doctor_stage_statuses(),
    'order' => "FIELD(status,'With Doctor','Consulting','Waiting_Doctor'), id DESC",
]);

$queueRows = cs_fetch_patients($conn, $facilityId, [
    'status_in' => ['Waiting_Doctor','With Doctor','Consulting','Waiting_Pharmacy','Completed'],
    'order' => "FIELD(status,'With Doctor','Consulting','Waiting_Doctor','Waiting_Pharmacy','Completed'), id ASC",
]);

$filterDate = $_GET['filter_date'] ?? date('Y-m-d');
$apptSearch = trim($_GET['appt_search'] ?? '');
$appointmentRows = cs_fetch_appointments($conn, $facilityId, [
    'date' => $filterDate,
    'search' => $apptSearch,
    'doctor_id' => $doctorId,
]);

$activityRows = cs_fetch_patients($conn, $facilityId, [
    'status_in' => ['Waiting_Doctor','With Doctor','Consulting','Waiting_Pharmacy','Completed'],
    'limit' => 8,
    'order' => 'id DESC',
]);

$referralDestinations = $referralsSupported ? cs_fetch_referral_destinations($conn, $facilityId) : [];
$outgoingReferrals = $referralsSupported ? cs_fetch_facility_referrals($conn, $facilityId, ['direction' => 'outgoing']) : [];
$incomingReferrals = $referralsSupported ? cs_fetch_facility_referrals($conn, $facilityId, ['direction' => 'incoming']) : [];

$doctorMediAlerts = $emergencyTriageSupported
    ? cs_fetch_doctor_medi_alerts($conn, $doctorId, $province, $city, $facility)
    : [];
$doctorMediAlertCount = count($doctorMediAlerts);

$announcementRows = cs_fetch_announcements($conn, $province, $city, $facility, $currentRole, ['limit' => 20]);
$latestAnnouncementId = 0;
foreach ($announcementRows as $annRow) {
    $aid = (int)($annRow['announcement_id'] ?? 0);
    if ($aid > $latestAnnouncementId) {
        $latestAnnouncementId = $aid;
    }
}

$roleLabel = cs_role_label($currentRole);
$userInitials = strtoupper(substr($doctorName, 0, 1));
$reportDate = date('l, d F Y');
$reportGeneratedAt = date('H:i');

$navItems = [
    'dashboard' => ['icon' => '📊', 'label' => 'Dashboard'],
    'patients' => ['icon' => '👥', 'label' => 'Patients'],
    'appointments' => ['icon' => '📅', 'label' => 'Appointments'],
    'queue' => ['icon' => '📋', 'label' => 'Queue'],
    'referrals' => ['icon' => '🏥', 'label' => 'Referrals'],
    'announcements' => ['icon' => '📢', 'label' => 'Announcements'],
    'reports' => ['icon' => '📈', 'label' => 'Reports'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Doctor Dashboard</title>
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
            <a href="doctor.php?tab=<?= $k ?>" class="<?= $tab === $k ? 'active' : '' ?>">
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
        <strong><?= cs_h($doctorName) ?></strong>
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
                    <div class="user-name"><?= cs_h($doctorName) ?></div>
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
                <h2>Welcome, <?= cs_h($doctorName) ?></h2>
                <p class="location">
                    <span>📍 <?= cs_h($province) ?></span>
                    <span>•</span>
                    <span><strong><?= cs_h($city) ?></strong></span>
                    <span>•</span>
                    <span><?= cs_h($facility) ?></span>
                </p>
            </div>
            <div class="hero-actions">
                <a href="doctor.php?tab=patients" class="hero-btn primary">Start Consultation</a>
                <a href="doctor.php?tab=queue" class="hero-btn">View Queue</a>
                <?php if ($emergencyTriageSupported && $doctorMediAlertCount > 0): ?>
                <a href="#doctorMediAlerts" class="hero-btn emergency-btn">🚨 <?= (int)$doctorMediAlertCount ?> MediAlert</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($emergencyTriageSupported && $doctorMediAlertCount > 0): ?>
        <div class="emergency-banner" id="doctorMediAlerts">
            <strong>🚨 <?= (int)$doctorMediAlertCount ?> MediAlert<?= $doctorMediAlertCount === 1 ? '' : 's' ?> from reception</strong>
            <span>Urgent patient cases require your attention. Open consultation to assess and refer if needed.</span>
        </div>
        <div class="dash-panel emergency-triage-panel" style="margin-bottom:24px;">
            <div class="dash-panel-head">
                <h3>🚨 MediAlert Notifications</h3>
                <span class="panel-tag emergency-tag"><?= (int)$doctorMediAlertCount ?> active</span>
            </div>
            <?php foreach ($doctorMediAlerts as $alert): ?>
            <?php
                $patientPayload = [
                    'id'         => (int)($alert['patient_user_id'] ?? $alert['patient_id'] ?? 0),
                    'full_name'  => $alert['patient_name'] ?? 'Unknown',
                    'id_number'  => $alert['patient_id_number'] ?? '',
                    'status'     => $alert['patient_status'] ?? '',
                    'notes'      => $alert['patient_notes'] ?? '',
                    'bp'         => $alert['patient_bp'] ?? '',
                    'temp'       => $alert['patient_temp'] ?? '',
                    'pulse'      => $alert['patient_pulse'] ?? '',
                    'weight'     => $alert['patient_weight'] ?? '',
                ];
            ?>
            <div class="emergency-card">
                <div class="emergency-card-head">
                    <span class="badge emergency"><?= cs_h($alert['emergency_type'] ?? 'Emergency') ?></span>
                    <span class="emergency-time"><?= cs_h($alert['medi_alert_sent_at'] ?? $alert['created_at'] ?? '') ?></span>
                </div>
                <p><strong>Patient:</strong> <?= cs_h($alert['patient_name'] ?? 'Unknown') ?>
                    <?= !empty($alert['patient_id_number']) ? '(' . cs_h($alert['patient_id_number']) . ')' : '' ?></p>
                <p><strong>Reported by:</strong> <?= cs_h($alert['poster_name'] ?? 'Staff') ?> (<?= cs_h($alert['source_role'] ?? '') ?>)</p>
                <?php if (!empty($alert['patient_bp']) || !empty($alert['patient_temp'])): ?>
                <p class="muted">Vitals: BP <?= cs_h($alert['patient_bp'] ?: '—') ?> · Temp <?= cs_h($alert['patient_temp'] ?: '—') ?></p>
                <?php endif; ?>
                <p class="emergency-details"><?= nl2br(cs_h($alert['message'] ?? '')) ?></p>
                <button type="button" class="btn emergency-btn" onclick="openConsult(<?= htmlspecialchars(json_encode($patientPayload), ENT_QUOTES) ?>)">Consult Patient</button>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon appointments">📅</div>
                <div>
                    <h4>Today's Appointments</h4>
                    <div class="kpi-value"><?= (int)$todayAppointmentsCount ?></div>
                    <div class="kpi-sub">Scheduled for today</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon waiting">⏳</div>
                <div>
                    <h4>Waiting Doctor</h4>
                    <div class="kpi-value"><?= (int)$waitingDoctorCount ?></div>
                    <div class="kpi-sub">Ready for consultation</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon doctor">👨‍⚕️</div>
                <div>
                    <h4>With Doctor</h4>
                    <div class="kpi-value"><?= (int)$withDoctorCount ?></div>
                    <div class="kpi-sub">Currently consulting</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon pharmacy">💊</div>
                <div>
                    <h4>Waiting Pharmacy</h4>
                    <div class="kpi-value"><?= (int)$waitingPharmacyCount ?></div>
                    <div class="kpi-sub">Sent for dispensing</div>
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
                    <p>No patients in your queue</p>
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

        <h2 class="section-title">Patient Consultations</h2>
        <p class="muted" style="margin:-12px 0 18px;">Only patients waiting for or currently with the doctor are shown here. Completed visits are managed by reception.</p>

        <?php if (isset($_GET['saved'])): ?>
        <div class="alert-success">Consultation saved successfully.</div>
        <?php endif; ?>
        <?php if (isset($_GET['started'])): ?>
        <div class="alert-info">Consultation started. Patient is now with doctor.</div>
        <?php endif; ?>
        <?php if (isset($_GET['referred'])): ?>
        <div class="alert-success">Patient referral submitted. Reception has been alerted if this was a critical case.</div>
        <?php endif; ?>
        <?php if (isset($_GET['emergency'])): ?>
        <div class="alert-success">Emergency alert sent to reception.</div>
        <?php endif; ?>
        <?php if ($emergencyTriageSupported && $doctorMediAlertCount > 0): ?>
        <div class="emergency-banner">
            <strong>🚨 <?= (int)$doctorMediAlertCount ?> MediAlert<?= $doctorMediAlertCount === 1 ? '' : 's' ?> waiting</strong>
            <span>Open the dashboard to review urgent cases sent by reception.</span>
            <a href="doctor.php?tab=dashboard#doctorMediAlerts" class="btn emergency-btn small">View MediAlerts</a>
        </div>
        <?php endif; ?>

        <div class="dash-panel patients-panel">
            <form method="GET" class="filterBar">
                <input type="hidden" name="tab" value="patients">
                <input type="text" name="search" value="<?= cs_h($search) ?>" placeholder="Search by name or ID...">
                <button class="btn">Search</button>
            </form>
            <div class="table-wrap">
                <table class="patients-table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>ID</th>
                            <th>Status</th>
                            <th>Vitals</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (count($patientRows) === 0): ?>
                        <tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:30px;">No patients waiting for doctor.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($patientRows as $r): ?>
                        <tr>
                            <td><strong><?= cs_h($r['full_name']) ?></strong></td>
                            <td><?= cs_h($r['id_number']) ?></td>
                            <td><span class="badge <?= cs_badge_class($r['status']) ?>"><?= cs_h($r['status']) ?></span></td>
                            <td class="smallMuted">BP <?= cs_h($r['bp'] ?: '—') ?> / Temp <?= cs_h($r['temp'] ?: '—') ?></td>
                            <td class="action-cell">
                                <button type="button" class="btn small" onclick="openConsult(<?= htmlspecialchars(json_encode($r), ENT_QUOTES) ?>)">Consult</button>
                                <a href="patient_history.php?patient_id=<?= (int)$r['id'] ?>" class="btn small secondary">History</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

<?php endif; ?>

<?php if ($tab === 'appointments'): ?>

        <h2 class="section-title">Appointments</h2>

        <div class="announcements-layout">
        <div class="dash-panel patients-panel">
            <form method="GET" class="filterBar">
                <input type="hidden" name="tab" value="appointments">
                <input type="text" name="appt_search" value="<?= cs_h($apptSearch) ?>" placeholder="Search patient...">
                <input type="date" name="filter_date" value="<?= cs_h($filterDate) ?>">
                <button class="btn">Filter</button>
            </form>
            <div class="table-wrap">
                <table class="patients-table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (count($appointmentRows) === 0): ?>
                        <tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:30px;">No appointments for this date.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($appointmentRows as $r): ?>
                        <tr>
                            <td><strong><?= cs_h($r['patient_name']) ?></strong></td>
                            <td><?= cs_h($r['appointment_date']) ?></td>
                            <td><?= cs_h(substr((string)$r['appointment_time'], 0, 5)) ?></td>
                            <td><?= cs_h($r['status']) ?></td>
                            <td><?= cs_h($r['reason']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($announcementsExists): ?>
        <div class="dash-panel">
            <div class="dash-panel-head">
                <h3>Live Announcements</h3>
                <span class="live-badge"><span class="live-dot"></span> Live</span>
            </div>
            <div id="liveAnnouncementFeed" class="announcement-feed" data-latest-id="<?= (int)$latestAnnouncementId ?>">
                <?php if (count($announcementRows) === 0): ?>
                <div class="activity-empty">
                    <div class="empty-icon">📢</div>
                    <p>No announcements yet.</p>
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
                    <option>Doctor</option>
                    <option>Nurse</option>
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

<?php if ($tab === 'queue'): ?>

        <h2 class="section-title">Doctor Queue</h2>

        <div class="dash-panel">
            <div class="table-wrap">
                <table class="patients-table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (count($queueRows) === 0): ?>
                        <tr><td colspan="3" style="text-align:center;color:#94a3b8;padding:30px;">Queue is empty.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($queueRows as $r):
                        $st = $r['status'];
                    ?>
                        <tr>
                            <td><strong><?= cs_h($r['full_name']) ?></strong></td>
                            <td><span class="badge <?= cs_badge_class($st) ?>"><?= cs_h($st) ?></span></td>
                            <td>
                                <?php if ($st === 'Waiting_Doctor'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="queue_update_status">
                                    <input type="hidden" name="patient_id" value="<?= (int)$r['id'] ?>">
                                    <input type="hidden" name="queue_status" value="With Doctor">
                                    <button class="btn small">Start</button>
                                </form>
                                <?php elseif (in_array($st, ['With Doctor','Consulting'], true)): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="queue_update_status">
                                    <input type="hidden" name="patient_id" value="<?= (int)$r['id'] ?>">
                                    <input type="hidden" name="queue_status" value="Waiting_Pharmacy">
                                    <button class="btn small">To Pharmacy</button>
                                </form>
                                <?php elseif ($st === 'Waiting_Pharmacy'): ?>
                                <span class="muted">At pharmacy</span>
                                <?php else: ?>
                                <span class="badge completed">Done</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

<?php endif; ?>

<?php if ($tab === 'referrals'): ?>

        <h2 class="section-title">Patient Referrals</h2>

        <?php if (!$referralsSupported): ?>
        <div class="dash-panel">
            <p class="muted">The referrals table is not available in this database.</p>
        </div>
        <?php else: ?>

        <div class="dash-panel" style="margin-bottom:24px;">
            <div class="dash-panel-head">
                <h3>Outgoing Referrals</h3>
                <span class="panel-tag">From <?= cs_h($facility) ?></span>
            </div>
            <div class="table-wrap">
                <table class="patients-table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Referred To</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (count($outgoingReferrals) === 0): ?>
                        <tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:30px;">No outgoing referrals yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($outgoingReferrals as $ref): ?>
                        <tr>
                            <td>
                                <strong><?= cs_h($ref['patient_name']) ?></strong>
                                <div class="smallMuted"><?= cs_h($ref['patient_id_number']) ?></div>
                            </td>
                            <td><?= cs_h($ref['to_facility']) ?></td>
                            <td><?= cs_h($ref['consultation_details']) ?></td>
                            <td><span class="badge other"><?= cs_h($ref['referral_status']) ?></span></td>
                            <td><?= cs_h($ref['created_at'] ? date('Y-m-d H:i', strtotime($ref['created_at'])) : '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="dash-panel">
            <div class="dash-panel-head">
                <h3>Incoming Referrals</h3>
                <span class="panel-tag">To <?= cs_h($facility) ?></span>
            </div>
            <div class="table-wrap">
                <table class="patients-table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>From Facility</th>
                            <th>Referring Doctor</th>
                            <th>Clinical Summary</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (count($incomingReferrals) === 0): ?>
                        <tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:30px;">No incoming referrals.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($incomingReferrals as $ref): ?>
                        <tr>
                            <td>
                                <strong><?= cs_h($ref['patient_name']) ?></strong>
                                <div class="smallMuted"><?= cs_h($ref['patient_id_number']) ?></div>
                            </td>
                            <td><?= cs_h($ref['from_facility']) ?></td>
                            <td><?= cs_h($ref['doctor_name']) ?></td>
                            <td><?= cs_h($ref['consultation_details']) ?></td>
                            <td><span class="badge other"><?= cs_h($ref['referral_status']) ?></span></td>
                            <td><?= cs_h($ref['created_at'] ? date('Y-m-d H:i', strtotime($ref['created_at'])) : '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php endif; ?>

<?php endif; ?>

<?php if ($tab === 'reports'): ?>

        <div class="dash-hero reports-hero">
            <div>
                <h2>Doctor Consultation Report</h2>
                <p class="location">
                    <span>📍 <?= cs_h($province) ?></span>
                    <span>•</span>
                    <span><strong><?= cs_h($city) ?></strong></span>
                    <span>•</span>
                    <span><?= cs_h($facility) ?></span>
                    <span>•</span>
                    <span>Doctor Desk</span>
                </p>
                <div class="reports-actions">
                    <button class="hero-btn primary" type="button" onclick="window.print()">🖨 Print Report</button>
                    <a class="hero-btn" href="doctor.php?tab=reports">↻ Refresh</a>
                </div>
            </div>
            <div class="reports-meta">
                <strong><?= cs_h($reportDate) ?></strong>
                Generated at <?= cs_h($reportGeneratedAt) ?><br>
                Prepared by <?= cs_h($doctorName) ?>
            </div>
        </div>

        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon total">📋</div>
                <div>
                    <h4>Total Patients</h4>
                    <div class="kpi-value"><?= (int)$totalPatients ?></div>
                    <div class="kpi-sub">Registered at facility</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon waiting">⏳</div>
                <div>
                    <h4>Waiting Doctor</h4>
                    <div class="kpi-value"><?= (int)$waitingDoctorCount ?></div>
                    <div class="kpi-sub">In doctor queue</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon doctor">👨‍⚕️</div>
                <div>
                    <h4>With Doctor</h4>
                    <div class="kpi-value"><?= (int)$withDoctorCount ?></div>
                    <div class="kpi-sub">Active consultations</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon pharmacy">💊</div>
                <div>
                    <h4>Pharmacy Queue</h4>
                    <div class="kpi-value"><?= (int)$waitingPharmacyCount ?></div>
                    <div class="kpi-sub">Completed: <?= (int)$completedCount ?></div>
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

        <div class="report-footer">
            Official doctor consultation report for <strong><?= cs_h($facility) ?></strong>, <?= cs_h($city) ?>, <?= cs_h($province) ?>.
            This document reflects live queue, consultation, and pharmacy referral data for clinical review.
        </div>

<?php endif; ?>

<div id="consultModal" class="modal" onclick="if(event.target===this)closeConsult()">
    <div class="modal-card consult-form">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;">
            <h3 id="c_name" style="margin:0;font-size:17px;">Consultation</h3>
            <button type="button" class="btn secondary small" onclick="closeConsult()">Close</button>
        </div>
        <div class="consult-summary">
            <p class="muted" id="c_meta"></p>
            <p class="consult-notes"><strong>Nurse notes:</strong> <span id="c_notes"></span></p>
            <p style="margin-top:10px;"><a href="#" id="c_history_link" class="btn secondary small" style="text-decoration:none;">View Medical Record</a></p>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="start_consultation">
            <input type="hidden" name="patient_id" id="start_patient_id">
            <button class="btn secondary small" type="submit">Start Consultation</button>
        </form>
        <form method="POST">
            <input type="hidden" name="action" value="finish_consultation">
            <input type="hidden" name="patient_id" id="finish_patient_id">
            <textarea name="doctor_notes" rows="2" placeholder="Doctor notes" required></textarea>
            <textarea name="diagnosis" rows="2" placeholder="Diagnosis" required></textarea>
            <textarea name="prescription" rows="2" placeholder="Prescription" required></textarea>
            <label><input type="checkbox" name="send_pharmacy" checked> Send to pharmacist</label>
            <button class="btn" type="submit">Finish Consultation</button>
        </form>

        <?php if ($referralsSupported && count($referralDestinations) > 0): ?>
        <details class="consult-expand referral-panel">
            <summary>Refer Patient (if needed)</summary>
            <p class="muted">Use when the case is critical or beyond your scope to treat at this facility.</p>
            <form method="POST">
                <input type="hidden" name="action" value="create_referral">
                <input type="hidden" name="patient_id" id="refer_patient_id">
                <select name="referral_type" required>
                    <option value="">Select referral reason</option>
                    <option value="Critical case">Critical case — needs urgent higher-level care</option>
                    <option value="Specialist required">Specialist required</option>
                    <option value="Beyond expertise">Beyond my expertise / skill set</option>
                </select>
                <select name="referred_to_facility_id" required>
                    <option value="">Select receiving facility</option>
                    <?php foreach ($referralDestinations as $dest): ?>
                    <option value="<?= (int)$dest['facility_id'] ?>">
                        <?= cs_h($dest['facility_name']) ?> — <?= cs_h($dest['city']) ?>, <?= cs_h($dest['province']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <textarea name="clinical_summary" rows="2" placeholder="Clinical summary: symptoms, vitals, findings..." required></textarea>
                <button class="btn secondary small" type="submit">Submit Referral</button>
            </form>
        </details>
        <?php elseif ($referralsSupported): ?>
        <p class="muted" style="margin-top:10px;font-size:12px;">No other facilities are registered for referral yet.</p>
        <?php endif; ?>

        <?php if ($emergencyTriageSupported): ?>
        <details class="consult-expand emergency-panel">
            <summary>🚨 Flag Emergency → Alert Reception</summary>
            <p class="muted">Reception will send MediAlert to the relevant specialist.</p>
            <form method="POST">
                <input type="hidden" name="action" value="flag_emergency">
                <input type="hidden" name="patient_id" id="emergency_patient_id">
                <select name="emergency_type" required>
                    <option value="">Select emergency type</option>
                    <?php foreach ($emergencyTypes as $et): ?>
                    <?php if ($et !== 'Critical referral'): ?>
                    <option value="<?= cs_h($et) ?>"><?= cs_h($et) ?></option>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                <textarea name="emergency_details" rows="2" placeholder="Clinical details, vitals, and urgency..." required></textarea>
                <button class="btn emergency-btn small" type="submit">Send Emergency Alert</button>
            </form>
        </details>
        <?php endif; ?>
    </div>
</div>

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

function openConsult(row) {
    var modal = document.getElementById('consultModal');
    if (!modal) return;
    modal.classList.add('show');
    document.getElementById('c_name').innerText = row.full_name || 'Consultation';
    document.getElementById('c_meta').innerText = 'ID: ' + (row.id_number || '') + ' • Status: ' + (row.status || '');
    document.getElementById('c_notes').innerText = row.notes || 'None';
    var historyLink = document.getElementById('c_history_link');
    if (historyLink) {
        historyLink.href = 'patient_history.php?patient_id=' + row.id;
    }
    document.getElementById('start_patient_id').value = row.id;
    document.getElementById('finish_patient_id').value = row.id;
    var referPid = document.getElementById('refer_patient_id');
    if (referPid) referPid.value = row.id;
    var emergencyPid = document.getElementById('emergency_patient_id');
    if (emergencyPid) emergencyPid.value = row.id;
}

function closeConsult() {
    var modal = document.getElementById('consultModal');
    if (modal) modal.classList.remove('show');
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
            plugins: { legend: { display: true, position: 'top' } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 } },
                x: { grid: { display: false } }
            }
        }
    });
}
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
    const url = 'fetch_announcements.php?since_id=' + latestId + '&limit=20';

    fetch(url, { credentials: 'same-origin' })
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
