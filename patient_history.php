<?php
session_start();
include 'helpers.php';
include 'db.php';
include 'ui_theme.php';

if(function_exists('require_login')){
    require_login();
} else {
    if(!isset($_SESSION['user_id'])){
        header('Location: login.php');
        exit;
    }
}

$currentRole = strtolower(trim((string)($_SESSION['role'] ?? '')));
$allowedRoles = ['doctor', 'nurse'];

if(!in_array($currentRole, $allowedRoles, true)){
    header('Location: login.php');
    exit;
}

include_once 'clinic_schema.php';

$province    = $_SESSION['province'] ?? '';
$city        = $_SESSION['city'] ?? '';
$facility    = $_SESSION['facility'] ?? '';
$facilityId  = cs_facility_id($conn, $province, $city, $facility);

function h($value){
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$backLinks = [
    'doctor' => 'doctor.php?tab=patients',
    'nurse' => 'nurse.php?tab=patients',
];
$backLink = $backLinks[$currentRole] ?? 'login.php';

$patientId = (int)($_GET['patient_id'] ?? 0);
$patient   = null;
$records   = [];
$referrals = [];
$accessDenied = false;

if($patientId > 0){
    $patientSql = "SELECT u.user_id AS id, u.full_name, "
        . "IFNULL(u.id_number, '') AS id_number, IFNULL(u.phone, '') AS phone, "
        . "IFNULL(u.status, '') AS status, IFNULL(u.province, '') AS province, "
        . "IFNULL(u.city, '') AS city, IFNULL(u.facility, '') AS facility "
        . "FROM users u "
        . "WHERE u.user_id = $patientId AND u.account_type = 'patient' "
        . "AND " . cs_patient_facility_filter_sql($conn, $facilityId, 'u');

    $pq = mysqli_query($conn, $patientSql);
    if($pq && mysqli_num_rows($pq) > 0){
        $patient = mysqli_fetch_assoc($pq);
    }
}

if($patient){
    if(!cs_can_role_view_patient_history($currentRole, $patient['status'])){
        $accessDenied = true;
    } else {
        $records = cs_fetch_patient_medical_history($conn, $facilityId, $patientId);
        if($currentRole === 'doctor' && cs_referrals_supported($conn)){
            $referrals = cs_fetch_patient_referrals($conn, $facilityId, $patientId);
        }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Patient Medical History</title>
    <style>
        :root{
            --primary: <?= $theme['primary'] ?>;
            --primaryDark: <?= $theme['primaryDark'] ?>;
            --accent: <?= $theme['accent'] ?>;
            --bg:#f4f6f9;
            --card:#ffffff;
            --text:#0f172a;
            --muted:#6b7280;
            --border:#e5e7eb;
            --success:#059669;
            --info:#0284c7;
        }
        body{ margin:0; font-family:Segoe UI, Arial, sans-serif; background:var(--bg); color:var(--text); }
        .page-wrap{ max-width:1120px; margin:0 auto; padding:24px; }
        .topbar{ display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:24px; }
        .topbar h1{ margin:0; font-size:24px; }
        .btn{ display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:10px 16px; border:none; border-radius:10px; text-decoration:none; font-weight:700; cursor:pointer; }
        .btn.primary{ background:var(--primary); color:#fff; }
        .btn.secondary{ background:#f3f4f6; color:#111827; }
        .card{ background:#ffffff; border:1px solid var(--border); border-radius:18px; padding:24px; margin-bottom:20px; }
        .sectionTitle{ display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:18px; }
        .record-row{ display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:12px; }
        .record-box{ background:#f8fafc; border:1px solid var(--border); border-radius:14px; padding:16px; }
        .muted{ color:var(--muted); }
        .tag{ display:inline-flex; padding:6px 10px; border-radius:999px; background:#e2e8f0; color:#334155; font-size:13px; }
        .record-card{ background:#ffffff; border:1px solid #e5e7eb; border-radius:16px; padding:18px; margin-bottom:14px; }
        .record-card strong{ display:block; margin-bottom:8px; }
        .field-row{ margin-top:8px; }
        .alert{ padding:14px 16px; border-radius:12px; background:#fff7ed; border:1px solid #fed7aa; color:#9a3412; }
    </style>
</head>
<body>
    <div class="page-wrap">
        <div class="topbar">
            <div>
                <h1>Patient Medical History</h1>
                <div class="muted">Full visit records<?= $currentRole === 'doctor' ? ' and referrals' : '' ?> for the patient.</div>
            </div>
            <a href="<?= h($backLink) ?>" class="btn secondary">← Back</a>
        </div>

        <?php if(!$patient): ?>
            <div class="card">
                <h2 class="muted">Patient not found</h2>
                <p>The patient ID is missing or invalid, or the patient does not belong to your facility.</p>
            </div>
        <?php elseif($accessDenied): ?>
            <div class="card">
                <div class="alert">
                    This patient's medical record is not available at their current workflow stage.
                    <?php if($currentRole === 'doctor'): ?>
                    Completed patients are managed by reception. Re-admit the patient to the doctor queue to view their record.
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="sectionTitle">
                    <div>
                        <h2 style="margin:0;"><?= h($patient['full_name']) ?></h2>
                        <div class="muted" style="margin-top:6px;">Patient ID: <?= h($patient['id_number']) ?> • <?= h($patient['status']) ?></div>
                    </div>
                    <div style="text-align:right;">
                        <div class="tag">Province: <?= h($patient['province']) ?></div>
                        <div class="tag" style="margin-left:8px;">City: <?= h($patient['city']) ?></div>
                        <div class="tag" style="margin-left:8px;">Facility: <?= h($patient['facility']) ?></div>
                    </div>
                </div>

                <div class="record-row">
                    <div class="record-box">
                        <div class="muted">Phone</div>
                        <div><?= h($patient['phone']) ?></div>
                    </div>
                    <div class="record-box">
                        <div class="muted">Patient Status</div>
                        <div><?= h($patient['status']) ?></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="sectionTitle">
                    <div>
                        <h3 style="margin:0;">Medical Records</h3>
                        <div class="muted">Listed by most recent consultation first.</div>
                    </div>
                    <div class="tag"><?= count($records) ?> record<?= count($records) === 1 ? '' : 's' ?></div>
                </div>
                <?php if(count($records) === 0): ?>
                    <div class="muted">No medical records have been created for this patient yet.</div>
                <?php else: ?>
                    <?php foreach($records as $record): ?>
                        <div class="record-card">
                            <strong><?= h($record['visit_date'] ? date('Y-m-d H:i', strtotime($record['visit_date'])) : 'Unknown visit') ?> • <?= h($record['visit_type'] ?? 'Consultation') ?></strong>
                            <div class="field-row"><strong>Doctor:</strong> <?= h($record['doctor_name'] ?: 'Unknown') ?></div>
                            <div class="field-row"><strong>Diagnosis:</strong> <?= h($record['diagnosis'] ?: '-') ?></div>
                            <div class="field-row"><strong>Prescription:</strong> <?= h($record['prescription'] ?: '-') ?></div>
                            <?php if(!empty($record['medication'])): ?>
                            <div class="field-row"><strong>Medication Dispensed:</strong> <?= h($record['medication']) ?></div>
                            <?php endif; ?>
                            <?php if(!empty($record['vitals'])): ?>
                            <div class="field-row"><strong>Vitals:</strong> <?= h($record['vitals']) ?></div>
                            <?php endif; ?>
                            <div class="field-row"><strong>Symptoms:</strong> <?= h($record['symptoms'] ?: '-') ?></div>
                            <div class="field-row"><strong>Allergies:</strong> <?= h($record['allergies'] ?: '-') ?></div>
                            <div class="field-row"><strong>Chronic Conditions:</strong> <?= h($record['chronic_conditions'] ?: '-') ?></div>
                            <div class="field-row"><strong>Treatment Plan:</strong> <?= h($record['treatment_plan'] ?: '-') ?></div>
                            <div class="field-row"><strong>Follow-up Date:</strong> <?= h($record['follow_up_date'] ?: '-') ?></div>
                            <div class="field-row"><strong>Follow-up Notes:</strong> <?= h($record['follow_up_notes'] ?: '-') ?></div>
                            <div class="field-row"><strong>Status:</strong> <?= h($record['status'] ?: '-') ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if($currentRole === 'doctor'): ?>
            <div class="card">
                <div class="sectionTitle">
                    <div>
                        <h3 style="margin:0;">Referral History</h3>
                        <div class="muted">Referrals created for this patient.</div>
                    </div>
                    <div class="tag"><?= count($referrals) ?> referral<?= count($referrals) === 1 ? '' : 's' ?></div>
                </div>
                <?php if(count($referrals) === 0): ?>
                    <div class="muted">No referrals have been logged for this patient yet.</div>
                <?php else: ?>
                    <?php foreach($referrals as $ref): ?>
                        <div class="record-card">
                            <strong><?= h($ref['referral_status'] ?: 'Pending') ?> • <?= h($ref['created_at'] ? date('Y-m-d H:i', strtotime($ref['created_at'])) : 'Unknown date') ?></strong>
                            <div class="field-row"><strong>Referring doctor:</strong> <?= h($ref['doctor_name'] ?: '-') ?></div>
                            <div class="field-row"><strong>From facility:</strong> <?= h(trim(($ref['from_facility'] ?? '') . ' — ' . ($ref['from_city'] ?? '') . ', ' . ($ref['from_province'] ?? ''), ' —,')) ?: '-' ?></div>
                            <div class="field-row"><strong>To facility:</strong> <?= h(trim(($ref['to_facility'] ?? '') . ' — ' . ($ref['to_city'] ?? '') . ', ' . ($ref['to_province'] ?? ''), ' —,')) ?: '-' ?></div>
                            <div class="field-row"><strong>Clinical summary:</strong> <?= h($ref['consultation_details'] ?: '-') ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
