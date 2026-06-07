
<?php
session_start();
include 'db.php';

$error = "";

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $pass  = $_POST['password'];

    $province = $_POST['province'];
    $city     = $_POST['city'];
    $facility = $_POST['facility'];

    // ✅ normalize UI role → lowercase
    $roleUI = strtolower(trim($_POST['role']));

    $res = mysqli_query($conn,"SELECT * FROM users WHERE email='$email' LIMIT 1");

    if($res && mysqli_num_rows($res) > 0){

        $user = mysqli_fetch_assoc($res);

        $storedPassword = $user['password'];

        // ✅ supports plain + hashed password
        if(
            $pass === $storedPassword ||
            password_verify($pass, $storedPassword)
        ){

            $roleDB = strtolower($user['role']);

            // ✅ facility + role validation
            if(
                strtolower($province) !== strtolower($user['province']) ||
                strtolower($city) !== strtolower($user['city']) ||
                strtolower($facility) !== strtolower($user['facility']) ||
                $roleUI !== $roleDB
            ){
                $error = "❌ You can only log in to your assigned facility.";
            } else {

                // ✅ FIXED SESSION VARIABLES
                $_SESSION['user_id']  = $user['user_id'];
                $_SESSION['name']     = $user['full_name'];
                $_SESSION['role']     = $roleDB;

                $_SESSION['province'] = $user['province'];
                $_SESSION['city']     = $user['city'];
                $_SESSION['facility'] = $user['facility'];

                include_once 'clinic_schema.php';
                $_SESSION['facility_id'] = cs_facility_id($conn, $user['province'], $user['city'], $user['facility']);

                // ✅ ROLE-BASED REDIRECT
                if($roleDB == 'reception'){
                    header("Location: receptionist.php");

                } elseif($roleDB == 'nurse'){
                    header("Location: nurse.php");

                } elseif($roleDB == 'doctor'){
                    header("Location: doctor.php");

                } elseif($roleDB == 'pharmacist'){
                    header("Location: pharmacy.php");

                } else {
                    $error = "❌ Unknown role";
                }

                exit;
            }

        } else {
            $error = "❌ Incorrect password";
        }

    } else {
        $error = "❌ Account not found";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SA Health Login</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
*, *::before, *::after { box-sizing: border-box; }

body {
    margin: 0;
    min-height: 100vh;
    font-family: 'Inter', 'Segoe UI', sans-serif;
    background: url('assets/backgrounds/sa_flag.jpg') no-repeat center center fixed;
    background-size: cover;
    overflow-x: hidden;
}

.bg-overlay {
    position: fixed;
    inset: 0;
    background: linear-gradient(135deg, rgba(0,30,80,0.15) 0%, rgba(0,0,0,0.08) 50%, rgba(180,0,0,0.1) 100%);
    pointer-events: none;
    z-index: 0;
}

.bg-decor {
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 0;
    overflow: hidden;
}

.bg-decor svg {
    position: absolute;
    opacity: 0.12;
}

.bg-decor .cross-1 { top: 8%; right: 12%; width: 80px; opacity: 0.18; }
.bg-decor .cross-2 { bottom: 18%; left: 6%; width: 120px; opacity: 0.15; }
.bg-decor .ekg { bottom: 22%; left: 14%; width: 200px; opacity: 0.2; }
.bg-decor .cityscape { bottom: 0; right: 0; width: 45%; opacity: 0.25; }

.flow-paths {
    position: fixed;
    inset: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 1;
}

.page {
    position: relative;
    z-index: 2;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
}

.wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 28px;
    max-width: 1100px;
    width: 100%;
}

.center {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 24px;
    flex-shrink: 0;
}

.side {
    display: flex;
    flex-direction: column;
    gap: 18px;
    flex-shrink: 0;
}

.bottom {
    display: flex;
    justify-content: center;
    gap: 18px;
}

.province-card {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 200px;
    padding: 10px 12px 14px;
    background: rgba(255, 255, 255, 0.93);
    border-radius: 14px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.14);
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.8);
}

.province-card::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--accent);
}

.province-card:hover {
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 10px 28px rgba(0, 0, 0, 0.18);
}

