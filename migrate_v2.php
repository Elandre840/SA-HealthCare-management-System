<?php
/**
 * One-time migration for clinic_system_demo_v2.
 * Run: c:\xampp\php\php.exe migrate_v2.php
 */
include 'db.php';

function run_sql($conn, $sql, $label) {
    try {
        if (mysqli_query($conn, $sql)) {
            echo "OK: $label\n";
            return true;
        }
    } catch (mysqli_sql_exception $e) {
        echo "SKIP ($label): " . $e->getMessage() . "\n";
        return false;
    }
    echo "SKIP/FAIL ($label): " . mysqli_error($conn) . "\n";
    return false;
}

echo "Starting migration...\n";

run_sql($conn, "
CREATE TABLE IF NOT EXISTS announcements (
    announcement_id INT AUTO_INCREMENT PRIMARY KEY,
    message TEXT NOT NULL,
    created_by INT NOT NULL,
    role_target VARCHAR(50) NOT NULL DEFAULT 'All',
    province VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    facility VARCHAR(150) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
", 'announcements table');

$patientCols = [
    "ADD COLUMN department VARCHAR(50) DEFAULT 'Reception'",
    "ADD COLUMN bp VARCHAR(20) NULL",
    "ADD COLUMN temp VARCHAR(20) NULL",
    "ADD COLUMN pulse VARCHAR(20) NULL",
    "ADD COLUMN weight VARCHAR(20) NULL",
    "ADD COLUMN notes TEXT NULL",
    "ADD COLUMN diagnosis TEXT NULL",
    "ADD COLUMN prescription TEXT NULL",
    "ADD COLUMN medication TEXT NULL",
];

foreach ($patientCols as $colSql) {
    run_sql($conn, "ALTER TABLE patients $colSql", "patients $colSql");
}

run_sql($conn, "
    ALTER TABLE patients
    MODIFY status VARCHAR(50) NOT NULL DEFAULT 'Waiting'
", 'patients status varchar');

run_sql($conn, "
    INSERT INTO facilities (province, city, facility_name)
    SELECT 'Eastern Cape', 'Qonce', 'Clinic'
    FROM DUAL
    WHERE NOT EXISTS (
        SELECT 1 FROM facilities
        WHERE LOWER(province) = 'eastern cape'
          AND LOWER(city) = 'qonce'
          AND LOWER(facility_name) = 'clinic'
    )
", 'seed Eastern Cape / Qonce / Clinic facility');

run_sql($conn, "
    UPDATE users SET
        province = 'Eastern Cape',
        city = 'Qonce',
        facility = 'Clinic',
        role = 'reception'
    WHERE email = 'reception@clinic.com'
", 'seed reception user location');

run_sql($conn, "
    UPDATE users SET
        province = 'Eastern Cape',
        city = 'Qonce',
        facility = 'Clinic',
        role = 'nurse'
    WHERE email = 'nurse@clinic.com'
", 'seed nurse user');

run_sql($conn, "
    UPDATE users SET
        province = 'Eastern Cape',
        city = 'Qonce',
        facility = 'Clinic',
        role = 'doctor'
    WHERE email = 'doctor@clinic.com'
", 'seed doctor user');

run_sql($conn, "
    UPDATE users SET
        province = 'Eastern Cape',
        city = 'Qonce',
        facility = 'Clinic',
        role = 'pharmacist'
    WHERE email = 'pharma@clinic.com'
", 'seed pharmacist user');

run_sql($conn, "
    UPDATE patients p
    INNER JOIN facilities f ON LOWER(f.province) = 'eastern cape'
        AND LOWER(f.city) = 'qonce'
        AND LOWER(f.facility_name) = 'clinic'
    SET p.facility_id = f.facility_id
    WHERE p.facility_id IS NULL OR p.facility_id = 0
", 'link patients to default facility');

$userCols = [
    "ADD COLUMN status VARCHAR(50) DEFAULT 'Waiting'",
    "ADD COLUMN department VARCHAR(50) DEFAULT 'Reception'",
    "ADD COLUMN bp VARCHAR(20) NULL",
    "ADD COLUMN temp VARCHAR(20) NULL",
    "ADD COLUMN pulse VARCHAR(20) NULL",
    "ADD COLUMN weight VARCHAR(20) NULL",
    "ADD COLUMN notes TEXT NULL",
    "ADD COLUMN diagnosis TEXT NULL",
    "ADD COLUMN prescription TEXT NULL",
    "ADD COLUMN medication TEXT NULL",
];
foreach ($userCols as $colSql) {
    run_sql($conn, "ALTER TABLE users $colSql", "users $colSql");
}

run_sql($conn, "
    UPDATE users
    SET status = 'Waiting', department = 'Reception'
    WHERE account_type = 'patient' AND (status IS NULL OR status = '')
", 'default status for patient users');

echo "Migration complete.\n";
