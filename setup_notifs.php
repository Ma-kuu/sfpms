<?php
require_once __DIR__ . '/config/db.php';
$pdo = getPDO();

$pdo->exec("
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
");
echo "Table created.";
