# SFPMS — School Feeding Program Management System (Panabo City Division)

A complete, role-based management system designed specifically for the DepEd Panabo City Division to monitor and manage the School Feeding Program across all 62 Panabo City schools.

## Quick Setup (XAMPP / Laragon)

### 1. Place Files
Copy the `sfpms/` folder to your web directory (e.g., `C:\xampp\htdocs\sfpms\` or `C:\laragon\www\sfpms\`).

### 2. Import the Database
1. Start **Apache** and **MySQL**.
2. Open **phpMyAdmin** → `http://localhost/phpmyadmin`
3. Click **Import** → choose `sfpms/database/sfpms.sql` → **Go**

### 3. Open the App
Visit: `http://localhost/sfpms/pages/login.php`

---

## Role-Based Access & Demo Credentials
The system features strict role-based access control. The database is pre-seeded with all 62 schools in Panabo City (Elementary & High Schools). Elementary schools support Grades 1 to 6, and High Schools support Grades 7 to 12.

| Role | Email | Password | Access Level |
|------|-------|----------|--------------| 
| **Super Admin** | `admin@sfpms.edu.ph` | `password` | All schools, full dashboard, inventory, system-wide reports. |
| **School Admin** | `admin1@sfpms.edu.ph` | `password` | Panabo Central ES only. Full school overview, inventory management. *Note: Pre-seeded accounts exist from admin1@ to admin62@sfpms.edu.ph for all 62 schools.* |
| **Teacher** | `ana.bautista@sfpms.edu.ph` | `password` | Panabo Central ES (Grade 1 - Lily). Only handles 1 grade and her specific section's students. Cannot access inventory. |

*Note: You can check `database/sfpms.sql` for more teacher accounts and high school examples (e.g., School 45 - A. O. Floirendo National High School).*

---

## Database Schema (10 Tables)

| # | Table | Description |
|---|-------|-------------|
| 1 | `schools` | All 62 Panabo City schools with Active/Inactive status |
| 2 | `users` | System accounts with role, school, grade, and section assignment |
| 3 | `students` | Student identity data (LRN, name, birthdate, sex, guardian contact) |
| 4 | `enrollments` | Student placement — links students to a school, grade, and section |
| 5 | `feeding_sessions` | Per-school feeding session records (date, meal type, remarks) |
| 6 | `feeding_attendance` | Per-student attendance per feeding session |
| 7 | `feeding_session_items` | Inventory items consumed per feeding session (qty used) |
| 8 | `nutritional_records` | Weight/height records with auto-computed BMI |
| 9 | `inventory` | School-level stock tracking with low-stock thresholds |
| 10 | `notifications` | System-generated alerts (low stock, missed sessions, recheck reminders) |

> **`beneficiaries` is a VIEW** (not a real table) that joins `students` and `enrollments` for backward compatibility with existing queries.

### Why students + enrollments?
The original `beneficiaries` table mixed identity data (who the student is) with placement data (where they study). Splitting into `students` + `enrollments` means:
- A student can be re-enrolled without losing their history.
- The `beneficiaries` view makes all existing SELECT queries work unchanged.

---

## Key Features & Updates
- **Role-Aware Dashboard & Charts**
  - **Super Admin**: Views overview statistics and comparison charts across all Panabo City schools (sorted descending).
  - **School Admin**: Views a bar chart of the number of beneficiaries per grade level specific to their assigned school.
  - **Teacher**: Views an "Attendance Today" doughnut chart showing real-time presence data for their specific section.
- **Global Navbar Notifications** — A dynamic notification bell in the top navigation bar alerts users of students who need a nutritional recheck, students with 3+ missed sessions, and low stock inventory items.
- **Sortable & Filterable Data Tables** — All major modules (Beneficiaries, Nutritional Records, Feeding Log, Inventory) feature reusable ascending/descending sorting on columns. Additional quick-filters exist for Grade Level (1-12), BMI Classifications, and Inventory Stock Status.
- **Beneficiary Management** — Automatically filtered by grade and section for teachers. Includes tracking for `guardian_contact` fields.
- **Feeding Log** — Session creation and section-specific attendance checklists.
- **Nutritional Records** — Auto-calculates BMI and assigns WHO classifications (Severely Wasted, Normal, Obese, etc.).
- **Inventory Tracking** — Exclusive to School Admins and Super Admins. Warns when stocks (Rice, Cooking Oil, etc.) drop below threshold.

---

## Security
- **Password Hashing** — All passwords stored using PHP `password_hash()` with bcrypt (PASSWORD_DEFAULT). Verified on login with `password_verify()`. Plain-text passwords are never stored.
- **CSRF Protection** — Every state-changing form and AJAX request includes a CSRF token. The central router validates the token on all POST actions before processing.
- **PDO Prepared Statements** — All queries use parameterized prepared statements. Dynamic SQL concatenation has been removed from the dashboard and notification modules.
- **Strict Role Boundaries** — Enforced in PHP via `Auth::checkRole()`. Teachers physically cannot query or alter data outside their assigned `grade_level` and `section`.

---

## Suggestions for Future Expansion (Not Overengineered)
1. **School Year/Semester Selection:** Filtering records based on the active academic year, so older data isn't mixed with the current year's feeding sessions.
2. **Export to DepEd Excel Formats:** Instead of standard PDFs, exporting attendance and BMI data directly into the exact Excel template DepEd requires for submission.
3. **SMS Alerts via Guardian Contact:** Use the stored `guardian_contact` number to notify parents when a student is flagged as Severely Wasted or has excessive absences.