.province-card .emblem {
    width: 50px;
    height: 50px;
    flex-shrink: 0;
    background: #eef1f6;
    border: 1px solid #d5dce8;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.province-card .emblem img {
    width: 46px;
    height: 46px;
    object-fit: contain;
    display: block;
    transform: scale(1.35);
}

.province-card .prov-name {
    flex: 1;
    font-size: 11.5px;
    font-weight: 600;
    color: #1a2b4a;
    line-height: 1.25;
}

.province-card .pin {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: var(--accent);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
}

.province-card .pin svg {
    width: 14px;
    height: 14px;
    fill: white;
}

.login-card {
    width: 420px;
    flex-shrink: 0;
    padding: 32px 36px 28px;
    border-radius: 22px;
    background: rgba(255, 255, 255, 0.22);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    border: 1.5px solid rgba(255, 255, 255, 0.65);
    box-shadow:
        0 8px 32px rgba(0, 0, 0, 0.12),
        inset 0 1px 0 rgba(255, 255, 255, 0.5);
}

.logo-wrap {
    text-align: center;
    margin-bottom: 18px;
}

.logo-wrap svg {
    width: 72px;
    height: 72px;
}

.login-card h1 {
    margin: 10px 0 4px;
    font-size: 20px;
    font-weight: 700;
    color: #0a2a6e;
    text-align: center;
    letter-spacing: -0.3px;
}

.tagline {
    margin: 0 0 22px;
    font-size: 12px;
    color: #3d5a8a;
    text-align: center;
    font-weight: 400;
}

.error {
    margin-bottom: 14px;
    padding: 10px 14px;
    background: rgba(255, 230, 230, 0.9);
    border: 1px solid #e53e3e;
    border-radius: 10px;
    font-size: 13px;
    color: #c53030;
}

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
    padding: 13px 40px 13px 42px;
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

.field input::placeholder {
    color: #9aa8be;
}

.field input:focus,
.field select:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.field select {
    cursor: pointer;
    color: #6b7c96;
}

.field select:valid,
.field select.has-value {
    color: #1a2b4a;
}

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
    margin: 0;
    width: auto;
}

.toggle-pw svg {
    width: 18px;
    height: 18px;
    fill: currentColor;
}

