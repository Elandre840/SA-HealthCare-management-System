# Setup Guide — SA Healthcare Management System

This guide walks you through running the project locally on **Windows with XAMPP**. The same steps apply on macOS/Linux with Apache, PHP, and MySQL installed.

---

## Requirements

| Requirement | Recommended version |
|-------------|---------------------|
| PHP | 8.0+ |
| MySQL / MariaDB | 10.4+ |
| Apache | XAMPP bundle |
| Browser | Chrome, Edge, or Firefox |

---

## 1. Install XAMPP

1. Download and install [XAMPP](https://www.apachefriends.org/).
2. Open the **XAMPP Control Panel**.
3. Start **Apache** and **MySQL**.

---

## 2. Get the project files

### Option A — Clone from GitHub

```bash
cd C:\xampp\htdocs
git clone https://github.com/Elandre840/SA-HealthCare-management-System.git clinic_system
```

### Option B — Copy manually

Place the project folder at:

```
C:\xampp\htdocs\clinic_system
```

---

## 3. Create the database

1. Open **phpMyAdmin**: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. Click **Import**.
3. Choose `clinic_system_demo_v2.sql` from the project root.
4. Click **Go**.

This creates the database **`clinic_system_demo_v2`** with demo staff, patients, appointments, and facility data.

### Alternative — SQL command line

```bash
mysql -u root -p < clinic_system_demo_v2.sql
```

Leave the password blank if using default XAMPP settings.

---

## 4. Configure the database connection

Open `db.php` and confirm these defaults match your environment:

```php
$host = "localhost";
$username = "root";
$password = "";
$database = "clinic_system_demo_v2";
```

Change `$username`, `$password`, or `$database` only if your MySQL setup differs.

---

## 5. Run the application

Open in your browser:

```
http://localhost/clinic_system/login.php
```

If Apache uses a different port (e.g. 8080):

```
http://localhost:8080/clinic_system/login.php
```

---

## 6. Log in with demo accounts

Login requires **email**, **password**, **province**, **city**, **facility**, and **role** — all must match the account record.

### Eastern Cape — Qonce — Clinic (primary demo site)

| Role | Email | Password | Province | City | Facility |
|------|-------|----------|----------|------|----------|
| Reception | reception@clinic.com | 123456 | Eastern Cape | Qonce | Clinic |
| Nurse | nurse@clinic.com | 123456 | Eastern Cape | Qonce | Clinic |
| Doctor | doctor@clinic.com | 123456 | Eastern Cape | Qonce | Clinic |
| Pharmacist | pharma@clinic.com | 123456 | Eastern Cape | Qonce | Clinic |

### Northern Cape — Kimberley — Clinic

| Role | Email | Password |
|------|-------|----------|
| Reception | reception@northerncapeclinic.com | 123456 |
| Nurse | nurse@northerncapeclinic.com | 123456 |
| Doctor | doctor@northerncapeclinic.com | 123456 |
| Pharmacist | pharma@northerncapeclinic.com | 123456 |

Use **Northern Cape → Kimberley → Clinic** on the login form.

### Western Cape — Cape Town — Clinic

| Role | Email | Password |
|------|-------|----------|
| Reception | reception_wc@clinic.com | 123456 |
| Nurse | nurse_wc@clinic.com | 123456 |
| Doctor | doctor_wc@clinic.com | 123456 |
| Pharmacist | pharma_wc@clinic.com | 123456 |

Use **Western Cape → Cape Town → Clinic** on the login form.

---

## 7. Walk through the workflow

1. **Reception** — Register or select a patient, book an appointment, move patient into the queue.
2. **Nurse** — Capture vitals and notes, send patient to doctor (or trigger emergency triage).
3. **Doctor** — Review vitals, add diagnosis and prescription, send to pharmacy or referral.
4. **Pharmacist** — Review prescription and mark medication as dispensed.

Each role has its own dashboard with tabs for patients, queue, announcements, and reports where applicable.

---

## Troubleshooting

### "Connection failed" on page load

- Confirm MySQL is running in XAMPP.
- Verify `db.php` credentials.
- Ensure `clinic_system_demo_v2` was imported successfully.

### "You can only log in to your assigned facility"

- Province, city, facility, and role on the login form must exactly match the account (see tables above).

### Blank page or PHP errors

- Enable error display temporarily in `php.ini`:
  - `display_errors = On`
- Check Apache error logs in `xampp/apache/logs/error.log`.

### Province emblem not showing

- Emblem files live in `assets/emblems/`. The app resolves paths from the province name (e.g. `easterncape.png` for Eastern Cape).

### MediAlert email / SMS

- Email uses PHP `mail()` and may not send without a configured mail server on localhost.
- SMS is **log-only** in demo mode; see `logs/medi_alert_sms.log` after triggering an emergency alert.

---

## Optional: migration scripts

The repo includes one-off migration scripts used during development. **Fresh installs should use the SQL dump only.**

| File | Purpose |
|------|---------|
| `migrate_v2.php` | Schema v2 upgrades |
| `migrate_emergency_triage.php` | Emergency triage columns |
| `migrate_users_phase3_fks.php` | User foreign keys |
| `migrate_users_phase4.php` | User model phase 4 |
| `migrate_drop_unused.php` | Remove unused tables |

Run these only if you are upgrading an older local database — not needed after importing `clinic_system_demo_v2.sql`.

---

## Security reminder

This project uses demo credentials and simplified security for portfolio demonstration. Before any real deployment:

- Hash all passwords (bcrypt)
- Use prepared statements everywhere
- Add HTTPS, CSRF protection, and input validation
- Never expose real patient health information

---

## Need help?

Open an issue on the [GitHub repository](https://github.com/Elandre840/SA-HealthCare-management-System/issues) or refer to [README.md](README.md) for project overview and features.
