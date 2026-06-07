
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
        $role      = mysqli_real_escape_string($conn, $role);
        $province  = mysqli_real_escape_string($conn, $province);
        $city      = mysqli_real_escape_string($conn, $city);
        $facility  = mysqli_real_escape_string($conn, $facility);
        $type      = mysqli_real_escape_string($conn, $type);

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
                $values[]  = "'staff'";
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

            if(column_exists($conn, 'users', 'role')){
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
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Register</title>

<style>
*{
    box-sizing:border-box;
}

body{
    margin:0;
    font-family:Segoe UI, Arial, sans-serif;
    background:linear-gradient(135deg,#dbeafe,#eff6ff,#f8fafc);
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    padding:30px 15px;
}

.wrapper{
    width:100%;
    max-width:540px;
}

.card{
    background:rgba(255,255,255,0.96);
    border-radius:24px;
    padding:30px;
    box-shadow:0 20px 50px rgba(0,0,0,0.15);
    border:1px solid rgba(255,255,255,0.7);
}

.header{
    text-align:center;
    margin-bottom:22px;
}

.header .icon{
    width:72px;
    height:72px;
    border-radius:20px;
    background:linear-gradient(135deg,#2563eb,#1d4ed8);
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:32px;
    margin:0 auto 14px auto;
    box-shadow:0 10px 24px rgba(37,99,235,0.30);
}

.header h2{
    margin:0;
    color:#0f172a;
    font-size:30px;
}

.header p{
    margin:8px 0 0 0;
    color:#64748b;
    font-size:14px;
}

.message{
    padding:12px 14px;
    border-radius:12px;
    margin-bottom:15px;
    font-size:14px;
    font-weight:600;
}

.success{
    background:#dcfce7;
    color:#166534;
    border:1px solid #86efac;
}

.error{
    background:#fee2e2;
    color:#b91c1c;
    border:1px solid #fca5a5;
}

.section-title{
    margin-top:20px;
    margin-bottom:8px;
    font-size:13px;
    font-weight:700;
    color:#334155;
    text-transform:uppercase;
    letter-spacing:.4px;
}

input, select{
    width:100%;
    padding:14px 14px;
    margin-top:10px;
    border-radius:14px;
    border:1px solid #cbd5e1;
    background:#f8fafc;
    outline:none;
    transition:0.2s;
    font-size:15px;
}

input:focus, select:focus{
    border-color:#2563eb;
    box-shadow:0 0 0 4px rgba(37,99,235,0.10);
    background:white;
}

.grid-2{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:12px;
}

button{
    width:100%;
    margin-top:22px;
    padding:15px;
    border:none;
    border-radius:14px;
    background:linear-gradient(135deg,#2563eb,#1e40af);
    color:white;
    font-size:16px;
    font-weight:700;
    cursor:pointer;
    transition:0.2s;
    box-shadow:0 10px 20px rgba(37,99,235,0.20);
}

button:hover{
    transform:translateY(-1px);
    box-shadow:0 14px 24px rgba(37,99,235,0.28);
}

.helper{
    margin-top:14px;
    text-align:center;
    color:#64748b;
    font-size:13px;
}

.hidden{
    display:none;
}

@media (max-width: 640px){
    .card{
        padding:22px;
        border-radius:18px;
    }

    .grid-2{
        grid-template-columns:1fr;
    }
}
</style>
</head>
<body>

<div class="wrapper">
    <div class="card">

        <div class="header">
            <div class="icon">📝</div>
            <h2 id="formTitle">Register</h2>
            <p>Create a new staff or patient record in the system</p>
        </div>

        <?php if($success): ?>
            <div class="message success"><?= $success ?></div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="message error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">

            <div class="section-title">Account Type</div>
            <select name="type" id="type" required>
                <option value="">Select Type</option>
                <option value="Staff">Staff</option>
                <option value="Patient">Patient</option>
            </select>

            <div class="section-title">Personal Information</div>
            <div class="grid-2">
                <input name="name" placeholder="First Name" required>
                <input name="surname" placeholder="Surname" required>
            </div>

            <div class="grid-2">
                <input name="id" placeholder="ID Number">
                <input name="phone" placeholder="Phone Number">
            </div>

            <input name="email" type="email" placeholder="Email Address" required>

            <div id="staffFields">
                <div class="section-title">Staff Details</div>
                <div class="grid-2">
                    <input name="emp" placeholder="Employee Number">
                    <select name="role">
                        <option value="">Select Role</option>
                        <option value="Reception">Reception</option>
                        <option value="Nurse">Nurse</option>
                        <option value="Doctor">Doctor</option>
                        <option value="Pharmacist">Pharmacist</option>
                        <option value="Admin">Admin</option>
                    </select>
                </div>
            </div>

            <div class="section-title">Location</div>
            <div class="grid-2">
                <select id="province" name="province" required>
                    <option value="">Select Province</option>
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

                <select id="city" name="city" required>
                    <option value="">Select City</option>
                </select>
            </div>

            <select name="facility" required>
                <option value="">Select Facility</option>
                <option>Clinic</option>
                <option>Hospital</option>
                <option>Pharmacy</option>
                <option>Doctor Practice</option>
            </select>

            <div class="section-title">Security</div>
            <input type="password" name="password" placeholder="Create Password" required>

            <button type="submit">Create Account</button>
        </form>

        <div class="helper">
            Make sure the province, city and facility match the assigned user location.
        </div>

    </div>
</div>

<script>
const typeSelect = document.getElementById("type");
const staffFields = document.getElementById("staffFields");
const formTitle = document.getElementById("formTitle");

function toggleTypeFields(){
    if(typeSelect.value === "Patient"){
        staffFields.classList.add("hidden");
        formTitle.innerText = "Patient Registration";
    } else if(typeSelect.value === "Staff"){
        staffFields.classList.remove("hidden");
        formTitle.innerText = "Staff Registration";
    } else {
        staffFields.classList.remove("hidden");
        formTitle.innerText = "Register";
    }
}

typeSelect.addEventListener("change", toggleTypeFields);
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
    const selected = province.value;
    const list = cities[selected] || [];

    city.innerHTML = '<option value="">Select City</option>';

    list.forEach(c => {
        const opt = document.createElement("option");
        opt.value = c;
        opt.textContent = c;
        city.appendChild(opt);
    });
}

province.addEventListener("change", loadCities);
loadCities();
</script>

</body>
</html>
