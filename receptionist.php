
<?php
include_once 'helpers.php';
require_login();
require_role('reception');
include_once 'db.php';
include_once 'clinic_schema.php';
include_once 'medi_alert.php';
include_once 'ui_theme.php';

$province = $_SESSION['province'] ?? '';
$city     = $_SESSION['city'] ?? '';
$facility = $_SESSION['facility'] ?? '';
$currentRole = $_SESSION['role'] ?? 'reception';
$facilityId = cs_facility_id($conn, $province, $city, $facility);

$theme = get_ui_theme($province);
$key  = strtolower(str_replace(' ', '', $province));
$logo = "assets/emblems/$key.png";

$allowedTabs = ['dashboard','patients','appointments','announcements','queue','reports'];
$tab = $_GET['tab'] ?? 'dashboard';
if (!in_array($tab, $allowedTabs, true)) {
    $tab = 'dashboard';
}

$announcementsExists = cs_announcements_supported($conn);
$emergencyTriageSupported = cs_emergency_triage_supported($conn);
$canPostAnnouncements = $announcementsExists;
$mediAlertFeedback = '';
$canListDoctorsFromUsers = true;
$hasAppointmentsForBooking = cs_table_exists($conn, 'appointments');
$usersHasCreatedAt = cs_column_exists($conn, 'users', 'created_at');

if (isset($_POST['action']) && $_POST['action'] === 'add_patient') {
    cs_add_patient(
        $conn,
        $facilityId,
        trim($_POST['full_name'] ?? ''),
        trim($_POST['id_number'] ?? ''),
        trim($_POST['phone'] ?? '')
    );
    header('Location: receptionist.php?tab=patients');
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'edit_patient') {
    $pid = (int)($_POST['patient_id'] ?? 0);
    if ($pid > 0) {
        cs_update_patient($conn, $facilityId, $pid, [
            'full_name'  => trim($_POST['edit_full_name'] ?? ''),
            'id_number'  => trim($_POST['edit_id_number'] ?? ''),
            'phone'      => trim($_POST['edit_phone'] ?? ''),
            'department' => trim($_POST['edit_department'] ?? ''),
            'status'     => trim($_POST['edit_status'] ?? ''),
        ]);
    }
    header('Location: receptionist.php?tab=patients');
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'send_medi_alert' && $emergencyTriageSupported) {
    $alertId = (int)($_POST['announcement_id'] ?? 0);
    $staffId = (int)($_POST['staff_id'] ?? 0);
    $sendEmail = isset($_POST['send_email']);
    $sendSms = isset($_POST['send_sms']);
    $uid = (int)($_SESSION['user_id'] ?? 0);

    if ($alertId > 0 && $staffId > 0 && ($sendEmail || $sendSms)) {
        $pending = cs_fetch_emergency_alerts($conn, $province, $city, $facility, ['status' => 'pending', 'limit' => 100]);
        $alert = null;
        foreach ($pending as $row) {
            if ((int)($row['announcement_id'] ?? 0) === $alertId) {
                $alert = $row;
                break;
            }
        }

        $staff = cs_fetch_staff_contact($conn, $staffId, $province, $city, $facility);
        if ($alert && $staff) {
            $patientName = trim((string)($alert['patient_name'] ?? ''));
            $body = cs_build_medi_alert_message($alert, $facility, $patientName);
            $subject = 'MEDIALERT: ' . ($alert['emergency_type'] ?? 'Emergency') . ' — ' . $facility;
            $results = [];

            if ($sendEmail && trim((string)($staff['email'] ?? '')) !== '') {
                $results[] = cs_send_medi_alert_email($staff['email'], $subject, $body);
            }
            if ($sendSms && trim((string)($staff['phone'] ?? '')) !== '') {
                $smsBody = 'MEDIALERT ' . ($alert['emergency_type'] ?? 'Emergency') . ' at ' . $facility . '. Patient: ' . ($patientName ?: 'see email') . '. Contact reception.';
                $results[] = cs_send_medi_alert_sms($staff['phone'], $smsBody);
            }

            $anyOk = false;
            foreach ($results as $res) {
                if (!empty($res['ok'])) {
                    $anyOk = true;
                }
            }

            if ($anyOk) {
                cs_update_emergency_alert_status($conn, $alertId, 'medi_alert_sent', $staffId);
                $mediAlertFeedback = 'medi_sent';
            } else {
                $mediAlertFeedback = 'medi_failed';
            }
        } else {
            $mediAlertFeedback = 'medi_invalid';
        }
    } else {
        $mediAlertFeedback = 'medi_invalid';
    }

    header('Location: receptionist.php?tab=announcements&medi=' . urlencode($mediAlertFeedback));
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'acknowledge_emergency' && $emergencyTriageSupported) {
    $alertId = (int)($_POST['announcement_id'] ?? 0);
    if ($alertId > 0) {
        cs_update_emergency_alert_status($conn, $alertId, 'acknowledged');
    }
    header('Location: receptionist.php?tab=announcements&ack=1');
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'add_announcement' && $canPostAnnouncements) {
    $msg = trim($_POST['message'] ?? '');
    $roleTarget = trim($_POST['role_target'] ?? 'All');
    $uid = (int)($_SESSION['user_id'] ?? 0);
    if ($uid > 0 && $msg !== '') {
        cs_add_announcement($conn, $province, $city, $facility, $uid, $roleTarget, $msg);
    }
    header('Location: receptionist.php?tab=announcements');
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'add_appointment') {
    $patientId = (int)($_POST['appt_patient_id'] ?? 0);
    $doctorId  = (int)($_POST['appt_doctor_id'] ?? 0);
    if ($patientId > 0 && $doctorId > 0) {
        cs_add_appointment(
            $conn,
            $facilityId,
            $patientId,
            $doctorId,
            $_POST['appt_date'] ?? date('Y-m-d'),
            $_POST['appt_time'] ?? '09:00',
            trim($_POST['appt_reason'] ?? '')
        );
    }
    header('Location: receptionist.php?tab=appointments');
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'edit_appointment') {
    $aid = (int)($_POST['appointment_id'] ?? 0);
    if ($aid > 0) {
        cs_update_appointment(
            $conn,
            $aid,
            [
                'patient_id'       => (int)($_POST['edit_appt_patient_id'] ?? 0),
                'doctor_id'        => (int)($_POST['edit_appt_doctor_id'] ?? 0),
                'appointment_date' => $_POST['edit_appt_date'] ?? '',
                'appointment_time' => $_POST['edit_appt_time'] ?? '',
                'notes'            => trim($_POST['edit_appt_reason'] ?? ''),
                'status'           => trim($_POST['edit_appt_status'] ?? 'Scheduled'),
            ],
            0,
            $facilityId
        );
    }
    header('Location: receptionist.php?tab=appointments');
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'cancel_appointment') {
    $aid = (int)($_POST['appointment_id'] ?? 0);
    if ($aid > 0) {
        cs_update_appointment($conn, $aid, ['status' => 'Cancelled']);
    }
    header('Location: receptionist.php?tab=appointments');
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'queue_update_status') {
    $pid = (int)($_POST['patient_id'] ?? 0);
    $status = trim($_POST['queue_status'] ?? '');
    if ($pid > 0 && $status !== '') {
        cs_update_patient($conn, $facilityId, $pid, ['status' => $status]);
    }
    header('Location: receptionist.php?tab=queue');
    exit;
}

