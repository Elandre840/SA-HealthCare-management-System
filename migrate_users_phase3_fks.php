<?php

/**

 * Phase 3 schema prep: drop legacy FK constraints so appointments/consultations

 * can use patient_user_id / doctor_user_id without shadow tables.

 *

 * Run once after phase 1+2 backfill:

 *   c:\xampp\php\php.exe migrate_users_phase3_fks.php

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



function drop_fk_if_exists($conn, $table, $constraint) {

    $table = mysqli_real_escape_string($conn, $table);

    $constraint = mysqli_real_escape_string($conn, $constraint);

    $q = mysqli_query($conn, "

        SELECT CONSTRAINT_NAME

        FROM information_schema.TABLE_CONSTRAINTS

        WHERE TABLE_SCHEMA = DATABASE()

          AND TABLE_NAME = '$table'

          AND CONSTRAINT_NAME = '$constraint'

          AND CONSTRAINT_TYPE = 'FOREIGN KEY'

        LIMIT 1

    ");

    if ($q && mysqli_num_rows($q) > 0) {

        run_sql($conn, "ALTER TABLE `$table` DROP FOREIGN KEY `$constraint`", "drop FK $table.$constraint");

    } else {

        echo "SKIP: FK $table.$constraint not found\n";

    }

}



echo "Phase 3 FK cleanup for users-only model...\n\n";



drop_fk_if_exists($conn, 'appointments', 'appointments_ibfk_1');

drop_fk_if_exists($conn, 'appointments', 'appointments_ibfk_2');

drop_fk_if_exists($conn, 'consultations', 'consultations_ibfk_2');



run_sql($conn, "ALTER TABLE appointments MODIFY patient_id INT NULL", 'appointments.patient_id nullable');

run_sql($conn, "ALTER TABLE appointments MODIFY doctor_id INT NULL", 'appointments.doctor_id nullable');



echo "\nDone. App can now write appointments using patient_user_id / doctor_user_id only.\n";

