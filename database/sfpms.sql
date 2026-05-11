-- SFPMS Database — Panabo City Division
-- Drop and recreate for clean seed

DROP DATABASE IF EXISTS sfpms;
CREATE DATABASE sfpms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sfpms;

-- Schools
CREATE TABLE schools (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(200) NOT NULL,
    address     VARCHAR(300),
    status      ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Users
CREATE TABLE users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(120) NOT NULL,
    email       VARCHAR(180) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('super_admin','school_admin','teacher') NOT NULL DEFAULT 'teacher',
    school_id   INT UNSIGNED NULL,
    grade_level VARCHAR(10)  NULL,
    section     VARCHAR(50)  NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Beneficiaries
CREATE TABLE beneficiaries (
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
    guardian_contact VARCHAR(20) NULL, 
    last_nutritional_check DATE NULL, 
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Feeding Sessions
CREATE TABLE feeding_sessions (
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

-- Feeding Attendance
CREATE TABLE feeding_attendance (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id      INT UNSIGNED NOT NULL,
    beneficiary_id  INT UNSIGNED NOT NULL,
    present         TINYINT(1) NOT NULL DEFAULT 0,
    UNIQUE KEY uq_session_beneficiary (session_id, beneficiary_id),
    FOREIGN KEY (session_id)     REFERENCES feeding_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id)    ON DELETE CASCADE
) ENGINE=InnoDB;

-- Nutritional Records
CREATE TABLE nutritional_records (
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

-- Inventory
CREATE TABLE inventory (
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
-- SEED DATA — PANABO CITY SCHOOLS
-- ============================================================
INSERT INTO schools (id, name, address) VALUES
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
(61, 'Panabo City Senior High School',                        'Panabo City, Davao del Norte'),
(62, 'Lorenzo T. Concepcion Integrated School',               'Panabo City, Davao del Norte');

-- =====================================================
-- SEED: Users (password: password)
-- =====================================================
INSERT INTO users (name, email, password, role, school_id) VALUES
('Super Administrator', 'admin@sfpms.edu.ph', 'password', 'super_admin', NULL);

-- Generate School Admins (1 for each school)
INSERT INTO users (name, email, password, role, school_id) VALUES
('Admin School 1', 'admin1@sfpms.edu.ph', 'password', 'school_admin', 1),
('Admin School 2', 'admin2@sfpms.edu.ph', 'password', 'school_admin', 2),
('Admin School 3', 'admin3@sfpms.edu.ph', 'password', 'school_admin', 3),
('Admin School 4', 'admin4@sfpms.edu.ph', 'password', 'school_admin', 4),
('Admin School 5', 'admin5@sfpms.edu.ph', 'password', 'school_admin', 5),
('Admin School 6', 'admin6@sfpms.edu.ph', 'password', 'school_admin', 6),
('Admin School 7', 'admin7@sfpms.edu.ph', 'password', 'school_admin', 7),
('Admin School 8', 'admin8@sfpms.edu.ph', 'password', 'school_admin', 8),
('Admin School 9', 'admin9@sfpms.edu.ph', 'password', 'school_admin', 9),
('Admin School 10', 'admin10@sfpms.edu.ph', 'password', 'school_admin', 10),
('Admin School 11', 'admin11@sfpms.edu.ph', 'password', 'school_admin', 11),
('Admin School 12', 'admin12@sfpms.edu.ph', 'password', 'school_admin', 12),
('Admin School 13', 'admin13@sfpms.edu.ph', 'password', 'school_admin', 13),
('Admin School 14', 'admin14@sfpms.edu.ph', 'password', 'school_admin', 14),
('Admin School 15', 'admin15@sfpms.edu.ph', 'password', 'school_admin', 15),
('Admin School 16', 'admin16@sfpms.edu.ph', 'password', 'school_admin', 16),
('Admin School 17', 'admin17@sfpms.edu.ph', 'password', 'school_admin', 17),
('Admin School 18', 'admin18@sfpms.edu.ph', 'password', 'school_admin', 18),
('Admin School 19', 'admin19@sfpms.edu.ph', 'password', 'school_admin', 19),
('Admin School 20', 'admin20@sfpms.edu.ph', 'password', 'school_admin', 20),
('Admin School 21', 'admin21@sfpms.edu.ph', 'password', 'school_admin', 21),
('Admin School 22', 'admin22@sfpms.edu.ph', 'password', 'school_admin', 22),
('Admin School 23', 'admin23@sfpms.edu.ph', 'password', 'school_admin', 23),
('Admin School 24', 'admin24@sfpms.edu.ph', 'password', 'school_admin', 24),
('Admin School 25', 'admin25@sfpms.edu.ph', 'password', 'school_admin', 25),
('Admin School 26', 'admin26@sfpms.edu.ph', 'password', 'school_admin', 26),
('Admin School 27', 'admin27@sfpms.edu.ph', 'password', 'school_admin', 27),
('Admin School 28', 'admin28@sfpms.edu.ph', 'password', 'school_admin', 28),
('Admin School 29', 'admin29@sfpms.edu.ph', 'password', 'school_admin', 29),
('Admin School 30', 'admin30@sfpms.edu.ph', 'password', 'school_admin', 30),
('Admin School 31', 'admin31@sfpms.edu.ph', 'password', 'school_admin', 31),
('Admin School 32', 'admin32@sfpms.edu.ph', 'password', 'school_admin', 32),
('Admin School 33', 'admin33@sfpms.edu.ph', 'password', 'school_admin', 33),
('Admin School 34', 'admin34@sfpms.edu.ph', 'password', 'school_admin', 34),
('Admin School 35', 'admin35@sfpms.edu.ph', 'password', 'school_admin', 35),
('Admin School 36', 'admin36@sfpms.edu.ph', 'password', 'school_admin', 36),
('Admin School 37', 'admin37@sfpms.edu.ph', 'password', 'school_admin', 37),
('Admin School 38', 'admin38@sfpms.edu.ph', 'password', 'school_admin', 38),
('Admin School 39', 'admin39@sfpms.edu.ph', 'password', 'school_admin', 39),
('Admin School 40', 'admin40@sfpms.edu.ph', 'password', 'school_admin', 40),
('Admin School 41', 'admin41@sfpms.edu.ph', 'password', 'school_admin', 41),
('Admin School 42', 'admin42@sfpms.edu.ph', 'password', 'school_admin', 42),
('Admin School 43', 'admin43@sfpms.edu.ph', 'password', 'school_admin', 43),
('Admin School 44', 'admin44@sfpms.edu.ph', 'password', 'school_admin', 44),
('Admin School 45', 'admin45@sfpms.edu.ph', 'password', 'school_admin', 45),
('Admin School 46', 'admin46@sfpms.edu.ph', 'password', 'school_admin', 46),
('Admin School 47', 'admin47@sfpms.edu.ph', 'password', 'school_admin', 47),
('Admin School 48', 'admin48@sfpms.edu.ph', 'password', 'school_admin', 48),
('Admin School 49', 'admin49@sfpms.edu.ph', 'password', 'school_admin', 49),
('Admin School 50', 'admin50@sfpms.edu.ph', 'password', 'school_admin', 50),
('Admin School 51', 'admin51@sfpms.edu.ph', 'password', 'school_admin', 51),
('Admin School 52', 'admin52@sfpms.edu.ph', 'password', 'school_admin', 52),
('Admin School 53', 'admin53@sfpms.edu.ph', 'password', 'school_admin', 53),
('Admin School 54', 'admin54@sfpms.edu.ph', 'password', 'school_admin', 54),
('Admin School 55', 'admin55@sfpms.edu.ph', 'password', 'school_admin', 55),
('Admin School 56', 'admin56@sfpms.edu.ph', 'password', 'school_admin', 56),
('Admin School 57', 'admin57@sfpms.edu.ph', 'password', 'school_admin', 57),
('Admin School 58', 'admin58@sfpms.edu.ph', 'password', 'school_admin', 58),
('Admin School 59', 'admin59@sfpms.edu.ph', 'password', 'school_admin', 59),
('Admin School 60', 'admin60@sfpms.edu.ph', 'password', 'school_admin', 60),
('Admin School 61', 'admin61@sfpms.edu.ph', 'password', 'school_admin', 61),
('Admin School 62', 'admin62@sfpms.edu.ph', 'password', 'school_admin', 62);

-- Teachers (School 1: Elementary, Grades 1-6)
INSERT INTO users (name, email, password, role, school_id, grade_level, section) VALUES
('Ana Bautista',    'ana.bautista@sfpms.edu.ph',    'password', 'teacher', 1, 'Grade 1', 'Lily'),
('Pedro Santiago',  'pedro.santiago@sfpms.edu.ph',  'password', 'teacher', 1, 'Grade 2', 'Dahlia'),
('Rosa Garcia',     'rosa.garcia@sfpms.edu.ph',     'password', 'teacher', 1, 'Grade 3', 'Sampaguita'),
('Carlos Flores',   'carlos.flores@sfpms.edu.ph',   'password', 'teacher', 1, 'Grade 4', 'Orchid'),
('Sofia Torres',    'sofia.torres@sfpms.edu.ph',    'password', 'teacher', 1, 'Grade 5', 'Rose'),
('Miguel Navarro',  'miguel.navarro@sfpms.edu.ph',  'password', 'teacher', 1, 'Grade 6', 'Tulip');

-- Teachers (School 45: High School, Grades 7-12)
INSERT INTO users (name, email, password, role, school_id, grade_level, section) VALUES
('Felipe Dizon',    'felipe.dizon@sfpms.edu.ph',    'password', 'teacher', 45, 'Grade 7',  'Apolinario'),
('Lorena Gomez',    'lorena.gomez@sfpms.edu.ph',    'password', 'teacher', 45, 'Grade 8',  'Jacinto'),
('Ricardo Lim',     'ricardo.lim@sfpms.edu.ph',     'password', 'teacher', 45, 'Grade 9',  'Bonifacio'),
('Bea Alonzo',      'bea.alonzo@sfpms.edu.ph',      'password', 'teacher', 45, 'Grade 10', 'Mabini'),
('Dingdong Dantes', 'dingdong.dantes@sfpms.edu.ph', 'password', 'teacher', 45, 'Grade 11', 'Rizal'),
('Marian Rivera',   'marian.rivera@sfpms.edu.ph',   'password', 'teacher', 45, 'Grade 12', 'Quezon');

-- =====================================================
-- SEED: Beneficiaries
-- =====================================================
INSERT INTO beneficiaries (lrn, first_name, last_name, birthdate, sex, grade_level, section, school_id, status, last_nutritional_check) VALUES
-- School 1: Elementary
('100000000001', 'Juan', 'Dela Cruz', '2016-01-01', 'Male', 'Grade 1', 'Lily', 1, 'Active', '2025-05-01'),
('100000000002', 'Maria', 'Santos', '2016-02-02', 'Female', 'Grade 1', 'Lily', 1, 'Active', '2025-05-01'),
('100000000003', 'Jose', 'Rizal', '2015-03-03', 'Male', 'Grade 2', 'Dahlia', 1, 'Active', '2025-05-01'),
('100000000004', 'Andres', 'Bonifacio', '2015-04-04', 'Male', 'Grade 2', 'Dahlia', 1, 'Active', '2025-05-01'),
('100000000005', 'Emilio', 'Aguinaldo', '2014-05-05', 'Male', 'Grade 3', 'Sampaguita', 1, 'Active', '2025-05-01'),
('100000000006', 'Apolinario', 'Mabini', '2014-06-06', 'Male', 'Grade 3', 'Sampaguita', 1, 'Active', '2025-05-01'),
('100000000007', 'Melchora', 'Aquino', '2013-07-07', 'Female', 'Grade 4', 'Orchid', 1, 'Active', '2025-05-01'),
('100000000008', 'Gabriela', 'Silang', '2013-08-08', 'Female', 'Grade 4', 'Orchid', 1, 'Active', '2025-05-01'),
('100000000009', 'Lapu', 'Lapu', '2012-09-09', 'Male', 'Grade 5', 'Rose', 1, 'Active', '2025-05-01'),
('100000000010', 'Teresa', 'Magbanua', '2012-10-10', 'Female', 'Grade 5', 'Rose', 1, 'Active', '2025-05-01'),
('100000000011', 'Diego', 'Silang', '2011-11-11', 'Male', 'Grade 6', 'Tulip', 1, 'Active', '2025-05-01'),
('100000000012', 'Gregoria', 'De Jesus', '2011-12-12', 'Female', 'Grade 6', 'Tulip', 1, 'Active', '2025-05-01'),
-- School 45: High School
('450000000001', 'Antonio', 'Luna', '2010-01-01', 'Male', 'Grade 7', 'Apolinario', 45, 'Active', '2025-05-01'),
('450000000002', 'Juan', 'Luna', '2010-02-02', 'Male', 'Grade 7', 'Apolinario', 45, 'Active', '2025-05-01'),
('450000000003', 'Marcelo', 'Del Pilar', '2009-03-03', 'Male', 'Grade 8', 'Jacinto', 45, 'Active', '2025-05-01'),
('450000000004', 'Graciano', 'Lopez Jaena', '2009-04-04', 'Male', 'Grade 8', 'Jacinto', 45, 'Active', '2025-05-01'),
('450000000005', 'Miguel', 'Malvar', '2008-05-05', 'Male', 'Grade 9', 'Bonifacio', 45, 'Active', '2025-05-01'),
('450000000006', 'Vicente', 'Lim', '2008-06-06', 'Male', 'Grade 9', 'Bonifacio', 45, 'Active', '2025-05-01'),
('450000000007', 'Josefa', 'Llanes Escoda', '2007-07-07', 'Female', 'Grade 10', 'Mabini', 45, 'Active', '2025-05-01'),
('450000000008', 'Vicente', 'Lukban', '2007-08-08', 'Male', 'Grade 10', 'Mabini', 45, 'Active', '2025-05-01'),
('450000000009', 'Macario', 'Sakay', '2006-09-09', 'Male', 'Grade 11', 'Rizal', 45, 'Active', '2025-05-01'),
('450000000010', 'Artemio', 'Ricarte', '2006-10-10', 'Male', 'Grade 11', 'Rizal', 45, 'Active', '2025-05-01'),
('450000000011', 'Leon', 'Kilat', '2005-11-11', 'Male', 'Grade 12', 'Quezon', 45, 'Active', '2025-05-01'),
('450000000012', 'Francisco', 'Dagohoy', '2005-12-12', 'Male', 'Grade 12', 'Quezon', 45, 'Active', '2025-05-01');

-- =====================================================
-- SEED: Inventory
-- =====================================================
INSERT INTO inventory (school_id, item_name, unit, quantity, low_stock_threshold) VALUES
(1,'Rice','kg',45.00,20.00),(1,'Canned Goods','pcs',8.00,15.00),
(1,'Cooking Oil','L',12.00,5.00),(1,'Salt','kg',3.00,2.00),(1,'Vegetables','kg',7.00,10.00),
(45,'Rice','kg',60.00,20.00),(45,'Canned Goods','pcs',25.00,15.00),
(45,'Cooking Oil','L',4.00,5.00),(45,'Salt','kg',5.00,2.00),(45,'Vegetables','kg',9.00,10.00);

-- =====================================================
-- SEED: Nutritional Records
-- =====================================================
INSERT INTO nutritional_records (beneficiary_id, record_date, weight_kg, height_cm, recorded_by) VALUES
(1,'2025-05-01',18.5,110.0,64),(2,'2025-05-01',17.5,108.0,64),
(3,'2025-05-01',20.0,115.0,65),(4,'2025-05-01',21.0,116.0,65),
(5,'2025-05-01',25.0,122.0,66),(6,'2025-05-01',24.0,120.0,66),
(7,'2025-05-01',28.0,130.0,67),(8,'2025-05-01',27.5,129.0,67),
(9,'2025-05-01',32.0,135.0,68),(10,'2025-05-01',33.0,136.0,68),
(11,'2025-05-01',35.0,140.0,69),(12,'2025-05-01',34.0,139.0,69),
(13,'2025-05-01',40.0,145.0,70),(14,'2025-05-01',42.0,148.0,70),
(15,'2025-05-01',45.0,152.0,71),(16,'2025-05-01',46.0,153.0,71),
(17,'2025-05-01',50.0,158.0,72),(18,'2025-05-01',49.0,157.0,72),
(19,'2025-05-01',55.0,162.0,73),(20,'2025-05-01',54.0,161.0,73),
(21,'2025-05-01',60.0,165.0,74),(22,'2025-05-01',61.0,166.0,74),
(23,'2025-05-01',65.0,170.0,75),(24,'2025-05-01',64.0,169.0,75);


-- Notifications Table
CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  school_id INT NULL,
  role VARCHAR(50) NULL,
  user_id INT NULL,
  type VARCHAR(50) NOT NULL,
  icon VARCHAR(50) NOT NULL,
  message TEXT NOT NULL,
  link VARCHAR(255) NOT NULL,
  is_read TINYINT(1) DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY (school_id),
  KEY (user_id),
  KEY (role)
);
