# SFPMS Codebase Overview

This document explains how your codebase is structured, using the **MVC (Model-View-Controller)** pattern.

## Classes vs. Controllers: What's the difference?

In your system, responsibilities are split to keep the code clean and manageable:

### 1. `classes/` (The "Models" / Business Logic)

- **What they do:** They handle the "heavy lifting" and talk directly to the database. They contain the SQL queries (`SELECT`, `INSERT`, `UPDATE`, `DELETE`) and core business rules.
- **Analogy:** The **Kitchen** in a restaurant. They prepare the actual food (data) but don't care how it is served to the customer.
- **Examples:** `Beneficiary.php` knows _how_ to save a student to the DB. `FeedingLog.php` knows _how_ to calculate attendance.

### 2. `controllers/` (The "Traffic Cops")

- **What they do:** They sit between the user (Views) and the data (Classes). When a user visits a page, the controller gathers the required data from the Classes. When a user submits a form, the controller catches the data, decides which Class should handle it, and then redirects the user.
- **Analogy:** The **Waiter** in a restaurant. They take your order (input), go to the kitchen (classes) to get the food (data), and bring it back to your table (views).
- **Examples:** `controllers/beneficiaries.php` gets the page number from the URL, asks `Beneficiary::getAll()` for the data, and gets it ready for `pages/beneficiaries.php` to display.

### 3. `pages/` (The "Views")

- **What they do:** This is what the user actually sees. They contain HTML, CSS classes, and just enough PHP to `echo` the data passed to them by the controllers.
- **Analogy:** The **Dining Table**. It's purely for presentation.

---

## Directory & File Breakdown

### `/classes` (Database & Logic)

- **`Auth.php`**: Handles login verification, session management, and role checking.
- **`Beneficiary.php`**: Contains CRUD logic for students/beneficiaries.
- **`Dashboard.php`**: Gathers all the statistical data and chart data for the dashboard.
- **`FeedingLog.php`**: Handles creating feeding sessions and saving attendance checklists.
- **`Inventory.php`**: Manages school stock and low-stock threshold logic.
- **`Notification.php`**: Generates and manages the alerts (missed sessions, low stock) for the navbar bell.
- **`NutritionalRecord.php`**: Handles BMI records and auto-calculates BMI classifications.
- **`Report.php`**: Gathers aggregated data specifically for the printable Reports module.
- **`School.php`**: CRUD logic for managing the 62 Panabo City schools.
- **`User.php`**: CRUD logic for system accounts (Teachers, Admins).

### `/controllers` (Data Gathering & Routing)

- **`beneficiaries.php`, `dashboard.php`, etc.**: These scripts run _before_ the page loads. They grab data using the classes above and pass it to the view.
- **`router.php`**: The central brain for all form submissions (POST requests). Every "Add", "Edit", or "Delete" button sends data here first. It checks CSRF tokens, calls the appropriate class, and redirects back with a success/error message.

### `/pages` (The Visual Interface)

- **`beneficiaries.php`, `dashboard.php`, etc.**: The HTML tables, forms, and layout for the user.
- **`login.php`**: The login screen interface.
- **`router.php`**: A simple proxy file that points to `controllers/router.php`.

### `/includes` (Reusable UI Components)

- **`header.php`**: The top navigation bar, CSS links, and the start of the HTML document.
- **`footer.php`**: The closing HTML tags and JS script links.
- **`modals.php`**: Contains the hidden pop-up forms (Add/Edit modals) used across the site.
- **`helpers.php`**: Utility functions like `sortUrl()`, `sortIcon()`, and CSRF protection functions.
- **`pagination.php`**: The logic to generate the "Page 1, 2, 3..." buttons at the bottom of tables.

### `/config` (System Settings)

- **`db.php`**: Contains your database credentials (DB_HOST, DB_USER) and the `getPDO()` function used by every class to connect to MySQL securely.

### `/database`

- **`sfpms.sql`**: The database schema and all the pre-seeded dummy data for Panabo City.

### `/assets`

- **`/css` & `/js`**: Custom stylesheets and JavaScript files (like handling AJAX or initializing charts).