.btn-login {
    width: 100%;
    margin-top: 6px;
    padding: 14px;
    background: linear-gradient(135deg, #0a2a6e 0%, #1a4080 100%);
    color: white;
    border: none;
    border-radius: 10px;
    font-family: inherit;
    font-size: 14px;
    font-weight: 700;
    letter-spacing: 1.5px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: background 0.2s, transform 0.15s;
    box-shadow: 0 4px 14px rgba(10, 42, 110, 0.35);
}

.btn-login:hover {
    background: linear-gradient(135deg, #0d3278 0%, #1e4a90 100%);
    transform: translateY(-1px);
}

.btn-login svg {
    width: 16px;
    height: 16px;
    fill: white;
}

.secure-footer {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin-top: 18px;
    padding: 8px 12px;
    font-size: 12px;
    font-weight: 600;
    color: #0a2a6e;
    background: rgba(255, 255, 255, 0.75);
    border-radius: 8px;
}

.secure-footer svg {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
}

@media (max-width: 960px) {
    .side, .bottom { display: none; }
    .flow-paths { display: none; }
    .login-card { width: 100%; max-width: 420px; }
}
</style>
</head>

<body>

<div class="bg-overlay"></div>

<div class="bg-decor">
    <svg class="cross-1" viewBox="0 0 40 40"><rect x="17" y="5" width="6" height="30" fill="#c0392b"/><rect x="5" y="17" width="30" height="6" fill="#c0392b"/></svg>
    <svg class="cross-2" viewBox="0 0 40 40"><rect x="17" y="5" width="6" height="30" fill="#2980b9"/><rect x="5" y="17" width="30" height="6" fill="#2980b9"/></svg>
    <svg class="ekg" viewBox="0 0 200 40"><polyline points="0,20 30,20 40,5 55,35 70,20 200,20" fill="none" stroke="#2980b9" stroke-width="2.5"/></svg>
    <svg class="cityscape" viewBox="0 0 400 120" preserveAspectRatio="xMaxYMax meet">
        <rect x="20" y="60" width="30" height="60" fill="#1a5276" opacity="0.6"/>
        <rect x="60" y="40" width="25" height="80" fill="#1a5276" opacity="0.5"/>
        <rect x="95" y="55" width="35" height="65" fill="#1a5276" opacity="0.55"/>
        <rect x="140" y="30" width="28" height="90" fill="#1a5276" opacity="0.6"/>
        <rect x="180" y="50" width="32" height="70" fill="#1a5276" opacity="0.5"/>
        <rect x="220" y="35" width="40" height="85" fill="#1a5276" opacity="0.55"/>
        <rect x="270" y="55" width="30" height="65" fill="#1a5276" opacity="0.5"/>
        <rect x="310" y="45" width="35" height="75" fill="#1a5276" opacity="0.6"/>
        <rect x="355" y="60" width="25" height="60" fill="#1a5276" opacity="0.5"/>
    </svg>
</div>

<svg class="flow-paths" viewBox="0 0 1200 700" preserveAspectRatio="xMidYMid slice">
    <path d="M 180 180 Q 320 220 480 310" stroke="#2d8f52" stroke-width="28" fill="none" opacity="0.55" stroke-linecap="round"/>
    <path d="M 180 350 Q 340 340 480 350" stroke="#2d8f52" stroke-width="28" fill="none" opacity="0.5" stroke-linecap="round"/>
    <path d="M 180 520 Q 320 480 480 390" stroke="#2d8f52" stroke-width="28" fill="none" opacity="0.55" stroke-linecap="round"/>
    <path d="M 1020 180 Q 880 220 720 310" stroke="#2d8f52" stroke-width="28" fill="none" opacity="0.55" stroke-linecap="round"/>
    <path d="M 1020 350 Q 860 340 720 350" stroke="#2d8f52" stroke-width="28" fill="none" opacity="0.5" stroke-linecap="round"/>
    <path d="M 1020 520 Q 880 480 720 390" stroke="#2d8f52" stroke-width="28" fill="none" opacity="0.55" stroke-linecap="round"/>
</svg>

<div class="page">
<div class="wrapper">

<div class="side">
    <div class="province-card" style="--accent:#27ae60" data-province="Eastern Cape">
        <span class="emblem"><img src="assets/emblems/easterncape.png" alt=""></span>
        <span class="prov-name">Eastern Cape</span>
        <span class="pin"><svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg></span>
    </div>
    <div class="province-card" style="--accent:#e67e22" data-province="Northern Cape">
        <span class="emblem"><img src="assets/emblems/northerncape.png" alt=""></span>
        <span class="prov-name">Northern Cape</span>
        <span class="pin"><svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg></span>
    </div>
    <div class="province-card" style="--accent:#2980b9" data-province="Free State">
        <span class="emblem"><img src="assets/emblems/freestate.png" alt=""></span>
        <span class="prov-name">Free State</span>
        <span class="pin"><svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg></span>
    </div>
</div>

<div class="center">

<div class="login-card">

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

<h1>SA Health Database System</h1>
<p class="tagline">Secure. Connected. For a healthier South Africa.</p>

<?php if($error): ?>
<div class="error"><?= $error ?></div>
<?php endif; ?>

<form method="POST">

<div class="field">
    <span class="field-icon"><svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v2h20v-2c0-3.3-6.7-5-10-5z"/></svg></span>
    <input type="email" name="email" placeholder="Username" required>
</div>

<div class="field">
    <span class="field-icon"><svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.8-2.2-5-5-5S7 3.2 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3-9H9V6c0-1.7 1.3-3 3-3s3 1.3 3 3v2z"/></svg></span>
    <input type="password" name="password" id="password" placeholder="Password" required>
    <button type="button" class="toggle-pw" id="togglePw" aria-label="Toggle password visibility">
        <svg viewBox="0 0 24 24" id="eyeIcon"><path d="M12 4.5C7 4.5 2.7 7.6 1 12c1.7 4.4 6 7.5 11 7.5s9.3-3.1 11-7.5C21.3 7.6 17 4.5 12 4.5zm0 12.5c-2.8 0-5-2.2-5-5s2.2-5 5-5 5 2.2 5 5-2.2 5-5 5zm0-8c-1.7 0-3 1.3-3 3s1.3 3 3 3 3-1.3 3-3-1.3-3-3-3z"/></svg>
    </button>
</div>

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
    <span class="field-icon"><svg viewBox="0 0 24 24"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/></svg></span>
    <select id="city" name="city" required>
        <option value="" disabled selected>Select City / District</option>
    </select>
    <span class="field-chevron"><svg viewBox="0 0 24 24"><path d="M7 10l5 5 5-5z"/></svg></span>
</div>

<div class="field">
    <span class="field-icon"><svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14h-2v-2h2v2zm0-4h-2V7h2v6z"/><path d="M12 2l-2 4h4l-2-4z" opacity="0"/><path d="M3 9h2v10H3zm16 0h2v10h-2zM7 9h2v2H7zm8 0h2v2h-2zM7 13h2v2H7zm8 0h2v2h-2z"/></svg></span>
    <select name="facility" required>
        <option value="" disabled selected>Select Facility</option>
        <option>Clinic</option>
        <option>Hospital</option>
        <option>Pharmacy</option>
    </select>
    <span class="field-chevron"><svg viewBox="0 0 24 24"><path d="M7 10l5 5 5-5z"/></svg></span>
</div>

<div class="field">
    <span class="field-icon"><svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v2h20v-2c0-3.3-6.7-5-10-5z"/></svg></span>
    <select name="role" required>
        <option value="" disabled selected>Select Role</option>
        <option>Reception</option>
        <option>Nurse</option>
        <option>Doctor</option>
        <option>Pharmacist</option>
    </select>
    <span class="field-chevron"><svg viewBox="0 0 24 24"><path d="M7 10l5 5 5-5z"/></svg></span>
</div>

<button type="submit" class="btn-login">
    <svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.8-2.2-5-5-5S7 3.2 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"/></svg>
    LOGIN
</button>

</form>

<div class="secure-footer">
    <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.6 3.8 10.7 9 12 5.2-1.3 9-6.4 9-12V5l-9-4zm-1 14l-4-4 1.4-1.4L11 12.2l5.6-5.6L18 8l-7 7z" fill="#27ae60"/></svg>
    Secure access to South Africa's health information
</div>

</div>

<div class="bottom">
    <div class="province-card" style="--accent:#8e44ad" data-province="Limpopo">
        <span class="emblem"><img src="assets/emblems/limpopo.png" alt=""></span>
        <span class="prov-name">Limpopo</span>
        <span class="pin"><svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg></span>
    </div>
    <div class="province-card" style="--accent:#f39c12" data-province="Mpumalanga">
        <span class="emblem"><img src="assets/emblems/mpumalanga.png" alt=""></span>
        <span class="prov-name">Mpumalanga</span>
        <span class="pin"><svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg></span>
    </div>
    <div class="province-card" style="--accent:#7f8c8d" data-province="North West">
        <span class="emblem"><img src="assets/emblems/northwest.png" alt=""></span>
        <span class="prov-name">North West</span>
        <span class="pin"><svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg></span>
    </div>
</div>

</div>

<div class="side">
    <div class="province-card" style="--accent:#c0392b" data-province="Gauteng">
        <span class="emblem"><img src="assets/emblems/gauteng.png" alt=""></span>
        <span class="prov-name">Gauteng</span>
        <span class="pin"><svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg></span>
    </div>
    <div class="province-card" style="--accent:#27ae60" data-province="Western Cape">
        <span class="emblem"><img src="assets/emblems/westerncape.png" alt=""></span>
        <span class="prov-name">Western Cape</span>
        <span class="pin"><svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg></span>
    </div>
    <div class="province-card" style="--accent:#2980b9" data-province="KwaZulu-Natal">
        <span class="emblem"><img src="assets/emblems/kzn.png" alt=""></span>
        <span class="prov-name">KwaZulu-Natal</span>
        <span class="pin"><svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg></span>
    </div>
</div>

</div>
</div>

<script>

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
    let list = cities[province.value] || [];
    city.innerHTML = '<option value="" disabled selected>Select City / District</option>';

    list.forEach(c => {
        let opt = document.createElement("option");
        opt.value = c;
        opt.textContent = c;
        city.appendChild(opt);
    });
}

province.addEventListener("change", function(){
    loadCities();
    this.classList.add("has-value");
});

document.querySelectorAll(".province-card").forEach(box => {
    box.addEventListener("click", function(){
        const selected = this.getAttribute("data-province");
        province.value = selected;
        province.classList.add("has-value");
        loadCities();
    });
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
