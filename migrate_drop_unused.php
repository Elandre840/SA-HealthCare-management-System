<?php
/**
 * Drop tables not used by the current clinic system dashboards.
 * Safe to run multiple times (uses DROP TABLE IF EXISTS).
 *
 * Keeps: users, facilities, appointments, consultations, announcements, referrals
 *
 * Removes: incidents, medications, prescriptions
 *
 * Run: c:\xampp\php\php.exe migrate_drop_unused.php
 */
include 'db.php';

function run_drop($conn, $table, $label) {
    $table = mysqli_real_escape_string($conn, $table);
    $check = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if (!$check || mysqli_num_rows($check) === 0) {
        echo "SKIP: $label (table does not exist)\n";
        return true;
    }

    $countQ = mysqli_query($conn, "SELECT COUNT(*) AS c FROM `$table`");
    $count = 0;
    if ($countQ && ($row = mysqli_fetch_assoc($countQ))) {
        $count = (int)$row['c'];
    }
    if ($count > 0) {
        echo "WARN: $label has $count row(s) — dropping anyway (unused by app)\n";
    }

    if (mysqli_query($conn, "DROP TABLE IF EXISTS `$table`")) {
        echo "OK: dropped $label\n";
        return true;
    }

    echo "FAIL: $label — " . mysqli_error($conn) . "\n";
    return false;
}

echo "Dropping unused tables in " . $conn->query('SELECT DATABASE()')->fetch_row()[0] . "...\n\n";

$unused = [
    'incidents' => 'incidents',
    'medications' => 'medications',
    'prescriptions' => 'prescriptions',
];

foreach ($unused as $table => $label) {
    run_drop($conn, $table, $label);
}

echo "\nDone. Active tables should include:\n";
echo "  users, facilities, appointments, consultations, announcements, referrals\n";
