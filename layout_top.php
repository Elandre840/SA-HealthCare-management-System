
<?php
include "auth.php";
force_login();

include "ui_theme.php";
$theme = get_theme();
$facility_label = get_facility_label();

$name = $_SESSION['name'] ?? "User";
$role = $_SESSION['role'] ?? "guest";
$province = $_SESSION['province'] ?? "Unknown";

$current = basename($_SERVER['PHP_SELF']);
function activeLink($file, $current){ return ($file === $current) ? 'active' : ''; }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title><?= htmlspecialchars($facility_label) ?></title>

<style>
:root{
  --accent: <?= $theme['accent'] ?>;
  --accent2: <?= $theme['accent2'] ?>;
  --bg:#f4f6fb;
  --card:#fff;
  --line: rgba(15,23,42,.08);
  --muted:#64748b;
}
*{box-sizing:border-box}
html,body{height:100%;margin:0;font-family:Segoe UI,Arial;background:var(--bg)}
.container{display:flex;min-height:100vh}
.sidebar{
  width:260px;flex-shrink:0;min-height:100vh;color:#fff;
  background:linear-gradient(180deg,var(--accent),var(--accent2));
  padding:18px;display:flex;flex-direction:column;justify-content:space-between;
}
.brand{
  display:flex;gap:12px;align-items:center;
  padding:12px;border-radius:16px;background:rgba(255,255,255,.10);
  border:1px solid rgba(255,255,255,.15);
}
.brand img{width:56px;height:56px;object-fit:contain;border-radius:12px;background:rgba(255,255,255,.14);padding:6px}
.brand .t1{font-weight:900;font-size:14px;line-height:1.1}
.brand .t2{opacity:.9;font-size:12px;margin-top:3px}
.menu{margin-top:14px;display:flex;flex-direction:column;gap:8px}
.menu a{
  color:#fff;text-decoration:none;padding:10px 12px;border-radius:12px;
  background:rgba(255,255,255,.10);border:1px solid rgba(255,255,255,.12);
  font-weight:700;transition:.15s ease;
}
.menu a:hover{background:rgba(255,255,255,.18);transform:translateY(-1px)}
.menu a.active{background:#fff;color:var(--accent);font-weight:900}

.userBox{
  background:rgba(0,0,0,.18);border:1px solid rgba(255,255,255,.18);
  padding:12px;border-radius:16px;font-size:13px
}
.userBox b{display:block;font-size:14px;margin-top:4px}
.userBox a{color:#fff;text-decoration:none;font-weight:900;display:inline-block;margin-top:10px}
.userBox a:hover{text-decoration:underline}

.main{flex:1;padding:18px 22px}
.topbar{
  background:#fff;border:1px solid var(--line);border-radius:18px;
  padding:14px 18px;margin-bottom:18px;
  display:flex;justify-content:space-between;align-items:center;
  box-shadow:0 10px 25px rgba(2,6,23,.06);
}
.topbar .title{font-weight:900}
.topbar .sub{color:var(--muted);font-size:13px;margin-top:3px}
.chip{
  display:flex;align-items:center;gap:10px;background:#f8fafc;border:1px solid var(--line);
  padding:10px 12px;border-radius:999px;font-size:13px
}
.avatar{
  width:34px;height:34px;border-radius:999px;background:linear-gradient(135deg,var(--accent),var(--accent2));
  color:#fff;display:grid;place-items:center;font-weight:900;
}
.card{
  background:var(--card);border:1px solid var(--line);border-radius:18px;
  padding:18px;margin-bottom:18px;box-shadow:0 10px 25px rgba(2,6,23,.06);
}
</style>
</head>
<body>
<div class="container">

  <aside class="sidebar">
    <div>
      <div class="brand">
        <img src="assets/emblems/<?= htmlspecialchars($theme['emblem']) ?>" alt="Emblem">
        <div>
          <div class="t1"><?= htmlspecialchars($facility_label) ?></div>
          <div class="t2"><?= htmlspecialchars($province) ?></div>
        </div>
      </div>

      <nav class="menu">
        <a class="<?= activeLink('add_patient.php',$current) ?>" href="add_patient.php">Dashboard</a>
        <a class="<?= activeLink('nurse_dashboard.php',$current) ?>" href="nurse_dashboard.php">Nurse</a>
        <a class="<?= activeLink('doctor_dashboard.php',$current) ?>" href="doctor_dashboard.php">Doctor</a>
        <a class="<?= activeLink('pharmacist_dashboard.php',$current) ?>" href="pharmacist_dashboard.php">Pharmacy</a>
      </nav>
    </div>

    <div class="userBox">
      Logged in as:
      <b><?= htmlspecialchars($name) ?></b>
      <div style="opacity:.9">Role: <?= htmlspecialchars($role) ?></div>
      <a href="logout.php">Logout</a>
    </div>
  </aside>

  <main class="main">
    <div class="topbar">
      <div>
        <div class="title">SA Health Database System</div>
        <div class="sub"><?= htmlspecialchars($facility_label) ?></div>
      </div>
      <div class="chip">
        <div class="avatar"><?= strtoupper(substr($name,0,1)) ?></div>
        <div>
          <div style="font-weight:900;line-height:1.1"><?= htmlspecialchars($name) ?></div>
          <div style="color:var(--muted);font-size:12px"><?= htmlspecialchars($role) ?></div>
        </div>
      </div>
    </div>
