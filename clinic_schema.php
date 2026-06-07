<?php

/**

 * Shared data-access helpers for clinic_system_demo_v2 schema.

 * Users-only model: staff + patients in users; appointments FK -> users.user_id.

 */



function cs_table_exists($conn, $table) {

    $table = mysqli_real_escape_string($conn, $table);

    $q = mysqli_query($conn, "SHOW TABLES LIKE '$table'");

    return $q && mysqli_num_rows($q) > 0;

}



function cs_column_exists($conn, $table, $col) {

    $table = mysqli_real_escape_string($conn, $table);

    $col   = mysqli_real_escape_string($conn, $col);

    $q = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$col'");

    return $q && mysqli_num_rows($q) > 0;

}



function cs_esc($conn, $value) {

    return mysqli_real_escape_string($conn, (string)$value);

}



function cs_h($value) {

    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');

}



function cs_staff_name_sql($alias = 'u') {

    return "CONCAT($alias.first_name, ' ', $alias.surname)";

}



function cs_facility_id($conn, $province, $city, $facility) {

    static $cache = [];

    $key = strtolower("$province|$city|$facility");

    if (isset($cache[$key])) {

        return $cache[$key];

    }



    if (!cs_table_exists($conn, 'facilities')) {

        return 0;

    }



    $p = cs_esc($conn, $province);

    $c = cs_esc($conn, $city);

    $f = cs_esc($conn, $facility);



    $q = mysqli_query($conn, "

        SELECT facility_id

        FROM facilities

        WHERE LOWER(TRIM(province)) = LOWER(TRIM('$p'))

          AND LOWER(TRIM(city)) = LOWER(TRIM('$c'))

          AND LOWER(TRIM(facility_name)) = LOWER(TRIM('$f'))

        LIMIT 1

    ");



    if ($q && mysqli_num_rows($q) > 0) {

        $cache[$key] = (int)mysqli_fetch_assoc($q)['facility_id'];

        return $cache[$key];

    }



    mysqli_query($conn, "

        INSERT INTO facilities (province, city, facility_name)

        VALUES ('$p', '$c', '$f')

    ");

    $cache[$key] = (int)mysqli_insert_id($conn);

    return $cache[$key];

}



function cs_facility_location($conn, $facilityId) {

    $fid = (int)$facilityId;

    if ($fid <= 0 || !cs_table_exists($conn, 'facilities')) {

        return null;

    }

    $q = mysqli_query($conn, "

        SELECT province, city, facility_name AS facility

        FROM facilities

        WHERE facility_id = $fid

        LIMIT 1

    ");

    return ($q && mysqli_num_rows($q) > 0) ? mysqli_fetch_assoc($q) : null;

}



function cs_location_filter_sql($conn, $province, $city, $facility, $alias = 'u') {

    $p = cs_esc($conn, $province);

    $c = cs_esc($conn, $city);

    $f = cs_esc($conn, $facility);

    return "

        LOWER(TRIM($alias.province)) = LOWER(TRIM('$p'))

        AND LOWER(TRIM($alias.city)) = LOWER(TRIM('$c'))

        AND LOWER(TRIM($alias.facility)) = LOWER(TRIM('$f'))

    ";

}



function cs_patient_facility_filter_sql($conn, $facilityId, $alias = 'u') {

    $fid = (int)$facilityId;

    $loc = cs_facility_location($conn, $fid);

    if (!$loc) {

        return '1=0';

    }



    $locationSql = cs_location_filter_sql($conn, $loc['province'], $loc['city'], $loc['facility'], $alias);

    if (cs_column_exists($conn, 'users', 'facility_id')) {

        return "(

            $alias.facility_id = $fid

            OR (

                ($alias.facility_id IS NULL OR $alias.facility_id = 0)

                AND $locationSql

            )

        )";

    }



    return $locationSql;

}



function cs_build_patient_filters($conn, array $opts, $statusCol = 'status') {

    $search = trim($opts['search'] ?? '');

    $status = $opts['status'] ?? null;

    $statusIn = $opts['status_in'] ?? null;

    $extra = '';



    if ($search !== '') {

        $s = cs_esc($conn, $search);

        $extra .= " AND (full_name LIKE '%$s%' OR id_number LIKE '%$s%' OR phone LIKE '%$s%')";

    }

    if ($status !== null && $status !== '') {

        $st = cs_esc($conn, $status);

        $extra .= " AND $statusCol = '$st'";

    }

    if (is_array($statusIn) && count($statusIn) > 0) {

        $parts = array_map(function ($v) use ($conn) {

            return "'" . cs_esc($conn, $v) . "'";

        }, $statusIn);

        $extra .= " AND $statusCol IN (" . implode(',', $parts) . ")";

    }



    return $extra;

}



function cs_normalize_patient_order($order) {

    $order = trim((string)$order);

    if ($order === '') {

        return 'full_name ASC';

    }



    $map = [

        'p.patient_id' => 'id',

        'u.user_id'    => 'id',

        'p.full_name'  => 'full_name',

        'u.full_name'  => 'full_name',

        'p.status'     => 'status',

        'u.status'     => 'status',

        'p.created_at' => 'created_at',

        'u.created_at' => 'created_at',

        'p.id_number'  => 'id_number',

        'u.id_number'  => 'id_number',

    ];



    return str_replace(array_keys($map), array_values($map), $order);

}



function cs_fetch_patients($conn, $facilityId, array $opts = []) {

    if (!cs_table_exists($conn, 'users') || !cs_column_exists($conn, 'users', 'account_type')) {

        return [];

    }



    $fid      = (int)$facilityId;

    $order    = cs_normalize_patient_order($opts['order'] ?? 'full_name ASC');

    $limit    = isset($opts['limit']) ? (int)$opts['limit'] : 0;

    $limitSql = $limit > 0 ? " LIMIT $limit" : '';



    $hasStatus = cs_column_exists($conn, 'users', 'status');

    $hasDept   = cs_column_exists($conn, 'users', 'department');

    $hasVitals = cs_column_exists($conn, 'users', 'bp');

    $statusExpr = $hasStatus ? "IFNULL(u.status, 'Waiting')" : "'Waiting'";

    $deptExpr   = $hasDept ? "IFNULL(u.department, 'Reception')" : "'Reception'";

    $bpExpr     = $hasVitals ? "IFNULL(u.bp, '')" : "''";

    $tempExpr   = $hasVitals ? "IFNULL(u.temp, '')" : "''";

    $pulseExpr  = $hasVitals ? "IFNULL(u.pulse, '')" : "''";

    $weightExpr = $hasVitals ? "IFNULL(u.weight, '')" : "''";

    $notesExpr  = cs_column_exists($conn, 'users', 'notes') ? "IFNULL(u.notes, '')" : "''";

    $dxExpr     = cs_column_exists($conn, 'users', 'diagnosis') ? "IFNULL(u.diagnosis, '')" : "''";

    $rxExpr     = cs_column_exists($conn, 'users', 'prescription') ? "IFNULL(u.prescription, '')" : "''";

    $medExpr    = cs_column_exists($conn, 'users', 'medication') ? "IFNULL(u.medication, '')" : "''";



    $filter = cs_build_patient_filters($conn, $opts, $statusExpr);



    $sql = "

        SELECT

            u.user_id AS id,

            u.full_name,

            IFNULL(u.id_number, '') AS id_number,

            IFNULL(u.phone, '') AS phone,

            '' AS gender,

            NULL AS date_of_birth,

            $statusExpr AS status,

            $deptExpr AS department,

            $bpExpr AS bp,

            $tempExpr AS temp,

            $pulseExpr AS pulse,

            $weightExpr AS weight,

            $notesExpr AS notes,

            $dxExpr AS diagnosis,

            $rxExpr AS prescription,

            $medExpr AS medication,

            u.created_at

        FROM users u

        WHERE u.account_type = 'patient'

          AND " . cs_patient_facility_filter_sql($conn, $fid, 'u') . "

          $filter

        ORDER BY $order

        $limitSql

    ";



    $rows = [];

    $q = mysqli_query($conn, $sql);

    if ($q) {

        while ($row = mysqli_fetch_assoc($q)) {

            $rows[] = $row;

        }

    }

    return $rows;

}



function cs_count_patients($conn, $facilityId, $extraWhere = '') {

    $opts = [];

    if ($extraWhere !== '' && preg_match("/status\s*=\s*'([^']+)'/", $extraWhere, $m)) {

        $opts['status'] = $m[1];

    }

    if ($extraWhere !== '' && preg_match("/status\s+IN\s*\(([^)]+)\)/i", $extraWhere, $m)) {

        $opts['status_in'] = array_map(function ($v) {

            return trim($v, " '\"");

        }, explode(',', $m[1]));

    }

    return cs_count_patients_filtered($conn, $facilityId, $opts);

}



function cs_count_patients_filtered($conn, $facilityId, array $opts = []) {

    return count(cs_fetch_patients($conn, $facilityId, $opts));

}



function cs_patient_status_counts($conn, $facilityId) {

    $data = [];

    foreach (cs_fetch_patients($conn, $facilityId) as $row) {

        $st = $row['status'] ?? 'Waiting';

        $data[$st] = ($data[$st] ?? 0) + 1;

    }

    return $data;

}



function cs_add_patient($conn, $facilityId, $fullName, $idNumber, $phone = '', $province = '', $city = '', $facility = '') {

    $loc = cs_facility_location($conn, $facilityId);

    $province = $province ?: ($loc['province'] ?? '');

    $city     = $city ?: ($loc['city'] ?? '');

    $facility = $facility ?: ($loc['facility'] ?? '');



    $name = cs_esc($conn, $fullName);

    $idn  = cs_esc($conn, $idNumber);

    $ph   = cs_esc($conn, $phone);

    $fid  = (int)$facilityId;

    $p    = cs_esc($conn, $province);

    $c    = cs_esc($conn, $city);

    $f    = cs_esc($conn, $facility);



    $nameParts = preg_split('/\s+/', trim($fullName), 2);

    $first = cs_esc($conn, $nameParts[0] ?? $fullName);

    $last  = cs_esc($conn, $nameParts[1] ?? '');



    $userCols = ['account_type', 'first_name', 'surname', 'full_name', 'id_number', 'phone', 'province', 'city', 'facility'];

    $userVals = ["'patient'", "'$first'", "'$last'", "'$name'", "'$idn'", "'$ph'", "'$p'", "'$c'", "'$f'"];



    if (cs_column_exists($conn, 'users', 'facility_id')) {

        $userCols[] = 'facility_id';

        $userVals[] = (string)$fid;

    }

    if (cs_column_exists($conn, 'users', 'status')) {

        $userCols[] = 'status';

        $userVals[] = "'Waiting'";

    }

    if (cs_column_exists($conn, 'users', 'department')) {

        $userCols[] = 'department';

        $userVals[] = "'Reception'";

    }



    mysqli_query($conn, "

        INSERT INTO users (" . implode(', ', $userCols) . ")

        VALUES (" . implode(', ', $userVals) . ")

    ");



    return (int)mysqli_insert_id($conn);

}



function cs_update_patient($conn, $facilityId, $patientId, array $fields) {

    $pid = (int)$patientId;

    $fid = (int)$facilityId;

    if ($pid <= 0) {

        return false;

    }



    $allowed = [

        'full_name', 'id_number', 'phone', 'status', 'department',

        'bp', 'temp', 'pulse', 'weight', 'notes',

        'diagnosis', 'prescription', 'medication'

    ];

    $sets = [];

    foreach ($fields as $key => $value) {

        if (!in_array($key, $allowed, true) || !cs_column_exists($conn, 'users', $key)) {

            continue;

        }

        $sets[] = "`$key`='" . cs_esc($conn, $value) . "'";

    }

    if (!empty($fields['full_name'])) {

        $parts = preg_split('/\s+/', trim($fields['full_name']), 2);

        if (cs_column_exists($conn, 'users', 'first_name')) {

            $sets[] = "first_name='" . cs_esc($conn, $parts[0] ?? $fields['full_name']) . "'";

        }

        if (cs_column_exists($conn, 'users', 'surname')) {

            $sets[] = "surname='" . cs_esc($conn, $parts[1] ?? '') . "'";

        }

    }

    if (empty($sets)) {

        return false;

    }



    return mysqli_query($conn, "

        UPDATE users

        SET " . implode(', ', $sets) . "

        WHERE user_id = $pid

          AND account_type = 'patient'

          AND " . cs_patient_facility_filter_sql($conn, $fid, 'users') . "

    ");

}



function cs_fetch_doctors($conn, $province, $city, $facility) {

    $p = cs_esc($conn, $province);

    $c = cs_esc($conn, $city);

    $f = cs_esc($conn, $facility);



    $sql = "

        SELECT user_id AS id, first_name, surname, " . cs_staff_name_sql('u') . " AS full_name,

               IFNULL(email, '') AS email, IFNULL(phone, '') AS phone

        FROM users u

        WHERE account_type = 'staff'

          AND role = 'doctor'

          AND LOWER(TRIM(province)) = LOWER(TRIM('$p'))

          AND LOWER(TRIM(city)) = LOWER(TRIM('$c'))

          AND LOWER(TRIM(facility)) = LOWER(TRIM('$f'))

        ORDER BY first_name ASC

    ";



    $rows = [];

    $q = mysqli_query($conn, $sql);

    if ($q) {

        while ($row = mysqli_fetch_assoc($q)) {

            $rows[] = $row;

        }

    }

    return $rows;

}



function cs_validate_patient_user($conn, $facilityId, $userId) {

    $uid = (int)$userId;

    $fid = (int)$facilityId;

    if ($uid <= 0) {

        return 0;

    }



    $q = mysqli_query($conn, "

        SELECT user_id

        FROM users

        WHERE user_id = $uid

          AND account_type = 'patient'

          AND " . cs_patient_facility_filter_sql($conn, $fid, 'users') . "

        LIMIT 1

    ");



    return ($q && mysqli_num_rows($q) > 0) ? $uid : 0;

}



function cs_validate_doctor_user($conn, $province, $city, $facility, $userId) {

    $uid = (int)$userId;

    if ($uid <= 0) {

        return 0;

    }



    $p = cs_esc($conn, $province);

    $c = cs_esc($conn, $city);

    $f = cs_esc($conn, $facility);



    $q = mysqli_query($conn, "

        SELECT user_id

        FROM users

        WHERE user_id = $uid

          AND account_type = 'staff'

          AND role = 'doctor'

          AND LOWER(TRIM(province)) = LOWER(TRIM('$p'))

          AND LOWER(TRIM(city)) = LOWER(TRIM('$c'))

          AND LOWER(TRIM(facility)) = LOWER(TRIM('$f'))

        LIMIT 1

    ");



    return ($q && mysqli_num_rows($q) > 0) ? $uid : 0;

}



function cs_fetch_appointments($conn, $facilityId, array $opts = []) {

    if (!cs_table_exists($conn, 'appointments')) {

        return [];

    }



    $date = cs_esc($conn, $opts['date'] ?? date('Y-m-d'));

    $search = trim($opts['search'] ?? '');

    $doctorId = isset($opts['doctor_id']) ? (int)$opts['doctor_id'] : 0;

    $fid = (int)$facilityId;



    $extra = " AND a.facility_id = $fid AND a.appointment_date = '$date' ";

    if ($doctorId > 0) {

        $extra .= " AND a.doctor_id = $doctorId ";

    }

    if ($search !== '') {

        $s = cs_esc($conn, $search);

        $extra .= " AND pu.full_name LIKE '%$s%' ";

    }



    $sql = "

        SELECT

            a.appointment_id AS id,

            a.patient_id,

            a.doctor_id,

            a.appointment_date,

            a.appointment_time,

            a.status,

            IFNULL(a.notes, '') AS reason,

            pu.full_name AS patient_name,

            du.full_name AS doctor_name

        FROM appointments a

        LEFT JOIN users pu ON pu.user_id = a.patient_id AND pu.account_type = 'patient'

        LEFT JOIN users du ON du.user_id = a.doctor_id AND du.account_type = 'staff' AND du.role = 'doctor'

        WHERE 1=1 $extra

        ORDER BY a.appointment_time ASC

    ";



    $rows = [];

    $q = mysqli_query($conn, $sql);

    if ($q) {

        while ($row = mysqli_fetch_assoc($q)) {

            $rows[] = $row;

        }

    }

    return $rows;

}



function cs_add_appointment($conn, $facilityId, $patientUserId, $doctorUserId, $date, $time, $notes = '') {

    $fid = (int)$facilityId;

    $loc = cs_facility_location($conn, $fid);

    if (!$loc) {

        return false;

    }



    $puid = cs_validate_patient_user($conn, $fid, $patientUserId);

    $duid = cs_validate_doctor_user($conn, $loc['province'], $loc['city'], $loc['facility'], $doctorUserId);

    $d    = cs_esc($conn, $date);

    $t    = cs_esc($conn, $time);

    $n    = cs_esc($conn, $notes);



    if ($puid <= 0 || $duid <= 0) {

        return false;

    }



    return mysqli_query($conn, "

        INSERT INTO appointments (patient_id, doctor_id, facility_id, appointment_date, appointment_time, status, notes)

        VALUES ($puid, $duid, $fid, '$d', '$t', 'Scheduled', '$n')

    ");

}



function cs_update_appointment($conn, $appointmentId, array $fields, $doctorUserId = 0, $facilityId = 0) {

    $allowed = ['patient_id', 'doctor_id', 'appointment_date', 'appointment_time', 'status', 'notes'];

    $sets = [];

    $loc = ((int)$facilityId > 0) ? cs_facility_location($conn, (int)$facilityId) : null;



    if (isset($fields['patient_id']) && (int)$facilityId > 0) {

        $resolved = cs_validate_patient_user($conn, (int)$facilityId, (int)$fields['patient_id']);

        if ($resolved <= 0) {

            return false;

        }

        $fields['patient_id'] = $resolved;

    }



    if (isset($fields['doctor_id']) && $loc) {

        $resolvedDoctor = cs_validate_doctor_user(

            $conn,

            $loc['province'],

            $loc['city'],

            $loc['facility'],

            (int)$fields['doctor_id']

        );

        if ($resolvedDoctor <= 0) {

            return false;

        }

        $fields['doctor_id'] = $resolvedDoctor;

    }



    foreach ($fields as $key => $value) {

        if (!in_array($key, $allowed, true)) {

            continue;

        }

        if (in_array($key, ['patient_id', 'doctor_id'], true)) {

            $sets[] = "$key=" . (int)$value;

        } else {

            $sets[] = "$key='" . cs_esc($conn, $value) . "'";

        }

    }

    if (empty($sets)) {

        return false;

    }



    $aid = (int)$appointmentId;

    $extra = $doctorUserId > 0 ? " AND doctor_id = $doctorUserId " : '';



    return mysqli_query($conn, "

        UPDATE appointments SET " . implode(', ', $sets) . "

        WHERE appointment_id = $aid $extra

    ");

}



function cs_announcements_supported($conn) {

    return cs_table_exists($conn, 'announcements')

        && cs_column_exists($conn, 'announcements', 'message')

        && cs_column_exists($conn, 'announcements', 'created_by');

}



function cs_emergency_triage_supported($conn) {

    return cs_announcements_supported($conn)

        && cs_column_exists($conn, 'announcements', 'priority')

        && cs_column_exists($conn, 'announcements', 'patient_id')

        && cs_column_exists($conn, 'announcements', 'alert_status');

}



function cs_fetch_announcements($conn, $province, $city, $facility, $currentRole, array $opts = []) {

    if (!cs_announcements_supported($conn)) {

        return [];

    }



    $p = cs_esc($conn, $province);

    $c = cs_esc($conn, $city);

    $f = cs_esc($conn, $facility);

    $role = cs_esc($conn, $currentRole);

    $roleLabel = cs_esc($conn, cs_role_label($currentRole));

    $sinceId = isset($opts['since_id']) ? (int)$opts['since_id'] : 0;

    $limit = isset($opts['limit']) ? max(1, (int)$opts['limit']) : 0;



    $extra = '';

    if ($sinceId > 0) {

        $extra .= " AND a.announcement_id > $sinceId ";

    }



    $sql = "

        SELECT a.*, " . cs_staff_name_sql('u') . " AS poster_name

        FROM announcements a

        LEFT JOIN users u ON u.user_id = a.created_by

        WHERE a.province = '$p'

          AND a.city = '$c'

          AND a.facility = '$f'

          AND (

            LOWER(TRIM(a.role_target)) = 'all'

            OR LOWER(TRIM(a.role_target)) = LOWER(TRIM('$role'))

            OR LOWER(TRIM(a.role_target)) = LOWER(TRIM('$roleLabel'))

          )

          $extra

        ORDER BY a.announcement_id DESC

    ";

    if ($limit > 0) {

        $sql .= " LIMIT $limit ";

    }



    $rows = [];

    $q = mysqli_query($conn, $sql);

    if ($q) {

        while ($row = mysqli_fetch_assoc($q)) {

            $rows[] = $row;

        }

    }

    return $rows;

}



function cs_add_announcement($conn, $province, $city, $facility, $userId, $roleTarget, $message) {

    if (!cs_announcements_supported($conn)) {

        return false;

    }



    $p = cs_esc($conn, $province);

    $c = cs_esc($conn, $city);

    $f = cs_esc($conn, $facility);

    $uid = (int)$userId;

    $rt = cs_esc($conn, $roleTarget);

    $msg = cs_esc($conn, $message);



    return mysqli_query($conn, "

        INSERT INTO announcements (message, created_by, role_target, province, city, facility)

        VALUES ('$msg', $uid, '$rt', '$p', '$c', '$f')

    ");

}



function cs_emergency_types() {

    return [

        'Cardiac / Heart attack',

        'Respiratory distress',

        'Stroke symptoms',

        'Trauma / Severe injury',

        'Severe allergic reaction',

        'Critical referral',

        'Other critical emergency',

    ];

}



function cs_add_emergency_alert($conn, $province, $city, $facility, $userId, $patientId, $emergencyType, $details, $sourceRole) {

    if (!cs_emergency_triage_supported($conn)) {

        return false;

    }



    $patientId = (int)$patientId;

    $uid = (int)$userId;

    $type = trim((string)$emergencyType);

    $details = trim((string)$details);

    $sourceRole = trim((string)$sourceRole);



    if ($uid <= 0 || $patientId <= 0 || $type === '' || $details === '') {

        return false;

    }



    $p = cs_esc($conn, $province);

    $c = cs_esc($conn, $city);

    $f = cs_esc($conn, $facility);

    $typeEsc = cs_esc($conn, $type);

    $srcEsc = cs_esc($conn, $sourceRole);

    $detEsc = cs_esc($conn, $details);



    $patientName = '';

    $pq = mysqli_query($conn, "SELECT full_name FROM users WHERE user_id = $patientId LIMIT 1");

    if ($pq && ($pr = mysqli_fetch_assoc($pq))) {

        $patientName = trim((string)($pr['full_name'] ?? ''));

    }



    $namePart = $patientName !== '' ? $patientName : 'Patient #' . $patientId;

    $message = "EMERGENCY — $type\nPatient: $namePart\n$details";



    $msgEsc = cs_esc($conn, $message);



    return mysqli_query($conn, "

        INSERT INTO announcements (

            message, created_by, role_target, province, city, facility,

            priority, patient_id, source_role, emergency_type, alert_status

        ) VALUES (

            '$msgEsc', $uid, 'Reception', '$p', '$c', '$f',

            'emergency', $patientId, '$srcEsc', '$typeEsc', 'pending'

        )

    ");

}



function cs_fetch_emergency_alerts($conn, $province, $city, $facility, array $opts = []) {

    if (!cs_emergency_triage_supported($conn)) {

        return [];

    }



    $p = cs_esc($conn, $province);

    $c = cs_esc($conn, $city);

    $f = cs_esc($conn, $facility);

    $status = isset($opts['status']) ? cs_esc($conn, $opts['status']) : '';

    $limit = isset($opts['limit']) ? max(1, (int)$opts['limit']) : 50;



    $extra = " AND a.priority = 'emergency' ";

    if ($status !== '') {

        $extra .= " AND a.alert_status = '$status' ";

    }



    $sql = "

        SELECT a.*, " . cs_staff_name_sql('u') . " AS poster_name,

               pt.full_name AS patient_name, pt.id_number AS patient_id_number

        FROM announcements a

        LEFT JOIN users u ON u.user_id = a.created_by

        LEFT JOIN users pt ON pt.user_id = a.patient_id

        WHERE a.province = '$p'

          AND a.city = '$c'

          AND a.facility = '$f'

          $extra

        ORDER BY a.announcement_id DESC

        LIMIT $limit

    ";



    $rows = [];

    $q = mysqli_query($conn, $sql);

    if ($q) {

        while ($row = mysqli_fetch_assoc($q)) {

            $rows[] = $row;

        }

    }

    return $rows;

}



function cs_fetch_staff_contact($conn, $staffId, $province, $city, $facility) {

    $sid = (int)$staffId;

    if ($sid <= 0) {

        return null;

    }



    $p = cs_esc($conn, $province);

    $c = cs_esc($conn, $city);

    $f = cs_esc($conn, $facility);



    $sql = "

        SELECT user_id, first_name, surname, " . cs_staff_name_sql('u') . " AS full_name,

               IFNULL(email, '') AS email, IFNULL(phone, '') AS phone, role

        FROM users u

        WHERE user_id = $sid

          AND account_type = 'staff'

          AND LOWER(TRIM(province)) = LOWER(TRIM('$p'))

          AND LOWER(TRIM(city)) = LOWER(TRIM('$c'))

          AND LOWER(TRIM(facility)) = LOWER(TRIM('$f'))

        LIMIT 1

    ";



    $q = mysqli_query($conn, $sql);

    if ($q && mysqli_num_rows($q) > 0) {

        return mysqli_fetch_assoc($q);

    }

    return null;

}



function cs_update_emergency_alert_status($conn, $announcementId, $status, $alertedStaffId = 0) {

    if (!cs_emergency_triage_supported($conn)) {

        return false;

    }



    $aid = (int)$announcementId;

    $status = cs_esc($conn, $status);

    $staffId = (int)$alertedStaffId;



    if ($aid <= 0 || $status === '') {

        return false;

    }



    $staffSql = $staffId > 0 ? ", alerted_staff_id = $staffId" : '';

    $mediSql = ($status === 'medi_alert_sent') ? ', medi_alert_sent_at = NOW()' : '';



    return mysqli_query($conn, "

        UPDATE announcements

        SET alert_status = '$status' $staffSql $mediSql

        WHERE announcement_id = $aid AND priority = 'emergency'

    ");

}



function cs_clear_doctor_medi_alerts_for_patient($conn, $doctorId, $patientId, $province, $city, $facility) {

    if (!cs_emergency_triage_supported($conn)) {

        return false;

    }



    $did = (int)$doctorId;

    $pid = (int)$patientId;

    if ($did <= 0 || $pid <= 0) {

        return false;

    }



    $p = cs_esc($conn, $province);

    $c = cs_esc($conn, $city);

    $f = cs_esc($conn, $facility);



    return mysqli_query($conn, "

        UPDATE announcements

        SET alert_status = 'acknowledged'

        WHERE priority = 'emergency'

          AND alerted_staff_id = $did

          AND patient_id = $pid

          AND alert_status = 'medi_alert_sent'

          AND province = '$p'

          AND city = '$c'

          AND facility = '$f'

    ");

}



function cs_count_pending_emergencies($conn, $province, $city, $facility) {

    if (!cs_emergency_triage_supported($conn)) {

        return 0;

    }



    $rows = cs_fetch_emergency_alerts($conn, $province, $city, $facility, [

        'status' => 'pending',

        'limit' => 100,

    ]);

    return count($rows);

}



function cs_fetch_doctor_medi_alerts($conn, $doctorId, $province, $city, $facility, array $opts = []) {

    if (!cs_emergency_triage_supported($conn)) {

        return [];

    }



    $did = (int)$doctorId;

    if ($did <= 0) {

        return [];

    }



    $p = cs_esc($conn, $province);

    $c = cs_esc($conn, $city);

    $f = cs_esc($conn, $facility);

    $status = isset($opts['status']) ? cs_esc($conn, $opts['status']) : 'medi_alert_sent';

    $limit = isset($opts['limit']) ? max(1, (int)$opts['limit']) : 20;



    $hasStatus = cs_column_exists($conn, 'users', 'status');

    $hasVitals = cs_column_exists($conn, 'users', 'bp');

    $statusExpr = $hasStatus ? "IFNULL(pt.status, 'Waiting')" : "'Waiting'";

    $bpExpr = $hasVitals ? "IFNULL(pt.bp, '')" : "''";

    $tempExpr = $hasVitals ? "IFNULL(pt.temp, '')" : "''";

    $pulseExpr = $hasVitals ? "IFNULL(pt.pulse, '')" : "''";

    $weightExpr = $hasVitals ? "IFNULL(pt.weight, '')" : "''";

    $notesExpr = cs_column_exists($conn, 'users', 'notes') ? "IFNULL(pt.notes, '')" : "''";



    $sql = "

        SELECT a.*, " . cs_staff_name_sql('u') . " AS poster_name,

               pt.user_id AS patient_user_id,

               pt.full_name AS patient_name,

               IFNULL(pt.id_number, '') AS patient_id_number,

               $statusExpr AS patient_status,

               $bpExpr AS patient_bp,

               $tempExpr AS patient_temp,

               $pulseExpr AS patient_pulse,

               $weightExpr AS patient_weight,

               $notesExpr AS patient_notes

        FROM announcements a

        LEFT JOIN users u ON u.user_id = a.created_by

        LEFT JOIN users pt ON pt.user_id = a.patient_id

        WHERE a.province = '$p'

          AND a.city = '$c'

          AND a.facility = '$f'

          AND a.priority = 'emergency'

          AND a.alerted_staff_id = $did

          AND a.alert_status = '$status'

        ORDER BY a.announcement_id DESC

        LIMIT $limit

    ";



    $rows = [];

    $q = mysqli_query($conn, $sql);

    if ($q) {

        while ($row = mysqli_fetch_assoc($q)) {

            $rows[] = $row;

        }

    }

    return $rows;

}



function cs_save_consultation($conn, $patientId, $doctorUserId, $diagnosis, $treatment, $notes, $facilityId = 0) {

    if (!cs_table_exists($conn, 'consultations')) {

        return false;

    }



    $duid = (int)$doctorUserId;

    $fid = (int)$facilityId;

    if ($fid > 0) {

        $loc = cs_facility_location($conn, $fid);

        if ($loc) {

            $duid = cs_validate_doctor_user($conn, $loc['province'], $loc['city'], $loc['facility'], $duid);

        }

    } elseif ($duid > 0) {

        $check = mysqli_query($conn, "

            SELECT user_id FROM users

            WHERE user_id = $duid AND account_type = 'staff' AND role = 'doctor'

            LIMIT 1

        ");

        $duid = ($check && mysqli_num_rows($check) > 0) ? $duid : 0;

    }



    if ($duid <= 0) {

        return false;

    }



    $dx = cs_esc($conn, $diagnosis);

    $tx = cs_esc($conn, $treatment);

    $nt = cs_esc($conn, $notes);



    return mysqli_query($conn, "

        INSERT INTO consultations (appointment_id, doctor_id, diagnosis, treatment, notes)

        VALUES (NULL, $duid, '$dx', '$tx', '$nt')

    ");

}



function cs_hourly_patient_counts($conn, $facilityId) {

    $labels = ['08:00','09:00','10:00','11:00','12:00','13:00','14:00','15:00'];

    $counts = array_fill(0, count($labels), 0);

    $map = [];

    $today = date('Y-m-d');



    if (cs_table_exists($conn, 'users') && cs_column_exists($conn, 'users', 'created_at')) {

        $q = mysqli_query($conn, "

            SELECT HOUR(u.created_at) h, COUNT(*) c

            FROM users u

            WHERE u.account_type = 'patient'

              AND DATE(u.created_at) = '$today'

              AND " . cs_patient_facility_filter_sql($conn, (int)$facilityId, 'u') . "

            GROUP BY HOUR(u.created_at)

        ");

        if ($q) {

            while ($r = mysqli_fetch_assoc($q)) {

                $map[(int)$r['h']] = (int)$r['c'];

            }

        }

    }



    for ($i = 0; $i < count($labels); $i++) {

        $h = 8 + $i;

        $counts[$i] = $map[$h] ?? 0;

    }



    return [$labels, $counts];

}



function cs_role_label($role) {

    $map = [

        'reception' => 'Reception',

        'nurse' => 'Nurse',

        'doctor' => 'Doctor',

        'pharmacist' => 'Pharmacist',

        'admin' => 'Admin',

    ];

    $key = strtolower(trim((string)$role));

    return $map[$key] ?? ucfirst($key);

}



function cs_badge_class($status) {

    $s = strtolower(str_replace(' ', '_', trim((string)$status)));

    if ($s === 'waiting') return 'waiting';

    if ($s === 'registered') return 'registered';

    if ($s === 'with_nurse') return 'nurse';

    if ($s === 'waiting_doctor') return 'doctorwait';

    if ($s === 'with_doctor' || $s === 'consulting') return 'doctor';

    if ($s === 'waiting_pharmacy') return 'pharmacy';

    if ($s === 'completed') return 'completed';

    if ($s === 'cancelled') return 'cancelled';

    if ($s === 'referred') return 'referred';

    return 'other';

}



function cs_referrals_supported($conn) {

    return cs_table_exists($conn, 'referrals')

        && cs_column_exists($conn, 'referrals', 'patient_id')

        && cs_column_exists($conn, 'referrals', 'doctor_id')

        && cs_column_exists($conn, 'referrals', 'referred_to_facility_id');

}



function cs_fetch_referral_destinations($conn, $excludeFacilityId) {

    if (!cs_table_exists($conn, 'facilities')) {

        return [];

    }



    $fid = (int)$excludeFacilityId;

    $extra = $fid > 0 ? " WHERE facility_id != $fid " : '';



    $rows = [];

    $q = mysqli_query($conn, "

        SELECT facility_id, province, city, facility_name

        FROM facilities

        $extra

        ORDER BY province, city, facility_name

    ");

    if ($q) {

        while ($row = mysqli_fetch_assoc($q)) {

            $rows[] = $row;

        }

    }

    return $rows;

}



function cs_add_referral($conn, $facilityId, $patientId, $doctorId, $toFacilityId, $details, $consultationId = 0) {

    if (!cs_referrals_supported($conn)) {

        return false;

    }



    $fid = (int)$facilityId;

    $pid = cs_validate_patient_user($conn, $fid, (int)$patientId);

    $loc = cs_facility_location($conn, $fid);

    if (!$loc) {

        return false;

    }



    $duid = cs_validate_doctor_user($conn, $loc['province'], $loc['city'], $loc['facility'], (int)$doctorId);

    $toFid = (int)$toFacilityId;

    $details = trim((string)$details);



    if ($pid <= 0 || $duid <= 0 || $toFid <= 0 || $toFid === $fid || $details === '') {

        return false;

    }



    $destCheck = mysqli_query($conn, "SELECT facility_id FROM facilities WHERE facility_id = $toFid LIMIT 1");

    if (!$destCheck || mysqli_num_rows($destCheck) === 0) {

        return false;

    }



    $cid = (int)$consultationId;

    $det = cs_esc($conn, $details);



    if ($cid <= 0 && cs_table_exists($conn, 'consultations')) {

        $consultNotes = cs_esc($conn, 'Referral initiated: ' . $details);

        $consultOk = mysqli_query($conn, "

            INSERT INTO consultations (appointment_id, doctor_id, diagnosis, treatment, notes)

            VALUES (NULL, $duid, 'Pending — referral', 'Referral', '$consultNotes')

        ");

        if ($consultOk) {

            $cid = (int)mysqli_insert_id($conn);

        }

    }



    if ($cid <= 0) {

        return false;

    }



    $ok = mysqli_query($conn, "

        INSERT INTO referrals (

            patient_id, doctor_id, consultation_id, consultation_details,

            referring_facility_id, referred_to_facility_id, referral_status

        ) VALUES (

            $pid, $duid, $cid, '$det', $fid, $toFid, 'Pending'

        )

    ");



    if ($ok) {

        cs_update_patient($conn, $fid, $pid, [

            'status' => 'Referred',

            'department' => 'Referral',

            'notes' => 'Referred: ' . $details,

        ]);

    }



    return $ok;

}



function cs_fetch_patient_referrals($conn, $facilityId, $patientId) {

    if (!cs_referrals_supported($conn)) {

        return [];

    }



    $pid = cs_validate_patient_user($conn, (int)$facilityId, (int)$patientId);

    if ($pid <= 0) {

        return [];

    }



    $sql = "

        SELECT

            r.referral_id,

            r.patient_id,

            r.doctor_id,

            r.consultation_id,

            r.consultation_details,

            r.referring_facility_id,

            r.referred_to_facility_id,

            r.referral_status,

            r.created_at,

            " . cs_staff_name_sql('d') . " AS doctor_name,

            rf.province AS from_province,

            rf.city AS from_city,

            rf.facility_name AS from_facility,

            tf.province AS to_province,

            tf.city AS to_city,

            tf.facility_name AS to_facility

        FROM referrals r

        LEFT JOIN users d ON d.user_id = r.doctor_id

        LEFT JOIN facilities rf ON rf.facility_id = r.referring_facility_id

        LEFT JOIN facilities tf ON tf.facility_id = r.referred_to_facility_id

        WHERE r.patient_id = $pid

        ORDER BY r.created_at DESC

    ";



    $rows = [];

    $q = mysqli_query($conn, $sql);

    if ($q) {

        while ($row = mysqli_fetch_assoc($q)) {

            $rows[] = $row;

        }

    }

    return $rows;

}



function cs_fetch_facility_referrals($conn, $facilityId, array $opts = []) {

    if (!cs_referrals_supported($conn)) {

        return [];

    }



    $fid = (int)$facilityId;

    if ($fid <= 0) {

        return [];

    }



    $direction = strtolower(trim($opts['direction'] ?? 'all'));

    $extra = '';

    if ($direction === 'outgoing') {

        $extra = " AND r.referring_facility_id = $fid ";

    } elseif ($direction === 'incoming') {

        $extra = " AND r.referred_to_facility_id = $fid ";

    } else {

        $extra = " AND (r.referring_facility_id = $fid OR r.referred_to_facility_id = $fid) ";

    }



    $sql = "

        SELECT

            r.referral_id,

            r.patient_id,

            r.doctor_id,

            r.consultation_details,

            r.referring_facility_id,

            r.referred_to_facility_id,

            r.referral_status,

            r.created_at,

            pu.full_name AS patient_name,

            pu.id_number AS patient_id_number,

            " . cs_staff_name_sql('d') . " AS doctor_name,

            rf.facility_name AS from_facility,

            tf.facility_name AS to_facility

        FROM referrals r

        LEFT JOIN users pu ON pu.user_id = r.patient_id AND pu.account_type = 'patient'

        LEFT JOIN users d ON d.user_id = r.doctor_id

        LEFT JOIN facilities rf ON rf.facility_id = r.referring_facility_id

        LEFT JOIN facilities tf ON tf.facility_id = r.referred_to_facility_id

        WHERE 1=1 $extra

        ORDER BY r.created_at DESC

    ";



    $rows = [];

    $q = mysqli_query($conn, $sql);

    if ($q) {

        while ($row = mysqli_fetch_assoc($q)) {

            $rows[] = $row;

        }

    }

    return $rows;

}

