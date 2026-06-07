<?php

/**

 * Phase 4: finalize users-only model.

 * - Rename *_user_id columns to patient_id / doctor_id (FK -> users)

 * - Drop shadow tables patients, doctors

 *

 * Run once after phase 3 PHP refactor:

 *   c:\xampp\php\php.exe migrate_users_phase4.php

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



function column_exists($conn, $table, $col) {

    $table = mysqli_real_escape_string($conn, $table);

    $col   = mysqli_real_escape_string($conn, $col);

    $q = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$col'");

    return $q && mysqli_num_rows($q) > 0;

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

    }

}



function drop_fks_to_table($conn, $referencedTable) {

    $referencedTable = mysqli_real_escape_string($conn, $referencedTable);

    $q = mysqli_query($conn, "

        SELECT TABLE_NAME, CONSTRAINT_NAME

        FROM information_schema.KEY_COLUMN_USAGE

        WHERE TABLE_SCHEMA = DATABASE()

          AND REFERENCED_TABLE_NAME = '$referencedTable'

          AND CONSTRAINT_NAME IS NOT NULL

    ");

    if ($q) {

        while ($ref = mysqli_fetch_assoc($q)) {

            drop_fk_if_exists($conn, $ref['TABLE_NAME'], $ref['CONSTRAINT_NAME']);

        }

    }

}



echo "Phase 4: finalize users-only schema...\n\n";



if (column_exists($conn, 'appointments', 'patient_user_id')) {

    run_sql($conn, "

        UPDATE appointments

        SET patient_user_id = patient_id

        WHERE patient_user_id IS NULL AND patient_id IS NOT NULL

    ", 'backfill appointments.patient_user_id');



    run_sql($conn, "

        UPDATE appointments

        SET doctor_user_id = doctor_id

        WHERE doctor_user_id IS NULL AND doctor_id IS NOT NULL

    ", 'backfill appointments.doctor_user_id');



    if (column_exists($conn, 'appointments', 'patient_id')

        && !column_exists($conn, 'appointments', 'legacy_patient_id')) {

        run_sql($conn, "

            ALTER TABLE appointments

            CHANGE patient_id legacy_patient_id INT NULL

        ", 'rename legacy appointments.patient_id');

    }



    if (column_exists($conn, 'appointments', 'doctor_id')

        && !column_exists($conn, 'appointments', 'legacy_doctor_id')) {

        run_sql($conn, "

            ALTER TABLE appointments

            CHANGE doctor_id legacy_doctor_id INT NULL

        ", 'rename legacy appointments.doctor_id');

    }



    if (column_exists($conn, 'appointments', 'legacy_patient_id')) {

        run_sql($conn, "ALTER TABLE appointments DROP COLUMN legacy_patient_id", 'drop legacy appointments.patient_id');

    }



    if (column_exists($conn, 'appointments', 'legacy_doctor_id')) {

        run_sql($conn, "ALTER TABLE appointments DROP COLUMN legacy_doctor_id", 'drop legacy appointments.doctor_id');

    }



    if (column_exists($conn, 'appointments', 'patient_user_id')) {

        run_sql($conn, "

            ALTER TABLE appointments

            CHANGE patient_user_id patient_id INT NOT NULL

        ", 'rename appointments.patient_user_id -> patient_id');

    }



    if (column_exists($conn, 'appointments', 'doctor_user_id')) {

        run_sql($conn, "

            ALTER TABLE appointments

            CHANGE doctor_user_id doctor_id INT NOT NULL

        ", 'rename appointments.doctor_user_id -> doctor_id');

    }

}



if (column_exists($conn, 'consultations', 'doctor_user_id')) {

    run_sql($conn, "

        UPDATE consultations

        SET doctor_user_id = doctor_id

        WHERE doctor_user_id IS NULL AND doctor_id IS NOT NULL

    ", 'backfill consultations.doctor_user_id');



    if (column_exists($conn, 'consultations', 'doctor_id')

        && column_exists($conn, 'consultations', 'doctor_user_id')) {

        run_sql($conn, "

            ALTER TABLE consultations

            CHANGE doctor_id legacy_doctor_id INT NULL

        ", 'rename legacy consultations.doctor_id');

        run_sql($conn, "ALTER TABLE consultations DROP COLUMN legacy_doctor_id", 'drop legacy consultations.doctor_id');

    }



    if (column_exists($conn, 'consultations', 'doctor_user_id')) {

        run_sql($conn, "

            ALTER TABLE consultations

            CHANGE doctor_user_id doctor_id INT NOT NULL

        ", 'rename consultations.doctor_user_id -> doctor_id');

    }

}



drop_fk_if_exists($conn, 'appointments', 'fk_appt_patient');

drop_fk_if_exists($conn, 'appointments', 'fk_appt_doctor');

drop_fk_if_exists($conn, 'consultations', 'fk_consult_doctor');



if (column_exists($conn, 'appointments', 'patient_id')) {

    run_sql($conn, "

        ALTER TABLE appointments

        ADD CONSTRAINT fk_appt_patient FOREIGN KEY (patient_id) REFERENCES users(user_id)

    ", 'FK appointments.patient_id -> users');

}



if (column_exists($conn, 'appointments', 'doctor_id')) {

    run_sql($conn, "

        ALTER TABLE appointments

        ADD CONSTRAINT fk_appt_doctor FOREIGN KEY (doctor_id) REFERENCES users(user_id)

    ", 'FK appointments.doctor_id -> users');

}



if (column_exists($conn, 'consultations', 'doctor_id')) {

    run_sql($conn, "

        ALTER TABLE consultations

        ADD CONSTRAINT fk_consult_doctor FOREIGN KEY (doctor_id) REFERENCES users(user_id)

    ", 'FK consultations.doctor_id -> users');

}



drop_fks_to_table($conn, 'patients');

drop_fks_to_table($conn, 'doctors');



run_sql($conn, 'DROP TABLE IF EXISTS patients', 'drop patients table');

run_sql($conn, 'DROP TABLE IF EXISTS doctors', 'drop doctors table');



echo "\nDone. Active core tables: users, facilities, appointments, consultations, announcements\n";

