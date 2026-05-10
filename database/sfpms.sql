-- ============================================================
-- School Feeding Program Management System (SFPMS)
-- Database: sfpms | Division: Panabo City, Davao del Norte
-- ============================================================

CREATE DATABASE IF NOT EXISTS sfpms
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE sfpms;

-- ============================================================
-- USERS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(120) NOT NULL,
    email       VARCHAR(180) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('super_admin','school_admin','teacher') NOT NULL DEFAULT 'teacher',
    school_id   INT UNSIGNED NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- SCHOOLS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS schools (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(200) NOT NULL,
    address     VARCHAR(300),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- BENEFICIARIES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS beneficiaries (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lrn         VARCHAR(12) NOT NULL UNIQUE,
    first_name  VARCHAR(80) NOT NULL,
    last_name   VARCHAR(80) NOT NULL,
    birthdate   DATE,
    sex         ENUM('Male','Female') NOT NULL,
    grade_level VARCHAR(10) NOT NULL,
    section     VARCHAR(50),
    school_id   INT UNSIGNED NOT NULL,
    status      ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- FEEDING SESSIONS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS feeding_sessions (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id   INT UNSIGNED NOT NULL,
    session_date DATE NOT NULL,
    meal_type   ENUM('Breakfast','Lunch','Snack') NOT NULL DEFAULT 'Lunch',
    remarks     TEXT,
    created_by  INT UNSIGNED,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- FEEDING ATTENDANCE TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS feeding_attendance (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id      INT UNSIGNED NOT NULL,
    beneficiary_id  INT UNSIGNED NOT NULL,
    present         TINYINT(1) NOT NULL DEFAULT 0,
    UNIQUE KEY uq_session_beneficiary (session_id, beneficiary_id),
    FOREIGN KEY (session_id)     REFERENCES feeding_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id)    ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- NUTRITIONAL RECORDS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS nutritional_records (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    beneficiary_id  INT UNSIGNED NOT NULL,
    record_date     DATE NOT NULL,
    weight_kg       DECIMAL(5,2) NOT NULL,
    height_cm       DECIMAL(5,2) NOT NULL,
    bmi             DECIMAL(5,2) GENERATED ALWAYS AS (weight_kg / POW(height_cm / 100, 2)) STORED,
    recorded_by     INT UNSIGNED,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by)    REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- INVENTORY TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS inventory (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id       INT UNSIGNED NOT NULL,
    item_name       VARCHAR(150) NOT NULL,
    unit            VARCHAR(30) NOT NULL DEFAULT 'pcs',
    quantity        DECIMAL(10,2) NOT NULL DEFAULT 0,
    low_stock_threshold DECIMAL(10,2) NOT NULL DEFAULT 10,
    last_updated    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- SEED DATA — PANABO CITY SCHOOLS (Elementary only)
-- Source: DepEd NID Dashboard - Panabo City Division
-- ============================================================
INSERT INTO schools (id, name, address) VALUES
-- Elementary Schools
(1,  'Panabo Central ES SPED Center',                        'Panabo City, Davao del Norte'),
(2,  'New Visayas Central Elementary School',                 'Panabo City, Davao del Norte'),
(3,  'Cabili ES',                                             'Panabo City, Davao del Norte'),
(4,  'Dona Nenita R. Floirendo ES',                           'Panabo City, Davao del Norte'),
(5,  'Gredu ES',                                              'Panabo City, Davao del Norte'),
(6,  'Rizal ES',                                              'Panabo City, Davao del Norte'),
(7,  'Salvacion ES',                                          'Panabo City, Davao del Norte'),
(8,  'San Pedro ES',                                          'Panabo City, Davao del Norte'),
(9,  'San Vicente ES',                                        'Panabo City, Davao del Norte'),
(10, 'A.O. Floirendo Elementary School',                      'Panabo City, Davao del Norte'),
(11, 'P. Changco ES',                                         'Panabo City, Davao del Norte'),
(12, 'Concordia A. Sison ES',                                 'Panabo City, Davao del Norte'),
(13, 'Dalisay Village ES',                                    'Panabo City, Davao del Norte'),
(14, 'Valentin N. Daquio ES',                                 'Panabo City, Davao del Norte'),
(15, 'Manuel A. Javellana ES',                                'Panabo City, Davao del Norte'),
(16, 'Nanyo Central ES',                                      'Panabo City, Davao del Norte'),
(17, 'Rodrigo D. Mabitad Sr. ES',                             'Panabo City, Davao del Norte'),
(18, 'Roxas ES',                                              'Panabo City, Davao del Norte'),
(19, 'Sindaton ES',                                           'Panabo City, Davao del Norte'),
(20, 'Southern Davao ES',                                     'Panabo City, Davao del Norte'),
(21, 'Tibungol ES',                                           'Panabo City, Davao del Norte'),
(22, 'Buenavista ES',                                         'Panabo City, Davao del Norte'),
(23, 'Consolacion ES',                                        'Panabo City, Davao del Norte'),
(24, 'Datu Abdul ES',                                         'Panabo City, Davao del Norte'),
(25, 'Glecerio L. Dondoy CES',                                'Panabo City, Davao del Norte'),
(26, 'J.P. Laurel ES',                                        'Panabo City, Davao del Norte'),
(27, 'Kasilak ES',                                            'Panabo City, Davao del Norte'),
(28, 'Katipunan ES',                                          'Panabo City, Davao del Norte'),
(29, 'Katualan ES',                                           'Panabo City, Davao del Norte'),
(30, 'Teofanis G. Gerona, Sr. ES',                            'Panabo City, Davao del Norte'),
(31, 'Kiotoy ES',                                             'Panabo City, Davao del Norte'),
(32, 'Licanan ES',                                            'Panabo City, Davao del Norte'),
(33, 'Little Panay ES',                                       'Panabo City, Davao del Norte'),
(34, 'Mabunao ES',                                            'Panabo City, Davao del Norte'),
(35, 'Malativas ES',                                          'Panabo City, Davao del Norte'),
(36, 'Namuag ES',                                             'Panabo City, Davao del Norte'),
(37, 'Narciso B. Galapin ES',                                 'Panabo City, Davao del Norte'),
(38, 'San Roque ES',                                          'Panabo City, Davao del Norte'),
(39, 'Sta. Cruz ES',                                          'Panabo City, Davao del Norte'),
(40, 'Tagurot ES',                                            'Panabo City, Davao del Norte'),
(41, 'Waterfall ES',                                          'Panabo City, Davao del Norte'),
(42, 'Sto. Niño Elementary School',                           'Panabo City, Davao del Norte'),
(43, 'San Francisco ES',                                      'Panabo City, Davao del Norte'),
(44, 'Antonio O. Floirendo Elementary School II',             'Panabo City, Davao del Norte'),
-- National High Schools
(45, 'A. O. Floirendo National High School',                  'Panabo City, Davao del Norte'),
(46, 'Sindaton National High School',                         'Panabo City, Davao del Norte'),
(47, 'Don Manuel A. Javellana Memorial National High School', 'Panabo City, Davao del Norte'),
(48, 'Malativas National High School',                        'Panabo City, Davao del Norte'),
(49, 'Manay National High School',                            'Panabo City, Davao del Norte'),
(50, 'Panabo City National High School',                      'Panabo City, Davao del Norte'),
(51, 'Little Panay National High School',                     'Panabo City, Davao del Norte'),
(52, 'Kasilak National High School',                          'Panabo City, Davao del Norte'),
(53, 'Cagangohan National High School',                       'Panabo City, Davao del Norte'),
(54, 'Desiderio F. Dalisay Sr. National High School',         'Panabo City, Davao del Norte'),
(55, 'Nanyo National High School',                            'Panabo City, Davao del Norte'),
(56, 'Southern Davao National High School',                   'Panabo City, Davao del Norte'),
(57, 'San Vicente National High School',                      'Panabo City, Davao del Norte'),
(58, 'Kauswagan National High School',                        'Panabo City, Davao del Norte'),
(59, 'Mabunao National High School',                          'Panabo City, Davao del Norte'),
(60, 'Quezon National High School',                           'Panabo City, Davao del Norte'),
-- Senior High School
(61, 'Panabo City Senior High School',                        'Panabo City, Davao del Norte'),
-- Integrated School
(62, 'Lorenzo T. Concepcion Integrated School',               'Panabo City, Davao del Norte');

-- ============================================================
-- USERS (password: password)
-- ============================================================
INSERT INTO users (name, email, password, role, school_id) VALUES
('Super Administrator',  'admin@sfpms.edu.ph', 'password', 'super_admin',   NULL),
('Maria Santos',         'maria@sfpms.edu.ph', 'password', 'school_admin',  1),
('Juan dela Cruz',       'juan@sfpms.edu.ph',  'password', 'teacher',       2);

-- ============================================================
-- BENEFICIARIES (School 1: Panabo Central ES, School 2: New Visayas CES)
-- ============================================================
INSERT INTO beneficiaries (lrn, first_name, last_name, birthdate, sex, grade_level, section, school_id, status) VALUES
-- Panabo Central ES SPED Center
('100000000001', 'Ana',     'Reyes',      '2016-03-12', 'Female', 'Grade 3', 'Sampaguita', 1, 'Active'),
('100000000002', 'Carlos',  'Mendoza',    '2015-07-22', 'Male',   'Grade 4', 'Orchid',     1, 'Active'),
('100000000003', 'Liza',    'Garcia',     '2016-01-05', 'Female', 'Grade 3', 'Sampaguita', 1, 'Active'),
('100000000004', 'Mark',    'Torres',     '2015-11-18', 'Male',   'Grade 4', 'Orchid',     1, 'Active'),
('100000000005', 'Sofia',   'Villanueva', '2017-04-30', 'Female', 'Grade 2', 'Dahlia',     1, 'Active'),
('100000000006', 'Jose',    'Aquino',     '2017-02-14', 'Male',   'Grade 2', 'Dahlia',     1, 'Active'),
('100000000007', 'Maria',   'Bautista',   '2014-09-09', 'Female', 'Grade 5', 'Rose',       1, 'Active'),
('100000000008', 'Pedro',   'Santiago',   '2014-06-21', 'Male',   'Grade 5', 'Rose',       1, 'Active'),
('100000000009', 'Rosa',    'Dela Cruz',  '2018-08-03', 'Female', 'Grade 1', 'Lily',       1, 'Active'),
('100000000010', 'Antonio', 'Flores',     '2018-12-25', 'Male',   'Grade 1', 'Lily',       1, 'Active'),
-- New Visayas Central ES
('200000000001', 'Elena',   'Ramos',      '2016-05-17', 'Female', 'Grade 3', 'Sunflower',  2, 'Active'),
('200000000002', 'Ramon',   'Castillo',   '2015-08-11', 'Male',   'Grade 4', 'Jasmine',    2, 'Active'),
('200000000003', 'Isabel',  'Morales',    '2016-02-28', 'Female', 'Grade 3', 'Sunflower',  2, 'Active'),
('200000000004', 'Miguel',  'Navarro',    '2015-10-07', 'Male',   'Grade 4', 'Jasmine',    2, 'Active'),
('200000000005', 'Carmen',  'Jimenez',    '2017-06-15', 'Female', 'Grade 2', 'Tulip',      2, 'Active'),
('200000000006', 'Luis',    'Fernandez',  '2017-03-22', 'Male',   'Grade 2', 'Tulip',      2, 'Active'),
('200000000007', 'Gloria',  'Herrera',    '2014-11-30', 'Female', 'Grade 5', 'Lotus',      2, 'Active'),
('200000000008', 'Roberto', 'Guerrero',   '2014-07-19', 'Male',   'Grade 5', 'Lotus',      2, 'Active'),
('200000000009', 'Teresa',  'Aguilar',    '2018-09-14', 'Female', 'Grade 1', 'Violet',     2, 'Active'),
('200000000010', 'Eduardo', 'Peralta',    '2018-04-02', 'Male',   'Grade 1', 'Violet',     2, 'Active');

-- ============================================================
-- FEEDING SESSIONS (5 per school)
-- ============================================================
INSERT INTO feeding_sessions (id, school_id, session_date, meal_type, created_by) VALUES
(1,  1, '2025-04-28', 'Lunch', 2),
(2,  1, '2025-04-29', 'Lunch', 2),
(3,  1, '2025-04-30', 'Lunch', 2),
(4,  1, '2025-05-02', 'Lunch', 2),
(5,  1, '2025-05-05', 'Lunch', 2),
(6,  2, '2025-04-28', 'Lunch', 3),
(7,  2, '2025-04-29', 'Lunch', 3),
(8,  2, '2025-04-30', 'Lunch', 3),
(9,  2, '2025-05-02', 'Lunch', 3),
(10, 2, '2025-05-05', 'Lunch', 3);

-- ============================================================
-- ATTENDANCE
-- ============================================================
INSERT INTO feeding_attendance (session_id, beneficiary_id, present) VALUES
(1,1,1),(1,2,1),(1,3,1),(1,4,1),(1,5,1),(1,6,1),(1,7,1),(1,8,1),(1,9,0),(1,10,0),
(2,1,1),(2,2,1),(2,3,0),(2,4,1),(2,5,1),(2,6,1),(2,7,1),(2,8,1),(2,9,0),(2,10,0),
(3,1,1),(3,2,1),(3,3,1),(3,4,1),(3,5,1),(3,6,1),(3,7,0),(3,8,1),(3,9,0),(3,10,0),
(4,1,1),(4,2,1),(4,3,1),(4,4,1),(4,5,0),(4,6,1),(4,7,1),(4,8,1),(4,9,1),(4,10,0),
(5,1,1),(5,2,1),(5,3,1),(5,4,1),(5,5,1),(5,6,1),(5,7,1),(5,8,1),(5,9,1),(5,10,1),
(6,11,1),(6,12,1),(6,13,1),(6,14,1),(6,15,1),(6,16,1),(6,17,1),(6,18,1),(6,19,0),(6,20,0),
(7,11,1),(7,12,0),(7,13,1),(7,14,1),(7,15,1),(7,16,1),(7,17,1),(7,18,1),(7,19,0),(7,20,0),
(8,11,1),(8,12,1),(8,13,1),(8,14,0),(8,15,1),(8,16,1),(8,17,1),(8,18,1),(8,19,0),(8,20,0),
(9,11,1),(9,12,1),(9,13,1),(9,14,1),(9,15,0),(9,16,1),(9,17,0),(9,18,1),(9,19,1),(9,20,0),
(10,11,1),(10,12,1),(10,13,1),(10,14,1),(10,15,1),(10,16,1),(10,17,1),(10,18,1),(10,19,1),(10,20,1);

-- ============================================================
-- NUTRITIONAL RECORDS
-- ============================================================
INSERT INTO nutritional_records (beneficiary_id, record_date, weight_kg, height_cm, recorded_by) VALUES
(1,'2025-04-28',22.5,118.0,2),(2,'2025-04-28',27.0,124.5,2),(3,'2025-04-28',19.8,115.0,2),
(4,'2025-04-28',31.2,128.0,2),(5,'2025-04-28',16.5,108.0,2),(6,'2025-04-28',17.2,110.5,2),
(7,'2025-04-28',35.0,135.0,2),(8,'2025-04-28',33.5,133.0,2),(9,'2025-04-28',12.8,98.5,2),
(10,'2025-04-28',13.5,100.0,2),(11,'2025-04-28',21.0,117.0,3),(12,'2025-04-28',28.5,126.0,3),
(13,'2025-04-28',20.3,116.0,3),(14,'2025-04-28',30.0,127.5,3),(15,'2025-04-28',15.9,107.0,3),
(16,'2025-04-28',16.8,109.0,3),(17,'2025-04-28',36.5,136.5,3),(18,'2025-04-28',34.2,134.0,3),
(19,'2025-04-28',11.5,97.0,3),(20,'2025-04-28',14.2,101.5,3);

-- ============================================================
-- INVENTORY
-- ============================================================
INSERT INTO inventory (school_id, item_name, unit, quantity, low_stock_threshold) VALUES
(1,'Rice','kg',45.00,20.00),(1,'Canned Goods','pcs',8.00,15.00),
(1,'Cooking Oil','L',12.00,5.00),(1,'Salt','kg',3.00,2.00),(1,'Vegetables','kg',7.00,10.00),
(2,'Rice','kg',60.00,20.00),(2,'Canned Goods','pcs',25.00,15.00),
(2,'Cooking Oil','L',4.00,5.00),(2,'Salt','kg',5.00,2.00),(2,'Vegetables','kg',9.00,10.00);
