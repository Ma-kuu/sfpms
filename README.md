# SFPMS — School Feeding Program Management System

## Quick Setup (XAMPP)

### 1. Place Files
Copy the `sfpms/` folder to:
```
C:\xampp\htdocs\sfpms\
```

### 2. Import the Database
1. Start **Apache** and **MySQL** in XAMPP Control Panel
2. Open **phpMyAdmin** → `http://localhost/phpmyadmin`
3. Click **Import** → choose `sfpms/database/sfpms.sql` → **Go**

### 3. Open the App
Visit: `http://localhost/sfpms/pages/login.php`

---

## Demo Credentials

| Role | Email | Password |
|------|-------|----------|
| Super Admin | `admin@sfpms.edu.ph` | `password` |
| School Admin (Sto. Tomas) | `maria@sfpms.edu.ph` | `password` |
| Teacher (Tagum) | `juan@sfpms.edu.ph` | `password` |

---

## Folder Structure
```
sfpms/
├── config/
│   └── db.php                  ← PDO connection
├── classes/
│   ├── Auth.php                ← Login, logout, role check
│   ├── Beneficiary.php         ← CRUD + absence flagging
│   ├── FeedingLog.php          ← Sessions + attendance
│   ├── NutritionalRecord.php   ← BMI records + WHO classification
│   └── Inventory.php           ← Stock management
├── includes/
│   ├── header.php              ← Bootstrap 5, sidebar, CSS
│   └── footer.php              ← Bootstrap JS close
├── pages/
│   ├── login.php
│   ├── logout.php
│   ├── dashboard.php
│   ├── beneficiaries.php
│   ├── feeding_log.php
│   ├── nutritional.php
│   ├── inventory.php
│   └── reports.php
└── database/
    └── sfpms.sql               ← Full schema + seed data
```

---

## Features
- **Dashboard** — KPI stats, Chart.js bar chart, absence & low-stock alerts
- **Beneficiaries** — LRN-based records, school/grade filter, status badges
- **Feeding Log** — Session management + per-session attendance checklist
- **Nutritional** — Weight/height → auto BMI (WHO classification badges)
- **Inventory** — LOW STOCK badge when quantity ≤ threshold
- **Reports** — Attendance summary, nutritional status, inventory — all printable
- **Flagging** — Automatically detects beneficiaries with 3+ consecutive absences

## Security
- PDO prepared statements throughout
- `password_hash` / `password_verify`
- Role-based access: `super_admin`, `school_admin`, `teacher`
- Session regeneration on login
