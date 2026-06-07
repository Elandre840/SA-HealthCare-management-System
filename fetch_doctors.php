
<?php
include 'db.php';
include 'clinic_schema.php';

header('Content-Type: text/html; charset=UTF-8');

$facilityId = (int)($_POST['facility_id'] ?? $_GET['facility_id'] ?? 0);
$province = $_POST['province'] ?? $_GET['province'] ?? '';
$city = $_POST['city'] ?? $_GET['city'] ?? '';
$facility = $_POST['facility'] ?? $_GET['facility'] ?? '';

if ($facilityId <= 0 && $province !== '' && $city !== '' && $facility !== '') {
    $facilityId = cs_facility_id($conn, $province, $city, $facility);
}

echo '<option value="">Select Doctor</option>';

if ($province !== '' && $city !== '' && $facility !== '') {
    $doctors = cs_fetch_doctors($conn, $province, $city, $facility);
} elseif ($facilityId > 0) {
    $q = mysqli_query($conn, "SELECT province, city, facility_name FROM facilities WHERE facility_id = $facilityId LIMIT 1");
    if ($q && ($f = mysqli_fetch_assoc($q))) {
        $doctors = cs_fetch_doctors($conn, $f['province'], $f['city'], $f['facility_name']);
    } else {
        $doctors = [];
    }
} else {
    $doctors = [];
}

foreach ($doctors as $d) {
    echo '<option value="' . (int)$d['id'] . '">' . htmlspecialchars($d['full_name']) . '</option>';
}
