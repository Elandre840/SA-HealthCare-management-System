
save_booking.php
    
    <!-- Select Facility -->
    <select name="facility_id" id="facility">
        <option>Select Facility</option>
    </select>

    <!-- Select Doctor -->
    <select name="doctor_id" id="doctor">
        <option>Select Doctor</option>
    </select>

    <!-- Date -->
    <input type="date" name="appointment_date" required>

    <!-- Time Slots -->
    <select name="appointment_time">
        <option>08:00</option>
        <option>09:00</option>
        <option>10:00</option>
    </select>

    <!-- Reason -->
    <textarea name="reason" placeholder="Reason for visit"></textarea>

    <button type="submit">Book Appointment</button>
</form>
