
<?php
include "db.php";

$success = "";
$error = "";

/* ✅ helper: check if a column exists */
function column_exists($conn, $table, $column){
    $table  = mysqli_real_escape_string($conn, $table);
    $column = mysqli_real_escape_string($conn, $column);

    $q = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $q && mysqli_num_rows($q) > 0;
}

if($_SERVER['REQUEST_METHOD'] == "POST"){

    $type      = trim($_POST['type'] ?? '');
    $name      = trim($_POST['name'] ?? '');
    $surname   = trim($_POST['surname'] ?? '');
    $id        = trim($_POST['id'] ?? '');
    $emp       = trim($_POST['emp'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $role      = trim($_POST['role'] ?? '');
    $province  = trim($_POST['province'] ?? '');
    $city      = trim($_POST['city'] ?? '');
    $facility  = trim($_POST['facility'] ?? '');
    $passwordRaw = $_POST['password'] ?? '';

    if($name == "" || $surname == "" || $email == "" || $province == "" || $city == "" || $facility == "" || $passwordRaw == ""){
        $error = "❌ Please complete all required fields.";
    } else {

        $name      = mysqli_real_escape_string($conn, $name);
        $surname   = mysqli_real_escape_string($conn, $surname);
        $id        = mysqli_real_escape_string($conn, $id);
        $emp       = mysqli_real_escape_string($conn, $emp);
        $phone     = mysqli_real_escape_string($conn, $phone);
        $email     = mysqli_real_escape_string($conn, $email);
        $role      = mysqli_real_escape_string($conn, strtolower(trim($_POST['role'] ?? '')));
        $province  = mysqli_real_escape_string($conn, $province);
        $city      = mysqli_real_escape_string($conn, $city);
        $facility  = mysqli_real_escape_string($conn, $facility);
        $type      = mysqli_real_escape_string($conn, $type);
        $accountType = (strcasecmp($type, 'Patient') === 0) ? 'patient' : 'staff';

        if ($accountType === 'staff' && $role === '') {
            $error = "❌ Please select a staff role.";
        } else {

        $password = password_hash($passwordRaw, PASSWORD_DEFAULT);

        $check = mysqli_query($conn, "SELECT user_id FROM users WHERE email='$email' LIMIT 1");

        if($check && mysqli_num_rows($check) > 0){
            $error = "❌ This email is already registered.";
        } else {

            /* ✅ Build insert dynamically based on actual table columns */
            $columns = [];
            $values  = [];

            if(column_exists($conn, 'users', 'account_type')){
                $columns[] = "account_type";
                $values[]  = "'$accountType'";
            }

            if(column_exists($conn, 'users', 'first_name')){
                $columns[] = "first_name";
                $values[]  = "'$name'";
            }

            if(column_exists($conn, 'users', 'type')){
                $columns[] = "type";
                $values[]  = "'$type'";
            }

            if(column_exists($conn, 'users', 'name')){
                $columns[] = "name";
                $values[]  = "'$name'";
            }

            if(column_exists($conn, 'users', 'surname')){
                $columns[] = "surname";
                $values[]  = "'$surname'";
            }

            if(column_exists($conn, 'users', 'full_name')){
                $columns[] = "full_name";
                $values[]  = "'" . mysqli_real_escape_string($conn, trim("$name $surname")) . "'";
            }

            if(column_exists($conn, 'users', 'id_number')){
                $columns[] = "id_number";
                $values[]  = "'$id'";
            }

            if(column_exists($conn, 'users', 'employee_number')){
                $columns[] = "employee_number";
                $values[]  = "'$emp'";
            }

            if(column_exists($conn, 'users', 'phone')){
                $columns[] = "phone";
                $values[]  = "'$phone'";
            }

            if(column_exists($conn, 'users', 'email')){
                $columns[] = "email";
                $values[]  = "'$email'";
            }

            if(column_exists($conn, 'users', 'role') && $accountType === 'staff' && $role !== ''){
                $columns[] = "role";
                $values[]  = "'$role'";
            }

            if(column_exists($conn, 'users', 'province')){
                $columns[] = "province";
                $values[]  = "'$province'";
            }

            if(column_exists($conn, 'users', 'city')){
                $columns[] = "city";
                $values[]  = "'$city'";
            }

            if(column_exists($conn, 'users', 'facility')){
                $columns[] = "facility";
                $values[]  = "'$facility'";
            }

            if(column_exists($conn, 'users', 'password')){
                $columns[] = "password";
                $values[]  = "'$password'";
            }

            if(empty($columns)){
                $error = "❌ Could not detect usable columns in users table.";
            } else {
                $sql = "INSERT INTO users (" . implode(", ", $columns) . ")
                        VALUES (" . implode(", ", $values) . ")";

                if(mysqli_query($conn, $sql)){
                    $success = "✅ Registration successful.";
                } else {
                    $error = "❌ Error: " . mysqli_error($conn);
                }
            }
        }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SA Healthcare Management System — Register</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
*, *::before, *::after { box-sizing: border-box; }

body {
    margin: 0;
    min-height: 100vh;
    font-family: 'Inter', 'Segoe UI', sans-serif;
    background: #eef2f8 url('assets/backgrounds/sa_flag.jpg') no-repeat center center fixed;
    background-size: cover;
    overflow-x: hidden;
}

.bg-overlay {
    position: fixed;
    inset: 0;
    background:
        radial-gradient(ellipse 90% 75% at 50% 45%, rgba(255, 255, 255, 0.18) 0%, rgba(255, 255, 255, 0.08) 50%, rgba(12, 35, 80, 0.12) 100%),
        linear-gradient(160deg, rgba(255, 255, 255, 0.12) 0%, rgba(255, 255, 255, 0.04) 50%, rgba(255, 255, 255, 0.1) 100%);
    pointer-events: none;
    z-index: 0;
}

.bg-decor {
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 0;
    overflow: hidden;
    opacity: 0.85;
}

.bg-decor svg { position: absolute; opacity: 0.08; }
.bg-decor .cross-1 { top: 8%; right: 12%; width: 80px; opacity: 0.1; }
.bg-decor .cross-2 { bottom: 18%; left: 6%; width: 120px; opacity: 0.08; }
.bg-decor .ekg { bottom: 22%; left: 14%; width: 200px; opacity: 0.1; }

.page {
    position: relative;
    z-index: 2;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
}

.register-card {
    width: 100%;
    max-width: 560px;
    padding: 32px 36px 28px;
    border-radius: 22px;
    background: rgba(255, 255, 255, 0.28);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1.5px solid rgba(255, 255, 255, 0.55);
    box-shadow:
        0 12px 40px rgba(15, 35, 70, 0.1),
        inset 0 1px 0 rgba(255, 255, 255, 0.45);
}

.logo-wrap {
    text-align: center;
    margin-bottom: 12px;
}

.logo-wrap svg { width: 64px; height: 64px; }

.register-card h1 {
    margin: 8px 0 4px;
    font-size: 20px;
    font-weight: 700;
    color: #0a2a6e;
    text-align: center;
    letter-spacing: -0.3px;
}

.tagline {
    margin: 0 0 20px;
    font-size: 12px;
    color: #3d5a8a;
    text-align: center;
}

.form-title {
    margin: 0 0 4px;
    font-size: 15px;
    font-weight: 600;
    color: #1a4080;
    text-align: center;
}

.message {
    margin-bottom: 14px;
    padding: 10px 14px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
}

.message.success {
    background: rgba(220, 252, 231, 0.92);
    border: 1px solid #86efac;
    color: #166534;
}

.message.error {
    background: rgba(255, 230, 230, 0.9);
    border: 1px solid #e53e3e;
    color: #c53030;
}

.section-title {
    margin: 18px 0 8px;
    font-size: 11px;
    font-weight: 700;
    color: #3d5a8a;
    text-transform: uppercase;
    letter-spacing: 0.6px;
}

.section-title:first-of-type { margin-top: 0; }

.field {
    position: relative;
    margin-bottom: 10px;
}

.field-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    width: 18px;
    height: 18px;
    color: #8a9bb5;
    pointer-events: none;
    display: flex;
    align-items: center;
    justify-content: center;
}

.field-icon svg {
    width: 16px;
    height: 16px;
    fill: currentColor;
}

.field input,
.field select {
    width: 100%;
    padding: 12px 40px 12px 42px;
    border: 1.5px solid rgba(180, 190, 210, 0.7);
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.88);
    font-family: inherit;
    font-size: 13.5px;
    color: #1a2b4a;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
    appearance: none;
    -webkit-appearance: none;
}

.field input::placeholder { color: #9aa8be; }

.field input:focus,
.field select:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.field select {
    cursor: pointer;
    color: #6b7c96;
}

.field select.has-value { color: #1a2b4a; }

.field-chevron {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    color: #8a9bb5;
}

.field-chevron svg {
    width: 14px;
    height: 14px;
    fill: currentColor;
}

.toggle-pw {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    padding: 4px;
    cursor: pointer;
    color: #8a9bb5;
    display: flex;
    align-items: center;
    width: auto;
}

.toggle-pw svg {
    width: 18px;
    height: 18px;
    fill: currentColor;
}

.grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.grid-2 .field { margin-bottom: 0; }

.btn-register {
    width: 100%;
    margin-top: 8px;
    padding: 14px;
    background: linear-gradient(135deg, #0a2a6e 0%, #1a4080 100%);
    color: white;
    border: none;
    border-radius: 10px;
    font-family: inherit;
    font-size: 14px;
    font-weight: 700;
    letter-spacing: 1px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: background 0.2s, transform 0.15s;
    box-shadow: 0 4px 14px rgba(10, 42, 110, 0.35);
}

.btn-register:hover {
    background: linear-gradient(135deg, #0d3278 0%, #1e4a90 100%);
    transform: translateY(-1px);
}

.btn-register svg {
    width: 16px;
    height: 16px;
    fill: white;
}

.helper {
    margin-top: 14px;
    text-align: center;
    color: #3d5a8a;
    font-size: 12px;
    line-height: 1.5;
}

.auth-links {
    margin-top: 14px;
    text-align: center;
    font-size: 13px;
    color: #3d5a8a;
}

.auth-links a {
    color: #0a2a6e;
    font-weight: 600;
    text-decoration: none;
}

.auth-links a:hover { text-decoration: underline; }

.secure-footer {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin-top: 16px;
    padding: 8px 12px;
    font-size: 11px;
    font-weight: 600;
    color: #0a2a6e;
    background: rgba(255, 255, 255, 0.75);
    border-radius: 8px;
}

.secure-footer svg {
    width: 15px;
    height: 15px;
    flex-shrink: 0;
}

.hidden { display: none; }

@media (max-width: 640px) {
    .register-card {
        padding: 24px 20px 22px;
        border-radius: 18px;
    }
    .grid-2 { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<div class="bg-overlay"></div>

<div class="bg-decor">
    <svg class="cross-1" viewBox="0 0 40 40"><rect x="17" y="5" width="6" height="30" fill="#c0392b"/><rect x="5" y="17" width="30" height="6" fill="#c0392b"/></svg>
    <svg class="cross-2" viewBox="0 0 40 40"><rect x="17" y="5" width="6" height="30" fill="#2980b9"/><rect x="5" y="17" width="30" height="6" fill="#2980b9"/></svg>
    <svg class="ekg" viewBox="0 0 200 40"><polyline points="0,20 30,20 40,5 55,35 70,20 200,20" fill="none" stroke="#2980b9" stroke-width="2.5"/></svg>
</div>

<div class="page">
<div class="register-card">

<div class="logo-wrap">
    <svg viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg">
        <path d="M40 68 C40 68 10 48 10 28 C10 16 18 8 28 8 C33 8 37 10 40 14 C43 10 47 8 52 8 C62 8 70 16 70 28 C70 48 40 68 40 68Z" fill="none" stroke="#1a5fb4" stroke-width="3"/>
        <circle cx="28" cy="30" r="4" fill="#27ae60"/>
        <circle cx="40" cy="26" r="4" fill="#2980b9"/>
        <circle cx="52" cy="30" r="4" fill="#c0392b"/>
        <line x1="12" y1="22" x2="22" y2="22" stroke="#c0392b" stroke-width="2"/>
        <line x1="22" y1="22" x2="26" y2="16" stroke="#c0392b" stroke-width="2"/>
        <line x1="26" y1="16" x2="32" y2="30" stroke="#c0392b" stroke-width="2"/>
        <line x1="32" y1="30" x2="40" y2="24" stroke="#c0392b" stroke-width="2"/>
        <line x1="40" y1="24" x2="48" y2="32" stroke="#c0392b" stroke-width="2"/>
        <line x1="48" y1="32" x2="56" y2="20" stroke="#c0392b" stroke-width="2"/>
        <line x1="56" y1="20" x2="68" y2="20" stroke="#c0392b" stroke-width="2"/>
    </svg>
</div>

<h1>SA Healthcare Management System</h1>
<p class="tagline">Secure. Connected. For a healthier South Africa.</p>
<p class="form-title" id="formTitle">Create your account</p>

<?php if($success): ?>
<div class="message success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if($error): ?>
<div class="message error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST">

<div class="section-title">Account Type</div>
<div class="field">
    <span class="field-icon"><svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v2h20v-2c0-3.3-6.7-5-10-5z"/></svg></span>
    <select name="type" id="type" required>
        <option value="" disabled selected>Select Type</option>
        <option value="Staff">Staff</option>
        <option value="Patient">Patient</option>
    </select>
    <span class="field-chevron"><svg viewBox="0 0 24 24"><path d="M7 10l5 5 5-5z"/></svg></span>
</div>

<div class="section-title">Personal Information</div>
<div class="grid-2">
    <div class="field">
        <span class="field-icon"><svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v2h20v-2c0-3.3-6.7-5-10-5z"/></svg></span>
        <input name="name" placeholder="First Name" required>
    </div>
    <div class="field">
        <span class="field-icon"><svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v2h20v-2c0-3.3-6.7-5-10-5z"/></svg></span>
        <input name="surname" placeholder="Surname" required>
    </div>
</div>

<div class="grid-2">
    <div class="field">
        <span class="field-icon"><svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg></span>
        <input name="id" placeholder="ID Number">
    </div>
    <div class="field">
        <span class="field-icon"><svg viewBox="0 0 24 24"><path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1-9.4 0-17-7.6-17-17 0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1L6.6 10.8z"/></svg></span>
        <input name="phone" placeholder="Phone Number">
    </div>
</div>

<div class="field">
    <span class="field-icon"><svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg></span>
    <input name="email" type="email" placeholder="Email Address" required>
</div>

<div id="staffFields">
    <div class="section-title">Staff Details</div>
    <div class="grid-2">
        <div class="field">
            <span class="field-icon"><svg viewBox="0 0 24 24"><path d="M20 6h-4V4c0-1.1-.9-2-2-2h-4c-1.1 0-2 .9-2 2v2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zM10 4h4v2h-4V4z"/></svg></span>
            <input name="emp" placeholder="Employee Number">
        </div>
        <div class="field">
            <span class="field-icon"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg></span>
            <select name="role" id="role">
                <option value="" disabled selected>Select Role</option>
                <option value="reception">Reception</option>
                <option value="nurse">Nurse</option>
                <option value="doctor">Doctor</option>
                <option value="pharmacist">Pharmacist</option>
                <option value="admin">Admin</option>
            </select>
            <span class="field-chevron"><svg viewBox="0 0 24 24"><path d="M7 10l5 5 5-5z"/></svg></span>
        </div>
    </div>
</div>

<div class="section-title">Location</div>
<div class="grid-2">
    <div class="field">
        <span class="field-icon"><svg viewBox="0 0 24 24"><path d="M12 2C8.1 2 5 5.1 5 9c0 5.2 7 13 7 13s7-7.8 7-13c0-3.9-3.1-7-7-7zm0 9.5c-1.4 0-2.5-1.1-2.5-2.5S10.6 6.5 12 6.5s2.5 1.1 2.5 2.5S13.4 11.5 12 11.5z"/></svg></span>
        <select id="province" name="province" required>
            <option value="" disabled selected>Select Province</option>
            <option>Eastern Cape</option>
            <option>Free State</option>
            <option>Gauteng</option>
            <option>KwaZulu-Natal</option>
            <option>Limpopo</option>
            <option>Mpumalanga</option>
            <option>Northern Cape</option>
            <option>North West</option>
            <option>Western Cape</option>
        </select>
        <span class="field-chevron"><svg viewBox="0 0 24 24"><path d="M7 10l5 5 5-5z"/></svg></span>
    </div>
    <div class="field">
        <span class="field-icon"><svg viewBox="0 0 24 24"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10z"/></svg></span>
        <select id="city" name="city" required>
            <option value="" disabled selected>Select City / District</option>
        </select>
        <span class="field-chevron"><svg viewBox="0 0 24 24"><path d="M7 10l5 5 5-5z"/></svg></span>
    </div>
</div>

<div class="field">
    <span class="field-icon"><svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg></span>
    <select name="facility" required>
        <option value="" disabled selected>Select Facility</option>
        <option>Clinic</option>
        <option>Hospital</option>
        <option>Pharmacy</option>
        <option>Doctor Practice</option>
    </select>
    <span class="field-chevron"><svg viewBox="0 0 24 24"><path d="M7 10l5 5 5-5z"/></svg></span>
</div>

<div class="section-title">Security</div>
<div class="field">
    <span class="field-icon"><svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.8-2.2-5-5-5S7 3.2 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3-9H9V6c0-1.7 1.3-3 3-3s3 1.3 3 3v2z"/></svg></span>
    <input type="password" name="password" id="password" placeholder="Create Password" required>
    <button type="button" class="toggle-pw" id="togglePw" aria-label="Toggle password visibility">
        <svg viewBox="0 0 24 24" id="eyeIcon"><path d="M12 4.5C7 4.5 2.7 7.6 1 12c1.7 4.4 6 7.5 11 7.5s9.3-3.1 11-7.5C21.3 7.6 17 4.5 12 4.5zm0 12.5c-2.8 0-5-2.2-5-5s2.2-5 5-5 5 2.2 5 5-2.2 5-5 5zm0-8c-1.7 0-3 1.3-3 3s1.3 3 3 3 3-1.3 3-3-1.3-3-3-3z"/></svg>
    </button>
</div>

<button type="submit" class="btn-register">
    <svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm4 2v2h-8v-2h8zm-4-10c-2.2 0-4 1.8-4 4h2c0-1.1.9-2 2-2s2 .9 2 2c0 2-3 1.75-3 5h2c0-2.25 3-2.5 3-5 0-2.2-1.8-4-4-4z"/></svg>
    CREATE ACCOUNT
</button>

</form>

<p class="helper">Province, city, and facility must match your assigned location for staff login.</p>
<p class="auth-links">Already have an account? <a href="login.php">Sign in</a></p>

<div class="secure-footer">
    <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.6 3.8 10.7 9 12 5.2-1.3 9-6.4 9-12V5l-9-4zm-1 14l-4-4 1.4-1.4L11 12.2l5.6-5.6L18 8l-7 7z" fill="#27ae60"/></svg>
    Secure registration for South Africa's health platform
</div>

</div>
</div>

<script>
const typeSelect = document.getElementById("type");
const staffFields = document.getElementById("staffFields");
const formTitle = document.getElementById("formTitle");
const roleSelect = document.getElementById("role");

function toggleTypeFields(){
    if(typeSelect.value === "Patient"){
        staffFields.classList.add("hidden");
        roleSelect.removeAttribute("required");
        formTitle.textContent = "Patient registration";
    } else if(typeSelect.value === "Staff"){
        staffFields.classList.remove("hidden");
        roleSelect.setAttribute("required", "required");
        formTitle.textContent = "Staff registration";
    } else {
        staffFields.classList.remove("hidden");
        roleSelect.removeAttribute("required");
        formTitle.textContent = "Create your account";
    }
}

typeSelect.addEventListener("change", function(){
    toggleTypeFields();
    if(this.value) this.classList.add("has-value");
});
toggleTypeFields();

const cities = {
    "Eastern Cape": ["Gqeberha","East London","Mthatha","Bhisho","Queenstown","Qonce","Butterworth","Uitenhage","Graaff-Reinet","Cradock"],
    "Free State": ["Bloemfontein","Welkom","Sasolburg","Kroonstad","Bethlehem","Parys","Virginia","Odendaalsrus","Phuthaditjhaba","Harrismith"],
    "Gauteng": ["Johannesburg","Pretoria","Soweto","Midrand","Centurion","Benoni","Boksburg","Sandton","Alberton","Roodepoort"],
    "KwaZulu-Natal": ["Durban","Pietermaritzburg","Newcastle","Richards Bay","Empangeni","Ladysmith","Pinetown","Ulundi","Vryheid","Port Shepstone"],
    "Limpopo": ["Polokwane","Tzaneen","Thohoyandou","Mokopane","Musina","Bela-Bela","Giyani","Phalaborwa","Lebowakgomo","Modimolle"],
    "Mpumalanga": ["Nelspruit","Middelburg","Emalahleni","Secunda","Ermelo","Standerton","White River","Delmas","Bethal","Barberton"],
    "Northern Cape": ["Kimberley","Upington","Springbok","Kuruman","De Aar","Postmasburg","Colesberg","Prieska","Douglas","Warrenton"],
    "North West": ["Rustenburg","Mahikeng","Klerksdorp","Potchefstroom","Brits","Vryburg","Lichtenburg","Zeerust","Wolmaransstad","Mmabatho"],
    "Western Cape": ["Cape Town","Stellenbosch","Paarl","George","Worcester","Mossel Bay","Knysna","Hermanus","Oudtshoorn","Saldanha"]
};

const province = document.getElementById("province");
const city = document.getElementById("city");

function loadCities(){
    const list = cities[province.value] || [];
    city.innerHTML = '<option value="" disabled selected>Select City / District</option>';
    list.forEach(c => {
        const opt = document.createElement("option");
        opt.value = c;
        opt.textContent = c;
        city.appendChild(opt);
    });
}

province.addEventListener("change", function(){
    loadCities();
    if(this.value) this.classList.add("has-value");
});

document.querySelectorAll("select").forEach(sel => {
    sel.addEventListener("change", function(){
        if(this.value) this.classList.add("has-value");
    });
});

document.getElementById("togglePw").addEventListener("click", function(){
    const pw = document.getElementById("password");
    const icon = document.getElementById("eyeIcon");
    if(pw.type === "password"){
        pw.type = "text";
        icon.innerHTML = '<path d="M12 7c2.8 0 5 2.2 5 5 0 .6-.1 1.2-.3 1.7l2.9 2.9c1.9-1.6 3.4-3.7 4.3-6.1-1.7-4.4-6-7.5-11-7.5-1.8 0-3.5.4-5 1.1l2.1 2.1c.5-.1 1-.2 1.5-.2zM2 4.3l2.3 2.3C3.1 8.5 1.7 10.1 1 12c1.7 4.4 6 7.5 11 7.5 1.6 0 3.1-.3 4.5-.9l2.6 2.6 1.4-1.4L3.4 2.9 2 4.3zm7 7l2 2c-.8.1-1.5.6-1.9 1.3l1.4 1.4c1-.9 1.6-2.2 1.6-3.7 0-.6-.1-1.2-.3-1.7L9 11.3z"/>';
    } else {
        pw.type = "password";
        icon.innerHTML = '<path d="M12 4.5C7 4.5 2.7 7.6 1 12c1.7 4.4 6 7.5 11 7.5s9.3-3.1 11-7.5C21.3 7.6 17 4.5 12 4.5zm0 12.5c-2.8 0-5-2.2-5-5s2.2-5 5-5 5 2.2 5 5-2.2 5-5 5zm0-8c-1.7 0-3 1.3-3 3s1.3 3 3 3 3-1.3 3-3-1.3-3-3-3z"/>';
    }
});
</script>

</body>
</html>
