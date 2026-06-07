
<?php
include 'db.php';
include 'helpers.php';
require_login();
?>

<h2>Add Patient</h2>

<form action="save_patient.php" method="POST">

    <input type="text" name="full_name" placeholder="Full Name" required><br><br>

    <input type="text" name="id_number" placeholder="ID Number" required><br><br>

    <input type="text" name="phone" placeholder="Phone Number"><br><br>

    <select name="department">
        <option>Reception</option>
        <option>Nurse</option>
        <option>Doctor</option>
        <option>Pharmacy</option>
    </select><br><br>

    <button type="submit">Save Patient</button>

</form>
