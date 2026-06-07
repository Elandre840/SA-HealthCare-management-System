
<?php
include_once 'helpers.php';
require_login();
require_role('pharmacist');
include_once 'db.php';
include_once 'clinic_schema.php';
include_once 'ui_theme.php';

$province = $_SESSION['province'] ?? '';
$city     = $_SESSION['city'] ?? '';
$facility = $_SESSION['facility'] ?? '';
$currentRole = $_SESSION['role'] ?? 'pharmacist';
$userName = $_SESSION['name'] ?? 'Pharmacist';
$facilityId = cs_facility_id($conn, $province, $city, $facility);

$theme = get_ui_theme($province);
$key  = strtolower(str_replace(' ', '', $province));
$logo = "assets/emblems/$key.png";

$allowedTabs = ['dashboard','dispense','announcements','queue','reports'];
$tab = $_GET['tab'] ?? 'dashboard';
if (!in_array($tab, $allowedTabs, true)) {
    $tab = 'dashboard';
}

$announcementsExists = cs_announcements_supported($conn);

if (isset($_POST['action']) && $_POST['action'] === 'dispense_medication') {
    $pid = (int)($_POST['patient_id'] ?? 0);
    if ($pid > 0) {
        $note = trim($_POST['pharmacy_notes'] ?? '');
        if ($note === '') {
            $note = 'Medication dispensed.';
        }
        cs_update_patient($conn, $facilityId, $pid, [
            'status' => 'Completed',
            'department' => 'Pharmacy',
            'medication' => trim($_POST['dispensed_medication'] ?? ''),
            'notes' => 'Pharmacy: ' . $note,
        ]);
    }
    header('Location: pharmacy.php?tab=dispense&ok=1');
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'add_announcement' && $announcementsExists) {
    $msg = trim($_POST['message'] ?? '');
    $uid = (int)($_SESSION['user_id'] ?? 0);
    if ($uid > 0 && $msg !== '') {
        cs_add_announcement($conn, $province, $city, $facility, $uid, trim($_POST['role_target'] ?? 'All'), $msg);
    }
    header('Location: pharmacy.php?tab=announcements');
    exit;
}

$statusData = cs_patient_status_counts($conn, $facilityId);
[$hourLabels, $hourCounts] = cs_hourly_patient_counts($conn, $facilityId);
$pharmacyReadyCount = cs_count_patients($conn, $facilityId, " AND p.status IN ('Waiting_Pharmacy','Prescription_Ready') ");
$completedCount = cs_count_patients($conn, $facilityId, " AND p.status = 'Completed' ");
$totalPatients = cs_count_patients($conn, $facilityId);

$search = trim($_GET['search'] ?? '');
$filter = $_GET['filter'] ?? 'Waiting_Pharmacy';
$statusIn = ($filter === 'All')
    ? ['Waiting_Pharmacy','Prescription_Ready','With Doctor','Completed']
    : [$filter];

$dispenseRows = cs_fetch_patients($conn, $facilityId, [
    'search' => $search,
    'status_in' => $statusIn,
]);

$queueRows = cs_fetch_patients($conn, $facilityId, [
    'status_in' => ['Waiting_Pharmacy','Prescription_Ready'],
    'order' => 'id ASC',
]);

