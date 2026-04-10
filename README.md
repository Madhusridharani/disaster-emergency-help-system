# 🚨 Disaster Emergency Help System (DEHS)

A PHP + MySQL web application for reporting and managing disaster emergencies.

---

## 📁 File Structure

```
disaster_system/
├── index.php       — Login page
├── register.php    — User registration
├── dashboard.php   — User dashboard (view own reports)
├── report.php      — Submit an emergency report
├── admin.php       — Admin panel (view all + update status)
├── logout.php      — Session destroy & redirect
├── db.php          — Database connection & helpers
├── style.css       — Complete stylesheet
└── schema.sql      — MySQL database schema + sample data
```

---

## ⚙️ Setup Instructions

### Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- A local server like **XAMPP**, **WAMP**, or **LAMP**

### Step 1 — Start your server
Start Apache and MySQL from your XAMPP/WAMP control panel.

### Step 2 — Copy project files
Place the `disaster_system/` folder inside:
- **XAMPP**: `C:/xampp/htdocs/disaster_system/`
- **WAMP**:  `C:/wamp64/www/disaster_system/`

### Step 3 — Create the database
1. Open **phpMyAdmin** → `http://localhost/phpmyadmin`
2. Click **Import** tab
3. Choose the `schema.sql` file and click **Go**

This creates the `disaster_db` database with tables and sample data.

### Step 4 — Configure database credentials
Open `db.php` and update if needed:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');   // your MySQL username
define('DB_PASS', '');       // your MySQL password (blank for XAMPP default)
define('DB_NAME', 'disaster_db');
```

### Step 5 — Open in browser
Visit: `http://localhost/disaster_system/`

---

## 🔐 Login Credentials

### Admin
| Field    | Value               |
|----------|---------------------|
| Email    | admin@disaster.gov  |
| Password | admin123            |

### Sample Users (if schema.sql sample data was imported)
| Name         | Email               | Password    |
|--------------|---------------------|-------------|
| Arjun Kumar  | arjun@example.com   | password    |
| Priya Sharma | priya@example.com   | password    |

---

## 🌟 Features

- ✅ User registration with validation
- ✅ Secure login with PHP sessions
- ✅ Password hashing (bcrypt)
- ✅ SQL injection prevention (prepared statements)
- ✅ XSS prevention (input sanitization)
- ✅ Emergency report submission (Fire/Flood/Accident/Medical/Other)
- ✅ User dashboard with personal report history
- ✅ Admin panel — view all reports, filter by type & status
- ✅ Admin can update report status (Pending → In Progress → Resolved)
- ✅ Flash messages (success/error)
- ✅ Fully responsive mobile-friendly design
- ✅ Emergency helpline reminder (112)

---

## 🆘 Emergency Note
This system is for **non-critical or follow-up reporting**.  
For life-threatening emergencies, always call **112** first.
