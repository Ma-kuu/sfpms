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

## Suggestions for Future Expansion (Not Overengineered)
To ensure the system provides great value without becoming overly complex, the following additions were made or are highly recommended:
1. **Guardian Contact Number:** (Added to DB) Useful for sending SMS updates or emergency contact if a student gets severely wasted or falls sick during feeding.
2. **School Year/Semester Selection:** Filtering records based on the active academic year, so older data isn't mixed with the current year's feeding sessions.
3. **Export to DepEd Excel Formats:** Instead of standard PDFs, exporting attendance and BMI data directly into the exact Excel template DepEd requires for submission.

## Security
- PDO prepared statements throughout.
- Strict role boundaries enforced in PHP (`Auth::checkRole()`).
- Teacher isolation: Teachers physically cannot query or alter data outside their assigned `grade_level` and `section`.