$activityRows = cs_fetch_patients($conn, $facilityId, [
    'status_in' => ['Waiting_Pharmacy','Prescription_Ready','Completed'],
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
$userInitials = strtoupper(substr($userName, 0, 1));
$reportDate = date('l, d F Y');
$reportGeneratedAt = date('H:i');

$navItems = [
    'dashboard' => ['icon' => '📊', 'label' => 'Dashboard'],
    'dispense' => ['icon' => '💊', 'label' => 'Dispense'],
    'queue' => ['icon' => '📋', 'label' => 'Queue'],
    'announcements' => ['icon' => '📢', 'label' => 'Announcements'],
    'reports' => ['icon' => '📈', 'label' => 'Reports'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pharmacy Dashboard</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php include __DIR__ . '/views/staff_styles.php'; ?>
<?php include __DIR__ . '/views/staff_dashboard_styles.php'; ?>
<style>
.dispense-form {
    margin-top: 20px;
    padding: 22px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
}
.dispense-form h3 {
    margin: 0 0 16px;
    font-size: 17px;
    font-weight: 700;
    color: var(--primary);
}
.dispense-form textarea {
    margin-top: 10px;
    min-height: 80px;
    resize: vertical;
}
.dispense-form textarea[disabled] {
    background: #eef2f7;
    color: #64748b;
}
.dispense-form .btn {
    margin-top: 14px;
    width: 100%;
    padding: 13px;
    font-size: 14px;
}
.patients-table tbody tr.selectable {
    cursor: pointer;
}
.patients-table tbody tr.selectable:hover {
    background: #f8fafc;
}
</style>
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
            <a href="pharmacy.php?tab=<?= $k ?>" class="<?= $tab === $k ? 'active' : '' ?>">
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
                <a href="pharmacy.php?tab=dispense" class="hero-btn primary">Dispense Medication</a>
                <a href="pharmacy.php?tab=queue" class="hero-btn">View Queue</a>
            </div>
        </div>

        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon pharmacy">💊</div>
                <div>
                    <h4>Pharmacy Queue</h4>
                    <div class="kpi-value"><?= (int)$pharmacyReadyCount ?></div>
                    <div class="kpi-sub">Awaiting dispensing</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon total">✅</div>
                <div>
                    <h4>Completed</h4>
                    <div class="kpi-value"><?= (int)$completedCount ?></div>
                    <div class="kpi-sub">Medication dispensed</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon nurse">📋</div>
                <div>
                    <h4>Total Patients</h4>
                    <div class="kpi-value"><?= (int)$totalPatients ?></div>
                    <div class="kpi-sub">Registered at facility</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon waiting">📊</div>
                <div>
                    <h4>Status Types</h4>
                    <div class="kpi-value"><?= count($statusData) ?></div>
                    <div class="kpi-sub">Active workflow stages</div>
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
                    <p>No pharmacy activity yet</p>
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

<?php if ($tab === 'dispense'): ?>

        <h2 class="section-title">Dispense Medication</h2>

        <?php if (isset($_GET['ok'])): ?>
        <div class="alert-success">Medication dispensed. Patient marked as completed.</div>
        <?php endif; ?>

        <div class="dash-panel patients-panel">
            <form method="GET" class="filterBar">
                <input type="hidden" name="tab" value="dispense">
                <input type="text" name="search" value="<?= cs_h($search) ?>" placeholder="Search by name or ID...">
                <select name="filter">
                    <?php foreach (['Waiting_Pharmacy','Prescription_Ready','Completed','All'] as $f): ?>
                    <option <?= $filter === $f ? 'selected' : '' ?>><?= $f ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn">Apply Filter</button>
            </form>
            <div class="table-wrap">
                <table class="patients-table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>ID</th>
                            <th>Status</th>
                            <th>Prescription</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (count($dispenseRows) === 0): ?>
                        <tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:30px;">No patients found for this filter.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($dispenseRows as $p): ?>
                        <tr class="selectable" onclick="selectPatient(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)">
                            <td><strong><?= cs_h($p['full_name']) ?></strong></td>
                            <td><?= cs_h($p['id_number']) ?></td>
                            <td><span class="badge <?= cs_badge_class($p['status']) ?>"><?= cs_h($p['status']) ?></span></td>
                            <td><?= cs_h($p['prescription'] ?: 'Not recorded') ?></td>
                            <td class="smallMuted">Select →</td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div id="dispenseFormBox" class="formBox dispense-form">
                <form method="POST">
                    <input type="hidden" name="action" value="dispense_medication">
                    <input type="hidden" name="patient_id" id="dispense_patient_id">
                    <h3 id="dispensePatientName">Dispense Medication</h3>
                    <p id="dispenseMeta" class="muted"></p>
                    <textarea id="viewDiagnosis" disabled placeholder="Diagnosis will appear here..."></textarea>
                    <textarea id="viewPrescription" disabled placeholder="Prescription will appear here..."></textarea>
                    <textarea name="dispensed_medication" id="dispensed_medication" placeholder="Dispensed medication" required></textarea>
                    <textarea name="pharmacy_notes" placeholder="Pharmacy notes (optional)"></textarea>
                    <button class="btn">Dispense → Complete</button>
                </form>
            </div>
        </div>

<?php endif; ?>

<?php if ($tab === 'queue'): ?>

        <h2 class="section-title">Pharmacy Queue</h2>

        <div class="dash-panel">
            <div class="dash-panel-head">
                <h3>Waiting for Dispensing</h3>
                <span class="panel-tag"><?= count($queueRows) ?> patients</span>
            </div>
            <div class="table-wrap">
                <table class="patients-table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>ID</th>
                            <th>Status</th>
                            <th>Prescription</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (count($queueRows) === 0): ?>
                        <tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:30px;">Queue is empty.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($queueRows as $p): ?>
                        <tr>
                            <td><strong><?= cs_h($p['full_name']) ?></strong></td>
                            <td><?= cs_h($p['id_number']) ?></td>
                            <td><span class="badge <?= cs_badge_class($p['status']) ?>"><?= cs_h($p['status']) ?></span></td>
                            <td><?= cs_h($p['prescription'] ?: 'Not recorded') ?></td>
                            <td><a href="pharmacy.php?tab=dispense" class="btn small">Dispense</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
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
                    <option>Pharmacist</option>
                    <option>Doctor</option>
                    <option>Nurse</option>
                    <option>Reception</option>
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
        </div>

<?php endif; ?>

<?php if ($tab === 'reports'): ?>

        <div class="dash-hero reports-hero">
            <div>
                <h2>Pharmacy Report</h2>
                <p class="location">
                    <span>📍 <?= cs_h($province) ?></span>
                    <span>•</span>
                    <span><strong><?= cs_h($city) ?></strong></span>
                    <span>•</span>
                    <span><?= cs_h($facility) ?></span>
                    <span>•</span>
                    <span>Pharmacy Desk</span>
                </p>
                <div class="reports-actions">
                    <button class="hero-btn primary" type="button" onclick="window.print()">🖨 Print Report</button>
                    <a class="hero-btn" href="pharmacy.php?tab=reports">↻ Refresh</a>
                </div>
            </div>
            <div class="reports-meta">
                <strong><?= cs_h($reportDate) ?></strong>
                Generated at <?= cs_h($reportGeneratedAt) ?><br>
                Prepared by <?= cs_h($userName) ?>
            </div>
        </div>

        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon pharmacy">💊</div>
                <div>
                    <h4>Pharmacy Queue</h4>
                    <div class="kpi-value"><?= (int)$pharmacyReadyCount ?></div>
                    <div class="kpi-sub">Awaiting dispensing</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon total">✅</div>
                <div>
                    <h4>Completed</h4>
                    <div class="kpi-value"><?= (int)$completedCount ?></div>
                    <div class="kpi-sub">Medication dispensed</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon nurse">📋</div>
                <div>
                    <h4>Total Patients</h4>
                    <div class="kpi-value"><?= (int)$totalPatients ?></div>
                    <div class="kpi-sub">Registered at facility</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon waiting">📊</div>
                <div>
                    <h4>Status Categories</h4>
                    <div class="kpi-value"><?= count($statusData) ?></div>
                    <div class="kpi-sub">Workflow stages tracked</div>
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
            Official pharmacy report for <strong><?= cs_h($facility) ?></strong>, <?= cs_h($city) ?>, <?= cs_h($province) ?>.
            This document reflects live dispensing queue, completion rates, and patient workflow data.
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

function selectPatient(row) {
    document.getElementById('dispenseFormBox').classList.add('show');
    document.getElementById('dispense_patient_id').value = row.id;
    document.getElementById('dispensePatientName').innerText = 'Dispense — ' + row.full_name;
    document.getElementById('dispenseMeta').innerText = 'ID: ' + (row.id_number || '') + ' • ' + (row.status || '');
    document.getElementById('viewDiagnosis').value = row.diagnosis || '';
    document.getElementById('viewPrescription').value = row.prescription || '';
    document.getElementById('dispensed_medication').value = row.medication || row.prescription || '';
    document.getElementById('dispenseFormBox').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
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
