<?php
/**
 * Emergency triage columns on announcements table.
 * Run: c:\xampp\php\php.exe migrate_emergency_triage.php
 */
include 'db.php';
include_once 'clinic_schema.php';

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

echo "Starting emergency triage migration...\n";

if (!cs_table_exists($conn, 'announcements')) {
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
}

$cols = [
    "ADD COLUMN priority VARCHAR(20) NOT NULL DEFAULT 'normal'",
    "ADD COLUMN patient_id INT NULL",
    "ADD COLUMN source_role VARCHAR(50) NULL",
    "ADD COLUMN emergency_type VARCHAR(100) NULL",
    "ADD COLUMN alert_status VARCHAR(30) NULL",
    "ADD COLUMN alerted_staff_id INT NULL",
    "ADD COLUMN medi_alert_sent_at TIMESTAMP NULL",
];

foreach ($cols as $colSql) {
    $colName = '';
    if (preg_match('/ADD COLUMN (\w+)/', $colSql, $m)) {
        $colName = $m[1];
    }
    if ($colName !== '' && cs_column_exists($conn, 'announcements', $colName)) {
        echo "SKIP: announcements.$colName already exists\n";
        continue;
    }
    run_sql($conn, "ALTER TABLE announcements $colSql", "announcements $colSql");
}

echo "\nDone. Emergency triage columns ready on announcements.\n";