$statusData = cs_patient_status_counts($conn, $facilityId);
[$hourLabels, $hourCounts] = cs_hourly_patient_counts($conn, $facilityId);

$activityRows = cs_fetch_patients($conn, $facilityId, [
    'limit' => 8,
    'order' => 'id DESC',
]);

$totalPatients = cs_count_patients($conn, $facilityId);
$waitingCount = cs_count_patients($conn, $facilityId, " AND p.status = 'Waiting' ");
$registeredCount = cs_count_patients($conn, $facilityId, " AND p.status = 'Registered' ");
$todayPatients = count(array_filter(
    cs_fetch_patients($conn, $facilityId),
    function ($p) {
        return !empty($p['created_at']) && date('Y-m-d', strtotime($p['created_at'])) === date('Y-m-d');
    }
));

$withNurseCount      = cs_count_patients($conn, $facilityId, " AND status = 'With Nurse' ");
$waitingDoctorCount  = cs_count_patients($conn, $facilityId, " AND status = 'Waiting_Doctor' ");
$withDoctorCount     = cs_count_patients($conn, $facilityId, " AND status = 'With Doctor' ");
$completedCount      = cs_count_patients($conn, $facilityId, " AND status = 'Completed' ");
$inCareCount         = $withNurseCount + $waitingDoctorCount + $withDoctorCount;

$todayAppointments   = cs_fetch_appointments($conn, $facilityId, ['date' => date('Y-m-d')]);
$todayApptTotal       = count($todayAppointments);
$todayApptScheduled   = count(array_filter($todayAppointments, fn($a) => strtolower((string)($a['status'] ?? '')) === 'scheduled'));
$todayApptCompleted   = count(array_filter($todayAppointments, fn($a) => strtolower((string)($a['status'] ?? '')) === 'completed'));
$todayApptCancelled   = count(array_filter($todayAppointments, fn($a) => strtolower((string)($a['status'] ?? '')) === 'cancelled'));

$reportPatients      = cs_fetch_patients($conn, $facilityId, ['order' => 'full_name ASC']);
$departmentCounts    = [];
foreach ($reportPatients as $rp) {
    $dept = $rp['department'] ?? 'Reception';
    $departmentCounts[$dept] = ($departmentCounts[$dept] ?? 0) + 1;
}

$reportDate          = date('l, d F Y');
$reportGeneratedAt   = date('H:i');

$search = trim($_GET['search'] ?? '');
$announcementRows = cs_fetch_announcements($conn, $province, $city, $facility, $currentRole, ['limit' => 20]);
$latestAnnouncementId = 0;
foreach ($announcementRows as $annRow) {
    $aid = (int)($annRow['announcement_id'] ?? 0);
    if ($aid > $latestAnnouncementId) {
        $latestAnnouncementId = $aid;
    }
}
$doctors = cs_fetch_doctors($conn, $province, $city, $facility);
$pendingEmergencies = $emergencyTriageSupported
    ? cs_fetch_emergency_alerts($conn, $province, $city, $facility, ['status' => 'pending'])
    : [];
$recentEmergencies = $emergencyTriageSupported
    ? cs_fetch_emergency_alerts($conn, $province, $city, $facility, ['limit' => 10])
    : [];
$pendingEmergencyCount = count($pendingEmergencies);

$userName = $_SESSION['name'] ?? 'Reception';
$userInitials = strtoupper(substr($userName, 0, 1));
$roleLabel = cs_role_label($currentRole);

$navItems = [
    'dashboard' => ['icon' => '📊', 'label' => 'Dashboard'],
    'patients' => ['icon' => '👥', 'label' => 'Patients'],
    'appointments' => ['icon' => '📅', 'label' => 'Appointments'],
    'announcements' => ['icon' => '📢', 'label' => 'Announcements'],
    'queue' => ['icon' => '📋', 'label' => 'Queue'],
    'reports' => ['icon' => '📈', 'label' => 'Reports'],
];

include __DIR__ . '/views/receptionist_view.php';
