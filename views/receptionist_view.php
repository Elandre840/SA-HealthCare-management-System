<?php
$patientListOpts = ['search' => $search, 'order' => 'full_name ASC'];
if ($search === '') {
    $patientListOpts['status_not_in'] = ['Completed'];
}
$patientList = cs_fetch_patients($conn, $facilityId, $patientListOpts);
$queuePatients = cs_fetch_patients($conn, $facilityId, [
    'status_not_in' => ['Completed'],
    'order' => 'id ASC',
]);
$appointmentDate = $_GET['filter_date'] ?? date('Y-m-d');
$apptSearch = trim($_GET['appt_search'] ?? $_GET['search'] ?? '');
$appointmentRows = cs_fetch_appointments($conn, $facilityId, [
    'date' => $appointmentDate,
    'search' => $apptSearch,
]);
$allPatients = cs_fetch_patients($conn, $facilityId, ['limit' => 200]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reception Dashboard</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php include __DIR__ . '/staff_styles.php'; ?>
<?php include __DIR__ . '/staff_dashboard_styles.php'; ?>
<style>
.register-form {
    margin-top: 20px;
    padding: 22px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
}
.register-form .btn { margin-top: 10px; }
.book-panel {
    margin-top: 20px;
    padding: 22px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
}
.book-panel h3 {
    margin: 0 0 16px;
    font-size: 17px;
    font-weight: 700;
    color: var(--primary);
}
</style>
</head>
<body class="staff-app">

<div id="sidebarOverlay" class="sidebar-overlay" onclick="closeSidebar()"></div>

<aside id="staffSidebar" class="staff-sidebar">
    <div>
        <div class="brand">
            <?php if (file_exists(__DIR__ . '/../' . $logo)): ?>
            <img src="<?= cs_h($logo) ?>" alt="">
            <?php endif; ?>
            <div>
                <div class="brand-title">SA Health System</div>
                <div class="brand-sub"><?= cs_h($province) ?></div>
            </div>
        </div>
        <nav class="staff-nav">
            <?php foreach ($navItems as $k => $item): ?>
            <a href="receptionist.php?tab=<?= $k ?>" class="<?= $tab === $k ? 'active' : '' ?>">
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
                <?php if (file_exists(__DIR__ . '/../' . $logo)): ?>
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
                <a href="receptionist.php?tab=patients" class="hero-btn primary">Register Patient</a>
                <a href="receptionist.php?tab=queue" class="hero-btn">View Queue</a>
                <?php if ($emergencyTriageSupported && $pendingEmergencyCount > 0): ?>
                <a href="receptionist.php?tab=announcements" class="hero-btn emergency-btn">🚨 <?= (int)$pendingEmergencyCount ?> Emergency</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($emergencyTriageSupported && $pendingEmergencyCount > 0): ?>
        <div class="emergency-banner">
            <strong>🚨 <?= (int)$pendingEmergencyCount ?> pending emergency alert<?= $pendingEmergencyCount === 1 ? '' : 's' ?></strong>
            <span>Review and send MediAlert to relevant staff.</span>
            <a href="receptionist.php?tab=announcements" class="btn emergency-btn small">Open Emergency Triage</a>
        </div>
        <?php endif; ?>

        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon appointments">📋</div>
                <div>
                    <h4>Today's Patients</h4>
                    <div class="kpi-value"><?= (int)$todayPatients ?></div>
                    <div class="kpi-sub">Registered today</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon waiting">⏳</div>
                <div>
                    <h4>Waiting</h4>
                    <div class="kpi-value"><?= (int)$waitingCount ?></div>
                    <div class="kpi-sub">At reception desk</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon total">✅</div>
                <div>
                    <h4>Registered</h4>
                    <div class="kpi-value"><?= (int)$registeredCount ?></div>
                    <div class="kpi-sub">Ready for triage</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon nurse">👥</div>
                <div>
                    <h4>Total Patients</h4>
                    <div class="kpi-value"><?= (int)$totalPatients ?></div>
                    <div class="kpi-sub">On file at facility</div>
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
                            <span><?= cs_h($a['status']) ?> • <?= cs_h($a['department']) ?></span>
                        </div>
                        <span class="badge <?= cs_badge_class($a['status']) ?>"><?= cs_h($a['status']) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <div class="activity-empty">
                    <div class="empty-icon">📭</div>
                    <p>No activity yet</p>
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
                <?php $isEmergency = ($ann['priority'] ?? '') === 'emergency'; ?>
                <div class="announcement-item<?= $isEmergency ? ' emergency' : '' ?>" data-id="<?= (int)($ann['announcement_id'] ?? 0) ?>">
                    <strong><?= cs_h($ann['poster_name'] ?? 'Staff') ?></strong>
                    <?php if ($isEmergency): ?>
                    <span class="badge emergency">EMERGENCY</span>
                    <?php else: ?>
                    <span class="badge other"><?= cs_h($ann['role_target'] ?? 'All') ?></span>
                    <?php endif; ?>
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

        <h2 class="section-title">Patient Registration</h2>
        <p class="muted" style="margin:-12px 0 18px;">Active patients are listed below. Search by name or ID to find completed visits and re-admit them to the workflow.</p>

        <div class="dash-panel patients-panel">
            <form method="GET" class="filterBar">
                <input type="hidden" name="tab" value="patients">
                <input type="text" name="search" placeholder="Search name or ID..." value="<?= cs_h($search) ?>">
                <button class="btn" type="submit">Search</button>
            </form>
            <div class="table-wrap">
                <table class="patients-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>ID</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (count($patientList) === 0): ?>
                        <tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:30px;">No patients found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($patientList as $r): ?>
                        <tr>
                            <td><strong><?= cs_h($r['full_name']) ?></strong></td>
                            <td><?= cs_h($r['id_number']) ?></td>
                            <td><?= cs_h($r['department']) ?></td>
                            <td><span class="badge <?= cs_badge_class($r['status']) ?>"><?= cs_h($r['status']) ?></span></td>
                            <td><button type="button" class="btn small" onclick='openEdit(<?= json_encode($r, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>)'>Edit</button></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button type="button" class="btn" onclick="document.getElementById('formBox').classList.toggle('show')">+ Register Patient</button>
            <div id="formBox" class="formBox register-form">
                <form method="POST">
                    <input type="hidden" name="action" value="add_patient">
                    <input name="full_name" placeholder="Full Name" required>
                    <input name="id_number" placeholder="ID Number" required>
                    <input name="phone" placeholder="Phone">
                    <button class="btn">Save Patient</button>
                </form>
            </div>
        </div>

        <div id="editModal" class="modal">
            <div class="modal-card">
                <h3>Edit Patient</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="edit_patient">
                    <input type="hidden" name="patient_id" id="edit_patient_id">
                    <input name="edit_full_name" id="edit_full_name" required>
                    <input name="edit_id_number" id="edit_id_number" required>
                    <input name="edit_phone" id="edit_phone">
                    <select name="edit_department" id="edit_department">
                        <option>Reception</option><option>Nurse</option><option>Doctor</option><option>Pharmacy</option>
                    </select>
                    <select name="edit_status" id="edit_status">
                        <option>Waiting</option><option>Registered</option><option>With Nurse</option>
                        <option>Waiting_Doctor</option><option>With Doctor</option><option>Waiting_Pharmacy</option><option>Completed</option>
                    </select>
                    <button class="btn">Save Changes</button>
                </form>
            </div>
        </div>

<?php endif; ?>

<?php if ($tab === 'appointments'): ?>

        <h2 class="section-title">Appointments</h2>

        <div class="dash-panel patients-panel">
            <form method="GET" class="filterBar">
                <input type="hidden" name="tab" value="appointments">
                <input type="text" name="appt_search" placeholder="Search patient..." value="<?= cs_h($apptSearch) ?>">
                <input type="date" name="filter_date" value="<?= cs_h($appointmentDate) ?>">
                <button class="btn">Filter</button>
            </form>
            <div class="table-wrap">
                <table class="patients-table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$appointmentRows): ?>
                        <tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:30px;">No appointments found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($appointmentRows as $r): ?>
                        <tr>
                            <td><strong><?= cs_h($r['patient_name']) ?></strong></td>
                            <td><?= cs_h($r['doctor_name']) ?></td>
                            <td><?= cs_h($r['appointment_date']) ?></td>
                            <td><?= cs_h(substr((string)$r['appointment_time'], 0, 5)) ?></td>
                            <td><?= cs_h($r['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($hasAppointmentsForBooking): ?>
            <div class="book-panel">
                <h3>Book Appointment</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="add_appointment">
                    <select name="appt_patient_id" id="appt_patient_id" required>
                        <option value="">Select patient</option>
                        <?php foreach ($allPatients as $p): ?>
                        <option value="<?= (int)$p['id'] ?>"><?= cs_h($p['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="appt_doctor_id" required>
                        <option value="">Select doctor</option>
                        <?php foreach ($doctors as $d): ?>
                        <option value="<?= (int)$d['id'] ?>"><?= cs_h($d['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="date" name="appt_date" value="<?= date('Y-m-d') ?>" required>
                    <input type="time" name="appt_time" required>
                    <textarea name="appt_reason" placeholder="Reason for visit"></textarea>
                    <button class="btn">Save Appointment</button>
                </form>
            </div>
            <?php endif; ?>
        </div>

<?php endif; ?>

<?php if ($tab === 'announcements'): ?>

        <h2 class="section-title">Facility Announcements</h2>

        <?php if (isset($_GET['medi']) && $_GET['medi'] === 'medi_sent'): ?>
        <div class="alert-success">MediAlert sent successfully.</div>
        <?php elseif (isset($_GET['medi']) && $_GET['medi'] === 'medi_failed'): ?>
        <div class="alert-error">MediAlert could not be sent. Check staff contact details.</div>
        <?php elseif (isset($_GET['medi']) && $_GET['medi'] === 'medi_invalid'): ?>
        <div class="alert-error">Could not send MediAlert. Select a staff member and at least one channel.</div>
        <?php endif; ?>
        <?php if (isset($_GET['ack'])): ?>
        <div class="alert-success">Emergency marked as acknowledged.</div>
        <?php endif; ?>

        <?php if ($emergencyTriageSupported): ?>
        <div class="dash-panel emergency-triage-panel">
            <div class="dash-panel-head">
                <h3>🚨 Emergency Triage</h3>
                <span class="panel-tag emergency-tag"><?= (int)$pendingEmergencyCount ?> pending</span>
            </div>
            <p class="muted">Alerts from nurses and doctors arrive here first. Send MediAlert (SMS/email) to notify the relevant specialist.</p>

            <?php if (count($pendingEmergencies) === 0): ?>
            <div class="activity-empty">
                <div class="empty-icon">✅</div>
                <p>No pending emergency alerts.</p>
            </div>
            <?php else: ?>
            <?php foreach ($pendingEmergencies as $em): ?>
            <div class="emergency-card">
                <div class="emergency-card-head">
                    <span class="badge emergency"><?= cs_h($em['emergency_type'] ?? 'Emergency') ?></span>
                    <span class="emergency-time"><?= cs_h($em['created_at'] ?? '') ?></span>
                </div>
                <p><strong>Patient:</strong> <?= cs_h($em['patient_name'] ?? 'Unknown') ?> <?= !empty($em['patient_id_number']) ? '(' . cs_h($em['patient_id_number']) . ')' : '' ?></p>
                <p><strong>Reported by:</strong> <?= cs_h($em['poster_name'] ?? 'Staff') ?> (<?= cs_h($em['source_role'] ?? '') ?>)</p>
                <p class="emergency-details"><?= nl2br(cs_h($em['message'] ?? '')) ?></p>

                <form method="POST" class="medi-alert-form">
                    <input type="hidden" name="action" value="send_medi_alert">
                    <input type="hidden" name="announcement_id" value="<?= (int)($em['announcement_id'] ?? 0) ?>">
                    <label>Notify staff member</label>
                    <select name="staff_id" required>
                        <option value="">Select doctor / specialist</option>
                        <?php foreach ($doctors as $doc): ?>
                        <option value="<?= (int)$doc['id'] ?>">
                            <?= cs_h($doc['full_name']) ?>
                            <?php if (!empty($doc['email']) || !empty($doc['phone'])): ?>
                            — <?= cs_h($doc['email'] ?: $doc['phone']) ?>
                            <?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="medi-channels">
                        <label><input type="checkbox" name="send_email" checked> Email</label>
                        <label><input type="checkbox" name="send_sms" checked> SMS</label>
                    </div>
                    <button class="btn emergency-btn" type="submit">Send MediAlert</button>
                </form>

                <form method="POST" class="ack-form">
                    <input type="hidden" name="action" value="acknowledge_emergency">
                    <input type="hidden" name="announcement_id" value="<?= (int)($em['announcement_id'] ?? 0) ?>">
                    <button class="btn secondary small" type="submit">Mark Acknowledged</button>
                </form>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

            <?php if (count($recentEmergencies) > count($pendingEmergencies)): ?>
            <details class="emergency-history">
                <summary>Recent emergency history</summary>
                <?php foreach ($recentEmergencies as $em): ?>
                <?php if (($em['alert_status'] ?? '') === 'pending') continue; ?>
                <div class="emergency-card resolved">
                    <div class="emergency-card-head">
                        <span class="badge <?= ($em['alert_status'] ?? '') === 'medi_alert_sent' ? 'waiting' : 'completed' ?>"><?= cs_h(ucfirst(str_replace('_', ' ', $em['alert_status'] ?? 'done'))) ?></span>
                        <span class="emergency-time"><?= cs_h($em['created_at'] ?? '') ?></span>
                    </div>
                    <p><strong><?= cs_h($em['emergency_type'] ?? 'Emergency') ?></strong> — <?= cs_h($em['patient_name'] ?? 'Unknown') ?></p>
                    <p class="muted"><?= cs_h($em['poster_name'] ?? 'Staff') ?> (<?= cs_h($em['source_role'] ?? '') ?>)</p>
                </div>
                <?php endforeach; ?>
            </details>
            <?php endif; ?>
        </div>
        <?php endif; ?>

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
                    <option>Reception</option>
                    <option>Nurse</option>
                    <option>Doctor</option>
                    <option>Pharmacist</option>
                </select>
                <textarea name="message" rows="4" placeholder="Share an update with staff at your facility..." required></textarea>
                <button class="btn">Post Announcement</button>
            </form>
            <?php else: ?>
            <p style="color:#94a3b8;">Announcements table not available.</p>
            <?php endif; ?>
        </div>

        <div class="dash-panel">
            <div class="dash-panel-head">
                <h3>Live Feed</h3>
                <span class="live-badge"><span class="live-dot"></span> Auto-updating</span>
            </div>
            <div id="liveAnnouncementFeed" class="announcement-feed" data-latest-id="<?= (int)$latestAnnouncementId ?>">
                <?php if (!$announcementsExists): ?>
                <p style="color:#94a3b8;">Announcements not available.</p>
                <?php elseif (count($announcementRows) === 0): ?>
                <div class="activity-empty">
                    <div class="empty-icon">📢</div>
                    <p>No announcements yet.</p>
                </div>
                <?php else: ?>
                <?php foreach ($announcementRows as $ann): ?>
                <?php $isEmergency = ($ann['priority'] ?? '') === 'emergency'; ?>
                <div class="announcement-item<?= $isEmergency ? ' emergency' : '' ?>" data-id="<?= (int)($ann['announcement_id'] ?? 0) ?>">
                    <strong><?= cs_h($ann['poster_name'] ?? 'Staff') ?></strong>
                    <?php if ($isEmergency): ?>
                    <span class="badge emergency">EMERGENCY</span>
                    <?php else: ?>
                    <span class="badge other"><?= cs_h($ann['role_target'] ?? 'All') ?></span>
                    <?php endif; ?>
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

        <h2 class="section-title">Patient Queue</h2>

        <div class="dash-panel">
            <div class="dash-panel-head">
                <h3>Workflow Queue</h3>
                <span class="panel-tag"><?= count($queuePatients) ?> patients</span>
            </div>
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
                    <?php if (count($queuePatients) === 0): ?>
                        <tr><td colspan="3" style="text-align:center;color:#94a3b8;padding:30px;">Queue is empty.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($queuePatients as $r):
                        $st = $r['status'];
                        $next = null;
                        $label = '';
                        if ($st === 'Waiting') { $next = 'Registered'; $label = 'Register'; }
                        elseif ($st === 'Registered') { $next = 'With Nurse'; $label = 'Send to Nurse'; }
                        elseif ($st === 'With Nurse') { $next = 'Waiting_Doctor'; $label = 'Doctor Queue'; }
                        elseif ($st === 'Waiting_Doctor') { $next = 'With Doctor'; $label = 'Send to Doctor'; }
                        elseif ($st === 'With Doctor') { $next = 'Waiting_Pharmacy'; $label = 'Send to Pharmacy'; }
                        elseif ($st === 'Waiting_Pharmacy') { $next = 'Completed'; $label = 'Complete'; }
                    ?>
                        <tr>
                            <td><strong><?= cs_h($r['full_name']) ?></strong></td>
                            <td><span class="badge <?= cs_badge_class($st) ?>"><?= cs_h($st) ?></span></td>
                            <td>
                                <?php if ($next): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="queue_update_status">
                                    <input type="hidden" name="patient_id" value="<?= (int)$r['id'] ?>">
                                    <input type="hidden" name="queue_status" value="<?= cs_h($next) ?>">
                                    <button class="btn small"><?= cs_h($label) ?></button>
                                </form>
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

<?php if ($tab === 'reports'): ?>

        <div class="dash-hero reports-hero">
            <div>
                <h2>Facility Daily Operations Report</h2>
                <p class="location">
                    <span>📍 <?= cs_h($province) ?></span>
                    <span>•</span>
                    <span><strong><?= cs_h($city) ?></strong></span>
                    <span>•</span>
                    <span><?= cs_h($facility) ?></span>
                    <span>•</span>
                    <span>Reception Desk</span>
                </p>
                <div class="reports-actions">
                    <button class="hero-btn primary" type="button" onclick="window.print()">🖨 Print Report</button>
                    <a class="hero-btn" href="receptionist.php?tab=reports">↻ Refresh</a>
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
                <div class="kpi-icon nurse">👥</div>
                <div>
                    <h4>Total Patients on File</h4>
                    <div class="kpi-value"><?= (int)$totalPatients ?></div>
                    <div class="kpi-sub">All active records</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon appointments">📋</div>
                <div>
                    <h4>Registered Today</h4>
                    <div class="kpi-value"><?= (int)$todayPatients ?></div>
                    <div class="kpi-sub">New arrivals since 00:00</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon waiting">⏳</div>
                <div>
                    <h4>Waiting at Reception</h4>
                    <div class="kpi-value"><?= (int)$waitingCount ?></div>
                    <div class="kpi-sub">Awaiting registration</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon doctor">🩺</div>
                <div>
                    <h4>In Clinical Pathway</h4>
                    <div class="kpi-value"><?= (int)$inCareCount ?></div>
                    <div class="kpi-sub">Nurse: <?= (int)$withNurseCount ?> • Doctor: <?= (int)$withDoctorCount ?></div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon appointments">📅</div>
                <div>
                    <h4>Appointments Today</h4>
                    <div class="kpi-value"><?= (int)$todayApptTotal ?></div>
                    <div class="kpi-sub">Scheduled: <?= (int)$todayApptScheduled ?></div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon total">✅</div>
                <div>
                    <h4>Completed Visits</h4>
                    <div class="kpi-value"><?= (int)$completedCount ?></div>
                    <div class="kpi-sub">Patients marked completed</div>
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
                    <h3>Hourly Patient Arrivals</h3>
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
                        <thead><tr><th>Status</th><th>Count</th><th>% of Total</th></tr></thead>
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
                <h3>Today's Appointments Register</h3>
                <span class="panel-tag"><?= cs_h(date('Y-m-d')) ?></span>
            </div>
            <div class="table-wrap" style="margin-top:0;border:none;">
                <table class="summary-table">
                    <thead><tr><th>Time</th><th>Patient</th><th>Doctor</th><th>Reason</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php if (empty($todayAppointments)): ?>
                        <tr><td colspan="5" class="smallMuted">No appointments scheduled for today.</td></tr>
                    <?php else: ?>
                        <?php foreach ($todayAppointments as $ap): ?>
                        <tr>
                            <td><?= cs_h(substr((string)($ap['appointment_time'] ?? ''), 0, 5)) ?></td>
                            <td><?= cs_h($ap['patient_name'] ?? '') ?></td>
                            <td><?= cs_h($ap['doctor_name'] ?? '') ?></td>
                            <td><?= cs_h($ap['reason'] ?? '—') ?></td>
                            <td><?= cs_h($ap['status'] ?? '') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="dash-panel">
            <div class="report-card-head">
                <h3>Full Patient Register</h3>
                <span class="panel-tag"><?= count($reportPatients) ?> records</span>
            </div>
            <div class="table-wrap" style="margin-top:0;border:none;">
                <table class="summary-table">
                    <thead><tr><th>Patient</th><th>ID Number</th><th>Phone</th><th>Department</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($reportPatients as $rp): ?>
                        <tr>
                            <td><strong><?= cs_h($rp['full_name']) ?></strong></td>
                            <td><?= cs_h($rp['id_number']) ?></td>
                            <td><?= cs_h($rp['phone']) ?></td>
                            <td><?= cs_h($rp['department']) ?></td>
                            <td><span class="badge <?= cs_badge_class($rp['status']) ?>"><?= cs_h($rp['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="report-footer">
            Official reception report for <strong><?= cs_h($facility) ?></strong>, <?= cs_h($city) ?>, <?= cs_h($province) ?>.
            This document reflects live queue, registration, and appointment data for clinical and administrative review.
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

function openEdit(row) {
    document.getElementById('edit_patient_id').value = row.id;
    document.getElementById('edit_full_name').value = row.full_name || '';
    document.getElementById('edit_id_number').value = row.id_number || '';
    document.getElementById('edit_phone').value = row.phone || '';
    document.getElementById('edit_department').value = row.department || 'Reception';
    document.getElementById('edit_status').value = row.status || 'Waiting';
    document.getElementById('editModal').classList.add('show');
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
if (document.getElementById('chart')) {
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
}

if (document.getElementById('lineChart')) {
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
                x: { grid: { display: false }, ticks: { font: { family: 'Inter', size: 11 } } },
                y: { beginAtZero: true, ticks: { stepSize: 1, font: { family: 'Inter', size: 11 } }, grid: { color: '#f1f5f9' } }
            }
        }
    });
}
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
    const isEmergency = item.priority === 'emergency';
    const badge = isEmergency
        ? '<span class="badge emergency">EMERGENCY</span>'
        : '<span class="badge other">' + escapeHtml(item.role_target) + '</span>';
    return `
        <div class="announcement-item${isEmergency ? ' emergency' : ''}" data-id="${item.id}">
            <strong>${escapeHtml(item.poster_name)}</strong>
            ${badge}
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
