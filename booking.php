
<?php
include 'db.php';
session_start();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Book Appointment</title>

    <!-- jQuery (REQUIRED for AJAX) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>

<h2>Book Appointment</h2>

<form action="save_booking.php" method="POST">

    <!-- Facility Dropdown -->
    <label>Select Facility:</label>
    <select name="facility_id" id="facility" required>
        <option value="">Select Facility</option>
        <?php
        $facilities = mysqli_query($conn, "SELECT * FROM facilities");
        while($row = mysqli_fetch_assoc($facilities)){
            echo "<option value='".$row['id']."'>".$row['facility_name']."</option>";
        }
        ?>
    </select>

    <br><br>

    <!-- Doctor Dropdown (Dynamic 🔥) -->
    <label>Select Doctor:</label>
    <select name="doctor_id" id="doctor" required>
        <option value="">Select Doctor</option>
    </select>

    <br><br>

    <!-- Date -->
    <label>Date:</label>
    <input type="date" name="appointment_date" required>

    <br><br>

    <!-- Time -->
    <label>Time:</label>
    <select name="appointment_time">
        <option>08:00</option>
        <option>09:00</option>
        <option>10:00</option>
    </select>

    <br><br>

    <button type="submit">Book Appointment</button>

</form>

<!-- ✅ YOUR AJAX CODE GOES HERE -->
<script>
$('#facility').change(function(){
    var facility_id = $(this).val();

    $.ajax({
        url: 'fetch_doctors.php',
        method: 'POST',
        data: {facility_id: facility_id},
        success: function(data){
            $('#doctor').html(data);
        }
    });
});
</script>

</body>
</html>
